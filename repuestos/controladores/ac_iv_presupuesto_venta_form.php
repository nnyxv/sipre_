<?php


function asignarArticulo($hddNumeroArt, $idArticulo, $frmDcto, $precioUnitario = "", $frmListaArticulo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	
	$objResponse->script("
	if (!inArray(byId('lstBuscarArticulo').value, [6,7])) {
		document.forms['frmDatosArticulo'].reset();
		byId('txtDescripcionArt').innerHTML = '';
	}");
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$txtCantidadArt = ($hddNumeroArt > 0) ? 0 : 1;
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	$objResponse->script("
	byId('tdUbicacion').style.visibility = '".((!in_array($ResultConfig12, array(1,2))) ? "hidden" : "")."';
	byId('tdlstUbicacion').style.visibility = '".((!in_array($ResultConfig12, array(1,2))) ? "hidden" : "")."';
	byId('tdMsjArticulo').style.display = 'none';");
	
	// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
	$existe = false;
	if (isset($arrayObjPieArticulo)) {
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			if ($frmListaArticulo['hddIdArticuloItm'.$valorPieArticulo] == $idArticulo) {
				// BUSCA LOS LOTES DEL ARTICULO
				$sqlBusq = "";
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
				AND vw_iv_art_almacen_costo.id_empresa = %s
				AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
				AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo = %s", 
					valTpDato($frmListaArticulo['hddIdArticuloCosto'.$valorPieArticulo], "int"));
				
				if (!in_array($ResultConfig12, array(1,2))) {
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
				}
				
				$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
				ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", $sqlBusq);
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
				$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				
				if ($totalRowsArtCosto > 0) {
					$hddNumeroArt = $valorPieArticulo;
				}
			}
		}
	}
	
	// BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT vw_iv_art_emp.*,
		
		(SELECT sec.descripcion
		FROM iv_subsecciones subsec
			INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
		WHERE subsec.id_subseccion = vw_iv_art_emp.id_subseccion) AS descripcion_seccion,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_emp.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_emp.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta
		
	FROM vw_iv_articulos_empresa vw_iv_art_emp
	WHERE vw_iv_art_emp.id_articulo = %s
		AND vw_iv_art_emp.id_empresa = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
	FROM pg_iva iva
		INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
	WHERE art_impuesto.id_articulo = %s
		AND iva.tipo IN (6,9,2)
		AND art_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
											WHERE cliente_imp_exento.id_cliente = %s);", 
		valTpDato($idArticulo, "int"),
		valTpDato($idCliente, "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$arrayIdIvaArt[] = $rowIva['idIva'];
		$arrayIvaArt[] = $rowIva['iva'];
	}
	
	$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
	$objResponse->assign("txtCantDisponible","value",number_format($rowArticulo['cantidad_disponible_logica'], 2, ".", ","));
	$objResponse->assign("hddIdIvaArt","value",((count($arrayIdIvaArt) > 0) ? implode(",",$arrayIdIvaArt) : ""));
	$objResponse->assign("txtIvaArt","value",((count($arrayIvaArt) > 0) ? implode(", ",$arrayIvaArt) : ""));
	
	$objResponse->script(sprintf("
	if (navigator.appName == 'Netscape') {
		byId('txtCantidadArt').onblur = function(e){ %s }
		byId('txtCantidadArt').onkeypress = function(e){ %s }
	} else if (navigator.appName == 'Microsoft Internet Explorer') {
		byId('txtCantidadArt').onblur = function(e){ %s }
		byId('txtCantidadArt').onkeypress = function(e){ %s }
	}",
		(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(e);" : "return validarSoloNumerosReales(e);"),
		(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(event);" : "return validarSoloNumerosReales(event);")));
	
	$objResponse->script("byId('txtCantDisponible').className = '".(($rowArticulo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
	
	if (!($rowArticulo['cantidad_disponible_logica'] > 0)) {
		$htmlMsj = "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
		$htmlMsj .= "<tr>";
			$htmlMsj .= "<td width=\"25\"><img src=\"../img/iconos/error.png\"/></td>";
			$htmlMsj .= "<td align=\"center\">";
				$htmlMsj .= utf8_encode("El artículo ( ".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")." ) no tiene disponibilidad. Para ver los articulos que lo sustituyen presione ")."<a class=\"modalImg linkAzulUnderline puntero\" id=\"aSustituto\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblArticuloSustituto', '".$idArticulo."', '".$idEmpresa."');\">".utf8_encode("aquí")."</a>";
			$htmlMsj .= "</td>";
		$htmlMsj .= "</tr>";
		$htmlMsj .= "</table>";
		
		$objResponse->script("byId('tdMsjArticulo').style.display = '';");
		$objResponse->assign("tdMsjArticulo","innerHTML",$htmlMsj);
	}
	
	if ($hddNumeroArt > 0) { // SI EL ARTICULO YA ESTA AGREGADO EN LA LISTA
		$arrayIdIvaArt = NULL;
		$arrayIvaArt = NULL;
		for ($contFilaIva = 1; isset($frmListaArticulo['hddIdIvaItm'.$hddNumeroArt.":".$contFilaIva]); $contFilaIva++) {
			$arrayIdIvaArt[] = $frmListaArticulo['hddIdIvaItm'.$hddNumeroArt.":".$contFilaIva];
			$arrayIvaArt[] = $frmListaArticulo['hddIvaItm'.$hddNumeroArt.":".$contFilaIva];
		}
		
		$objResponse->assign("txtCantidadArt","value",str_replace(",", "", $frmListaArticulo['hddCantItm'.$hddNumeroArt]) + $txtCantidadArt);
		$objResponse->assign("hddIdIvaArt","value",((count($arrayIdIvaArt) > 0) ? implode(",",$arrayIdIvaArt) : ""));
		$objResponse->assign("txtIvaArt","value",((count($arrayIvaArt) > 0) ? implode(", ",$arrayIvaArt) : ""));
		
		$hddIdPrecioItm = $frmListaArticulo['hddIdPrecioItm'.$hddNumeroArt];
		$precioUnitario = str_replace(",", "", $frmListaArticulo['hddPrecioItm'.$hddNumeroArt]);
		$selIdCasilla = $frmListaArticulo['hddIdCasilla'.$hddNumeroArt];
		
		$onChange = "xajax_asignarPrecio('".$idArticulo."', this.value, xajax.getFormValues('frmDcto'), 'false', '".$precioUnitario."');";
	} else { // SI EL ARTICULO NO HA SIDO AGREGADO AUN EN LA LISTA
		$objResponse->assign("txtCantidadArt","value",number_format(0, 2, ".", ","));
		
		$objResponse->script("
		if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
			byId('txtCantidadArt').value++;
		}
		
		if (byId('hddNumeroArt').value > 0) {
			byId('aAgregarArticulo').click();
		}");
		
		if ($precioUnitario > 0) {
			$selIdPrecio = 7;
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
		}
		
		$onChangeHabilitado = "xajax_asignarPrecio('".$idArticulo."', this.value, xajax.getFormValues('frmDcto'));";
	}
	$selIdCasilla = ($selIdCasilla > 0) ? $selIdCasilla : $rowArticulo['id_casilla_predeterminada'];
	
	$objResponse->script("
	byId('txtCantidadArt').focus();
	byId('txtCantidadArt').select();");
	
	// VERIFICACION PARA EL MANEJO DEL PRECIO ESPECIAL PARA CLIENTES
	$selIdPrecio = (!$hddIdPrecioItm) ? $rowArticulo['id_precio_predeterminado'] : $hddIdPrecioItm;
	$queryPrecioArticuloCliente = sprintf("SELECT * FROM iv_articulos_precios_cliente
	WHERE id_cliente = %s
		OR (id_cliente = %s AND id_articulo = %s);",
		valTpDato($idCliente, "int"),
		valTpDato($idCliente, "int"), valTpDato($idArticulo, "int"));
	$rsPrecioArticuloCliente = mysql_query($queryPrecioArticuloCliente);
	if (!$rsPrecioArticuloCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowPrecioArticuloCliente = mysql_fetch_assoc($rsPrecioArticuloCliente)) {	
		if ($rowPrecioArticuloCliente['id_articulo'] == $idArticulo) {
			$selIdPrecio = $rowPrecioArticuloCliente['id_precio'];
			$asigPrecioArt = true;
		} else if ($rowPrecioArticuloCliente['id_articulo'] == "" && !isset($asigPrecioArt)) {
			$selIdPrecio = $rowPrecioArticuloCliente['id_precio'];
		}
	}
	$objResponse->loadCommands(asignarPrecio($idArticulo, $selIdPrecio, $frmDcto, "true", $precioUnitario));
	
	// CARGA LOS PRECIOS DEL ARTICULO
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.estatus IN (1) OR (precio.porcentaje IN ('0','0.01') AND precio.estatus IN (2)) ORDER BY precio.porcentaje DESC, precio.id_precio ASC;");
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$htmlLst = "";
	while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
		$selected = ($selIdPrecio == $rowPrecio['id_precio']) ? "selected=\"selected\"" : "";
		
		$htmlLst .= "<option ".$selected." value=\"".$rowPrecio['id_precio']."\">".utf8_encode($rowPrecio['descripcion_precio'])."</option>";
	}
	
	$onChange .= "
	if (!inArray(this.value, [6,7,12,13,18,".$selIdPrecio."])){
		xajax_asignarPrecio('".$idArticulo."', '".$selIdPrecio."', xajax.getFormValues('frmDcto'));
		selectedOption(this.id,'".$selIdPrecio."');
		byId('aDesbloquearPrecioArt').click();
	} else {".$onChangeHabilitado."}";
	
	$htmlLstIni = "<select id=\"lstPrecioArt\" name=\"lstPrecioArt\" class=\"inputHabilitado\" onchange=\"".$onChange."\" style=\"width:200px\">";
	$htmlLstFin = "</select>";
	$objResponse->assign("tdlstPrecioArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	
	if ($txtCantidadArt > 0 && $hddNumeroArt > 0) { // SI EL ARTICULO YA ESTA AGREGADO EN LA LISTA
		$objResponse->script("xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarArticuloSustituto').click();");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
			valTpDato($estatusCliente, "text"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.status
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento > 0) ? $idClaveMovimiento : $rowCliente['id_clave_movimiento_predeterminado'];
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
		
		$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""));
		
		$objResponse->script("
		byId('aDesbloquearClaveMovimiento').style.display = '';
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
		}");
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""));
		
		$objResponse->script("
		byId('aDesbloquearClaveMovimiento').style.display = '';
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
		}");
	}
	
	if ($rowCliente['id'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarFechaVencimiento($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$fechaVencimiento = dateAddLab(strtotime($frmDcto['txtFechaPresupuesto']),5,true);
	
	$objResponse->assign("txtFechaVencimientoPresupuesto","value",date(spanDateFormat,$fechaVencimiento));
	
	return $objResponse;
}

function asignarGasto($frmLista, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$hddNumeroItm = $frmLista['hddNumeroItm'];
	
	for ($contFilaObj = 1; $contFilaObj <= 20; $contFilaObj++) {
		$txtMontoGasto = str_replace(",", "", $frmLista['txtMontoGasto'.$contFilaObj]);
		
		if (round($txtMontoGasto, 2) > 0) {
			$htmlGastoArt .= sprintf("<input type='hidden' id='hddIdGastoArt:%s:%s' name='hddIdGastoArt:%s:%s' readonly='readonly' value='%s'>",
				$hddNumeroItm, $contFilaObj, $hddNumeroItm, $contFilaObj, $frmLista['hddIdGasto'.$contFilaObj]);
			$htmlGastoArt .= sprintf("<input type='hidden' id='txtMontoGastoArt:%s:%s' name='txtMontoGastoArt:%s:%s' readonly='readonly' value='%s'/>",
				$hddNumeroItm, $contFilaObj, $hddNumeroItm, $contFilaObj, $txtMontoGasto);
			
			$totalGastoArt += $txtMontoGasto;
		}
	}
	
	$objResponse->assign("tdItmGastoObj:".$hddNumeroItm,"innerHTML",$htmlGastoArt);
	
	$objResponse->assign("hddGastoItm".$hddNumeroItm,"value",number_format($totalGastoArt, 2, ".", ","));
	
	$totalArt = (str_replace(",", "", $frmListaArticulo['hddCantItm'.$hddNumeroItm]) * str_replace(",", "", $frmListaArticulo['hddPrecioItm'.$hddNumeroItm])) + $totalGastoArt;
	
	$objResponse->assign("txtTotalItm".$hddNumeroItm,"value",number_format($totalArt, 2, ".", ","));
	
	$objResponse->script("byId('btnCancelarLista').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarPrecio($idArticulo, $idPrecio, $frmDcto, $precioPredet = "false", $precioUnitario = "") {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idMoneda = $frmDcto['lstMoneda'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	// BUSCA LOS LOTES DEL ARTICULO
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s AND art_costo.id_empresa = %s AND art_costo.estatus = 1", 
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
		
	if (!in_array($ResultConfig12, array(1,2))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada - art_costo.cantidad_espera - art_costo.cantidad_bloqueada) > 0");
	}
	$sqlOrderBy = (in_array($ResultConfig12, array(1,2))) ? "ORDER BY art_costo.fecha_registro DESC" : "ORDER BY art_costo.fecha_registro ASC";
	$sqlLimit = (in_array($ResultConfig12, array(1,2))) ? "LIMIT 1" : "";
	
	$queryArtCosto = sprintf("SELECT
		art_costo.id_articulo_costo,
		art_costo.costo,
		art_costo.costo_promedio,
		(art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida) AS cant_existencia,
		(art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada) AS cantidad_disponible_fisica,
		(art_costo.cantidad_inicio + art_costo.cantidad_entrada - art_costo.cantidad_salida - art_costo.cantidad_reservada - art_costo.cantidad_espera - art_costo.cantidad_bloqueada) AS cantidad_disponible_logica,
		art_costo.fecha_registro
	FROM iv_articulos_costos art_costo %s %s %s;",
		$sqlBusq, $sqlOrderBy, $sqlLimit);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
	
	// BUSCA EL PRECIO
	$queryArtPrecio = sprintf("SELECT
		art_precio.id_articulo_precio,
		art_precio.id_precio,
		art_precio.precio AS precio_unitario,
		
		(SELECT iva.observacion
		FROM iv_articulos_impuesto art_impsto
			INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
		WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
			AND art_impsto.id_articulo = art_precio.id_articulo
		LIMIT 1) AS descripcion_impuesto,
		
		(SELECT SUM(iva.iva)
		FROM iv_articulos_impuesto art_impsto
			INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
		WHERE iva.tipo IN (6,9,2)
			AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
		
		(art_precio.precio * (SELECT SUM(iva.iva)
							FROM iv_articulos_impuesto art_impsto
								INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (6,9,2)
								AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
		
		moneda.abreviacion AS abreviacion_moneda
	FROM iv_articulos_precios art_precio
		INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
	WHERE art_precio.id_articulo = %s
		AND (art_precio.id_articulo_costo = %s
			OR (%s IS NULL AND id_articulo_costo IS NULL))
		AND art_precio.id_precio = %s
		AND art_precio.id_moneda = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($rowArtCosto['id_articulo_costo'], "int"),
			valTpDato($rowArtCosto['id_articulo_costo'], "int"),
		valTpDato($idPrecio, "int"),
		valTpDato($idMoneda, "int"));
	$rsArtPrecio = mysql_query($queryArtPrecio);
	if (!$rsArtPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
	
	// BUSCA EL PRECIO SUGERIDO
	$queryArtPrecioSugerido = sprintf("SELECT
		art_precio.id_articulo_precio,
		art_precio.id_precio,
		art_precio.precio AS precio_unitario,
		
		(SELECT iva.observacion
		FROM iv_articulos_impuesto art_impsto
			INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
		WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
			AND art_impsto.id_articulo = art_precio.id_articulo
		LIMIT 1) AS descripcion_impuesto,
		
		(SELECT SUM(iva.iva)
		FROM iv_articulos_impuesto art_impsto
			INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
		WHERE iva.tipo IN (6,9,2)
			AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
		
		(art_precio.precio * (SELECT SUM(iva.iva)
							FROM iv_articulos_impuesto art_impsto
								INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (6,9,2)
								AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
		
		moneda.abreviacion AS abreviacion_moneda
	FROM iv_articulos_precios art_precio
		INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
	WHERE art_precio.id_articulo = %s
		AND art_precio.id_empresa = %s
		AND art_precio.id_precio IN (18)
		AND art_precio.id_moneda = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idMoneda, "int"));
	$rsArtPrecioSugerido = mysql_query($queryArtPrecioSugerido);
	if (!$rsArtPrecioSugerido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtPrecioSugerido = mysql_fetch_assoc($rsArtPrecioSugerido);
	
	$objResponse->assign("hddIdArtPrecio","value",$rowArtPrecio['id_articulo_precio']);
	$precioUnitario = ($rowArtPrecio['precio_unitario'] > 0 && $precioUnitario == "" && !in_array($rowArtPrecio['id_precio'], array(6,7,12,13,18))) ? $rowArtPrecio['precio_unitario'] : $precioUnitario;
	$objResponse->assign("txtPrecioArt","value",$precioUnitario);
	$objResponse->assign("txtPrecioSugerido","value",$rowArtPrecioSugerido['precio_unitario']);
	$objResponse->assign("hddBajarPrecio","value","");
	$objResponse->script("
	byId('txtPrecioArt').readOnly = true;
	byId('txtPrecioSugerido').className = 'inputInicial';
	byId('txtPrecioSugerido').readOnly = true;");
	$objResponse->assign("tdMonedaPrecioArt","innerHTML",$rowArtPrecio['abreviacion_moneda']);
	
	if ($precioPredet == "true") {
		$objResponse->assign("hddIdPrecioArtPredet","value",$rowArtPrecio['id_precio']);
		$objResponse->assign("hddPrecioArtPredet","value",$precioUnitario);
	}
	
	switch($idPrecio) {
		case 6 :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","<a class=\"modalImg\" id=\"aDesbloquearPrecio\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'iv_catalogo_venta_precio_editado');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>");
			break;
		case 7 :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","<a class=\"modalImg\" id=\"aDesbloquearPrecio\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'iv_catalogo_venta_precio_editado_bajar');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>");
			break;
		case 12 :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","<a class=\"modalImg\" id=\"aDesbloquearPrecio\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'iv_precio_editado_debajo_costo');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>");
			break;
		default :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","");
	}
	
	return $objResponse;
}

function bloquearLstClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['pago_contado'] == 1 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 1 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	}
	
	$objResponse->script($accion);

	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	if (isset($frmBuscarArticulo['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscarArticulo['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscarArticulo['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	if (strlen($idEmpresa) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($auxCodArticulo != "---") {
		if ($codArticulo != "-1" && $codArticulo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
				valTpDato($codArticulo, "text"));
		}
	}
	
	if (strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($frmBuscarArticulo['lstBuscarArticulo']) {
			case 1 : $sqlBusq .= $cond.sprintf("marca LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 2 : $sqlBusq .= $cond.sprintf("tipo_articulo LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 3 : $sqlBusq .= $cond.sprintf("descripcion_seccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 4 : $sqlBusq .= $cond.sprintf("descripcion_subseccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 5 : $sqlBusq .= $cond.sprintf("descripcion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 6 : 
				$arrayCriterioBuscarArticulo = explode("A", $frmBuscarArticulo['txtCriterioBuscarArticulo']);
				$txtCriterioBuscarArticulo = $arrayCriterioBuscarArticulo['0'];
				array_shift($arrayCriterioBuscarArticulo);
				$arrayPrecioUnit = explode("Z", $arrayCriterioBuscarArticulo[0]);
				$arrayPrecioUnit = array_reverse($arrayPrecioUnit);
				$precioUnitario = str_replace(",","",implode(".",$arrayPrecioUnit));
				$sqlBusq .= $cond.sprintf("id_articulo = %s", valTpDato($txtCriterioBuscarArticulo, "int"));
				break;
			case 7 : $sqlBusq .= $cond.sprintf("codigo_articulo_prov LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
		}
	}
		
	$objResponse->assign("divListaArticulo","innerHTML","");
	
	if ($auxCodArticulo != "---" || strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$query = sprintf("SELECT id_articulo FROM vw_iv_articulos_empresa_datos_basicos %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows == 1) {
			$row = mysql_fetch_assoc($rs);
			
			$objResponse->loadCommands(asignarArticulo("", $row['id_articulo'], $frmDcto, $precioUnitario, $frmListaArticulo, "false"));
			
			$objResponse->script("byId('txtCriterioBuscarArticulo').value = '';");
		} else if ($totalRows > 1) {
			$valBusq = sprintf("%s|%s|%s|%s",
				$idEmpresa,
				$codArticulo,
				$frmBuscarArticulo['lstBuscarArticulo'],
				$frmBuscarArticulo['txtCriterioBuscarArticulo']);
			
			$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
		} else {
			$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
			$htmlTb .= "<td colspan=\"11\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTblFin .= "</table>";
			
			$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		}
	}
	
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

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $guardarDcto = "false", $calcularDcto = "false", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	if (isset($arrayObjPieArticulo)) {
		$i = 0;
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArticulo:".$valorPieArticulo,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorPieArticulo,"innerHTML",$i);
			
			$objResponse->script("
			var frm = document.forms['frmListaArticulo'];
			for (i = 0; i < frm.length; i++){
				if (frm.elements[i].id == 'cbxItmArticulo'){
					if (frm.elements[i].value == '".$valorPieArticulo."'){
						frm.elements[i].style.display = 'none';
						frm.elements[i].disabled = true;
					}
				}
			}");
			
			$existe = false;
			if (isset($arrayDesbloquear)) {
				foreach($arrayDesbloquear as $indice2 => $valor2) {
					if ($arrayDesbloquear[$indice2][0] == $frmListaArticulo['hddIdArticuloItm'.$valorPieArticulo]) {
						$existe = true;
						$arrayDesbloquear[$indice2][1] = $valorPieArticulo;
					}
				}
			}
			
			if ($existe == false) {
				$arrayDesbloquear[] = array(
					$frmListaArticulo['hddIdArticuloItm'.$valorPieArticulo],
					$valorPieArticulo);
			}
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObjPieArticulo) > 0) ? implode("|",$arrayObjPieArticulo) : ""));
	
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
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if (isset($arrayDesbloquear)) {
		foreach ($arrayDesbloquear as $indice => $valor) {
			$objResponse->script("
			var frm = document.forms['frmListaArticulo'];
			for (i = 0; i < frm.length; i++){
				if (frm.elements[i].id == 'cbxItmArticulo'){
					if (frm.elements[i].value == '".$arrayDesbloquear[$indice][1]."'){
						frm.elements[i].style.display = '';
						frm.elements[i].disabled = false;
					}
				}
			}");
		}
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdPresupuesto'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	
	$idMonedaLocal = $frmDcto['lstMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// VERIFICA VALORES DE CONFIGURACION (Porcentaje Máximo de Descuento)
	$ResultConfig19 = valorConfiguracion(19, $idEmpresa, $_SESSION['idUsuarioSysGts']);
	if ($ResultConfig19[0] != true && strlen($ResultConfig19[1]) > 0) {
		die ($ResultConfig19[1]);
	} else if ($ResultConfig19[0] == true) {
		$ResultConfig19 = $ResultConfig19[1];
	}
	
	if (!($txtDescuento > 0 && $ResultConfig19 != "")) {
		// VERIFICA VALORES DE CONFIGURACION (Porcentaje Máximo de Descuento)
		$ResultConfig19 = valorConfiguracion(19, $idEmpresa);
		if ($ResultConfig19[0] != true && strlen($ResultConfig19[1]) > 0) {
			die ($ResultConfig19[1]);
		} else if ($ResultConfig19[0] == true) {
			$ResultConfig19 = $ResultConfig19[1];
		}
	}
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObjPieArticulo)) {
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valorPieArticulo]);
			
			$txtSubTotal += $txtTotalItm;
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObjPieArticulo)) {
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valorPieArticulo]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valorPieArticulo]);
			
			$subTotalItm = $txtTotalItm;
			$totalDescuentoItm = ($hddTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($subTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$subTotalItm = $subTotalItm - $totalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valorPieArticulo && $hddPagaImpuesto == 1) {
						$arrayIvaItm[$frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valor1[1]];
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
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valorPieArticulo.':'.$arrayIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExento += $subTotalItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valorPieArticulo.':'.$arrayIvaItm[$idIva]]) : $porcIva;
					$subTotalIvaItm = ($subTotalItm * $porcIva) / 100;
					
					$existIva = false;
					if (isset($arrayIva)) {
						foreach ($arrayIva as $indiceIva => $valorIva) {
							if ($arrayIva[$indiceIva][0] == $idIva) {
								$arrayIva[$indiceIva][1] += $subTotalItm;
								$arrayIva[$indiceIva][2] += $subTotalIvaItm;
								$existIva = true;
							}
						}
					}
					
					if ($idIva > 0 && $existIva == false
					&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
						$arrayIva[] = array(
							$idIva,
							$subTotalItm,
							$subTotalIvaItm,
							$porcIva,
							$lujoIva,
							$rowIva['observacion']);
					}
				}
			}
			
			if ($totalRowsIva == 0) {
				$txtTotalExento += $subTotalItm;
			}
			
			$objResponse->assign("txtTotalItm".$valorPieArticulo, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $hddCantRecibItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valorPieArticulo]);
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
					$txtPorcGasto = ($txtSubTotal == 0) ? 0 : str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
					$txtMontoGasto = ($txtSubTotal == 0) ? 0 : $txtPorcGasto * ($txtSubTotal / 100);
					$objResponse->assign('txtMontoGasto'.$valor, "value", number_format($txtMontoGasto, 2, ".", ","));
				} else if ($frmTotalDcto['hddTipoGasto'.$valor] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
					$txtMontoGasto = ($txtSubTotal == 0) ? 0 : str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
					$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
					$objResponse->assign('txtPorcGasto'.$valor, "value", number_format($txtPorcGasto, 2, ".", ","));
				}
				
				$txtMontoGasto = str_replace(",", "", $txtMontoGasto);
				
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);",
					valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valor] : 0;
					
					if (($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIva += $txtMontoGasto; break;
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
							case 1 : $txtGastosConIva += $txtMontoGasto; break;
							default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
						}
					}
				}
				
				if ($totalRowsIva == 0) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIva += $txtMontoGasto; break;
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
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
		$porcDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
		
		$objResponse->script("
		byId('txtDescuento').className = 'inputInicial';
		byId('txtDescuento').readOnly = true;");
		$objResponse->assign("txtDescuento", "value", number_format($porcDescuento, 2, ".", ","));
	} else {
		$porcDescuento = $txtDescuento;
		$objResponse->script("
		byId('txtDescuento').className = 'inputHabilitado';
		/*byId('txtDescuento').readOnly = false;*/");
		//$objResponse->assign("txtDescuento", "value", number_format($porcDescuento, 2, ".", ","));
	}
	
	if ($frmTotalDcto['hddConfig19'] == 1 && $porcDescuento > $ResultConfig19) {
		$porcDescuento = $ResultConfig19;
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));	
		$objResponse->alert(utf8_encode("El porcentaje de descuento supera al máximo permitido."));
	}
	
	$txtSubTotalDescuento = $txtSubTotal * ($porcDescuento / 100);
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);
	
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));	
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva', "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign('txtGastosSinIva', "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGastos = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor2], "int"));
			$rsGastos = mysql_query($queryGastos);
			if (!$rsGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGastos = mysql_fetch_assoc($rsGastos);
			
			if ($rowGastos['id_modo_gasto'] == 1) { // 1 = Nacional
				$objResponse->assign("spnGastoMoneda".$valor2,"innerHTML",$abrevMonedaLocal);
			}
			
			if (($rowGastos['id_modo_gasto'] == 1 && $rowMonedaLocal['incluir_impuestos'] == 0)) {				// 1 = Nacional && 0 = No
				$objResponse->script("byId('trIvaGasto".$valor2."').style.visibility = 'hidden';");
			} else if (($rowGastos['id_modo_gasto'] == 1 && $rowMonedaLocal['incluir_impuestos'] == 1)) {		// 1 = Nacional && 1 = Si
				$objResponse->script("byId('trIvaGasto".$valor2."').style.visibility = '';");
			}
		}
	}
	
	$objResponse->assign("tdSubTotalMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdDescuentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdGastoConIvaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdGastoSinIvaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalRegistroMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalFacturaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExoneradoMoneda", "innerHTML", $abrevMonedaLocal);
	
	if (count($arrayObjPieArticulo) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpleado').className = 'inputInicial';
		byId('txtIdEmpleado').readOnly = true;
		if (byId('txtIdEmpleado').value > 0) {
			byId('aListarEmpleado').style.display = 'none'
		}
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		 
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$idMonedaLocal.");
		}");
	} else { // SI NO TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdEmpleado').readOnly = false;
		byId('aListarEmpleado').style.display = '';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdCliente').readOnly = false;
		byId('aListarCliente').style.display = '';
		
		byId('lstMoneda').className = 'inputHabilitado';
		byId('lstMoneda').onchange = function () { }");
	}
	
	if (in_array($calcularDcto, array("1", "true"))) { // FORMAS EN QUE ACEPTA VALOR TRUE DESDE PHP Y JAVASCRIPT
		usleep(1 * 1000000);
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
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
		$totalRowsClaveMov = mysql_num_rows($rsClaveMov);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento'] || $totalRowsClaveMov == 1) {
				$selected = "selected=\"selected\"";
				
				$nombreObjeto2 = (substr($nombreObjeto,strlen($nombreObjeto)-4,strlen($nombreObjeto)) == "Pres") ? "Pres": "";
				
				$objResponse->loadCommands(bloquearLstClaveMovimiento($rowClaveMov['id_clave_movimiento'], $nombreObjeto2));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
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
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
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

function cargarDcto($idDocumento) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('txtObservacion').className = 'inputHabilitado';");
	
	if ($idDocumento > 0) {
		$queryPresupuesto = sprintf("SELECT * FROM vw_iv_presupuestos_venta WHERE id_presupuesto_venta = %s;",
			valTpDato($idDocumento, "int"));
		$rsPresupuesto = mysql_query($queryPresupuesto);
		if (!$rsPresupuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPresupuesto = mysql_fetch_assoc($rsPresupuesto);
		
		$idCliente = $rowPresupuesto['id_cliente'];
			
		if ($rowPresupuesto['estatus_presupuesto_venta'] != 0) {
			return $objResponse->script("
			alert('".utf8_encode("El presupuesto no puede ser abierto por el estatus que tiene")."');
			top.history.back();");
		}
		
		if ($rowPresupuesto['id_presupuesto_venta'] > 0) {
			$queryPresupuestoDet = sprintf("SELECT * FROM iv_presupuesto_venta_detalle WHERE id_presupuesto_venta = %s
			ORDER BY id_presupuesto_venta_detalle ASC;",
				valTpDato($idDocumento, "int"));
			$rsPresupuestoDet = mysql_query($queryPresupuestoDet);
			if (!$rsPresupuestoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayObj = NULL;
			while ($rowPresupuestoDet = mysql_fetch_assoc($rsPresupuestoDet)) {
				$Result1 = insertarItemArticulo($contFila, "", $rowPresupuestoDet['id_presupuesto_venta_detalle'], $idCliente, $rowPresupuestoDet['id_articulo'], "", "", $rowPresupuestoDet['id_articulo_costo'], $rowPresupuestoDet['cantidad'], $rowPresupuestoDet['pendiente'], $rowPresupuestoDet['id_precio'], $rowPresupuestoDet['precio_unitario'], $rowPresupuestoDet['precio_sugerido'], "", "", $rowPresupuestoDet['id_iva']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			}
			
			$objResponse->assign("tdGastos","innerHTML",formularioGastos(false,$rowPresupuesto['id_presupuesto_venta'],"PRESUPUESTO"));
			$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $rowPresupuesto['id_empresa']));
			
			// DATOS DEL CLIENTE
			$objResponse->loadCommands(asignarCliente($rowPresupuesto['id_cliente'], $rowPresupuesto['id_empresa'], "", $rowPresupuesto['condicion_pago'], $rowPresupuesto['id_clave_movimiento'], "false"));
			
			// DATOS DEL PRESUPUESTO
			$objResponse->loadCommands(asignarEmpresaUsuario($rowPresupuesto['id_empresa'], "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'false', 'true');"));
			$objResponse->assign("txtIdPresupuesto","value",utf8_encode($rowPresupuesto['id_presupuesto_venta']));
			$objResponse->assign("txtFechaPresupuesto","value",date(spanDateFormat,strtotime($rowPresupuesto['fecha'])));
			$objResponse->assign("txtFechaVencimientoPresupuesto","value",date(spanDateFormat,strtotime($rowPresupuesto['fecha_vencimiento'])));
			$objResponse->assign("txtNumeroPresupuesto","value",(($rowPresupuesto['numeracion_presupuesto'] != "") ? $rowPresupuesto['numeracion_presupuesto'] : ""));
			$objResponse->assign("txtNumeroSiniestro","value",(($rowPresupuesto['numero_siniestro'] != "") ? $rowPresupuesto['numero_siniestro'] : ""));
			$objResponse->loadCommands(cargaLstMoneda($rowPresupuesto['id_moneda']));
			$objResponse->assign("hddIdEmpleado","value",$rowPresupuesto['id_empleado_preparador']);
			$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowPresupuesto['nombre_empleado']));
			$objResponse->script("selectedOption('lstTipoClave',3);");
			$objResponse->assign("hddConfig19","value",1);
			$objResponse->assign("txtDescuento","value",number_format($rowPresupuesto['porcentaje_descuento'], 2, ".", ","));
			$objResponse->assign("txtObservacion","value",utf8_encode($rowPresupuesto['observaciones']));
			$objResponse->assign("hddEstatusPresupuesto","value",$rowPresupuesto['estatus_presupuesto_venta']);
			
			$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'false', 'true');");
		}
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtFechaVencimientoPresupuesto').className = 'inputHabilitado';
		byId('txtNumeroSiniestro').className = 'inputHabilitado';
		byId('txtDescuento').className = 'inputHabilitado';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$mesCierreInvFis = (date("m") == "01") ? 12 : (date("m") - 1);
		$anoCierreInvFis = (date("m") == "01") ? (date("Y") - 1) : date("Y");
		
		// VERIFICA SI SE REALIZO EL CIERRE MENSUAL DEL MES ANTERIOR
		$queryCierreMensual = sprintf("SELECT
			(SELECT COUNT(*) FROM iv_cierre_mensual
			WHERE mes = %s
				AND ano = %s
				AND id_empresa = %s
				AND estatus = 1) AS cierre_mes_anterior_realizado,
			(SELECT COUNT(*) FROM iv_cierre_mensual
			WHERE mes = %s
				AND ano = %s
				AND estatus = 0) AS cierre_mes_actual_pendiente;",
			valTpDato($mesCierreInvFis, "int"),
			valTpDato($anoCierreInvFis, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato(date("m"), "int"),
			valTpDato(date("Y"), "int"));
		$rsCierreMensual = mysql_query($queryCierreMensual);
		if (!$rsCierreMensual) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsCierreMensual = mysql_num_rows($rsCierreMensual);
		$rowCierreMensual = mysql_fetch_assoc($rsCierreMensual);
		
		if ($rowCierreMensual['cierre_mes_anterior_realizado'] > 0 && $rowCierreMensual['cierre_mes_actual_pendiente'] == 0) {
			$objResponse->assign("rbtTipoPagoContado","checked","checked");
			$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
			
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento","0","3","1","1"));
			$objResponse->loadCommands(cargaLstMoneda());
			
			$objResponse->script("
			selectedOption('lstTipoClave',3);
			
			byId('lstTipoClave').onchange = function () {
				selectedOption(this.id,3);
				xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','-1','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
			}");
			
			// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
			$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
				valTpDato($_SESSION['idUsuarioSysGts'], "int"));
			$rsUsuario = mysql_query($queryUsuario);
			if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowUsuario = mysql_fetch_assoc($rsUsuario);
			
			$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'false', 'true');"));
			$objResponse->assign("txtFechaPresupuesto","value",date(spanDateFormat));
			$objResponse->assign("hddIdEmpleado","value",$_SESSION['idEmpleadoSysGts']);
			$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
			$objResponse->assign("hddConfig19","value",1);
			
			$objResponse->assign("tdGastos","innerHTML",formularioGastos(false,"","PRESUPUESTO"));
		} else {
			$objResponse->script("
			alert('".utf8_encode("No Puede Crear Presupuesto, Debido A Que Aún No se ha Realizado el Cierre del Mes Anterior")."');
			location='iv_presupuesto_venta_list.php';");
		}
	}
	
	return $objResponse;
}

