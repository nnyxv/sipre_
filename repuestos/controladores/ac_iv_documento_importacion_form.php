<?php


function asignarProveedor($idProveedor, $objDestino) {
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
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$objDestino,"value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRif".$objDestino,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$objDestino,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$objDestino,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$objDestino,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefono".$objDestino,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$objDestino,"value",$rowProvCredito['diascredito']);
		
		$objResponse->assign("rbtTipoPagoCredito".$objDestino,"checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito".$objDestino."').disabled = false;");
		
	} else {
		$objResponse->assign("txtDiasCredito".$objDestino,"value","0");
		
		$objResponse->assign("rbtTipoPagoContado".$objDestino,"checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito".$objDestino."').disabled = true;");
	}
	
	$objResponse->script("
	byId('btnCancelarListaProveedor').click();");
	
	return $objResponse;
}

function buscarArticuloFacturaGasto($frmBuscarArticuloCompra){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarArticuloCompra['txtCriterioBuscarArticuloCompra']);
	
	$objResponse->loadCommands(listaArticuloFacturaGasto(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarFacturaGasto($frmBuscarFacturaGasto, $frmFacturaGasto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmFacturaGasto['txtIdEmpresa'],
		$frmBuscarFacturaGasto['txtCriterioBuscarFacturaGasto']);
	
	$objResponse->loadCommands(listaFacturaGasto(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor'],
		$frmBuscarProveedor['hddObjDestino']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularFacturaGasto($frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; isset($frmFacturaGasto['txtIvaFacturaGasto'.$cont]); $cont++) {
		$objResponse->script(sprintf("
		fila = document.getElementById('trIvaFacturaGasto:%s');
		padre = fila.parentNode;
		padre.removeChild(fila);",
			$cont));
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmFacturaGasto['cbx'];
	if (isset($frmFacturaGasto['cbx'])) {
		$i = 0;
		foreach ($frmFacturaGasto['cbx'] as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArticuloFacturaGasto:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmArticuloFacturaGasto:".$valor,"innerHTML",$i);
		}
	}
	if (isset($arrayObj))
		$objResponse->assign("hddObjItmArticuloFacturaGasto","value",implode("|", $arrayObj));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmFacturaGasto['cbx1'];
	if (isset($frmFacturaGasto['cbx1'])) {
		$i = 0;
		foreach ($frmFacturaGasto['cbx1'] as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmFacturaGasto:".$valor,"className",$clase);
		}
	}
	if (isset($arrayObj1))
		$objResponse->assign("hddObjItmFacturaGasto","value",implode("|", $arrayObj1));
	
	$idEmpresa = $frmFacturaGasto['txtIdEmpresa'];
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO
	$subTotalFacturaGasto = 0;
	$exentoFacturaGasto = 0;
	$exoneradoFacturaGasto = 0;
	$arrayIva = NULL;
	$arrayDetalleIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			// BUSCA LOS DATOS DEL IMPUESTO
			$estatusIva = 1;
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmFacturaGasto['lstIvaItmFacturaGasto'.$valor], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$idIva = $rowIva['idIva'];
			$porcIva = $rowIva['iva'];
			
			$totalItmFacturaGasto = str_replace(",","",$frmFacturaGasto['txtCantItmFacturaGasto'.$valor]) * str_replace(",","",$frmFacturaGasto['txtCostoItmFacturaGasto'.$valor]);
			
			$totalItem = $totalItmFacturaGasto;
			$descuentoItem = (str_replace(",","",$frmFacturaGasto['txtPorcDescuentoFacturaGasto']) * $totalItem) / 100;
			$totalItem = $totalItem - $descuentoItem;
			
			if ($totalRowsIva == 0 || $estatusIva == 0) {
				$exentoFacturaGasto += $totalItem;
			} else {
				$ivaItem = ($totalItem * $porcIva) / 100;
					
				$existIva = false;
				if (isset($arrayIva)) {
					foreach ($arrayIva as $indiceIva => $valorIva) {
						if ($arrayIva[$indiceIva][0] == $frmFacturaGasto['lstIvaItmFacturaGasto'.$valor]) {
							$arrayIva[$indiceIva][1] += $totalItem;
							$arrayIva[$indiceIva][2] += $ivaItem;
							$existIva = true;
						}
					}
				}
				
				if ($idIva != "" && $existIva == false
				&& ($totalItmFacturaGasto - $descuentoItem) > 0) {
					$arrayDetalleIva[0] = $frmFacturaGasto['lstIvaItmFacturaGasto'.$valor];
					$arrayDetalleIva[1] = $totalItem;
					$arrayDetalleIva[2] = $ivaItem;
					$arrayDetalleIva[3] = $porcIva;
					$arrayIva[] = $arrayDetalleIva;
				}
			}
			
			$objResponse->assign("txtTotalItmArticuloFacturaGasto".$valor,"value",number_format($totalItmFacturaGasto, 2, ".", ","));
			
			$subTotalFacturaGasto += $totalItmFacturaGasto;
		}
	}
	
	// CREA LOS ELEMENTOS DE IVA
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($arrayIva[$indiceIva][0], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowIva = mysql_fetch_assoc($rsIva);
						
			if ($arrayIva[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIvaFacturaGasto:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIvaFacturaGasto:%s\">%s".
							"<input type=\"hidden\" id=\"hddIdIvaFacturaGasto%s\" name=\"hddIdIvaFacturaGasto%s\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIvaFacturaGasto%s\" name=\"txtBaseImpIvaFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIvaFacturaGasto%s\" name=\"txtIvaFacturaGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIvaFacturaGasto%s\" name=\"txtSubTotalIvaFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIvaFacturaGasto:%s');
					if(obj == undefined)
						$('#trTotalFacturaGasto').before(elemento);",
					$indiceIva,
						$indiceIva, utf8_encode($rowIva['observacion']),
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0],
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][1],2), 2, ".", ","),
						$indiceIva, $indiceIva, $rowIva['iva'], "%",
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][2],2), 2, ".", ","),
					
					$indiceIva));
			}
			
			$subTotalIva += round(doubleval($arrayIva[$indiceIva][2]), 2);
		}
	}
	
	$porcDescuentoFacturaGasto = str_replace(",","",$frmFacturaGasto['txtPorcDescuentoFacturaGasto']);
	$descuentoFacturaGasto = $subTotalFacturaGasto * ($porcDescuentoFacturaGasto / 100);
	$totalFacturaGasto = $subTotalFacturaGasto - $descuentoFacturaGasto + $subTotalIva;
	
	$objResponse->assign("txtSubTotalFacturaGasto","value",number_format($subTotalFacturaGasto, 2, ".", ","));
	$objResponse->assign("txtPorcDescuentoFacturaGasto","value",number_format($porcDescuentoFacturaGasto, 2, ".", ","));
	$objResponse->assign("txtDescuentoFacturaGasto","value",number_format($descuentoFacturaGasto, 2, ".", ","));	
	$objResponse->assign("txtTotalFacturaGasto","value",number_format($totalFacturaGasto, 2, ".", ","));
	$objResponse->assign("txtExentoFacturaGasto","value",number_format($exentoFacturaGasto, 2, ".", ","));
	$objResponse->assign("txtExoneradoFacturaGasto","value",number_format($exoneradoFacturaGasto, 2, ".", ","));
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice => $valor) {
			$totalDocumentosImportacion += str_replace(",","",$frmFacturaGasto['txtSubtotalFacturaGasto'.$valor]);
		}
	}
	$objResponse->assign("txtTotalDocumentosImportacion","value",number_format($totalDocumentosImportacion, 2, ".", ","));
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	if ($rowEmpresa['contribuyente_especial'] == 1 && count($arrayIva) > 0) {
		$objResponse->loadCommands(cargaLstRetencionImpuesto());
		$objResponse->script("
		byId('trRetencionIva').style.display = '';");
	} else {
		$objResponse->loadCommands(cargaLstRetencionImpuesto(0));
		$objResponse->script("
		byId('trRetencionIva').style.display = 'none';");
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

function cargaLstIvaItm($nombreObjeto, $selId = "") {
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputCompleto\">";
		
	$selected = "";
	if ($selId == 0 && $selId != "") {
		$selected = "selected=\"selected\"";
		$opt = "Si";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if ($selVal == $rowIva['iva'] && $selId == $rowIva['idIva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selVal == $rowIva['iva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selId == $rowIva['idIva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if (($rowIva['tipo'] == 1 && $rowIva['activo'] == 1) && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
			
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstRetencionImpuesto($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (5) AND iva.estado = 1 ORDER BY iva");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstRetencionImpuesto\" name=\"lstRetencionImpuesto\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= "<option ".(($selId == 0 && strlen($selId) > 0) ? "selected=\"selected\"" : "")." value=\"0\">".("Sin Retención")."</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['iva']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['iva']."\">".utf8_encode($row['observacion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstRetencionImpuesto","innerHTML",$html);
	
	return $objResponse;
}

function eliminarArticuloFacturaGasto($trItmArticuloFacturaGasto, $frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	if (isset($trItmArticuloFacturaGasto) && $trItmArticuloFacturaGasto > 0) {
		$objResponse->script(sprintf("
		fila = document.getElementById('trItmArticuloFacturaGasto:%s');
		padre = fila.parentNode;
		padre.removeChild(fila);",
			$trItmArticuloFacturaGasto));
			
		$objResponse->script("xajax_eliminarArticuloFacturaGasto('',xajax.getFormValues('frmFacturaGasto'));");
	}
	
	$objResponse->loadCommands(calcularFacturaGasto($frmFacturaGasto));
		
	return $objResponse;
}

function eliminarFacturaGasto($trItmFacturaGasto, $frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	if (isset($trItmFacturaGasto) && $trItmFacturaGasto > 0) {
		$objResponse->script(sprintf("
		fila = document.getElementById('trItmFacturaGasto:%s');
		padre = fila.parentNode;
		padre.removeChild(fila);",
			$trItmFacturaGasto));
			
		$objResponse->script("xajax_eliminarFacturaGasto('',xajax.getFormValues('frmFacturaGasto'));");
	}
	
	$objResponse->loadCommands(calcularFacturaGasto($frmFacturaGasto));
	
	return $objResponse;
}

function guardarFacturaGasto($frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_documento_importacion_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmFacturaGasto['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmFacturaGasto['cbx1'];
	
	$idFacturaGasto = $frmFacturaGasto['hddIdFacturaGasto'];
	$idEmpresa = $frmFacturaGasto['txtIdEmpresa'];
	$idModulo = 3; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idProveedor = $frmFacturaGasto['txtIdProvFacturaGasto'];
			
	$txtSubTotal = str_replace(",","",$frmFacturaGasto['txtSubTotalFacturaGasto']);
	$porcDescuentoFacturaGasto = str_replace(",","",$frmFacturaGasto['txtPorcDescuentoFacturaGasto']);
	$txtSubTotalDescuento = str_replace(",","",$frmFacturaGasto['txtDescuentoFacturaGasto']);
	$txtTotalOrden = str_replace(",","",$frmFacturaGasto['txtTotalFacturaGasto']);
	$txtTotalExento = str_replace(",","",$frmFacturaGasto['txtExentoFacturaGasto']);
	$txtTotalExonerado = str_replace(",","",$frmFacturaGasto['txtExoneradoFacturaGasto']);
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	$sinAlmacen = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$frmFacturaGasto['hddIdCasillaItmFacturaGasto'.$valor] = 4; // <-- AGREGADO POR DEFECTO PORQUE NO SE MANEJA LO DE LAS CASILLAS
			if ($valor > 0 && strlen($frmFacturaGasto['hddIdCasillaItmFacturaGasto'.$valor]) == "") {
				$sinAlmacen = true;
			}
		}
	}
	
	if ($sinAlmacen == false) {
		mysql_query("START TRANSACTION;");
		
		$insertSQL = sprintf("INSERT INTO cp_factura (id_empresa, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_modulo, id_pedido_compra, id_orden_compra, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, porcentaje_descuento, subtotal_descuento, saldo_factura, chasis, aplica_libros, activa, id_empleado_creador)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmFacturaGasto['txtNumeroFacturaGasto'], "text"),
			valTpDato($frmFacturaGasto['txtNumeroControlFacturaGasto'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmFacturaGasto['txtFechaFacturaGasto'])), "date"),
			valTpDato($idProveedor, "int"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato($idModulo, "int"),
			valTpDato("", "int"),
			valTpDato("", "int"),
			valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
			valTpDato($frmFacturaGasto['txtObservacionFacturaGasto'], "text"),
			valTpDato($frmFacturaGasto['lstTipoPagoFacturaGasto'], "int"), // 0 = Contado, 1 = Credito
			valTpDato($txtTotalExento, "real_inglesa"),
			valTpDato($txtTotalExonerado, "real_inglesa"),
			valTpDato($txtSubTotal, "real_inglesa"),
			valTpDato($porcDescuentoFacturaGasto, "real_inglesa"),
			valTpDato($txtSubTotalDescuento, "real_inglesa"),
			valTpDato($txtTotalOrden, "real_inglesa"),
			valTpDato("", "text"),
			valTpDato(1, "int"), // 0 = No, 1 = Si
			valTpDato(1, "int"), // Null = Anulada, 1 = Activa
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idFacturaImportacion = mysql_insert_id();
		
		$arrayDetIdDctoContabilidad[0] = $idFacturaImportacion;
		$arrayDetIdDctoContabilidad[1] = $idModulo;
		$arrayDetIdDctoContabilidad[2] = "COMPRA";
		$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
			
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$idArticulo = $frmFacturaGasto['hddIdArticuloItmFacturaGasto'.$valor];
				$cantPedida = str_replace(",", "", $frmFacturaGasto['txtCantItmFacturaGasto'.$valor]);
				$cantRecibida = $cantPedida;
				$cantPendiente = $cantPedida - $cantRecibida;
				$costoUnitario = str_replace(",", "", $frmFacturaGasto['txtCostoItmFacturaGasto'.$valor]);
				$descuentoUnitario = ($porcDescuentoFacturaGasto * $costoUnitario) / 100;
				$idIva = $frmFacturaGasto['lstIvaItmFacturaGasto'.$valor];
				
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", $idIva);
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowIva = mysql_fetch_assoc($rsIva);
				
				$porcIva = $rowIva['iva'];
				$idCasilla = $frmFacturaGasto['hddIdCasillaItmFacturaGasto'.$valor];
				
				// VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa
				WHERE id_empresa = %s
					AND id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				mysql_query("SET NAMES 'utf8';");
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				mysql_query("SET NAMES 'latin1';");
				// SI NO ESTA REGISTRADO, LO REGISTRA
				if ($rowArtEmp['id_articulo_empresa'] == "") {
					$insertSQL = sprintf("INSERT INTO ga_articulos_empresa (id_empresa, id_articulo, clasificacion)
					VALUE (%s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato("F", "text"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
				
				// VERIFICA SI EL ARTICULO YA ESTA REGISTRADO EN DICHA UBICACION
				$queryArtAlmacen = sprintf("SELECT * FROM vw_ga_articulos_almacen
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				$rsArtAlmacen = mysql_query($queryArtAlmacen);
				if (!$rsArtAlmacen) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
				// SI NO ESTA REGISTRADO, LO REGISTRA
				if ($rowArtAlmacen['id_articulo_almacen'] == "") {
					$insertSQL = sprintf("INSERT INTO ga_articulos_almacen (id_casilla, id_articulo) VALUE (%s, %s);",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
				
				// VERIFICA SI EL ARTICULO TIENE UNA UBICACION PREDETERMINADA EN UN ALMACEN DE LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa
				WHERE id_empresa = %s
					AND id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				// SI NO ESTA REGISTRADO, LO REGISTRA
				if ($rowArtEmp['id_casilla_predeterminada'] == "") {
					$updateSQL = sprintf("UPDATE ga_articulos_empresa SET
						id_casilla_predeterminada = %s 
					WHERE id_articulo_empresa = %s;",
						valTpDato($idCasilla, "int"),
						valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
				
				// BUSCA LOS DATOS DEL ARTICULO PARA LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa
				WHERE id_empresa = %s
					AND id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				
				$idCasilla = $rowArtEmp['id_casilla_predeterminada'];
				
				$insertSQL = sprintf("INSERT INTO cp_factura_detalle (id_factura, id_pedido_compra, id_articulo, id_casilla, cantidad, pendiente, precio_unitario, tipo_descuento, porcentaje_descuento, subtotal_descuento, id_iva, iva, tipo, id_cliente, estatus, por_distribuir)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFacturaImportacion, "int"),
					valTpDato("", "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($cantPedida, "real_inglesa"),
					valTpDato($cantPendiente, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "int"), // 0 = Porcentaje, 1 = Monto Fijo
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($idIva, "int"),
					valTpDato($porcIva, "real_inglesa"),
					valTpDato(0, "int"), // 0 = Reposicion, 1 = Cliente
					valTpDato("", "int"), 
					valTpDato(0, "int"), // 0 = En Espera, 1 = Recibido
					valTpDato(0, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// REGISTRA EL COSTO DE COMPRA DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO ga_articulos_costos (id_proveedor, id_articulo, precio, fecha)
				VALUE (%s, %s, %s, %s);",
					valTpDato($idProveedor, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato(($costoUnitario - $descuentoUnitario), "real_inglesa"),
					valTpDato(date("Y-m-d"), "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO ga_kardex (id_documento, id_articulo, id_casilla, tipo_movimiento, cantidad, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, SYSDATE());",
					valTpDato($idFacturaImportacion, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato(1, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($cantRecibida, "real_inglesa"),
					valTpDato($frmFacturaGasto['lstClaveMovimientoFacturaGasto'], "int"),
					valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
					valTpDato(date("Y-m-d"), "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA LOS PRECIOS DE LOS ARTICULOS
				$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio NOT IN (6,7) AND precio.estatus = 1 ORDER BY precio.id_precio ASC;");
				$rsPrecio = mysql_query($queryPrecio);
				if (!$rsPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
					$queryArtPrecio = sprintf("SELECT * FROM ga_articulos_precios
					WHERE id_articulo = %s
						AND id_precio = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($rowPrecio['id_precio'], "int"));
					$rsArtPrecio = mysql_query($queryArtPrecio);
					if (!$rsArtPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
					
					if ($rowPrecio['tipo'] == 0) { // PRECIO SOBRE COSTO
						$montoGanancia = (($costoUnitario - $descuentoUnitario) * ($rowPrecio['porcentaje'] / 100)) + ($costoUnitario - $descuentoUnitario);
					} else if ($rowPrecio['tipo'] == 1) { // PRECIO SOBRE VENTA
						$montoGanancia = (($costoUnitario - $descuentoUnitario) * 100) / ( 100 - $rowPrecio['porcentaje']);
					}
					
	
					if ($rowArtPrecio['id_articulo_precio'] == "") {
						$insertSQL = sprintf("INSERT INTO ga_articulos_precios (id_articulo, id_precio, precio)
						VALUE (%s, %s, %s);",
							valTpDato($idArticulo, "int"),
							valTpDato($rowPrecio['id_precio'], "int"),
							valTpDato($montoGanancia, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					} else {
						$updateSQL = sprintf("UPDATE ga_articulos_precios SET
							precio = %s
						WHERE id_articulo_precio = %s;",
							valTpDato($montoGanancia, "real_inglesa"),
							valTpDato($rowArtPrecio['id_articulo_precio'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS)
				$updateSQL = sprintf("UPDATE ga_articulos_almacen SET
					cantidad_entrada = (SELECT SUM(kardex.cantidad)
						FROM ga_kardex kardex
						WHERE kardex.tipo_movimiento IN (1,2)
							AND kardex.id_articulo = ga_articulos_almacen.id_articulo
							AND kardex.id_casilla = ga_articulos_almacen.id_casilla)
				WHERE (ga_articulos_almacen.id_articulo = %s
						OR ga_articulos_almacen.id_articulo = %s)
					AND (ga_articulos_almacen.id_casilla = %s
						OR ga_articulos_almacen.id_casilla = %s);",
					valTpDato($idArticulo, "int"),
					valTpDato($idArticuloOrg, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($idCasillaPredet, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				$totalItmFacturaGasto = $cantRecibida * $costoUnitario;
				
				$totalItem = $totalItmFacturaGasto;
				$descuentoItem = ($porcDescuentoFacturaGasto * $totalItem) / 100;
				$totalItem = $totalItem - $descuentoItem;
				
				if (($idIva == 0
					&& ($idIva == "" || $idIva == "-"))
				|| $porcIva == 0) {
					$exentoFacturaGasto += $totalItem;
				} else {
					$ivaItem = ($totalItem * $porcIva) / 100;
						
					$existIva = false;
					if (isset($arrayIva)) {
						foreach ($arrayIva as $indiceIva => $valorIva) {
							if ($arrayIva[$indiceIva][0] == $idIva) {
								$arrayIva[$indiceIva][1] += $totalItem;
								$arrayIva[$indiceIva][2] += $ivaItem;
								$existIva = true;
							}
						}
					}
					
					if ($idIva != "" && $existIva == false
					&& ($totalItmFacturaGasto - $descuentoItem) > 0) {
						$arrayDetalleIva[0] = $idIva;
						$arrayDetalleIva[1] = $totalItem;
						$arrayDetalleIva[2] = $ivaItem;
						$arrayDetalleIva[3] = $porcIva;
						$arrayIva[] = $arrayDetalleIva;
					}
				}
			}
		}
		
		// INSERTA LOS IVA DEL PEDIDO
		if (isset($arrayIva)) {
			foreach ($arrayIva as $indiceIva => $valorIva) {
				if ($arrayIva[$indiceIva][2] > 0) {
					$insertSQL = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaImportacion, "int"),
						valTpDato($arrayIva[$indiceIva][1], "real_inglesa"),
						valTpDato($arrayIva[$indiceIva][2], "real_inglesa"),
						valTpDato($arrayIva[$indiceIva][0], "int"),
						valTpDato($arrayIva[$indiceIva][3], "real_inglesa"),
						valTpDato($frmFacturaGasto['hddLujoIva'.$cont], "boolean"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("FA", "text"),
			valTpDato($idFacturaImportacion, "int"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		
		// INSERTA EL MOVIMIENTO
		$insertSQL = sprintf("INSERT INTO ga_movimiento (id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, ultima_partida_usada, id_usuario, credito, id_moneda_extranjera_doc, id_moneda_extranjera_doc_cambio, id_moneda_extranjera_ref, id_moneda_extranjera_ref_cambio)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmFacturaGasto['lstClaveMovimientoFacturaGasto'], "int"),
			valTpDato($idFacturaImportacion, "int"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato($idProveedor, "int"),
			valTpDato(0, "boolean"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato("", "int"),
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato($frmFacturaGasto['lstTipoPagoFacturaGasto'], "int"), // 0 = Contado, 1 = Credito
			valTpDato("", "int"),
			valTpDato("", "int"),
			valTpDato("", "int"),
			valTpDato("", "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idMovimiento = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL DETALLE DEL MOVIMIENTO
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$idArticulo = $frmFacturaGasto['hddIdArticuloItmFacturaGasto'.$valor];
				$cantPedida = str_replace(",", "", $frmFacturaGasto['txtCantItmFacturaGasto'.$valor]);
				$cantRecibida = $cantPedida;
				$cantPendiente = $cantPedida - $cantRecibida;
				$gastoUnitario = $frmFacturaGasto['hddGastosArt'.$valor] / $cantRecibida;
				$costoUnitario = str_replace(",", "", $frmFacturaGasto['txtCostoItmFacturaGasto'.$valor]);
				$costoUnitarioAcumulado = $costoUnitario + $gastoUnitario;
				$descuentoUnitario = ($porcDescuentoFacturaGasto * $costoUnitarioAcumulado) / 100;
				$costoUnitarioAcumuladoConDescuento = $costoUnitarioAcumulado - $descuentoUnitario;
				$idIva = $frmFacturaGasto['lstIvaItmFacturaGasto'.$valor];
				
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", $idIva);
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowIva = mysql_fetch_assoc($rsIva);
				
				$iva = $rowIva['iva'];
				$idCasilla = $frmFacturaGasto['hddIdCasillaItmFacturaGasto'.$valor];
				
				if (strlen($idArticulo) > 0) {
					$insertSQL = sprintf("INSERT INTO ga_movimiento_detalle (id_movimiento, id_articulo, cantidad, precio, costo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($costoUnitarioAcumulado, "real_inglesa"),
						valTpDato($costoUnitarioAcumulado, "real_inglesa"),
						valTpDato($porcDescuentoFacturaGasto, "real_inglesa"),
						valTpDato((($porcDescuentoFacturaGasto * ($cantRecibida * $costoUnitarioAcumulado)) / 100), "real_inglesa"),
						valTpDato(0, "int"),
						valTpDato(0, "boolean"),
						valTpDato("", "int"),
						valTpDato("", "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
				
		// CREACION DE LA RETENCION DEL IMPUESTO
		if ($frmFacturaGasto['lstRetencionImpuesto'] > 0
		&& $txtTotalExento + $txtTotalExonerado != $txtTotalOrden) {
			// NUMERACION DEL DOCUMENTO
			$queryNumeracion = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE emp_num.id_numeracion = %s
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato(2, "int"), // 2 = Comprobante Retenciones
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
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$insertSQL = sprintf("INSERT INTO cp_retencioncabezera (id_empresa, numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idProveedor)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato(date("Y"), "int"),
				valTpDato(date("m"), "int"),
				valTpDato($idProveedor, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idRetencionCabezera = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		
			$porcRetencion = $frmFacturaGasto['lstRetencionImpuesto'];
			
			$comprasSinIva = $txtTotalExento + $txtTotalExonerado;
			
			// RECORRE LOS IVA DEL PEDIDO PARA CREARLE SU RETENCION
			if (isset($arrayIva)) {
				foreach ($arrayIva as $indiceIva => $valorIva) {
					if ($arrayIva[$indiceIva][2] > 0) {
						$ivaRetenido = round((doubleval($porcRetencion) * str_replace(",","",$arrayIva[$indiceIva][2])) / 100, 2);
						
						$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idRetencionCabezera, "int"),
							valTpDato(date("Y-m-d", strtotime($frmFacturaGasto['txtFechaFacturaGasto'.$hddItmGasto])), "date"),
							valTpDato($idFacturaImportacion, "int"),
							valTpDato($frmFacturaGasto['txtNumeroControlFacturaGasto'.$hddItmGasto], "text"),
							valTpDato(" ", "text"),
							valTpDato(" ", "text"),
							valTpDato("01", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
							valTpDato(" ", "text"), // CUANDO ES NOTA DE CREDITO O DE DEBITO
							valTpDato($txtTotalOrden, "real_inglesa"),
							valTpDato($comprasSinIva, "double"),
							valTpDato($arrayIva[$indiceIva][1], "real_inglesa"),
							valTpDato($arrayIva[$indiceIva][3], "real_inglesa"),
							valTpDato($arrayIva[$indiceIva][2], "real_inglesa"),
							valTpDato($ivaRetenido, "real_inglesa"),
							valTpDato($porcRetencion, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// INSERTA EL PAGO DEBIDO A LA RETENCION
						$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
						VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idFacturaImportacion, "int"),
							valTpDato("FA", "text"),
							valTpDato("RETENCION", "text"),
							valTpDato($idRetencionCabezera, "int"),
							valTpDato(date("Y-m-d"), "date"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
							valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
							valTpDato("-", "text"),
							valTpDato("-", "text"),
							valTpDato("-", "text"),
							valTpDato("-", "text"),
							valTpDato($ivaRetenido, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// ACTUALIZA EL SALDO DE LA FACTURA
						$updateSQL = sprintf("UPDATE cp_factura SET
							saldo_factura = (saldo_factura - %s)
						WHERE id_factura = %s;",
							valTpDato($ivaRetenido, "real_inglesa"),
							valTpDato($idFacturaImportacion, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					}
				}
			}
		} else if ($frmFacturaGasto['lstRetencionImpuesto'] > 0
		&& $txtTotalExento + $txtTotalExonerado == $txtTotalOrden) {
			return $objResponse->alert("Este Registro No Posee Impuesto(s) para Aplicar(les) Retención, Por Favor Verifique la Opción de Retención Seleccionada"); 
		}
		
		if ($idFacturaGasto > 0) {
			// INSERTA LOS GASTOS DE IMPORTACION DE LA FACTURA
			$insertSQL = sprintf("UPDATE cp_factura_gasto SET
				id_factura_compra_cargo = %s,
				id_condicion_gasto = %s
			WHERE id_factura_gasto = %s;",
				valTpDato($idFacturaImportacion, "int"),
				valTpDato(1, "int"), // 1 = Real, 2 = Estimado
				valTpDato($idFacturaGasto, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else if (isset($arrayObj1)){
			foreach ($arrayObj1 as $indiceItm => $valorItm) {
				$idFacturaGasto = $frmFacturaGasto['hddIdFacturaGasto'.$valorItm];
				
				// INSERTA LOS GASTOS DE IMPORTACION DE LA FACTURA
				$updateSQL = sprintf("UPDATE cp_factura_gasto SET
					id_factura_compra_cargo = %s,
					id_condicion_gasto = %s
				WHERE id_factura_gasto = %s;",
					valTpDato($idFacturaImportacion, "int"),
					valTpDato(1, "int"), // 1 = Real, 2 = Estimado
					valTpDato($idFacturaGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		} else {
			return $objResponse->alert("La factura no tiene documentos(s) de importación asignado(s)");
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Registro de Compra Guardado con Éxito");
		
		$comprobanteRetencion = ($frmFacturaGasto['rbtRetencion'] == 1) ? 0 : 1;
		
		$objResponse->script("verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=".$idFacturaImportacion."', 900, 700);");
		$objResponse->script("window.location.href='iv_documento_importacion_list.php';");
	
		if (isset($arrayIdDctoContabilidad)) {
			foreach ($arrayIdDctoContabilidad as $indice => $valor) {
				$idModulo = $arrayIdDctoContabilidad[$indice][1];
				$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
				
				// MODIFICADO ERNESTO
				if ($tipoDcto == "COMPRA") {
					$idFactura = $arrayIdDctoContabilidad[$indice][0];
					switch ($idModulo) {
						case 0 : if (function_exists("generarComprasRe")) { generarComprasRe($idFactura,"",""); } break;
						case 1 : if (function_exists("generarComprasSe")) { generarComprasSe($idFactura,"",""); } break;
						case 2 : if (function_exists("generarComprasVe")) { generarComprasVe($idFactura,"",""); } break;
						case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idFactura,"",""); } break;
					}
				}
				// MODIFICADO ERNESTO
			}
		}
	} else {
		$objResponse->alert("Existen Artículos Los Cuales No Tienen Una Ubicación Asignada");
	}
	
	return $objResponse;
}

function insertarArticuloFacturaGasto($idArticulo, $frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmFacturaGasto['cbx'];
	$sigValor = $arrayObj[count($arrayObj)-1];
	
	$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$sigValor++;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$query = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s;", valTpDato($idArticulo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO SIN INJECT
	$objResponse->script(sprintf("$('#trItmArticuloFacturaGasto').before('".
		"<tr align=\"left\" id=\"trItmArticuloFacturaGasto:%s\" class=\"textoGris_11px %s\">".
			"<td id=\"tdNumItmArticuloFacturaGasto:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtCantItmFacturaGasto%s\" name=\"txtCantItmFacturaGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCostoItmFacturaGasto%s\" name=\"txtCostoItmFacturaGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItmArticuloFacturaGasto%s\" name=\"txtTotalItmArticuloFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td align=\"center\" title=\"trItmArticuloFacturaGasto:%s\"><a id=\"aEliminar:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdArticuloItmFacturaGasto%s\" name=\"hddIdArticuloItmFacturaGasto%s\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');
		
		byId('txtCantItmFacturaGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));
		}
		byId('txtCostoItmFacturaGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));
		}
		byId('lstIvaItmFacturaGasto%s').onchange = function() {
			xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));
		}
		byId('aEliminar:%s').onclick = function() {
			xajax_eliminarArticuloFacturaGasto('%s', xajax.getFormValues('frmFacturaGasto'));
		}",
		$sigValor, $clase,
			$sigValor, $sigValor,
			elimCaracter($row['codigo_articulo'],";"),
			preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($row['descripcion']))),
			$sigValor, $sigValor, number_format(0, 2, ".", ","),
			$sigValor, $sigValor, number_format(0, 2, ".", ","),
			cargaLstIvaItm("lstIvaItmFacturaGasto".$sigValor),
			$sigValor, $sigValor, number_format(0, 2, ".", ","),
			$sigValor, $sigValor,
				$sigValor,
				$sigValor, $sigValor, $row['id_articulo'],
		
		$sigValor,
		$sigValor,
		$sigValor,
		$sigValor,
			$sigValor));
	
	$objResponse->script("xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));");
	
	return $objResponse;
}

function insertarFacturaGasto($idFacturaGasto, $frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmFacturaGasto['cbx1'];
	$sigValor = $arrayObj[count($arrayObj)-1];
	
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmFacturaGasto['hddIdFacturaGasto'.$valor] == $idFacturaGasto) {
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$sigValor++;
		
		// BUSCA LOS DATOS DEL GASTO DE IMPORTACION
		$query = sprintf("SELECT 
			fact_comp_gasto.id_factura_gasto,
			fact_comp.fecha_origen,
			fact_comp.numero_factura_proveedor,
			prov.nombre AS nombre_proveedor,
			gasto.nombre,
			fact_comp_gasto.monto
		FROM cp_factura_gasto fact_comp_gasto
			INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
			INNER JOIN cp_factura fact_comp ON (fact_comp_gasto.id_factura = fact_comp.id_factura)
			INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		WHERE id_factura_gasto = %s;",
			valTpDato($idFacturaGasto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		// INSERTA EL ARTICULO SIN INJECT
		$objResponse->script(sprintf("$('#trItmFacturaGasto').before('".
			"<tr align=\"left\" id=\"trItmFacturaGasto:%s\" class=\"%s\">".
				"<td align=\"right\">%s</td>".
				"<td>%s</td>".
				"<td>%s</td>".
				"<td><input type=\"text\" id=\"txtSubtotalFacturaGasto%s\" name=\"txtSubtotalFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
				"<td align=\"center\" title=\"trItmFacturaGasto:%s\"><a id=\"aEliminarItemFacturaGasto:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
					"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
					"<input type=\"hidden\" id=\"hddIdFacturaGasto%s\" name=\"hddIdFacturaGasto%s\" readonly=\"readonly\" value=\"%s\"></td>".
			"</tr>');
			
			byId('aEliminarItemFacturaGasto:%s').onclick = function() {
				xajax_eliminarFacturaGasto('%s', xajax.getFormValues('frmFacturaGasto'));
			}",
			$sigValor, $clase,
				utf8_encode($row['numero_factura_proveedor']),
				utf8_encode($row['nombre_proveedor']),
				utf8_encode($row['nombre']),
				$sigValor, $sigValor, utf8_encode(number_format($row['monto'],2,".",",")),
				$sigValor, $sigValor,
					$sigValor,
					$sigValor, $sigValor, $idFacturaGasto,
			
			$sigValor,
				$sigValor));
	} else {
		$objResponse->alert("Este item ya se encuentra incluido");
	}
	
	$objResponse->script("xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));");
	
	return $objResponse;
}

function listaArticuloFacturaGasto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_articulo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_articulo LIKE %s
		OR descripcion LIKE %s
		OR tipo_articulo LIKE %s
		OR tipo_compra LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_ga_articulos %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaArticuloFacturaGasto", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaArticuloFacturaGasto", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArticuloFacturaGasto", "20%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Artículo");
		$htmlTh .= ordenarCampo("xajax_listaArticuloFacturaGasto", "20%", $pageNum, "tipo_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Compra");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticuloFacturaGasto%s\" onclick=\"validarInsertarArticuloFacturaGasto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_articulo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_articulo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_compra'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloFacturaGasto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaArticuloCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFacturaGasto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp_gasto.id_condicion_gasto = 2");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp_gasto.id_modo_gasto = 2");
	
	/*if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_comp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}*/
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_comp.numero_factura_proveedor LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		fact_comp_gasto.id_factura_gasto,
		fact_comp.fecha_origen,
		fact_comp.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		gasto.nombre,
		fact_comp_gasto.monto
	FROM cp_factura_gasto fact_comp_gasto
		INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
		INNER JOIN cp_factura fact_comp ON (fact_comp_gasto.id_factura = fact_comp.id_factura)
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaFacturaGasto", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Reg. Compra");
		$htmlTh .= ordenarCampo("xajax_listaFacturaGasto", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaGasto", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaGasto", "48%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Gasto");
		$htmlTh .= ordenarCampo("xajax_listaFacturaGasto", "10%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Subtotal");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarFacturaGasto%s\" onclick=\"validarInsertarFacturaGasto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_factura_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaGasto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaFacturaGasto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[1]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function nuevoDcto($hddIdFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmFacturaGasto'].reset();
	byId('hddIdFacturaGasto').value = '';
	
	byId('txtIdProvFacturaGasto').className = 'inputHabilitado';
	byId('txtNumeroFacturaGasto').className = 'inputHabilitado';
	byId('txtNumeroControlFacturaGasto').className = 'inputHabilitado';
	byId('txtFechaFacturaGasto').className = 'inputHabilitado';
	byId('lstTipoPagoFacturaGasto').className = 'inputHabilitado';
	byId('txtObservacionFacturaGasto').className = 'inputHabilitado';
	byId('txtPorcDescuentoFacturaGasto').className = 'inputHabilitado';");
	
	if ($hddIdFacturaGasto > 0) {
		// BUSCA LOS DATOS DE LA FACTURA DEL GASTO
		$query = sprintf("SELECT
			fact_comp.id_empresa,
			gasto.nombre,
			fact_comp_gasto.id_factura_compra_cargo,
			fact_comp_import.fecha_origen,
			fact_comp_import.numero_factura_proveedor,
			fact_comp_import.numero_control_factura,
			prov.nombre AS nombre_proveedor,
			fact_comp.id_modulo,
			fact_comp_gasto.monto,
			fact_comp_gasto.id_condicion_gasto
		FROM cp_factura_gasto fact_comp_gasto
			INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
			LEFT JOIN cp_factura fact_comp_import ON (fact_comp_gasto.id_factura_compra_cargo = fact_comp_import.id_factura)
			LEFT JOIN cp_proveedor prov ON (fact_comp_import.id_proveedor = prov.id_proveedor)
			INNER JOIN cp_factura fact_comp ON (fact_comp_gasto.id_factura = fact_comp.id_factura)
		WHERE fact_comp_gasto.id_factura_gasto = %s;",
			valTpDato($hddIdFacturaGasto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$idEmpresa = $row['id_empresa'];
	} else {
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
	WHERE inv_fis.id_empresa = %s
		AND inv_fis.estatus = 0;",
		valTpDato($idEmpresa , "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsInvFis = mysql_num_rows($rsInvFis);
	
	if (($totalRowsInvFis == 0 && $row['id_modulo'] == 0) || $row['id_modulo'] != 0) {
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa"));
		
		if ($hddIdFacturaGasto > 0) {
			$objResponse->assign("txtNombreGasto","value",utf8_encode($row['nombre']));
			$objResponse->assign("txtSubTotal","value",number_format($row['monto'], 2, ".", ","));
			
			$objResponse->assign("hddIdFacturaGasto","value",$hddIdFacturaGasto);
			
			$objResponse->script("
			byId('trDatosGastoImportacion').style.display = '';
			byId('fieldsetFacturaGasto').style.display = 'none';");
		} else {
			$objResponse->script("
			byId('trDatosGastoImportacion').style.display = 'none';
			byId('fieldsetFacturaGasto').style.display = '';");
		}
		
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoFacturaGasto", "3", "1"));
		
		$objResponse->script("xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));");
	} else {
		$objResponse->script("
		alert('Usted no puede Registrar Compras, debido a que está en Proceso un Inventario Físico');
		location='iv_registro_compra_list.php';");
	}

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarArticuloFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"buscarFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"calcularFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstIvaItm");
$xajax->register(XAJAX_FUNCTION,"cargaLstRetencionImpuesto");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"eliminarFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"guardarFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"insertarFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"listaArticuloFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"listaFacturaGasto");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"nuevoDcto");
?>