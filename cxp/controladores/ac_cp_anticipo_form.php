<?php


function asignarProveedor($idProveedor, $objDestino, $asigDescuento = true) {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$objDestino,"value",htmlentities($rowProv['nombre_proveedor']));
	$objResponse->assign("txtRif".$objDestino,"value",htmlentities($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$objDestino,"innerHTML",htmlentities($rowProv['direccion']));
	$objResponse->assign("txtContacto".$objDestino,"value",htmlentities($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$objDestino,"value",htmlentities($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$objDestino,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$objDestino,"value",$rowProvCredito['diascredito']);
		
		$objResponse->call("selectedOption","lstTipoPago",1);
	} else {
		$objResponse->assign("txtDiasCredito".$objDestino,"value","0");
		
		$objResponse->call("selectedOption","lstTipoPago",0);
	}
	
	$objResponse->script("
	byId('btnCancelarListaProveedor').click();");
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $objDestino) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_motivo
	WHERE id_motivo = %s
		AND modulo LIKE 'CP'
		AND ingreso_egreso LIKE 'I';",
		valTpDato($idMotivo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtId".$objDestino,"value",$row['id_motivo']);
	$objResponse->assign("txt".$objDestino,"value",htmlentities($row['descripcion']));
	
	$objResponse->script("
	byId('btnCancelarListaMotivo').click();");
	
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

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['hddObjDestinoProveedor'],
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valorItm,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);
		}
	}
	if (isset($arrayObj))
		$objResponse->assign("hddObj","value",implode("|", $arrayObj));
	
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// SUMA LOS PAGOS
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalPago += str_replace(",","",$frmListaPagoDcto['txtMontoPago'.$valor]);
		}
	}
	
	$txtSubTotal = round(str_replace(",","",$frmTotalDcto['txtSubTotal']),2);
	$txtDescuento = round(str_replace(",","",$frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = $txtSubTotal * $txtDescuento / 100;
	$txtTotalExento = round(str_replace(",","",$frmTotalDcto['txtTotalExento']),2);
	$txtTotalExonerado = round(str_replace(",","",$frmTotalDcto['txtTotalExonerado']),2);
	
	if (isset($frmTotalDcto['cbxIva'])) {
		foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
			// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO
			$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 AND iva.idIva = %s ORDER BY iva",
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBaseImpIva = str_replace(",","",$frmTotalDcto['txtBaseImpIva'.$valor]);
			
			$txtIva = str_replace(",","",$frmTotalDcto['txtIva'.$valor]);
			$txtSubTotalIva = $txtBaseImpIva * $txtIva / 100;
			
			$objResponse->assign("txtSubTotalIva".$valor,"value",number_format($txtSubTotalIva, 2, ".", ","));
			
			$totalSubtotalIva += $txtSubTotalIva;
			
			// BUSCA LA BASE IMPONIBLE MAYOR
			if ($totalRows > 0 && $txtBaseImpIva > 0) {
				$txtBaseImpIvaVenta = $txtBaseImpIva;
			}
		}
	}
	
	$totalDcto = $txtSubTotal - $txtSubTotalDescuento + $totalSubtotalIva;
	$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaVenta;
	
	$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	$objResponse->assign("txtTotalPedido","value",number_format($totalDcto, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".htmlentities($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function formNotaCredito($idNotaCredito, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idNotaCredito > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarProv').style.display = 'none';
		byId('txtIdProv').readOnly = true;
		byId('txtIdProv').className = 'inputInicial';
		byId('txtNumeroNotaCredito').readOnly = true;
		byId('txtNumeroNotaCredito').className = 'inputInicial';
		byId('txtNumeroControl').readOnly = true;
		byId('txtNumeroControl').className = 'inputInicial';
		byId('txtFechaNotaCredito').readOnly = true;
		byId('txtFechaNotaCredito').className = 'inputInicial';
		byId('lstTipoPago').className = 'inputInicial';
		byId('lstAplicaLibro').className = 'inputInicial';
		byId('aListarMotivo').style.display = 'none';
		byId('txtIdMotivo').readOnly = true;
		byId('txtIdMotivo').className = 'inputInicial';
		byId('txtObservacion').readOnly = true;
		byId('txtObservacion').className = 'inputInicial';
		
		byId('txtSubTotal').readOnly = true;
		byId('txtSubTotal').className = 'inputSinFondo';
		byId('txtSubTotal').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtDescuento').readOnly = true;
		byId('txtDescuento').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtTotalExento').readOnly = true;
		byId('txtTotalExento').className = 'inputSinFondo';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtTotalExonerado').readOnly = true;
		byId('txtTotalExonerado').className = 'inputSinFondo';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
		}
		
		byId('trListaPagoDcto').style.display = 'none';
		
		byId('btnGuardar').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
		$queryNotaCredito = sprintf("SELECT * FROM cp_notacredito
		WHERE id_notacredito = %s;",
			valTpDato($idNotaCredito, "text"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCredito = mysql_fetch_assoc($rsNotaCredito);
		
		if ($_GET['vw'] != "v") {
			$objResponse->script("
			byId('btnGuardar').style.display = '';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCredito['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarProveedor($rowNotaCredito['id_proveedor'], 'Prov', false));
		
		$objResponse->assign("txtFecha","value",date(spanDateFormat, strtotime($rowNotaCredito['fecha_registro_notacredito'])));
		$objResponse->assign("txtIdNotaCredito","value",$rowNotaCredito['id_notacredito']);
		$objResponse->assign("txtNumeroNotaCredito","value",$rowNotaCredito['numero_nota_credito']);
		$objResponse->assign("txtNumeroControl","value",$rowNotaCredito['numero_control_notacredito']);
		$objResponse->assign("txtFechaNotaCredito","value",date(spanDateFormat, strtotime($rowNotaCredito['fecha_notacredito'])));
		$objResponse->call("selectedOption","lstTipoPago",-1);
		$objResponse->loadCommands(cargaLstModulo($rowNotaCredito['id_departamento_notacredito'], true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCredito['aplica_libros_notacredito']);
		$objResponse->loadCommands(asignarMotivo($rowNotaCredito['id_motivo'],"Motivo"));
		$objResponse->assign("txtObservacion","value",$rowNotaCredito['observacion_notacredito']);
		
		$objResponse->script(sprintf("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'%s');
		}",
			$rowNotaCredito['aplica_libros_notacredito']));
		
		$objResponse->script(sprintf("
		byId('lstTipoPago').onchange = function() {
			selectedOption(this.id,'%s');
		}",
			-1));
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT
			nota_credito_iva.id_notacredito_iva,
			nota_credito_iva.id_notacredito,
			nota_credito_iva.baseimponible_notacredito,
			nota_credito_iva.subtotal_iva_notacredito,
			nota_credito_iva.id_iva_notacredito,
			nota_credito_iva.iva_notacredito,
			iva.observacion
		FROM cp_notacredito_iva nota_credito_iva
			INNER JOIN pg_iva iva ON (nota_credito_iva.id_iva_notacredito = iva.idIva)
		WHERE nota_credito_iva.id_notacredito = %s
		ORDER BY iva",
			valTpDato($idNotaCredito, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indice = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indice++;
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">I.V.A.:<br><div class=\"textoNegrita_10px\">(%s)</div>".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);",
				$indice,
					$indice, htmlentities($rowIva['observacion']),
						$indice, $indice, $rowIva['id_iva_notacredito'],
						$indice,
					$indice, $indice, number_format(round($rowIva['baseimponible_notacredito'],2), 2, ".", ","),
					$indice, $indice, $rowIva['iva_notacredito'], "%",
					$indice, $indice, number_format(round($rowIva['subtotal_iva_notacredito'],2), 2, ".", ","),
				
				$indice,
				
				$indice,
				
				$indice));
		}
		
		$porcDescuento = $rowNotaCredito['subtotal_descuento'] * 100 / $rowNotaCredito['subtotal_notacredito'];
		$objResponse->assign("txtSubTotal","value",number_format($rowNotaCredito['subtotal_notacredito'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCredito['subtotal_descuento'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowNotaCredito['monto_exento_notacredito'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowNotaCredito['monto_exonerado_notacredito'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCredito['saldo_notacredito'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		/*$query = sprintf("SELECT * FROM cp_pagos_documentos pago_dcto
		WHERE tipo_documento_pago LIKE 'ND'
			AND id_documento_pago = %s;",
			valTpDato($idNotaCredito, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemMetodoPago($contFila, $row['id_pago']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}*/
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdProv').className = 'inputHabilitado';
		byId('txtNumeroNotaCredito').className = 'inputHabilitado';
		byId('txtNumeroControl').className = 'inputHabilitado';
		byId('txtFechaNotaCredito').className = 'inputHabilitado';
		byId('lstTipoPago').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtIdMotivo').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtSubTotal').className = 'inputHabilitado';
		byId('txtSubTotal').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtDescuento').className = 'inputHabilitado';
		byId('txtDescuento').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('trListaPagoDcto').style.display = 'none';");
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indiceIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indiceIva++;
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">I.V.A.:<br><div class=\"textoNegrita_10px\">(%s)</div>".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputHabilitado\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);
				
				byId('txtBaseImpIva%s').onblur = function() {
					setFormatoRafk(this,2);
					xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$indiceIva,
					$indiceIva, htmlentities($rowIva['observacion']),
						$indiceIva, $indiceIva, $rowIva['idIva'],
						$indiceIva,
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
					$indiceIva, $indiceIva, $rowIva['iva'], "%",
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
				
				$indiceIva,
				
				$indiceIva,
				
				$indiceIva));
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->assign("txtTotalSaldo","value",number_format(0, 2, ".", ","));
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
		
		$objResponse->script("
		window.onload = function(){
			jQuery(function($){
				$(\"#txtFechaNotaCredito\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFechaNotaCredito\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"torqoise\"
			});
		};");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx'];
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idNotaCredito = $frmDcto['txtIdNotaCredito'];
	$idMotivo = $frmDcto['txtIdMotivo'];
	$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
	$precioUnitario = $frmTotalDcto['txtSubTotal'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCredito > 0) {
	} else {
		$queryProv = sprintf("SELECT prov.credito, prov_cred.*
		FROM cp_proveedor prov
			LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
		WHERE prov.id_proveedor = %s;",
			valTpDato($frmDcto['txtIdProv'], "int"));
		$rsProv = mysql_query($queryProv);
		if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProv = mysql_fetch_assoc($rsProv);
		
		$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
		
		// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
		$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas(spanDateFormat,$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
		
		// INSERTAR LOS DATOS DE LA NOTA DE CRÉDITO
		$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, fecha_registro_notacredito, id_proveedor, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, subtotal_descuento, saldo_notacredito, aplica_libros_notacredito, id_empleado_creador)
		VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmDcto['txtNumeroNotaCredito'], "text"),
			valTpDato($frmDcto['txtNumeroControl'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato($frmDcto['lstModulo'], "int"),
			valTpDato(0, "int"),
			valTpDato('NC', "text"),
			valTpDato(1, "int"), // 0 = No Cancelado, 1 = Sin Asignar, 2 = Asignado Parcial, 3 = Asignado
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalPedido'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCredito = mysql_insert_id();
		
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
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
					$insertSQL = sprintf("INSERT INTO cp_notacredito_iva (id_notacredito, baseimponible_notacredito, subtotal_iva_notacredito, id_iva_notacredito, iva_notacredito)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idNotaCredito, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("NC", "text"),
			valTpDato($idNotaCredito, "int"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
		
	$objResponse->assign("txtIdNotaCredito","value",$idNotaCredito);
	
	$objResponse->alert("Nota de Crédito Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['rbtRetencion'] == 1) ? 0 : 1;
	
	$objResponse->script(sprintf("window.location.href='cp_transacciones_diarias_documentos_notacredito_inicio.php';"));
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CP'
	AND ingreso_egreso LIKE 'I'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"));
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "90%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
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
			$htmlTb .= "<td>";
				$htmlTb .= "<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>";
			$htmlTb .= "</td>";
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
		$htmlTf .= "<td align=\"center\" colspan=\"3\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"3\">";
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

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"formNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
?>