function eliminarArticulo($frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArticulo['cbxItmArticulo'])) {
		foreach ($frmListaArticulo['cbxItmArticulo'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmArticulo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function formGastosArticulo($frmListaArticulo, $hddNumeroItm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddNumeroItm","value",$hddNumeroItm);
	
	$queryGastos = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
		gasto.id_iva,
		iva_comp.iva AS iva_compra,
		iva_comp.observacion AS observacion_iva_compra,
		iva_comp.tipo AS tipo_iva_compra,
		iva_comp.activo AS activo_iva_compra,
		iva_comp.estado AS estado_iva_compra,
		gasto.id_iva_venta,
		iva_vent.iva AS iva_venta,
		iva_vent.observacion AS observacion_iva_venta,
		iva_vent.tipo AS tipo_iva_venta,
		iva_vent.activo AS activo_iva_venta,
		iva_vent.estado AS estado_iva_venta,
		gasto.estatus_iva
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva_comp ON (gasto.id_iva = iva_comp.idIva)
		LEFT JOIN pg_iva iva_vent ON (gasto.id_iva_venta = iva_vent.idIva)
	WHERE id_modo_gasto IN (1)
	ORDER BY gasto.id_modo_gasto, gasto.nombre ASC;");
	$rsGastos = mysql_query($queryGastos);
	$totalRowsGastos = mysql_num_rows($rsGastos);
	if (!$rsGastos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	while ($rowGastos = mysql_fetch_assoc($rsGastos)) {
		$contFila++;
		
		$valueMonto = 0;
		for ($contFilaObj = 1; $contFilaObj <= 20; $contFilaObj++) {
			if ($frmListaArticulo['hddIdGastoArt:'.$hddNumeroItm.':'.$contFilaObj] == $rowGastos['id_gasto']) {
				$valueMonto = $frmListaArticulo['txtMontoGastoArt:'.$hddNumeroItm.':'.$contFilaObj];
			}
		}
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"right\" id=\"trGasto:".$contFila."\" title=\"trGasto:".$contFila."\">" : "";
		
		$htmlTb .= "<td class=\"tituloCampo\" width=\"12%\">".utf8_encode($rowGastos['nombre']).":";
			$htmlTb .= sprintf("<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\">",
				$contFila, $contFila, $rowGastos['id_gasto']);
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"13%\">";
			$htmlTb .= sprintf("<input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputHabilitado\" maxlength=\"8\" onblur=\"setFormatoRafk(this,2);\" onclick=\"if (this.value <= 0){ this.select(); }\" onkeypress=\"return validarSoloNumerosReales(event);\" size=\"16\" style=\"text-align:right\" value=\"%s\"/>",
				$contFila, $contFila, number_format($valueMonto,2,".",""));
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	$idDocumentoVenta = $frmDcto['txtIdPresupuesto'];
	
	if ($frmDcto['rbtTipoPago'] == 0) {
		$queryClienteCredito = sprintf("SELECT *
		FROM cj_cc_credito cred
			INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente = %s
			AND cliente_emp.id_empresa = %s;",
			valTpDato($idCliente, "int"),
			valTpDato($idEmpresa, "int"));
		
		if (str_replace(",","",$frmTotalDcto['txtTotal']) > $rowClienteCredito['creditodisponible']) {
			if (isset($arrayObjPieArticulo)) {
				mysql_query("START TRANSACTION;");
				
				$deleteSQL = sprintf("DELETE FROM iv_presupuesto_venta_detalle
				WHERE id_presupuesto_venta_detalle = %s
					AND id_presupuesto_venta = %s
					AND id_articulo = %s
					AND id_casilla = %s",
					valTpDato($frmListaArticulo['hddIdPresupuestoDet'.$arrayObjPieArticulo[count($arrayObjPieArticulo)-1]], "int"),
					valTpDato($frmDcto['txtIdPresupuesto'], "int"),
					valTpDato($frmListaArticulo['hddIdArticuloItm'.$arrayObjPieArticulo[count($arrayObjPieArticulo)-1]], "int"),
					valTpDato($frmListaArticulo['hddIdCasilla'.$arrayObjPieArticulo[count($arrayObjPieArticulo)-1]], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				mysql_query("COMMIT;");
				
				$objResponse->script("
				fila = document.getElementById('trItmArticulo:".$arrayObjPieArticulo[count($arrayObjPieArticulo)-1]."');
				padre = fila.parentNode;
				padre.removeChild(fila);");
			}
			
			$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
			
			return $objResponse->alert(utf8_encode("Limite de Créditos"));
		}
	}
	
	mysql_query("START TRANSACTION;");
	
	if ($idDocumentoVenta > 0) {
		if (!xvalidaAcceso($objResponse,"iv_presupuesto_venta_list","editar")) { return $objResponse; }
		
		// EDITA LOS DATOS DEL PRESUPUESTO
		$updateSQL = sprintf("UPDATE iv_presupuesto_venta SET
			numeracion_presupuesto = %s,
			numero_siniestro = %s,
			id_empresa = %s,
			id_cliente = %s,
			fecha = %s,
			fecha_vencimiento = %s,
			id_moneda = %s,
			condicion_pago = %s,
			id_clave_movimiento = %s,
			estatus_presupuesto_venta = %s,
			id_modulo = %s,
			id_empleado_preparador = %s,
			subtotal = %s,
			porcentaje_descuento = %s,
			subtotal_descuento = %s,
			observaciones = %s
		WHERE id_presupuesto_venta = %s;",
			valTpDato($frmDcto['txtNumeroPresupuesto'], "text"),
			valTpDato($frmDcto['txtNumeroSiniestro'], "text"),
			valTpDato($frmDcto['txtIdEmpresa'], "int"),
			valTpDato($frmDcto['txtIdCliente'], "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaPresupuesto'])), "date"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaVencimientoPresupuesto'])), "date"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['rbtTipoPago'], "int"),
			valTpDato($frmDcto['lstClaveMovimiento'], "int"),
			valTpDato(0, "int"),
			valTpDato(0, "int"),
			valTpDato($frmDcto['hddIdEmpleado'], "int"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['txtIdPresupuesto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// VERIFICA SI LOS ARTICULOS ALMACENADOS EN LA BD EN EL PEDIDO AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryPresupuestoDet = sprintf("SELECT * FROM iv_presupuesto_venta_detalle
		WHERE id_presupuesto_venta = %s;",
			valTpDato($idDocumentoVenta, "int"));
		$rsPresupuestoDet = mysql_query($queryPresupuestoDet);
		if (!$rsPresupuestoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowPresupuestoDet = mysql_fetch_assoc($rsPresupuestoDet)) {
			$existRegDet = false;
			if (isset($arrayObjPieArticulo)) {
				foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
					if ($rowPresupuestoDet['id_presupuesto_venta_detalle'] == $frmListaArticulo['hddIdPresupuestoDet'.$valorPieArticulo]) {
						$existRegDet = true;
					}
				}
			}
			
			if ($existRegDet == false) {
				$deleteSQL = sprintf("DELETE FROM iv_presupuesto_venta_detalle WHERE id_presupuesto_venta_detalle = %s",
					valTpDato($rowPresupuestoDet['id_presupuesto_venta_detalle'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
		
		// ELIMINA LOS GASTOS DEL PRESUPUESTO
		$deleteSQL = sprintf("DELETE FROM iv_presupuesto_venta_gasto WHERE id_presupuesto_venta = %s",
			valTpDato($idDocumentoVenta, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ELIMINA LOS IMPUESTOS DEL PRESUPUESTO
		$deleteSQL = sprintf("DELETE FROM iv_presupuesto_venta_iva WHERE id_presupuesto_venta = %s",
			valTpDato($idDocumentoVenta, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
	} else {
		if (!xvalidaAcceso($objResponse,"iv_presupuesto_venta_list","insertar")) { return $objResponse; }
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(21, "int"), // 21 = Presupuesto Venta Repuestos
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
		
		// INSERTA LOS DATOS DEL PRESUPUESTO
		$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta (numeracion_presupuesto, numero_siniestro, id_empresa, id_cliente, fecha, fecha_vencimiento, id_moneda, condicion_pago, id_clave_movimiento, estatus_presupuesto_venta, id_modulo, id_empleado_preparador, subtotal, porcentaje_descuento, subtotal_descuento, observaciones)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "text"),
			valTpDato($frmDcto['txtNumeroSiniestro'], "text"),
			valTpDato($frmDcto['txtIdEmpresa'], "int"),
			valTpDato($frmDcto['txtIdCliente'], "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaPresupuesto'])), "date"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaVencimientoPresupuesto'])), "date"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['rbtTipoPago'], "int"),
			valTpDato($frmDcto['lstClaveMovimiento'], "int"),
			valTpDato(0, "int"),
			valTpDato(0, "int"),
			valTpDato($frmDcto['hddIdEmpleado'], "int"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDocumentoVenta = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// INSERTA EL DETALLE DEL PRESUPUESTO
	if (isset($arrayObjPieArticulo)) {
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$idDocumentoDetalle = $frmListaArticulo['hddIdPresupuestoDet'.$valorPieArticulo];
			$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valorPieArticulo];
			$hddIdArticuloAlmacenCosto = $frmListaArticulo['hddIdArticuloAlmacenCosto'.$valorPieArticulo];
			$hddIdArticuloCosto = $frmListaArticulo['hddIdArticuloCosto'.$valorPieArticulo];
			$cantPedida = str_replace(",","",$frmListaArticulo['hddCantItm'.$valorPieArticulo]);
			$cantPendiente = str_replace(",","",$frmListaArticulo['hddCantItm'.$valorPieArticulo]);
			$precioUnitario = str_replace(",", "", $frmListaArticulo['hddPrecioItm'.$valorPieArticulo]);
			$precioSugerido = str_replace(",", "", $frmListaArticulo['hddPrecioSugeridoItm'.$valorPieArticulo]);
			$gastoUnitario = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$valorPieArticulo]) / $cantPendiente;
			$hddIdIvaItm = "";
			$hddIvaItm = 0;
			if (isset($arrayObjIvaItm)) { // RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valorPieArticulo && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valor1[1]];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valorPieArticulo.':'.$valor1[1]];
					}
				}
			}
				
			if ($cantPedida > 0) {
				if ($idDocumentoDetalle > 0) {
					// ACTUALIZA QUIEN MODIFICO EL PRECIO EN CASO DE QUE EL CAMPO SEA NULO
					$updateSQL = sprintf("UPDATE iv_presupuesto_venta_detalle SET
						id_empleado_creador = %s
					WHERE id_presupuesto_venta_detalle = %s
						AND (precio_unitario <> %s
							OR id_empleado_creador IS NULL);",
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($idDocumentoDetalle, "int"),
						valTpDato($precioUnitario, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					$updateSQL = sprintf("UPDATE iv_presupuesto_venta_detalle SET
						id_articulo_costo = %s,
						cantidad = %s,
						pendiente = %s,
						id_precio = %s,
						precio_unitario = %s,
						precio_sugerido = %s,
						id_iva = %s,
						iva = %s
					WHERE id_presupuesto_venta_detalle = %s;",
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantPedida, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdPrecioItm'.$valorPieArticulo], "int"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($precioSugerido, "real_inglesa"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($idDocumentoDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				
					// ELIMINA LOS GASTOS DEL DETALLE DEL PRESUPUESTO
					$deleteSQL = sprintf("DELETE FROM iv_presupuesto_venta_detalle_gastos WHERE id_presupuesto_venta_detalle = %s",
						valTpDato($idDocumentoDetalle, "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				} else {
					$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta_detalle (id_presupuesto_venta, id_articulo, id_articulo_costo, cantidad, pendiente, id_precio, precio_unitario, precio_sugerido, id_iva, iva, id_empleado_creador)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantPedida, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdPrecioItm'.$valorPieArticulo], "int"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($precioSugerido, "real_inglesa"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idDocumentoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					$objResponse->assign("hddIdPresupuestoDet".$valorPieArticulo,"value",$idDocumentoDetalle);
				}
				
				// ACTUALIZA LOS GASTOS DEL DETALLE DEL PEDIDO
				for ($contFilaObj = 1; $contFilaObj <= 20; $contFilaObj++) {
					if (round($frmListaArticulo['txtMontoGastoArt:'.$valorPieArticulo.':'.$contFilaObj],2) > 0) {
						$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta_detalle_gastos (id_presupuesto_venta_detalle, id_gasto, monto_gasto)
						VALUE (%s, %s, %s);",
							valTpDato($idDocumentoDetalle, "int"),
							valTpDato($frmListaArticulo['hddIdGastoArt:'.$valorPieArticulo.':'.$contFilaObj], "int"),
							valTpDato($frmListaArticulo['txtMontoGastoArt:'.$valorPieArticulo.':'.$contFilaObj], "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			} else {
				return $objResponse->alert(utf8_encode("El registro ".$frmListaArticulo['txtCodigoArtItm'.$valorPieArticulo]." tiene una cantidad inválida"));
			}
		}
	}
	
	// INSERTA LOS GASTOS DEL PRESUPUESTO
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
			
			if (round($txtMontoGasto, 2) > 0) {
				$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta_gasto (id_presupuesto_venta, id_gasto, tipo, porcentaje_monto, monto, id_iva, iva, estatus_iva)
				SELECT %s, id_gasto, %s, %s, %s, %s, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idDocumentoVenta, "int"),
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
	
	// INSERTA LOS IMPUESTOS DEL PRESUPUESTO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta_iva (id_presupuesto_venta, base_imponible, subtotal_iva, id_iva, iva)
			VALUE (%s, %s, %s, %s, %s)",
				valTpDato($idDocumentoVenta, "int"),
				valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
				valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdPresupuesto","value",$idDocumentoVenta);
	
	$objResponse->alert(utf8_encode("Presupuesto Guardado con Éxito"));
	
	$objResponse->script("
	cerrarVentana = true;
	window.location.href='iv_presupuesto_venta_formato_pdf.php?valBusq=".$idDocumentoVenta."';");
	
	return $objResponse;
}

function insertarArticulo($frmDatosArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	$contFila = $arrayObjPieArticulo[count($arrayObjPieArticulo)-1];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddNumeroArt = $frmDatosArticulo['hddNumeroArt'];
	$idPresupuestoDetalle = $frmListaArticulo['hddIdPresupuestoDet'.$hddNumeroArt];
	
	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Venta)
	$queryConfig5 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 5 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig5 = mysql_query($queryConfig5);
	if (!$rsConfig5) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig5 = mysql_fetch_assoc($rsConfig5);
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	// BUSCA EL DETALLE DEL PEDIDO
	$queryPresupuestoDet = sprintf("SELECT * FROM iv_presupuesto_venta_detalle
	WHERE id_presupuesto_venta_detalle = %s;",
		valTpDato($idPresupuestoDetalle, "int"));
	$rsPresupuestoDet = mysql_query($queryPresupuestoDet);
	if (!$rsPresupuestoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowPresupuestoDet = mysql_fetch_assoc($rsPresupuestoDet);
	
	$idArticulo = $frmDatosArticulo['hddIdArticulo'];
	$idCasilla = $frmDatosArticulo['lstCasillaArt'];
	$cantPedida = str_replace(",", "", $frmDatosArticulo['txtCantidadArt']);
	$lstPrecioArt = $frmDatosArticulo['lstPrecioArt'];
	$precioUnitario = str_replace(",", "", $frmDatosArticulo['txtPrecioArt']);
	$precioSugerido = str_replace(",", "", $frmDatosArticulo['txtPrecioSugerido']);
	$idIva = $frmDatosArticulo['hddIdIvaArt'];
	
	$hddCantItm = str_replace(",", "", $frmListaArticulo['hddCantItm'.$hddNumeroArt]);
	$hddPrecioItm = str_replace(",", "", $frmListaArticulo['hddPrecioItm'.$hddNumeroArt]);
	$hddGastoItm = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$hddNumeroArt]);
	
	$hddBajarPrecio = $frmDatosArticulo['hddBajarPrecio'];
	$hddIdPrecioArtPredet = $frmDatosArticulo['hddIdPrecioArtPredet'];
	$hddPrecioArtPredet = $frmDatosArticulo['hddPrecioArtPredet'];
	
	if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
		$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
		WHERE id_articulo = %s
			AND id_casilla = %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"));
	} else {
		$queryArtEmp = sprintf("SELECT SUM(cantidad_disponible_logica) AS cantidad_disponible_logica FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		WHERE vw_iv_art_almacen_costo.id_articulo = %s
			AND vw_iv_art_almacen_costo.id_empresa = %s
			AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
			AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
			AND vw_iv_art_almacen_costo.estatus_almacen_venta = 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
	}
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	if ((($cantPedida - $hddCantItm) <= $rowArtEmp['cantidad_disponible_logica'] && $rowArtEmp['cantidad_disponible_logica'] >= 0)
	|| ($cantPedida - $hddCantItm) > $rowArtEmp['cantidad_disponible_logica']) {
		$arrayIdArticuloAlmacenCosto = array(-1);
		$arrayIdArticuloCosto = array(-1);
		$cantFaltante = $cantPedida - $hddCantItm;
		$cantFaltante = ($cambiarUbicacion == true) ? $cantPedida : $cantFaltante;
		
		while ($cantFaltante != 0) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
			AND vw_iv_art_almacen_costo.id_empresa = %s
			AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
			AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
			AND vw_iv_art_almacen_costo.estatus_almacen_venta = 1",
				valTpDato($idArticulo, "int"),
				valTpDato($idEmpresa, "int"));
			
			if ($cantFaltante > 0) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
			}
				
			if ($hddNumeroArt > 0 && $cantFaltante > 0 && $cambiarUbicacion != true) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo IN (SELECT pres_vent_det.id_articulo_costo FROM iv_presupuesto_venta_detalle pres_vent_det
																						WHERE pres_vent_det.id_presupuesto_venta = %s)",
					valTpDato($rowPresupuestoDet['id_presupuesto_venta'], "int"));
			} else if ($hddNumeroArt > 0 && $cantFaltante < 0 && $cambiarUbicacion != true) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo IN (SELECT pres_vent_det.id_articulo_costo FROM iv_presupuesto_venta_detalle pres_vent_det
																						WHERE pres_vent_det.id_presupuesto_venta_detalle = %s)",
					valTpDato($idPresupuestoDetalle, "int"));
			} else {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_almacen_costo NOT IN (%s)
				AND vw_iv_art_almacen_costo.id_articulo_costo NOT IN (%s)", 
					valTpDato(implode(",",$arrayIdArticuloAlmacenCosto), "campo"), 
					valTpDato(implode(",",$arrayIdArticuloCosto), "campo"));
			}
			
			$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
			ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC, orden_prioridad_venta ASC LIMIT 1;", $sqlBusq);
			$rsArtCosto = mysql_query($queryArtCosto);
			if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
			if ($totalRowsArtCosto > 0) {
				while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
					$arrayIdArticuloAlmacenCosto[] = $rowArtCosto['id_articulo_almacen_costo'];
					$arrayIdArticuloCosto[] = $rowArtCosto['id_articulo_costo'];
					
					$loteSinExistencia = ($rowArtCosto['cantidad_disponible_logica'] == $cantFaltante) ? true : false;
					$cantPedida = ($rowArtCosto['cantidad_disponible_logica'] > $cantFaltante) ? $cantFaltante : $rowArtCosto['cantidad_disponible_logica'];
					$cantFaltante -= $cantPedida;
					$idCasilla = (in_array($ResultConfig12, array(1,2))) ? $idCasilla : $rowArtCosto['id_casilla'];
					
					// BUSCA EL PRECIO PREDETERMINADO EN EL LOTE PARA LA VALIDACION DE QUE NO EXCEDA EL MONTO INGRESADO
					$queryArtPrecio = sprintf("SELECT *
					FROM iv_articulos_precios art_precio
						INNER JOIN iv_articulos_costos art_costo ON (art_precio.id_articulo_costo = art_costo.id_articulo_costo)
					WHERE art_precio.id_articulo = %s 
						AND art_costo.id_empresa = %s
						AND art_precio.id_precio = %s
						AND art_costo.id_articulo_costo = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresa, "int"),
						valTpDato($hddIdPrecioArtPredet, "int"),
						valTpDato($rowArtCosto['id_articulo_costo'], "int"));
					$rsArtPrecio = mysql_query($queryArtPrecio);
					if (!$rsArtPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
					$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
					
					$hddPrecioArtPredet = $rowArtPrecio['precio'];
					
					// BUSCA LA UBICACION DEL LOTE
					$queryArtAlmCosto = sprintf("SELECT *
					FROM iv_articulos_almacen art_almacen
						INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
					WHERE art_almacen.id_casilla = %s
						AND art_almacen.id_articulo = %s
						AND art_almacen_costo.id_articulo_costo = %s
						AND art_almacen_costo.estatus = 1;",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($rowArtCosto['id_articulo_costo'], "int"));
					$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
					if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtAlmCosto = mysql_num_rows($rsArtAlmCosto);
					$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
					
					if (!in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
						// BUSCA EL PRECIO ELEGIDO EN EL LOTE
						$queryArtPrecio = sprintf("SELECT *
						FROM iv_articulos_precios art_precio
							INNER JOIN iv_articulos_costos art_costo ON (art_precio.id_articulo_costo = art_costo.id_articulo_costo)
						WHERE art_precio.id_articulo = %s 
							AND art_costo.id_empresa = %s
							AND art_precio.id_precio = %s
							AND art_costo.id_articulo_costo = %s;",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"),
							valTpDato($lstPrecioArt, "int"),
							valTpDato($rowArtCosto['id_articulo_costo'], "int"));
						$rsArtPrecio = mysql_query($queryArtPrecio);
						if (!$rsArtPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
						$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
						
						$precioUnitario = (in_array($lstPrecioArt, array(6,7,12,13,18))) ? $precioUnitario : $rowArtPrecio['precio'];
					}
					$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
					$abrevMonedaCostoUnitario = $rowArtCosto['abreviacion'];
					
					//$objResponse->alert("Agrega: ".$cantPedida."; Falta: ".$cantFaltante."; Precio: ".$precioUnitario."; Costo: ".$costoUnitario);
					if ($hddNumeroArt > 0) {
						if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == false && round($hddPrecioArtPredet,2) > round($precioUnitario,2)
						&& $precioUnitario != $hddPrecioItm) {
							return $objResponse->alert(utf8_encode("El Precio está por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$hddPrecioArtPredet));
						} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && round($hddPrecioArtPredet,2) < round($precioUnitario,2)
						&& $precioUnitario != $hddPrecioItm) {
							return $objResponse->alert(utf8_encode("El Precio está por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$hddPrecioArtPredet));
						} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && $costoUnitario > round($precioUnitario,2)
						&& $precioUnitario != $hddPrecioItm) {
							return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por debajo del Costo ".$costoUnitario));
						} else if (in_array($lstPrecioArt, array(12)) && $hddBajarPrecio == true && $costoUnitario < round($precioUnitario,2)
						&& $precioUnitario != $hddPrecioItm) {
							return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por encima del Costo ".$costoUnitario));
						} else {
							$cantPedida = ($cambiarUbicacion == true) ? $cantPedida : $cantPedida + $hddCantItm;
							$hddIdPrecioItm = $lstPrecioArt;
							
							// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
							$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
								valTpDato($idCasilla, "int"));
							$rsUbicacion = mysql_query($queryUbicacion);
							if (!$rsUbicacion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
							$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
							
							// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
							$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
							FROM pg_iva iva
								INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
							WHERE art_impuesto.id_articulo = %s
								AND iva.tipo IN (6,9,2)
								AND art_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
																	WHERE cliente_imp_exento.id_cliente = %s);", 
								valTpDato($idArticulo, "int"), 
								valTpDato($idCliente, "int"));
							$rsIva = mysql_query($queryIva);
							if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$contIva = 0;
							while ($rowIva = mysql_fetch_assoc($rsIva)) {
								$contIva++;
								
								$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
								"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
								"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
								"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
								"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
									$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['iva'], 
									$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['idIva'], 
									$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['lujo'], 
									$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['estado'], 
									$hddNumeroArt.":".$contIva);
							}
							
							$objResponse->assign("spnUbicacion".$hddNumeroArt,"innerHTML",utf8_encode($rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion']));
							$objResponse->assign("hddCantItm".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
							$objResponse->assign("tdCantPend:".$hddNumeroArt,"innerHTML",number_format($cantPedida, 2, ".", ","));
							$objResponse->assign("hddPrecioItm".$hddNumeroArt,"value",number_format($precioUnitario, 2, ".", ","));
							//$objResponse->assign("tdIvaItm".$hddNumeroArt,"innerHTML",$ivaUnidad);
							$objResponse->assign("txtTotalItm".$hddNumeroArt,"value",number_format((($cantPedida * $precioUnitario) + $hddGastoItm), 2, ".", ","));
							
							$objResponse->assign("hddIdArticuloAlmacenCosto".$hddNumeroArt,"value",$rowArtAlmCosto['id_articulo_almacen_costo']);
							$objResponse->assign("hddIdArticuloCosto".$hddNumeroArt,"value",$rowArtCosto['id_articulo_costo']);
							$objResponse->assign("hddIdCasilla".$hddNumeroArt,"value",$idCasilla);
							$objResponse->assign("hddIdPrecioItm".$hddNumeroArt,"value",$lstPrecioArt);
							
							$hddNumeroArt = "";
						}
					} else {
						if (count($arrayObjPieArticulo) < $rowConfig5['valor']) {
							if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == false && round($hddPrecioArtPredet,2) > round($precioUnitario,2)) {
								return $objResponse->alert(utf8_encode("El Precio está por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$hddPrecioArtPredet));
							} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && round($hddPrecioArtPredet,2) < round($precioUnitario,2)) {
								return $objResponse->alert(utf8_encode("El Precio está por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$hddPrecioArtPredet));
							} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && $costoUnitario > round($precioUnitario,2)) {
								return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por debajo del Costo ".$costoUnitario));
							} else if (in_array($lstPrecioArt, array(12)) && $hddBajarPrecio == true && $costoUnitario < round($precioUnitario,2)) {
								return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por encima del Costo ".$costoUnitario));
							} else {
								$Result1 = insertarItemArticulo($contFila, "", "", $idCliente, $idArticulo, $idCasilla, $rowArtAlmCosto['id_articulo_almacen_costo'], $rowArtCosto['id_articulo_costo'], $cantPedida, $cantPedida, $lstPrecioArt, $precioUnitario, $costoUnitario, $abrevMonedaCostoUnitario, $idIva);
								if ($Result1[0] != true && strlen($Result1[1]) > 0) {
									return $objResponse->alert($Result1[1]);
								} else if ($Result1[0] == true) {
									$contFila = $Result1[2];
									$objResponse->script($Result1[1]);
									$arrayObjPieArticulo[] = $contFila;
								}
								
								$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
							}
						} else {
							return $objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por Pedido"));
						}
					}
				}
			} else {
				// VERIFICA SI EXISTE UN ARTICULO SIMILAR QUE TAMPOCO TENGA LOTE
				if (isset($arrayObjPieArticulo)) {
					foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
						if ($frmListaArticulo['hddIdArticuloItm'.$valorPieArticulo] == $idArticulo && !($frmListaArticulo['hddIdArticuloCosto'.$valorPieArticulo] > 0)) {
							$hddNumeroArt = $valorPieArticulo;
						}
					}
				}
				
				$hddCantItm = str_replace(",", "", $frmListaArticulo['hddCantItm'.$hddNumeroArt]);
				$hddPrecioItm = str_replace(",", "", $frmListaArticulo['hddPrecioItm'.$hddNumeroArt]);
				$hddGastoItm = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$hddNumeroArt]);
				
				$loteSinExistencia = true;
				$cantPedida = $cantFaltante;
				$cantFaltante -= $cantPedida;
				$idCasilla = (in_array($ResultConfig12, array(1,2))) ? $idCasilla : $rowArtCosto['id_casilla'];
				
				if ($hddNumeroArt > 0) {
					if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == false && round($hddPrecioArtPredet,2) > round($precioUnitario,2)
					&& $precioUnitario != $hddPrecioItm) {
						return $objResponse->alert(utf8_encode("El Precio está por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$hddPrecioArtPredet));
					} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && round($hddPrecioArtPredet,2) < round($precioUnitario,2)
					&& $precioUnitario != $hddPrecioItm) {
						return $objResponse->alert(utf8_encode("El Precio está por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$hddPrecioArtPredet));
					} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && $costoUnitario > round($precioUnitario,2)
					&& $precioUnitario != $hddPrecioItm) {
						return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por debajo del Costo ".$costoUnitario));
					} else if (in_array($lstPrecioArt, array(12)) && $hddBajarPrecio == true && $costoUnitario < round($precioUnitario,2)
					&& $precioUnitario != $hddPrecioItm) {
						return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por encima del Costo ".$costoUnitario));
					} else {
						$cantPedida = ($cambiarUbicacion == true) ? $cantPedida : $cantPedida + $hddCantItm;
						$hddIdPrecioItm = $lstPrecioArt;
						
						// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
						$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
							valTpDato($idCasilla, "int"));
						$rsUbicacion = mysql_query($queryUbicacion);
						if (!$rsUbicacion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
						$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
						
						// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
						$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
						FROM pg_iva iva
							INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
						WHERE art_impuesto.id_articulo = %s
							AND iva.tipo IN (6,9,2)
							AND art_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
																WHERE cliente_imp_exento.id_cliente = %s);", 
							valTpDato($idArticulo, "int"), 
							valTpDato($idCliente, "int"));
						$rsIva = mysql_query($queryIva);
						if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$contIva = 0;
						while ($rowIva = mysql_fetch_assoc($rsIva)) {
							$contIva++;
							
							$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
							"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
								$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['iva'], 
								$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['idIva'], 
								$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['lujo'], 
								$hddNumeroArt, $contIva, $hddNumeroArt, $contIva, $rowIva['estado'], 
								$hddNumeroArt.":".$contIva);
						}
						
						$objResponse->assign("spnUbicacion".$hddNumeroArt,"innerHTML",utf8_encode($rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion']));
						$objResponse->assign("hddCantItm".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
						$objResponse->assign("tdCantPend:".$hddNumeroArt,"innerHTML",number_format($cantPedida, 2, ".", ","));
						$objResponse->assign("hddPrecioItm".$hddNumeroArt,"value",number_format($precioUnitario, 2, ".", ","));
						//$objResponse->assign("tdIvaItm".$hddNumeroArt,"innerHTML",$ivaUnidad);
						$objResponse->assign("txtTotalItm".$hddNumeroArt,"value",number_format((($cantPedida * $precioUnitario) + $hddGastoItm), 2, ".", ","));
						
						$objResponse->assign("hddIdArticuloAlmacenCosto".$hddNumeroArt,"value",$rowArtAlmCosto['id_articulo_almacen_costo']);
						$objResponse->assign("hddIdArticuloCosto".$hddNumeroArt,"value",$rowArtCosto['id_articulo_costo']);
						$objResponse->assign("hddIdCasilla".$hddNumeroArt,"value",$idCasilla);
						$objResponse->assign("hddIdPrecioItm".$hddNumeroArt,"value",$lstPrecioArt);
						
						$hddNumeroArt = "";
					}
				} else {
					if (count($arrayObjPieArticulo) < $rowConfig5['valor']) {
						if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == false && round($hddPrecioArtPredet,2) > round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("El Precio está por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$hddPrecioArtPredet));
						} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && round($hddPrecioArtPredet,2) < round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("El Precio está por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$hddPrecioArtPredet));
						} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && $costoUnitario > round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por debajo del Costo ".$costoUnitario));
						} else if (in_array($lstPrecioArt, array(12)) && $hddBajarPrecio == true && $costoUnitario < round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por encima del Costo ".$costoUnitario));
						} else {
							$Result1 = insertarItemArticulo($contFila, "", "", $idCliente, $idArticulo, $idCasilla, $rowArtAlmCosto['id_articulo_almacen_costo'], $rowArtCosto['id_articulo_costo'], $cantPedida, $cantPedida, $lstPrecioArt, $precioUnitario, $costoUnitario, $abrevMonedaCostoUnitario, $idIva);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$contFila = $Result1[2];
								$objResponse->script($Result1[1]);
								$arrayObjPieArticulo[] = $contFila;
							}
							
							$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
						}
					} else {
						return $objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por Pedido"));
					}
				}
			}
			
			$hddNumeroArt = "";
			//$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
		}
		
		if ($loteSinExistencia == true) {
			$objResponse->script("byId('hddNumeroArt').value = '';");
		}
		
		$objResponse->script("
		if (!(byId('hddNumeroArt').value > 0)) {
			document.forms['frmDatosArticulo'].reset();
			byId('txtDescripcionArt').innerHTML = '';
		}
		
		if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
			byId('txtCriterioBuscarArticulo').focus();
			byId('txtCriterioBuscarArticulo').select();
		} else {
			document.forms['frmBuscarArticulo'].reset();
			byId('txtCodigoArticulo0').focus();
			byId('txtCodigoArticulo0').select();
		}");
	
		$objResponse->assign("divListaArticulo","innerHTML","");
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'false', 'true');");
	} else {
		// BUSQUEDA DEL ARTICULO POR EL ID
		$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_empresa
		WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
		$objResponse->assign("txtCantidadUbicacion","value",$rowArtEmp['cantidad_disponible_logica']);
		
		return $objResponse->alert("No posee disponible la cantidad suficiente");
	}
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if (strlen($valCadBusq[3]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[2]) {
			case 1 : $sqlBusq .= $cond.sprintf("vw_iv_art_emp.marca LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
			case 2 : $sqlBusq .= $cond.sprintf("vw_iv_art_emp.tipo_articulo LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
			case 3 : 
				$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
				FROM iv_subsecciones subsec
					INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
				WHERE subsec.id_subseccion = vw_iv_art_emp.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 4 : 
				$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion FROM iv_subsecciones subsec
				WHERE subsec.id_subseccion = vw_iv_art_emp.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 5 : $sqlBusq .= $cond.sprintf("vw_iv_art_emp.descripcion LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_articulo = %s", valTpDato($valCadBusq[3], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("vw_iv_art_emp.codigo_articulo_prov LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
		}
	}
	
	$query = sprintf("SELECT * FROM vw_iv_articulos_empresa vw_iv_art_emp %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "52%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Marca"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "cantidad_pedida", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Pedida a Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$srcIcono = "";
		$class = "";
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] == 0) {
			$srcIcono = "../img/iconos/cancel.png";
		} else if ($row['cantidad_disponible_logica'] <= $row['stock_minimo']) {
			$srcIcono = "../img/iconos/error.png";
			$class = "class=\"divMsjAlerta\"";
		} else if ($row['cantidad_disponible_logica'] > $row['stock_minimo']) {
			$srcIcono = "../img/iconos/tick.png";
			$class = "class=\"divMsjInfo\"";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArticulo('', '".$row['id_articulo']."', xajax.getFormValues('frmDcto'), '', xajax.getFormValues('frmListaArticulo'), 'false');\" title=\"Seleccionar\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"right\" ".$class.">".valTpDato(number_format($row['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cantidad_pedida'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_".strtolower($row['clasificacion']).".gif\" title=\"".utf8_encode("Clasificación ".strtoupper($row['clasificacion']))."\"/>";
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaArticuloAlterno($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 3, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	$query = sprintf("SELECT * FROM vw_iv_articulos_alternos_empresa WHERE id_articulo_ppal = %s AND id_empresa = %s",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[1], "int"));
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] <= 0)
			$srcIcono = "../img/iconos/cancel.png";
		else if ($row['cantidad_disponible_logica'] <= 5)
			$srcIcono = "../img/iconos/error.png";
		else if ($row['cantidad_disponible_logica'] > 5)
			$srcIcono = "../img/iconos/tick.png";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['foto'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td rowspan=\"3\">"."<button type=\"button\" onclick=\"xajax_asignarArticulo('', '".$row['id_articulo']."', xajax.getFormValues('frmDcto')); byId('btnCancelarArticuloSustituto').click();\" title=\"Seleccionar\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" rowspan=\"3\" valign=\"top\"><img class=\"imgBorde\" src=\"".$imgFoto."\" border=\"0\" width=\"100\"/></td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Código:")."</td>";
			$htmlTb .= "<td colspan=\"2\">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Disponibilidad:")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cantidad_disponible_logica']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Marca:")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca'])."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Tipo Artículo:")."</td>";
			$htmlTb .= "<td colspan=\"2\">".utf8_encode($row['tipo_articulo'])."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Descripción:")."</td>";
			$htmlTb .= "<td colspan=\"4\">".utf8_encode($row['descripcion'])."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td width=\"18%\"></td>";
			$htmlTb .= "<td width=\"32%\"></td>";
			$htmlTb .= "<td width=\"18%\"></td>";
			$htmlTb .= "<td width=\"18%\"></td>";
			$htmlTb .= "<td width=\"14%\"></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloAlterno(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloAlterno(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloAlterno(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloAlterno(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloAlterno(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticuloAlterno","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaArticuloSustituto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 3, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $valCadBusq[1];
	$idArticulo = $valCadBusq[0];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_articulo_sustituido = %s",
		valTpDato($idArticulo, "int"));
	
	$query = sprintf("SELECT vw_iv_art_emp.*,
	
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = art.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
		
		vw_iv_art_emp.clasificacion
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo)
		INNER JOIN iv_articulos_codigos_sustitutos art_cod_sust ON (vw_iv_art_emp.id_articulo = art_cod_sust.id_articulo) %s", $sqlBusq);
	
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] <= 0)
			$srcIcono = "../img/iconos/cancel.png";
		else if ($row['cantidad_disponible_logica'] <= 5)
			$srcIcono = "../img/iconos/error.png";
		else if ($row['cantidad_disponible_logica'] > 5)
			$srcIcono = "../img/iconos/tick.png";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['foto'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td rowspan=\"3\">"."<button type=\"button\" onclick=\"xajax_asignarArticulo('', '".$row['id_articulo']."', xajax.getFormValues('frmDcto')); byId('btnCancelarArticuloSustituto').click();\" title=\"Seleccionar\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" rowspan=\"3\" valign=\"top\"><img class=\"imgBorde\" src=\"".$imgFoto."\" border=\"0\" width=\"100\"/></td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Código:")."</td>";
			$htmlTb .= "<td colspan=\"2\">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Disponibilidad:")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cantidad_disponible_logica']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Marca:")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca'])."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Tipo Artículo:")."</td>";
			$htmlTb .= "<td colspan=\"2\">".utf8_encode($row['tipo_articulo'])."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Descripción:")."</td>";
			$htmlTb .= "<td colspan=\"4\">".utf8_encode($row['descripcion'])."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td width=\"18%\"></td>";
			$htmlTb .= "<td width=\"32%\"></td>";
			$htmlTb .= "<td width=\"18%\"></td>";
			$htmlTb .= "<td width=\"18%\"></td>";
			$htmlTb .= "<td width=\"14%\"></td>";
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
		$htmlTb .= "<td colspan=\"7\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticuloSustituto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.nit LIKE %s
		OR cliente.licencia LIKE %s
		OR cliente.telf LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.telf,
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function verificarSiniestro($numeroSiniestro) {
	$objResponse = new xajaxResponse();
	
	$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta WHERE numero_siniestro LIKE %s;",
		valTpDato($numeroSiniestro, "text"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedido = mysql_num_rows($rsPedido);
	
	if ($totalRowsPedido > 0) {
		$objResponse->alert("Ya existe(n) ".$totalRowsPedido." documento(s) con este Numero de Siniestro");
			
		while ($rowPedido = mysql_fetch_assoc($rsPedido)) {
			$objResponse->script("verVentana('reportes/iv_pedido_venta_pdf.php?valBusq=".$rowPedido['id_pedido_venta']."', 960, 550);");
		}
	} else {
		$queryPresupuesto = sprintf("SELECT * FROM vw_iv_presupuestos_venta
		WHERE numero_siniestro LIKE %s;",
			valTpDato($numeroSiniestro, "text"));
		$rsPresupuesto = mysql_query($queryPresupuesto);
		if (!$rsPresupuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPresupuesto = mysql_num_rows($rsPresupuesto);
		
		if ($totalRowsPresupuesto > 0) {
			$rowPresupuesto = mysql_fetch_assoc($rsPresupuesto);
			
			$objResponse->script("
			if (confirm('".utf8_encode("Ya existe un presupuesto con este Numero de Siniestro, ¿Desea cargarlo?")."') == true) {
				window.location.href='iv_presupuesto_venta_form.php?id=".$rowPresupuesto['id_presupuesto_venta']."';
			}");
		}
	}
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function validarPermiso($frmPermiso, $frmDatosArticulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "iv_pedido_venta_clave_mov") {
			$objResponse->script(sprintf("byId('lstClaveMovimiento').onchange = function(){ xajax_bloquearLstClaveMovimiento(this.value); }"));
			$objResponse->script("byId('aDesbloquearClaveMovimiento').style.display = 'none';");
			$objResponse->script("
			byId('lstClaveMovimiento').focus();
			byId('lstClaveMovimiento').select();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_venta") {
			$objResponse->script(sprintf("byId('lstPrecioArt').onchange = function(){ xajax_asignarPrecio('%s', this.value, xajax.getFormValues('frmDcto')); }",
				$frmDatosArticulo['hddIdArticulo']));
				
		} else if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_editado") {
			$objResponse->assign("hddBajarPrecio","value","");
			$objResponse->script("
			byId('txtPrecioArt').readOnly = false;
			byId('txtPrecioSugerido').className = 'inputHabilitado';
			byId('txtPrecioSugerido').readOnly = false;");
			$objResponse->script("byId('aDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			byId('txtPrecioArt').focus();
			byId('txtPrecioArt').select();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_editado_bajar") {
			$objResponse->assign("hddBajarPrecio","value",true);
			$objResponse->script("
			byId('txtPrecioArt').readOnly = false;
			byId('txtPrecioSugerido').className = 'inputHabilitado';
			byId('txtPrecioSugerido').readOnly = false;");
			$objResponse->script("byId('aDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			byId('txtPrecioArt').focus();
			byId('txtPrecioArt').select();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_precio_editado_debajo_costo") {
			$objResponse->assign("hddBajarPrecio","value",true);
			$objResponse->script("
			byId('txtPrecioArt').readOnly = false;
			byId('txtPrecioSugerido').className = 'inputHabilitado';
			byId('txtPrecioSugerido').readOnly = false;");
			$objResponse->script("byId('aDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			byId('txtPrecioArt').focus();
			byId('txtPrecioArt').select();");
		}
	} else {
		if (in_array($frmPermiso['hddModulo'], array("iv_catalogo_venta_precio_venta",
													"iv_catalogo_venta_precio_editado",
													"iv_catalogo_venta_precio_editado_bajar",
													"iv_precio_editado_debajo_costo"))) {
			$objResponse->script("byId('lstPrecioArt').onchange();");
		}
		
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFechaVencimiento");
$xajax->register(XAJAX_FUNCTION,"asignarGasto");
$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"bloquearLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formGastosArticulo");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticuloAlterno");
$xajax->register(XAJAX_FUNCTION,"listaArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"verificarSiniestro");

$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function insertarItemArticulo($contFila, $hddIdPedidoDet = "", $hddIdPresupuestoDet = "", $idCliente = "", $idArticulo = "", $idCasilla = "", $hddIdArticuloAlmacenCosto = "", $hddIdArticuloCosto = "", $cantPedida = "", $cantPendiente = "", $hddIdPrecioItm = "", $precioUnitario = "", $precioSugerido = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $idIva = "") {
	$contFila++;
	
	if ($hddIdPresupuestoDet > 0) {
		$totalRowsPresupuestoDetalle = 1;
		
		$queryIdEmpresa = sprintf("SELECT pres_vent.id_empresa
		FROM iv_presupuesto_venta_detalle pres_vent_det
			INNER JOIN iv_presupuesto_venta pres_vent ON (pres_vent_det.id_presupuesto_venta = pres_vent.id_presupuesto_venta)
		WHERE pres_vent_det.id_presupuesto_venta_detalle = %s;",
			valTpDato($hddIdPresupuestoDet, "int"));
		$rsEmpresa = mysql_query($queryIdEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = $rowEmpresa['id_empresa'];
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return array(false, $ResultConfig12[1], $contFila);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
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
		if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
		
		$costoUnitarioDet = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
		$abrevMonedaCostoUnitario = $rowArtCosto['abreviacion'];
	}
	
	$costoUnitario = ($costoUnitario == "" && $totalRowsPresupuestoDetalle > 0) ? $costoUnitarioDet : $costoUnitario;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT art.*
	FROM iv_articulos art
		INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
	WHERE art.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
	$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rsUbicacion = mysql_query($queryUbicacion);
	if (!$rsUbicacion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
	$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
	
	$ubicacion = $rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion'];
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
	FROM pg_iva iva
		INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
	WHERE art_impuesto.id_articulo = %s
		AND iva.tipo IN (6,9,2)
		AND art_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
											WHERE cliente_imp_exento.id_cliente = %s);", 
		valTpDato($idArticulo, "int"), 
		valTpDato($idCliente, "int"));
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
	
	// BUSCA LOS PRECIOS DEL ARTICULO
	$queryArtPrecio = sprintf("SELECT
		art_precio.id_precio,
		precio.descripcion_precio,
		art_precio.precio,
		moneda.abreviacion,
		precio.tipo
	FROM iv_articulos_precios art_precio
		INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
		INNER JOIN pg_precios precio ON (art_precio.id_precio = precio.id_precio)
	WHERE art_precio.id_articulo = %s
		AND art_precio.id_articulo_costo = %s
		AND precio.estatus IN (1,2)
	ORDER BY precio.porcentaje DESC;",
		valTpDato($idArticulo, "int"),
		valTpDato($hddIdArticuloCosto, "int"));
	$rsArtPrecio = mysql_query($queryArtPrecio);
	if (!$rsArtPrecio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$htmlPreciosArt = "<table width=\"360\">";
	while ($rowArtPrecio = mysql_fetch_assoc($rsArtPrecio)) {
		$styleTr = ($rowArtPrecio['id_precio'] == $hddIdPrecioItm) ? "style=\"font-weight:bold\"" : "";
		
		$htmlPreciosArt .= "<tr align=\"left\" ".$styleTr.">";
			$htmlPreciosArt .= "<td>".utf8_encode($rowArtPrecio['descripcion_precio'])."</td>";
			$htmlPreciosArt .= "<td align=\"right\">".utf8_encode($rowArtPrecio['abreviacion']).number_format($rowArtPrecio['precio'], 2, ".", ",")."</td>";
		$htmlPreciosArt .= "</tr>";
		
		if ($rowArtPrecio['id_precio'] == $hddIdPrecioItm && $rowArtPrecio['tipo'] == 0) { // PRECIO SOBRE COSTO
			$utilidad = ((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($costoUnitario);
		} else if ($rowArtPrecio['id_precio'] == $hddIdPrecioItm && $rowArtPrecio['tipo'] == 1) { // PRECIO SOBRE VENTA
			$utilidad = ((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($precioUnitario);
		}
		$utilidad = number_format($utilidad, 2, ".", ",")."%";
	}
	if (in_array($hddIdPrecioItm, array(6,7,12,13,18))) {
		$utilidad = "S/V: ".number_format(((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($precioUnitario), 2, ".", ",")."%";
		$utilidad .= " - ";
		$utilidad .= "S/C: ".number_format(((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($costoUnitario), 2, ".", ",")."%";
	}
	$htmlPreciosArt .= "<tr><td colspan=\"2\"><hr></td></tr>";
	$htmlPreciosArt .= "<tr align=\"left\">";
		$htmlPreciosArt .= "<td><b>"."Costo:"."</b></td>";
		$htmlPreciosArt .= "<td align=\"right\"><b>".utf8_encode($abrevMonedaCostoUnitario).number_format($costoUnitario, 2, ".", ",")."</b></td>";
	$htmlPreciosArt .= "</tr>";
	$htmlPreciosArt .= "<tr align=\"left\">";
		$htmlPreciosArt .= "<td><b>"."Utl. Bruta:"."</b></td>";
		$htmlPreciosArt .= "<td align=\"right\"><b>".$utilidad."</b></td>";
	$htmlPreciosArt .= "</tr>";
	$htmlPreciosArt .= "</table>";
	
	// CREA LA TABLA DE GASTOS
	$queryDetGasto = sprintf("SELECT * FROM iv_presupuesto_venta_detalle_gastos
	WHERE id_presupuesto_venta_detalle = %s;",
		valTpDato($hddIdPresupuestoDet, "int"));
	$rsDetGasto = mysql_query($queryDetGasto);
	if (!$rsDetGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contFilaObj = 0;
	$totalGastoArt = 0;
	$htmlGastoArtObj = "";
	while ($rowDetGasto = mysql_fetch_assoc($rsDetGasto)) {
		$contFilaObj++;
		
		$htmlGastoArtObj .= sprintf("<input type=\"hidden\" id=\"hddIdGastoArt:%s:%s\" name=\"hddIdGastoArt:%s:%s\" readonly=\"readonly\" value=\"%s\">",
			$contFila, $contFilaObj, $contFila, $contFilaObj, $rowDetGasto['id_gasto']);
		$htmlGastoArtObj .= sprintf("<input type=\"hidden\" id=\"txtMontoGastoArt:%s:%s\" name=\"txtMontoGastoArt:%s:%s\" readonly=\"readonly\" value=\"%s\"/>",
			$contFila, $contFilaObj, $contFila, $contFilaObj, $rowDetGasto['monto_gasto']);
		
		$totalGastoArt += $rowDetGasto['monto_gasto'];
	}
	
	$htmlGastoArt = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$htmlGastoArt .= "<tr>";
		$htmlGastoArt .= "<td><a class=\"modalImg\" id=\"aGastoArt:".$contFila."\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\"/></a></td>";
		$htmlGastoArt .= "<td id=\"tdItmGastoObj:".$contFila."\" title=\"tdItmGastoObj:".$contFila."\">".$htmlGastoArtObj."</td>";
		$htmlGastoArt .= "<td width=\"100%\"><input type=\"text\" id=\"hddGastoItm".$contFila."\" name=\"hddGastoItm".$contFila."\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"".number_format($totalGastoArt, 2, ".", ",")."\"/></td>";
	$htmlGastoArt .= "</tr>";
	$htmlGastoArt .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieArticulo').before('".
		"<tr align=\"left\" id=\"trItmArticulo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmArticulo:%s\"><input id=\"cbxItmArticulo\" name=\"cbxItmArticulo[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxPieArticulo\" name=\"cbxPieArticulo[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><input type=\"text\" id=\"txtCodigoArtItm%s\" name=\"txtCodigoArtItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
			"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
				"<div id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</div>".
				"%s".
				"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"%s\">".
				"<tr><td nowrap=\"nowrap\">%s</td><td><input type=\"text\" id=\"hddPrecioSugeridoItm%s\" name=\"hddPrecioSugeridoItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" %s value=\"%s\"/></td></tr>".
				"</table></td>".
			"<td><input type=\"text\" id=\"hddCantItm%s\" name=\"hddCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdCantPend:%s\" align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td><input type=\"text\" id=\"hddPrecioItm%s\" name=\"hddPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPresupuestoDet%s\" name=\"hddIdPresupuestoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" title=\"Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aGastoArt:%s').onclick = function() { abrirDivFlotante1(this, 'tblLista', 'Gasto', '%s'); }
		
		byId('hddPrecioItm%s').onmouseover = function() { Tip('%s', TITLE, 'Lista de Precios'); }
		byId('hddPrecioItm%s').onmouseout = function() { UnTip(); }",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$contFila, $contFila,
			$contFila, $contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
				((in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) ? "" : "<div id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</div>"),
				"100%",
				(($precioSugerido != 0) ? "<span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">Precio Sugerido:</span>" : ""), $contFila, $contFila, (($precioSugerido != 0) ? "" : "style=\"display:none\""), number_format($precioSugerido, 2, ".", ","),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, number_format($cantPendiente, 2, ".", ","),
			$htmlGastoArt,
			$contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
			$contFila, $ivaUnidad,
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
				$contFila, $contFila, $hddIdPresupuestoDet,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdArticuloAlmacenCosto,
				$contFila, $contFila, $hddIdArticuloCosto,
				$contFila, $contFila, $hddIdPrecioItm,
				$contFila, $contFila, $idCasilla,
		
		$contFila, $contFila,
		
		$contFila, $htmlPreciosArt,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>