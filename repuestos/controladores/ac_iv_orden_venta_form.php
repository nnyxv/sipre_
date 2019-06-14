<?php
include("../clases/num2letras.php");

function asignarArticulo($hddNumeroArt, $idArticulo, $frmDcto, $frmListaArticulo, $cerrarVentana = "true") {
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
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
	$Result1 = actualizacionEsperaPorFacturar($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
	$Result1 = actualizarSaldos($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	$objResponse->script("
	byId('tdUbicacion').style.visibility = '".((!in_array($ResultConfig12, array(1,2))) ? "hidden" : "")."';
	byId('tdlstUbicacion').style.visibility = '".((!in_array($ResultConfig12, array(1,2))) ? "hidden" : "")."';
	byId('tdMsjArticulo').style.display = 'none';");
	
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
				AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
				AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_costo = %s", 
					valTpDato($frmListaArticulo['hddIdArticuloCosto'.$valor], "int"));
				
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
					$hddNumeroArt = $valor;
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
	$objResponse->assign("hddIdArticuloCosto","value",$frmListaArticulo['hddIdArticuloCosto'.$hddNumeroArt]);
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
		
		$objResponse->assign("txtCantidadArt","value",str_replace(",", "", $frmListaArticulo['txtCantItm'.$hddNumeroArt]) + $txtCantidadArt);
		$objResponse->assign("hddIdIvaArt","value",((count($arrayIdIvaArt) > 0) ? implode(",",$arrayIdIvaArt) : ""));
		$objResponse->assign("txtIvaArt","value",((count($arrayIvaArt) > 0) ? implode(", ",$arrayIvaArt) : ""));
		
		$hddIdPrecioItm = $frmListaArticulo['hddIdPrecioItm'.$hddNumeroArt];
		$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$hddNumeroArt]);
		$precioSugerido = str_replace(",", "", $frmListaArticulo['hddPrecioSugeridoItm'.$hddNumeroArt]);
		$selIdCasilla = $frmListaArticulo['hddIdCasilla'.$hddNumeroArt];
		
		$onChange = "xajax_asignarPrecio('".$idArticulo."', '".$idArticuloCosto."', this.value, xajax.getFormValues('frmDcto'), 'false', '".$precioUnitario."');";
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
	$objResponse->loadCommands(asignarPrecio($idArticulo, $idArticuloCosto, $selIdPrecio, $frmDcto, "true", $precioUnitario));
	
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
	
	
	// BUSCA LAS UBICACIONES PARA LA VENTA DEL ARTICULO
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND id_empresa = %s
		AND estatus_articulo_almacen = 1
		AND estatus_almacen_venta = 1;",
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
	
	$htmlLstIni = "<select id=\"lstCasillaArt\" name=\"lstCasillaArt\" class=\"inputHabilitado\" onchange=\"xajax_asignarDisponibilidadUbicacion(this.value,'txtCantidadUbicacion');\" style=\"width:99%\">";
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
						$objResponse->script("xajax_asignarDisponibilidadUbicacion('".$arrayUbicacion[$indice2]['id_casilla']."','txtCantidadUbicacion');");
					}
					
					$htmlLst .= "<option ".$selected." value=\"".$arrayUbicacion[$indice2]['id_casilla']."\">".utf8_encode(str_replace("-[]", "", $arrayUbicacion[$indice2]['ubicacion']))."</option>";
				}
			}
			$htmlLst .= "</optgroup>";
		}
	}
	$htmlLstFin = "</select>";
	$objResponse->assign("tdlstCasillaArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	
	if ($txtCantidadArt > 0 && $hddNumeroArt > 0) { // SI EL ARTICULO YA ESTA AGREGADO EN LA LISTA
		$objResponse->script("xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarArticuloSustituto').click();");
	}
	
	return $objResponse;
}

function asignarDisponibilidadUbicacion($idCasilla, $objetoDestino) {
	$objResponse = new xajaxResponse();
	
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_casilla = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idCasilla, "int"));
	$rsUbic = mysql_query($queryUbic);
	if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUbic = mysql_fetch_assoc($rsUbic);
	
	$objResponse->assign($objetoDestino,"value",$rowUbic['cantidad_disponible_logica']);
	
	return $objResponse;
}

