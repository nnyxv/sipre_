<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function asignarArticulo($hddNumeroArt, $idArticulo, $frmDcto, $precioUnitario = "", $frmListaArticulo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
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
	
	// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdArticuloItm'.$valor] == $idArticulo) {
				// BUSCA LOS LOTES DEL ARTICULO
				$sqlBusq = "";
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
				AND vw_iv_art_almacen_costo.id_empresa = %s
				AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo = %s", 
					valTpDato($frmListaArticulo['hddIdArticuloCosto'.$valor], "int"));
				
				if (!in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
				}
				
				$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
				ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", $sqlBusq);
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
				while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
					if ($frmListaArticulo['hddIdArticuloCosto'.$valor] == $rowArtCosto['id_articulo_costo']) {
						$hddNumeroArt = $valor;
					}
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
		AND iva.tipo IN (6,9,2);", 
		valTpDato($idArticulo, "int"));
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
	$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
	
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
	
	if ($frmDcto['lstTipoVale'] == 1 && $frmDcto['lstTipoMovimiento'] == 4) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC; 2 = Entrada, 4 = Salida
		$onChange = "onchange=\"xajax_asignarLote(this.value, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDatosArticulo'));\"";
		
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
		
		$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
		ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", $sqlBusq);
	} else {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_articulo = %s
		AND art_costo.id_empresa = %s
		AND art_costo.estatus = 1",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$sqlOrderBy = (in_array($ResultConfig12, array(1,2))) ? "ORDER BY fecha_registro DESC" : "ORDER BY fecha_registro ASC";
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryArtCosto = sprintf("SELECT art_costo.*,
			art_costo.fecha_registro AS fecha_registro_articulo_costo,
			moneda.abreviacion
		FROM iv_articulos_costos art_costo
			INNER JOIN pg_monedas moneda ON (art_costo.id_moneda = moneda.idmoneda) %s;", $sqlBusq, $sqlOrderBy);
	}
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	$htmlLstIni = "<select id=\"lstCostoArt\" name=\"lstCostoArt\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
		$htmlLst = "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
		
		$selected = ($selIdArtCosto == $rowArtCosto['id_articulo_costo'] || $totalRowsArtCosto == 1) ? "selected=\"selected\"" : "";
		
		$htmlLst .= "<optgroup label=\"LOTE: ".$rowArtCosto['id_articulo_costo']."\">";
			$htmlLst .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowArtCosto['id_articulo_costo']."\">".$rowArtCosto['abreviacion'].number_format($costoUnitario, 2, ".", ",")."</option>";
		$htmlLst .= "</optgroup>";
	}
	$htmlLstFin .= "</select>";
	
	$objResponse->assign("divlstCostoArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	
	if ($frmDcto['lstTipoVale'] == 1) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
		$objResponse->script("
		byId('trtxtPrecioArt').style.display = 'none';
		byId('divlstCostoArt').style.display = '';
		
		byId('txtPrecioArt').className = 'inputInicial';
		byId('txtPrecioArt').readOnly = true;");
		//$objResponse->assign("txtPrecioArt","value",$costoUnitario);
	} else if ($frmDcto['lstTipoVale'] == 3) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
		$objResponse->script("
		byId('trtxtPrecioArt').style.display = '';
		byId('divlstCostoArt').style.display = '';
		
		byId('txtPrecioArt').className = 'inputHabilitado';
		byId('txtPrecioArt').readOnly = false;");
		$objResponse->assign("txtPrecioArt","value","");
	}
	
	if ($hddNumeroArt > 0) { // SI EL ARTICULO YA ESTA AGREGADO EN LA LISTA
		$objResponse->assign("txtCantidadArt","value",number_format((str_replace(",", "", $frmListaArticulo['hddCantItm'.$hddNumeroArt]) + $txtCantidadArt), 2, ".", ","));
		
		$precioUnitario = str_replace(",","",$frmListaArticulo['hddPrecioItm'.$hddNumeroArt]);
		$costoUnitario = str_replace(",","",$frmListaArticulo['hddCostoItm'.$hddNumeroArt]);
		//$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
		
		$objResponse->script("xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	} else { // NO EXISTE EL ARTICULO EN LA LISTA DEL PEDIDO
		$objResponse->assign("hddNumeroArt","value","");
		$objResponse->assign("txtCantidadArt","value",number_format(0, 2, ".", ","));
		
		$objResponse->script("
		if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
			byId('txtCantidadArt').value++;
		}
		
		if (byId('hddNumeroArt').value > 0) {
			byId('aAgregarArticulo').click();
		}");
		
		$selIdCasilla = ($selIdCasilla > 0) ? $selIdCasilla : $rowArticulo['id_casilla_predeterminada'];
		
		// BUSCA LAS UBICACIONES PARA LA VENTA DEL ARTICULO
		$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
		WHERE id_articulo = %s
			AND id_empresa = %s
			AND id_casilla IS NOT NULL
			AND estatus_articulo_almacen = 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsUbic = mysql_query($queryUbic);
		if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsUbic = mysql_num_rows($rsUbic);
		while ($rowUbic = mysql_fetch_assoc($rsUbic)) {
			$arrayUbicacion = NULL;
			$existeUbicacion = false;
			if (isset($arrayAlmacen)) {
				foreach($arrayAlmacen as $indice => $valor) {
					if ($arrayAlmacen[$indice][0] == $rowUbic['id_almacen']) {
						$existeUbicacion = true;
						
						$arrayUbicacion = $arrayAlmacen[$indice][2];
						
						$arrayDetalleUbicacion['id_casilla'] = $rowUbic['id_casilla'];
						$arrayDetalleUbicacion['ubicacion'] = $rowUbic['ubicacion'];
						$arrayUbicacion[] = $arrayDetalleUbicacion;
						$arrayAlmacen[$indice][2] = $arrayUbicacion;
					}
				}
			}
			
			if ($existeUbicacion == false) {
				$arrayDetalleUbicacion['id_casilla'] = $rowUbic['id_casilla'];
				$arrayDetalleUbicacion['ubicacion'] = $rowUbic['ubicacion'];
				$arrayUbicacion[] = $arrayDetalleUbicacion;
				$arrayAlmacen[] = array(
					$rowUbic['id_almacen'],
					$rowUbic['descripcion_almacen'],
					$arrayUbicacion);
			}
		}
		
		$htmlLstIni = "<select id=\"lstCasillaArt\" name=\"lstCasillaArt\" class=\"inputHabilitado\" onchange=\"xajax_asignarDisponibilidadUbicacion(this.value, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDatosArticulo'), 'txtCantidadUbicacion');\" style=\"width:99%\">";
			$htmlLst = "<option value=\"-1\">[ Seleccione ]</option>";
		if (isset($arrayAlmacen)) {
			foreach($arrayAlmacen as $indice => $valor) {
				$arrayUbicacion = $arrayAlmacen[$indice][2];
				
				$htmlLst .= "<optgroup label=\"".utf8_encode($arrayAlmacen[$indice][1])."\">";
				if (isset($arrayUbicacion)) {
					foreach($arrayUbicacion as $indice2 => $valor2) {
						$selected = "";
						if (in_array($arrayUbicacion[$indice2]['id_casilla'], array($selIdCasilla))
						|| !($totalRowsUbic > 1)) {
							$selected = "selected=\"selected\"";
							$objResponse->script("xajax_asignarDisponibilidadUbicacion('".$arrayUbicacion[$indice2]['id_casilla']."', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDatosArticulo'), 'txtCantidadUbicacion');");
						}
						
						$htmlLst .= "<option ".$selected." value=\"".$arrayUbicacion[$indice2]['id_casilla']."\">".utf8_encode(str_replace("-[]", "", $arrayUbicacion[$indice2]['ubicacion']))."</option>";
					}
				}
				$htmlLst .= "</optgroup>";
			}
		}
		$htmlLstFin = "</select>";
		
		$objResponse->assign("tdlstCasillaArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	}
	
	$objResponse->script("
	byId('txtCantidadArt').focus();
	byId('txtCantidadArt').select();");
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $asigDescuento = "true", $cerrarVentana = "true") {
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
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");
	
	return $objResponse;
}

function asignarDcto($idDcto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT nota_cred.*,
		cliente.id AS id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		(nota_cred.subtotalNotaCredito - subtotal_descuento + ivaLujoNotaCredito + ivaNotaCredito) AS total_nota_credito
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
	WHERE idNotaCredito = %s",
		valTpDato($idDcto, "int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtIdCliente","value",$rowDcto['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowDcto['nombre_cliente']));
	
	$objResponse->assign("hddIdDcto","value",$idDcto);
	$objResponse->assign("txtNroDcto","value",$rowDcto['numeracion_nota_credito']);
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function asignarDisponibilidadUbicacion($idCasilla, $frmDcto, $frmDatosArticulo, $objetoDestino) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idArticuloCosto = $frmDatosArticulo['lstCostoArt'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
		$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
		WHERE id_casilla = %s
			AND estatus_articulo_almacen = 1;",
			valTpDato($idCasilla, "int"));
	} else {
		$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		WHERE vw_iv_art_almacen_costo.id_articulo_costo = %s
			AND vw_iv_art_almacen_costo.id_casilla = %s
			AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1;",
			valTpDato($idArticuloCosto, "int"),
			valTpDato($idCasilla, "int"));
	}
	$rsUbic = mysql_query($queryUbic);
	if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUbic = mysql_fetch_assoc($rsUbic);
	
	$objResponse->assign($objetoDestino,"value",$rowUbic['cantidad_disponible_logica']);
	
	return $objResponse;
}

function asignarLote($idArticuloCosto, $frmDcto, $frmDatosArticulo) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idArticulo = $frmDatosArticulo['hddIdArticulo'];
	$idArticuloCosto = $frmDatosArticulo['lstCostoArt'];
	
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
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
	AND vw_iv_art_almacen_costo.id_empresa = %s
	AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
		
	if ($frmDcto['lstTipoMovimiento'] == 4) { // 2 = Entrada, 4 = Salida
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	}
	
	if ($idArticuloCosto != "-1" && $idArticuloCosto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo = %s",
			valTpDato($idArticuloCosto, "int"));
	}
	
	// BUSCA LAS UBICACIONES PARA LA VENTA DEL ARTICULO
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s;", $sqlBusq);
	$rsUbic = mysql_query($queryUbic);
	if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUbic = mysql_num_rows($rsUbic);
	while ($rowUbic = mysql_fetch_assoc($rsUbic)) {
		$arrayUbicacion = NULL;
		$existeUbicacion = false;
		if (isset($arrayAlmacen)) {
			foreach($arrayAlmacen as $indice => $valor) {
				if ($arrayAlmacen[$indice][0] == $rowUbic['id_almacen']) {
					$existeUbicacion = true;
					
					$arrayUbicacion = $arrayAlmacen[$indice][2];
					
					$arrayDetalleUbicacion['id_casilla'] = $rowUbic['id_casilla'];
					$arrayDetalleUbicacion['ubicacion'] = $rowUbic['ubicacion'];
					$arrayUbicacion[] = $arrayDetalleUbicacion;
					$arrayAlmacen[$indice][2] = $arrayUbicacion;
				}
			}
		}
		
		if ($existeUbicacion == false) {
			$arrayDetalleUbicacion['id_casilla'] = $rowUbic['id_casilla'];
			$arrayDetalleUbicacion['ubicacion'] = $rowUbic['ubicacion'];
			$arrayUbicacion[] = $arrayDetalleUbicacion;
			$arrayAlmacen[] = array(
				$rowUbic['id_almacen'],
				$rowUbic['descripcion_almacen'],
				$arrayUbicacion);
		}
	}
	
	$htmlLstIni = "<select id=\"lstCasillaArt\" name=\"lstCasillaArt\" class=\"inputHabilitado\" onchange=\"xajax_asignarDisponibilidadUbicacion(this.value, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDatosArticulo'), 'txtCantidadUbicacion');\" style=\"width:99%\">";
		$htmlLst = "<option value=\"-1\">[ Seleccione ]</option>";
	if (isset($arrayAlmacen)) {
		foreach($arrayAlmacen as $indice => $valor) {
			$arrayUbicacion = $arrayAlmacen[$indice][2];
			
			$htmlLst .= "<optgroup label=\"".utf8_encode($arrayAlmacen[$indice][1])."\">";
			if (isset($arrayUbicacion)) {
				foreach($arrayUbicacion as $indice2 => $valor2) {
					$selected = "";
					if (in_array($arrayUbicacion[$indice2]['id_casilla'], array($selIdCasilla))
					|| !($totalRowsUbic > 1)) {
						$selected = "selected=\"selected\"";
						$objResponse->script("xajax_asignarDisponibilidadUbicacion('".$arrayUbicacion[$indice2]['id_casilla']."', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDatosArticulo'), 'txtCantidadUbicacion');");
					}
					
					$htmlLst .= "<option ".$selected." value=\"".$arrayUbicacion[$indice2]['id_casilla']."\">".utf8_encode(str_replace("-[]", "", $arrayUbicacion[$indice2]['ubicacion']))."</option>";
				}
			}
			$htmlLst .= "</optgroup>";
		}
	}
	$htmlLstFin = "</select>";
	$objResponse->assign("tdlstCasillaArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	
	return $objResponse;
}

function asignarTipoVale($idTipoVale) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtIdCliente","value","");
	$objResponse->assign("txtNombreCliente","value","");
	$objResponse->assign("hddIdDcto","value","");
	$objResponse->assign("txtNroDcto","value","");
	
	if ($idTipoVale == 1) { // DE ENTRADA O SALIDA
		$objResponse->script("
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('lstTipoVale').className = 'inputCompletoHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputCompletoHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = false;
		byId('btnListarCliente').style.display = '';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '', '5,6');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "2,4", "", "5,6"));
	} else if ($idTipoVale == 3) { // DE NOTA DE CREDITO DE CxC
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputCompletoHabilitado';
		byId('txtNroDcto').className = 'inputHabilitado';
		byId('lstTipoMovimiento').className = 'inputCompleto';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = '';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,2);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",2);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", 2, "", "3"));
	} else {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputCompletoHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputCompleto';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = true;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value);
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", -1));
	}
	
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticulo'.$cont].";";
	}
	$auxCodArticulo = $codArticulo;
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
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

