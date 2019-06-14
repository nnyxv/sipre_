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
			window.setTimeout(function(){ xajax_guardarDcto(xajax.getFormValues('frmDcto'), '".$bloquearForm."', '".$frmDcto['hddIdPresupuestoVenta']."'); },1000);");
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
	
function cargaLstEstadoCivil($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'estadoCivil' AND git.status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstEstadoCivil\" name=\"lstEstadoCivil\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoCivil","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstatus($id_estatus = "") {
	$objResponse = new xajaxResponse();

	// LLAMA SELECT ESTATUS
	$sql_estatus = sprintf("SELECT id_estatus, nombre_estatus FROM crm_estatus
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_estatus;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_estatus = mysql_query($sql_estatus);
	$rs_estatus = mysql_num_rows($query_estatus);
	$select_estatus = "<select id='id_estatus' name='estatus' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_estatus .= '<option value="">[ Seleccione ]</option>';
	while ($fila_estatus = mysql_fetch_array($query_estatus)) {
		$selected = ($fila_estatus['id_estatus'] == $id_estatus) ? "selected=\"selected\"" : "";
		
		$select_estatus .= '<option '.$selected.' value="'.$fila_estatus['id_estatus'].'">'.utf8_encode($fila_estatus['nombre_estatus']).'</option>';
	}
	$select_estatus .= "</select>";
	$objResponse->assign('td_select_estatus', 'innerHTML', $select_estatus);
	
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

function cargaLstMotivoRechazo($motivo, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM crm_motivo_rechazo
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_motivo_rechazo;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstMotivoRechazo\" name=\"lstMotivoRechazo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	if ($motivo == 'Rechazo') {
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_motivo_rechazo']) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row['id_motivo_rechazo']."\">".utf8_encode($row['nombre_motivo_rechazo'])."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("td_select_motivo_rechazo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstNivelInfluencia($id_nivel_influencia = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMA SELECT NIVEL INFLUENCIA
	$sql_nivel_influencia = sprintf("SELECT id_nivel_influencia, nombre_nivel_influencia FROM crm_nivel_influencia
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_nivel_influencia;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_nivelt_influencia = mysql_query($sql_nivel_influencia);
	$rs_nivel_influencia = mysql_num_rows($query_nivelt_influencia);
	$select_nivel_influencia = "<select id='id_nivel_influencia' name='nivel_influencia' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_nivel_influencia .= '<option value="">[ Seleccione ]</option>';				
	while ($fila_nivel_influencia = mysql_fetch_array($query_nivelt_influencia)) {
		$selected = ($fila_nivel_influencia['id_nivel_influencia'] == $id_nivel_influencia) ? "selected=\"selected\"" : "";
		
		$select_nivel_influencia .= '<option '.$selected.' value="'.$fila_nivel_influencia['id_nivel_influencia'].'">'.utf8_encode($fila_nivel_influencia['nombre_nivel_influencia']).'</option>';
	}
	$select_nivel_influencia .= "</select>";
	$objResponse->assign('td_select_nivel_influencia', 'innerHTML', $select_nivel_influencia);
	
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

function cargaLstPosibilidadCierre($id_posibilidad_cierre = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMA SELECT POSIBILIDAD DE CIERRE
	$sql_posibilidad_cierre = sprintf("SELECT id_posibilidad_cierre, nombre_posibilidad_cierre FROM crm_posibilidad_cierre
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_posibilidad_cierre;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_posibilidad_cierre = mysql_query($sql_posibilidad_cierre);
	if (!$query_posibilidad_cierre) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rs_posibilidad_cierre = mysql_num_rows($query_posibilidad_cierre);
	$select_posibilidad_cierre = "<select id='posibilidad_cierre' name='posibilidad_cierre' class=\"inputHabilitado\" onchange='motivoRechazo(this.value)' style=\"width:99%\">";
		$select_posibilidad_cierre .= '<option value="">[ Seleccione ]</option>';
	while ($fila_posibilidad_cierre = mysql_fetch_array($query_posibilidad_cierre)) {
		$selected = ($fila_posibilidad_cierre['id_posibilidad_cierre'] == $id_posibilidad_cierre) ? "selected=\"selected\"" : "";
		
		$select_posibilidad_cierre .= '<option '.$selected.' value="'.$fila_posibilidad_cierre['id_posibilidad_cierre'].'">'.utf8_encode($fila_posibilidad_cierre['nombre_posibilidad_cierre']).'</option>';
	}
	$select_posibilidad_cierre .= "</select>";
	$objResponse->assign('td_select_posibilidad_cierre', 'innerHTML', $select_posibilidad_cierre);

	return $objResponse;
}

function cargaLstPuesto($id_puesto = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMAR SELECT PUESTO
	$sql_puesto = sprintf("SELECT id_puesto, nombre_puesto FROM crm_puesto
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_puesto;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_puesto = mysql_query($sql_puesto);
	$rs_puesto = mysql_num_rows($query_puesto);
	$select_puesto = "<select id='id_puesto' name='puesto' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_puesto .= '<option value="">[ Seleccione ]</option>';
	while ($fila_puesto = mysql_fetch_array($query_puesto)) {
		$selected = ($fila_puesto['id_puesto'] == $id_puesto) ? "selected=\"selected\"" : "";
		
		$select_puesto .= '<option '.$selected.' value="'.$fila_puesto['id_puesto'].'">'.utf8_encode($fila_puesto['nombre_puesto']).'</option>';
	}
	$select_puesto .= "</select>";
	$objResponse->assign('td_select_puesto', 'innerHTML', $select_puesto);
	
	return $objResponse;
}

function cargaLstSector($id_sector = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMAR SELECT SECTOR
	$sql_sector = sprintf("SELECT id_sector, nombre_sector FROM crm_sector
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_sector;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_sector = mysql_query($sql_sector);
	$rs_sector = mysql_num_rows($query_sector);
	$select_sector = "<select id='id_sector' name='sector' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_sector .= '<option value="">[ Seleccione ]</option>';
	while ($fila_sector = mysql_fetch_array($query_sector)) {
		$selected = ($fila_sector['id_sector'] == $id_sector) ? "selected=\"selected\"" : "";
		
		$select_sector .= '<option '.$selected.' value="'.$fila_sector['id_sector'].'">'.utf8_encode($fila_sector['nombre_sector']).'</option>';
	}
	$select_sector .= "</select>";
	$objResponse->assign('td_select_sector', 'innerHTML', $select_sector);
	
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

function cargaLstTitulo($id_titulo = "") {
	$objResponse = new xajaxResponse();
	
	// LLENAR SELECT TITULO
	$sql_titulo = sprintf("SELECT id_titulo, nombre_titulo FROM crm_titulo
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_titulo",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_titulo = mysql_query($sql_titulo);
	if (!$query_titulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rs_titulo = mysql_num_rows($query_titulo);	
	$select_titulo = "<select id='id_titulo' name='titulo' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_titulo .= '<option value="">[ Seleccione ]</option>';
	while ($fila_titulo = mysql_fetch_array($query_titulo)) {
		$selected = ($fila_titulo['id_titulo'] == $id_titulo) ? "selected=\"selected\"" : "";
		
		$select_titulo .= '<option '.$selected.' value="'.$fila_titulo['id_titulo'].'">' .utf8_encode($fila_titulo['nombre_titulo']). '</option>';
	}
	$select_titulo .= "</select>";
	$objResponse->assign("td_select_titulo","innerHTML",$select_titulo);
	
	return $objResponse;
}

function cargarDcto($idPresupuesto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
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
	
	if ($idPresupuesto > 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
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
		WHERE pres_vent.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
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
			$Result1 = insertarItemArticulo($contFila, "", $rowPresupuestoDet['id_presupuesto_venta_detalle'], $idCliente, $rowPresupuestoDet['id_articulo'], $rowPresupuestoDet['id_casilla'], $rowPresupuestoDet['cantidad'], $rowPresupuestoDet['pendiente'], $rowPresupuestoDet['id_precio'], $rowPresupuestoDet['precio_unitario'], $rowPresupuestoDet['precio_sugerido'], "", "", $rowPresupuestoDet['monto_pagado'], $rowPresupuestoDet['id_iva'], $rowPresupuestoDet['id_condicion_pago'], $rowPresupuestoDet['id_condicion_mostrar'], $rowPresupuestoDet['id_condicion_mostrar_pendiente'], (($totalRowsFinanciamiento > 0) ? true : false));
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
			valTpDato(38, "int"), // 38 = Presupuesto Venta Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNumeracion = mysql_num_rows($rsNumeracion);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		if (!($totalRowsNumeracion > 0)) {
			return $objResponse->script("
			alert('No puede realizar presupuesto de venta por esta empresa');
			if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado("Empleado", $_SESSION['idEmpleadoSysGts'], "", "", "false"));
		
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
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'));");
	
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
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
		
	return $objResponse;
}

function formProspecto($idCliente, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieModeloInteres = $frmProspecto['cbxPieModeloInteres'];
	
	if (isset($arrayObjPieModeloInteres)) {
		foreach ($arrayObjPieModeloInteres as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmModeloInteres:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("
	byId('trCedulaProspecto').style.display = '';
	byId('lstTipoProspecto').style.display = '';");
	if (in_array(idArrayPais,array(2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("
		byId('trCedulaProspecto').style.display = 'none';
		byId('lstTipoProspecto').style.display = 'none';");
	}
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","editar")) { $objResponse->script("byId('btnCancelarProspecto').click();"); return $objResponse; }
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';");
		
		$query = sprintf("SELECT cliente.*,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			vw_pg_empleado.nombre_empleado
		FROM cj_cc_cliente cliente
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($row['tipo_cuenta_cliente'] == 1) { // 1 = Prospecto, 2 = Cliente
			$objResponse->script("
			byId('lstTipoProspecto').className = 'inputHabilitado';
			byId('txtCedulaProspecto').className = 'inputHabilitado';
			byId('txtNitProspecto').className = 'inputHabilitado';
			byId('txtNombreProspecto').className = 'inputHabilitado';
			byId('txtApellidoProspecto').className = 'inputHabilitado';
			byId('txtLicenciaProspecto').className = 'inputHabilitado';
			
			byId('txtCedulaProspecto').readOnly = false;
			byId('txtNitProspecto').readOnly = false;
			byId('txtNombreProspecto').readOnly = false;
			byId('txtApellidoProspecto').readOnly = false;
			
			byId('tdFlotanteTitulo1').innerHTML = 'Editar Prospecto';");
		} else {
			$objResponse->script("
			byId('tdFlotanteTitulo1').innerHTML = 'Editar Cliente';");
			
			$objResponse->script("
			byId('trCedulaProspecto').style.display = '';
			byId('lstTipoProspecto').style.display = '';");
		}
		
		$objResponse->assign("hddIdClienteProspecto","value",$row['id']);
		switch ($row['tipo']) {
			case "Natural" : $lstTipoProspecto = "Natural"; break;
			case "Juridico" : $lstTipoProspecto = "Juridico"; break;
		}
		$objResponse->script("selectedOption('lstTipoProspecto', '".$lstTipoProspecto."');");
		$objResponse->script("byId('lstTipoProspecto').onchange = function() { selectedOption(this.id, '".$lstTipoProspecto."'); }");
		$objResponse->assign("txtCedulaProspecto","value",$row['ci_cliente']);
		$objResponse->assign("txtNitProspecto","value",$row['nit']);
		$objResponse->assign("txtNombreProspecto","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtApellidoProspecto","value",utf8_encode($row['apellido']));
		$objResponse->assign("txtLicenciaProspecto","value",utf8_encode($row['licencia']));
		
		$objResponse->assign("txtUrbanizacionProspecto","value",utf8_encode($row['urbanizacion']));
		$objResponse->assign("txtCalleProspecto","value",utf8_encode($row['calle']));
		$objResponse->assign("txtCasaProspecto","value",utf8_encode($row['casa']));
		$objResponse->assign("txtMunicipioProspecto","value",utf8_encode($row['municipio']));
		$objResponse->assign("txtCiudadProspecto","value",utf8_encode($row['ciudad']));
		$objResponse->assign("txtEstadoProspecto","value",utf8_encode($row['estado']));
		$objResponse->assign("txtTelefonoProspecto","value",$row['telf']);
		$objResponse->assign("txtOtroTelefonoProspecto","value",$row['otrotelf']);
		$objResponse->assign("txtCorreoProspecto","value",utf8_encode($row['correo']));
		
		$objResponse->assign("txtUrbanizacionPostalProspecto","value",utf8_encode($row['urbanizacion_postal']));
		$objResponse->assign("txtCallePostalProspecto","value",utf8_encode($row['calle_postal']));
		$objResponse->assign("txtCasaPostalProspecto","value",utf8_encode($row['casa_postal']));
		$objResponse->assign("txtMunicipioPostalProspecto","value",utf8_encode($row['municipio_postal']));
		$objResponse->assign("txtCiudadPostalProspecto","value",utf8_encode($row['ciudad_postal']));
		$objResponse->assign("txtEstadoPostalProspecto","value",utf8_encode($row['estado_postal']));
		
		$objResponse->assign("txtUrbanizacionComp","value",utf8_encode($row['urbanizacion_comp']));
		$objResponse->assign("txtCalleComp","value",utf8_encode($row['calle_comp']));
		$objResponse->assign("txtCasaComp","value",utf8_encode($row['casa_comp']));
		$objResponse->assign("txtMunicipioComp","value",utf8_encode($row['municipio_comp']));
		$objResponse->assign("txtEstadoComp","value",utf8_encode($row['estado_comp']));
		$objResponse->assign("txtTelefonoComp","value",$row['telf_comp']);
		$objResponse->assign("txtOtroTelefonoComp","value",$row['otro_telf_comp']);
		$objResponse->assign("txtEmailComp","value",utf8_encode($row['correo_comp']));
		$objResponse->assign("txtFechaUltAtencion","value",(($row['fechaUltimaAtencion'] != "") ? date(spanDateFormat, strtotime($row['fechaUltimaAtencion'])) : ""));
		$objResponse->assign("txtFechaUltEntrevista","value",(($row['fechaUltimaEntrevista'] != "") ? date(spanDateFormat, strtotime($row['fechaUltimaEntrevista'])) : ""));
		$objResponse->assign("txtFechaProxEntrevista","value",(($row['fechaProximaEntrevista'] != "") ? date(spanDateFormat, strtotime($row['fechaProximaEntrevista'])) : ""));
		
		// BUSCA LOS MODELOS DE INTERES
		$query = sprintf("SELECT 
			id_prospecto_vehiculo,
			id_cliente,
			id_unidad_basica,
			precio_unidad_basica,
			id_medio,
			id_nivel_interes,
			id_plan_pago
		FROM an_prospecto_vehiculo prosp_vehi
		WHERE id_cliente = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemModeloInteres($contFila, $row['id_prospecto_vehiculo']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjPieModeloInteres[] = $contFila;
			}
		}
		
		$sql_perfil_prospecto = "SELECT * FROM crm_perfil_prospecto WHERE id = $idCliente;";
		$query_perfil_prospecto = mysql_query($sql_perfil_prospecto);
		if (!$query_perfil_prospecto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$existe_perfil =mysql_num_rows($query_perfil_prospecto);
		$ros = mysql_fetch_array($query_perfil_prospecto);
			
		$id_puesto = $ros['id_puesto'];
		$id_titulo = $ros['id_titulo'];
		$id_sector = $ros['id_sector']; 
		$id_posibilidad_cierre = $ros['id_posibilidad_cierre'];
		$id_nivel_influencia = $ros['id_nivel_influencia'];
		$id_motivo_rechazo = $ros['id_motivo_rechazo'];
		$id_estatus = $ros['id_estatus'];
		$compania = $ros['compania'];
		$estado_civil = $ros['id_estado_civil'];
		$sexo = $ros['sexo'];
		$clase_social =$ros['clase_social'];
		$observacion = $ros['observacion'];
		
		$objResponse->loadCommands(cargaLstEstadoCivil($estado_civil));
		switch ($sexo) {
			case "F" : $objResponse->script("byId('rdbSexoF').checked = true;"); break;
			case "M" : $objResponse->script("byId('rdbSexoM').checked = true;"); break;
		}

		$objResponse->assign("txtCompania","value",utf8_encode($compania));
		$objResponse->assign("txtFechaNacimiento","value",(($ros['fecha_nacimiento'] != "") ? date(spanDateFormat,strtotime($ros['fecha_nacimiento'])) : ""));
		$objResponse->assign("txtObservacion","innerHTML",utf8_encode($observacion));
		$objResponse->script("selectedOption('lstNivelSocial', '".$clase_social."')");
		$objResponse->loadCommands(cargaLstPuesto($id_puesto));
		$objResponse->loadCommands(cargaLstTitulo($id_titulo));
		$objResponse->loadCommands(cargaLstSector($id_sector)); 
		$objResponse->loadCommands(cargaLstNivelInfluencia($id_nivel_influencia));
		$objResponse->loadCommands(cargaLstEstatus($id_estatus));
		$objResponse->loadCommands(cargaLstPosibilidadCierre($id_posibilidad_cierre));
		
		$queryPosibilidad = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE id_posibilidad_cierre = %s;",
			valTpDato($id_posibilidad_cierre, "int"));
		$rs = mysql_query($queryPosibilidad);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$motivo = $row['nombre_posibilidad_cierre'];
			
		$objResponse->loadCommands(cargaLstMotivoRechazo($motivo, $id_motivo_rechazo));
		
	} else {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","insertar")) { $objResponse->script("byId('btnCancelarProspecto').click();"); return $objResponse; }
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';");
		
		$objResponse->script("byId('lstTipoProspecto').onchange = function() { }");
		
		$objResponse->loadCommands(cargaLstEstadoCivil());
		$objResponse->loadCommands(cargaLstPuesto());
		$objResponse->loadCommands(cargaLstTitulo());
		$objResponse->loadCommands(cargaLstSector()); 
		$objResponse->loadCommands(cargaLstNivelInfluencia());
		$objResponse->loadCommands(cargaLstEstatus());
		$objResponse->loadCommands(cargaLstPosibilidadCierre());
		$objResponse->loadCommands(cargaLstMotivoRechazo($motivo));
	}
	
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

function guardarDcto($frmDcto, $bloquearForm = "false", $hddIdPresupuestoVenta = "") {
	$objResponse = new xajaxResponse();
	
	$frmListaArticulo = $frmDcto;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieArticulo = $frmListaArticulo['cbxPieArticulo'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	sort($frmDcto['cbxPieAdicional']);
	$arrayObjPieAdicional = $frmDcto['cbxPieAdicional'];
	
	$idPresupuesto = (intval($hddIdPresupuestoVenta) > 0) ? intval($hddIdPresupuestoVenta) : $frmDcto['hddIdPresupuestoVenta'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	$idUnidadBasica = $frmDcto['txtIdUnidadBasica'];
	
	$txtFechaTasaCambio = ($frmDcto['txtFechaTasaCambio'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaTasaCambio'])) : "";
	
	$txtFechaCuotaFinanciar = ($frmDcto['txtFechaCuotaFinanciar'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar'])) : "";
	$txtFechaCuotaFinanciar2 = ($frmDcto['txtFechaCuotaFinanciar2'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar2'])) : "";
	$txtFechaCuotaFinanciar3 = ($frmDcto['txtFechaCuotaFinanciar3'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar3'])) : "";
	$txtFechaCuotaFinanciar4 = ($frmDcto['txtFechaCuotaFinanciar4'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar4'])) : "";
	
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
	
	if ($idPresupuesto > 0) {
		if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","editar")) { return $objResponse; }
		
		// INSERTA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_presupuesto SET 
			id_empresa = %s,
			id_cliente = %s,
			id_moneda = %s,
			id_moneda_tasa_cambio = %s,
			id_tasa_cambio = %s,
			monto_tasa_cambio = %s,
			fecha_tasa_cambio = %s,
			id_clave_movimiento = %s,
			fecha = %s,
			asesor_ventas = %s,
			estado = %s,
			id_uni_bas = %s,
			precio_venta = %s,
			monto_descuento = %s,
			porcentaje_iva = %s,
			porcentaje_impuesto_lujo = %s,
			tipo_inicial = %s,
			porcentaje_inicial = %s,
			monto_inicial = %s,
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
			
			total_accesorio = %s,
			total_adicional_contrato = %s,
			total_inicial_gastos = %s,
			porcentaje_flat = %s,
			monto_flat = %s,
			observacion = %s,
			anticipo = %s,
			
			id_poliza = %s,
			monto_seguro = %s,
			periodo_poliza = %s,
			inicial_poliza = %s,
			meses_poliza = %s,
			cuotas_poliza = %s,
			contado_poliza = %s,
			
			total_general = %s,
			exacc1 = %s,
			exacc2 = %s,
			exacc3 = %s,
			exacc4 = %s,
			vexacc1 = %s,
			vexacc2 = %s,
			vexacc3 = %s,
			vexacc4 = %s,
			empresa_accesorio = %s
		WHERE id_presupuesto = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($frmDcto['hddIdMoneda'], "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"),
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa"),
			valTpDato($txtFechaTasaCambio, "date"),
			valTpDato($frmDcto['lstClaveMovimiento'], "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date"),
			valTpDato($frmDcto['txtIdEmpleado'], "int"),
			valTpDato(0, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
			valTpDato($idUnidadBasica, "int"),
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
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa"),
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa"),
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa"),
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa"),
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa"),
			valTpDato($frmDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa"),
			
			valTpDato($frmDcto['lstPoliza'], "int"),
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa"),
			valTpDato($frmDcto['txtPeriodoPoliza'], "text"),
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa"),
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa"),
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa"),
			valTpDato($frmDcto['txtContadoPoliza'], "real_inglesa"),
		
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
			
			valTpDato($idPresupuesto, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
	} else {
		if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","insertar")) { return $objResponse; }
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(38, "int"), // 38 = Presupuesto Venta Vehículos
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
		$insertSQL = "INSERT INTO an_presupuesto (numeracion_presupuesto, id_empresa, id_cliente, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, fecha_tasa_cambio, id_clave_movimiento, fecha, asesor_ventas, estado, id_uni_bas, precio_venta, monto_descuento, porcentaje_iva, porcentaje_impuesto_lujo, tipo_inicial, porcentaje_inicial, monto_inicial, monto_cash_back, id_banco_financiar, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, fecha_pago_cuota, meses_financiar2, interes_cuota_financiar2, cuotas_financiar2, fecha_pago_cuota2, total_accesorio, total_adicional_contrato, total_inicial_gastos, porcentaje_flat, monto_flat, observacion, anticipo, id_poliza, monto_seguro, periodo_poliza, inicial_poliza, meses_poliza, cuotas_poliza, contado_poliza, total_general, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4, empresa_accesorio)
		VALUES (".valTpDato($numeroActual, "text").", ".
			valTpDato($idEmpresa, "int").", ".
			valTpDato($idCliente, "int").", ".
			valTpDato($frmDcto['hddIdMoneda'], "int").", ".
			valTpDato($frmDcto['lstMoneda'], "int").", ".
			valTpDato($frmDcto['lstTasaCambio'], "int").", ".
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa").", ".
			valTpDato($txtFechaTasaCambio, "date").", ".
			valTpDato($frmDcto['lstClaveMovimiento'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date").", ".
			valTpDato($frmDcto['txtIdEmpleado'], "int").", ".
			valTpDato(0, "int").", ". // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
			valTpDato($idUnidadBasica, "int").", ".
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
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtObservacion'], "text").", ".
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstPoliza'], "int").", ".
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPeriodoPoliza'], "text").", ".
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtContadoPoliza'], "real_inglesa").", ".
		
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
		$idPresupuesto = mysql_insert_id();
	}
	
	// INSERTA EL DETALLE DEL PRESUPUESTO
	if (isset($arrayObjPieArticulo)) {
		$frmListaArticulo = $frmDcto;
		foreach ($arrayObjPieArticulo as $indicePieArticulo => $valorPieArticulo) {
			$idPresupuestoDetalle = $frmListaArticulo['hddIdPresupuestoDet'.$valorPieArticulo];
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
				if ($idPresupuestoDetalle > 0) {
					$updateSQL = sprintf("UPDATE an_presupuesto_venta_detalle SET
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
					WHERE id_presupuesto_venta_detalle = %s;",
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
						valTpDato($idPresupuestoDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
						
					$arrayIdArticuloPresupuesto[] = $idPresupuestoDetalle;
				} else {
					$insertSQL = sprintf("INSERT INTO an_presupuesto_venta_detalle (id_presupuesto_venta, id_articulo, cantidad, pendiente, id_precio, precio_unitario, precio_sugerido, id_iva, iva, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idPresupuesto, "int"),
						valTpDato($idArticulo, "int"),
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
					$idPresupuestoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					$arrayIdArticuloPresupuesto[] = $idPresupuestoDetalle;
					
					$objResponse->assign("hddIdPresupuestoDet".$valorPieArticulo,"value",$idPresupuestoDetalle);
				}
				
				// ELIMINA LOS GASTOS DEL DETALLE DEL PRESUPUESTO
				$deleteSQL = sprintf("DELETE FROM an_presupuesto_venta_detalle_gastos WHERE id_presupuesto_venta_detalle = %s",
					valTpDato($idPresupuestoDetalle, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS GASTOS DEL DETALLE DEL PEDIDO
				for ($contFilaObj = 1; $contFilaObj <= 20; $contFilaObj++) {
					if (round($frmListaArticulo['txtMontoGastoArt:'.$valorPieArticulo.':'.$contFilaObj],2) > 0) {
						$insertSQL = sprintf("INSERT INTO an_presupuesto_venta_detalle_gastos (id_presupuesto_venta_detalle, id_gasto, monto_gasto)
						VALUE (%s, %s, %s);",
							valTpDato($idPresupuestoDetalle, "int"),
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
	if ($idPresupuesto > 0 || is_array($arrayIdArticuloPresupuesto)) {
		$deleteSQL = sprintf("DELETE FROM an_presupuesto_venta_detalle
		WHERE id_presupuesto_venta = %s
			AND (id_presupuesto_venta_detalle NOT IN (%s) OR %s = '-1');",
			valTpDato($idPresupuesto, "int"),
			valTpDato(implode(",",$arrayIdArticuloPresupuesto), "campo"),
			valTpDato(implode(",",$arrayIdArticuloPresupuesto), "text"));
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
						$updateSQL = sprintf("UPDATE an_paquete_presupuesto SET
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
						WHERE id_paquete_presupuesto = %s;", 
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
						
						$arrayIdPaquetePresupuesto[] = $hddIdDetItm;
					} else {
						$insertSQL = sprintf("INSERT INTO an_paquete_presupuesto (id_presupuesto, id_acc_paq, id_tipo_accesorio, precio_accesorio, costo_accesorio, porcentaje_iva_accesorio, iva_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idPresupuesto, "int"),
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
						
						$arrayIdPaquetePresupuesto[] = $hddIdDetItm;
					}
				} else {
					if ($hddIdDetItm > 0) {
						$updateSQL = sprintf("UPDATE an_accesorio_presupuesto SET
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
						WHERE id_accesorio_presupuesto = %s;",
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
						
						$arrayIdAdicionalPresupuesto[] = $hddIdDetItm;
					} else {
						$insertSQL = sprintf("INSERT INTO an_accesorio_presupuesto (id_presupuesto, id_accesorio, id_tipo_accesorio, precio_accesorio, costo_accesorio, porcentaje_iva_accesorio, iva_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idPresupuesto, "int"),
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
						
						$arrayIdAdicionalPresupuesto[] = $hddIdDetItm;
					}
				}
			}
		}
	}
	if ($idPresupuesto > 0 || is_array($arrayIdAdicionalPresupuesto)) {
		$deleteSQL = sprintf("DELETE FROM an_accesorio_presupuesto
		WHERE id_presupuesto = %s
			AND (id_accesorio_presupuesto NOT IN (%s) OR %s = '-1');",
			valTpDato($idPresupuesto, "int"),
			valTpDato(implode(",",$arrayIdAdicionalPresupuesto), "campo"),
			valTpDato(implode(",",$arrayIdAdicionalPresupuesto), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$deleteSQL);
	}
	if ($idPresupuesto > 0 || is_array($arrayIdPaquetePresupuesto)) {
		$deleteSQL = sprintf("DELETE FROM an_paquete_presupuesto
		WHERE id_presupuesto = %s
			AND (id_paquete_presupuesto NOT IN (%s) OR %s = '-1');",
			valTpDato($idPresupuesto, "int"),
			valTpDato(implode(",",$arrayIdPaquetePresupuesto), "campo"),
			valTpDato(implode(",",$arrayIdPaquetePresupuesto), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$deleteSQL);
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(("Presupuesto Guardado con Éxito"));
	
	$objResponse->script("
	cerrarVentana = true;
	window.location.href='an_ventas_presupuesto_editar.php?view=1&id=".$idPresupuesto."';");

	return $objResponse;
}

function guardarProspecto($frmProspecto, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieModeloInteres = $frmProspecto['cbxPieModeloInteres'];
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idProspecto = $frmProspecto['hddIdClienteProspecto'];
	
	if (!(count($arrayObjPieModeloInteres) > 0)) {
		return $objResponse->alert("Debe agregar un modelo de interés");
	}
	
	foreach ($arrayObjPieModeloInteres as $indicePieModeloInteres => $valorPieModeloInteres) {
		$objResponse->script("byId('txtPrecioUnidadBasicaItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		$objResponse->script("byId('lstMedioItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		$objResponse->script("byId('lstNivelInteresItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		$objResponse->script("byId('lstPlanPagoItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		
		if (!($frmProspecto['txtPrecioUnidadBasicaItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "txtPrecioUnidadBasicaItm".$valorPieModeloInteres; }
		if (!($frmProspecto['lstMedioItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "lstMedioItm".$valorPieModeloInteres; }
		if (!($frmProspecto['lstNivelInteresItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "lstNivelInteresItm".$valorPieModeloInteres; }
		if (!($frmProspecto['lstPlanPagoItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "lstPlanPagoItm".$valorPieModeloInteres; }
	}
	
	// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indice => $valor) {
				$objResponse->script("byId('".$valor."').className = 'inputCompletoErrado'");
			}
		}
		
		return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
	}
	
	if (($idProspecto > 0 && !xvalidaAcceso($objResponse,"an_prospecto_list","editar"))
	|| (!($idProspecto > 0) && !xvalidaAcceso($objResponse,"an_prospecto_list","insertar"))) { return $objResponse; }
	
	$objDcto = new ModeloProspecto;
	$objDcto->idEmpresa = $idEmpresa;
	$objDcto->idProspecto = $idProspecto;
	$objDcto->idEmpleado = $frmDcto['txtIdEmpleado'];
	$Result1 = $objDcto->guardarProspecto($frmProspecto);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	$idProspecto = $Result1['idProspecto'];
	$idPerfilProspecto = $Result1['idPerfilProspecto'];
	
	// INSERTA LOS MODELOS DE INTERES NUEVOS
	if (isset($arrayObjPieModeloInteres)) {
		foreach ($arrayObjPieModeloInteres as $indicePieModeloInteres => $valorPieModeloInteres) {
			if ($valorPieModeloInteres != "") {
				if ($frmProspecto['hddIdProspectoVehiculo'.$valorPieModeloInteres] > 0) {
					$updateSQL = sprintf("UPDATE an_prospecto_vehiculo SET
						precio_unidad_basica = %s,
						id_medio = %s,
						id_nivel_interes = %s,
						id_plan_pago = %s
					WHERE id_prospecto_vehiculo = %s;",
						valTpDato($frmProspecto['txtPrecioUnidadBasicaItm'.$valorPieModeloInteres], "real_inglesa"),
						valTpDato($frmProspecto['lstMedioItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstNivelInteresItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstPlanPagoItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['hddIdProspectoVehiculo'.$valorPieModeloInteres], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					$arrayIdProspectoVehiculo[] = $frmProspecto['hddIdProspectoVehiculo'.$valorPieModeloInteres];
				} else {
					$insertSQL = sprintf("INSERT INTO an_prospecto_vehiculo (id_cliente, id_unidad_basica, precio_unidad_basica, id_medio, id_nivel_interes, id_plan_pago)
					VALUE (%s, %s, %s, %s, %s, %s);", 
						valTpDato($idProspecto, "int"),
						valTpDato($frmProspecto['hddIdUnidadBasica'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['txtPrecioUnidadBasicaItm'.$valorPieModeloInteres], "real_inglesa"),
						valTpDato($frmProspecto['lstMedioItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstNivelInteresItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstPlanPagoItm'.$valorPieModeloInteres], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idProspectoVehiculo = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					$arrayIdProspectoVehiculo[] = $idProspectoVehiculo;
				}
				$idUnidadBasica = $frmProspecto['hddIdUnidadBasica'.$valorPieModeloInteres];
			}
		}
	}
	if ($idProspecto > 0 || is_array($arrayIdProspectoVehiculo)) {
		$deleteSQL = sprintf("DELETE FROM an_prospecto_vehiculo
		WHERE id_cliente = %s
			AND (id_prospecto_vehiculo NOT IN (%s) OR %s = '-1');",
			valTpDato($idProspecto, "int"),
			valTpDato(implode(",",$arrayIdProspectoVehiculo), "campo"),
			valTpDato(implode(",",$arrayIdProspectoVehiculo), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	$valores = "";
	if (isset($frmProspecto["checkDocuemento"])){
		foreach($frmProspecto["checkDocuemento"] as $indice => $valor){
			$valores .= $valor."<br>";
			$sqlChebox = sprintf("INSERT INTO crm_documentos_recaudados (id_perfil_prospecto, id_documento_venta)
			VALUES (%s,%s)",
				$idPerfilProspecto, 
				$valor);
			mysql_query("SET NAMES 'utf8'");
			$queryChebox = mysql_query($sqlChebox);				
			if (!$queryChebox) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1'");
		}
		
		$objResponse->alert("Fuente de Informacion guardada con éxito.");
	}

	$objResponse->assign("tdChebox","innerHTML",$valores);
	
	// VERIFICA SI TIENE LA EMPRESA AGREGADA
	$query = sprintf("SELECT * FROM cj_cc_cliente_empresa
	WHERE id_cliente = %s
		AND id_empresa = %s;",
		valTpDato($idProspecto, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	
	if ($totalRows == 0) {
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
		VALUE (%s, %s);",
			valTpDato($idProspecto, "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idClienteEmpresa = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Prospecto guardado con éxito.");
	
	if ($idPerfilProspecto > 0) {
		$objResponse->alert("Perfil del Prospecto guardado con éxito.");
	}
	
	$objResponse->script("byId('btnCancelarProspecto').click();");
	
	$objResponse->loadCommands(asignarCliente($idProspecto, $idEmpresa, $estatusCliente, $condicionPago, $rowPresupuesto['id_clave_movimiento']));
	
	if ($idUnidadBasica > 0 && count($arrayIdProspectoVehiculo) == 1) {
		$objResponse->loadCommands(asignarUnidadBasica($idUnidadBasica));
	}
	
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), 'false', 'true');");
	
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
		$htmlTh .= "<td></td>";
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
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblProspecto', '%s');\"><img class=\"puntero\" src=\"../img/iconos/user_edit.png\" title=\"Editar Prospecto\"/></a>",
					$contFila,
					$row['id']);
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
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFactor");
$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"asignarPoliza");
$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"asignarSinBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"bloquearLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"buscarAdicional");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasicaModelo");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCivil");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstatus");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"cargaLstMesesFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivoRechazo");
$xajax->register(XAJAX_FUNCTION,"cargaLstNivelInfluencia");
$xajax->register(XAJAX_FUNCTION,"cargaLstPoliza");
$xajax->register(XAJAX_FUNCTION,"cargaLstPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargaLstPuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstSector");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"cargaLstTitulo");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarAdicionalLote");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloLote");
$xajax->register(XAJAX_FUNCTION,"formProspecto");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion2");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarProspecto");
$xajax->register(XAJAX_FUNCTION,"insertarAdicional");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarModelo");
$xajax->register(XAJAX_FUNCTION,"insertarPaquete");
$xajax->register(XAJAX_FUNCTION,"listaAdicional");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaPaquete");
$xajax->register(XAJAX_FUNCTION,"listaUnidadBasica");
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

function cargaLstMedioItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'medios' AND status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
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

function cargaLstNivelInteresItm($nombreObjeto, $selId = "", $bloquearObj = false){
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$array = array(1 => "Bajo", 2 => "Medio", 3 => "Alto");
	$totalRows = count($array);
	
	$html .= "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstPlanPagoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'planesDePago' AND status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function insertarItemAdicional($contFila, $idPedidoAdicional = "", $idPresupuestoAdicional = "", $hddIdAdicionalItm = "", $hddIdAdicionalPaqueteItm = "", $txtPrecioConIvaItm = "", $hddCostoUnitarioItm = "", $txtPrecioPagadoItm = "", $hddPorcIvaItm = "", $hddAplicaIvaItm = "", $cbxCondicion = "", $lstMostrarItm = "", $lstMostrarPendienteItm = "", $hddTipoAdicional = "", $bloquearObj = false) {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPresupuestoAdicional > 0) {
		// BUSCA EL DETALLE
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
		WHERE acc_pres.id_accesorio_presupuesto = %s
		ORDER BY acc_pres.id_accesorio_presupuesto ASC;",
			valTpDato($idPresupuestoAdicional, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	}
	
	$estatusPedidoDet = ($estatusPedidoDet == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['estatus_accesorio_presupuesto'] : 0;
	
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
				$contFila, $contFila, $idPresupuestoAdicional,
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
	
	if ($idPresupuestoAdicional > 0) {
		// BUSCA EL DETALLE
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
		WHERE acc_pres.id_accesorio_presupuesto = %s
		ORDER BY acc_pres.id_accesorio_presupuesto ASC;",
			valTpDato($idPresupuestoAdicional, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	}
	
	$estatusPedidoDet = ($estatusPedidoDet == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['estatus_accesorio_presupuesto'] : 0;
	
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
				$contFila, $contFila, $idPresupuestoAdicional,
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

function insertarItemModeloInteres($contFila, $idProspectoVehiculo = "", $idUnidadBasica = "", $txtPrecioUnidadBasicaItm = "", $lstMedioItm = "", $lstNivelInteresItm = "", $lstPlanPagoItm = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idProspectoVehiculo > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryProspectoVehiculo = sprintf("SELECT 
			prospecto_veh.id_prospecto_vehiculo,
			prospecto_veh.id_cliente,
			prospecto_veh.id_unidad_basica,
			prospecto_veh.precio_unidad_basica,
			prospecto_veh.id_medio,
			prospecto_veh.id_plan_pago,
			prospecto_veh.id_nivel_interes
		FROM an_prospecto_vehiculo prospecto_veh
		WHERE prospecto_veh.id_prospecto_vehiculo = %s;",
			valTpDato($idProspectoVehiculo, "int"));
		$rsProspectoVehiculo = mysql_query($queryProspectoVehiculo);
		if (!$rsProspectoVehiculo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsProspectoVehiculo = mysql_num_rows($rsProspectoVehiculo);
		$rowProspectoVehiculo = mysql_fetch_assoc($rsProspectoVehiculo);
	}
	
	$idUnidadBasica = ($idUnidadBasica == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_unidad_basica'] : $idUnidadBasica;
	$txtPrecioUnidadBasicaItm = ($txtPrecioUnidadBasicaItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['precio_unidad_basica'] : $txtPrecioUnidadBasicaItm;
	$lstMedioItm = ($lstMedioItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_medio'] : $lstMedioItm;
	$lstNivelInteresItm = ($lstNivelInteresItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_nivel_interes'] : $lstNivelInteresItm;
	$lstPlanPagoItm = ($lstPlanPagoItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_plan_pago'] : $lstPlanPagoItm;
	
	// BUSCA LOS DATOS DE LA UNIDAD BASICA
	$query = sprintf("SELECT
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM vw_iv_modelos vw_iv_modelo
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPieModeloInteres').before('".
		"<tr id=\"trItmModeloInteres:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmModeloInteres:%s\"><input type=\"checkbox\" id=\"cbxItmModeloInteres\" name=\"cbxItmModeloInteres[]\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbxPieModeloInteres\" name=\"cbxPieModeloInteres[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioUnidadBasicaItm%s\" name=\"txtPrecioUnidadBasicaItm%s\" class=\"inputCompletoHabilitado\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdProspectoVehiculo%s\" name=\"hddIdProspectoVehiculo%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadBasica%s\" name=\"hddIdUnidadBasica%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtPrecioUnidadBasicaItm%s').onblur = function() {
			setFormatoRafk(this,2);
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['vehiculo']),
				$contFila, $contFila, number_format($txtPrecioUnidadBasicaItm, 2, ".", ","),
			cargaLstMedioItm("lstMedioItm".$contFila, $lstMedioItm),
			cargaLstNivelInteresItm("lstNivelInteresItm".$contFila, $lstNivelInteresItm),
			cargaLstPlanPagoItm("lstPlanPagoItm".$contFila, $lstPlanPagoItm),
				$contFila, $contFila, $idProspectoVehiculo,
				$contFila, $contFila, $idUnidadBasica,
			
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>