function asignarGasto($frmLista, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$hddNumeroItm = $frmLista['hddNumeroItm'];
	
	$totalGastoArt = 0;
	for ($contObj = 1; isset($frmLista['hddIdGasto'.$contObj]); $contObj++) {
		if ($frmLista['txtMontoGasto'.$contObj] > 0) {
			$htmlGastoArt .= sprintf("<input type='hidden' id='hddIdGastoArt:%s:%s' name='hddIdGastoArt:%s:%s' readonly='readonly' value='%s'>",
				$hddNumeroItm, $contObj, $hddNumeroItm, $contObj, $frmLista['hddIdGasto'.$contObj]);
			$htmlGastoArt .= sprintf("<input type='hidden' id='txtMontoGastoArt:%s:%s' name='txtMontoGastoArt:%s:%s' readonly='readonly' value='%s'/>",
				$hddNumeroItm, $contObj, $hddNumeroItm, $contObj, $frmLista['txtMontoGasto'.$contObj]);
			
			$totalGastoArt += $frmLista['txtMontoGasto'.$contObj];
		}
	}
	
	$objResponse->assign("tdItmGasto:".$hddNumeroItm,"innerHTML",number_format($totalGastoArt, 2, ".", ","));
	$objResponse->assign("tdItmGastoObj:".$hddNumeroItm,"innerHTML",$htmlGastoArt);
	
	$objResponse->assign("hddGastoItm".$hddNumeroItm,"value",number_format($totalGastoArt, 2, ".", ","));
	
	$totalArt = (str_replace(",", "", $frmListaArticulo['txtCantItm'.$hddNumeroItm]) * str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$hddNumeroItm])) + $totalGastoArt;
	
	$objResponse->assign("txtTotalItm".$hddNumeroItm,"value",number_format($totalArt, 2, ".", ","));
	
	$objResponse->script("byId('btnCancelarLista').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarPrecio($idArticulo, $idArticuloCosto, $idPrecio, $frmDcto, $precioPredet = "false", $precioUnitario = "") {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idMoneda = $frmDcto['hddIdMoneda'];
	
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
	
	$idArticuloCosto = ($idArticuloCosto > 0) ? $idArticuloCosto : $rowArtCosto['id_articulo_costo'];
	
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
		AND art_precio.id_articulo_costo = %s
		AND art_precio.id_precio = %s
		AND art_precio.id_moneda = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idArticuloCosto, "int"),
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
	$objResponse->script("byId('txtPrecioArt').readOnly = true;");
	$objResponse->assign("tdMonedaPrecioArt","innerHTML",$rowArtPrecio['abreviacion_moneda']);
	
	if ($precioPredet == "true") {
		$objResponse->assign("hddIdPrecioArtPredet","value",$idPrecio);
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

function buscarGasto($frmBuscarGasto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarGasto['txtCriterioBuscarGasto']);
	
	$objResponse->loadCommands(listaGasto(0, "nombre", "ASC", $valBusq));
	
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $guardarDcto = "false", $calcularDcto = "false", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
			
			$objResponse->script("
			var frm = document.forms['frmListaArticulo'];
			for (i = 0; i < frm.length; i++){
				if (frm.elements[i].id == 'cbxItm'){
					if (frm.elements[i].value == '".$valor."'){
						frm.elements[i].style.display = 'none';
						frm.elements[i].disabled = true;
					}
				}
			}
			byId('aEditarItem:".$valor."').style.display = 'none';");
			
			$existe = false;
			if (isset($arrayDesbloquear)) {
				foreach($arrayDesbloquear as $indice2 => $valor2) {
					if ($arrayDesbloquear[$indice2][0] == $frmListaArticulo['hddIdArticuloItm'.$valor]) {
						$existe = true;
						$arrayDesbloquear[$indice2][1] = $valor;
					}
				}
			}
			
			if ($existe == false) {
				$arrayDesbloquear[] = array(
					$frmListaArticulo['hddIdArticuloItm'.$valor],
					$valor);
			}
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
				if (frm.elements[i].id == 'cbxItm'){
					if (frm.elements[i].value == '".$arrayDesbloquear[$indice][1]."'){
						frm.elements[i].style.display = '';
						frm.elements[i].disabled = false;
					}
				}
			}
			byId('aEditarItem:".$arrayDesbloquear[$indice][1]."').style.display = '';");
		}
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdPedido'];
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
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
			
			$txtSubTotal += $txtTotalItm;
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$subTotalItm = $txtTotalItm;
			$totalDescuentoItm = ($hddTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($subTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$subTotalItm = $subTotalItm - $totalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						
						$arrayIvaItm[$frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = ($hddIdIvaItm > 0) ? $hddIdIvaItm : -1;
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
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$arrayIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExento += $subTotalItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$arrayIvaItm[$idIva]]) : $porcIva;
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
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $hddCantRecibItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
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
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$arrayPosIvaItm = array(-1);
				$arrayIdIvaItm = array(-1);
				$arrayIvaItm = array(-1);
				$arrayEstatusIvaItm = array(-1);
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indiceIvaGasto => $valorIvaGasto) {
						$valorIvaGasto = explode(":", $valorIvaGasto);
						
						if ($valorIvaGasto[0] == $valor) {
							$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valorIvaGasto[1]]] = $valorIvaGasto[1];
							$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valorIvaGasto[1]];
							$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valorIvaGasto[1]];
							$arrayEstatusIvaItm[] = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valorIvaGasto[1]];
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
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if (($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIva += $txtMontoGasto; break;
							default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
					}
				} else if (!($totalRowsIva > 0)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIva += $txtMontoGasto; break;
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
					}
				}
			}
			
			$txtTotalGasto += ($frmTotalDcto['hddIdModoGasto'.$valor] == 1) ? str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]) : 0;
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
		$subTotalDescuento = $subTotalDescuentoItm;
		
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
	
	$subTotalDescuento = $txtSubTotal * ($porcDescuento / 100);
	$totalOrden = doubleval($txtSubTotal) - doubleval($subTotalDescuento);
	$totalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);
	
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($subTotalDescuento, 2, ".", ","));	
	$objResponse->assign("txtTotalOrden", "value", number_format($totalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva', "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign('txtGastosSinIva', "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalGasto", "value", number_format($txtTotalGasto, 2, ".", ","));
	
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
	
	if (count($arrayObj) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpleado').className = 'inputInicial';
		byId('txtIdEmpleado').readOnly = true;
		byId('aListarEmpleado').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		 
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$idMonedaLocal.");
		}");
		
		if (in_array($guardarDcto, array("1", "true"))) {
			$objResponse->script("
			window.setTimeout(function(){ xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), '".$bloquearForm."'); },1000);");
		}
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

function cargarDcto($idDocumento, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$queryPedido = sprintf("SELECT
		vw_iv_ped_vent.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_pedidos_venta vw_iv_ped_vent
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE vw_iv_ped_vent.id_pedido_venta = %s
		AND (vw_iv_ped_vent.estatus_pedido_venta = 1
		OR (vw_iv_ped_vent.estatus_pedido_venta = 2
			AND (vw_iv_ped_vent.id_empleado_aprobador IS NULL OR vw_iv_ped_vent.id_empleado_aprobador = 0)));",
		valTpDato($idDocumento, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$idCliente = $rowPedido['id_cliente'];
	
	if ($rowPedido['id_pedido_venta'] > 0) {
		$objResponse->script("
		byId('txtDescuento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		// BUSCA LOS DATOS DEL USUARIO APROBADOR PARA SABER SUS DATOS PERSONALES
		$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuario = mysql_fetch_assoc($rsUsuario);
		
		// SE COMIENZA A CARGAR EL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle WHERE id_pedido_venta = %s
		ORDER BY id_pedido_venta_detalle",
			valTpDato($idDocumento, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		$arrayObj = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemArticulo($contFila, $rowPedidoDet['id_pedido_venta_detalle'], "", $idCliente, $rowPedidoDet['id_articulo'], $rowPedidoDet['id_casilla'], $rowPedidoDet['id_articulo_almacen_costo'], $rowPedidoDet['id_articulo_costo'], $rowPedidoDet['cantidad'], $rowPedidoDet['pendiente'], $rowPedidoDet['id_precio'], $rowPedidoDet['precio_unitario'], $rowPedidoDet['precio_sugerido'], "", "", $rowPedidoDet['id_iva']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		// BUSCA LOS GASTOS DEL PEDIDO
		$queryPedidoGasto = sprintf("SELECT * FROM iv_pedido_venta_gasto ped_vent_gasto WHERE id_pedido_venta = %s
		ORDER BY id_pedido_venta_gasto ASC;",
			valTpDato($idDocumento, "int"));
		$rsPedidoGasto = mysql_query($queryPedidoGasto);
		if (!$rsPedidoGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowPedidoGasto = mysql_fetch_assoc($rsPedidoGasto)) {
			$Result1 = insertarItemGasto($contFilaGasto, "", $rowPedidoGasto['id_pedido_venta_gasto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFilaGasto = $Result1[2];
				$frmListaArticulo['hddIdPedidoGasto'.$contFilaGasto] = $rowPedidoGasto['id_pedido_venta_gasto'];
				$objResponse->script($Result1[1]);
				$arrayObjGasto[] = $contFilaGasto;
			}
		}
		
		// DATOS DEL CLIENTE
		$queryCliente = sprintf("SELECT
			cliente_emp.id_cliente_empresa,
			cliente_emp.id_empresa,
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
			INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		WHERE id = %s
			AND id_empresa = %s;",
			valTpDato($rowPedido['id_cliente'], "int"),
			valTpDato($rowPedido['id_empresa'], "int"));
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsCliente = mysql_num_rows($rsCliente);
		$rowCliente = mysql_fetch_assoc($rsCliente);
		
		if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
			$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
				valTpDato($rowCliente['id_cliente_empresa'], "int"));
			$rsClienteCredito = mysql_query($queryClienteCredito);
			if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
			
			$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
			
			$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
			$objResponse->assign("txtCreditoCliente","value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		} else {
			$objResponse->assign("txtDiasCreditoCliente","value","0");
		}
		
		$nombreCondicionPago = ($rowPedido['condicion_pago'] == 0) ? "CRÉDITO" : "CONTADO";
		$objResponse->assign("hddTipoPago","value",utf8_encode($rowPedido['condicion_pago']));
		$objResponse->assign("txtTipoPago","value",utf8_encode($nombreCondicionPago));
		
		// DATOS DE LA ORDEN
		$objResponse->assign("txtFechaAprobacion","value",date(spanDateFormat));
		$objResponse->assign("hddIdEmpleadoAprobado","value",$_SESSION['idEmpleadoSysGts']);
		$objResponse->assign("txtNombreEmpleadoAprobado","value",utf8_encode($rowUsuario['nombre_empleado']));
		$objResponse->assign("txtFechaAprobado","value",date(spanDateFormat));
		$objResponse->assign("hddSobregiroAprobado","value","0");
		
		// DATOS DEL CLIENTE
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
		$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
		$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
		$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
		$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $totalRowsCliente > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
		
		// DATOS DEL PEDIDO
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($rowPedido['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowPedido['nombre_empresa']));
		$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido_venta']);
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat,strtotime($rowPedido['fecha'])));
		$objResponse->assign("txtNumeroPedidoPropio","value",(($rowPedido['id_pedido_venta_propio'] != "") ? $rowPedido['id_pedido_venta_propio'] : ""));
		$objResponse->assign("txtNumeroReferencia","value",(($rowPedido['id_pedido_venta_referencia'] != "") ? $rowPedido['id_pedido_venta_referencia'] : ""));
		$objResponse->assign("hddIdPresupuestoVenta","value",(($rowPedido['id_presupuesto_venta'] != "") ? $rowPedido['id_presupuesto_venta'] : ""));
		$objResponse->assign("txtNumeroPresupuestoVenta","value",(($rowPedido['numeracion_presupuesto'] != "") ? $rowPedido['numeracion_presupuesto'] : ""));
		$objResponse->assign("hddIdMoneda","value",utf8_encode($rowPedido['id_moneda']));
		$objResponse->assign("txtMoneda","value",utf8_encode($rowPedido['descripcion']));
		$objResponse->assign("txtTipoClave","value","3.- VENTA");
		$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowPedido['descripcion_clave_movimiento']));
		$objResponse->assign("txtDescuento","value",$rowPedido['porcentaje_descuento']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowPedido['observaciones']));
		$objResponse->assign("txtIdEmpleado","value",$rowPedido['id_empleado_preparador']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowPedido['nombre_empleado']));
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'false', 'true');");
	} else {
		$objResponse->alert(utf8_encode("El Pedido no puede ser cargado debido a que su status no es válido"));
		
		$objResponse->script(sprintf("window.location.href='iv_orden_venta_list.php';"));
	}
	
	return $objResponse;
}

function desaprobarDcto($frmDesaprobarDcto, $frmDcto, $frmListaArticulo, $frmTotalDcto, $docOrden = "false") {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_orden_venta_list","insertar")) { return $objResponse; }
	
	if (!(($frmDcto['hddTipoPago'] == 0 && xvalidaAcceso($objResponse,"iv_orden_venta_list_credito_desaprobar","insertar"))
	|| ($frmDcto['hddTipoPago'] == 1 && $frmTotalDcto['txtDescuento'] == 0 && xvalidaAcceso($objResponse,"iv_orden_venta_list_contado_desaprobar","insertar"))
	|| ($frmDcto['hddTipoPago'] == 1 && $frmTotalDcto['txtDescuento'] > 0 && xvalidaAcceso($objResponse,"iv_orden_venta_list_contado_descuento_desaprobar","insertar")))) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	mysql_query("START TRANSACTION;");
	
	$idDocumentoVenta = $frmDcto['txtIdPedido'];
	
	// BUSCA LOS DATOS DEL PEDIDO DE VENTA
	$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta
	WHERE id_pedido_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$estatusPedidoVenta = $frmDesaprobarDcto['lstEstatusPedido'];
	
	// INSERTA O ACTUALIZA LOS DETALLES DEL PEDIDO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idPedidoDet = $frmListaArticulo['hddIdPedidoDet'.$valor];
			
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle
			WHERE id_pedido_venta_detalle = %s;",
				valTpDato($idPedidoDet, "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			$idArticulo = $rowPedidoDet['id_articulo'];
			$idCasilla = $rowPedidoDet['id_casilla'];
			$hddIdArticuloCosto = $rowPedidoDet['id_articulo_costo'];
			$cantPedida = doubleval($rowPedidoDet['cantidad']);
			$cantPendiente = doubleval($rowPedidoDet['pendiente']);
			
			if ($estatusPedidoVenta == 5) { // 0 = Pendiente por Terminar, 5 = Anulada
				// ANULA EL DETALLE DEL PEDIDO
				$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
					estatus = %s
				WHERE id_pedido_venta_detalle = %s;",
					valTpDato(2, "int"), // 0 = En Espera, 1 = Despachado, 2 = Anulado
					valTpDato($idPedidoDet, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
				$Result1 = actualizacionEsperaPorFacturar($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
				// SE CONECTA CON EL SISTEMA DE SOLICITUDES
				$Result1 = actualizarCantidadSistemaSolicitud($frmDcto['txtNumeroReferencia'], $idArticulo, $hddIdArticuloCosto, $cantPendiente);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
		}
	}
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE iv_pedido_venta SET
		id_empleado_aprobador = NULL,
		fecha_aprobacion = NULL,
		estatus_pedido_venta = %s
	WHERE id_pedido_venta = %s
		AND (estatus_pedido_venta = 1
		OR (estatus_pedido_venta = 2 AND (id_empleado_aprobador IS NULL OR id_empleado_aprobador = 0)));",
		valTpDato($estatusPedidoVenta, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelto, 5 = Anulado
		valTpDato($idDocumentoVenta, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// SE CONECTA CON EL SISTEMA DE SOLICITUDES
	$Result1 = actualizarEstatusSistemaSolicitud($rowPedido['id_pedido_venta_referencia'], $estatusPedidoVenta);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->alert($Result1[1]);
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Pedido de Venta Desaprobado con Éxito"));
		
	$objResponse->script(sprintf("window.location.href='iv_orden_venta_list.php';"));
	
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');");
		
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

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $bloquearForm = "false") {
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
	
	mysql_query("START TRANSACTION;");
	
	$idDocumentoVenta = $frmDcto['txtIdPedido'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	
	// BUSCA LOS DATOS DEL PEDIDO DE VENTA
	$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta WHERE id_pedido_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$estatusPedidoVenta = 2;
	
	// VERIFICA SI LOS ARTICULOS ALMACENADOS EN LA BD EN EL PEDIDO AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle WHERE id_pedido_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($rowPedidoDet['id_pedido_venta_detalle'] == $frmListaArticulo['hddIdPedidoDet'.$valor]) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$idPedidoDet = $rowPedidoDet['id_pedido_venta_detalle'];
			$idArticulo = $rowPedidoDet['id_articulo'];
			$idCasilla = $rowPedidoDet['id_casilla'];
			$hddIdArticuloCosto = $rowPedidoDet['id_articulo_costo'];
			$cantPend = doubleval($rowPedidoDet['pendiente']);
		
			// ELIMINA EL DETALLE DEL PEDIDO
			$deleteSQL = sprintf("DELETE FROM iv_pedido_venta_detalle WHERE id_pedido_venta_detalle = %s;",
				valTpDato($idPedidoDet, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
			$Result1 = actualizacionEsperaPorFacturar($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// SE CONECTA CON EL SISTEMA DE SOLICITUDES
			$Result1 = actualizarCantidadSistemaSolicitud($frmDcto['txtNumeroReferencia'], $idArticulo, $hddIdArticuloCosto, 0);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
	}
	
	// INSERTA O ACTUALIZA LOS DETALLES DEL PEDIDO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idDocumentoDetalle = $frmListaArticulo['hddIdPedidoDet'.$valor];
			$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valor];
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
			$hddIdArticuloAlmacenCosto = $frmListaArticulo['hddIdArticuloAlmacenCosto'.$valor];
			$hddIdArticuloCosto = $frmListaArticulo['hddIdArticuloCosto'.$valor];
			$cantPedida = $frmListaArticulo['txtCantItm'.$valor];
			$cantPendiente = $frmListaArticulo['txtCantItm'.$valor];
			$precioUnitario = $frmListaArticulo['txtPrecioItm'.$valor];
			$precioSugerido = $frmListaArticulo['hddPrecioSugeridoItm'.$valor];
			$gastoUnitario = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$valor]) / $cantPendiente;
			$hddIdIvaItm = "";
			$hddIvaItm = 0;
			if (isset($arrayObjIvaItm)) { // RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
					}
				}
			}
			
			if ($idArticulo > 0 && $cantPedida > 0) {
				if ($idDocumentoDetalle > 0) {
					$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle WHERE id_pedido_venta_detalle = %s;",
						valTpDato($idDocumentoDetalle, "int"));
					$rsPedidoDet = mysql_query($queryPedidoDet);
					if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
					
					$idCasillaAnt = ($idCasilla != $rowPedidoDet['id_casilla']) ? $rowPedidoDet['id_casilla'] : "";
					$cantPedidaAnt = ($cantPedida != $rowPedidoDet['cantidad']) ? $rowPedidoDet['cantidad'] : "";
					
					// ACTUALIZA QUIEN MODIFICO EL PRECIO EN CASO DE QUE EL CAMPO SEA NULO
					$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
						id_empleado_creador = %s
					WHERE id_pedido_venta_detalle = %s
						AND (precio_unitario <> %s
							OR id_empleado_creador IS NULL);",
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($idDocumentoDetalle, "int"),
						valTpDato($precioUnitario, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
						id_casilla = %s,
						id_articulo_almacen_costo = %s,
						id_articulo_costo = %s,
						cantidad = %s,
						pendiente = %s,
						id_precio = %s,
						precio_unitario = %s,
						precio_sugerido = %s,
						id_iva = %s,
						iva = %s
					WHERE id_pedido_venta_detalle = %s;",
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantPedida, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdPrecioItm'.$valor], "int"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($precioSugerido, "real_inglesa"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($idDocumentoDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO iv_pedido_venta_detalle (id_pedido_venta, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, pendiente, id_precio, precio_unitario, precio_sugerido, id_iva, iva, id_empleado_creador)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idDocumentoVenta, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantPedida, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdPrecioItm'.$valor], "int"),
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
					
					$objResponse->assign("hddIdPedidoDet".$valor,"value",$idDocumentoDetalle);
				}
				
				// ELIMINA LOS IMPUESTOS DEL DETALLE DEL PEDIDO
				$deleteSQL = sprintf("DELETE FROM iv_pedido_venta_detalle_impuesto WHERE id_pedido_venta_detalle = %s;",
					valTpDato($idDocumentoDetalle, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
				// ELIMINA LOS GASTOS DEL DETALLE DEL PEDIDO
				$deleteSQL = sprintf("DELETE FROM iv_pedido_venta_detalle_gastos WHERE id_pedido_venta_detalle = %s;",
					valTpDato($idDocumentoDetalle, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
				$Result1 = actualizacionEsperaPorFacturar($idArticulo, $idCasilla, $idCasillaAnt);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				if ($cantPedidaAnt > 0 || $idCasillaAnt > 0) {
					// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
					$Result1 = actualizarSaldos($idArticulo, $idCasilla, $idCasillaAnt);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				}
				
				// SE CONECTA CON EL SISTEMA DE SOLICITUDES
				$Result1 = actualizarCantidadSistemaSolicitud($frmDcto['txtNumeroReferencia'], $idArticulo, $hddIdArticuloCosto, $cantPendiente, $precioUnitario, $gastoUnitario, $hddIvaItm);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO iv_pedido_venta_detalle_impuesto (id_pedido_venta_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idDocumentoDetalle, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
						}
					}
				}
				
				// ACTUALIZA LOS GASTOS DEL DETALLE DEL PEDIDO
				for ($contFilaObj = 1; $contFilaObj <= 20; $contFilaObj++) {
					if (round($frmListaArticulo['txtMontoGastoArt:'.$valor.':'.$contFilaObj],2) > 0) {
						$insertSQL = sprintf("INSERT INTO iv_pedido_venta_detalle_gastos (id_pedido_venta_detalle, id_gasto, monto_gasto)
						VALUE (%s, %s, %s);",
							valTpDato($idDocumentoDetalle, "int"),
							valTpDato($frmListaArticulo['hddIdGastoArt:'.$valor.':'.$contFilaObj], "int"),
							valTpDato($frmListaArticulo['txtMontoGastoArt:'.$valor.':'.$contFilaObj], "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			} else if ($idArticulo > 0) {
				return $objResponse->alert(utf8_encode("El registro ".$frmListaArticulo['txtCodigoArtItm'.$valor]." tiene una cantidad inválida"));
			}
		}
	}
	
	// ELIMINA LOS GASTOS DEL PEDIDO
	$deleteSQL = sprintf("DELETE FROM iv_pedido_venta_gasto WHERE id_pedido_venta = %s",
		valTpDato($idDocumentoVenta, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
			
			if (round($txtMontoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO iv_pedido_venta_gasto (id_pedido_venta, id_gasto, tipo, porcentaje_monto, monto)
				SELECT %s, id_gasto, %s, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($idGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$hddIdPedidoGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$contIvaGasto = 0;
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indiceIvaGasto => $valorIvaGasto) {
						$valorIvaGasto = explode(":", $valorIvaGasto);
						if ($valorIvaGasto[0] == $valor && $hddPagaImpuesto == 1) {
							$contIvaGasto++;
							
							$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valorIvaGasto[1]];
							$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valorIvaGasto[1]];
							$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valorIvaGasto[1]];
							
							$insertSQL = sprintf("INSERT INTO iv_pedido_venta_gasto_impuesto (id_pedido_venta_gasto, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($hddIdPedidoGasto, "int"),
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
				$updateSQL = sprintf("UPDATE iv_pedido_venta_gasto SET
					id_iva = %s,
					iva = %s,
					estatus_iva = %s
				WHERE id_pedido_venta_gasto = %s;",
					valTpDato($hddIdIvaGasto, "int"),
					valTpDato($hddIvaGasto, "real_inglesa"),
					valTpDato($hddEstatusIvaGasto, "boolean"),
					valTpDato($hddIdPedidoGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ELIMINA LOS IMPUESTOS DEL PEDIDO
	$deleteSQL = sprintf("DELETE FROM iv_pedido_venta_iva WHERE id_pedido_venta = %s",
		valTpDato($idDocumentoVenta, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_pedido_venta_iva (id_pedido_venta, base_imponible, subtotal_iva, id_iva, iva)
			VALUE (%s, %s, %s, %s, %s);",
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
	
	// INSERTA LOS DATOS DE LA APROBACION DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_venta SET
		subtotal = %s,
		porcentaje_descuento = %s,
		subtotal_descuento = %s
	WHERE id_pedido_venta = %s;",
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($idDocumentoVenta, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idOrdenCompra = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	if (in_array($bloquearForm, array("1", "true"))) {
		if (!xvalidaAcceso($objResponse,"iv_orden_venta_list","insertar")) {  return $objResponse; }
		
		if (($frmDcto['hddTipoPago'] == 0 && xvalidaAcceso($objResponse,"iv_orden_venta_list_credito","insertar"))
		|| ($frmDcto['hddTipoPago'] == 1 && $frmTotalDcto['txtDescuento'] == 0 && xvalidaAcceso($objResponse,"iv_orden_venta_list_contado","insertar"))
		|| ($frmDcto['hddTipoPago'] == 1 && $frmTotalDcto['txtDescuento'] > 0 && xvalidaAcceso($objResponse,"iv_orden_venta_list_contado_descuento","insertar"))) {
		} else {
			 return $objResponse;
		}
		
		// ACTUALIZA EL ESTATUS DEL PEDIDO DE VENTA
		$updateSQL = sprintf("UPDATE iv_pedido_venta SET
			estatus_pedido_venta = %s
		WHERE id_pedido_venta = %s
			AND (estatus_pedido_venta = 1
			OR (estatus_pedido_venta = 2 AND (id_empleado_aprobador IS NULL OR id_empleado_aprobador = 0)));",
			valTpDato($estatusPedidoVenta, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelto, 5 = Anulado
			valTpDato($idDocumentoVenta, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA LOS DATOS DE LA APROBACION DEL PEDIDO
		$updateSQL = sprintf("UPDATE iv_pedido_venta SET
			id_empleado_aprobador = %s,
			fecha_aprobacion = %s,
			observaciones = %s
		WHERE id_pedido_venta = %s;",
			valTpDato($frmTotalDcto['hddIdEmpleadoAprobado'], "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaAprobacion'])), "date"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($idDocumentoVenta, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idOrdenCompra = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// SE CONECTA CON EL SISTEMA DE SOLICITUDES
		$Result1 = actualizarEstatusSistemaSolicitud($rowPedido['id_pedido_venta_referencia'], $estatusPedidoVenta);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->alert($Result1[1]);
		}
		
		if ($frmDcto['hddTipoPago'] == 0) { // 0 = Credito, 1 = Contado
			// ACTUALIZA EL CREDITO DISPONIBLE
			$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		
			$queryCliente = sprintf("SELECT 
				cliente.credito,
				cliente_cred.creditodisponible
			FROM cj_cc_cliente cliente
				INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
				INNER JOIN cj_cc_credito cliente_cred ON (cliente_emp.id_cliente_empresa = cliente_cred.id_cliente_empresa)
			WHERE cliente.id = %s
				AND id_empresa = %s
				AND (UPPER(cliente.credito) = 'SI');",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			$rsCliente = mysql_query($queryCliente);
			if (!$rsCliente) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsCliente = mysql_num_rows($rsCliente);
			$rowCliente = mysql_fetch_assoc($rsCliente);
			
			if ($totalRowsCliente > 0 && $rowCliente['creditodisponible'] < 0
			&& $frmDcto['hddSobregiroAprobado'] == 0) {
				//$montoSobregiro = $rowCliente['creditodisponible'] - str_replace(",", "", $frmTotalDcto['txtTotalOrden']);
				
				return $objResponse->script("
				if (confirm('".utf8_encode("El Cliente está Sobregirado por un Monto de: ".$rowCliente['creditodisponible'])."') == true) {
					byId('aGuardarSobregiro').click();
				}");
			} else if ($totalRowsCliente == 0) {
				return $objResponse->alert(utf8_encode("El Cliente No Tiene Crédito Aprobado, debe Cambiar el Tipo de Pago"));
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert(utf8_encode("Pedido de Venta Aprobado con Éxito"));
		
		$objResponse->script("
		cerrarVentana = true;
		window.location.href='iv_orden_venta_formato_pdf.php?valBusq=".$idDocumentoVenta."';");
	} else {
		mysql_query("COMMIT;");
	}
	
	return $objResponse;
}

function insertarArticulo($frmDatosArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddNumeroArt = $frmDatosArticulo['hddNumeroArt'];
	$idPedidoDetalle = $frmListaArticulo['hddIdPedidoDet'.$hddNumeroArt];
	
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
	$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle WHERE id_pedido_venta_detalle = %s;",
		valTpDato($idPedidoDetalle, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	
	$idArticulo = $frmDatosArticulo['hddIdArticulo'];
	$idCasilla = $frmDatosArticulo['lstCasillaArt'];
	$cantPedida = str_replace(",", "", $frmDatosArticulo['txtCantidadArt']);
	$lstPrecioArt = $frmDatosArticulo['lstPrecioArt'];
	$precioUnitario = str_replace(",", "", $frmDatosArticulo['txtPrecioArt']);
	$precioSugerido = str_replace(",", "", $frmDatosArticulo['txtPrecioSugerido']);
	$idIva = $frmDatosArticulo['hddIdIvaArt'];
	
	$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$hddNumeroArt]);
	$txtPrecioItm = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$hddNumeroArt]);
	$hddGastoItm = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$hddNumeroArt]);
	
	$hddBajarPrecio = $frmDatosArticulo['hddBajarPrecio'];
	$hddIdPrecioArtPredet = $frmDatosArticulo['hddIdPrecioArtPredet'];
	$hddPrecioArtPredet = $frmDatosArticulo['hddPrecioArtPredet'];
	
	if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
		$Result1 = actualizacionEsperaPorFacturar($idArticulo, $idCasilla);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
		$Result1 = actualizarSaldos($idArticulo, $idCasilla);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
		WHERE id_articulo = %s
			AND id_casilla = %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"));
	} else {
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
		$Result1 = actualizacionEsperaPorFacturar($idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
		$Result1 = actualizarSaldos($idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		$queryArtEmp = sprintf("SELECT SUM(cantidad_disponible_logica) AS cantidad_disponible_logica FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		WHERE vw_iv_art_almacen_costo.id_articulo = %s
			AND vw_iv_art_almacen_costo.id_empresa = %s
			AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
			AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
	}
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	$permitir = false;
	if ($frmListaArticulo['hddIdCasilla'.$hddNumeroArt] == $idCasilla) {
		if (($cantPedida - $txtCantItm) <= doubleval($rowArtEmp['cantidad_disponible_logica']) && doubleval($rowArtEmp['cantidad_disponible_logica']) >= 0) {
			$permitir = true;
		}
	} else if ($frmListaArticulo['hddIdCasilla'.$hddNumeroArt] != $idCasilla) {
		if ($rowArtEmp['cantidad_disponible_logica'] >= $cantPedida) {
			$permitir = true;
			$cambiarUbicacion = true;
		}
	}
	
	if ($permitir == true) {
		$arrayIdArticuloAlmacenCosto = array(-1);
		$arrayIdArticuloCosto = array(-1);
		$cantFaltante = $cantPedida - $txtCantItm;
		$cantFaltante = ($cambiarUbicacion == true) ? $cantPedida : $cantFaltante;
		
		while ($cantFaltante != 0 || ($hddNumeroArt > 0 && $precioUnitario != $txtPrecioItm)) {
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
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_almacen_costo IN (SELECT ped_vent_det.id_articulo_almacen_costo FROM iv_pedido_venta_detalle ped_vent_det
																								WHERE ped_vent_det.id_pedido_venta = %s)",
					valTpDato($rowPedidoDet['id_pedido_venta'], "int"));
			} else if ($hddNumeroArt > 0 && $cantFaltante < 0 && $cambiarUbicacion != true) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_almacen_costo IN (SELECT ped_vent_det.id_articulo_almacen_costo FROM iv_pedido_venta_detalle ped_vent_det
																								WHERE ped_vent_det.id_pedido_venta_detalle = %s)",
					valTpDato($idPedidoDetalle, "int"));
			} else if ($hddNumeroArt > 0 && $cantFaltante == 0 && count($arrayIdArticuloAlmacenCosto) == 1) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_almacen_costo IN (SELECT ped_vent_det.id_articulo_almacen_costo FROM iv_pedido_venta_detalle ped_vent_det
																								WHERE ped_vent_det.id_pedido_venta_detalle = %s)",
					valTpDato($idPedidoDetalle, "int"));
			} else {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				/*$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_almacen_costo NOT IN (%s)
				AND vw_iv_art_almacen_costo.id_articulo_costo NOT IN (%s)", 
					valTpDato(implode(",",$arrayIdArticuloAlmacenCosto), "campo"), 
					valTpDato(implode(",",$arrayIdArticuloCosto), "campo"));*/
				$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo_almacen_costo NOT IN (%s)",
					valTpDato(implode(",",$arrayIdArticuloAlmacenCosto), "campo"));
			}
			
			$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
			ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC
			LIMIT 1;", $sqlBusq);
			$rsArtCosto = mysql_query($queryArtCosto); //return $objResponse->alert($queryArtCosto);
			if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
			while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
				$arrayIdArticuloAlmacenCosto[] = $rowArtCosto['id_articulo_almacen_costo'];
				$arrayIdArticuloCosto[] = $rowArtCosto['id_articulo_costo'];
				
				$loteSinExistencia = ($rowArtCosto['cantidad_disponible_logica'] == $cantFaltante) ? true : false;
				$cantPedida = ($rowArtCosto['cantidad_disponible_logica'] > $cantFaltante) ? $cantFaltante : $rowArtCosto['cantidad_disponible_logica'];
				$cantFaltante -= $cantPedida;
				$idCasilla = (in_array($ResultConfig12, array(1,2))) ? $idCasilla : $rowArtCosto['id_casilla'];
				
				// BUSCA EL PRECIO PREDETERMINADO EN EL LOTE
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
					&& $precioUnitario != $txtPrecioItm) {
						return $objResponse->alert(utf8_encode("El Precio está por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$hddPrecioArtPredet));
					} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && round($hddPrecioArtPredet,2) < round($precioUnitario,2)
					&& $precioUnitario != $txtPrecioItm) {
						return $objResponse->alert(utf8_encode("El Precio está por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$hddPrecioArtPredet));
					} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && $costoUnitario > round($precioUnitario,2)
					&& $precioUnitario != $txtPrecioItm) {
						return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por debajo del Costo ".$costoUnitario));
					} else if (in_array($lstPrecioArt, array(12)) && $hddBajarPrecio == true && $costoUnitario < round($precioUnitario,2)
					&& $precioUnitario != $txtPrecioItm) {
						return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por encima del Costo ".$costoUnitario));
					} else {
						$cantPedida = ($cambiarUbicacion == true) ? $cantPedida : $cantPedida + $txtCantItm;
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
							AND art_precio.id_empresa = %s
							AND precio.estatus = 1
						ORDER BY precio.porcentaje DESC;",
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"));
						$rsArtPrecio = mysql_query($queryArtPrecio);
						if (!$rsArtPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						
						$objResponse->assign("spnUbicacion".$hddNumeroArt,"innerHTML",utf8_encode($rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion']));
						$objResponse->assign("txtCantItm".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
						$objResponse->assign("tdCantPend:".$hddNumeroArt,"innerHTML",number_format($cantPedida, 2, ".", ","));
						$objResponse->assign("txtPrecioItm".$hddNumeroArt,"value",number_format($precioUnitario, 2, ".", ","));
						$objResponse->assign("hddPrecioSugeridoItm".$hddNumeroArt,"value",number_format($precioSugerido, 2, ".", ","));
						//$objResponse->assign("tdIvaItm".$hddNumeroArt,"innerHTML",$ivaUnidad);
						$objResponse->assign("txtTotalItm".$hddNumeroArt,"value",number_format((($cantPedida * $precioUnitario) + $hddGastoItm), 2, ".", ","));
						
						$objResponse->assign("hddIdArticuloAlmacenCosto".$hddNumeroArt,"value",$rowArtAlmCosto['id_articulo_almacen_costo']);
						$objResponse->assign("hddIdArticuloCosto".$hddNumeroArt,"value",$rowArtAlmCosto['id_articulo_costo']);
						$objResponse->assign("hddIdCasilla".$hddNumeroArt,"value",$idCasilla);
						$objResponse->assign("hddIdPrecioItm".$hddNumeroArt,"value",$lstPrecioArt);
						
						/*$objResponse->script(sprintf("
						byId('txtPrecioItm%s').onmouseover = function() { Tip('%s', TITLE, 'Lista de Precios'); }",
							$hddNumeroArt, $htmlPreciosArt));*/
						//$objResponse->alert("Id Lote: ".$rowArtAlmCosto['id_articulo_costo']);
						$hddNumeroArt = "";
					}
				} else {
					if (count($arrayObj) < $rowConfig5['valor']) {
						if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == false && round($hddPrecioArtPredet,2) > round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("El Precio está por debajo del Precio Asignado por Defecto, el Mismo debe estar por encima de ".$hddPrecioArtPredet));
						} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && round($hddPrecioArtPredet,2) < round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("El Precio está por encima del Precio Asignado por Defecto, el Mismo debe estar por debajo de ".$hddPrecioArtPredet));
						} else if (in_array($lstPrecioArt, array(6,7)) && $hddBajarPrecio == true && $costoUnitario > round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por debajo del Costo ".$costoUnitario));
						} else if (in_array($lstPrecioArt, array(12)) && $hddBajarPrecio == true && $costoUnitario < round($precioUnitario,2)) {
							return $objResponse->alert(utf8_encode("No se puede agregar el Artículo porque el Precio está por encima del Costo ".$costoUnitario));
						} else {
							$Result1 = insertarItemArticulo($contFila, "", "", $idCliente, $idArticulo, $idCasilla, $rowArtAlmCosto['id_articulo_almacen_costo'], $rowArtAlmCosto['id_articulo_costo'], $cantPedida, $cantPedida, $lstPrecioArt, $precioUnitario, $costoUnitario, $abrevMonedaCostoUnitario, $idIva);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$contFila = $Result1[2];
								$objResponse->script($Result1[1]);
								$arrayObj[] = $contFila;
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
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true', 'true');");
		
		$objResponse->script("byId('btnCancelarArticulo').click();");
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
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
		
		(SELECT SUM(iva.iva)
		FROM pg_gastos_impuesto gasto_impuesto
			INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
		WHERE gasto_impuesto.id_gasto = gasto.id_gasto
			AND iva.tipo IN (6,9,2)) AS iva
	FROM pg_gastos gasto %s", $sqlBusq);
	
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaGasto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if ($frmPermiso['hddModulo'] == "iv_pedido_venta_form_descuento") {
			$objResponse->assign("hddConfig19","value",1);
			$objResponse->script("byId('txtDescuento').readOnly = false;");
			$objResponse->script("byId('aDesbloquearDescuento').style.display = 'none';");
			$objResponse->script("
			byId('txtDescuento').focus();
			byId('txtDescuento').select();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_venta") {
			$objResponse->script(sprintf("byId('lstPrecioArt').onchange = function(){ xajax_asignarPrecio('%s', '%s', this.value, xajax.getFormValues('frmDcto')); }",
				$frmDatosArticulo['hddIdArticulo'],
				$frmDatosArticulo['hddIdArticuloCosto']));
			
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
		} else if ($frmPermiso['hddModulo'] == "iv_sobregiro_cliente") {
			$objResponse->assign("hddSobregiroAprobado","value","1");
			
			$objResponse->script("byId('btnGuardar').click();");
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
$xajax->register(XAJAX_FUNCTION,"asignarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"asignarGasto");
$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"buscarGasto");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"desaprobarDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarGasto");
$xajax->register(XAJAX_FUNCTION,"formGastosArticulo");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarGasto");
$xajax->register(XAJAX_FUNCTION,"listaGasto");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function insertarItemArticulo($contFila, $hddIdPedidoDet = "", $hddIdPresupuestoDet = "", $idCliente = "", $idArticulo = "", $idCasilla = "", $hddIdArticuloAlmacenCosto = "", $hddIdArticuloCosto = "", $cantPedida = "", $cantPendiente = "", $hddIdPrecioItm = "", $precioUnitario = "", $precioSugerido = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $idIva = "") {
	$contFila++;
	
	if ($hddIdPedidoDet > 0) {
		$totalRowsPresupuestoDetalle = 1;
		
		$queryIdEmpresa = sprintf("SELECT ped_vent.id_empresa
		FROM iv_pedido_venta_detalle ped_vent_det
			INNER JOIN iv_pedido_venta ped_vent ON (ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta)
		WHERE ped_vent_det.id_pedido_venta_detalle = %s;",
			valTpDato($hddIdPedidoDet, "int"));
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
	WHERE art.id_articulo = %s
		AND art_alm.id_casilla = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idCasilla, "int"));
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
	$queryDetGasto = sprintf("SELECT * FROM iv_pedido_venta_detalle_gastos WHERE id_pedido_venta_detalle = %s;",
		valTpDato($hddIdPedidoDet, "int"));
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
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItm:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td><a class=\"modalImg\" id=\"aEditarItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"%s\"/></a></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><input type=\"text\" id=\"txtCodigoArtItm%s\" name=\"txtCodigoArtItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
			"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
				"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"%s\">".
				"<tr><td colspan=\"2\"><span id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</span></td></tr>".
				"<tr><td colspan=\"2\">%s</td></tr>".
				"<tr><td>%s</td><td><input type=\"text\" id=\"hddPrecioSugeridoItm%s\" name=\"hddPrecioSugeridoItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" %s value=\"%s\"/></td></tr>".
				"</table></td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td align=\"right\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPresupuestoDet%s\" name=\"hddIdPresupuestoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" title=\"Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblArticulo', '%s', '%s'); }
		byId('aGastoArt:%s').onclick = function() { abrirDivFlotante1(this, 'tblLista', 'Gasto', '%s'); }
		
		byId('txtPrecioItm%s').onmouseover = function() { Tip('%s', TITLE, 'Lista de Precios'); }
		byId('txtPrecioItm%s').onmouseout = function() { UnTip(); }",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$contFila, utf8_encode("Editar Artículo"),
			$contFila, $contFila,
			$contFila, $contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
				"100%",
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
				((in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) ? "" : "<span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</span>"),
				(($precioSugerido != 0) ? "<span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">Precio Sugerido:</span>" : ""), $contFila, $contFila, (($precioSugerido != 0) ? "" : "style=\"display:none\""), number_format($precioSugerido, 2, ".", ","),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$htmlGastoArt,
			$contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
			$contFila, $ivaUnidad,
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
				$contFila, $contFila, $hddIdPresupuestoDet,
				$contFila, $contFila, $hddIdPedidoDet,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdArticuloAlmacenCosto,
				$contFila, $contFila, $hddIdArticuloCosto,
				$contFila, $contFila, $hddIdPrecioItm,
				$contFila, $contFila, $idCasilla,
		
		$contFila, $contFila, $idArticulo,
		$contFila, $contFila,
		
		$contFila, $htmlPreciosArt,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemGasto($contFila, $hddIdGasto, $hddIdPedidoGasto = "") {
	$contFila++;
	
	if ($hddIdPedidoGasto > 0) {
		$queryPedidoDet = sprintf("SELECT ped_vent_gasto.*
		FROM pg_iva iva
			RIGHT JOIN pg_gastos gasto ON (iva.idIva = gasto.id_iva)
			INNER JOIN iv_pedido_venta_gasto ped_vent_gasto ON (gasto.id_gasto = ped_vent_gasto.id_gasto)
		WHERE ped_vent_gasto.id_pedido_venta_gasto = %s;", 
			valTpDato($hddIdPedidoGasto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdGasto = $rowPedidoDet['id_gasto'];
		$txtPorcGasto = $rowPedidoDet['porcentaje_monto'];
		$txtMontoGasto = $rowPedidoDet['monto'];
		$txtMedidaGasto = $rowPedidoDet['monto_medida'];
		
		// BUSCA LOS IMPUESTOS DEL GASTO
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_pedido_venta_gasto_impuesto WHERE id_pedido_venta_gasto = %s;",
			valTpDato($hddIdPedidoGasto, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	} else {
		// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
  			INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
		WHERE iva.tipo IN (6,9,2) AND iva.estado = 1
			AND gasto_impuesto.id_gasto = %s
		ORDER BY iva;",
			valTpDato($hddIdGasto, "int"));
		$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
		if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
			$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
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
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (6,9,2)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	$ivaUnidad = "";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
		"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
			"100%", $contFila, $contIva, "100%",
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	$hddIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIvaGasto : $rowGasto['iva'];
	$hddIdIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIdIvaGasto : $rowGasto['id_iva'];
	$hddEstatusIvaGasto = ($hddEstatusIvaGasto != "" && $totalRowsPedidoDet > 0) ? $hddEstatusIvaGasto : $rowGasto['estatus_iva'];
	
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
			"<td title=\"trItmGasto:%s\"><input id=\"cbxItmGasto\" name=\"cbxItmGasto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"tituloCampo\">%s:</td>".
			"<td><table cellpadding=\"0\" cellspacing=\"0\"><tr><td><input type=\"radio\" id=\"rbtInicialPorc%s\" name=\"rbtInicial%s\" value=\"1\"></td>".
				"<td><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"></td><td>%s</td></tr></table></td>".
			"<td id=\"spnGastoMoneda%s\"></td>".
			"<td><table cellpadding=\"0\" cellspacing=\"0\"><tr><td><input type=\"radio\" id=\"rbtInicialMonto%s\" name=\"rbtInicial%s\" value=\"2\"/></td>".
				"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td></tr></table></td>".
			"<td %s><input type=\"text\" id=\"txtMedidaGasto%s\" name=\"txtMedidaGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right; %s\" value=\"%s\"></td>".
			"<td><div id=\"divIvaGasto%s\">%s</div>%s".
				"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdModoGasto%s\" name=\"hddIdModoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdTipoMedida%s\" name=\"hddIdTipoMedida%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdPedidoGasto%s\" name=\"hddIdPedidoGasto%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('rbtInicialPorc%s').onclick = function() {
			byId('hddTipoGasto%s').value = 0;
			byId('txtPorcGasto%s').readOnly = false;
			byId('txtMontoGasto%s').readOnly = true;
			byId('txtPorcGasto%s').className = 'inputCompletoHabilitado';
			byId('txtMontoGasto%s').className = 'inputCompleto';
		}
		
		byId('rbtInicialMonto%s').onclick = function() {
			byId('hddTipoGasto%s').value = 1;
			byId('txtPorcGasto%s').readOnly = true;
			byId('txtMontoGasto%s').readOnly = false;
			byId('txtPorcGasto%s').className = 'inputCompleto';
			byId('txtMontoGasto%s').className = 'inputCompletoHabilitado';
		}
		
		byId('txtPorcGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Porc', this.value, 'txtMontoGasto%s');
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('txtMontoGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('txtMedidaGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('rbtInicialMonto%s').click();",
		$contFila,
			$contFila, $contFila,
				$contFila,
			$rowGasto['nombre'],
			$contFila, $contFila,
				$contFila, $contFila, number_format($txtPorcGasto, 2, ".", ","), "%",
			$contFila,
			$contFila, $contFila,
				$contFila, $contFila, number_format($txtMontoGasto, 2, ".", ","),
			(($rowGasto['id_tipo_medida'] == 1) ? "title=\"Peso Total (g)\"" : ""), $contFila, $contFila, "display:none",number_format($txtMedidaGasto, 2, ".", ","),
			$contFila, $ivaUnidad, $htmlAfecta,
				$contFila, $contFila, $hddIdGasto,
				$contFila, $contFila, $rowGasto['id_modo_gasto'],
				$contFila, $contFila, $rowGasto['id_tipo_medida'],
				$contFila, $contFila, 1,
				$contFila, $contFila, $hddIdPedidoGasto,
		
		$contFila,
			$contFila,
			$contFila,
			$contFila, 
			$contFila,
			$contFila, 
		
		$contFila,
			$contFila,
			$contFila,
			$contFila, 
			$contFila,
			$contFila, 
		
		$contFila,
			$contFila,
		
		$contFila,
			$contFila,
		
		$contFila,
		
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>