function buscarNotaCredito($frmBuscarNotaCredito) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarNotaCredito['txtCriterioBuscarNotaCredito']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "numeracion_nota_credito", "DESC", $valBusq));
	
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
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantTotalItem += str_replace(",","",$frmListaArticulo['hddCantItm'.$valor]);
			$txtSubTotal += str_replace(",","",$frmListaArticulo['hddCantItm'.$valor]) * str_replace(",","",$frmListaArticulo['hddPrecioItm'.$valor]);
		}
	}
	
	$objResponse->assign("txtCantTotalItem","value",number_format($txtCantTotalItem, 2, ".", ","));
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	
	if (count($arrayObj) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('lstTipoVale').className = 'inputCompleto';
		byId('lstTipoVale').onchange = function () {
			selectedOption(this.id,'".$frmDcto['lstTipoVale']."');
		}
		
		byId('lstTipoMovimiento').className = 'inputCompleto';
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,'".$frmDcto['lstTipoMovimiento']."');
		}");
		
		if ($frmDcto['lstTipoVale'] == 3) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
			$objResponse->script("byId('btnListarDcto').style.display = 'none';");
		}
	} else { // SI NO TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdCliente').readOnly = false;
		byId('aListarCliente').style.display = '';");
		
		if ($frmDcto['txtIdCliente'] > 0) {
			$objResponse->script("byId('lstTipoMovimiento').className = 'inputCompletoHabilitado'");
			if (!($frmDcto['lstTipoMovimiento'] > 0)) {
				$arrayObjetoInvalido[] = "lstTipoMovimiento";
			}
			
			if (count($arrayObjetoInvalido) > 0) {
				if (count($arrayObjetoInvalido) > 0) {
					foreach ($arrayObjetoInvalido as $indiceObjetoInvalido => $valorObjetoInvalido) {
						$objResponse->script("byId('".$valorObjetoInvalido."').className = 'inputErrado'");
					}
				}
				
				return $objResponse->alert(utf8_encode("Los campos señalados en rojo son invalidos"));
			}
			
			$objResponse->script("
			byId('lstTipoVale').className = 'inputCompleto';
			byId('lstTipoVale').onchange = function () {
				selectedOption(this.id,'".$frmDcto['lstTipoVale']."');
			}
			
			byId('lstTipoMovimiento').className = 'inputCompleto';
			byId('lstTipoMovimiento').onchange = function () {
				selectedOption(this.id,'".$frmDcto['lstTipoMovimiento']."');
			}");
		} else {
			$objResponse->script("
			xajax_asignarTipoVale('".$frmDcto['lstTipoVale']."');
			
			byId('lstTipoVale').className = 'inputCompletoHabilitado';
			byId('lstTipoVale').onchange = function () {
				xajax_asignarTipoVale(this.value);
			}
			
			byId('lstTipoMovimiento').className = 'inputCompletoHabilitado';
			byId('lstTipoMovimiento').onchange = function () {
				xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '".(($frmDcto['lstTipoVale'] == 1) ? "5,6": "3")."');
			}");
		}
		
		if ($frmDcto['lstTipoVale'] == 3) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
			$objResponse->script("byId('btnListarDcto').style.display = '';");
		}
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_ajuste_inventario_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	
	if ($frmDcto['lstTipoVale'] == 1) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	} else if ($frmDcto['lstTipoVale'] == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		switch ($frmDcto['lstTipoMovimiento']) { // 2 = ENTRADA, 4 = SALIDA
			case 2 : $documentoGenera = 6; break;
			case 4 : $documentoGenera = 5; break;
		}
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.tipo = %s
			AND clave_mov.documento_genera = %s
			AND clave_mov.id_modulo IN (0)
		ORDER BY clave DESC 
		LIMIT 1;",
			valTpDato($frmDcto['lstTipoMovimiento'], "int"),
			valTpDato($documentoGenera, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		$idClaveMovimiento = $rowClaveMov['id_clave_movimiento'];
	}
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasilla'.$valor]) == "") {
				$objResponse->alert(utf8_encode("Existen artículos los cuales no tienen ubicación asignada"));
			}
		}
	}
	
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
	
	if ($frmDcto['lstTipoMovimiento'] == 2) { // 2 = ENTRADA, 4 = SALIDA
		$insertSQL = sprintf("INSERT INTO iv_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_entrada, observacion, id_empleado_creador)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])),"date"),
			valTpDato($frmDcto['hddIdDcto'], "int"),
			valTpDato($frmDcto['txtIdCliente'], "int"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmDcto['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inv. Fisico
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['txtIdEmpleado'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idVale = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$arrayIdDctoContabilidad[] = array(
			$idVale,
			$idModulo,
			"ENTRADA");
		
		if ($frmDcto['lstTipoVale'] == 3) { // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inv. Fisico
			// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO CREADA POR CUENTAS POR COBRAR
			$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
				id_clave_movimiento = %s,
				estatus_nota_credito = 2
			WHERE idNotaCredito = %s;",
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($frmDcto['hddIdDcto'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		$estadoKardex = 0;
	} else if ($frmDcto['lstTipoMovimiento'] == 4) { // 2 = ENTRADA, 4 = SALIDA
		$insertSQL = sprintf("INSERT INTO iv_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_salida, observacion, id_empleado_creador)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])),"date"),
			valTpDato($frmDcto['hddIdDcto'], "int"),
			valTpDato($frmDcto['txtIdCliente'], "int"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmDcto['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['txtIdEmpleado'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idVale = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$arrayIdDctoContabilidad[] = array(
			$idVale,
			$idModulo,
			"SALIDA");
		
		$estadoKardex = 1;
	}
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato($frmDcto['lstTipoMovimiento'], "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idVale, "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
		valTpDato($frmDcto['txtIdCliente'], "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(1, "boolean")); // 0 = Credito, 1 = Contado
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL MOVIMIENTO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valor];
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
			
			$cantRecibida = round(str_replace(",","",$frmListaArticulo['hddCantItm'.$valor]),2);
			$precioUnitario = round(str_replace(",","",$frmListaArticulo['hddPrecioItm'.$valor]),2);
			$costoUnitario = round(str_replace(",","",$frmListaArticulo['hddCostoItm'.$valor]),2);
			$totalArticulo = $cantRecibida * $precioUnitario;
			
			$hddIdArticuloAlmacenCosto = $frmListaArticulo['hddIdArticuloAlmacenCosto'.$valor];
			$hddIdArticuloCosto = $frmListaArticulo['hddIdArticuloCosto'.$valor];
			
			switch ($frmDcto['lstTipoMovimiento']) {
				case 2 : // ENTRADA
					$insertSQL = sprintf("INSERT INTO iv_vale_entrada_detalle (id_vale_entrada, id_articulo, id_casilla, cantidad, precio_venta, costo_compra)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idVale, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"));
					break;
				case 4 : // SALIDA
					$insertSQL = sprintf("INSERT INTO iv_vale_salida_detalle (id_vale_salida, id_articulo, id_casilla, cantidad, precio_venta, costo_compra)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idVale, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"));
					break;
			}
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return $objResponse->alert("Existe un registro duplicado en el detalle"."\n\nLine: ".__LINE__);
				} else {
					return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			mysql_query("SET NAMES 'latin1';");
			
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
			if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$hddIdArticuloAlmacenCosto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
				valTpDato($idModulo, "int"),
				valTpDato($idVale, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($frmDcto['lstTipoMovimiento'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimiento, "int"),
				valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
				valTpDato($cantRecibida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato($estadoKardex, "int"), // 0 = Entrada, 1 = Salida
				valTpDato($frmTotalDcto['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) {
				if (mysql_errno() == 1452) {
					$objResponse->alert("El artículo ya no tiene asignada la ubicacion seleccionada");
				} else {
					return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL DETALLE DEL MOVIMIENTO DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idMovimiento, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($cantRecibida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(((0 * $precioUnitario) / 100), "real_inglesa"),
				valTpDato(0, "int"), // 0 = Unitario, 1 = Import
				valTpDato(0, "boolean"), // 0 = No, 1 = Si
				valTpDato("", "int"),
				valTpDato("", "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idMovimientoDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
			$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA EL COSTO PROMEDIO
			$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// SI EL VALE ES NORMAL PARA AJUSTAR EL INVENTARIO
			if ($frmDcto['lstTipoVale'] == 1) {
				// ACTUALIZA EL PRECIO DE VENTA
				$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdVale","value",$idVale);
	$objResponse->assign("txtNumeroVale","value",$numeroActual);
	
	$objResponse->alert(utf8_encode("Vale Guardado con Éxito"));
	
	switch ($frmDcto['lstTipoMovimiento']) {
		case 2 : $objResponse->script(sprintf("verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|2', 960, 550);", $idVale)); break;
		case 4 : $objResponse->script(sprintf("verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|4', 960, 550);", $idVale)); break;
	}
	
	$objResponse->script(sprintf("
	cerrarVentana = true;
	window.location.href='iv_ajuste_inventario_list.php';"));

	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "ENTRADA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeEntradaRe")) { generarValeEntradaRe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeEntradaVe")) { generarValeEntradaVe($idVale,"",""); } break;
				}
			} else if ($tipoDcto == "SALIDA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeSalidaRe")) { generarValeSalidaRe($idVale,"",""); } break;
					case 1 : if (function_exists("generarValeSe")) { generarValeSe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeSalidaVe")) { generarValeSalidaVe($idVale,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	return $objResponse;
}

function importarDcto($frmImportarPedido, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
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
	while ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() != '') {
		$cantPedida = $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue();
		
		if ($itemExcel == true && doubleval($cantPedida) > 0) {
			$arrayFila[] = array(
				$archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(), // Código
				$archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(), // Ped.
				$archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(), // Lote
				$archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue()); // Costo Unit.
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Codigo"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFila)) {
		foreach ($arrayFila as $indice => $valor) {
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmDcto['txtIdEmpresa'] > 0) ? $frmDcto['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];

			// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Vale de Entrada y Salida)
			$queryConfig16 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 16 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig16 = mysql_query($queryConfig16);
			if (!$rsConfig16) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowConfig16 = mysql_fetch_assoc($rsConfig16);
			
			// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
			$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
			if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
				return $objResponse->alert($ResultConfig12[1]);
			} else if ($ResultConfig12[0] == true) {
				$ResultConfig12 = $ResultConfig12[1];
			}
			
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArt = sprintf("SELECT 
				vw_iv_art_emp.id_articulo,
				vw_iv_art_emp.codigo_articulo
			FROM vw_iv_articulos_empresa vw_iv_art_emp
			WHERE vw_iv_art_emp.codigo_articulo LIKE %s
				AND vw_iv_art_emp.id_empresa = %s;",
				valTpDato($arrayFila[$indice][0], "text"),
				valTpDato($idEmpresa, "int"));
			$rsArt = mysql_query($queryArt);
			if (!$rsArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArt = mysql_num_rows($rsArt);
			$rowArt = mysql_fetch_assoc($rsArt);
			
			$idArticulo = $rowArt['id_articulo'];
			$hddIdArticuloCosto = $arrayFila[$indice][2];
			$precioUnitario = $arrayFila[$indice][3];
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice2 => $valor2) {
					if ($idArticulo > 0
					&& $frmListaArticulo['hddIdArticuloItm'.$valor2] == $idArticulo
					&& $frmListaArticulo['hddIdArticuloCosto'.$valor2] == $hddIdArticuloCosto) {
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				if (count($arrayObj) < $rowConfig16['valor']) {
					if ($totalRowsArt > 0) {
						$sqlBusq = "";
						$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
						$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
						AND vw_iv_art_almacen_costo.id_empresa = %s
						AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"));
						
						if ($frmDcto['lstTipoMovimiento'] == 4) { // 2 = Entrada, 4 = Salida
							$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
							$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
						}
						
						if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
						} else {
						//if ($frmDcto['lstTipoVale'] == 1) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
							$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
							$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo = %s", 
								valTpDato($hddIdArticuloCosto, "int"));
						//}
						}
						
						$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
						ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC, orden_prioridad_venta ASC LIMIT 1;", $sqlBusq);
						$rsArtCosto = mysql_query($queryArtCosto);
						if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
						while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
							$cantPedida = $arrayFila[$indice][1];
							$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
							$precioUnitario = ($frmDcto['lstTipoVale'] == 1) ? $costoUnitario : $precioUnitario;
							$hddIdArticuloAlmacenCosto = ($hddIdArticuloAlmacenCosto > 0 && $hddIdArticuloAlmacenCosto != $rowArtCosto['id_articulo_almacen_costo']) ? $hddIdArticuloAlmacenCosto : $rowArtCosto['id_articulo_almacen_costo'];
							$hddIdArticuloCosto = ($hddIdArticuloCosto > 0 && $hddIdArticuloCosto != $rowArtCosto['id_articulo_costo']) ? $hddIdArticuloCosto : $rowArtCosto['id_articulo_costo'];
							
							$Result1 = insertarItemArticulo($contFila, $frmDcto, $idEmpresa, "", $idArticulo, $idCasilla, $hddIdArticuloAlmacenCosto, $hddIdArticuloCosto, $cantPedida, $precioUnitario, $costoUnitario, "", "");
							$arrayObjUbicacion = $Result1[3];
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$contFila = $Result1[2];
								$frmListaArticulo['hddIdArticuloItm'.$contFila] = $idArticulo;
								$objResponse->script($Result1[1]);
								$arrayObj[] = $contFila;
							}
						}
						
						if (!($totalRowsArtCosto > 0)) {
							$arrayObjNoUbicacion[] = $arrayFila[$indice][0];
						}
					} else {
						$arrayObjNoExiste[] = $arrayFila[$indice][0];
					}
				} else {
					$msjCantidadExcedida = "Solo puede agregar un máximo de ".$rowConfig16['valor']." items por vale";
				}
			} else {
				$arrayObjExiste[] = $arrayFila[$indice][0];
			}
		}
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
		if (strlen($msjCantidadExcedida) > 0) {
			$objResponse->alert(utf8_encode($msjCantidadExcedida));
		}
		
		if (count($arrayObjNoUbicacion) > 0) {
			$objResponse->alert(utf8_encode("No tiene(n) ubicación en el sistema ".count($arrayObjNoUbicacion)." items:\n".implode("\n",$arrayObjNoUbicacion)));
		}
		
		if (count($arrayObjNoExiste) > 0) {
			$objResponse->alert(utf8_encode("No existe(n) en el sistema ".count($arrayObjNoExiste)." items:\n".implode("\n",$arrayObjNoExiste)));
		}
			
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(utf8_encode("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items:\n".implode("\n",$arrayObjExiste)));
		} else if (count($arrayObj) > 0) {
			$objResponse->alert(utf8_encode("Pedido importado con éxito"));
		} else {
			$objResponse->alert(utf8_encode("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
	} else {
		$objResponse->alert(utf8_encode("Verifique que el pedido tenga cantidades solicitadas"));
	}
	
	return $objResponse;
}

function insertarArticulo($frmDatosArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$hddNumeroArt = $frmDatosArticulo['hddNumeroArt'];
	
	$idArticulo = $frmDatosArticulo['hddIdArticulo'];
	$idCasilla = $frmDatosArticulo['lstCasillaArt'];
	$cantPedida = str_replace(",","",$frmDatosArticulo['txtCantidadArt']);
	$precioUnitario = str_replace(",","",$frmDatosArticulo['txtPrecioArt']);
	
	$hddCantItm = str_replace(",", "", $frmListaArticulo['hddCantItm'.$hddNumeroArt]);
	
	//$hddIdArticuloAlmacenCosto = $frmListaArticulo['hddIdArticuloAlmacenCosto'.$valor];
	$hddIdArticuloCosto = $frmDatosArticulo['lstCostoArt'];
	
	// VERIFICA SI EL LOTE A INSERTAR YA ESTA AGREGADO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$existe = false;
			if ($frmListaArticulo['hddIdArticuloCosto'.$valor] == $hddIdArticuloCosto) {
				$existe = true;
				$hddNumeroArt = $valor;
			}
			
			$hddNumeroArt = ($existe == true) ? $hddNumeroArt : "";
		}
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}

	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Vale de Entrada y Salida)
	$queryConfig16 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 16 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig16 = mysql_query($queryConfig16);
	if (!$rsConfig16) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig16 = mysql_fetch_assoc($rsConfig16);
	
	if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
		$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
		WHERE id_articulo = %s
			AND id_casilla = %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"));
	} else {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		
		if ($frmDcto['lstTipoVale'] == 1) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo = %s", 
				valTpDato($hddIdArticuloCosto, "int"));
		}
		
		if ($frmDcto['lstTipoMovimiento'] == 4) { // 2 = Entrada, 4 = Salida
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla = %s", 
				valTpDato($idCasilla, "int"));
		}
		
		$queryArtEmp = sprintf("SELECT SUM(cantidad_disponible_logica) AS cantidad_disponible_logica FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s;", $sqlBusq);
	}
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	if (($frmDcto['lstTipoMovimiento'] == 2 && doubleval($cantPedida) > 0)
	|| ($frmDcto['lstTipoMovimiento'] == 4 && doubleval($rowArtEmp['cantidad_disponible_logica'] - $cantPedida) >= 0)) {
		/*$arrayIdArticuloCosto = array(-1);
		$cantFaltante = $cantPedida - $hddCantItm;
		$cantFaltante = ($cambiarUbicacion == true) ? $cantPedida : $cantFaltante;*/
		
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		
		if (!in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla = %s", 
				valTpDato($idCasilla, "int"));
		}
		
		if ($frmDcto['lstTipoVale'] == 1 && $frmDcto['lstTipoMovimiento'] == 4) { // 1 = De Entrada / Salida, 3 = De Nota Crédito de CxC; 2 = Entrada, 4 = Salida
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.estatus_articulo_costo = 1
			AND vw_iv_art_almacen_costo.id_articulo_costo = %s", 
				valTpDato($hddIdArticuloCosto, "int"));
		}
			
		$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
		ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC
		LIMIT 1;", $sqlBusq);
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
		while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
			$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
			$precioUnitario = ($frmDcto['lstTipoVale'] == 1) ? $costoUnitario : $precioUnitario;
			$hddIdArticuloAlmacenCosto = ($hddIdArticuloCosto > 0 && $hddIdArticuloCosto != $rowArtCosto['id_articulo_costo']) ? $hddIdArticuloAlmacenCosto : $rowArtCosto['id_articulo_almacen_costo'];
			$hddIdArticuloCosto = ($hddIdArticuloCosto > 0 && $hddIdArticuloCosto != $rowArtCosto['id_articulo_costo']) ? $hddIdArticuloCosto : $rowArtCosto['id_articulo_costo'];
			
			if ($hddNumeroArt > 0) {
				$objResponse->assign("hddCantItm".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
				$objResponse->assign("hddPrecioItm".$hddNumeroArt,"value",number_format($precioUnitario, 2, ".", ","));
				$objResponse->assign("hddCostoItm".$hddNumeroArt,"value",number_format($costoUnitario, 2, ".", ","));
				$objResponse->assign("txtTotalItm".$hddNumeroArt,"value",number_format(($cantPedida * $precioUnitario), 2, ".", ","));
				
				$objResponse->script("
				if (byId('hddNumeroArt').value > 0) {
					if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
						byId('txtCriterioBuscarArticulo').focus();
						byId('txtCriterioBuscarArticulo').select();
					} else {
						document.forms['frmBuscarArticulo'].reset();
						byId('txtCodigoArticulo0').focus();
						byId('txtCodigoArticulo0').select();
					}
				} else {
					document.forms['frmDatosArticulo'].reset();
					byId('txtDescripcionArt').innerHTML = '';
					
					if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
						byId('txtCriterioBuscarArticulo').focus();
						byId('txtCriterioBuscarArticulo').select();
					} else {
						document.forms['frmBuscarArticulo'].reset();
						byId('txtCodigoArticulo0').focus();
						byId('txtCodigoArticulo0').select();
					}
				}");
			} else {
				// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
				$arrayObj = $frmListaArticulo['cbx'];
				$contFila = $arrayObj[count($arrayObj)-1];
				
				if (count($arrayObj) < $rowConfig16['valor']) {
					$Result1 = insertarItemArticulo($contFila, $frmDcto, $idEmpresa, "", $idArticulo, $idCasilla, $hddIdArticuloAlmacenCosto, $hddIdArticuloCosto, $cantPedida, $precioUnitario, $costoUnitario, $almacen, $ubicacion);
					$arrayObjUbicacion = $Result1[3];
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila = $Result1[2];
						$objResponse->script($Result1[1]);
						$arrayObj[] = $contFila;
					}
					
					$objResponse->script("
					if (!(byId('hddNumeroArt').value > 0)) {
						document.forms['frmDatosArticulo'].reset();
						byId('txtDescripcionArt').innerHTML = '';
						
						if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
							byId('txtCriterioBuscarArticulo').focus();
							byId('txtCriterioBuscarArticulo').select();
						} else {
							document.forms['frmBuscarArticulo'].reset();
							byId('txtCodigoArticulo0').focus();
							byId('txtCodigoArticulo0').select();
						}
					}");
					
					$objResponse->assign("divListaArticulo","innerHTML","");
					
					$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
				} else {
					return $objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig16['valor']." items por Vale"));
				}
			}
		}
	} else if ($totalRowsArtEmp > 0 && $hddIdArticuloCosto > 0) {
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
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "10%", $pageNum, "cantidad_pedida", $campOrd, $tpOrd, $valBusq, $maxRows, "Pedida a Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
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
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
		$htmlTb .= "<td colspan=\"5\">";
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

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idDepartamentoNotaCredito IN (0,1,2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("subtotalNotaCredito > 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((idDepartamentoNotaCredito = 0
		AND (SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle
			WHERE id_nota_credito = idNotaCredito) = 0)
	OR (idDepartamentoNotaCredito = 1
		AND tipoDocumento = 'FA'
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN cj_cc_factura_detalle fact_vent_det ON (fact_vent.idFactura = fact_vent_det.id_factura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN sa_det_fact_tempario fact_vent_det_temp ON (fact_vent.idFactura = fact_vent_det_temp.idFactura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN sa_det_fact_tot fact_vent_det_tot ON (fact_vent.idFactura = fact_vent_det_tot.idFactura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		AND (SELECT COUNT(fact_vent.idFactura) FROM cj_cc_encabezadofactura fact_vent
				INNER JOIN sa_det_fact_notas fact_vent_det_nota ON (fact_vent.idFactura = fact_vent_det_nota.idFactura)
			WHERE fact_vent.idFactura = nota_cred.idDocumento) = 0
		)
	)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_vale_entrada) FROM iv_vale_entrada
	WHERE id_documento = idNotaCredito AND tipo_vale_entrada = 3) = 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s
		OR CONCAT_WS('-' , cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT nota_cred.*,
		cliente.id AS id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		(nota_cred.subtotalNotaCredito - subtotal_descuento + ivaLujoNotaCredito + ivaNotaCredito) AS total_nota_credito
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id) %s", $sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "numeracion_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Nota Créd."));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "64%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "total_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgPedidoModulo = "";
		$imgPedidoModuloCondicion = "";
		if ($row['estatus_pedido_venta'] == "") {
			$imgPedidoModuloCondicion = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("Nota Crédito CxC")."\"/>";
			
			switch($row['idDepartamentoNotaCredito']) {
				case 0 :$imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Nota Crédito Repuestos")."\"/>"; break;
				case 1 :$imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Nota Crédito Servicios")."\"/>"; break;
				case 2 :$imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Nota Crédito Vehículos")."\"/>"; break;
			}
		} else {
			$imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Nota Crédito Repuestos")."\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button type=\"button\" onclick=\"xajax_asignarDcto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>",
				$row['idNotaCredito']);//
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_nota_credito']."</td>";
			$htmlTb .= "<td>".utf8_decode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_nota_credito'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
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

function nuevoDcto($frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm=>$valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
	WHERE inv_fis.id_empresa = %s
		AND inv_fis.estatus = 0",
		valTpDato($idEmpresa , "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsInvFis = mysql_num_rows($rsInvFis);
	
	if ($totalRowsInvFis == 0) {
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
			$objResponse->script("
			byId('txtObservacion').className = 'inputHabilitado';");
			
			// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
			$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
				valTpDato($_SESSION['idUsuarioSysGts'], "int"));
			$rsUsuario = mysql_query($queryUsuario);
			if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowUsuario = mysql_fetch_assoc($rsUsuario);
			
			$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
			$objResponse->assign("txtFecha","value",date(spanDateFormat));
			$objResponse->assign("txtIdEmpleado","value",$rowUsuario['id_empleado']);
			$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
			
			$objResponse->loadCommands(asignarTipoVale(""));
		} else {
			$objResponse->script("
			alert('".utf8_encode("No puede crear un pedido, debido a que aún no se ha realizado el Cierre del Mes anterior")."');
			location='iv_pedido_venta_list.php';");
		}
	} else {
		$objResponse->script("
		alert('".utf8_encode("Usted no puede Crear Vales de Entrada o Salida, debido a que está en Proceso un Inventario Físico")."');
		location='iv_ajuste_inventario_list.php';");
	}
	
	return $objResponse;
}

function validarPermiso($frmPermiso, $frmDatosArticulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "iv_ajuste_inventario_form_costo") {
			$objResponse->assign("hddCostoCero","value",true);
			$objResponse->script("
			byId('txtCostoArt').className = 'inputHabilitado';
			byId('txtCostoArt').readOnly = false;
			byId('aDesbloquearCosto').style.display = 'none';
			
			byId('txtCostoArt').focus();
			byId('txtCostoArt').select();");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarDcto");
$xajax->register(XAJAX_FUNCTION,"asignarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"asignarLote");
$xajax->register(XAJAX_FUNCTION,"asignarTipoVale");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");
$xajax->register(XAJAX_FUNCTION,"nuevoDcto");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function insertarItemArticulo($contFila, $frmDcto, $idEmpresa, $idPedidoCompraDetalle = "", $idArticulo = "", $idCasilla = "", $hddIdArticuloAlmacenCosto = "", $hddIdArticuloCosto = "", $cantPedida = "", $precioUnitario = "", $costoUnitario = "", $almacen = "", $ubicacion = "") {
	global $spanPrecioUnitario;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return array(false, $ResultConfig12[1], $contFila, $arrayObjUbicacion);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	if ($idPedidoCompraDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoCompraDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
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
	
	$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
	$abrevMonedaCostoUnitario = $rowArtCosto['abreviacion'];
	$precioUnitario = ($frmDcto['lstTipoVale'] == 1) ? $costoUnitario : $precioUnitario;
	
	$idArticulo = ($idArticulo == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_articulo'] : $idArticulo;
	$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
	$precioUnitario = ($precioUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $precioUnitario;
	$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['costo_unitario'] : $costoUnitario;
	$idCasilla = ($idCasilla == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_casilla'] : $idCasilla;
	
	// VERIFICA LA CANTIDAD DE UBICACIONES QUE TIENE
	$queryUbicArt = sprintf("SELECT * FROM vw_iv_articulos_almacen
	WHERE id_empresa = %s
		AND id_articulo = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rsUbicArt = mysql_query($queryUbicArt);
	if (!$rsUbicArt) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$totalRowsUbicArt = mysql_num_rows($rsUbicArt);
	
	if ($idCasilla > 0 || ($almacen != "" && $ubicacion != "")) {
		// BUSCA SI EL ARTICULO TIENE ASIGNADA LA UBICACION
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND ((descripcion_almacen LIKE %s
					AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', ''))
				OR id_casilla = %s)
			AND estatus_articulo_almacen = 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($almacen, "text"),
			valTpDato($ubicacion, "text"),
			valTpDato($idCasilla, "int"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		if ($totalRowsArtAlm > 0) {
			$idCasilla = $rowArtAlm['id_casilla'];
		} else {
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas
			WHERE descripcion_almacen LIKE %s
				AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '');",
				valTpDato($almacen, "text"),
				valTpDato($ubicacion, "text"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
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
			if (!$rsArtAlm) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			
			$existe = ($totalRowsArtAlm > 0) ? true : $existe;
			
			if ($existe == false) {
				$idCasilla = $rowUbic['id_casilla'];
			} else {
				$totalRowsArtAlm = 0;
				$idCasilla = "-1";
			}
		}
	} else {
		// BUSCA LA UBICACION PREDETERMINADA
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND casilla_predeterminada = 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		$idCasilla = $rowArtAlm['id_casilla'];
	}
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
	$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rsUbicacion = mysql_query($queryUbicacion);
	if (!$rsUbicacion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
	$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
	
	$ubicacion = $rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion'];
	
	if ($totalRowsUbicArt > 1) {
		$claseAlmacen = "trResaltar7";
	} else if (!($idCasilla > 0) && $totalRowsArtAlm == 0) {
		$claseAlmacen = "trResaltar6";
	}
	
	$claseCosto = ($precioUnitario > 0) ? "" : "divMsjError";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td id=\"tdCodArt:%s\">%s</td>".
			"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
				"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"%s\">".
				"<tr><td colspan=\"2\"><span id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</span></td></tr>".
				"</table>".
				"%s</td>".
			"<td><input type=\"text\" id=\"hddCantItm%s\" name=\"hddCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td class=\"%s\"><input type=\"text\" id=\"hddPrecioItm%s\" name=\"hddPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdValeDet%s\" name=\"hddIdValeDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" title=\"Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			$contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
				"100%",
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
				(in_array($ResultConfig12, array(1,2)) ? "" : "<br><span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</span>"),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$claseCosto, $contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
				$contFila, $contFila, number_format($costoUnitario, 2, ".", ","),
			$contFila, $contFila, number_format(($cantPedida * $precioUnitario), 2, ".", ","),
				$contFila, $contFila, $idValeDetalle,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdArticuloAlmacenCosto,
				$contFila, $contFila, $hddIdArticuloCosto,
				$contFila, $contFila, $idCasilla);
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}
?>