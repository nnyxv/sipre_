<?php


function asignarClaveMovimiento($frmDcto, $idClaveMovimiento) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$Result1 = buscarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->assign("txtNumeroControlNotaCredito","value",($Result1[1]));
	}
	
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; isset($frmTotalDcto['txtIva'.$cont]); $cont++) {
		$objResponse->script("
		fila = document.getElementById('trIva:".$cont."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DE LA FACTURA)
	if (isset($frmListaArticulo['cbxPieDetalle']) && isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = array_merge($frmListaArticulo['cbxPieDetalle'], $frmTotalDcto['cbxPieDetalle']);
	} else if (isset($frmListaArticulo['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	} else if (isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmTotalDcto['cbxPieDetalle'];
	}
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle_".$valorPieDetalle,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle_".$valorPieDetalle,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObjPieDetalle) > 0) ? implode("|",$arrayObjPieDetalle) : ""));
			
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (IMPUESTO DEL DETALLE)
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdFactura'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas moneda
	WHERE moneda.estatus = 1
		AND moneda.predeterminada = 1;");
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if (isset($frmListaArticulo['cbxPieDetalle']) && in_array($valorPieDetalle, $frmListaArticulo['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbxPieDetalle']) && in_array($valorPieDetalle, $frmTotalDcto['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$txtCantRecibItm = str_replace(",", "", $frmListaArticuloAux['txtCantItm'.$valorPieDetalle]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]);
			$hddCostoItm = str_replace(",", "", $frmListaArticuloAux['hddCostoItm'.$valorPieDetalle]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticuloAux['hddMontoDescuentoItm'.$valorPieDetalle]);
			$txtTotalItm = $txtCantRecibItm * $txtPrecioItm;
			
			if ((in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAccesorioItm'.$valorPieDetalle], array(1)))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(3))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(4))) {
				$txtSubTotal += $txtTotalItm;
				$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
			} else if (in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAccesorioItm'.$valorPieDetalle], array(2,3))) {
				$txtTotalAdicionalOtro += $txtTotalItm;
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if (isset($frmListaArticulo['cbxPieDetalle']) && in_array($valorPieDetalle, $frmListaArticulo['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbxPieDetalle']) && in_array($valorPieDetalle, $frmTotalDcto['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$txtCantRecibItm = str_replace(",", "", $frmListaArticuloAux['txtCantItm'.$valorPieDetalle]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]);
			$hddCostoItm = str_replace(",", "", $frmListaArticuloAux['hddCostoItm'.$valorPieDetalle]);
			$txtTotalItm = $txtCantRecibItm * $txtPrecioItm;
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticuloAux['hddTotalDescuentoItm'.$valorPieDetalle]);
			
			$hddTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					
					if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
						$arrayPosIvaItm[$frmListaArticuloAux['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]]] = $valorIvaItm[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
					}
				}
			}
			
			if ((in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAccesorioItm'.$valorPieDetalle], array(1)))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(3))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(4))) {
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$queryIva);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticuloAux['hddEstatusIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						$txtTotalExento += $txtTotalNetoItm;
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ?  str_replace(",", "", $frmListaArticuloAux['hddIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
			}
			
			$objResponse->assign("txtTotalItm".$valorPieDetalle, "value", number_format($txtTotalItm, 2, ".", ","));
			$objResponse->assign("txtTotalConImpuestoItm".$valorPieDetalle, "value", number_format(($txtTotalItm + $subTotalIvaItm), 2, ".", ","));
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach($arrayIva as $indiceIva => $valorIva) {
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
			
			$subTotalIva += doubleval($arrayIva[$indiceIva][2]);
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIvaLocal)) {
		foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
			if ($arrayIvaLocal[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIvaLocal:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIvaLocal:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIvaLocal%s\" name=\"hddIdIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIvaLocal%s\" name=\"hddLujoIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIvaLocal\" name=\"cbxIvaLocal[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIvaLocal%s\" name=\"txtBaseImpIvaLocal%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIvaLocal%s\" name=\"txtIvaLocal%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIvaLocal%s\" name=\"txtSubTotalIvaLocal%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIvaLocal:%s');
					if (obj == undefined)
						$('#trRetencionIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIvaLocal[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format(round($arrayIvaLocal[$indiceIva][1], 2), 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format(round($arrayIvaLocal[$indiceIva][2], 2), 2, ".", ","), 
						
					$indiceIva));
			}
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
	
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalAdicionalOtro", "value", number_format($txtTotalAdicionalOtro, 2, ".", ","));
	
	$objResponse->assign("txtGastosConIva", "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva", "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalOrden, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format($txtTotalOrden, 2, ".", ","));
	
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

function cargarDcto($idFactura){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$queryDcto = sprintf("SELECT 
		cxc_fact.id_empresa,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.numeroPedido,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaVencimientoFactura,
		cxc_fact.fecha_pagada,
		cxc_fact.fecha_cierre,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cxc_fact.idCliente AS id_cliente,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		cxc_fact.observacionFactura,
		cxc_fact.subtotalFactura AS subtotal_factura,
		cxc_fact.porcentaje_descuento,
		cxc_fact.descuentoFactura AS subtotal_descuento,
		cxc_fact.baseImponible AS base_imponible,
		cxc_fact.porcentajeIvaFactura AS porcentaje_iva,
		cxc_fact.calculoIvaFactura AS subtotal_iva,
		cxc_fact.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
		cxc_fact.calculoIvaDeLujoFactura AS subtotal_iva_lujo,
		cxc_fact.montoExento AS monto_exento,
		cxc_fact.montoExonerado AS monto_exonerado,
		cxc_fact.condicionDePago,
		cxc_fact.montoTotalFactura,
		cxc_fact.diasDeCredito,
		an_ped_vent.id_pedido,
		an_ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN pg_clave_movimiento clave_mov ON (cxc_fact.id_clave_movimiento = clave_mov.id_clave_movimiento)
		INNER JOIN pg_empleado empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_fact.idFactura = %s
		AND cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($idFactura, "int"),
		valTpDato($idModuloPpal, "campo"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_array($rsDcto);
		
	$idEmpresa = $rowDcto['id_empresa'];
	$idCliente = $rowDcto['id_cliente'];
	$idCondicionPago = $rowDcto['condicionDePago'];
	$idClaveMovimiento = $rowDcto['id_clave_movimiento'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
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
		valTpDato($idCliente, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
		} else {
			$objResponse->assign("txtDiasCreditoCliente","value","0");
		}
	}
	
	$nombreCondicionPago = ($idCondicionPago == 0) ? "CRÉDITO" : "CONTADO";
	$objResponse->assign("hddTipoPago","value",$idCondicionPago);
	$objResponse->assign("txtTipoPago","value",$nombreCondicionPago);
	
	// DATOS DE LA NOTA DE CREDITO
	$objResponse->assign("txtFechaNotaCredito","value",date(spanDateFormat));
	$objResponse->call("selectedOption","lstTipoClave",2);
	$objResponse->script("byId('lstTipoClave').onchange = function(){ selectedOption(this.id,'".(2)."'); };");
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", "2", $idCondicionPago, "3", "", "onchange=\"xajax_asignarClaveMovimiento(xajax.getFormValues('frmDcto'), this.value);\""));
	
	// DATOS DEL CLIENTE
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	// DATOS DE LA FACTURA
	$objResponse->assign("txtIdEmpresa","value",$rowDcto['id_empresa']);
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowDcto['nombre_empresa']));
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtFechaFactura","value",date(spanDateFormat, strtotime($rowDcto['fechaRegistroFactura'])));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat, strtotime($rowDcto['fechaVencimientoFactura'])));
	$objResponse->assign("txtNumeroFactura","value",$rowDcto['numeroFactura']);
	$objResponse->assign("txtNumeroControlFactura","value",$rowDcto['numeroControl']);
	$objResponse->assign("txtIdPresupuesto","value",$rowDcto['id_presupuesto']);
	$objResponse->assign("txtNumeroPresupuesto","value",$rowDcto['numeracion_presupuesto']);
	$objResponse->assign("txtIdPedido","value",$rowDcto['id_pedido']);
	$objResponse->assign("txtNumeroPedido","value",$rowDcto['numeracion_pedido']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowDcto['nombre_empleado']));
	$objResponse->assign("txtTipoClaveFactura","value","3.- VENTA");
	$objResponse->assign("hddIdClaveMovimientoFactura","value",$rowDcto['id_clave_movimiento']);
	$objResponse->assign("txtClaveMovimientoFactura","value",utf8_encode($rowDcto['descripcion_clave_movimiento']));
	
	$objResponse->assign("txtSubTotal","value",number_format($rowDcto['subtotal_factura'], 2, ".", ","));
	$objResponse->assign("txtDescuento","value",number_format($rowDcto['porcentaje_descuento'], 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($rowDcto['subtotal_descuento'], 2, ".", ","));
	$objResponse->assign("txtGastosConIva","value",number_format(0, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva","value",number_format(0, 2, ".", ","));
	$objResponse->assign("txtTotalExento","value",number_format($rowDcto['monto_exento'], 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($rowDcto['monto_exonerado'], 2, ".", ","));
	$objResponse->assign("txtTotalOrden","value",number_format($rowDcto['montoTotalFactura'], 2, ".", ","));
	
	$queryVehiculo = sprintf("SELECT
		cxc_fact_det_vehic.id_factura_detalle_vehiculo,
		cxc_fact_det_vehic.id_unidad_fisica,
		cxc_fact_det_vehic.costo_compra,
		uni_fis.id_unidad_fisica,
		uni_bas.nom_uni_bas,
		marca.nom_marca,
		modelo.nom_modelo,
		version.nom_version,
		uni_fis.placa,
		ano.nom_ano,
		uni_fis.serial_chasis,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		color_externo.nom_color AS color_externo,
		color_interno.nom_color AS color_interno,
		uni_bas.com_uni_bas,
		uni_fis.codigo_unico_conversion,
		uni_fis.marca_kit,
		uni_fis.marca_cilindro,
		uni_fis.modelo_regulador,
		uni_fis.serial1,
		uni_fis.serial_regulador,
		uni_fis.capacidad_cilindro,
		uni_fis.fecha_elaboracion_cilindro,
		uni_fis.registro_legalizacion,
		uni_fis.registro_federal,
		cxc_fact_det_vehic.precio_unitario,
		uni_fis.costo_compra,
		uni_bas.isan_uni_bas,
		uni_fis.estado_venta
	FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		INNER JOIN an_version version ON (uni_bas.ver_uni_bas = version.id_version)
		INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		INNER JOIN an_color color_externo ON (uni_fis.id_color_externo1 = color_externo.id_color)
		INNER JOIN an_color color_interno ON (uni_fis.id_color_interno1 = color_interno.id_color)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
	WHERE id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsVehiculo = mysql_query($queryVehiculo);
	if (!$rsVehiculo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsVehiculo = mysql_num_rows($rsVehiculo);
	$rowVehiculo = mysql_fetch_array($rsVehiculo);
	if ($totalRowsVehiculo > 0) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// BUSCA LOS IMPUESTOS DEL DETALLE
		$queryFacturaDetVehicImpuesto = sprintf("SELECT * FROM cj_cc_factura_detalle_vehiculo_impuesto WHERE id_factura_detalle_vehiculo = %s;",
			valTpDato($rowVehiculo['id_factura_detalle_vehiculo'], "int"));
		$rsFacturaDetVehicImpuesto = mysql_query($queryFacturaDetVehicImpuesto);
		if (!$rsFacturaDetVehicImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFacturaDetVehicImpuesto = mysql_num_rows($rsFacturaDetVehicImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowFacturaDetVehicImpuesto = mysql_fetch_assoc($rsFacturaDetVehicImpuesto)) {
			$arrayIdIvaItm[] = $rowFacturaDetVehicImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
		
		$hddIdItm = $rowVehiculo['id_unidad_fisica'];
		$hddTpItm = 3;
		$divCodigoItm = $rowVehiculo['nom_uni_bas'];
		$txtPrecioItm = $rowVehiculo['precio_unitario'];
		$hddCostoItm = $rowVehiculo['costo_compra'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
		WHERE iva.tipo IN (6,9,2)
			AND iva.idIva IN (%s);",
			valTpDato($hddIdIvaItm, "campo"));
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
				$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
				$contFila.":".$contIva);
		}
		
		$divDescripcionItm = "<table width=\"100%\">";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Marca:</td>"."<td width=\"30%\">".$rowVehiculo['nom_marca']."</td>";
			$divDescripcionItm .= "<td width=\"20%\"></td>"."<td width=\"30%\"></td>";
		$divDescripcionItm .= "</tr>";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Modelo:")."</td>"."<td>".$rowVehiculo['nom_modelo']."</td>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Versión:")."</td>"."<td>".$rowVehiculo['nom_version']."</td>";
		$divDescripcionItm .= "</tr>";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Año:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['nom_ano']."</td>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Placa:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['placa']."</td>";
		$divDescripcionItm .= "</tr>";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Serial Carroceria:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['serial_carroceria']."</td>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Serial Motor:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['serial_motor']."</td>";
		$divDescripcionItm .= "</tr>";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Nro. Vehículo:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['serial_chasis']."</td>";
		$divDescripcionItm .= "</tr>";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Color Carroceria:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['color_externo']."</td>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Tipo Tapiceria:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['color_interno']."</td>";
		$divDescripcionItm .= "</tr>";
		$divDescripcionItm .= "<tr>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Registro Legalización:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['registro_legalizacion']."</td>";
			$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">".utf8_decode("Registro Federal:")."</td>";
			$divDescripcionItm .= "<td>".$rowVehiculo['registro_federal']."</td>";
		$divDescripcionItm .= "</tr>";
		if (in_array($rowVehiculo['com_uni_bas'],array(2,5))) {
			$divDescripcionItm .= "<tr><td align=\"center\" class=\"tituloArea\" colspan=\"4\">SISTEMA GNV</td></tr>";
			$divDescripcionItm .= "<tr>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Serial 1:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['serial1']."</td>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Código Único:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['codigo_unico_conversion']."</td>";
			$divDescripcionItm .= "</tr>";
			$divDescripcionItm .= "<tr>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Marca Kit:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['marca_kit']."</td>";
			$divDescripcionItm .= "</tr>";
			$divDescripcionItm .= "<tr>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Modelo Regulador:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['modelo_regulador']."</td>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Serial Regulador:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['serial_regulador']."</td>";
			$divDescripcionItm .= "</tr>";
			$divDescripcionItm .= "<tr>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Marca Cilindro:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['marca_cilindro']."</td>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Capacidad Cilindro (NG):</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['capacidad_cilindro']."</td>";
			$divDescripcionItm .= "</tr>";
			$divDescripcionItm .= "<tr>";
				$divDescripcionItm .= "<td align=\"right\" class=\"tituloCampo\">Fecha Elab. Cilindro:</td>";
				$divDescripcionItm .= "<td>".$rowVehiculo['fecha_elaboracion_cilindro']."</td>";
			$divDescripcionItm .= "</tr>";
		}
		$divDescripcionItm .= "</table>";
		
		$objResponse->script(sprintf(
		"$('#trItmPieDetalle').before('".
			"<tr align=\"left\" id=\"trItmDetalle_%s\" class=\"textoGris_11px %s\">".
				"<td title=\"trItmDetalle_%s\">".
					"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td align=\"center\" class=\"textoNegrita_9px\">%s</td>".
				"<td><div id=\"divCodigoItm%s\">%s</div></td>".
				"<td><div id=\"divDescripcionItm%s\">%s</div></td>".
				"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
				"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				"<td><div id=\"divIvaItm%s\">%s</div></td>".
				"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"</tr>');",
			$contFila, $clase,
				$contFila,
					$contFila,
				$contFila,
				$contFila, $divCodigoItm,
				$contFila, utf8_encode($divDescripcionItm),
				$contFila, $contFila, number_format(1, 2, ".", ","), 
				$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","),
					$contFila, $contFila, $hddCostoItm,
				$contFila, $ivaUnidad,
				$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","),
					$contFila, $contFila, $hddIdPedidoDet,
					$contFila, $contFila, $hddIdItm,
					$contFila, $contFila, $hddTpItm));
	}
	
	// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
	$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = %s
		AND (fact_vent_det.cantidad - fact_vent_det.devuelto) > 0;",
		valTpDato($idFactura, "int"));
	$rsFacturaDet = mysql_query($queryFacturaDet);
	if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaDet = mysql_num_rows($rsFacturaDet);
	if ($totalRowsFacturaDet > 0) {
		$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
		WHERE inv_fis.id_empresa = %s
			AND inv_fis.estatus = 0",
			valTpDato($idEmpresa , "int"));
		$rsInvFis = mysql_query($queryInvFis);
		if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsInvFis = mysql_num_rows($rsInvFis);
		if ($totalRowsInvFis > 0) {
			return $objResponse->script("
			alert('Usted no puede Aprobar Devoluciones de Venta, debido a que está en Proceso un Inventario Físico');
			if (top.history.back()) { top.history.back(); } else { location='cj_devolucion_venta_list.php'; }");
		}
		
		while ($rowFacturaDet = mysql_fetch_assoc($rsFacturaDet)) {
			$contFila++;
			
			$hddIdItm = $rowFacturaDet['id_articulo'];
			$hddTpItm = 4;
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
			
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_venta_det
			WHERE ped_venta_det.id_pedido_venta = %s
				AND ped_venta_det.id_articulo = %s;",
				valTpDato($idPedido, "int"),
				valTpDato($idArticulo, "int"));
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
					"<td title=\"trItmDetalle_%s\">".
						"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td id=\"tdNumItmDetalle_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
					"<td id=\"tdCodArt:%s\" class=\"%s\">%s</td>".
					"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
						"<span id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</span>".
						"%s</td>".
					"<td style=\"display:none\"><input type=\"text\" id=\"txtCantFactItm%s\" name=\"txtCantFactItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
					"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
					"<td id=\"tdCantPendienteItm:%s\" align=\"right\" style=\"display:none\">%s</td>".
					"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" title=\"Costo Unitario\" value=\"%s\"/></td>".
					"<td id=\"tdIvaItm%s\">%s</td>".
					"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdFactDet%s\" name=\"hddIdFactDet%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdKardexItm%s\" name=\"hddIdKardexItm%s\" readonly=\"readonly\" title=\"Kardex\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/></td>".
				"</tr>');",
				$contFila,
					$contFila,
						 $contFila,
					$contFila, $contFila,
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
						$contFila, $contFila, $idCasilla,
						$contFila, $contFila, $rowKardex['id_kardex'],
						$contFila, $contFila, $hddIdItm, 
						$contFila, $contFila, $hddTpItm)); // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
		}
	}
	
	$queryFacturaDetAcc = sprintf("SELECT
		cxc_fact_det_acc.id_factura_detalle_accesorios,
		cxc_fact_det_acc.id_accesorio,
		cxc_fact_det_acc.id_tipo_accesorio,
		acc.nom_accesorio,
		cxc_fact_det_acc.cantidad,
		cxc_fact_det_acc.costo_compra,
		cxc_fact_det_acc.precio_unitario,
		cxc_fact_det_acc.id_iva,
		cxc_fact_det_acc.iva,
		cxc_fact_det_acc.tipo_accesorio
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
	WHERE cxc_fact_det_acc.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsFacturaDetAcc = mysql_query($queryFacturaDetAcc);
	if (!$rsFacturaDetAcc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowFacturaDetAcc = mysql_fetch_array($rsFacturaDetAcc)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$arrayIdIvaItm = array(-1);
		$hddIdItm = $rowFacturaDetAcc['id_accesorio'];
		$hddTpItm = $rowFacturaDetAcc['tipo_accesorio'];
		$hddIdAccesorioItm = $rowFacturaDetAcc['id_accesorio'];
		$hddTipoAccesorioItm = $rowFacturaDetAcc['id_tipo_accesorio'];
		$divCodigoItm = "";
		$divDescripcionItm = $rowFacturaDetAcc['nom_accesorio'];
		$txtPrecioItm = $rowFacturaDetAcc['precio_unitario'];
		$hddCostoItm = $rowFacturaDetAcc['costo_compra'];
		($rowFacturaDetAcc['id_iva'] > 0) ? $arrayIdIvaItm[] = $rowFacturaDetAcc['id_iva'] : "";
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
		WHERE iva.tipo IN (6,9,2)
			AND iva.idIva IN (%s);",
			valTpDato($hddIdIvaItm, "campo"));
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
				$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
				$contFila.":".$contIva);
		}
		
		$objResponse->script(sprintf(
		"$('#".(($hddTipoAccesorioItm == 1) ? "trItmPieDetalle" : "trItmPieAdicionalOtro")."').before('".
			"<tr align=\"left\" id=\"trItmDetalle_%s\" class=\"textoGris_11px %s\">".
				"<td title=\"trItmDetalle_%s\">".
					"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td align=\"center\" class=\"textoNegrita_9px\">%s</td>".
				"<td><div id=\"divCodigoItm%s\">%s</div></td>".
				"<td><div id=\"divDescripcionItm%s\">%s</div></td>".
				"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
				"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				"<td id=\"divIvaItm%s\" align=\"right\">%s</td>".
				"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdAccesorioItm%s\" name=\"hddIdAccesorioItm%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddTipoAccesorioItm%s\" name=\"hddTipoAccesorioItm%s\" value=\"%s\"/></td>".
			"</tr>');",
			$contFila, $clase,
				$contFila,
					$contFila,
				$contFila,
				$contFila, $divCodigoItm,
				$contFila, utf8_encode($divDescripcionItm),
				$contFila, $contFila, number_format(1, 2, ".", ","), 
				$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","),
					$contFila, $contFila, $hddCostoItm,
				$contFila, $ivaUnidad,
				$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","),
					$contFila, $contFila, $hddIdPedidoDet,
					$contFila, $contFila, $hddIdAccesorioItm,
					$contFila, $contFila, $hddIdItm,
					$contFila, $contFila, $hddTpItm,
					$contFila, $contFila, $hddTipoAccesorioItm));
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_devolucion_vehiculos_form","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbxPieDetalle']) && isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = array_merge($frmListaArticulo['cbxPieDetalle'], $frmTotalDcto['cbxPieDetalle']);
	} else if (isset($frmListaArticulo['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	} else if (isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmTotalDcto['cbxPieDetalle'];
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	
	$idFactura = $frmDcto['txtIdFactura'];
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT cxc_fact.*,
		(SELECT clave_mov.id_clave_movimiento_contra FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = cxc_fact.id_clave_movimiento) AS id_clave_movimiento_contra
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowFact = mysql_fetch_array($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	$idEmpleadoAsesor = $rowFact['idVendedor'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	mysql_query("START TRANSACTION;");
	
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
	
	// INSERTA LOS DATOS DE LA NOTA DE CREDITO
	$updateSQL = sprintf("INSERT INTO cj_cc_notacredito (id_empresa, idCliente, numeracion_nota_credito, numeroControl, fechaNotaCredito, idDepartamentoNotaCredito, id_empleado_vendedor, id_clave_movimiento, idDocumento, tipoDocumento, estadoNotaCredito, montoNetoNotaCredito, saldoNotaCredito, observacionesNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, baseimponibleNotaCredito, porcentajeIvaNotaCredito, ivaNotaCredito, base_imponible_iva_lujo, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, montoExentoCredito, montoExoneradoCredito, estatus_nota_credito, aplicaLibros, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idEmpresa, "int"),
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($frmDcto['txtNumeroControlNotaCredito'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato($idModulo, "int"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idFactura, "int"),
		valTpDato("FA", "text"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($rowFact['montoTotalFactura'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($rowFact['subtotalFactura'], "real_inglesa"),
		valTpDato($rowFact['porcentaje_descuento'], "real_inglesa"),
		valTpDato($rowFact['descuentoFactura'], "real_inglesa"),
		valTpDato($rowFact['baseImponible'], "real_inglesa"),
		valTpDato($rowFact['porcentajeIvaFactura'], "real_inglesa"),
		valTpDato($rowFact['calculoIvaFactura'], "real_inglesa"),
		valTpDato($rowFact['base_imponible_iva_lujo'], "real_inglesa"),
		valTpDato($rowFact['porcentajeIvaDeLujoFactura'], "real_inglesa"),
		valTpDato($rowFact['calculoIvaDeLujoFactura'], "real_inglesa"),
		valTpDato($rowFact['montoExento'], "real_inglesa"),
		valTpDato($rowFact['montoExonerado'], "real_inglesa"),
		valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA_CREDITO");
	
	// VERIFICA SI LA FACTURA FUE AGREGADA POR VENTA DE VEHICULO O POR CUENTAS POR COBRAR
	$queryFacturaVehiculo = sprintf("SELECT
		cxc_fact.idFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroPedido,
		uni_fis.id_unidad_fisica,
		uni_fis.id_uni_bas
	FROM an_pedido ped_vent
		INNER JOIN an_factura_venta cxc_fact ON (ped_vent.id_pedido = cxc_fact.numeroPedido)
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
	WHERE cxc_fact.idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFacturaVehiculo = mysql_query($queryFacturaVehiculo);
	if (!$rsFacturaVehiculo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsFacturaVehiculo = mysql_num_rows($rsFacturaVehiculo);
	$rowFacturaVehiculo = mysql_fetch_array($rsFacturaVehiculo);
	
	if ($totalRowsFacturaVehiculo > 0) { // FUE AGREGADA POR VENTAS DE VEHÍCULOS
		if (isset($arrayObjPieDetalle)) {
			foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
				if (isset($frmListaArticulo['cbxPieDetalle']) && in_array($valorPieDetalle, $frmListaArticulo['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
					$frmListaArticuloAux = $frmListaArticulo;
				} else if (isset($frmTotalDcto['cbxPieDetalle']) && in_array($valorPieDetalle, $frmTotalDcto['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
					$frmListaArticuloAux = $frmTotalDcto;
				}
				
				$hddTpItm = $frmListaArticuloAux['hddTpItm'.$valorPieDetalle];
				
				if ($hddTpItm == 3) {
					// INSERTA LOS VEHICULOS DEVUELTOS
					$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo (id_nota_credito, id_unidad_fisica, id_condicion_unidad, precio_unitario, costo_compra, id_iva, iva, precio_compra, costo_agregado, costo_depreciado, costo_trade_in)
					SELECT
						%s,
						id_unidad_fisica,
						id_condicion_unidad,
						precio_unitario,
						costo_compra,
						id_iva,
						iva,
						precio_compra,
						costo_agregado,
						costo_depreciado,
						costo_trade_in
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					WHERE cxc_fact_det_vehic.id_factura = %s;",
						valTpDato($idNotaCredito, "int"),
						valTpDato($idFactura, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idNotaCreditoDetalleVehiculo = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
							$valorIvaItm = explode(":", $valorIvaItm);
							if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
								$hddIdIvaItm = $frmListaArticuloAux['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
								$hddIvaItm = $frmListaArticuloAux['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
								
								// INSERTA LOS IMPUESTOS DE LOS VEHICULOS DEVUELTOS
								$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo_impuesto (id_nota_credito_detalle_vehiculo, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($idNotaCreditoDetalleVehiculo, "int"),
									valTpDato($hddIdIvaItm, "int"),
									valTpDato($hddIvaItm, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
								
							}
						}
					}
				} else if ($hddTpItm == 4) { // 4 = Repuesto
					$frmListaArticulo = $frmListaArticuloAux;
					
					if ($idMovimiento == 0) {
						// INSERTA EL MOVIMIENTO
						$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
							valTpDato($idClaveMovimiento, "int"),
							valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
							valTpDato($idNotaCredito, "int"),
							valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
							valTpDato($frmDcto['txtIdCliente'], "int"),
							valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
							valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
							valTpDato($_SESSION['idUsuarioSysGts'], "int"),
							valTpDato($rowFact['condicionDePago'], "boolean")); // 0 = Credito, 1 = Contado
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idMovimiento = mysql_insert_id();
					}
					
					// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
					$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det WHERE fact_vent_det.id_factura_detalle = %s;",
						valTpDato($frmListaArticulo['hddIdFactDet'.$valorPieDetalle], "int"));
					$rsFacturaDet = mysql_query($queryFacturaDet);
					if (!$rsFacturaDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
					
					$idArticulo = $rowFacturaDet['id_articulo'];
					$idCasilla = $frmListaArticulo['hddIdCasilla'.$valorPieDetalle];
					$hddIdKardexItm = $frmListaArticulo['hddIdKardexItm'.$valorPieDetalle];
					
					// CANTIDADES DEL PEDIDO
					$cantFacturada = str_replace(",", "", $frmListaArticulo['txtCantFactItm'.$valorPieDetalle]);
					$cantPedida = doubleval(str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]));
					$cantDevuelta = doubleval(str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]));
					$cantPendiente = $cantPedida - $cantDevuelta;
					$precioUnitario = $rowFacturaDet['precio_unitario'];
					$costoUnitario = $rowFacturaDet['costo_compra'];
					
					// BUSCA LA INFORMACION EN EL KARDEX
					$queryKardex = sprintf("SELECT * FROM iv_kardex kardex WHERE kardex.id_kardex = %s;",
						valTpDato($hddIdKardexItm, "int"));
					$rsKardex = mysql_query($queryKardex);
					if (!$rsKardex) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsKardex = mysql_num_rows($rsKardex);
					$rowKardex = mysql_fetch_assoc($rsKardex);
					
					// BUSCA EL ULTIMO COSTO DEL ARTICULO
					$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresa, "int"));
					$rsArtCosto = mysql_query($queryArtCosto);
					if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
					$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
					
					$costoUnitarioKardex = ($ResultConfig12 == 1) ? $costoUnitario : round($rowArtCosto['costo_promedio'],3);
				
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
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
						valTpDato($idModulo, "int"),
						valTpDato($idNotaCredito, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
						valTpDato($cantDevuelta, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitarioKardex, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
						valTpDato(((str_replace(",","",$frmTotalDcto['txtDescuento']) * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
						valTpDato($frmTotalDcto['txtObservacion'], "text"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) {
						if (mysql_errno() == 1048) {
							return $objResponse->alert("Existen artículos los cuales no tienen una ubicación asignada");
	
						} else {
							return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						}
					}
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// INSERTA EL DETALLE DEL MOVIMIENTO
					$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idKardex, "int"),
						valTpDato($cantDevuelta, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
						valTpDato(((str_replace(",","",$frmTotalDcto['txtDescuento']) * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean"), // 0 = No, 1 = Si
						valTpDato("", "int"),
						valTpDato("", "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idMovimientoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// EDITA EL ESTADO DEL DETALLE DE LA FACTURA
					$updateSQL = sprintf("UPDATE cj_cc_factura_detalle SET
						estatus = %s
					WHERE id_factura = %s
						AND id_articulo = %s;",
						valTpDato(2, "int"), // 0 = En Espera, 1 = Entregado, 2 = Devuelto
						valTpDato($idFactura, "int"),
						valTpDato($idArticulo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// EDITA EL ESTADO DEL DETALLE DE LA NOTA DE CREDITO
					$updateSQL = sprintf("UPDATE cj_cc_nota_credito_detalle SET
						estatus = %s
					WHERE id_nota_credito_detalle = %s;",
						valTpDato(1, "int"), // 0 = En Espera, 1 = Recibido
						valTpDato($frmListaArticulo['hddIdNotaCredDet'.$valorPieDetalle], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
					$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
					// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
					$Result1 = actualizarSaldos($idArticulo, $idCasilla);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				}
			}
		}
		
		// INSERTA LOS ACCESORIOS DEVUELTOS
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios (id_nota_credito, id_accesorio, id_tipo_accesorio, cantidad, costo_compra, precio_unitario, id_iva, iva, tipo_accesorio)
		SELECT %s, id_accesorio, id_tipo_accesorio, cantidad, costo_compra, precio_unitario, id_iva, iva, tipo_accesorio FROM cj_cc_factura_detalle_accesorios cxc_fa_det_acc
		WHERE cxc_fa_det_acc.id_factura = %s;",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idNotaCreditoDetalleAccesorio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS ACCESORIOS EN EL DETALLE
		$queryNCDetAcc = sprintf("SELECT * FROM cj_cc_nota_credito_detalle_accesorios WHERE id_nota_credito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsNCDetAcc = mysql_query($queryNCDetAcc);
		if (!$rsNCDetAcc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNCDetAcc = mysql_num_rows($rsNCDetAcc);
		while ($rowNCDetAcc = mysql_fetch_array($rsNCDetAcc)) {
			// INSERTA LOS IMPUESTOS DE LOS ACCESORIOS DEVUELTOS
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios_impuesto (id_nota_credito_detalle_accesorios, id_impuesto, impuesto)
			SELECT
				%s,
				cxc_fa_det_acc_impuesto.id_impuesto,
				cxc_fa_det_acc_impuesto.impuesto
			FROM cj_cc_factura_detalle_accesorios cxc_fa_det_acc
				INNER JOIN cj_cc_factura_detalle_accesorios_impuesto cxc_fa_det_acc_impuesto ON (cxc_fa_det_acc.id_factura_detalle_accesorios = cxc_fa_det_acc_impuesto.id_factura_detalle_accesorios)
			WHERE cxc_fa_det_acc.id_accesorio = %s
				AND cxc_fa_det_acc.id_factura = %s;",
				valTpDato($rowNCDetAcc['id_nota_credito_detalle_accesorios'], "int"),
				valTpDato($rowNCDetAcc['id_accesorio'], "int"),
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		
		// BUSCA SI LA FACTURA A DEVOLVER TIENE UNA UNIDAD
		$queryFADetVehic = sprintf("SELECT * FROM cj_cc_factura_detalle_vehiculo WHERE id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsFADetVehic = mysql_query($queryFADetVehic);
		if (!$rsFADetVehic) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsFADetVehic = mysql_num_rows($rsFADetVehic);
		while ($rowFADetVehic = mysql_fetch_array($rsFADetVehic)) {
			$idUnidadFisica = $rowFADetVehic['id_unidad_fisica'];
			
			// REGISTRA EL MOVIMIENTO DE LA UNIDAD
			$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idNotaCredito, "int"),
				valTpDato($rowFacturaVehiculo['id_uni_bas'], "int"),
				valTpDato($idUnidadFisica, "int"),
				valTpDato($frmDcto['lstTipoClave'], "int"),
				valTpDato($idClaveMovimiento, "int"),
				valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
				valTpDato(1, "real_inglesa"),
				valTpDato($rowFADetVehic['precio_unitario'], "real_inglesa"),
				valTpDato($rowFADetVehic['costo_compra'], "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato($rowFact['porcentaje_descuento'], "real_inglesa"),
				valTpDato(((str_replace(",","",$rowFact['porcentaje_descuento']) * $rowFADetVehic['precio_unitario']) / 100), "real_inglesa"),
				valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
				valTpDato("NOW()", "campo"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL ESTADO DE VENTA DEL VEHÍCULO
			$updateSQL = sprintf("UPDATE an_unidad_fisica SET
				estado_venta = 'DISPONIBLE',
				fecha_pago_venta = '0000-00-00'
			WHERE id_unidad_fisica = %s;",
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA EN VEHICULOS
		$updateSQL = sprintf("UPDATE an_factura_venta SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_pedido SET
			estado_pedido = 4
		WHERE id_pedido = %s;",
			valTpDato($rowFact['numeroPedido'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	// INSERTA LOS IMPUESTOS
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
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// CALCULO DE LAS COMISIONES
	$Result1 = devolverComision($idNotaCredito);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA SI LA FACTURA TIENE COMO PAGO UN ANTICIPO CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR, PND, CANCELADO O SIN CANCELAR
	// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
	$queryAnticipo = sprintf("SELECT DISTINCT cxc_pago_an.*
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago),
		an_pagos cxc_pago_an
	WHERE cxc_pago_an.id_factura = %s
		AND ((((cxc_pago_an.numeroDocumento = cxc_ant.idAnticipo
						AND (cxc_pago.id_concepto IN (2)
							OR cxc_pago.id_concepto IN (1,6,7,8,9)
							OR cxc_ant.totalPagadoAnticipo <= cxc_ant.montoNetoAnticipo))
					OR cxc_pago_an.estatus = 2)
				AND cxc_pago_an.formaPago = 7)
			OR cxc_pago_an.formaPago = 8);",
		valTpDato($idFactura, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	if ($totalRowsAnticipo > 0) {
		while($rowAnticipo = mysql_fetch_assoc($rsAnticipo)) {
			$txtIdFormaPago = $rowAnticipo['formaPago'];
			
			// ANULA EL PAGO
			$updateSQL = sprintf("UPDATE an_pagos SET
				estatus = NULL,
				fecha_anulado = %s,
				id_empleado_anulado = %s
			WHERE idPago = %s;",
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowAnticipo['idPago'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			if ($txtIdFormaPago == 7) { // 7 = Anticipo
				$idAnticipo = $rowAnticipo['numeroDocumento'];
				
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarAnticipo($idAnticipo);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// VERIFICA SI ALGUN ANTICIPO DE TRADE IN TIENE ALGUN DOCUMENTO ASOCIADO QUE AFECTE AL COSTO DE LA UNIDAD VENDIDA
				$queryTradeInCxC = sprintf("SELECT
					(CASE 
						WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
							'ND_CXC'
						WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
							'NC_CXC'
					END) AS tipo_documento,
					(CASE 
						WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
							cxc_nd.idNotaCargo
						WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
							cxc_nc.idNotaCredito
					END) AS id_documento,
					(CASE 
						WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
							cxc_nd.montoTotalNotaCargo
						WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
							cxc_nc.montoNetoNotaCredito
					END) AS monto_total
				FROM an_tradein_cxc tradein_cxc
					LEFT JOIN cj_cc_notadecargo cxc_nd ON (tradein_cxc.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
					LEFT JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)
				WHERE tradein_cxc.id_anticipo = %s
					AND tradein_cxc.estatus = 1;",
					valTpDato($idAnticipo, "int"));
				$rsTradeInCxC = mysql_query($queryTradeInCxC);
				if (!$rsTradeInCxC) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsTradeInCxC = mysql_num_rows($rsTradeInCxC);
				while($rowTradeInCxC = mysql_fetch_assoc($rsTradeInCxC)) {
					$tipoDocumento = $rowTradeInCxC['tipo_documento'];
					$idDocumento = $rowTradeInCxC['id_documento'];
					
					// ANULA EL DETALLE DEL AGREGADO
					if ($idDocumento > 0) {
						$contAgregado++;
						
						if ($tipoDocumento == 'ND_CXC') {
							$updateSQL = sprintf("UPDATE an_unidad_fisica_agregado SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE id_unidad_fisica = %s
								AND id_nota_cargo_cxc = %s
								AND estatus = 1;",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($idUnidadFisica, "int"),
								valTpDato($idDocumento, "int"));
						} else if ($tipoDocumento == 'NC_CXC') {
							// ANULA EL PAGO
							$updateSQL = sprintf("UPDATE an_pagos SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE id_factura = %s
								AND numeroDocumento = %s
								AND formaPago IN (8);",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($idFactura, "int"),
								valTpDato($idDocumento, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							$idNotaCreditoAgregado = $idDocumento;
							
							$objDcto = new Documento;
							$Result1 = $objDcto->actualizarNotaCredito($idNotaCreditoAgregado);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
							
							$updateSQL = sprintf("UPDATE an_unidad_fisica_agregado SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE id_unidad_fisica = %s
								AND id_nota_credito_cxc = %s
								AND estatus = 1;",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($idUnidadFisica, "int"),
								valTpDato($idDocumento, "int"));
						}
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					}
				}
				
			} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
				$idNotaCredito = $rowAnticipo['numeroDocumento'];
				
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarNotaCredito($idNotaCredito);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
			}
		}
		
		if ($contAgregado > 0) {
			// ACTUALIZA EL COSTO DE LOS AGREGADOS
			$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
				costo_agregado = (SELECT SUM(IF((id_factura_cxp IS NOT NULL
												OR id_nota_cargo_cxp IS NOT NULL
												OR id_nota_credito_cxc IS NOT NULL
												OR id_vale_salida IS NOT NULL), 1, (-1)) * monto) FROM an_unidad_fisica_agregado uni_fis_agregado
									WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica
										AND uni_fis_agregado.estatus = 1)
			WHERE id_unidad_fisica = %s;",
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarFactura($idFactura);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	}
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowFact = mysql_fetch_array($rsFact);
	
	if (in_array($rowFact['estadoFactura'],array(0,2))) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
		// BUSCA LOS DATOS DE LA NOTA DE CREDITO
		$queryNotaCredito = sprintf("SELECT cxc_nc.*,
			(CASE cxc_nc.estadoNotaCredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Cancelado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS estado_nota_credito,
			motivo.descripcion AS descripcion_motivo
		FROM pg_motivo motivo
			RIGHT JOIN cj_cc_notacredito cxc_nc ON (motivo.id_motivo = cxc_nc.id_motivo)
		WHERE idNotaCredito = %s",
			valTpDato($idNotaCredito, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
		
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (ape.id_empresa = %s
				OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = %s));",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		if ($rowFact['estadoFactura'] == 0) { // 0 = No Cancelado
			if ($rowFact['saldoFactura'] == $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] > $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
		} else if ($rowFact['estadoFactura'] == 2) { // 2 = Parcialmente Cancelado
			if ($rowFact['saldoFactura'] == $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] > $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] < $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowFact['saldoFactura'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
		}
		
		$arrayObjPago = array();
		$arrayDetallePago = array(
			"idCajaPpal" => $idCajaPpal,
			"apertCajaPpal" => $apertCajaPpal,
			"idApertura" => $idApertura,
			"numeroActualFactura" => $rowFact['numeroFactura'],
			"fechaRegistroPago" => $fechaRegistroPago,
				//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
				//"idEncabezadoPago" => $idEncabezadoPago,
				//"cbxPosicionPago" => $cbxPosicionPago,
				//"hddIdPago" => $hddIdPago,
			"txtIdFormaPago" => 8, // 8 = Nota de Crédito
			"txtIdNumeroDctoPago" => $idNotaCredito,
				//"txtNumeroDctoPago" => $txtNumeroDctoPago,
				//"txtIdBancoCliente" => $txtIdBancoCliente,
				//"txtCuentaClientePago" => $txtCuentaClientePago,
				//"txtIdBancoCompania" => $txtIdBancoCompania,
				//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
				//"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valor2]),
				//"txtFechaDeposito" => $txtFechaDeposito,
				//"txtTipoTarjeta" => $txtTipoTarjeta,
				//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
				//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
				//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
				//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
				//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
				//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
			"txtMonto" => $saldoNotaCred,
			"cbxCondicionMostrar" => $frmListaDctoPagado['cbxCondicionMostrar'.$valorPago],
			"lstSumarA" => $frmListaDctoPagado['cbxMostrarContado'.$valorPago]
		);
		
		$arrayObjPago[] = $arrayDetallePago;
		
		$objDcto = new Documento;
		$objDcto->idModulo = $idModulo;
		$objDcto->idDocumento = $idFactura;
		$objDcto->idEmpresa = $idEmpresa;
		$objDcto->idCliente = $idCliente;
		$Result1 = $objDcto->guardarReciboPagoCxCFA(
			$idCajaPpal,
			$apertCajaPpal,
			$idApertura,
			$fechaRegistroPago,
			$arrayObjPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
		
		$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
		
	} else if ($rowFact['estadoFactura'] == 1) { // 1 = Cancelado
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarNotaCredito($idNotaCredito);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
		creditodisponible = limitecredito - (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idCliente = cliente_emp.id_cliente
														AND cxc_fact.id_empresa = cliente_emp.id_empresa
														AND cxc_fact.estadoFactura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
													WHERE cxc_nd.idCliente = cliente_emp.id_cliente
														AND cxc_nd.id_empresa = cliente_emp.id_empresa
														AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
													WHERE anticip.idCliente = cliente_emp.id_cliente
														AND anticip.id_empresa = cliente_emp.id_empresa
														AND anticip.estadoAnticipo IN (1,2)
														AND anticip.estatus = 1), 0)
											- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idCliente = cliente_emp.id_cliente
														AND cxc_nc.id_empresa = cliente_emp.id_empresa
														AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
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
		creditoreservado = (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idCliente = cliente_emp.id_cliente
								AND cxc_fact.id_empresa = cliente_emp.id_empresa
								AND cxc_fact.estadoFactura IN (0,2)), 0)
							+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
							WHERE cxc_nd.idCliente = cliente_emp.id_cliente
								AND cxc_nd.id_empresa = cliente_emp.id_empresa
								AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
							- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
							WHERE anticip.idCliente = cliente_emp.id_cliente
								AND anticip.id_empresa = cliente_emp.id_empresa
								AND anticip.estadoAnticipo IN (1,2)
								AND anticip.estatus = 1), 0)
							- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idCliente = cliente_emp.id_cliente
								AND cxc_nc.id_empresa = cliente_emp.id_empresa
								AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
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
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA_CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasRe")) { generarNotasRe($idNotaCredito,"",""); } break;
					case 1 : if (function_exists("generarNotasVentasSe")) { generarNotasVentasSe($idNotaCredito,"",""); } break;
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Nota de Crédito Guardada con Éxito");
	
	$objResponse->script("
	verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=".$idNotaCredito."', 960, 550);
	window.location.href='cj_devolucion_venta_list.php';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");

// FUNCION AGREGADA EL 17-09-2012
function actualizarNumeroControl($idEmpresa, $idClaveMovimiento){
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	return array(true, "");
}

function buscarNumeroControl($idEmpresa, $idClaveMovimiento){
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return array(false, "No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($numeroActual,strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($numeroActual,strlen($formato),"0",STR_PAD_LEFT);
	}

	return array(true, $numeroActualFormato);
}

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>