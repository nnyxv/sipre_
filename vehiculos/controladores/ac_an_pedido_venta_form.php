<?php


function asignarArticulo($hddNumeroArt, $idArticulo, $frmDcto, $precioUnitario = "", $frmListaArticulo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
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
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
	$Result1 = actualizacionEsperaPorFacturar($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
	$Result1 = actualizarSaldos($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	$objResponse->script("
	byId('tdMsjArticulo').style.display = 'none';");
	
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
		
		$objResponse->assign("txtCantidadArt","value",str_replace(",", "", $frmListaArticulo['txtCantItm'.$hddNumeroArt]) + $txtCantidadArt);
		$objResponse->assign("hddIdIvaArt","value",((count($arrayIdIvaArt) > 0) ? implode(",",$arrayIdIvaArt) : ""));
		$objResponse->assign("txtIvaArt","value",((count($arrayIvaArt) > 0) ? implode(", ",$arrayIvaArt) : ""));
		
		$hddIdPrecioItm = $frmListaArticulo['hddIdPrecioItm'.$hddNumeroArt];
		$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$hddNumeroArt]);
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
	
	// BUSCA LAS UBICACIONES PARA LA VENTA DEL ARTICULO
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND id_empresa = %s
		AND estatus_almacen_venta = 1
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

function asignarBanco($idBanco, $valores = "") {
	$objResponse = new xajaxResponse();
	
	$valores = (is_array($valores)) ? $valores : explode("|",$valores);
	if (isset($valores)) {
		foreach ($valores as $indice => $valor) {
			$valor = explode("*",$valor);
			$arrayFinal[$valor[0]] = $valor[1];
		}
	}
	
	$objResponse->script("
	byId('trCuotasFinanciar2').style.display = 'none';
	byId('trCuotasFinanciar3').style.display = 'none';
	byId('trCuotasFinanciar4').style.display = 'none';");
	
	$tdMesesFinanciar2 = "";
	$tdMesesFinanciar3 = "";
	$tdMesesFinanciar4 = "";
	
	// BUSCA LOS DATOS DEL BANCO
	$queryBanco = sprintf("SELECT nombreBanco, porcentaje_flat FROM bancos WHERE idBanco = %s;",
		valTpDato($idBanco, "int"));
	$rsBanco = mysql_query($queryBanco);
	if (!$rsBanco) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsBanco = mysql_num_rows($rsBanco);
	$rowBanco = mysql_fetch_array($rsBanco);
	
	$queryFactor = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($idBanco, "int"));
	$rsFactor = @mysql_query($queryFactor);
	if (!$rsFactor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactor = mysql_num_rows($rsFactor);
	if ($totalRowsBanco > 0) {
		if ($totalRowsFactor > 0) {
			$tdMesesFinanciar = "<select id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"xajax_asignarFactor(this.value, xajax.getFormValues('frmDcto'));\">";
				$tdMesesFinanciar .= "<option value=\"\">[ Seleccione ]</option>";
			while($rowFactor = @mysql_fetch_assoc($rsFactor)) {
				$selected = ($arrayFinal['lstMesesFinanciar'] == $rowFactor['mes']) ? "selected=\"selected\"" : "";
				
				$tdMesesFinanciar .= "<option ".$selected." value=\"".$rowFactor['mes']."\">".$rowFactor['financiamento']."</option>";
			}
			$tdMesesFinanciar .= "</select>";
			
			$objResponse->assign("tdMesesFinanciar","innerHTML",$tdMesesFinanciar);
			
			$objResponse->assign("tdCuotasFinanciar","innerHTML",
				"<input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputCompleto\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/>".
				"<input type=\"hidden\" id=\"hddFactorFinanciar\" name=\"hddFactorFinanciar\"/>");
		} else {
			$objResponse->loadCommands(asignarFactor(-1,array()));
			$objResponse->script("
			byId('trCuotasFinanciar2').style.display = '';
			byId('trCuotasFinanciar3').style.display = '';
			byId('trCuotasFinanciar4').style.display = '';");
			
			for ($cont = 1; $cont <= 4; $cont++) {
				$contAux = ($cont == 1) ? "" : $cont;
				
				$objResponse->assign("tdMesesFinanciar".$contAux,"innerHTML",
					"<table border=\"0\">".
					"<tr>".
						"<td><input type=\"text\" id=\"lstMesesFinanciar".$contAux."\" name=\"lstMesesFinanciar".$contAux."\" class=\"inputHabilitado\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar'.$contAux])."\"/></td>".
						"<td>"." Meses"."</td>".
						"<td>"."&nbsp;/&nbsp;"."</td>".
						"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar".$contAux."\" name=\"txtInteresCuotaFinanciar".$contAux."\" class=\"inputHabilitado\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar'.$contAux])."\"/></td>".
						"<td>"."%"."</td>".
					"</tr>".
					"</table>");
				
				$objResponse->assign("tdFechaCuotaFinanciar".$contAux,"innerHTML",
					"<table border=\"0\">".
					"<tr align=\"right\">".
						"<td nowrap=\"nowrap\">Fecha Pago:</td>".
						"<td>"."<input type=\"text\" id=\"txtFechaCuotaFinanciar".$contAux."\" name=\"txtFechaCuotaFinanciar".$contAux."\" autocomplete=\"off\" class=\"inputHabilitado\" size=\"10\" style=\"text-align:center\" value=\"".(($arrayFinal['txtFechaCuotaFinanciar'.$contAux] != "") ? date(spanDateFormat, strtotime($arrayFinal['txtFechaCuotaFinanciar'.$contAux])) : "")."\"/>"."</td>".
					"</tr>".
					"</table>");
				
				$objResponse->assign("tdCuotasFinanciar".$contAux,"innerHTML",
					"<input type=\"text\" id=\"txtCuotasFinanciar".$contAux."\" name=\"txtCuotasFinanciar".$contAux."\" class=\"inputCompletoHabilitado\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar'.$contAux])."\"/>");
			}
		}
	} else {
		for ($cont = 1; $cont <= 4; $cont++) {
			$contAux = ($cont == 1) ? "" : $cont;
			
			$objResponse->assign("tdMesesFinanciar".$contAux,"innerHTML",
				"<table border=\"0\">".
				"<tr>".
					"<td><input type=\"text\" id=\"lstMesesFinanciar".$contAux."\" name=\"lstMesesFinanciar".$contAux."\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" readonly=\"readonly\" style=\"text-align:right; width:40px;\" value=\"0.00\"/></td>".
					"<td>"." Meses"."</td>".
					"<td>"."&nbsp;/&nbsp;"."</td>".
					"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar".$contAux."\" name=\"txtInteresCuotaFinanciar".$contAux."\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" readonly=\"readonly\" style=\"text-align:right; width:60px;\" value=\"0.00\"/></td>".
					"<td>"."%"."</td>".
				"</tr>".
				"</table>");
			
			$objResponse->assign("tdFechaCuotaFinanciar".$contAux,"innerHTML",
				"<table border=\"0\">".
				"<tr align=\"right\">".
					"<td nowrap=\"nowrap\">Fecha Pago:</td>".
					"<td>"."<input type=\"text\" id=\"txtFechaCuotaFinanciar".$contAux."\" name=\"txtFechaCuotaFinanciar".$contAux."\" autocomplete=\"off\" class=\"inputSinFondo\" readonly=\"readonly\" size=\"10\" style=\"text-align:center\" value=\"\"/>"."</td>".
				"</tr>".
				"</table>");
			
			$objResponse->assign("tdCuotasFinanciar".$contAux,"innerHTML",
				"<input type=\"text\" id=\"txtCuotasFinanciar".$contAux."\" name=\"txtCuotasFinanciar".$contAux."\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/>");
		}
	}
	
	$objResponse->assign("txtPorcFLAT","value",number_format($rowBanco['porcentaje_flat'], 2, ".", ","));
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	if ($totalRowsBanco > 0 || $totalRowsFactor > 0) {
		$objResponse->script("
		jQuery(function($){
			$(\"#txtFechaCuotaFinanciar\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$(\"#txtFechaCuotaFinanciar2\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$(\"#txtFechaCuotaFinanciar3\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$(\"#txtFechaCuotaFinanciar4\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar2\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar3\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar4\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$idModulo = 2;
	
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
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.reputacionCliente,
		cliente.status,
		cliente.tipo_cuenta_cliente
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
		
		$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		$objResponse->assign("txtCreditoCliente","value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		
		if (function_exists("cargaLstClaveMovimiento")) { 
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "3", "", "1", $idClaveMovimiento,
			((in_array($idModulo,array(0))) ? "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""
				: "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\"")));
			
			if (in_array($idModulo,array(0))) {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = '';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento','".$idModulo."','3','','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
				}");
			} else {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = 'none';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
				}");
			}
		}
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
		if (function_exists("cargaLstClaveMovimiento")) { 
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "3", "", "1", $idClaveMovimiento,
			((in_array($idModulo,array(0))) ? "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""
				: "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\"")));
			
			if (in_array($idModulo,array(0))) {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = '';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento','".$idModulo."','3','','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
				}");
			} else {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = 'none';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
				}");
			}
		}
	}
	
	if ($rowCliente['id_cliente'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
	if ($tipoCuentaCliente == 1) {
		$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">PROSPECTO [".$rowCliente['reputacionCliente']."]</div>";
		$backgroundReputacion = '#FFFFCC'; // AMARILLO
	} else {
		switch ($rowCliente['id_reputacion_cliente']) {
			case 1 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#FFEEEE'; // ROJO
				break;
			case 2 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#DDEEFF'; // AZUL
				break;
			case 3 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#E6FFE6'; // VERDE
				break;
		}
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	$objResponse->assign("tblIdCliente","style.background",$backgroundReputacion);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
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

function asignarEmpleado($objDestino, $idEmpleado, $idEmpresa, $estatusFiltro = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	if (in_array($estatusFiltro, array("1", "true"))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
		
		// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$queryEmpleado = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombre".$objDestino,"value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarFactor($lstMesesFinanciar, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$queryFactor = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
		AND mes = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($frmDcto['lstBancoFinanciar'], "int"),
		valTpDato($lstMesesFinanciar, "double"));
	$rsFactor = @mysql_query($queryFactor);
	if (!$rsFactor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactor = mysql_num_rows($rsFactor);
	$rowFactor = @mysql_fetch_assoc($rsFactor);
	
	$objResponse->assign("hddFactorFinanciar","value",$rowFactor['factor']);
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function asignarMoneda($frmDcto, $guardarDcto = "false", $calcularDcto = "false", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('txtTasaCambio').className = 'inputHabilitado';
	byId('txtTasaCambio').readOnly = false;
	byId('txtFechaTasaCambio').className = 'inputHabilitado';
	byId('txtFechaTasaCambio').readOnly = false;
	
	byId('txtTasaCambio').onblur = function() {
		setFormatoRafk(this,3);
	}
	
	jQuery(function($){
		$(\"#txtFechaTasaCambio\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaTasaCambio\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	byId('trTasaCambio').style.display = 'none';");
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Extrangero
	
	if ($idModoCompra == 1) { // 1 = Nacional, 2 = Importacion
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
	} else {
		$objResponse->script("
		byId('trTasaCambio').style.display = '';");
		
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
		
		$objResponse->assign("txtTasaCambio", "value", number_format($rowTasaCambio['monto_tasa_cambio'], 3, ".", ","));
	}
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	
	$objResponse->assign("hddIncluirImpuestos", "value", $rowMonedaOrigen['incluir_impuestos']);
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), '".$guardarDcto."', '".$calcularDcto."', '".$bloquearForm."');");
	
	return $objResponse;
}

function asignarPoliza($idPoliza) {
	$objResponse = new xajaxResponse();
	
	$queryPoliza = sprintf("SELECT * FROM an_poliza WHERE id_poliza = %s;",
		valTpDato($idPoliza, "int"));
	$rsPoliza = mysql_query($queryPoliza);
	if (!$rsPoliza) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPoliza = mysql_fetch_assoc($rsPoliza);
	
	$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPoliza['nom_comp_seguro']);
	$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPoliza['dir_agencia']);
	$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPoliza['ciudad_agencia']);
	$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPoliza['pais_agencia']);
	$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPoliza['telf_agencia']);
	$objResponse->assign("txtMontoSeguro","value",number_format($rowPoliza['contado_poliza'], 2, ".", ","));
	$objResponse->assign("txtInicialPoliza","value",number_format($rowPoliza['inicial_poliza'], 2, ".", ","));
	$objResponse->assign("txtMesesPoliza","value",$rowPoliza['meses_poliza']);
	$objResponse->assign("txtCuotasPoliza","value",number_format($rowPoliza['cuotas_poliza'], 2, ".", ","));
	
	$objResponse->assign("cheque_poliza","value",$rowPoliza['cheque_poliza']);
	$objResponse->assign("financiada","value",$rowPoliza['financiada']);
	
	return $objResponse;
}

function asignarPrecio($idArticulo, $idPrecio, $frmDcto, $precioPredet = "false", $precioUnitario = "") {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idMoneda = $frmDcto['lstMoneda'];
	
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
		AND art_precio.id_empresa = %s
		AND art_precio.id_precio = %s
		AND art_precio.id_moneda = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"),
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

function asignarSinBancoFinanciar($frmDcto) {
	$objResponse = new xajaxResponse();
	
	if ($frmDcto['cbxSinBancoFinanciar'] == 1) {
		if ($frmDcto['hddSinBancoFinanciar'] == 1) {
			$objResponse->script("
			selectedOption('lstBancoFinanciar','');
			byId('lstBancoFinanciar').onchange();");
		} else {
			$objResponse->script("
			byId('cbxSinBancoFinanciar').checked = false;
			byId('aDesbloquearSinBancoFinanciar').click();");
		}
	}
	
	return $objResponse;
}

function asignarUnidadBasica($idUnidadBasica, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_modelos vw_iv_modelo WHERE vw_iv_modelo.id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdUnidadBasica","value",$idUnidadBasica);
	$objResponse->assign("txtNombreUnidadBasica","value",utf8_encode($row['nom_uni_bas']));
	$objResponse->assign("txtMarca","value",utf8_encode($row['nom_marca']));
	$objResponse->assign("txtModelo","value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("txtVersion","value",utf8_encode($row['nom_version']));
	$objResponse->assign("txtAno","value",utf8_encode($row['nom_ano']));
	
	// BUSCA LOS IMPUESTOS DE VENTA
	$query = sprintf("SELECT
		iva.iva,
		iva.observacion
	FROM an_unidad_basica_impuesto uni_bas_impuesto
		INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
	WHERE uni_bas_impuesto.id_unidad_basica = %s
		AND iva.tipo IN (6);",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$txtPorcIva += $row['iva'];
		$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
		$spanPorcIva .= $cond.$row['observacion'];
	}
	$spanPorcIva .= ($totalRows > 0) ? "" : "Exento";
	
	// BUSCA LOS IMPUESTOS DE VENTA DE LUJO
	$query = sprintf("SELECT
		iva.iva,
		iva.observacion
	FROM an_unidad_basica_impuesto uni_bas_impuesto
		INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
	WHERE uni_bas_impuesto.id_unidad_basica = %s
		AND iva.tipo IN (2);",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$txtPorcIvaLujo += $row['iva'];
		$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
		$spanPorcIva .= $cond.$row['observacion'];
	}
	
	$objResponse->assign("txtPorcIva","value",number_format($txtPorcIva, 2, ".", ","));
	$objResponse->assign("txtPorcIvaLujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
	$objResponse->assign("spanPorcIva","innerHTML",((strlen($spanPorcIva) > 0) ? "(".$spanPorcIva.")" : ""));
	
	if (function_exists("asignarUnidadFisica")) { $objResponse->loadCommands(asignarUnidadFisica("")); }
	
	$objResponse->script("byId('txtCantidadAsignada').focus();");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

function asignarUnidadFisica($idUnidadFisica, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.id_activo_fijo,
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		vw_iv_modelo.nom_marca,
		vw_iv_modelo.nom_modelo,
		vw_iv_modelo.nom_version,
		vw_iv_modelo.nom_ano,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.tipo_placa,
		cond_unidad.descripcion AS condicion_unidad,
		uni_fis.kilometraje,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		(CASE
			WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
				IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
			WHEN (an_ve.fecha IS NOT NULL) THEN
				an_ve.fecha
		END) AS fecha_origen,
		IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
				WHEN (an_ve.fecha IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
			END),
		0) AS dias_inventario,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		asig.idAsignacion,
		alm.nom_almacen,
		cxp_fact.id_factura,
		cxp_fact.numero_factura_proveedor,
		cxp_fact.id_modulo,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in,
		
		(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
		WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND an_ve.fecha IS NOT NULL
			AND an_ve.tipo_vale_entrada = 1
			AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_uni_bas'] > 0) {
		$objResponse->loadCommands(asignarUnidadBasica($row['id_uni_bas']));
	}
	
	$objResponse->assign("txtIdUnidadFisica","value",$idUnidadFisica);
	$objResponse->assign("txtNombreUnidadFisica","value",$idUnidadFisica);
	$objResponse->assign("txtColorExterno1","value",utf8_encode($row['color_externo1']));
	$objResponse->assign("txtSerialCarroceria","value",utf8_encode($row['serial_carroceria']));
	$objResponse->assign("txtSerialMotor","value",utf8_encode($row['serial_motor']));
	$objResponse->assign("txtKilometraje","value",utf8_encode($row['kilometraje']));
	$objResponse->assign("txtPlaca","value",utf8_encode($row['placa']));
	$objResponse->assign("txtCondicion","value",utf8_encode($row['condicion_unidad']));
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

function bloquearLstClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;",
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

function buscarAdicional($frmBuscarAdicional) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarAdicional['txtCriterioBuscarAdicional']);
	
	$objResponse->loadCommands(listaAdicional(0, "nom_accesorio", "ASC", $valBusq));
	$objResponse->loadCommands(listaPaquete(0, "nom_paquete", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
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
			
			// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
			$existe = false;
			if (isset($arrayObjPieArticulo)) {
				foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
					if ($frmDcto['hddIdArticuloItm'.$valorPieArticulo] == $row['id_articulo']
					&& (str_replace(",","",$frmDcto['txtPrecioItm'.$valorPieArticulo]) == str_replace(",","",$precioUnitario) || str_replace(",","",$precioUnitario) == "")) {
						$objResponse->script("xajax_asignarArticulo('".$valorPieArticulo."', '".$row['id_articulo']."', xajax.getFormValues('frmDcto'), '".$precioUnitario."', xajax.getFormValues('frmListaArticulo'), 'false');");
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$objResponse->loadCommands(asignarArticulo("", $row['id_articulo'], $frmDcto, $precioUnitario, "", "false"));
			}
			
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

function buscarEmpleado($frmBuscarEmpleado, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
	
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
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

function buscarUnidadBasica($frmBuscarUnidadBasica, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		(is_array($frmBuscarUnidadBasica['lstMarcaBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstMarcaBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstMarcaBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstModeloBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstModeloBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstModeloBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstVersionBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstVersionBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstVersionBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstAnoBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstAnoBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstAnoBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstCatalogoBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstCatalogoBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstCatalogoBuscarUnidadBasica']),
		$frmBuscarUnidadBasica['txtCriterioBuscarUnidadBasica'],
		$frmBuscarUnidadBasica['hddObjDestinoUnidadBasica']);
	
	$objResponse->loadCommands(listaUnidadBasica(0, "id_uni_bas", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadBasicaModelo($frmBuscarModelo, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		(is_array($frmBuscarModelo['lstMarcaBuscarModelo']) ? implode(",",$frmBuscarModelo['lstMarcaBuscarModelo']) : $frmBuscarModelo['lstMarcaBuscarModelo']),
		(is_array($frmBuscarModelo['lstModeloBuscarModelo']) ? implode(",",$frmBuscarModelo['lstModeloBuscarModelo']) : $frmBuscarModelo['lstModeloBuscarModelo']),
		(is_array($frmBuscarModelo['lstVersionBuscarModelo']) ? implode(",",$frmBuscarModelo['lstVersionBuscarModelo']) : $frmBuscarModelo['lstVersionBuscarModelo']),
		(is_array($frmBuscarModelo['lstAnoBuscarModelo']) ? implode(",",$frmBuscarModelo['lstAnoBuscarModelo']) : $frmBuscarModelo['lstAnoBuscarModelo']),
		(is_array($frmBuscarModelo['lstCatalogoBuscarModelo']) ? implode(",",$frmBuscarModelo['lstCatalogoBuscarModelo']) : $frmBuscarModelo['lstCatalogoBuscarModelo']),
		$frmBuscarModelo['txtCriterioBuscarModelo'],
		$frmBuscarModelo['hddObjDestinoModelo']);
	
	$objResponse->loadCommands(listaUnidadBasica(0, "id_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarUnidadFisica($frmBuscarUnidadFisica, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdUnidadBasica'],
		$frmBuscarUnidadFisica['txtCriterioBuscarUnidadFisica']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "id_unidad_fisica", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $guardarDcto = "false", $calcularDcto = "false", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	if (isset($arrayObjPieArticulo)) {
		$i = 0;
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArticulo_".$valorPieArticulo,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmArticulo_".$valorPieArticulo,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObjPieArticulo) > 0) ? implode("|",$arrayObjPieArticulo) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	sort($frmDcto['cbxPieAdicional']);
	$arrayObjPieAdicional = $frmDcto['cbxPieAdicional'];
	if (isset($arrayObjPieAdicional)) {
		$i = 0;
		foreach ($arrayObjPieAdicional as $indicePieAdicional => $valorPieAdicional) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmAdicional_".$valorPieAdicional,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmAdicional_".$valorPieAdicional,"innerHTML",$i);
		}
	}
	
	$objResponse->script("
	byId('fieldsetVehiculoUsado').style.display = 'none';
	byId('fieldsetFormaPago').style.display = 'none';
	byId('fieldsetVentaUnidad').style.display = 'none';
	
	byId('aAgregarFormaPago').style.display = 'none';
	byId('btnQuitarFormaPago').style.display = 'none';");
	
	if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("
		byId('trPagoContado').style.display = 'none';
		byId('trPND').style.display = 'none';
		byId('trTotalPND').style.display = 'none';
		byId('trOtrosPagos').style.display = 'none';
		byId('trTotalOtrosPagos').style.display = 'none';
		
		byId('fieldsetOtros').style.display = 'none';");
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
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
	
	$txtPrecioBase = ($frmDcto['txtIdUnidadBasica'] > 0 || $frmDcto['txtIdUnidadFisica'] > 0) ? doubleval(str_replace(",","",$frmDcto['txtPrecioBase'])) : 0;
	$txtDescuento = doubleval(str_replace(",","",$frmDcto['txtDescuento']));
	$txtPorcIva = doubleval((($hddPagaImpuesto == 1) ? str_replace(",","",$frmDcto['txtPorcIva']) : 0));
	$txtPorcIvaLujo = doubleval((($hddPagaImpuesto == 1) ? str_replace(",","",$frmDcto['txtPorcIvaLujo']) : 0));
	
	$txtPrecioVenta = $txtPrecioBase - $txtDescuento;
	
	$txtSubTotalIva += ($txtPorcIva != 0) ? ($txtPrecioVenta * $txtPorcIva) / 100 : 0;
	$txtSubTotalIva += ($txtPorcIvaLujo != 0) ? ($txtPrecioVenta * $txtPorcIvaLujo) / 100 : 0;
	
	$txtPrecioVenta += $txtSubTotalIva;
	
	if ($frmDcto['txtIdUnidadBasica'] > 0 || $frmDcto['txtIdUnidadFisica'] > 0) {
		$objResponse->script("
		byId('fieldsetFormaPago').style.display = '';
		byId('fieldsetVentaUnidad').style.display = '';");
	}
	
	// CALCULA EL SUBTOTAL
	$txtSubTotalArticulo = 0;
	if (isset($arrayObjPieArticulo)) {
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valorPieArticulo]);
			
			$txtSubTotalArticulo += $txtTotalItm;
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$txtSubTotalArticuloConImpuesto = 0;
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObjPieArticulo)) {
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$objResponse->script("
			byId('divPrecioPagadoItmArticulo".$valorPieArticulo."').style.display = 'none';");
			
			if ($frmDcto['cbxCondicionItmArticulo'.$valorPieArticulo] == 1) { // 1 = Pagado, 2 = Financiado
			} else {
				$objResponse->assign("txtPrecioPagadoItmArticulo".$valorPieArticulo,"value",number_format(0, 2, ".", ","));
			}
			
			$objResponse->script("
			if (byId('cbxCondicionItmArticulo".$valorPieArticulo."').checked == true) {
				byId('divPrecioPagadoItmArticulo".$valorPieArticulo."').style.display = '';
			}");
			
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valorPieArticulo]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valorPieArticulo]);
			
			$totalDescuentoItm = ($hddTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotalArticulo; // DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $totalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					
					if ($valorIvaItm[0] == $valorPieArticulo && $hddPagaImpuesto == 1) {
						$arrayIvaItm[$frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valorIvaItm[1]]] = $valorIvaItm[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valorIvaItm[1]];
					}
				}
			}
			
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
				valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			$txtTotalIvaItm = 0;
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valorPieArticulo.':'.$arrayIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExento += $txtTotalNetoItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valorPieArticulo.':'.$arrayIvaItm[$idIva]]) : $porcIva;
					$subTotalIvaItm = ($txtTotalNetoItm * $porcIva) / 100;
				
					$txtTotalIvaItm += $subTotalIvaItm;
					
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
			
			$txtTotalArticuloFinanciar += ($frmDcto['cbxCondicionItmArticulo'.$valorPieArticulo] == 1) ? (($txtTotalItm + $txtTotalIvaItm) - str_replace(",","",$frmDcto['txtPrecioPagadoItmArticulo'.$valorPieArticulo])) : ($txtTotalItm + $txtTotalIvaItm);
			$txtTotalArticulo += ($txtTotalItm + $txtTotalIvaItm);
			
			$objResponse->assign("txtTotalItm".$valorPieArticulo, "value", number_format($txtTotalItm, 2, ".", ","));
			$objResponse->assign("txtTotalConImpuestoItm".$valorPieArticulo, "value", number_format(($txtTotalItm + $txtTotalIvaItm), 2, ".", ","));
			
			$subTotalDescuentoItm += $hddCantRecibItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valorPieArticulo]);
			
			$txtSubTotalArticuloConImpuesto += ($txtTotalItm + $txtTotalIvaItm);
		}
	}
	
	if (isset($arrayObjPieAdicional)) {
		foreach ($arrayObjPieAdicional as $indicePieAdicional => $valorPieAdicional) {
			$objResponse->assign("hddTipoAdicionalItm".$valorPieAdicional,"value",$frmDcto['lstTipoAdicionalItm'.$valorPieAdicional]);
			
			$objResponse->script("
			byId('divPrecioPagadoItm".$valorPieAdicional."').style.display = 'none';
			byId('lstMostrarItm".$valorPieAdicional."').style.display = 'none';
			byId('divMostrarPendienteItm".$valorPieAdicional."').style.display = 'none';");
			
			if (in_array($frmDcto['lstTipoAdicionalItm'.$valorPieAdicional],array(1,4))) { // 1 = Adicional, 4 = Cargo
				if ($frmDcto['cbxCondicionItm'.$valorPieAdicional] == 1 && in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$frmDcto['txtPrecioPagadoItm'.$valorPieAdicional] = str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]);
					
					$objResponse->assign("txtPrecioPagadoItm".$valorPieAdicional,"value",number_format($frmDcto['txtPrecioPagadoItm'.$valorPieAdicional], 2, ".", ","));
				}
				
				$txtTotalAdicional += str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]);
				$txtTotalAdicionalFinanciar += ($frmDcto['cbxCondicionItm'.$valorPieAdicional] == 1) ? (str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]) - str_replace(",","",$frmDcto['txtPrecioPagadoItm'.$valorPieAdicional])) : str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]);
				$txtTotalAdicionalPredeterminado += ($frmDcto['hddMostrarPredeterminadoItm'.$valorPieAdicional] == 1) ? str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]) : 0;
				
				if ($frmDcto['lstMostrarItm'.$valorPieAdicional] == 2) { // 2 = Incluir en el Costo
					$objResponse->script("
					byId('txtPrecioConIvaItm".$valorPieAdicional."').className = 'inputInicial';
					byId('txtPrecioConIvaItm".$valorPieAdicional."').readOnly = true;");
					$objResponse->assign("txtPrecioConIvaItm".$valorPieAdicional,"value",$frmDcto['hddCostoUnitarioItm'.$valorPieAdicional]);
					$objResponse->assign("txtPrecioPagadoItm".$valorPieAdicional,"value",$frmDcto['hddCostoUnitarioItm'.$valorPieAdicional]);
				} else {
					if ($frmDcto['hddIdFinanciamiento'] > 0) {
						$objResponse->script("
						byId('txtPrecioConIvaItm".$valorPieAdicional."').className = 'inputSinFondo';
						byId('txtPrecioConIvaItm".$valorPieAdicional."').readOnly = true;");
					} else {
						$objResponse->script("
						byId('txtPrecioConIvaItm".$valorPieAdicional."').className = 'inputCompletoHabilitado';
						byId('txtPrecioConIvaItm".$valorPieAdicional."').readOnly = false;");
					}
					
					if ($frmDcto['cbxCondicionItm'.$valorPieAdicional] == 1) { // 1 = Pagado, 2 = Financiado
						if (str_replace(",","",$frmDcto['txtPrecioPagadoItm'.$valorPieAdicional]) > 0
						&& str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]) != str_replace(",","",$frmDcto['txtPrecioPagadoItm'.$valorPieAdicional])
						&& in_array(idArrayPais,array(3))) {
							$objResponse->script("
							byId('divMostrarPendienteItm".$valorPieAdicional."').style.display = '';");
						}
					} else {
						$objResponse->assign("txtPrecioPagadoItm".$valorPieAdicional,"value",number_format(0, 2, ".", ","));
					}
				}
				
				$objResponse->script("
				byId('divPrecioPagadoItm".$valorPieAdicional."').style.display = 'none';
				byId('lstMostrarItm".$valorPieAdicional."').style.display = '';
				if (byId('cbxCondicionItm".$valorPieAdicional."').checked == true) {
					byId('divPrecioPagadoItm".$valorPieAdicional."').style.display = '';
					byId('lstMostrarItm".$valorPieAdicional."').style.display = 'none';
					selectedOption('lstMostrarItm".$valorPieAdicional."','');
				} else {
					selectedOption('lstMostrarPendienteItm".$valorPieAdicional."','');
				}
				byId('divCondicionItm".$valorPieAdicional."').style.display = '';
				if (byId('lstMostrarItm".$valorPieAdicional."').value > 0) {
					byId('divCondicionItm".$valorPieAdicional."').style.display = 'none';
					byId('cbxCondicionItm".$valorPieAdicional."').checked = false;
				}");
				
			} else if (in_array($frmDcto['lstTipoAdicionalItm'.$valorPieAdicional],array(3))) { // 3 = Contrato
				$txtTotalAdicionalContrato += str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]);
				
				$objResponse->script("
				byId('divCondicionItm".$valorPieAdicional."').style.display = 'none';
				byId('cbxCondicionItm".$valorPieAdicional."').checked = false;
				selectedOption('lstMostrarItm".$valorPieAdicional."','');
				selectedOption('lstMostrarPendienteItm".$valorPieAdicional."','');");
				
				$objResponse->assign("txtPrecioPagadoItm".$valorPieAdicional,"value",number_format(0, 2, ".", ","));
			}
			
			if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				$objResponse->script("
				byId('lstMostrarItm".$valorPieAdicional."').style.display = 'none';");
			}
			
			if (in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				$objResponse->script("
				byId('divPrecioPagadoItm".$valorPieAdicional."').style.display = 'none';");
			}
		}
	}
	
	if ($frmDcto['rbtInicial'] == 1) {
		$txtPorcInicial = doubleval(str_replace(",","",$frmDcto['txtPorcInicial']));
		$txtMontoInicial = ($txtPorcInicial * $txtPrecioVenta) / 100;
	} else {
		$txtMontoInicial = doubleval(str_replace(",","",$frmDcto['txtMontoInicial']));
		$txtPorcInicial = ($txtMontoInicial * 100) / $txtPrecioVenta;
	}
	
	$txtMontoAnticipo = ($txtTotalArticulo - $txtTotalArticuloFinanciar) + ($txtTotalAdicional - $txtTotalAdicionalFinanciar);
	$txtMontoCashBack = doubleval(str_replace(",","",$frmDcto['txtMontoCashBack']));
	$txtSaldoFinanciar = ($txtPrecioVenta + $txtTotalArticuloFinanciar + $txtTotalAdicionalFinanciar) - ($txtMontoInicial + $txtMontoCashBack);
	
	$hddFactorFinanciar = doubleval(str_replace(",","",$frmDcto['hddFactorFinanciar']));
	$txtCuotasFinanciar = ($hddFactorFinanciar != 0) ? ($txtSaldoFinanciar * $hddFactorFinanciar) : doubleval(str_replace(",","",$frmDcto['txtCuotasFinanciar']));
	
	if ($txtSaldoFinanciar == 0) {
		$txtMontoFLAT = 0;
		$objResponse->script("
		byId('trBancoFinanciar').style.display = 'none';
		byId('trMontoFLAT').style.display = 'none';");
		
		if ($frmDcto['lstBancoFinanciar'] > 0) {
			$objResponse->script("
			selectedOption('lstBancoFinanciar','');
			byId('lstBancoFinanciar').onchange();");
		}
	} else {
		$objResponse->script("
		byId('trBancoFinanciar').style.display = '';
		byId('trMontoFLAT').style.display = '';
		
		byId('fieldsetFormaPago').style.display = '';");
		$txtPorcFLAT =  str_replace(",","",$frmDcto['txtPorcFLAT']);
		$txtMontoFLAT = round((($txtSaldoFinanciar * $txtPorcFLAT) / 100),2); 
	}
	
	$txtTotalInicialAdicionales = ($txtMontoInicial + $txtMontoCashBack) + $txtTotalAdicional;
	$txtMontoComplementoInicial = ($txtMontoInicial + $txtMontoCashBack) + $txtMontoAnticipo;
	$txtPrecioTotal = $txtMontoComplementoInicial + $txtMontoFLAT;
	$txtTotalPedido = $txtPrecioVenta + $txtTotalAdicional + $txtSubTotalArticuloConImpuesto;
	
	$objResponse->assign("txtPrecioBase","value",number_format($txtPrecioBase, 2, ".", ","));
	$objResponse->assign("txtPrecioVenta","value",number_format($txtPrecioVenta, 2, ".", ","));
	$objResponse->assign("txtTotalOpcionales", "value", number_format($txtSubTotalArticuloConImpuesto, 2, ".", ","));
	$objResponse->assign("txtPrecioVentaOpcional", "value", number_format(($txtPrecioVenta + $txtSubTotalArticuloConImpuesto), 2, ".", ","));
	$objResponse->assign("txtTotalAdicionales", "value", number_format(($txtTotalAdicional - $txtTotalAdicionalPredeterminado), 2, ".", ","));
	
	$objResponse->assign("txtTotalOpcional","value",number_format($txtSubTotalArticuloConImpuesto, 2, ".", ","));
	$objResponse->assign("txtTotalAdicionalNormal","value",number_format(($txtTotalAdicional - $txtTotalAdicionalPredeterminado), 2, ".", ","));
	$objResponse->assign("txtTotalAdicionalPredeterminado","value",number_format($txtTotalAdicionalPredeterminado, 2, ".", ","));
	$objResponse->assign("txtTotalAdicional","value",number_format($txtTotalAdicional, 2, ".", ","));
	$objResponse->assign("txtTotalAdicionalContrato","value",number_format($txtTotalAdicionalContrato, 2, ".", ","));
	
	$objResponse->assign("txtPorcInicial","value",number_format($txtPorcInicial, 2, ".", ","));
	$objResponse->assign("txtMontoInicial","value",number_format($txtMontoInicial, 2, ".", ","));
	$objResponse->assign("txtSaldoFinanciar","value",number_format($txtSaldoFinanciar, 2, ".", ","));
	$objResponse->assign("txtCuotasFinanciar","value",number_format($txtCuotasFinanciar, 2, ".", ","));
	$objResponse->assign("txtMontoFLAT","value",number_format($txtMontoFLAT, 2, ".", ","));
	
	$objResponse->assign("txtMontoAnticipo","value",number_format($txtMontoAnticipo, 2, ".", ","));
	$objResponse->assign("txtMontoAnticipoUnidad","value",number_format($txtMontoInicial, 2, ".", ","));
	$objResponse->assign("txtMontoCashBackUnidad","value",number_format($txtMontoCashBack, 2, ".", ","));
	$objResponse->assign("txtMontoComplementoInicial","value",number_format($txtMontoComplementoInicial, 2, ".", ","));
	$objResponse->assign("txtTotalInicialAdicionales","value",number_format($txtTotalInicialAdicionales, 2, ".", ","));
	$objResponse->assign("txtPrecioTotal","value",number_format($txtPrecioTotal, 2, ".", ","));
	$objResponse->assign("txtTotalPedido","value",number_format($txtTotalPedido, 2, ".", ","));
	
	if (count($arrayObjPieArticulo) > 0) { // SI TIENE ITEMS AGREGADOS
		if (in_array($guardarDcto, array("1", "true"))) {
			$objResponse->script("
			window.setTimeout(function(){ xajax_guardarDcto(xajax.getFormValues('frmDcto'), '".$bloquearForm."', '".$frmDcto['txtIdPedido']."'); },1000);");
		}
	}
	
	if (in_array($calcularDcto, array("1", "true"))) { // FORMAS EN QUE ACEPTA VALOR TRUE DESDE PHP Y JAVASCRIPT
		usleep(1 * 1000000);
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	}
	
	return $objResponse;
}

function cargaLstAnoBuscar($nombreObjeto = "", $selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_ano'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstBancoFinanciar($nombreObjeto = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE nombreBanco <> '-' ORDER BY nombreBanco;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarBanco(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
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
	$totalRows = mysql_num_rows($rs);
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

function cargaLstCredito($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$arrayCredito = array(
		array(1, "Contado"),
		array(0, "Crédito"));
	$totalRows = count($arrayCredito);
	
	if ($selId == "0") { // 0 = Crédito
		$onChange = sprintf("selectedOption('lstCredito', 0);");
	} else if ($selId == "1") { // 1 = Contado
		$onChange = sprintf("selectedOption('lstCredito', 1);");
	}
	$onChange .= sprintf("xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '3', this.value, '1', '%s');",
		"19"); // 19 = Mostrador Público Contado
	
	$html = "<select id=\"lstCredito\" name=\"lstCredito\" onchange=\"".$onChange."\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($arrayCredito as $indice => $valor) {
		$selected = ($selId == $arrayCredito[$indice][0] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$arrayCredito[$indice][0]."\">".$arrayCredito[$indice][1]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCredito","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($nombreObjeto = "", $claveFiltro = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$claveFiltro = (is_array($claveFiltro)) ? implode(",",$claveFiltro) : $claveFiltro;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(empleado.activo = 1
	OR empleado.id_empleado = %s)",
		valTpDato($selId, "int"));
	
	if ($claveFiltro != "-1" && $claveFiltro != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("empleado.clave_filtro IN (%s)",
			valTpDato($claveFiltro, "campo"));
	}
	
	$query = sprintf("SELECT
		empleado.id_empleado,
		empleado.nombre_empleado,
		empleado.nombre_departamento,
		empleado.nombre_cargo,
		empleado.clave_filtro,
		empleado.nombre_filtro
	FROM vw_pg_empleados empleado %s
	ORDER BY empleado.nombre_empleado;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMarcaModeloVersion($tpLst, $idLstOrigen, $nombreObjeto, $objetoBuscar = "false", $padreId = "", $selId = "", $onChange = "") {
	$objResponse = new xajaxResponse();
	
	$padreId = is_array($padreId) ? implode(",",$padreId) : $padreId;
	
	switch ($tpLst) {
		case "unidad_basica" : $arraySelec = array("lstPadre","lstMarca","lstModelo","lstVersion");
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1) {
		$onChange = "onchange=\"".$onChange." xajax_cargaLstMarcaModeloVersion('".$tpLst."', '".$arraySelec[$posList+1]."', '".$nombreObjeto."', '".$objetoBuscar."', getSelectValues(byId(this.id)), '', '".str_replace("'","\'",$onChange)."');\"";
	}
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1' && $nombreObjeto != "Buscar") {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT * FROM an_marca marca
				ORDER BY marca.nom_marca;");
				$campoId = "id_marca";
				$campoDesc = "nom_marca";
				break;
			case 1 :
				$query = sprintf("SELECT * FROM an_modelo modelo
				WHERE modelo.id_marca IN (%s)
				ORDER BY modelo.nom_modelo;",
					valTpDato($padreId, "campo"));
				$campoId = "id_modelo";
				$campoDesc = "nom_modelo";
				break;
			case 2 :
				$query = sprintf("SELECT * FROM an_version vers
				WHERE vers.id_modelo IN (%s)
				ORDER BY vers.nom_version;",
					valTpDato($padreId, "campo"));
				$campoId = "id_version";
				$campoDesc = "nom_version";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select ".(($totalRows > 2 && $objetoBuscar == "true") ? "multiple=\"multiple\"": "")." id=\"".$arraySelec[$posList+1].$nombreObjeto."\" name=\"".$arraySelec[$posList+1].$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\">".utf8_encode($row[$campoDesc])."</option>";
		}
		$html .= "</select>";
	}
	
	$objResponse->assign("td".$arraySelec[$posList+1].$nombreObjeto, 'innerHTML', $html);
	
	return $objResponse;
}

function cargaLstMesesFinanciar($idBanco, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($arrayFinal['lstMesesFinanciar'] == $rowFactor['mes'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$lstMesesFinanciar .= "<option ".$selected." value=\"".$rowFactor['mes']."\">".$rowFactor['financiamento']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMesesFinanciar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmDcto')); ".$onChange."\"";

	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" ".$class." ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda'] || $totalRows == 1) {
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

function cargaLstPoliza($nombreObjeto = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_poliza WHERE estatus = 1 ORDER BY nombre_poliza;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarPoliza(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_poliza'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_poliza']."\">".utf8_encode($row['nombre_poliza'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTasaCambio($idMoneda, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmDcto')); ".$onChange."\"";
	
	$query = sprintf("SELECT *
	FROM pg_tasa_cambio tasa_cambio
		INNER JOIN pg_monedas moneda_local ON (tasa_cambio.id_moneda_nacional = moneda_local.idmoneda)
	WHERE tasa_cambio.id_moneda_extranjera = %s;",
		valTpDato($idMoneda, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstTasaCambio\" name=\"lstTasaCambio\" ".$class." ".$onChange." style=\"width:150px\">";
	if ($totalRows > 0) {
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_tasa_cambio'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			
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

function cargarDcto($idPedido, $idPresupuesto = "", $idPedidoFinanciamiento = "", $idFactura = "", $idEmpresa = "", $numeroPresupuesto = "", $hddTipoPedido = "") {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('trBuscarPresupuesto').style.display = 'none';
	byId('trFieldsetCliente').style.display = '';
	byId('trFieldsetUnidadFisica').style.display = '';
	byId('trFieldsetVehiculoUsado').style.display = '';
	byId('trBtnGuardar').style.display = '';
	
	byId('fielsetPresupuestoAccesorios').style.display = 'none';");
	
	if (in_array(idArrayPais,array(1,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("
		byId('fieldsetArticulo').style.display = 'none';
		
		byId('trMontoCashBack').style.display = 'none';");
	}
	
	if ($idFactura > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT cxc_fact.*,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_fact_vent
		FROM cj_cc_encabezadofactura cxc_fact
		WHERE cxc_fact.idFactura = %s
			AND cxc_fact.anulada LIKE 'NO';",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_array($rsFactura);
		
		$idPedido = $rowFactura['numeroPedido'];
		$idEmpresa = $rowFactura['id_empresa'];
		
		$objResponse->assign("txtIdFactura","value",$idFactura);
		
		// VERIFICA VALORES DE CONFIGURACION (Editar Factura de Venta de Vehículos)
		$queryConfig209 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 209 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa,"int"));
		$rsConfig209 = mysql_query($queryConfig209);
		if (!$rsConfig209) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig209 = mysql_num_rows($rsConfig209);
		$rowConfig209 = mysql_fetch_assoc($rsConfig209);
		
		$valor = explode("|",$rowConfig209['valor']);
		$estatus209 = $valor[0];
		$cantDiasMaximo = $valor[1];
		$cantMesesAnteriores = $valor[2];
		
		if ($estatus209 == 1) {
			$txtFechaProveedor = date(str_replace("d","01",spanDateFormat), strtotime($rowFactura['fechaRegistroFactura']));
			if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$cantMesesAnteriores." month", strtotime(date(spanDateFormat))))
				&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$cantMesesAnteriores." month", strtotime(date(spanDateFormat)))))
			|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $cantMesesAnteriores) { // VERIFICA SI ES DE MESES ANTERIORES
				if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $cantDiasMaximo
				|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI LA EDICION DE LA VENTA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
					if (!($rowFactura['id_pedido_reemplazo'] > 0)) {
						$permitirEditarFactura = true;
					}
				}
			}
		}
		
		if (!($permitirEditarFactura == true)) {
			return $objResponse->script("
			alert('Esta factura no puede ser editada');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_factura_venta_historico_list.php'; }");
		}
	}
	
	if (($idPresupuesto > 0 || $idPedidoFinanciamiento > 0 || ($idEmpresa > 0 && $numeroPresupuesto > 0)) && !($idPedido > 0)) {
		if ($idPedidoFinanciamiento > 0) {
			// BUSCA LOS PRESUPUESTOS QUE PERTENECEN AL FINANCIAMIENTO
			$queryFinanciamiento = sprintf("SELECT *
			FROM fi_pedido ped_financ
				INNER JOIN fi_documento ped_financ_det ON (ped_financ.id_pedido_financiamiento = ped_financ_det.id_pedido_financiamiento)
				INNER JOIN an_presupuesto pres_vent ON (ped_financ_det.id_presupuesto = pres_vent.id_presupuesto)
			WHERE ped_financ.id_pedido_financiamiento = %s
				AND ped_financ.estatus_pedido IN (%s)
				AND pres_vent.estado IN (%s);",
				valTpDato($idPedidoFinanciamiento, "int"),
				valTpDato("0,3", "campo"), // 0 = No Aprobado, 1 = Parcialmente Pagado, 2 = Pagado, 3 = Aprobado, 4 = Atrasado
				valTpDato(0, "campo")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
			$rsFinanciamiento = mysql_query($queryFinanciamiento);
			if (!$rsFinanciamiento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFinanciamiento = mysql_num_rows($rsFinanciamiento);
			while ($rowFinanciamiento = mysql_fetch_assoc($rsFinanciamiento)) {
				$idPresupuesto = $rowFinanciamiento['id_presupuesto'];
			}
		}
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtFechaReserva').className = 'inputHabilitado';
		byId('txtFechaEntrega').className = 'inputHabilitado';
		byId('txtFechaVenta').className = 'inputHabilitado';
		byId('txtFechaAdministracion').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		// BUSCA LOS DATOS DEL PRESUPUESTO
		$queryPresupuesto = sprintf("SELECT pres_vent.*,
			an_ped_vent.id_pedido
		FROM an_presupuesto pres_vent
			LEFT JOIN an_pedido an_ped_vent ON (pres_vent.id_presupuesto = an_ped_vent.id_presupuesto)
		WHERE pres_vent.id_presupuesto = %s
			OR (pres_vent.id_empresa = %s
				AND pres_vent.numeracion_presupuesto LIKE %s);",
			valTpDato($idPresupuesto, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroPresupuesto, "text"));
		$rsPresupuesto = mysql_query($queryPresupuesto);
		if (!$rsPresupuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPresupuesto = mysql_num_rows($rsPresupuesto);
		$rowPresupuesto = mysql_fetch_assoc($rsPresupuesto);
		
		$idEmpresa = $rowPresupuesto['id_empresa'];
		$idCliente = $rowPresupuesto['id_cliente'];
		$idPresupuesto = $rowPresupuesto['id_presupuesto'];
		$idUnidadBasica = $rowPresupuesto['id_uni_bas'];
		
		if (!($totalRowsPresupuesto > 0)) {
			return $objResponse->script("
			alert('No se encontró el registro');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		if ($rowPresupuesto['id_pedido'] > 0) {
			return $objResponse->script("
			alert('El pedido del presupuesto ".$rowPresupuesto['numeracion_presupuesto']." ya ha sido generado');
			window.location = 'an_pedido_venta_form.php?id=".$rowPresupuesto['id_pedido']."';");
		}
		
		if (in_array($rowPresupuesto['estado'],array(3))) { // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
			return $objResponse->script("
			alert('El presupuesto ".$rowPresupuesto['numeracion_presupuesto']." está desautorizado');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		// BUSCA LA DISPONIBILIDAD DE LA UNIDAD BASICA
		$queryUnidadFisica = sprintf("SELECT COUNT(*) FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_uni_bas = %s
			AND uni_fis.estado_venta IN ('POR REGISTRAR','DISPONIBLE')
			AND uni_fis.propiedad = 'PROPIO';",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsUnidadFisica = mysql_num_rows($rsUnidadFisica);
		$rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica);
		if ($totalRowsUnidadFisica == 0) {
			return $objResponse->script("
			alert('No existen unidades físicas disponibles para el presupuesto: ".$rowPresupuesto['numeracion_presupuesto']."');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		// BUSCA SI EL PEDIDO PERTENECE A UN FINANCIAMIENTO
		$queryFinanciamiento = sprintf("SELECT *
		FROM fi_pedido ped_financ
			INNER JOIN fi_documento ped_financ_det ON (ped_financ.id_pedido_financiamiento = ped_financ_det.id_pedido_financiamiento)
		WHERE ped_financ_det.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsFinanciamiento = mysql_query($queryFinanciamiento);
		if (!$rsFinanciamiento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFinanciamiento = mysql_num_rows($rsFinanciamiento);
		$rowFinanciamiento = mysql_fetch_assoc($rsFinanciamiento);
		
		$objResponse->assign("hddIdFinanciamiento","value",$rowFinanciamiento['id_pedido_financiamiento']);
		$objResponse->assign("txtNumeroFinanciamiento","value",$rowFinanciamiento['numeracion_pedido']);
		
		if ($totalRowsFinanciamiento > 0) {
			$objResponse->script("
			byId('txtIdEmpresa').className = 'inputInicial';
			byId('txtIdEmpresa').onblur = function() { }
			byId('txtIdEmpresa').readOnly = true;
			byId('aListarEmpresa').style.display = 'none';
			
			byId('txtIdCliente').className = 'inputInicial';
			byId('txtIdCliente').onblur = function() { }
			byId('txtIdCliente').readOnly = true;
			byId('aListarCliente').style.display = 'none';
			
			byId('txtIdUnidadBasica').className = 'inputInicial';
			byId('txtIdUnidadBasica').onblur = function() { }
			byId('txtIdUnidadBasica').readOnly = true;
			byId('aListarUnidadBasica').style.display = 'none';
			
			byId('txtPrecioBase').className = 'inputSinFondo';
			byId('txtPrecioBase').readOnly = true;
			byId('txtDescuento').className = 'inputSinFondo';
			byId('txtDescuento').readOnly = true;
			byId('rbtInicialPorc').style.display = 'none';
			byId('txtPorcInicial').className = 'inputInicial';
			byId('txtPorcInicial').readOnly = true;
			byId('rbtInicialMonto').style.display = 'none';
			byId('txtMontoInicial').className = 'inputSinFondo';
			byId('txtMontoInicial').readOnly = true;
			byId('txtMontoCashBack').className = 'inputSinFondo';
			byId('txtMontoCashBack').readOnly = true;
			
			byId('tblSinBancoFinanciar').style.display = 'none';
			
			byId('aAgregarArticulo').style.display = 'none';
			byId('btnQuitarArticulo').style.display = 'none';
			byId('aAgregarAdicional').style.display = 'none';
			byId('btnQuitarAdicional').style.display = 'none';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado("Empleado", $rowPresupuesto['asesor_ventas'], "", "", "false"));
		$objResponse->loadCommands(asignarCliente($idCliente, $idEmpresa, $estatusCliente, $condicionPago, $rowPresupuesto['id_clave_movimiento']));
		
		// DATOS PEDIDO
		$rowPresupuesto['id_moneda'] = ($rowPresupuesto['id_moneda'] > 0) ? $rowPresupuesto['id_moneda'] : $idMoneda;
		$idMonedaLocal = $rowPresupuesto['id_moneda'];
		$idMonedaOrigen = ($rowPresupuesto['id_moneda_tasa_cambio'] > 0) ? $rowPresupuesto['id_moneda_tasa_cambio'] : $rowPresupuesto['id_moneda'];
		$txtTasaCambio = ($rowPresupuesto['monto_tasa_cambio'] >= 0) ? $rowPresupuesto['monto_tasa_cambio'] : 0;
		
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("txtFechaTasaCambio","value",(($rowPresupuesto['fecha_tasa_cambio'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fecha_tasa_cambio'])) : ""));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
		$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowPresupuesto['id_tasa_cambio']));
		$objResponse->assign("hddIdPresupuestoVenta","value",$rowPresupuesto['id_presupuesto']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value",$rowPresupuesto['numeracion_presupuesto']);
		$objResponse->script(sprintf("
		selectedOption('lstTipoClave',3);
		
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','2','3','','1','%s','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
		}",
			$rowPresupuesto['id_clave_movimiento']));
		
		// UNIDAD FISICA
		$objResponse->loadCommands(asignarUnidadBasica($idUnidadBasica));
		
		// VENTA DE LA UNIDAD
		$txtPorcIva = $rowPresupuesto['porcentaje_iva'];
		$txtPorcIvaLujo = $rowPresupuesto['porcentaje_impuesto_lujo'];
		
		$objResponse->assign("txtPrecioBase","value",number_format($rowPresupuesto['precio_venta'], 2, "." , ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPresupuesto['monto_descuento'], 2, "." , ","));
		
		// VERIFICA SI TIENE IMPUESTO
		if (getmysql(sprintf("SELECT UPPER(isan_uni_bas) FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIva += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIva = 0;
			$spanPorcIva .= "Exento";
		}
		
		if (getmysql(sprintf("SELECT impuesto_lujo FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (2)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIvaLujo += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIvaLujo = 0;
		}
		
		if ($idUnidadFisica > 0) {
			if ($txtPorcIva != 0 && $txtPorcIva != $txtNuevoPorcIva) {
				$txtPorcIva = $txtNuevoPorcIva;
				$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
			}
			if ($txtPorcIvaLujo != 0 && $txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
				$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
				$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
			}
			(count($arrayMsg) > 0) ? $objResponse->alert(implode($arrayMsg,"\n")) : "";
		}
		
		$objResponse->assign("txtPorcIva","value",number_format($txtPorcIva, 2, ".", ","));
		$objResponse->assign("txtPorcIvaLujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
		$objResponse->assign("spanPorcIva","innerHTML",((strlen($spanPorcIva) > 0) ? "(".$spanPorcIva.")" : ""));
		
		// OPCIONALES
		$queryPresupuestoDet = sprintf("SELECT *
		FROM an_presupuesto_venta_detalle an_pres_vent_det
			INNER JOIN iv_articulos art ON (an_pres_vent_det.id_articulo = art.id_articulo)
		WHERE an_pres_vent_det.id_presupuesto_venta = %s
		ORDER BY an_pres_vent_det.id_presupuesto_venta_detalle ASC;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoDet = mysql_query($queryPresupuestoDet);
		if (!$rsPresupuestoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowPresupuestoDet = mysql_fetch_assoc($rsPresupuestoDet)) {
			$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
			WHERE id_empresa = %s
				AND id_articulo = %s
				AND cantidad_disponible_logica >= %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($rowPresupuestoDet['id_articulo'], "int"),
				valTpDato($rowPresupuestoDet['pendiente'], "real_inglesa"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
			$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
			
			if ($totalRowsArtEmp > 0) {
				$Result1 = insertarItemArticulo($contFila, "", $rowPresupuestoDet['id_presupuesto_venta_detalle'], $idCliente, $rowPresupuestoDet['id_articulo'], $rowArtEmp['id_casilla'], $rowPresupuestoDet['cantidad'], $rowPresupuestoDet['pendiente'], $rowPresupuestoDet['id_precio'], $rowPresupuestoDet['precio_unitario'], $rowPresupuestoDet['precio_sugerido'], "", "", $rowPresupuestoDet['monto_pagado'], $rowPresupuestoDet['id_iva'], $rowPresupuestoDet['id_condicion_pago'], $rowPresupuestoDet['id_condicion_mostrar'], $rowPresupuestoDet['id_condicion_mostrar_pendiente'], (($totalRowsFinanciamiento > 0) ? true : false));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			} else {
				$arrayArticuloNoDisponible[] = $rowPresupuestoDet['codigo_articulo'];
			}
		}
		
		if (count($arrayArticuloNoDisponible) > 0) {
			return $objResponse->script("
			alert('No posee disponible la cantidad suficiente de lo(s) artículos: ".implode(", ",$arrayArticuloNoDisponible)."');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		// ADICIONALES DE PAQUETE
		$queryPedidoDet = sprintf("SELECT
			paq_pres.id_paquete_presupuesto,
			paq_pres.id_presupuesto,
			paq_pres.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_pres.id_tipo_accesorio,
			(CASE paq_pres.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			paq_pres.precio_accesorio,
			paq_pres.costo_accesorio,
			paq_pres.porcentaje_iva_accesorio,
			(paq_pres.precio_accesorio + (paq_pres.precio_accesorio * paq_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_pres.iva_accesorio,
			paq_pres.monto_pagado,
			paq_pres.id_condicion_pago,
			paq_pres.id_condicion_mostrar,
			paq_pres.monto_pendiente,
			paq_pres.id_condicion_mostrar_pendiente,
			paq_pres.estatus_paquete_presupuesto
		FROM an_paquete_presupuesto paq_pres
			INNER JOIN an_acc_paq acc_paq ON (paq_pres.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_pres.id_presupuesto = %s
			AND (acc.id_mostrar_predeterminado IS NULL OR acc.id_mostrar_predeterminado = 0)
		ORDER BY paq_pres.id_paquete_presupuesto ASC;",
			valTpDato($idPresupuesto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemAdicional($contFila, "", "", $rowPedidoDet['id_accesorio'], $rowPedidoDet['id_acc_paq'], $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], $rowPedidoDet['id_tipo_accesorio'], (($totalRowsFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		// ADICIONALES
		$queryPedidoDet = sprintf("SELECT
			acc_pres.id_accesorio_presupuesto,
			acc_pres.id_presupuesto,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_pres.id_tipo_accesorio,
			(CASE acc_pres.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			acc_pres.precio_accesorio,
			acc_pres.costo_accesorio,
			acc_pres.porcentaje_iva_accesorio,
			(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_pres.iva_accesorio,
			acc_pres.monto_pagado,
			acc_pres.id_condicion_pago,
			acc_pres.id_condicion_mostrar,
			acc_pres.monto_pendiente,
			acc_pres.id_condicion_mostrar_pendiente,
			acc_pres.estatus_accesorio_presupuesto
		FROM an_accesorio_presupuesto acc_pres
			INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
		WHERE acc_pres.id_presupuesto = %s
			AND (acc.id_mostrar_predeterminado IS NULL OR acc.id_mostrar_predeterminado = 0)
		ORDER BY acc_pres.id_accesorio_presupuesto ASC;",
			valTpDato($idPresupuesto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemAdicional($contFila, "", $rowPedidoDet['id_accesorio_presupuesto'], $rowPedidoDet['id_accesorio'], "", $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], $rowPedidoDet['id_tipo_accesorio'], (($totalRowsFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		// ADICIONALES PREDETERMINADOS
		$queryAdicionalPredet = sprintf("SELECT * FROM an_accesorio acc WHERE acc.id_mostrar_predeterminado IN (1);");
		$rsAdicionalPredet = mysql_query($queryAdicionalPredet);
		if (!$rsAdicionalPredet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowAdicionalPredet = mysql_fetch_assoc($rsAdicionalPredet)) {
			$queryPedidoDet = sprintf("SELECT
				acc_pres.id_accesorio_presupuesto,
				acc_pres.id_presupuesto,
				acc.id_accesorio,
				CONCAT(acc.nom_accesorio, IF (acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
				acc.des_accesorio,
				acc_pres.id_tipo_accesorio,
				(CASE acc_pres.id_tipo_accesorio
					WHEN 1 THEN 'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
					WHEN 4 THEN 'Cargo'
				END) AS descripcion_tipo_accesorio,
				acc_pres.precio_accesorio,
				acc_pres.costo_accesorio,
				acc_pres.porcentaje_iva_accesorio,
				(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
				acc_pres.iva_accesorio,
				acc_pres.monto_pagado,
				acc_pres.id_condicion_pago,
				acc_pres.id_condicion_mostrar,
				acc_pres.monto_pendiente,
				acc_pres.id_condicion_mostrar_pendiente,
				acc_pres.estatus_accesorio_presupuesto
			FROM an_accesorio_presupuesto acc_pres
				INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
			WHERE acc_pres.id_presupuesto = %s
				AND acc_pres.id_accesorio = %s
			ORDER BY acc.nom_accesorio ASC;",
				valTpDato($idPresupuesto, "int"),
				valTpDato($rowAdicionalPredet['id_accesorio'], "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			$Result1 = insertarItemAdicionalPredeterminado($contFila, "", $rowPedidoDet['id_accesorio_presupuesto'], $rowAdicionalPredet['id_accesorio'], "", $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], $rowPedidoDet['id_tipo_accesorio'], (($totalRowsFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		if (!($totalRowsFinanciamiento > 0)) {
			// FORMA DE PAGO
			if ($rowPresupuesto['tipo_inicial'] == 0) {
				$objResponse->script("
				byId('rbtInicialPorc').checked = true;
				byId('rbtInicialPorc').click();");
			} else {
				$objResponse->script("
				byId('rbtInicialMonto').checked = true;
				byId('rbtInicialMonto').click();");
			}
		}
		$objResponse->assign("hddTipoInicial","value",$rowPresupuesto['tipo_inicial']);
		$objResponse->assign("txtPorcInicial","value",number_format($rowPresupuesto['porcentaje_inicial'], 2, "." , ","));
		$objResponse->assign("txtMontoInicial","value",number_format($rowPresupuesto['monto_inicial'], 2, "." , ","));
		$objResponse->assign("txtMontoCashBack","value",number_format($rowPresupuesto['monto_cash_back'], 2, "." , ","));
		
		// CONTRATO A PAGARSE
		if ($rowPresupuesto['porcentaje_inicial'] < 100) {
			if ($rowPresupuesto['id_banco_financiar'] > 0) {
			} else {
				$objResponse->script("
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;");
			}
		}
		$objResponse->loadCommands(cargaLstBancoFinanciar("lstBancoFinanciar", $rowPresupuesto['id_banco_financiar']));
		$lstMesesFinanciar = $rowPresupuesto['meses_financiar'];
		$txtInteresCuotaFinanciar = $rowPresupuesto['interes_cuota_financiar'];
		$txtCuotasFinanciar = numformat($rowPresupuesto['cuotas_financiar'],2);
		$lstMesesFinanciar2 = $rowPresupuesto['meses_financiar2'];
		$txtInteresCuotaFinanciar2 = $rowPresupuesto['interes_cuota_financiar2'];
		$txtCuotasFinanciar2 = numformat($rowPresupuesto['cuotas_financiar2'],2);
		$lstMesesFinanciar3 = $rowPresupuesto['meses_financiar3'];
		$txtInteresCuotaFinanciar3 = $rowPresupuesto['interes_cuota_financiar3'];
		$txtCuotasFinanciar3 = numformat($rowPresupuesto['cuotas_financiar3'],3);
		$lstMesesFinanciar4 = $rowPresupuesto['meses_financiar4'];
		$txtInteresCuotaFinanciar4 = $rowPresupuesto['interes_cuota_financiar4'];
		$txtCuotasFinanciar4 = numformat($rowPresupuesto['cuotas_financiar4'],4);
		$valores = array(
			"lstMesesFinanciar*".$rowPresupuesto['meses_financiar'],
			"txtInteresCuotaFinanciar*".$rowPresupuesto['interes_cuota_financiar'],
			"txtCuotasFinanciar*".$rowPresupuesto['cuotas_financiar'],
			"txtFechaCuotaFinanciar*".$rowPresupuesto['fecha_pago_cuota'],
			"lstMesesFinanciar2*".$rowPresupuesto['meses_financiar2'],
			"txtInteresCuotaFinanciar2*".$rowPresupuesto['interes_cuota_financiar2'],
			"txtCuotasFinanciar2*".$rowPresupuesto['cuotas_financiar2'],
			"txtFechaCuotaFinanciar2*".$rowPresupuesto['fecha_pago_cuota2'],
			"lstMesesFinanciar3*".$rowPresupuesto['meses_financiar3'],
			"txtInteresCuotaFinanciar3*".$rowPresupuesto['interes_cuota_financiar3'],
			"txtCuotasFinanciar3*".$rowPresupuesto['cuotas_financiar3'],
			"txtFechaCuotaFinanciar3*".$rowPresupuesto['fecha_pago_cuota3'],
			"lstMesesFinanciar4*".$rowPresupuesto['meses_financiar4'],
			"txtInteresCuotaFinanciar4*".$rowPresupuesto['interes_cuota_financiar4'],
			"txtCuotasFinanciar4*".$rowPresupuesto['cuotas_financiar4'],
			"txtFechaCuotaFinanciar4*".$rowPresupuesto['fecha_pago_cuota4']);
		$objResponse->loadCommands(asignarBanco($rowPresupuesto['id_banco_financiar'], $valores));
		$objResponse->loadCommands(asignarFactor($rowPresupuesto['meses_financiar'], array("lstBancoFinanciar" => $rowPresupuesto['id_banco_financiar'])));
		$objResponse->assign("txtPorcFLAT","value",number_format($rowPresupuesto['porcentaje_flat'], 2, "." , ","));
		$objResponse->assign("txtMontoFLAT","value",number_format($rowPresupuesto['monto_flat'], 2, "." , ","));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza("lstPoliza", $rowPresupuesto['id_poliza']));
		$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPresupuesto['nombre_agencia_seguro']);
		$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPresupuesto['direccion_agencia_seguro']);
		$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPresupuesto['ciudad_agencia_seguro']);
		$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPresupuesto['pais_agencia_seguro']);
		$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPresupuesto['telefono_agencia_seguro']);
		$objResponse->assign("txtNumPoliza","value",$rowPresupuesto['num_poliza']);
		$objResponse->assign("txtMontoSeguro","value",number_format($rowPresupuesto['monto_seguro'], 2, "." , ","));
		$objResponse->assign("txtPeriodoPoliza","value",$rowPresupuesto['periodo_poliza']);
		$objResponse->assign("txtDeduciblePoliza","value",$rowPresupuesto['ded_poliza']);
		$objResponse->assign("txtFechaEfect","value",(($rowPresupuesto['fech_efect'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fech_efect'])) : ""));
		$objResponse->assign("txtFechaExpi","value",(($rowPresupuesto['fech_expira'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fech_expira'])) : ""));
		$objResponse->assign("txtInicialPoliza","value",$rowPresupuesto['inicial_poliza']);
		$objResponse->assign("txtMesesPoliza","value",$rowPresupuesto['meses_poliza']);
		$objResponse->assign("txtCuotasPoliza","value",$rowPresupuesto['cuotas_poliza']);
		
		// OTROS
		$objResponse->assign("txtMontoAnticipo","value",number_format($rowPresupuesto['anticipo'], 2, "." , ","));
		$objResponse->assign("txtMontoComplementoInicial","value",number_format($rowPresupuesto['complemento_inicial'], 2, "." , ","));
		
		// OBSERVACIONES
		$objResponse->assign("txtObservacion","innerHTML",$rowPresupuesto['observacion']);
		
		// COMPROBACION
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300"), $rowPresupuesto['gerente_ventas']));
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteAdministracion", "3", $rowPresupuesto['administracion']));
		
		
		// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
		$queryPresupuestoAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio pres_acc WHERE pres_acc.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoAccesorio = mysql_query($queryPresupuestoAccesorio);
		if (!$rsPresupuestoAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPresupuestoAccesorio = mysql_num_rows($rsPresupuestoAccesorio);
		$rowPresupuestoAccesorio = mysql_fetch_assoc($rsPresupuestoAccesorio);
		
		// PRESUPUESTO ACCESORIOS
		if ($totalRowsPresupuestoAccesorio > 0) {
			$objResponse->script("
			byId('fielsetPresupuestoAccesorios').style.display = '';");
			
			$objResponse->assign("hddIdPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtNumeroPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtSubTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'], 2, ".", ","));
			$objResponse->assign("txtTotalImpuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->assign("txtTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'] + $rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->script(sprintf("byId('aEditarPresupuestoAcc').href = 'an_combo_presupuesto_list.php?view=1&id=%s';",
				$idPresupuesto));
			$objResponse->script(sprintf("byId('aPresupuestoAccPDF').href = 'javascript:verVentana(\'reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s\', 960, 550);';",
				$rowPresupuestoAccesorio['id_presupuesto_accesorio']));
		}
		
		$objResponse->script("cerrarVentana = false;");
		
	} else if ($idPedido > 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtFechaReserva').className = 'inputHabilitado';
		byId('txtFechaEntrega').className = 'inputHabilitado';
		byId('txtFechaVenta').className = 'inputHabilitado';
		byId('txtFechaAdministracion').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		// BUSCA LOS DATOS DEL PEDIDO
		$queryPedido = sprintf("SELECT an_ped_vent.*,
			pres_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			cxc_fact.idFactura AS id_factura_reemplazo,
			cxc_fact.numeroFactura AS numero_factura_reemplazo,
			pres_vent_acc.id_presupuesto_accesorio,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
			CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
			cliente.tipo,
			cliente.ciudad,
			cliente.direccion,
			cliente.telf,
			cliente.otrotelf,
			cliente.correo,
			cliente.reputacionCliente + 0 AS id_reputacion_cliente,
			cliente.tipo_cuenta_cliente,
			clave_mov.id_clave_movimiento,
			clave_mov.clave,
			clave_mov.descripcion AS descripcion_clave_movimiento,
			IFNULL(uni_fis.id_uni_bas, pres_vent.id_uni_bas) AS id_uni_bas,
			CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			uni_fis.estado_venta,
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.nombre_cargo,
			vw_pg_empleado.telefono,
			vw_pg_empleado.celular,
			vw_pg_empleado.email,
			IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
			IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
			IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
			pres_vent.id_banco_financiar,
			banco.nombreBanco,
			pres_vent.estado AS estado_presupuesto,
			an_ped_vent.estado_pedido,
			ped_financ.id_pedido_financiamiento,
			ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
			ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
			adicional_contrato.nombre_agencia_seguro,
			adicional_contrato.direccion_agencia_seguro,
			adicional_contrato.ciudad_agencia_seguro,
			adicional_contrato.pais_agencia_seguro,
			adicional_contrato.telefono_agencia_seguro,
			
			IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_pedido an_ped_vent
			INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
			INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
			INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
			LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
				LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
			LEFT JOIN an_adicionales_contrato adicional_contrato ON (an_ped_vent.id_pedido = adicional_contrato.id_pedido)
			LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
				LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
				LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
					LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (an_ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (an_ped_vent.id_factura_cxc = cxc_fact.idFactura)
		WHERE an_ped_vent.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idEmpresa = $rowPedido['id_empresa'];
		$idCliente = $rowPedido['id_cliente'];
		$idPresupuesto = $rowPedido['id_presupuesto'];
		$idFactura = ($idFactura > 0) ? $idFactura : $rowPedido['id_factura_cxc'];
		$idPedidoFinanciamiento = $rowPedido['id_pedido_financiamiento'];
		$idUnidadFisica = $rowPedido['id_unidad_fisica'];
		$idUnidadBasica = $rowPedido['id_uni_bas'];
		
		if (in_array($rowPedido['estado_pedido'],array(2,4,5))) { // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			if ($rowPedido['estatus_unidad_fisica'] == 1 && !isset($_GET['idFactura'])) {
				return $objResponse->script("
				alert('El pedido ".$rowPedido['numeracion_pedido']." ya fue facturado');
				if (top.history.back()) { top.history.back(); } else { window.location.href='an_pedido_venta_list.php'; }");
			}
		} else if (in_array($rowPedido['estado_pedido'],array(3))) { // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			$objResponse->alert("El pedido ".$rowPedido['numeracion_pedido']." está desautorizado");
		}
		
		$objResponse->assign("hddIdFinanciamiento","value",$rowPedido['id_pedido_financiamiento']);
		$objResponse->assign("txtNumeroFinanciamiento","value",$rowPedido['numeracion_pedido_financiamiento']);
		
		if ($idPedidoFinanciamiento > 0) {
			$objResponse->script("
			byId('txtIdEmpresa').className = 'inputInicial';
			byId('txtIdEmpresa').onblur = function() { }
			byId('txtIdEmpresa').readOnly = true;
			byId('aListarEmpresa').style.display = 'none';
			
			byId('txtIdCliente').className = 'inputInicial';
			byId('txtIdCliente').onblur = function() { }
			byId('txtIdCliente').readOnly = true;
			byId('aListarCliente').style.display = 'none';
			
			byId('txtIdUnidadBasica').className = 'inputInicial';
			byId('txtIdUnidadBasica').onblur = function() { }
			byId('txtIdUnidadBasica').readOnly = true;
			byId('aListarUnidadBasica').style.display = 'none';
			
			byId('txtPrecioBase').className = 'inputSinFondo';
			byId('txtPrecioBase').readOnly = true;
			byId('txtDescuento').className = 'inputSinFondo';
			byId('txtDescuento').readOnly = true;
			byId('rbtInicialPorc').style.display = 'none';
			byId('txtPorcInicial').className = 'inputInicial';
			byId('txtPorcInicial').readOnly = true;
			byId('rbtInicialMonto').style.display = 'none';
			byId('txtMontoInicial').className = 'inputSinFondo';
			byId('txtMontoInicial').readOnly = true;
			byId('txtMontoCashBack').className = 'inputSinFondo';
			byId('txtMontoCashBack').readOnly = true;
			
			byId('tblSinBancoFinanciar').style.display = 'none';
			
			byId('aAgregarArticulo').style.display = 'none';
			byId('btnQuitarArticulo').style.display = 'none';
			byId('aAgregarAdicional').style.display = 'none';
			byId('btnQuitarAdicional').style.display = 'none';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado("Empleado", $rowPedido['asesor_ventas'], "", "", "false"));
		$objResponse->loadCommands(asignarCliente($idCliente, $idEmpresa, $estatusCliente, $condicionPago, $rowPedido['id_clave_movimiento']));
		
		// DATOS PEDIDO
		$idMonedaLocal = $rowPedido['id_moneda'];
		$idMonedaOrigen = ($rowPedido['id_moneda_tasa_cambio'] > 0) ? $rowPedido['id_moneda_tasa_cambio'] : $rowPedido['id_moneda'];
		$txtTasaCambio = ($rowPedido['monto_tasa_cambio'] >= 0) ? $rowPedido['monto_tasa_cambio'] : 0;
		
		$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido']);
		$objResponse->assign("txtNumeroPedidoPropio","value",$rowPedido['numeracion_pedido']);
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat, strtotime($rowPedido['fecha'])));
		$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("txtFechaTasaCambio","value",(($rowPedido['fecha_tasa_cambio'] != "") ? date(spanDateFormat, strtotime($rowPedido['fecha_tasa_cambio'])) : ""));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
		$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowPedido['id_tasa_cambio']));
		$objResponse->assign("hddIdPresupuestoVenta","value",$rowPedido['id_presupuesto']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value",$rowPedido['numeracion_presupuesto']);
		$objResponse->script(sprintf("
		selectedOption('lstTipoClave',3);
		
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','2','3','','1','%s','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
		}",
			$rowPedido['id_clave_movimiento']));
		
		if ($idFactura > 0) {
			// BUSCA LOS DATOS DE LA FACTURA
			$queryFactura = sprintf("SELECT cxc_fact.*,
				(CASE cxc_fact.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS estado_fact_vent
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = %s
				AND cxc_fact.anulada LIKE 'NO';",
				valTpDato($idFactura, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowFactura = mysql_fetch_array($rsFactura);
			
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
				$rowFactura['idFactura']);
			$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>", $rowFactura['idFactura']);
			
			$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
			"<tr align=\"center\">".
				"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
				"<td>".
					"<table>".
					"<tr align=\"right\">".
						"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
						"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
						"<td>".$rowFactura['numeroFactura']."</td>".
					"</tr>".
					"</table>".
				"</td>".
			"</tr>".
			"</table>";
			$objResponse->assign("tdMsjPedido","innerHTML",$html);
		}
		
		// UNIDAD FISICA
		if ($idUnidadFisica > 0) {
			$objResponse->loadCommands(asignarUnidadFisica($idUnidadFisica));
			$objResponse->assign("hddIdUnidadFisicaAnterior","value",$idUnidadFisica);
		} else {
			$objResponse->loadCommands(asignarUnidadBasica($idUnidadBasica));
		}
		
		// VENTA DE LA UNIDAD
		$txtPorcIva = $rowPedido['porcentaje_iva'];
		$txtPorcIvaLujo = $rowPedido['porcentaje_impuesto_lujo'];
		
		$objResponse->assign("txtPrecioBase","value",number_format($rowPedido['precio_venta'], 2, "." , ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPedido['monto_descuento'], 2, "." , ","));
		
		// VERIFICA SI TIENE IMPUESTO
		if (getmysql(sprintf("SELECT UPPER(isan_uni_bas) FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIva += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIva = 0;
			$spanPorcIva .= "Exento";
		}
		
		if (getmysql(sprintf("SELECT impuesto_lujo FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (2)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIvaLujo += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIvaLujo = 0;
		}
		
		if ($idUnidadFisica > 0) {
			if ($txtPorcIva != 0 && $txtPorcIva != $txtNuevoPorcIva) {
				$txtPorcIva = $txtNuevoPorcIva;
				$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
			}
			if ($txtPorcIvaLujo != 0 && $txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
				$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
				$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
			}
			(count($arrayMsg) > 0) ? $objResponse->alert(implode($arrayMsg,"\n")) : "";
		}
		
		$objResponse->assign("txtPorcIva","value",number_format($txtPorcIva, 2, ".", ","));
		$objResponse->assign("txtPorcIvaLujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
		$objResponse->assign("spanPorcIva","innerHTML",((strlen($spanPorcIva) > 0) ? "(".$spanPorcIva.")" : ""));
		
		// OPCIONALES
		$queryPedidoDet = sprintf("SELECT *
		FROM an_pedido_venta_detalle an_ped_vent_det
			INNER JOIN iv_articulos art ON (an_ped_vent_det.id_articulo = art.id_articulo)
		WHERE an_ped_vent_det.id_pedido_venta = %s
		ORDER BY an_ped_vent_det.id_pedido_venta_detalle ASC;",
			valTpDato($idPedido, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemArticulo($contFila, $rowPedidoDet['id_pedido_venta_detalle'], $rowPedidoDet['id_presupuesto_venta_detalle'], $idCliente, $rowPedidoDet['id_articulo'], $rowPedidoDet['id_casilla'], $rowPedidoDet['cantidad'], $rowPedidoDet['pendiente'], $rowPedidoDet['id_precio'], $rowPedidoDet['precio_unitario'], $rowPedidoDet['precio_sugerido'], "", "", $rowPedidoDet['monto_pagado'], $rowPedidoDet['id_iva'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], (($idPedidoFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		// ADICIONALES DE PAQUETE
		$queryPedidoDet = sprintf("SELECT
			paq_ped.id_paquete_pedido,
			paq_ped.id_pedido,
			paq_ped.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_ped.id_tipo_accesorio,
			(CASE paq_ped.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			paq_ped.precio_accesorio,
			paq_ped.costo_accesorio,
			paq_ped.porcentaje_iva_accesorio,
			(paq_ped.precio_accesorio + (paq_ped.precio_accesorio * paq_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_ped.iva_accesorio,
			paq_ped.monto_pagado,
			paq_ped.id_condicion_pago,
			paq_ped.id_condicion_mostrar,
			paq_ped.monto_pendiente,
			paq_ped.id_condicion_mostrar_pendiente,
			paq_ped.estatus_paquete_pedido
		FROM an_paquete_pedido paq_ped
			INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_ped.id_pedido = %s
			AND (acc.id_mostrar_predeterminado IS NULL OR acc.id_mostrar_predeterminado = 0)
		ORDER BY paq_ped.id_paquete_pedido ASC;",
			valTpDato($idPedido, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$idPedidoAdicional = ($permitirEditarFactura == true && $idFactura > 0) ? "" : $rowPedidoDet['id_paquete_pedido'];
			
			$Result1 = insertarItemAdicional($contFila, $idPedidoAdicional, "", $rowPedidoDet['id_accesorio'], $rowPedidoDet['id_acc_paq'], $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], $rowPedidoDet['id_tipo_accesorio'], (($idPedidoFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		// ADICIONALES
		$queryPedidoDet = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			acc_ped.precio_accesorio,
			acc_ped.costo_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.iva_accesorio,
			acc_ped.monto_pagado,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar,
			acc_ped.monto_pendiente,
			acc_ped.id_condicion_mostrar_pendiente,
			acc_ped.estatus_accesorio_pedido
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_pedido = %s
			AND (acc.id_mostrar_predeterminado IS NULL OR acc.id_mostrar_predeterminado = 0)
		ORDER BY acc_ped.id_accesorio_pedido ASC;",
			valTpDato($idPedido, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$idPedidoAdicional = ($permitirEditarFactura == true && $idFactura > 0) ? "" : $rowPedidoDet['id_accesorio_pedido'];
			
			$Result1 = insertarItemAdicional($contFila, $idPedidoAdicional, "", $rowPedidoDet['id_accesorio'], "", $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], $rowPedidoDet['id_tipo_accesorio'], (($idPedidoFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		// ADICIONALES PREDETERMINADOS
		$queryAdicionalPredet = sprintf("SELECT * FROM an_accesorio acc WHERE acc.id_mostrar_predeterminado IN (1);");
		$rsAdicionalPredet = mysql_query($queryAdicionalPredet);
		if (!$rsAdicionalPredet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowAdicionalPredet = mysql_fetch_assoc($rsAdicionalPredet)) {
			$queryPedidoDet = sprintf("SELECT
				acc_ped.id_accesorio_pedido,
				acc_ped.id_pedido,
				acc.id_accesorio,
				CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
				acc.des_accesorio,
				acc_ped.id_tipo_accesorio,
				(CASE acc_ped.id_tipo_accesorio
					WHEN 1 THEN 'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
					WHEN 4 THEN 'Cargo'
				END) AS descripcion_tipo_accesorio,
				acc_ped.precio_accesorio,
				acc_ped.costo_accesorio,
				acc_ped.porcentaje_iva_accesorio,
				(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
				acc_ped.iva_accesorio,
				acc_ped.monto_pagado,
				acc_ped.id_condicion_pago,
				acc_ped.id_condicion_mostrar,
				acc_ped.monto_pendiente,
				acc_ped.id_condicion_mostrar_pendiente,
				acc_ped.estatus_accesorio_pedido
			FROM an_accesorio_pedido acc_ped
				RIGHT JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
			WHERE acc_ped.id_pedido = %s
				AND acc_ped.id_accesorio = %s
			ORDER BY acc.nom_accesorio ASC;",
				valTpDato($idPedido, "int"),
				valTpDato($rowAdicionalPredet['id_accesorio'], "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			$Result1 = insertarItemAdicionalPredeterminado($contFila, $rowPedidoDet['id_accesorio_pedido'], "", $rowAdicionalPredet['id_accesorio'], "", $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], $rowPedidoDet['id_tipo_accesorio'], (($idPedidoFinanciamiento > 0) ? true : false));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		if (!($idPedidoFinanciamiento > 0)) {
			// FORMA DE PAGO
			if ($rowPedido['tipo_inicial'] == 0) {
				$objResponse->script("
				byId('rbtInicialPorc').checked = true;
				byId('rbtInicialPorc').click();");
			} else {
				$objResponse->script("
				byId('rbtInicialMonto').checked = true;
				byId('rbtInicialMonto').click();");
			}
		}
		$objResponse->assign("hddTipoInicial","value",$rowPedido['tipo_inicial']);
		$objResponse->assign("txtPorcInicial","value",number_format($rowPedido['porcentaje_inicial'], 2, "." , ","));
		$objResponse->assign("txtMontoInicial","value",number_format($rowPedido['inicial'], 2, "." , ","));
		$objResponse->assign("txtMontoCashBack","value",number_format($rowPedido['monto_cash_back'], 2, "." , ","));
		
		// CONTRATO A PAGARSE
		if ($rowPedido['porcentaje_inicial'] < 100) {
			if ($rowPedido['id_banco_financiar'] > 0) {
			} else {
				$objResponse->script("
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;");
			}
		}
		$objResponse->loadCommands(cargaLstBancoFinanciar("lstBancoFinanciar", $rowPedido['id_banco_financiar']));
		$lstMesesFinanciar = $rowPedido['meses_financiar'];
		$txtInteresCuotaFinanciar = $rowPedido['interes_cuota_financiar'];
		$txtCuotasFinanciar = numformat($rowPedido['cuotas_financiar'],2);
		$lstMesesFinanciar2 = $rowPedido['meses_financiar2'];
		$txtInteresCuotaFinanciar2 = $rowPedido['interes_cuota_financiar2'];
		$txtCuotasFinanciar2 = numformat($rowPedido['cuotas_financiar2'],2);
		$lstMesesFinanciar3 = $rowPedido['meses_financiar3'];
		$txtInteresCuotaFinanciar3 = $rowPedido['interes_cuota_financiar3'];
		$txtCuotasFinanciar3 = numformat($rowPedido['cuotas_financiar3'],3);
		$lstMesesFinanciar4 = $rowPedido['meses_financiar4'];
		$txtInteresCuotaFinanciar4 = $rowPedido['interes_cuota_financiar4'];
		$txtCuotasFinanciar4 = numformat($rowPedido['cuotas_financiar4'],4);
		$valores = array(
			"lstMesesFinanciar*".$rowPedido['meses_financiar'],
			"txtInteresCuotaFinanciar*".$rowPedido['interes_cuota_financiar'],
			"txtCuotasFinanciar*".$rowPedido['cuotas_financiar'],
			"txtFechaCuotaFinanciar*".$rowPedido['fecha_pago_cuota'],
			"lstMesesFinanciar2*".$rowPedido['meses_financiar2'],
			"txtInteresCuotaFinanciar2*".$rowPedido['interes_cuota_financiar2'],
			"txtCuotasFinanciar2*".$rowPedido['cuotas_financiar2'],
			"txtFechaCuotaFinanciar2*".$rowPedido['fecha_pago_cuota2'],
			"lstMesesFinanciar3*".$rowPedido['meses_financiar3'],
			"txtInteresCuotaFinanciar3*".$rowPedido['interes_cuota_financiar3'],
			"txtCuotasFinanciar3*".$rowPedido['cuotas_financiar3'],
			"txtFechaCuotaFinanciar3*".$rowPedido['fecha_pago_cuota3'],
			"lstMesesFinanciar4*".$rowPedido['meses_financiar4'],
			"txtInteresCuotaFinanciar4*".$rowPedido['interes_cuota_financiar4'],
			"txtCuotasFinanciar4*".$rowPedido['cuotas_financiar4'],
			"txtFechaCuotaFinanciar4*".$rowPedido['fecha_pago_cuota4']);
		$objResponse->loadCommands(asignarBanco($rowPedido['id_banco_financiar'], $valores));
		$objResponse->loadCommands(asignarFactor($rowPedido['meses_financiar'], array("lstBancoFinanciar" => $rowPedido['id_banco_financiar'])));
		$objResponse->assign("txtPorcFLAT","value",number_format($rowPedido['porcentaje_flat'], 2, "." , ","));
		$objResponse->assign("txtMontoFLAT","value",number_format($rowPedido['monto_flat'], 2, "." , ","));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza("lstPoliza", $rowPedido['id_poliza']));
		$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPedido['nombre_agencia_seguro']);
		$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPedido['direccion_agencia_seguro']);
		$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPedido['ciudad_agencia_seguro']);
		$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPedido['pais_agencia_seguro']);
		$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPedido['telefono_agencia_seguro']);
		$objResponse->assign("txtNumPoliza","value",$rowPedido['num_poliza']);
		$objResponse->assign("txtMontoSeguro","value",number_format($rowPedido['monto_seguro'], 2, "." , ","));
		$objResponse->assign("txtPeriodoPoliza","value",$rowPedido['periodo_poliza']);
		$objResponse->assign("txtDeduciblePoliza","value",$rowPedido['ded_poliza']);
		$objResponse->assign("txtFechaEfect","value",(($rowPedido['fech_efect'] != "") ? date(spanDateFormat, strtotime($rowPedido['fech_efect'])) : ""));
		$objResponse->assign("txtFechaExpi","value",(($rowPedido['fech_expira'] != "") ? date(spanDateFormat, strtotime($rowPedido['fech_expira'])) : ""));
		$objResponse->assign("txtInicialPoliza","value",$rowPedido['inicial_poliza']);
		$objResponse->assign("txtMesesPoliza","value",$rowPedido['meses_poliza']);
		$objResponse->assign("txtCuotasPoliza","value",$rowPedido['cuotas_poliza']);
		
		// OTROS
		$objResponse->assign("txtMontoAnticipo","value",number_format($rowPedido['anticipo'], 2, "." , ","));
		$objResponse->assign("txtMontoComplementoInicial","value",number_format($rowPedido['complemento_inicial'], 2, "." , ","));
		
		// OBSERVACIONES
		$objResponse->assign("txtObservacion","innerHTML",$rowPedido['observaciones']);
		
		// COMPROBACION
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300"), $rowPedido['gerente_ventas']));
		$objResponse->assign("txtFechaVenta","value",date(spanDateFormat, strtotime($rowPedido['fecha_gerente_ventas'])));
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteAdministracion", "3", $rowPedido['administracion']));
		$objResponse->assign("txtFechaAdministracion","value",date(spanDateFormat, strtotime($rowPedido['fecha_administracion'])));
		
		$objResponse->assign("txtFechaReserva","value",date(spanDateFormat, strtotime($rowPedido['fecha_reserva_venta'])));
		$objResponse->assign("txtFechaEntrega","value",date(spanDateFormat, strtotime($rowPedido['fecha_entrega'])));
		
		
		// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
		$queryPresupuestoAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio pres_acc WHERE pres_acc.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoAccesorio = mysql_query($queryPresupuestoAccesorio);
		if (!$rsPresupuestoAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPresupuestoAccesorio = mysql_num_rows($rsPresupuestoAccesorio);
		$rowPresupuestoAccesorio = mysql_fetch_assoc($rsPresupuestoAccesorio);
		
		// PRESUPUESTO ACCESORIOS
		if ($totalRowsPresupuestoAccesorio > 0) {
			$objResponse->script("
			byId('fielsetPresupuestoAccesorios').style.display = '';");
			
			$objResponse->assign("hddIdPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtNumeroPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtSubTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'], 2, ".", ","));
			$objResponse->assign("txtTotalImpuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->assign("txtTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'] + $rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->script(sprintf("byId('aEditarPresupuestoAcc').href = 'an_combo_presupuesto_list.php?view=1&id=%s';",
				$idPresupuesto));
			$objResponse->script(sprintf("byId('aPresupuestoAccPDF').href = 'javascript:verVentana(\'reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s\', 960, 550);';",
				$rowPresupuestoAccesorio['id_presupuesto_accesorio']));
		}
		
		$objResponse->script("cerrarVentana = false;");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtFechaReserva').className = 'inputHabilitado';
		byId('txtFechaEntrega').className = 'inputHabilitado';
		byId('txtFechaVenta').className = 'inputHabilitado';
		byId('txtFechaAdministracion').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(39, "int"), // 39 = Pedido Venta Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNumeracion = mysql_num_rows($rsNumeracion);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		if (!($totalRowsNumeracion > 0)) {
			return $objResponse->script("
			alert('No puede realizar pedido de venta por esta empresa');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_pedido_venta_list.php'; }");
		}
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado("Empleado", $_SESSION['idEmpleadoSysGts'], "", "", "false"));
		
		if ($hddTipoPedido == "i") {
			$objResponse->script("
			byId('trBuscarPresupuesto').style.display = '';
			byId('trFieldsetCliente').style.display = 'none';
			byId('trFieldsetUnidadFisica').style.display = 'none';
			byId('trFieldsetVehiculoUsado').style.display = 'none';
			byId('trBtnGuardar').style.display = 'none';
			
			byId('txtBuscarPresupuesto').className = 'inputHabilitado';
			byId('txtBuscarPresupuesto').focus();");
		} else {
			$objResponse->assign("rbtTipoPagoContado","checked","checked");
			$objResponse->script("
			byId('rbtTipoPagoCredito').disabled = true;
			
			byId('rbtInicialPorc').checked = true;
			byId('rbtInicialPorc').click();
			
			selectedOption('lstTipoClave',3);
			
			byId('lstTipoClave').onchange = function () {
				selectedOption(this.id,3);
				xajax_cargaLstClaveMovimiento('lstClaveMovimiento','2','3','1','1','-1','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
			}");
			
			// DATOS PEDIDO
			$objResponse->assign("txtFechaPedido","value",date(spanDateFormat));
			$objResponse->loadCommands(cargaLstMoneda($idMoneda));
			$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
			$objResponse->assign("hddIdMoneda","value",$idMoneda);
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", "3", "", "1"));
			
			// ADICIONALES PREDETERMINADOS
			$queryAdicionalPredet = sprintf("SELECT * FROM an_accesorio acc WHERE acc.id_mostrar_predeterminado IN (1);");
			$rsAdicionalPredet = mysql_query($queryAdicionalPredet);
			if (!$rsAdicionalPredet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayObj1 = NULL;
			while ($rowAdicionalPredet = mysql_fetch_assoc($rsAdicionalPredet)) {
				$Result1 = insertarItemAdicionalPredeterminado($contFila, "", "", $rowAdicionalPredet['id_accesorio']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj1[] = $contFila;
				}
			}
			
			// CONTRATO A PAGARSE
			$objResponse->loadCommands(cargaLstBancoFinanciar("lstBancoFinanciar"));
			
			// SEGURO
			$objResponse->loadCommands(cargaLstPoliza("lstPoliza"));
			
			// COMPROBACION
			$objResponse->loadCommands(cargaLstEmpleado("lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300")));
			$objResponse->loadCommands(cargaLstEmpleado("lstGerenteAdministracion", "3"));
		}
	}
	
	$objResponse->script("
	jQuery(function($){
		$(\"#txtFechaReserva\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaEntrega\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaEfect\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaExpi\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaVenta\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaAdministracion\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaReserva\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaEntrega\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaEfect\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaExpi\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaVenta\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaAdministracion\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});");
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), '".(($totalRowsFinanciamiento > 0) ? "true" : "false")."');");
	
	return $objResponse;
}

function eliminarAdicionalLote($frmDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmDcto['cbxItmAdicional'])) {
		foreach ($frmDcto['cbxItmAdicional'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmAdicional_".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function eliminarArticuloLote($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
	if (isset($frmListaArticulo['cbxItmArticulo'])) {
		foreach ($frmListaArticulo['cbxItmArticulo'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmArticulo_".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
			
			mysql_query("START TRANSACTION;");
			
			$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valorItm];
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valorItm];
			$cantPendiente = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorItm]);
			
			$deleteSQL = sprintf("DELETE FROM an_pedido_venta_detalle WHERE id_pedido_venta_detalle = %s",
				valTpDato($frmListaArticulo['hddIdPedidoDet'.$valorItm], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
			$Result1 = actualizacionEsperaPorFacturar($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// SE CONECTA CON EL SISTEMA DE SOLICITUDES
			$Result1 = actualizarCantidadSistemaSolicitud($frmDcto['txtNumeroReferencia'], $idArticulo, 0);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			mysql_query("COMMIT;");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
		
	return $objResponse;
}

function formCliente($idCliente, $frmCliente) {}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModuloPermiso","value",$hddModulo);
	
	return $objResponse;
}

function formValidarPermisoEdicion2($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso2","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModuloPermiso2","value",$hddModulo);
	
	return $objResponse;
}

function guardarCliente($frmCliente, $frmListaCliente) {}

function guardarDcto($frmDcto, $bloquearForm = "false", $txtIdPedido = "") {
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	sort($frmDcto['cbxPieAdicional']);
	$arrayObjPieAdicional = $frmDcto['cbxPieAdicional'];
	
	$idPedido = (intval($txtIdPedido) > 0) ? intval($txtIdPedido) : $frmDcto['txtIdPedido'];
	$idPresupuesto = $frmDcto['hddIdPresupuestoVenta'];
	$idFactura = $frmDcto['txtIdFactura'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	$idUnidadFisica = $frmDcto['txtIdUnidadFisica'];
	$idUnidadFisicaAnterior = $frmDcto['hddIdUnidadFisicaAnterior'];
	
	// VERIFICA QUE EL CLIENTE DEL PEDIDO ESTE CREADO COMO CLIENTE (1 = Prospecto, 2 = Cliente)
	$tipoCuentaCliente = getmysql(sprintf("SELECT tipo_cuenta_cliente FROM cj_cc_cliente WHERE id = %s;", valTpDato($idCliente, "int")));
	$estadoPedido = ($tipoCuentaCliente == 1 || !($idUnidadFisica > 0)) ? 3 : 1; // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	
	$txtFechaTasaCambio = ($frmDcto['txtFechaTasaCambio'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaTasaCambio'])) : "";
	
	$txtFechaRetoma = ($frmDcto['txtFechaRetoma'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaRetoma'])) : "";
	
	$txtFechaCuotaFinanciar = ($frmDcto['txtFechaCuotaFinanciar'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar'])) : "";
	$txtFechaCuotaFinanciar2 = ($frmDcto['txtFechaCuotaFinanciar2'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar2'])) : "";
	$txtFechaCuotaFinanciar3 = ($frmDcto['txtFechaCuotaFinanciar3'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar3'])) : "";
	$txtFechaCuotaFinanciar4 = ($frmDcto['txtFechaCuotaFinanciar4'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar4'])) : "";
	
	$txtFechaEfect = ($frmDcto['txtFechaEfect'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaEfect'])) : "";
	$txtFechaExpi =  ($frmDcto['txtFechaExpi'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaExpi'])) : "";
	
	mysql_query("START TRANSACTION;");
	
	foreach($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
		$objResponse->script("byId('txtPrecioPagadoItmArticulo".$valorPieArticulo."').className = 'inputHabilitado'");
		
		if (str_replace(",", "", $frmListaArticulo['txtPrecioPagadoItmArticulo'.$valorPieArticulo]) > str_replace(",", "", $frmListaArticulo['txtTotalConImpuestoItm'.$valorPieArticulo])) { $arrayCantidadInvalida[] = "txtPrecioPagadoItmArticulo".$valorPieArticulo; }
	}
	
	foreach($arrayObjPieAdicional as $indicePieAdicional => $valorPieAdicional) {
		$objResponse->script("byId('txtPrecioConIvaItm".$valorPieAdicional."').className = 'inputCompletoHabilitado'");
		$objResponse->script("byId('txtPrecioPagadoItm".$valorPieAdicional."').className = 'inputHabilitado'");
		
		if (!(str_replace(",", "", $frmListaArticulo['txtPrecioConIvaItm'.$valorPieAdicional]) > 0) && $frmListaArticulo['hddMostrarPredeterminadoItm'.$valorPieAdicional] == 0) { $arrayCantidadInvalida[] = "txtPrecioConIvaItm".$valorPieAdicional; }
		if (str_replace(",", "", $frmListaArticulo['txtPrecioPagadoItm'.$valorPieAdicional]) > str_replace(",", "", $frmListaArticulo['txtPrecioConIvaItm'.$valorPieAdicional])) { $arrayCantidadInvalida[] = "txtPrecioPagadoItm".$valorPieAdicional; }
	}
	
	// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indiceCantidadInvalida => $valorCantidadInvalida) {
				$objResponse->script("
				if (inArray(byId('".$valorCantidadInvalida."').className, ['inputHabilitado'])) {
					byId('".$valorCantidadInvalida."').className = 'inputErrado';
				} else if (inArray(byId('".$valorCantidadInvalida."').className, ['inputCompletoHabilitado'])) {
					byId('".$valorCantidadInvalida."').className = 'inputCompletoErrado';
				}");
			}
		}
		
		return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
	}
	
	if ($idFactura > 0) {
		if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
		
		$insertSQL = "INSERT INTO an_pedido (numeracion_pedido, id_empresa, id_cliente, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, fecha_tasa_cambio, id_clave_movimiento, id_presupuesto, id_factura_cxc, id_unidad_fisica, fecha, estado_pedido, asesor_ventas, gerente_ventas, fecha_gerente_ventas, administracion, fecha_administracion, precio_retoma, fecha_retoma, id_uni_bas_retoma, id_color_retorma, placa_retoma, certificado_origen_retoma, precio_venta, monto_descuento, porcentaje_iva, porcentaje_impuesto_lujo, tipo_inicial, porcentaje_inicial, inicial, monto_cash_back, id_banco_financiar, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, fecha_pago_cuota, meses_financiar2, interes_cuota_financiar2, cuotas_financiar2, fecha_pago_cuota2, meses_financiar3, interes_cuota_financiar3, cuotas_financiar3, fecha_pago_cuota3, meses_financiar4, interes_cuota_financiar4, cuotas_financiar4, fecha_pago_cuota4, total_accesorio, total_adicional_contrato, total_inicial_gastos, porcentaje_flat, monto_flat, observaciones, anticipo, complemento_inicial, id_poliza, num_poliza, monto_seguro, periodo_poliza, ded_poliza, fech_efect, fech_expira, inicial_poliza, meses_poliza, cuotas_poliza, fecha_reserva_venta, fecha_entrega, forma_pago_precio_total, total_pedido, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4, empresa_accesorio)
		SELECT
			CONCAT(SUBSTRING_INDEX(numeracion_pedido, '(', 1), '(', ((SELECT COUNT(an_ped_vent.id_factura_cxc) FROM an_pedido an_ped_vent
																	WHERE an_ped_vent.id_empresa = an_pedido.id_empresa
																		AND an_ped_vent.numeracion_pedido LIKE CONCAT(SUBSTRING_INDEX(an_pedido.numeracion_pedido, '(', 1), '%')) + 1),')'), ".					
			valTpDato($idEmpresa, "int").", ".
			valTpDato($idCliente, "int").", ".
			valTpDato($frmDcto['hddIdMoneda'], "int").", ".
			valTpDato($frmDcto['lstMoneda'], "int").", ".
			valTpDato($frmDcto['lstTasaCambio'], "int").", ".
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa").", ".
			valTpDato($txtFechaTasaCambio, "date").", ".
			valTpDato($frmDcto['lstClaveMovimiento'], "int").", ".
			valTpDato($frmDcto['hddIdPresupuestoVenta'], "int").", ".
			valTpDato($frmDcto['txtIdFactura'], "int").", ".
			valTpDato($idUnidadFisica, "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date").", ".
			valTpDato($estadoPedido, "int").", ". // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			valTpDato($frmDcto['txtIdEmpleado'], "int").", ".
			valTpDato($frmDcto['lstGerenteVenta'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVenta'])), "date").", ".
			valTpDato($frmDcto['lstGerenteAdministracion'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaAdministracion'])), "date").", ".
			valTpDato($txtPrecioRetoma, "real_inglesa").", ".
			valTpDato($txtFechaRetoma, "date").", ".
			valTpDato($txtIdUnidadBasicaRetoma, "int").", ".
			valTpDato($txtIdColorRetoma, "int").", ".
			valTpDato($txtPlacaRetoma, "text").", ".
			valTpDato($txtCertificadoOrigenRetoma, "text").", ".
			valTpDato($frmDcto['txtPrecioBase'], "real_inglesa").", ".
			valTpDato($frmDcto['txtDescuento'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIva'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIvaLujo'], "real_inglesa").", ".
			valTpDato($frmDcto['hddTipoInicial'], "int").", ".
			valTpDato($frmDcto['txtPorcInicial'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoInicial'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoCashBack'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstBancoFinanciar'], "int").", ".
			valTpDato($frmDcto['txtSaldoFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['lstMesesFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar2'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar2, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar3'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar3, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar4'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar4, "date").", ".
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtObservacion'], "text").", ".
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoComplementoInicial'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstPoliza'], "int").", ".
			valTpDato($frmDcto['txtNumPoliza'], "text").", ".
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPeriodoPoliza'], "text").", ".
			valTpDato($frmDcto['txtDeduciblePoliza'], "real_inglesa").", ".
			valTpDato($txtFechaEfect, "date").", ".
			valTpDato($txtFechaExpi, "date").", ".
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa").", ".
			
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaReserva'])), "date").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaEntrega'])), "date").", ".
			valTpDato($frmDcto['txtPrecioTotal'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalPedido'], "real_inglesa").", ".
			valTpDato($frmDcto['txtNumeroPresupuestoAcc'], "text").", ".
			valTpDato($exacc2, "text").", ".
			valTpDato($exacc3, "text").", ".
			valTpDato($exacc4, "text").", ".
			valTpDato($frmDcto['txtSubTotalPresupuestoAccesorio'], "real_inglesa").", ".
			valTpDato($vexacc2, "real_inglesa").", ".
			valTpDato($vexacc3, "real_inglesa").", ".
			valTpDato($vexacc4, "real_inglesa").", ".
			valTpDato($empresa_accesorio, "text")."
		FROM an_pedido
		WHERE id_pedido = ".valTpDato($idPedido, "int").";";
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idPedido = mysql_insert_id();
	} else if ($idPedido > 0) {
		if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","editar")) { return $objResponse; }
		
		// INSERTA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_pedido SET
			id_empresa = %s,
			id_cliente = %s,
			id_moneda = %s,
			id_moneda_tasa_cambio = %s,
			id_tasa_cambio = %s,
			monto_tasa_cambio = %s,
			fecha_tasa_cambio = %s,
			id_clave_movimiento = %s,
			id_unidad_fisica = %s,
			estado_pedido = (CASE 
								WHEN (an_pedido.estado_pedido IN (2,4,5)) THEN
									an_pedido.estado_pedido
								ELSE
									%s
							END),
			asesor_ventas = %s,
			gerente_ventas = %s,
			fecha_gerente_ventas = %s,
			administracion = %s,
			fecha_administracion = %s,
			precio_retoma = %s,
			fecha_retoma = %s,
			id_uni_bas_retoma = %s,
			id_color_retorma = %s,
			placa_retoma = %s,
			certificado_origen_retoma = %s,
			precio_venta = %s,
			monto_descuento = %s,
			porcentaje_iva = %s,
			porcentaje_impuesto_lujo = %s,
			tipo_inicial = %s,
			porcentaje_inicial = %s,
			inicial = %s,
			monto_cash_back = %s,
			
			id_banco_financiar = %s,
			saldo_financiar = %s,
			meses_financiar = %s,
			interes_cuota_financiar = %s,
			cuotas_financiar = %s,
			fecha_pago_cuota = %s,
			meses_financiar2 = %s,
			interes_cuota_financiar2 = %s,
			cuotas_financiar2 = %s,
			fecha_pago_cuota2 = %s,
			meses_financiar3 = %s,
			interes_cuota_financiar3 = %s,
			cuotas_financiar3 = %s,
			fecha_pago_cuota3 = %s,
			meses_financiar4 = %s,
			interes_cuota_financiar4 = %s,
			cuotas_financiar4 = %s,
			fecha_pago_cuota4 = %s,
			
			total_accesorio = %s,
			total_adicional_contrato = %s,
			total_inicial_gastos = %s,
			porcentaje_flat = %s,
			monto_flat = %s,
			observaciones = %s,
			anticipo = %s,
			complemento_inicial = %s,
			
			id_poliza = %s,
			num_poliza = %s,
			monto_seguro = %s,
			periodo_poliza = %s,
			ded_poliza = %s,
			fech_efect = %s,
			fech_expira = %s,
			inicial_poliza = %s,
			meses_poliza = %s,
			cuotas_poliza = %s,
			
			fecha_reserva_venta = %s,
			fecha_entrega = %s,
			forma_pago_precio_total = %s,
			total_pedido = %s,
			exacc1 = %s,
			exacc2 = %s,
			exacc3 = %s,
			exacc4 = %s,
			vexacc1 = %s,
			vexacc2 = %s,
			vexacc3 = %s,
			vexacc4 = %s,
			empresa_accesorio = %s
		WHERE id_pedido = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($frmDcto['hddIdMoneda'], "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"),
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa"),
			valTpDato($txtFechaTasaCambio, "date"),
			valTpDato($frmDcto['lstClaveMovimiento'], "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($estadoPedido, "int"), // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			valTpDato($frmDcto['txtIdEmpleado'], "int"),
			valTpDato($frmDcto['lstGerenteVenta'], "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVenta'])), "date"),
			valTpDato($frmDcto['lstGerenteAdministracion'], "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaAdministracion'])), "date"),
			valTpDato($txtPrecioRetoma, "real_inglesa"),
			valTpDato($txtFechaRetoma, "date"),
			valTpDato($txtIdUnidadBasicaRetoma, "int"),
			valTpDato($txtIdColorRetoma, "int"),
			valTpDato($txtPlacaRetoma, "text"),
			valTpDato($txtCertificadoOrigenRetoma, "text"),
			valTpDato($frmDcto['txtPrecioBase'], "real_inglesa"),
			valTpDato($frmDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmDcto['txtPorcIva'], "real_inglesa"),
			valTpDato($frmDcto['txtPorcIvaLujo'], "real_inglesa"),
			valTpDato($frmDcto['hddTipoInicial'], "int"),
			valTpDato($frmDcto['txtPorcInicial'], "real_inglesa"),
			valTpDato($frmDcto['txtMontoInicial'], "real_inglesa"),
			valTpDato($frmDcto['txtMontoCashBack'], "real_inglesa"),
			
			valTpDato($frmDcto['lstBancoFinanciar'], "int"),
			valTpDato($frmDcto['txtSaldoFinanciar'], "real_inglesa"),
			valTpDato($frmDcto['lstMesesFinanciar'], "real_inglesa"),
			valTpDato($frmDcto['txtInteresCuotaFinanciar'], "real_inglesa"),
			valTpDato($frmDcto['txtCuotasFinanciar'], "real_inglesa"),
			valTpDato($txtFechaCuotaFinanciar, "date"),
			valTpDato($frmDcto['lstMesesFinanciar2'], "real_inglesa"),
			valTpDato($frmDcto['txtInteresCuotaFinanciar2'], "real_inglesa"),
			valTpDato($frmDcto['txtCuotasFinanciar2'], "real_inglesa"),
			valTpDato($txtFechaCuotaFinanciar2, "date"),
			valTpDato($frmDcto['lstMesesFinanciar3'], "real_inglesa"),
			valTpDato($frmDcto['txtInteresCuotaFinanciar3'], "real_inglesa"),
			valTpDato($frmDcto['txtCuotasFinanciar3'], "real_inglesa"),
			valTpDato($txtFechaCuotaFinanciar3, "date"),
			valTpDato($frmDcto['lstMesesFinanciar4'], "real_inglesa"),
			valTpDato($frmDcto['txtInteresCuotaFinanciar4'], "real_inglesa"),
			valTpDato($frmDcto['txtCuotasFinanciar4'], "real_inglesa"),
			valTpDato($txtFechaCuotaFinanciar4, "date"),
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa"),
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa"),
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa"),
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa"),
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa"),
			valTpDato($frmDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa"),
			valTpDato($frmDcto['txtMontoComplementoInicial'], "real_inglesa"),
			
			valTpDato($frmDcto['lstPoliza'], "int"),
			valTpDato($frmDcto['txtNumPoliza'], "text"),
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa"),
			valTpDato($frmDcto['txtPeriodoPoliza'], "text"),
			valTpDato($frmDcto['txtDeduciblePoliza'], "real_inglesa"),
			valTpDato($txtFechaEfect, "date"),
			valTpDato($txtFechaExpi, "date"),
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa"),
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa"),
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa"),
			
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaReserva'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaEntrega'])), "date"),
			valTpDato($frmDcto['txtPrecioTotal'], "real_inglesa"),
			valTpDato($frmDcto['txtTotalPedido'], "real_inglesa"),
			valTpDato($frmDcto['txtNumeroPresupuestoAcc'], "text"),
			valTpDato($exacc2, "text"),
			valTpDato($exacc3, "text"),
			valTpDato($exacc4, "text"),
			valTpDato($frmDcto['txtSubTotalPresupuestoAccesorio'], "real_inglesa"),
			valTpDato($vexacc2, "real_inglesa"),
			valTpDato($vexacc3, "real_inglesa"),
			valTpDato($vexacc4, "real_inglesa"),
			valTpDato($empresa_accesorio, "text"),
			
			valTpDato($idPedido, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
	} else {
		if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","insertar")) { return $objResponse; }
		
		// VERIFICA QUE LA UNIDAD FISICA NO HAYA SIDO RESERVADA ANTES
		$queryUnidadReservada = sprintf("SELECT estado_venta FROM an_unidad_fisica
		WHERE id_unidad_fisica = %s
			AND estado_venta IN ('RESERVADO');",
			valTpDato($idUnidadFisica, "int"));
		$rsUnidadReservada = mysql_query($queryUnidadReservada);
		if (!$rsUnidadReservada) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsUnidadReservada = mysql_num_rows($rsUnidadReservada);
		if ($totalRowsUnidadReservada > 0) {
			return $objResponse->script("
			alert('La unidad seleccionada ya se ha reservado hace pocos instantes');
			history.go(-1);");
		}
		
		// VERIFICA QUE EL PRESUPUESTO NO HAYA SIDO GENERADO ANTERIORMENTE
		if ($idPresupuesto > 0) {
			$pedidoc = getmysql("SELECT COUNT(*) FROM an_pedido WHERE id_presupuesto = ".valTpDato($idPresupuesto, "int").";");
			if ($pedidoc > 0) {
				$objResponse->alert("El Pedido del Presupesto ".$idPresupuesto." ya fu&eacute; Generado");
				return $objResponse->script("
				window.location = 'an_pedido_venta_list.php';");
			}
		}
		
		if ($tipoCuentaCliente == 1) {
			$objResponse->alert("El prospecto perteneciente a este pedido no está aprobado como cliente. Recomendamos lo apruebe en la pantalla de Prospectación");
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(39, "int"), // 39 = Pedido Venta Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// INSERTA LOS DATOS DEL PEDIDO
		$insertSQL = "INSERT INTO an_pedido (numeracion_pedido, id_empresa, id_cliente, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, fecha_tasa_cambio, id_clave_movimiento, id_presupuesto, id_factura_cxc, id_unidad_fisica, fecha, estado_pedido, asesor_ventas, gerente_ventas, fecha_gerente_ventas, administracion, fecha_administracion, precio_retoma, fecha_retoma, id_uni_bas_retoma, id_color_retorma, placa_retoma, certificado_origen_retoma, precio_venta, monto_descuento, porcentaje_iva, porcentaje_impuesto_lujo, tipo_inicial, porcentaje_inicial, inicial, monto_cash_back, id_banco_financiar, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, fecha_pago_cuota, meses_financiar2, interes_cuota_financiar2, cuotas_financiar2, fecha_pago_cuota2, meses_financiar3, interes_cuota_financiar3, cuotas_financiar3, fecha_pago_cuota3, meses_financiar4, interes_cuota_financiar4, cuotas_financiar4, fecha_pago_cuota4, total_accesorio, total_adicional_contrato, total_inicial_gastos, porcentaje_flat, monto_flat, observaciones, anticipo, complemento_inicial, id_poliza, num_poliza, monto_seguro, periodo_poliza, ded_poliza, fech_efect, fech_expira, inicial_poliza, meses_poliza, cuotas_poliza, fecha_reserva_venta, fecha_entrega, forma_pago_precio_total, total_pedido, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4, empresa_accesorio)
		VALUES (".valTpDato($numeroActual, "text").", ".
			valTpDato($idEmpresa, "int").", ".
			valTpDato($idCliente, "int").", ".
			valTpDato($frmDcto['hddIdMoneda'], "int").", ".
			valTpDato($frmDcto['lstMoneda'], "int").", ".
			valTpDato($frmDcto['lstTasaCambio'], "int").", ".
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa").", ".
			valTpDato($txtFechaTasaCambio, "date").", ".
			valTpDato($frmDcto['lstClaveMovimiento'], "int").", ".
			valTpDato($frmDcto['hddIdPresupuestoVenta'], "int").", ".
			valTpDato($frmDcto['txtIdFactura'], "int").", ".
			valTpDato($idUnidadFisica, "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date").", ".
			valTpDato($estadoPedido, "int").", ". // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			valTpDato($frmDcto['txtIdEmpleado'], "int").", ".
			valTpDato($frmDcto['lstGerenteVenta'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVenta'])), "date").", ".
			valTpDato($frmDcto['lstGerenteAdministracion'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaAdministracion'])), "date").", ".
			valTpDato($txtPrecioRetoma, "real_inglesa").", ".
			valTpDato($txtFechaRetoma, "date").", ".
			valTpDato($txtIdUnidadBasicaRetoma, "int").", ".
			valTpDato($txtIdColorRetoma, "int").", ".
			valTpDato($txtPlacaRetoma, "text").", ".
			valTpDato($txtCertificadoOrigenRetoma, "text").", ".
			valTpDato($frmDcto['txtPrecioBase'], "real_inglesa").", ".
			valTpDato($frmDcto['txtDescuento'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIva'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIvaLujo'], "real_inglesa").", ".
			valTpDato($frmDcto['hddTipoInicial'], "int").", ".
			valTpDato($frmDcto['txtPorcInicial'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoInicial'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoCashBack'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstBancoFinanciar'], "int").", ".
			valTpDato($frmDcto['txtSaldoFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['lstMesesFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar2'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar2, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar3'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar3, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar4'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar4, "date").", ".
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtObservacion'], "text").", ".
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoComplementoInicial'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstPoliza'], "int").", ".
			valTpDato($frmDcto['txtNumPoliza'], "text").", ".
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPeriodoPoliza'], "text").", ".
			valTpDato($frmDcto['txtDeduciblePoliza'], "real_inglesa").", ".
			valTpDato($txtFechaEfect, "date").", ".
			valTpDato($txtFechaExpi, "date").", ".
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa").", ".
			
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaReserva'])), "date").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaEntrega'])), "date").", ".
			valTpDato($frmDcto['txtPrecioTotal'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalPedido'], "real_inglesa").", ".
			valTpDato($frmDcto['txtNumeroPresupuestoAcc'], "text").", ".
			valTpDato($exacc2, "text").", ".
			valTpDato($exacc3, "text").", ".
			valTpDato($exacc4, "text").", ".
			valTpDato($frmDcto['txtSubTotalPresupuestoAccesorio'], "real_inglesa").", ".
			valTpDato($vexacc2, "real_inglesa").", ".
			valTpDato($vexacc3, "real_inglesa").", ".
			valTpDato($vexacc4, "real_inglesa").", ".
			valTpDato($empresa_accesorio, "text").");";
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idPedido = mysql_insert_id();
	}
	
	// VERIFICA SI TIENE CONTRATO DE FINANCIAMIENTO
	$queryContrato = sprintf("SELECT * FROM an_adicionales_contrato adicional_contrato WHERE adicional_contrato.id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$rsContrato = mysql_query($queryContrato);
	if (!$rsContrato) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsContrato = mysql_num_rows($rsContrato);
	$rowContrato = mysql_fetch_assoc($rsContrato);
	
	if ($idPoliza > 0) {
		// INSERTA LOS DATOS DEL CONTRATO
		if ($totalRowsContrato > 0) {
			$idContrato = $rowContrato['id_adi_contrato'];
			
			$updateSQL = "UPDATE an_adicionales_contrato SET
				nombre_agencia_seguro = ".valTpDato($frmDcto['txtNombreAgenciaSeguro'], "text").",
				direccion_agencia_seguro = ".valTpDato($frmDcto['txtDireccionAgenciaSeguro'], "text").",
				ciudad_agencia_seguro = ".valTpDato($frmDcto['txtCiudadAgenciaSeguro'], "text").",
				pais_agencia_seguro = ".valTpDato($frmDcto['txtPaisAgenciaSeguro'], "text").",
				telefono_agencia_seguro = ".valTpDato($frmDcto['txtTelefonoAgenciaSeguro'], "text")."
			WHERE id_pedido = ".valTpDato($idPedido, "int").";";
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else if (strlen($txtNombreAgenciaSeguro) > 0) {
			$insertSQL = "INSERT INTO an_adicionales_contrato (id_pedido, nombre_agencia_seguro, direccion_agencia_seguro, ciudad_agencia_seguro, pais_agencia_seguro, telefono_agencia_seguro)
			VALUES (".valTpDato($idPedido, "int").", ".
				valTpDato($frmDcto['txtNombreAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtDireccionAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtCiudadAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtPaisAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtTelefonoAgenciaSeguro'], "text").");";
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idContrato = mysql_insert_id();
		}
	}
	
	// INSERTA EL DETALLE DEL PRESUPUESTO
	if (isset($arrayObjPieArticulo)) {
		$frmListaArticulo = $frmDcto;
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$idPedidoDetalle = $frmListaArticulo['hddIdPedidoDet'.$valorPieArticulo];
			$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valorPieArticulo];
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valorPieArticulo];
			$cantPedida = str_replace(",","",$frmListaArticulo['txtCantItm'.$valorPieArticulo]);
			$cantPendiente = str_replace(",","",$frmListaArticulo['txtCantItm'.$valorPieArticulo]);
			$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valorPieArticulo]);
			$precioSugerido = str_replace(",", "", $frmListaArticulo['hddPrecioSugeridoItm'.$valorPieArticulo]);
			$gastoUnitario = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$valorPieArticulo]) / $cantPendiente;
			$txtTotalItm = str_replace(",","",$frmListaArticulo['txtTotalItm'.$valorPieArticulo]);
			$txtPrecioPagadoItm = ((str_replace(",","",$frmListaArticulo['txtPrecioPagadoItmArticulo'.$valorPieArticulo]) > 0) ? str_replace(",","",$frmListaArticulo['txtPrecioPagadoItmArticulo'.$valorPieArticulo]) : 0);
			$cbxCondicion = $frmListaArticulo['cbxCondicionItmArticulo'.$valorPieArticulo];
			$lstMostrarItm = $frmListaArticulo['lstMostrarItmArticulo'.$valorPieArticulo];
			$txtMontoPendiente = $txtTotalItm - $txtPrecioPagadoItm;
			$lstMostrarPendienteItm = $frmListaArticulo['lstMostrarPendienteItmArticulo'.$valorPieArticulo];
			$hddIdIvaItm = "";
			$hddIvaItm = 0;
			if (isset($arrayObjIvaItm)) { // RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					if ($valorIvaItm[0] == $valorPieArticulo && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valorIvaItm[1]];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valorPieArticulo.':'.$valorIvaItm[1]];
					}
				}
			}
			
			if ($cantPedida > 0) {
				if ($idPedidoDetalle > 0) {
					// BUSCA LA CASILLA ANTERIOR
					$queryPedidoDet = sprintf("SELECT * FROM an_pedido_venta_detalle WHERE id_pedido_venta_detalle = %s;",
						valTpDato($idPedidoDetalle, "int"));
					$rsPedidoDet = mysql_query($queryPedidoDet);
					if (!$rsPedidoDet) { errorPedidoVenta($arrayObjPieArticulo, $objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
					
					// ACTUALIZA QUIEN MODIFICO EL PRECIO EN CASO DE QUE EL CAMPO SEA NULO
					$updateSQL = sprintf("UPDATE an_pedido_venta_detalle SET
						id_empleado_creador = %s
					WHERE id_pedido_venta_detalle = %s
						AND (precio_unitario <> %s
							OR id_empleado_creador IS NULL);",
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($idPedidoDetalle, "int"),
						valTpDato($precioUnitario, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorPedidoVenta($arrayObjPieArticulo, $objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					$updateSQL = sprintf("UPDATE an_pedido_venta_detalle SET
						id_casilla = %s,
						cantidad = %s,
						pendiente = %s,
						id_precio = %s,
						precio_unitario = %s,
						precio_sugerido = %s,
						id_iva = %s,
						iva = %s,
						monto_pagado = %s,
						id_condicion_pago = %s,
						id_condicion_mostrar = %s,
						monto_pendiente = %s,
						id_condicion_mostrar_pendiente = %s
					WHERE id_pedido_venta_detalle = %s;",
						valTpDato($idCasilla, "int"),
						valTpDato($cantPedida, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdPrecioItm'.$valorPieArticulo], "int"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($precioSugerido, "real_inglesa"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($txtPrecioPagadoItm, "real_inglesa"),
						valTpDato($cbxCondicion, "int"),
						valTpDato($lstMostrarItm, "int"),
						valTpDato($txtMontoPendiente, "real_inglesa"),
						valTpDato($lstMostrarPendienteItm, "int"),
						valTpDato($idPedidoDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
						
					$arrayIdArticuloPedido[] = $idPedidoDetalle;
				} else {
					$insertSQL = sprintf("INSERT INTO an_pedido_venta_detalle (id_pedido_venta, id_articulo, id_casilla, cantidad, pendiente, id_precio, precio_unitario, precio_sugerido, id_iva, iva, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idPedido, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($cantPedida, "real_inglesa"),
						valTpDato($cantPendiente, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdPrecioItm'.$valorPieArticulo], "int"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($precioSugerido, "real_inglesa"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($txtPrecioPagadoItm, "real_inglesa"),
						valTpDato($cbxCondicion, "int"),
						valTpDato($lstMostrarItm, "int"),
						valTpDato($txtMontoPendiente, "real_inglesa"),
						valTpDato($lstMostrarPendienteItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idPedidoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					$arrayIdArticuloPedido[] = $idPedidoDetalle;
					
					$objResponse->assign("hddIdPedidoDet".$valorPieArticulo,"value",$idPedidoDetalle);
				}
				
				// ELIMINA LOS IMPUESTOS DEL DETALLE DEL PEDIDO
				$deleteSQL = sprintf("DELETE FROM an_pedido_venta_detalle_impuesto WHERE id_pedido_venta_detalle = %s;",
					valTpDato($idPedidoDetalle, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { errorPedidoVenta($arrayObjPieArticulo, $objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
				// ELIMINA LOS GASTOS DEL DETALLE DEL PRESUPUESTO
				$deleteSQL = sprintf("DELETE FROM an_pedido_venta_detalle_gastos WHERE id_pedido_venta_detalle = %s",
					valTpDato($idPedidoDetalle, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
				$Result1 = actualizacionEsperaPorFacturar($idArticulo, $idCasilla, $rowPedidoDet['id_casilla']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					errorPedidoVenta($arrayObjPieArticulo, $objResponse);
					return $objResponse->alert($Result1[1]);
				}
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla, $rowPedidoDet['id_casilla']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					errorPedidoVenta($arrayObjPieArticulo, $objResponse);
					return $objResponse->alert($Result1[1]);
				}
				
				// SE CONECTA CON EL SISTEMA DE SOLICITUDES
				$Result1 = actualizarCantidadSistemaSolicitud($frmDcto['txtNumeroReferencia'], $idArticulo, $cantPendiente, $precioUnitario, $gastoUnitario, $hddIvaItm);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					errorPedidoVenta($arrayObjPieArticulo, $objResponse); 
					return $objResponse->alert($Result1[1]);
				}
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
						$valorIvaItm = explode(":", $valorIvaItm);
						if ($valorIvaItm[0] == $valorPieArticulo && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieArticulo.':'.$valorIvaItm[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valorPieArticulo.':'.$valorIvaItm[1]];
							
							$insertSQL = sprintf("INSERT INTO an_pedido_venta_detalle_impuesto (id_pedido_venta_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idPedidoDetalle, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { errorPedidoVenta($arrayObjPieArticulo, $objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
						}
					}
				}
				
				// ACTUALIZA LOS GASTOS DEL DETALLE DEL PEDIDO
				for ($contFilaObj = 1; $contFilaObj <= 20; $contFilaObj++) {
					if (round($frmListaArticulo['txtMontoGastoArt:'.$valorPieArticulo.':'.$contFilaObj],2) > 0) {
						$insertSQL = sprintf("INSERT INTO an_pedido_venta_detalle_gastos (id_pedido_venta_detalle, id_gasto, monto_gasto)
						VALUE (%s, %s, %s);",
							valTpDato($idPedidoDetalle, "int"),
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
	if ($idPedido > 0 || is_array($arrayIdArticuloPedido)) {
		$deleteSQL = sprintf("DELETE FROM an_pedido_venta_detalle
		WHERE id_pedido_venta = %s
			AND (id_pedido_venta_detalle NOT IN (%s) OR %s = '-1');",
			valTpDato($idPedido, "int"),
			valTpDato(implode(",",$arrayIdArticuloPedido), "campo"),
			valTpDato(implode(",",$arrayIdArticuloPedido), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$deleteSQL);
	}
	
	// GUARDA LOS ADICIONALES
	if (isset($arrayObjPieAdicional)) {
		foreach ($arrayObjPieAdicional as $indicePieAdicional => $valorPieAdicional) {
			$hddIdDetItm = $frmDcto['hddIdDetItm'.$valorPieAdicional];
			$lstTipoAdicionalItm = $frmDcto['lstTipoAdicionalItm'.$valorPieAdicional];
			$hddIdAdicionalItm = $frmDcto['hddIdAdicionalItm'.$valorPieAdicional];
			$hddIdAdicionalPaqueteItm = $frmDcto['hddIdAdicionalPaqueteItm'.$valorPieAdicional];
			$txtPrecioConIvaItm = str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valorPieAdicional]);
			$hddCostoUnitarioItm = str_replace(",","",$frmDcto['hddCostoUnitarioItm'.$valorPieAdicional]);
			$hddPorcIvaItm = str_replace(",","",$frmDcto['hddPorcIvaItm'.$valorPieAdicional]);
			$txtPrecioPagadoItm = ((str_replace(",","",$frmDcto['txtPrecioPagadoItm'.$valorPieAdicional]) > 0) ? str_replace(",","",$frmDcto['txtPrecioPagadoItm'.$valorPieAdicional]) : 0);
			$hddAplicaIvaItm = $frmDcto['hddAplicaIvaItm'.$valorPieAdicional];
			$cbxCondicion = $frmDcto['cbxCondicionItm'.$valorPieAdicional];
			$lstMostrarItm = $frmDcto['lstMostrarItm'.$valorPieAdicional];
			$txtMontoPendiente = (in_array($lstTipoAdicionalItm,array(1,4))) ? ($txtPrecioConIvaItm - $txtPrecioPagadoItm) : 0; // 1 = Adicional, 4 = Cargo
			$lstMostrarPendienteItm = $frmDcto['lstMostrarPendienteItm'.$valorPieAdicional];
			
			$txtPrecioUnitarioItm = $txtPrecioConIvaItm - (($hddPorcIvaItm != 0) ? ($txtPrecioConIvaItm * $hddPorcIvaItm / (100 + $hddPorcIvaItm)) : 0);
			
			if ($txtPrecioUnitarioItm > 0) {
				if ($hddIdAdicionalPaqueteItm > 0) {
					if ($hddIdDetItm > 0) {
						$updateSQL = sprintf("UPDATE an_paquete_pedido SET
							id_tipo_accesorio = %s,
							precio_accesorio = %s,
							costo_accesorio = %s,
							porcentaje_iva_accesorio = %s,
							iva_accesorio = %s,
							monto_pagado = %s,
							id_condicion_pago = %s,
							id_condicion_mostrar = %s,
							monto_pendiente = %s,
							id_condicion_mostrar_pendiente = %s
						WHERE id_paquete_pedido = %s;", 
							valTpDato($lstTipoAdicionalItm, "int"),
							valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
							valTpDato($hddCostoUnitarioItm, "real_inglesa"),
							valTpDato($hddPorcIvaItm, "real_inglesa"),
							valTpDato($hddAplicaIvaItm, "int"),
							valTpDato($txtPrecioPagadoItm, "real_inglesa"),
							valTpDato($cbxCondicion, "int"),
							valTpDato($lstMostrarItm, "int"),
							valTpDato($txtMontoPendiente, "real_inglesa"),
							valTpDato($lstMostrarPendienteItm, "int"),
							valTpDato($hddIdDetItm, "int"));		
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						$arrayIdPaquetePedido[] = $hddIdDetItm;
					} else {
						$insertSQL = sprintf("INSERT INTO an_paquete_pedido (id_pedido, id_acc_paq, id_tipo_accesorio, precio_accesorio, costo_accesorio, porcentaje_iva_accesorio, iva_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idPedido, "int"),
							valTpDato($hddIdAdicionalPaqueteItm, "int"),
							valTpDato($lstTipoAdicionalItm, "int"),
							valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
							valTpDato($hddCostoUnitarioItm, "real_inglesa"),
							valTpDato($hddPorcIvaItm, "real_inglesa"),
							valTpDato($hddAplicaIvaItm, "int"),
							valTpDato($txtPrecioPagadoItm, "real_inglesa"),
							valTpDato($cbxCondicion, "int"),
							valTpDato($lstMostrarItm, "int"),
							valTpDato($txtMontoPendiente, "real_inglesa"),
							valTpDato($lstMostrarPendienteItm, "int"));		
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$hddIdDetItm = mysql_insert_id();
						
						$arrayIdPaquetePedido[] = $hddIdDetItm;
					}
				} else {
					if ($hddIdDetItm > 0) {
						$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
							id_tipo_accesorio = %s,
							precio_accesorio = %s,
							costo_accesorio = %s,
							porcentaje_iva_accesorio = %s,
							iva_accesorio = %s,
							monto_pagado = %s,
							id_condicion_pago = %s,
							id_condicion_mostrar = %s,
							monto_pendiente = %s,
							id_condicion_mostrar_pendiente = %s
						WHERE id_accesorio_pedido = %s;",
							valTpDato($lstTipoAdicionalItm, "int"),
							valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
							valTpDato($hddCostoUnitarioItm, "real_inglesa"),
							valTpDato($hddPorcIvaItm, "real_inglesa"),
							valTpDato($hddAplicaIvaItm, "int"),
							valTpDato($txtPrecioPagadoItm, "real_inglesa"),
							valTpDato($cbxCondicion, "int"),
							valTpDato($lstMostrarItm, "int"),
							valTpDato($txtMontoPendiente, "real_inglesa"),
							valTpDato($lstMostrarPendienteItm, "int"),
							valTpDato($hddIdDetItm, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						$arrayIdAdicionalPedido[] = $hddIdDetItm;
					} else {
						$insertSQL = sprintf("INSERT INTO an_accesorio_pedido (id_pedido, id_accesorio, id_tipo_accesorio, precio_accesorio, costo_accesorio, porcentaje_iva_accesorio, iva_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idPedido, "int"),
							valTpDato($hddIdAdicionalItm, "int"),
							valTpDato($lstTipoAdicionalItm, "int"),
							valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
							valTpDato($hddCostoUnitarioItm, "real_inglesa"),
							valTpDato($hddPorcIvaItm, "real_inglesa"),
							valTpDato($hddAplicaIvaItm, "int"),
							valTpDato($txtPrecioPagadoItm, "real_inglesa"),
							valTpDato($cbxCondicion, "int"),
							valTpDato($lstMostrarItm, "int"),
							valTpDato($txtMontoPendiente, "real_inglesa"),
							valTpDato($lstMostrarPendienteItm, "int"));	
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$hddIdDetItm = mysql_insert_id();
						
						$arrayIdAdicionalPedido[] = $hddIdDetItm;
					}
				}
			}
		}
	}
	if ($idPedido > 0 || is_array($arrayIdAdicionalPedido)) {
		$deleteSQL = sprintf("DELETE FROM an_accesorio_pedido
		WHERE id_pedido = %s
			AND (id_accesorio_pedido NOT IN (%s) OR %s = '-1');",
			valTpDato($idPedido, "int"),
			valTpDato(implode(",",$arrayIdAdicionalPedido), "campo"),
			valTpDato(implode(",",$arrayIdAdicionalPedido), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$deleteSQL);
	}
	if ($idPedido > 0 || is_array($arrayIdPaquetePedido)) {
		$deleteSQL = sprintf("DELETE FROM an_paquete_pedido
		WHERE id_pedido = %s
			AND (id_paquete_pedido NOT IN (%s) OR %s = '-1');",
			valTpDato($idPedido, "int"),
			valTpDato(implode(",",$arrayIdPaquetePedido), "campo"),
			valTpDato(implode(",",$arrayIdPaquetePedido), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$deleteSQL);
	}
	
	if ($idUnidadFisica > 0 || $idUnidadFisicaAnterior > 0) {
		// SI LA UNIDAD FISICA ES DISTINTA, LIBERA LA ANTERIOR
		if ($idUnidadFisica != $idUnidadFisicaAnterior) {
			// LIBERA LA UNIDAD FISICA
			$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
				kilometraje = %s,
				placa = %s,
				estado_venta = (CASE uni_fis.estado_compra
									WHEN 'COMPRADO' THEN 'POR REGISTRAR'
									WHEN 'REGISTRADO' THEN 'DISPONIBLE'
								END)
			WHERE id_unidad_fisica = %s
				AND estado_venta IN ('RESERVADO');",
				valTpDato($frmDcto['txtKilometraje'], "text"),
				valTpDato($frmDcto['txtPlaca'], "text"),
				valTpDato($idUnidadFisicaAnterior, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
			kilometraje = %s,
			placa = %s
		WHERE id_unidad_fisica = %s;",
			valTpDato($frmDcto['txtKilometraje'], "text"),
			valTpDato($frmDcto['txtPlaca'], "text"),
			valTpDato($idUnidadFisica, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// RESERVA LA UNIDAD FISICA
		$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
			estado_venta = 'RESERVADO'
		WHERE id_unidad_fisica = %s
			AND estado_venta IN ('POR REGISTRAR','DISPONIBLE');",
			valTpDato($idUnidadFisica, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if (!($idFactura > 0)) {
		// ACTUALIZA EL ESTADO DEL PRESUPUESTO
		$updateSQL = sprintf("UPDATE an_presupuesto SET
			estado = 1
		WHERE id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO SI LA UNIDAD NO HA SIDO TOTALMMENTE REGISTRADA O SI PERTENECE AL ALMACEN DE OTRA EMPRESA
	// (0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada)
	$updateSQL = sprintf("UPDATE an_pedido ped_vent SET
		ped_vent.estado_pedido = (CASE 
									WHEN (ped_vent.estado_pedido IN (2,4,5)) THEN
										ped_vent.estado_pedido
									ELSE
										3
								END)
	WHERE ped_vent.id_pedido = %s
		AND ((SELECT uni_fis.estado_compra FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = %s) IN ('COMPRADO')
			OR (SELECT alm.id_empresa
				FROM an_almacen alm
					INNER JOIN an_unidad_fisica uni_fis ON (alm.id_almacen = uni_fis.id_almacen)
				WHERE uni_fis.id_unidad_fisica = %s) <> ped_vent.id_empresa);",
		valTpDato($idPedido, "int"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($idUnidadFisica, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdPedido","value",$idPedido);
	$objResponse->assign("txtNumeroPedidoPropio","value",$numeroActual);
	
	if (in_array($bloquearForm, array("1", "true"))) {
		$objResponse->alert(("Pedido Guardado con Éxito"));
		
		$objResponse->script("
		cerrarVentana = true;
		window.location.href='an_ventas_pedido_editar.php?view=import&id=".$idPedido."';");
	} else {
		$objResponse->script("byId('lstCasillaArt').onchange();");
	}
	
	errorPedidoVenta(NULL, $objResponse, false);
	
	return $objResponse;
}

function insertarAdicional($idAdicional, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	sort($frmDcto['cbxPieAdicional']);
	$arrayObjPieAdicional = $frmDcto['cbxPieAdicional'];
	$contFila1 = $arrayObjPieAdicional[count($arrayObjPieAdicional)-1];
	
	foreach ($arrayObjPieAdicional as $indicePieAdicional => $valorPieAdicional){
		if ($frmDcto['hddIdAdicionalItm'.$valorPieAdicional] == $idAdicional) {
			return $objResponse->alert("El adicional seleccionado ya se encuentra agregado");
		}
	}
	
	$Result1 = insertarItemAdicional($contFila1, "", "", $idAdicional);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila1 = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObjPieAdicional[] = $contFila1;
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function insertarArticulo($frmDatosArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	$contFila = $arrayObjPieArticulo[count($arrayObjPieArticulo)-1];
	
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
	
	$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND id_casilla = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idCasilla, "int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	if (!(($cantPedida - str_replace(",","",$frmListaArticulo['txtCantItm'.$hddNumeroArt])) <= $rowArtEmp['cantidad_disponible_logica']
	&& $rowArtEmp['cantidad_disponible_logica'] >= 0)) {
		// BUSQUEDA DEL ARTICULO POR EL ID
		$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_empresa WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
		$objResponse->assign("txtCantidadUbicacion","value",$rowArtEmp['cantidad_disponible_logica']);
		
		return $objResponse->alert("No posee disponible la cantidad suficiente");
	}
	
	// BUSCA EL ULTIMO COSTO DEL ARTICULO
	$queryCostoArt = sprintf("SELECT art_costo.*,
		moneda.abreviacion
	FROM iv_articulos_costos art_costo
		INNER JOIN pg_monedas moneda ON (art_costo.id_moneda = moneda.idmoneda)
	WHERE art_costo.id_articulo = %s
		AND art_costo.id_empresa = %s
	ORDER BY art_costo.fecha_registro DESC LIMIT 1;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCostoArt = mysql_query($queryCostoArt);
	if (!$rsCostoArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
	
	$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowCostoArt['costo'],3) : round($rowCostoArt['costo_promedio'],3);
	$abrevMonedaCostoUnitario = $rowCostoArt['abreviacion'];
	
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
			$objResponse->assign("txtCantItm".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
			$objResponse->assign("tdCantPend:".$hddNumeroArt,"innerHTML",number_format($cantPedida, 2, ".", ","));
			$objResponse->assign("txtPrecioItm".$hddNumeroArt,"value",number_format($precioUnitario, 2, ".", ","));
			$objResponse->assign("txtTotalItm".$hddNumeroArt,"value",number_format((($cantPedida * $precioUnitario) + $hddGastoItm), 2, ".", ","));
			
			$objResponse->assign("hddIdCasilla".$hddNumeroArt,"value",$idCasilla);
			$objResponse->assign("hddIdPrecioItm".$hddNumeroArt,"value",$lstPrecioArt);
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
				$Result1 = insertarItemArticulo($contFila, "", "", $idCliente, $idArticulo, $idCasilla, $cantPedida, $cantPedida, $lstPrecioArt, $precioUnitario, $precioSugerido, $costoUnitario, $abrevMonedaCostoUnitario, "", $idIva);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObjPieArticulo[] = $contFila;
				}
			}
		} else {
			$objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por Presupuesto"));
		}
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), 'true', 'true');");
	
	return $objResponse;
}

function insertarModelo($idUnidadBasica, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieModeloInteres = $frmProspecto['cbxPieModeloInteres'];
	$contFila = $arrayObjPieModeloInteres[count($arrayObjPieModeloInteres)-1];
	
	// BUSCA LOS DATOS DE LA UNIDAD BASICA
	$query = sprintf("SELECT
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_bas.pvp_venta1
	FROM an_uni_bas uni_bas
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
	WHERE uni_bas.id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$Result1 = insertarItemModeloInteres($contFila, "", $idUnidadBasica, $row['pvp_venta1']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObjPieModeloInteres[] = $contFila;
	}
	
	$objResponse->script("byId('btnCancelarModelo').click();");
	
	return $objResponse;
}

function insertarPaquete($idPaquete, $frmListaAdicional, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	sort($frmDcto['cbxPieAdicional']);
	$arrayObjPieAdicional = $frmDcto['cbxPieAdicional'];
	$contFila1 = $arrayObjPieAdicional[count($arrayObjPieAdicional)-1];
	
	foreach ($frmListaAdicional['cbxPaqueteAcc'] as $indicePaqueteAcc => $valorPaqueteAcc){
		$queryPaqueteAcc = sprintf("SELECT
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio
		FROM an_acc_paq acc_paq
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE acc_paq.Id_acc_paq = %s
			AND acc_paq.id_paquete = %s;",
			valTpDato($valorPaqueteAcc, "int"),
			valTpDato($idPaquete, "int"));
		$rsPaqueteAcc = mysql_query($queryPaqueteAcc);
		if (!$rsPaqueteAcc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPaqueteAcc = mysql_num_rows($rsPaqueteAcc);
		
		if ($totalRowsPaqueteAcc > 0) {
			$rowPaqueteAcc = mysql_fetch_assoc($rsPaqueteAcc);
			
			$idAdicionalPaquete = $rowPaqueteAcc['Id_acc_paq'];
			$idAdicional = $rowPaqueteAcc['id_accesorio'];
			foreach ($arrayObjPieAdicional as $indicePieAdicional => $valorPieAdicional){
				if ($frmDcto['hddIdAdicionalItm'.$valorPieAdicional] == $idAdicional) {
					return $objResponse->alert("El adicional seleccionado (".$rowPaqueteAcc['nom_accesorio'].") ya se encuentra agregado");
				}
			}
			
			$Result1 = insertarItemAdicional($contFila1, "", "", $idAdicional, $idAdicionalPaquete);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila1 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjPieAdicional[] = $contFila1;
			}
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function listaAdicional($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio IN (1,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(acc.id_mostrar_predeterminado IS NULL OR acc.id_mostrar_predeterminado = 0)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_accesorio LIKE %s
		OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		acc.id_accesorio,
		acc.id_modulo,
		acc.id_tipo_accesorio,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
			WHEN 4 THEN 'Cargo'
		END) AS descripcion_tipo_accesorio,
		CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc.iva_accesorio,
		acc.precio_accesorio,
		acc.costo_accesorio,
		acc.genera_comision,
		acc.incluir_costo_compra_unidad,
		acc.id_tipo_comision,
		acc.porcentaje_comision,
		acc.monto_comision,
		acc.id_filtro_factura,
		acc.activo,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM an_accesorio acc
		LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
		LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "28%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "40%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "12%", $pageNum, "descripcion_tipo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Adicional");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "10%", $pageNum, "costo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_insertarAdicional('".$row['id_accesorio']."', xajax.getFormValues('frmDcto'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_tipo_accesorio'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_accesorio'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAdicional(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaAdicional","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_Ws(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.telf LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente_emp.id_empresa,
		cliente.id,
		cliente.tipo,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.nit AS nit_cliente,
		cliente.licencia AS licencia_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		cliente.bloquea_venta,
		cliente.paga_impuesto,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				1
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
		END) AS tipo_cuenta_cliente,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				'Prospecto'
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
					'Prospecto Aprobado (Cliente Venta)',
					'Sin Prospectación (Cliente Post-Venta)')
		END) AS descripcion_tipo_cuenta_cliente,
		vw_pg_empleado.nombre_empleado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanCI));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('Empleado', '".$row['id_empleado']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPaquete($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(*) FROM an_acc_paq acc_paq
	WHERE acc_paq.id_paquete = paq.id_paquete) > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(TRIM(nom_paquete) LIKE TRIM(%s)
		OR des_paquete LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_paquete paq %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$query2 = sprintf("SELECT
			acc_paq.Id_acc_paq,
			acc.id_accesorio,
			acc.id_modulo,
			acc.id_tipo_accesorio,
			(CASE acc.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc.iva_accesorio,
			acc.precio_accesorio,
			acc.costo_accesorio,
			acc.genera_comision,
			acc.incluir_costo_compra_unidad,
			acc.id_tipo_comision,
			acc.porcentaje_comision,
			acc.monto_comision,
			acc.id_filtro_factura,
			acc.activo,
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM an_acc_paq acc_paq
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
			LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
			LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
		WHERE acc_paq.id_paquete = %s;",
			valTpDato($row['id_paquete'], "int"));
		$rs2 = mysql_query($query2);
		if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows2 = mysql_num_rows($rs2);
		
		$htmlTb .= (fmod($contFila, 1) == 1) ? "<tr align=\"left\" height=\"24\">" : "";
		
			$htmlTb .= "<td valign=\"top\">";
				$htmlTb .= "<fieldset><legend class=\"legend\">".utf8_encode($row['nom_paquete'])." (".utf8_encode($row['des_paquete']).")</legend>";
				if ($totalRows2 > 0) {
					$htmlTb .= "<table border=\"0\" width=\"100%\">";
					$contFila2 = 0;
					while ($row2 = mysql_fetch_array($rs2)) {
						$clase2 = (fmod($contFila2, 4) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila2++;
						
						$htmlTb .= (fmod($contFila2, 2) == 1) ? "<tr align=\"left\" class=\"".$clase2."\" height=\"24\">" : "";
						
							$htmlTb .= "<td width=\"50%\">";
								$htmlTb .= "<div class=\"checkbox-label\"><label><input type=\"checkbox\" id=\"cbxPaqueteAcc\" name=\"cbxPaqueteAcc[]\" checked=\"checked\" value=\"".$row2['Id_acc_paq']."\"/>".utf8_encode($row2['nom_accesorio'])."</label></div>";
							$htmlTb .= "</td>";
						
						$htmlTb .= (fmod($contFila2, 2) == 0) ? "</tr>" : "";
					}
					$htmlTb .= "<tr>";
						$htmlTb .= "<td align=\"center\" colspan=\"2\">";
							$htmlTb .= "<button type=\"button\" onclick=\"xajax_insertarPaquete(".$row['id_paquete'].", xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmDcto'));\" style=\"cursor:default\" value=\"Agregar Paquete\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/plus.png\"/></td><td>&nbsp;</td><td>Agregar Paquete</td></tr></table></button>";
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				}
				$htmlTb .= "</fieldset>";
			$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 1) == 0) ? "</tr>" : "";
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaPaquete","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}
	
function listaUnidadBasica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.catalogo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("unidad_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT vw_iv_modelo.*,
		uni_bas.clv_uni_bas
	FROM an_uni_bas uni_bas
		LEFT JOIN sa_unidad_empresa unidad_emp ON (uni_bas.id_uni_bas = unidad_emp.id_unidad_basica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas) %s", $sqlBusq);
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
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		switch($row['catalogo']) {
			case 0 : $classCatalogo = ""; break;
			case 1 : $classCatalogo = "class=\"divMsjInfo6\""; break;
			default : $classCatalogo = ""; break;
		}

		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$onClick = ($valCadBusq[7] == "ListaModelo") ? "xajax_insertarModelo('".$row['id_uni_bas']."', xajax.getFormValues('frmProspecto'));" : "xajax_asignarUnidadBasica('".$row['id_uni_bas']."');";
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td ".$classCatalogo." title=\"Id ".$row['id_uni_bas']."\" valign=\"top\">"."<button type=\"button\" onclick=\"".$onClick."\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= sprintf("<td style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">".
						"<div align=\"center\" class=\"divGris\">%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
					"</td>", "100%",
						utf8_encode($row['nom_uni_bas']),
					utf8_encode($row['nom_marca']),
					utf8_encode($row['nom_modelo']),
					utf8_encode($row['nom_version']),
					"Año ".utf8_encode($row['nom_ano']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("div".$valCadBusq[7],"innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO')");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		// UNIDADES QUE ESTAN EN LA MISMA EMPRESA DE DONDE SE CREA EL PEDIDO
		// UNIDADES QUE ESTEN EN SUCURSALES DE LA EMPRESA PRINCIPAL DE DONDE SE CREA EL PEDIDO
		// UNIDADES QUE ESTEN EN LA EMPRESA PRINCIPAL DE LAS SUCURSALES DE DONDE SE CREA EL PEDIDO
		// UNIDADES QUE ESTEN EN LAS SUCURSALES QUE PERTENEZCAN A LA EMPRESA PRINCIPAL DE LA SURCURSAL DE DONDE SE CREA EL PEDIDO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = alm.id_empresa)
		OR alm.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = %s)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
														WHERE suc.id_empresa = %s))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_uni_bas = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		(CASE vw_iv_modelo.catalogo
			WHEN 0 THEN ''
			WHEN 1 THEN 'En Catálogo'
		END) AS mostrar_catalogo
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "uni_fis.id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id Unidad Física"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, ("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Almacén"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Asignación"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Fact. Compra"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, ("Costo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.id_factura,
			cxp_fact.numero_factura_proveedor,
			cxp_fact.id_modulo,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			
			(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
			WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1
				AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowUnidadFisica['estado_venta']) {
				case "SINIESTRADO" : $class = "class=\"divMsjError\""; break;
				case "DISPONIBLE" : $class = "class=\"divMsjInfo\""; break;
				case "RESERVADO" : $class = "class=\"divMsjAlerta\""; break;
				case "VENDIDO" : $class = "class=\"divMsjInfo3\""; break;
				case "ENTREGADO" : $class = "class=\"divMsjInfo4\""; break;
				case "PRESTADO" : $class = "class=\"divMsjInfo2\""; break;
				case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
				case "DEVUELTO" : $class = "class=\"divMsjInfo6\""; break;
				default : $class = ""; break;
			}
			
			$aVerDcto = "";
			if ($rowUnidadFisica['id_factura'] > 0) {
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
					$rowUnidadFisica['id_factura']);
				switch ($rowUnidadFisica['id_modulo']) {
					case 0: $aVerDctoAux = sprintf("../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $rowUnidadFisica['id_factura']); break;
					case 2: $aVerDctoAux = sprintf("../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $rowUnidadFisica['id_factura']); break;
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>";
				if (!in_array($rowUnidadFisica['estado_venta'],array("RESERVADO"))) {
					$htmlTb .= "<button type=\"button\" onclick=\"xajax_asignarUnidadFisica('".$rowUnidadFisica['id_unidad_fisica']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>";
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</div>";
					$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($rowUnidadFisica['condicion_unidad'])."</div>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">Código: ".$rowUnidadFisica['id_activo_fijo']."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowUnidadFisica['numero_factura_proveedor'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= "<div>".number_format($rowUnidadFisica['precio_compra'], 2, ".", ",")."</div>";
					$htmlTb .= (($rowUnidadFisica['costo_agregado'] != 0) ? "<div class=\"".(($rowUnidadFisica['costo_agregado'] > 0) ? "textoVerdeNegrita_10px" : "textoRojoNegrita_10px")."\" title=\"".htmlentities("Total Agregados")."\">[".number_format($rowUnidadFisica['costo_agregado'], 2, ".", ",")."]</div>" : "");
					$htmlTb .= (($rowUnidadFisica['costo_depreciado'] != 0) ? "<div class=\"textoRojoNegrita_10px\" title=\"".htmlentities("Total Depreciación")."\">[-".number_format($rowUnidadFisica['costo_depreciado'], 2, ".", ",")."]</div>" : "");
					$htmlTb .= (($rowUnidadFisica['costo_trade_in'] != 0) ? "<div class=\"".(($rowUnidadFisica['costo_trade_in'] > 0) ? "textoRojoNegrita_10px" : "textoVerdeNegrita_10px")."\" title=\"".htmlentities("Total Depreciación Ingreso por Trade In")."\">[".number_format(((-1) * $rowUnidadFisica['costo_trade_in']), 2, ".", ",")."]</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal['cant_unidades'] = $contFila2;
			$arrayTotal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['precio_compra'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	if ($pageNum == $totalPages) {
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.id_factura,
			cxp_fact.numero_factura_proveedor,
			cxp_fact.id_modulo,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			
			(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
			WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1
				AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s;", $sqlBusq);
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$contFila2++;
			
			$arrayTotalFinal['cant_unidades'] = $contFila2;
			$arrayTotalFinal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal['precio_compra'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasenaPermiso'], "text"),
		valTpDato($frmPermiso['hddModuloPermiso'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] > 0) {
		if ($frmPermiso['hddModuloPermiso'] == "an_pedido_venta_form_entidad_bancaria") {
			$objResponse->assign("hddSinBancoFinanciar","value","1");
			$objResponse->script("byId('aDesbloquearSinBancoFinanciar').style.display = 'none';");
			
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
		} else if ($frmPermiso['hddModuloPermiso'] == "an_pedido_venta_form_unidad_fisica") {
			$objResponse->script("
			byId('txtKilometraje').className = 'inputHabilitado';
			byId('txtKilometraje').readOnly = false;
			byId('txtPlaca').className = 'inputHabilitado';
			byId('txtPlaca').readOnly = false;");
			
			$objResponse->script("
			byId('aDesbloquearKilometraje').style.display = 'none';
			byId('aDesbloquearPlaca').style.display = 'none';");
			
			$objResponse->script("byId('btnCancelarPermiso').click();");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFactor");
$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"asignarPoliza");
$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"asignarSinBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"bloquearLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"buscarAdicional");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"cargaLstMesesFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstPoliza");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarAdicionalLote");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloLote");
$xajax->register(XAJAX_FUNCTION,"formCliente");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion2");
$xajax->register(XAJAX_FUNCTION,"guardarCliente");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarAdicional");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarPaquete");
$xajax->register(XAJAX_FUNCTION,"listaAdicional");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaPaquete");
$xajax->register(XAJAX_FUNCTION,"listaUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}

function cargaLstTipoAdicionalItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "class=\"inputCompleto\"" : "class=\"inputCompletoHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,\'".$selId."\');\"" : "onchange=\"xajax_asignarMoneda(xajax.getFormValues(\'frmDcto\'));\"";
	
	$array = array(1 => "Adicional", 3 => "Contrato", 4 => "Cargo");
	$totalRows = count($array);
	
	$html .= "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstMostrarItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$array = array(1 => "Incluir en el Precio", 2 => "Incluir en el Costo");
	$totalRows = count($array);
	
	$html .= "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function errorPedidoVenta($arrayObjPieArticulo, $objResponse, $calcularDcto = true) {
	if (isset($arrayObjPieArticulo)) {
		$objResponse->script("
		fila = document.getElementById('trItmArticulo_".$arrayObjPieArticulo[count($arrayObjPieArticulo)-1]."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
	}
	
	if ($calcularDcto == true) {
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	}
}

function insertarItemAdicional($contFila, $idPedidoAdicional = "", $idPresupuestoAdicional = "", $hddIdAdicionalItm = "", $hddIdAdicionalPaqueteItm = "", $txtPrecioConIvaItm = "", $hddCostoUnitarioItm = "", $txtPrecioPagadoItm = "", $hddPorcIvaItm = "", $hddAplicaIvaItm = "", $cbxCondicion = "", $lstMostrarItm = "", $lstMostrarPendienteItm = "", $hddTipoAdicional = "", $bloquearObj = false) {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPedidoAdicional > 0) {
		// BUSCA EL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			acc_ped.precio_accesorio,
			acc_ped.costo_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.iva_accesorio,
			acc_ped.monto_pagado,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar,
			acc_ped.monto_pendiente,
			acc_ped.id_condicion_mostrar_pendiente,
			acc_ped.estatus_accesorio_pedido
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_accesorio_pedido = %s
		ORDER BY acc_ped.id_accesorio_pedido ASC;",
			valTpDato($idPedidoAdicional, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	}
	
	$estatusPedidoDet = ($estatusPedidoDet == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['estatus_accesorio_pedido'] : 0;
	
	// BUSCA LOS DATOS DEL ADICIONAL
	$queryAdicional = sprintf("SELECT acc.*,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
			WHEN 4 THEN 'Cargo'
		END) AS descripcion_tipo_accesorio,
		IF(iva_accesorio = 1, (SELECT SUM(iva) FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva), 0) AS porcentaje_iva_accesorio,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM an_accesorio acc
		LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
		LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
	WHERE acc.id_accesorio = %s;",
		valTpDato($hddIdAdicionalItm, "int"));
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsAdicional = mysql_num_rows($rsAdicional);
	$rowAdicional = mysql_fetch_assoc($rsAdicional);
	
	$txtPrecioConIvaItm = ($txtPrecioConIvaItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['precio_accesorio'] : $txtPrecioConIvaItm;
	$hddCostoUnitarioItm = ($hddCostoUnitarioItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['costo_accesorio'] : $hddCostoUnitarioItm;
	$txtPrecioPagadoItm = ($txtPrecioPagadoItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['monto_pagado'] : $txtPrecioPagadoItm;
	$hddPorcIvaItm = ($hddPorcIvaItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['porcentaje_iva_accesorio'] : $hddPorcIvaItm;
	$hddAplicaIvaItm = ($hddAplicaIvaItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['iva_accesorio'] : $hddAplicaIvaItm;
	$hddTipoAdicional = ($hddTipoAdicional == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['id_tipo_accesorio'] : $hddTipoAdicional;
	$nombreAdicional = $rowAdicional['nom_accesorio']. (($hddAplicaIvaItm == 1) ? " (Incluye Impuesto)" : "(E)");
	$hddMostrarPredeterminadoItm = $rowAdicional['id_mostrar_predeterminado'];
	
	$cbxItmAdicional = (in_array($estatusPedidoDet,array(0))) ?
		sprintf("<input type=\"checkbox\" id=\"cbxItmAdicional\" name=\"cbxItmAdicional[]\" value=\"%s\"/>",
			$contFila) : "";
	if ((in_array(idArrayPais,array(1,2)) && $totalRowsPedidoDet == 0) 
	|| ($cbxCondicion == 1 && $totalRowsPedidoDet > 0 && $txtPrecioPagadoItm > 0)) {
		$checkedCondicionItm = "checked=\"checked\"";
	}
	$displayCondicionItm = ($bloquearObj == true) ? "style=\"display:none\"" : "";
	$classNamePrecioPagadoItm = ($bloquearObj == true) ? "class=\"inputInicial\"" : "class=\"inputHabilitado\"";
	$readOnlyPrecioPagadoItm = ($bloquearObj == true) ? "readonly=\"readonly\"" : "";
	$classNamePrecioConIvaItm = (in_array($estatusPedidoDet,array(0)) && $bloquearObj == false) ? "class=\"inputCompletoHabilitado\"" : "class=\"inputSinFondo\"";
	$readOnlyPrecioConIvaItm = (in_array($estatusPedidoDet,array(0)) && $bloquearObj == false) ? "" : "readonly=\"readonly\"";
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPieAdicional').before('".
		"<tr id=\"trItmAdicional_%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmAdicional_%s\">%s".
				"<input type=\"checkbox\" id=\"cbxPieAdicional\" name=\"cbxPieAdicional[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmAdicional_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td>".
				"<div>%s</div>".
				
				"<div id=\"divCondicionItm%s\" class=\"checkbox-label\"><label %s><input type=\"checkbox\" id=\"cbxCondicionItm%s\" name=\"cbxCondicionItm%s\" %s value=\"1\"/>Pagado</label></div>".
				
				"<div id=\"divPrecioPagadoItm%s\">Monto Pagado: <input type=\"text\" id=\"txtPrecioPagadoItm%s\" name=\"txtPrecioPagadoItm%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" %s size=\"12\" style=\"text-align:right;\" value=\"%s\"/></div>".
				
				"<div>%s</div>".
				
				"<div id=\"%s\">Monto Restante: %s</div>".
			"</td>".
			"<td><input type=\"text\" id=\"txtPrecioConIvaItm%s\" name=\"txtPrecioConIvaItm%s\" %s %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right;\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdDetItm%s\" name=\"hddIdDetItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAdicionalItm%s\" name=\"hddIdAdicionalItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAdicionalPaqueteItm%s\" name=\"hddIdAdicionalPaqueteItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoUnitarioItm%s\" name=\"hddCostoUnitarioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPorcIvaItm%s\" name=\"hddPorcIvaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddAplicaIvaItm%s\" name=\"hddAplicaIvaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAdicionalItm%s\" name=\"hddTipoAdicionalItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddMostrarPredeterminadoItm%s\" name=\"hddMostrarPredeterminadoItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('cbxCondicionItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('lstMostrarItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('lstMostrarPendienteItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('txtPrecioPagadoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('txtPrecioConIvaItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}",
		$contFila, $clase,
			$contFila, $cbxItmAdicional,
				$contFila,
			$contFila, $contFila,
			utf8_encode($nombreAdicional),
				cargaLstTipoAdicionalItm("lstTipoAdicionalItm".$contFila, $hddTipoAdicional, (($bloquearObj == true || !in_array($estatusPedidoDet,array(0))) ? true : false)),
				
				$contFila, $displayCondicionItm,
					$contFila, $contFila, $checkedCondicionItm,
				
				$contFila, $contFila, $contFila, $classNamePrecioPagadoItm, $readOnlyPrecioPagadoItm, number_format($txtPrecioPagadoItm, 2, ".", ","),
				
				cargaLstMostrarItm("lstMostrarItm".$contFila, $lstMostrarItm),
				
				("divMostrarPendienteItm".$contFila), cargaLstMostrarItm("lstMostrarPendienteItm".$contFila, $lstMostrarPendienteItm),
			$contFila, $contFila, $classNamePrecioConIvaItm, $readOnlyPrecioConIvaItm, number_format($txtPrecioConIvaItm, 2, ".", ","),
				$contFila, $contFila, $idPedidoAdicional,
				$contFila, $contFila, $hddIdAdicionalItm,
				$contFila, $contFila, $hddIdAdicionalPaqueteItm,
				$contFila, $contFila, $hddCostoUnitarioItm,
				$contFila, $contFila, $hddPorcIvaItm,
				$contFila, $contFila, $hddAplicaIvaItm,
				$contFila, $contFila, $hddTipoAdicionalItm,
				$contFila, $contFila, $hddMostrarPredeterminadoItm,
		
		$contFila,
		$contFila,
		$contFila,
		$contFila,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemAdicionalPredeterminado($contFila, $idPedidoAdicional = "", $idPresupuestoAdicional = "", $hddIdAdicionalItm = "", $hddIdAdicionalPaqueteItm = "", $txtPrecioConIvaItm = "", $hddCostoUnitarioItm = "", $txtPrecioPagadoItm = "", $hddPorcIvaItm = "", $hddAplicaIvaItm = "", $cbxCondicion = "", $lstMostrarItm = "", $lstMostrarPendienteItm = "", $hddTipoAdicional = "", $bloquearObj = false) {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPedidoAdicional > 0) {
		// BUSCA EL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
				WHEN 4 THEN 'Cargo'
			END) AS descripcion_tipo_accesorio,
			acc_ped.precio_accesorio,
			acc_ped.costo_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.iva_accesorio,
			acc_ped.monto_pagado,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar,
			acc_ped.monto_pendiente,
			acc_ped.id_condicion_mostrar_pendiente,
			acc_ped.estatus_accesorio_pedido
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_accesorio_pedido = %s
		ORDER BY acc_ped.id_accesorio_pedido ASC;",
			valTpDato($idPedidoAdicional, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	}
	
	$estatusPedidoDet = ($estatusPedidoDet == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['estatus_accesorio_pedido'] : 0;
	
	// BUSCA LOS DATOS DEL ADICIONAL
	$queryAdicional = sprintf("SELECT acc.*,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
			WHEN 4 THEN 'Cargo'
		END) AS descripcion_tipo_accesorio,
		IF(iva_accesorio = 1, (SELECT SUM(iva) FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva), 0) AS porcentaje_iva_accesorio,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM an_accesorio acc
		LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
		LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
	WHERE acc.id_accesorio = %s;",
		valTpDato($hddIdAdicionalItm, "int"));
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsAdicional = mysql_num_rows($rsAdicional);
	$rowAdicional = mysql_fetch_assoc($rsAdicional);
	
	$txtPrecioConIvaItm = ($txtPrecioConIvaItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['precio_accesorio'] : $txtPrecioConIvaItm;
	$hddCostoUnitarioItm = ($hddCostoUnitarioItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['costo_accesorio'] : $hddCostoUnitarioItm;
	$txtPrecioPagadoItm = ($txtPrecioPagadoItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['monto_pagado'] : $txtPrecioPagadoItm;
	$hddPorcIvaItm = ($hddPorcIvaItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['porcentaje_iva_accesorio'] : $hddPorcIvaItm;
	$hddAplicaIvaItm = ($hddAplicaIvaItm == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['iva_accesorio'] : $hddAplicaIvaItm;
	$hddTipoAdicional = ($hddTipoAdicional == "" && $totalRowsPedidoDet == 0) ? $rowAdicional['id_tipo_accesorio'] : $hddTipoAdicional;
	$nombreAdicional = $rowAdicional['nom_accesorio']. (($hddAplicaIvaItm == 1) ? " (Incluye Impuesto)" : "(E)");
	$hddMostrarPredeterminadoItm = $rowAdicional['id_mostrar_predeterminado'];
	
	if ((in_array(idArrayPais,array(1,2)) && $totalRowsPedidoDet == 0) 
	|| ($cbxCondicion == 1 && $totalRowsPedidoDet > 0 && $txtPrecioPagadoItm > 0)) {
		$checkedCondicionItm = "checked=\"checked\"";
	}
	$displayCondicionItm = ($bloquearObj == true) ? "style=\"display:none\"" : "";
	$classNamePrecioPagadoItm = ($bloquearObj == true) ? "class=\"inputInicial\"" : "class=\"inputHabilitado\"";
	$readOnlyPrecioPagadoItm = ($bloquearObj == true) ? "readonly=\"readonly\"" : "";
	$classNamePrecioConIvaItm = (in_array($estatusPedidoDet,array(0)) && $bloquearObj == false) ? "class=\"inputCompletoHabilitado\"" : "class=\"inputSinFondo\"";
	$readOnlyPrecioConIvaItm = (in_array($estatusPedidoDet,array(0)) && $bloquearObj == false) ? "" : "readonly=\"readonly\"";
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPieAdicionalPreterminado').before('".
		"<tr id=\"trItmAdicional_%s\" align=\"right\" class=\"textoGris_11px\">".
			"<td class=\"tituloCampo\">%s:</td>".
			"<td title=\"trItmAdicional_%s\">".
				"<input type=\"checkbox\" id=\"cbxPieAdicional\" name=\"cbxPieAdicional[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
				
				"<div style=\"display:none\">%s</div>".
				
				"<div id=\"divCondicionItm%s\" class=\"checkbox-label\"><label %s><input type=\"checkbox\" id=\"cbxCondicionItm%s\" name=\"cbxCondicionItm%s\" %s value=\"1\"/>Pagado</label></div>".
				
				"<div id=\"divPrecioPagadoItm%s\">Monto Pagado: <input type=\"text\" id=\"txtPrecioPagadoItm%s\" name=\"txtPrecioPagadoItm%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" %s size=\"12\" style=\"text-align:right;\" value=\"%s\"/></div>".
				
				"<div>%s</div>".
				
				"<div id=\"%s\">Monto Restante: %s</div>".
			"</td>".
			"<td>"."</td>".
			"<td><input type=\"text\" id=\"txtPrecioConIvaItm%s\" name=\"txtPrecioConIvaItm%s\" %s %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right;\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdDetItm%s\" name=\"hddIdDetItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAdicionalItm%s\" name=\"hddIdAdicionalItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAdicionalPaqueteItm%s\" name=\"hddIdAdicionalPaqueteItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoUnitarioItm%s\" name=\"hddCostoUnitarioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPorcIvaItm%s\" name=\"hddPorcIvaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddAplicaIvaItm%s\" name=\"hddAplicaIvaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAdicionalItm%s\" name=\"hddTipoAdicionalItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddMostrarPredeterminadoItm%s\" name=\"hddMostrarPredeterminadoItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('cbxCondicionItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('lstMostrarItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('lstMostrarPendienteItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('txtPrecioPagadoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('txtPrecioConIvaItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}",
		$contFila,
			utf8_encode($nombreAdicional),
			$contFila,
				$contFila,
				
				cargaLstTipoAdicionalItm("lstTipoAdicionalItm".$contFila, $hddTipoAdicional, (($bloquearObj == true || !in_array($estatusPedidoDet,array(0))) ? true : false)),
				
				$contFila, $displayCondicionItm,
					$contFila, $contFila, $checkedCondicionItm,
				
				$contFila, $contFila, $contFila, $classNamePrecioPagadoItm, $readOnlyPrecioPagadoItm, number_format($txtPrecioPagadoItm, 2, ".", ","),
				
				cargaLstMostrarItm("lstMostrarItm".$contFila, $lstMostrarItm),
				
				("divMostrarPendienteItm".$contFila), cargaLstMostrarItm("lstMostrarPendienteItm".$contFila, $lstMostrarPendienteItm),
			$contFila, $contFila, $classNamePrecioConIvaItm, $readOnlyPrecioConIvaItm, number_format($txtPrecioConIvaItm, 2, ".", ","),
				$contFila, $contFila, $idPedidoAdicional,
				$contFila, $contFila, $hddIdAdicionalItm,
				$contFila, $contFila, $hddIdAdicionalPaqueteItm,
				$contFila, $contFila, $hddCostoUnitarioItm,
				$contFila, $contFila, $hddPorcIvaItm,
				$contFila, $contFila, $hddAplicaIvaItm,
				$contFila, $contFila, $hddTipoAdicionalItm,
				$contFila, $contFila, $hddMostrarPredeterminadoItm,
		
		$contFila,
		$contFila,
		$contFila,
		$contFila,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemArticulo($contFila, $hddIdPedidoDet = "", $hddIdPresupuestoDet = "", $idCliente = "", $idArticulo = "", $idCasilla = "", $cantPedida = "", $cantPendiente = "", $hddIdPrecioItm = "", $precioUnitario = "", $precioSugerido = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $txtPrecioPagadoItm = "", $idIva = "", $cbxCondicion = "", $lstMostrarItm = "", $lstMostrarPendienteItm = "", $bloquearObj = false) {
	$contFila++;
	
	$totalRowsPresupuestoDetalle = 0;
	if ($hddIdPresupuestoDet > 0 || $hddIdPedidoDet > 0) {
		$totalRowsPresupuestoDetalle = 1;
		
		if ($hddIdPedidoDet > 0) {
			$queryIdEmpresa = sprintf("SELECT an_ped_vent.id_empresa
			FROM an_pedido an_ped_vent
				INNER JOIN an_pedido_venta_detalle an_ped_vent_det ON (an_ped_vent.id_pedido = an_ped_vent_det.id_pedido_venta)
			WHERE an_ped_vent_det.id_pedido_venta_detalle = %s;",
				valTpDato($hddIdPedidoDet, "int"));
		} else if ($hddIdPresupuestoDet > 0) {
			$queryIdEmpresa = sprintf("SELECT pres_vent.id_empresa
			FROM an_presupuesto pres_vent
				INNER JOIN an_presupuesto_venta_detalle an_pres_vent_det ON (pres_vent.id_presupuesto = an_pres_vent_det.id_presupuesto_venta)
			WHERE an_pres_vent_det.id_presupuesto_venta_detalle = %s;",
				valTpDato($hddIdPresupuestoDet, "int"));
		}
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
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryArtCosto = sprintf("SELECT art_costo.*,
			moneda.abreviacion
		FROM iv_articulos_costos art_costo
			INNER JOIN pg_monedas moneda ON (art_costo.id_moneda = moneda.idmoneda)
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa = %s
		ORDER BY art_costo.fecha_registro DESC LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
		
		$costoUnitarioDet = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
		$abrevMonedaCostoUnitario = $rowArtCosto['abreviacion'];
	}
	
	$costoUnitario = ($costoUnitario == "" && $totalRowsPresupuestoDetalle > 0) ? $costoUnitarioDet : $costoUnitario;
	if ((in_array(idArrayPais,array(1,2)) && $totalRowsPresupuestoDetalle == 0) 
	|| ($cbxCondicion == 1 && $totalRowsPresupuestoDetalle > 0 && $txtPrecioPagadoItm > 0)) {
		$checkedCondicionItm = "checked=\"checked\"";
	}
	$displayCondicionItm = ($bloquearObj == true) ? "style=\"display:none\"" : "";
	$classNamePrecioPagadoItm = ($bloquearObj == true) ? "class=\"inputInicial\"" : "class=\"inputHabilitado\"";
	$readOnlyPrecioPagadoItm = ($bloquearObj == true) ? "readonly=\"readonly\"" : "";
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	if ($idCasilla > 0) {
		// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
		$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
			valTpDato($idCasilla, "int"));
		$rsUbicacion = mysql_query($queryUbicacion);
		if (!$rsUbicacion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
		$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
	}
	
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
		"<input type=\"checkbox\" id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
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
		AND art_precio.id_empresa = %s
		AND precio.estatus IN (1)
	ORDER BY precio.porcentaje DESC;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
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
	if ($hddIdPedidoDet > 0) {
		$queryDetGasto = sprintf("SELECT * FROM an_pedido_venta_detalle_gastos
		WHERE id_pedido_venta_detalle = %s;",
			valTpDato($hddIdPedidoDet, "int"));
	} else if ($hddIdPresupuestoDet > 0) {
		$queryDetGasto = sprintf("SELECT * FROM an_presupuesto_venta_detalle_gastos
		WHERE id_presupuesto_venta_detalle = %s;",
			valTpDato($hddIdPresupuestoDet, "int"));
	}
	if (strlen($queryDetGasto) > 0) {
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
	}
	
	$htmlGastoArt = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$htmlGastoArt .= "<tr>";
		$htmlGastoArt .= "<td><a class=\"modalImg\" id=\"aGastoArt:".$contFila."\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\"/></a></td>";
		$htmlGastoArt .= "<td id=\"tdItmGastoObj:".$contFila."\" title=\"tdItmGastoObj:".$contFila."\">".$htmlGastoArtObj."</td>";
		$htmlGastoArt .= "<td width=\"100%\"><input type=\"text\" id=\"hddGastoItm".$contFila."\" name=\"hddGastoItm".$contFila."\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"".number_format($totalGastoArt, 2, ".", ",")."\"/></td>";
	$htmlGastoArt .= "</tr>";
	$htmlGastoArt .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPieArticulo').before('".
		"<tr align=\"left\" id=\"trItmArticulo_%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmArticulo_%s\"><input type=\"checkbox\" id=\"cbxItmArticulo\" name=\"cbxItmArticulo[]\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbxPieArticulo\" name=\"cbxPieArticulo[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmArticulo_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><input type=\"text\" id=\"txtCodigoArtItm%s\" name=\"txtCodigoArtItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
			"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
				"<div id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</div>".
				"%s".
				
				"<div %s>%s <input type=\"text\" id=\"hddPrecioSugeridoItm%s\" name=\"hddPrecioSugeridoItm%s\" class=\"inputSinFondo\" size=\"12\" readonly=\"readonly\" value=\"%s\"/></div>".
				
				"<div id=\"divCondicionItmArticulo%s\" class=\"checkbox-label\" %s><label><input type=\"checkbox\" id=\"cbxCondicionItmArticulo%s\" name=\"cbxCondicionItmArticulo%s\" %s value=\"1\"/>Pagado</label></div>".
				
				"<div id=\"divPrecioPagadoItmArticulo%s\">Monto Pagado: <input type=\"text\" id=\"txtPrecioPagadoItmArticulo%s\" name=\"txtPrecioPagadoItmArticulo%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" %s size=\"12\" style=\"text-align:right;\" value=\"%s\"/></div>".
			"</td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdCantPend:%s\" align=\"right\" style=\"display:none\">%s</td>".
			"<td align=\"right\" style=\"display:none\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" title=\"Costo Unitario\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td style=\"display:none\"><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtTotalConImpuestoItm%s\" name=\"txtTotalConImpuestoItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPresupuestoDet%s\" name=\"hddIdPresupuestoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('cbxCondicionItmArticulo%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		byId('txtPrecioPagadoItmArticulo%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		
		byId('aGastoArt:%s').onclick = function() { abrirDivFlotante1(this, 'tblLista', 'Gasto', '%s'); }
		
		byId('txtPrecioItm%s').onmouseover = function() { Tip('%s', TITLE, 'Lista de Precios'); }
		byId('txtPrecioItm%s').onmouseout = function() { UnTip(); }",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$contFila, $contFila,
			$contFila, $contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
				((in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) ? "" : "<div id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</div>"),
				
				(($precioSugerido != 0) ? "" : "style=\"display:none\""), "<span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">Precio Sugerido:</span>", $contFila, $contFila, number_format($precioSugerido, 2, ".", ","),
				
				$contFila, $displayCondicionItm,
					$contFila, $contFila, $checkedCondicionItm,
				
				$contFila, $contFila, $contFila, $classNamePrecioPagadoItm, $readOnlyPrecioPagadoItm, number_format($txtPrecioPagadoItm, 2, ".", ","),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, number_format($cantPendiente, 2, ".", ","),
			$htmlGastoArt,
			$contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
				$contFila, $contFila, $costoUnitario,
			$contFila, $ivaUnidad,
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
				$contFila, $contFila, $hddIdPresupuestoDet,
				$contFila, $contFila, $hddIdPedidoDet,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdPrecioItm,
				$contFila, $contFila, $idCasilla,
		
		$contFila,
		$contFila,
		
		$contFila, $contFila,
		
		$contFila, $htmlPreciosArt,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemClienteEmpresa($contFila, $idClienteEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idClienteEmpresa > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryClienteEmpresa = sprintf("SELECT
			cliente_emp.id_cliente_empresa,
			cliente_emp.id_empresa,
			cred.id AS id_credito,
			cred.diascredito,
			cred.fpago,
			cred.limitecredito,
			cred.creditoreservado,
			cred.creditodisponible,
			cred.intereses
		FROM cj_cc_credito cred
			RIGHT JOIN cj_cc_cliente_empresa cliente_emp ON (cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente_empresa = %s;",
			valTpDato($idClienteEmpresa, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsClienteEmpresa = mysql_num_rows($rsClienteEmpresa);
		$rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa);
	}
	
	$idEmpresa = ($idEmpresa == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_empresa'] : $idEmpresa;
	$idCredito = ($idCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_credito'] : $idCredito;
	$txtDiasCredito = ($txtDiasCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['diascredito'] : $txtDiasCredito;
	$txtFormaPago = ($txtFormaPago == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['fpago'] : $txtFormaPago;
	$txtLimiteCredito = ($txtLimiteCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['limitecredito'] : $txtLimiteCredito;
	$txtCreditoReservado = ($txtCreditoReservado == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditoreservado'] : $txtCreditoReservado;
	$txtCreditoDisponible = ($txtCreditoDisponible == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditodisponible'] : $txtCreditoDisponible;
	$txtIntereses = ($txtIntereses == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['intereses'] : $txtIntereses;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDiasCredito%s\" name=\"txtDiasCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtFormaPago%s\" name=\"txtFormaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtLimiteCredito%s\" name=\"txtLimiteCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoReservado%s\" name=\"txtCreditoReservado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoDisponible%s\" name=\"txtCreditoDisponible%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><a id=\"aEditarCredito%s\" class=\"modalImg\" rel=\"#divFlotante2\"><img class=\"puntero\" src=\"../img/iconos/edit_privilegios.png\" title=\"Editar Crédito\"/></a>".
				"<input type=\"hidden\" id=\"hddIdClienteEmpresa%s\" name=\"hddIdClienteEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCredito%s\" name=\"hddIdCredito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarCredito%s').onclick = function() {
			abrirDivFlotante2(this, 'tblCredito', '%s');
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			$contFila, $contFila, number_format($txtDiasCredito, 0, ".", ","),
			$contFila, $contFila, $txtFormaPago,
			$contFila, $contFila, number_format($txtLimiteCredito, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoReservado, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoDisponible, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $idClienteEmpresa,
				$contFila, $contFila, $idCredito,
				$contFila, $contFila, $idEmpresa,
			
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>