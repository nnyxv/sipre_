<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function reconversion($idNotaCredito){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	
	$objResponse->alert($idNotaCredito);
	
	$idNotaCredito2 = $idNotaCredito;
	
	//reconvertir primero la factura
	//con el id de la nota de crédito se busca el id de la factura
	$buscaidfact = "SELECT idDocumento FROM cj_cc_notacredito where idNotaCredito = $idNotaCredito ";
	
	//$objResponse->alert($buscaidfact);
	
	$rsBuscaIdFact = mysql_query($buscaidfact);
	
	if (!$rsBuscaIdFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowID = mysql_fetch_assoc($rsBuscaIdFact);
	$idFactura2 = $rowID['idDocumento'];
	
	//con el id de la factura se busca si esta fue o no reconvertida....
	$queryValidacion = "SELECT * FROM cj_cc_factura_reconversion WHERE id_factura = $idFactura2";
	
	//$objResponse->alert($queryValidacion);	
	
	$rsValidacion = mysql_query($queryValidacion);
	$existe = mysql_num_rows($rsValidacion);

	$queryConsultaidCliente = "SELECT idCliente FROM 
									  cj_cc_encabezadofactura 
							   WHERE idFactura = $idFactura2 ";
	$rsConsulta1 = mysql_query($queryConsultaidCliente);
	$valor1 = mysql_fetch_array($rsConsulta1);
	$bandera2 = mysql_num_rows($rsConsulta1);

	$queryConsultaidClienteEmpresa = "SELECT id_cliente_empresa FROM 
											cj_cc_cliente_empresa
										WHERE id_cliente =". $valor1['idCliente'];//42849
	$rsConsulta2 = mysql_query($queryConsultaidClienteEmpresa);
	$valor2 = mysql_fetch_array($rsConsulta2);
	$bandera3 = mysql_num_rows($rsConsulta2);



	if($existe == 0){
		//$objResponse->alert("entro en existe??");
		//TABLA1
		$queryFactura1 = "UPDATE sa_iv_pagos 
						  SET montoPagado = montoPagado/100000 
						  WHERE id_factura = $idFactura2";
		$rsFactura1 = mysql_query($queryFactura1);
		if (!$rsFactura1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura1);
		

		//TABLA2
		$queryFactura2 = "UPDATE cj_cc_encabezadofactura  
						  SET montoTotalFactura = montoTotalFactura/100000, 
						  	  subTotalFactura = subTotalFactura /100000,
						  	  baseImponible =baseImponible /100000,
						  	  calculoIvaFactura = calculoIvaFactura/100000,
						  	  saldoFactura	=  saldoFactura/100000
						  WHERE idFactura = $idFactura2";
		$rsFactura2 = mysql_query($queryFactura2);
		if (!$rsFactura2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura2);

		//TABLA3
		$queryFactura3 = "UPDATE cj_cc_factura_detalle  
						  SET costo_compra  = costo_compra /100000,
						  	  costo_promedio_compra = costo_promedio_compra/100000,
						      precio_unitario = precio_unitario/100000 
						  WHERE id_factura = $idFactura2";
		$rsFactura3 = mysql_query($queryFactura3);
		if (!$rsFactura3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura3);

		//TABLA4
		$queryFactura4 = "UPDATE cj_cc_factura_iva  
						  SET base_imponible  = base_imponible/100000, 
						      subtotal_iva = subtotal_iva/100000 
						  WHERE id_factura = $idFactura2";
		$rsFactura4 = mysql_query($queryFactura4);
		if (!$rsFactura1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura4);

		//TABLA5
		$queryFactura5 = "UPDATE cj_cc_retenciondetalle  
						  SET baseImponible  = baseImponible/100000,
						  	  totalCompraIncluyendoIva = totalCompraIncluyendoIva/100000,
						  	  comprasSinIva = comprasSinIva/100000,
						  	  impuestoIva = impuestoIva/100000,
						  	  ivaRetenido = ivaRetenido/100000
						  WHERE idFactura = $idFactura2";
		$rsFactura5 = mysql_query($queryFactura5);
		if (!$rsFactura5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura5);

		//TABLA 6
			$queryCreditoDispClienteEmpresa ="UPDATE cj_cc_credito 
												SET limitecredito = limitecredito/100000,
												creditoreservado = creditoreservado/100000,
												creditodisponible = creditodisponible/100000
												WHERE id_cliente_empresa =". $valor2['id_cliente_empresa'];
		$rsFactura6 = mysql_query($queryCreditoDispClienteEmpresa);
		if (!$rsFactura6) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCreditoDispClienteEmpresa);


		$queryReconversion = "INSERT INTO cj_cc_factura_reconversion (id_factura,id_usuario) VALUES ($idFactura2,$id_usuario)";
		$rsReconversion = mysql_query($queryReconversion);
		if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);


		/*$mensaje = "Items Actualizados'";
		$objResponse->alert("$mensaje");
		$objResponse->script("location.reload()");
		return $objResponse;*/
		
	}else{
		$objResponse->alert("Los items de la factura a devolver ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");		
	}	
	
	//

//Una vez convertida la factura, se convierte la nota de crédito
	//Verifico si la nota de carggo ya tiene reconversion
	$queryValidacion = "SELECT * FROM cj_cc_notacredito_reconversion WHERE id_notacredito = $idNotaCredito2";
	$rsValidacion = mysql_query($queryValidacion);
	$numReg = mysql_num_rows($rsValidacion);

	//Consulto el id del cliente normal
	$queryConsultaidCliente = "SELECT idCliente FROM 
									  cj_cc_notacredito 
							   WHERE idNotaCredito = $idNotaCredito2 ";
	$rsConsulta1 = mysql_query($queryConsultaidCliente);
	$valor1 = mysql_fetch_array($rsConsulta1);
	$numReg1 = mysql_num_rows($rsConsulta1);


	//Consulto el cliente empresa
	$queryConsultaidClienteEmpresa = "SELECT id_cliente_empresa FROM 
											cj_cc_cliente_empresa
										WHERE id_cliente =". $valor1['idCliente'];//42849
	$rsConsulta2 = mysql_query($queryConsultaidClienteEmpresa);
	$valor2 = mysql_fetch_array($rsConsulta2);
	$numReg2 = mysql_num_rows($rsConsulta2);


	if($numReg == 0){
		//TABLA1
		$queryNotaCredito1 = "UPDATE cj_cc_notacredito 
							SET montoNetoNotaCredito = montoNetoNotaCredito/100000,
							saldoNotaCredito = saldoNotaCredito/100000,
							subtotalNotaCredito = subtotalNotaCredito/100000,
							fletesNotaCredito = fletesNotaCredito/100000,
							subtotal_descuento = subtotal_descuento/100000,
							baseimponibleNotaCredito = baseimponibleNotaCredito/100000,
							ivaNotaCredito = ivaNotaCredito/100000,
							montoExentoCredito = montoExentoCredito/100000,
							montoExoneradoCredito = montoExoneradoCredito/100000
							WHERE idNotaCredito = $idNotaCredito2 ";
		$rsNota1 = mysql_query($queryNotaCredito1);
		if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito1);

		//TABLA2
		$queryNotaCredito2 = "UPDATE cj_cc_nota_credito_iva 
							SET base_imponible = base_imponible/100000,
							subtotal_iva = subtotal_iva/100000
							WHERE id_nota_credito = $idNotaCredito2 ";
		$rsNota2 = mysql_query($queryNotaCredito2);
		if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito2);


		//TABLA3
		$queryNotaCredito3 = "UPDATE sa_iv_pagos 
							SET montoPagado = montoPagado/100000
							WHERE numeroDocumento = $idNotaCredito2 ";
		$rsNota3 = mysql_query($queryNotaCredito3);
		if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito3);

		//TABLA4
		$queryNotaCredito4 = "UPDATE an_pagos 
							SET montoPagado = montoPagado/100000
							WHERE numeroDocumento = $idNotaCredito2 ";
		$rsNota4 = mysql_query($queryNotaCredito4);
		if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito4);
			
		//TABLA5
		$queryNotaCredito5 = "UPDATE cj_cc_credito 
							SET limitecredito = limitecredito/100000,
							creditodisponible = creditodisponible/100000,
							creditoreservado = creditoreservado/100000
							WHERE id_cliente_empresa =".$valor2['id_cliente_empresa'];
		$rsNota5 = mysql_query($queryNotaCredito5);
		if (!$rsNota5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito5);

		//TABLA6
		/*$queryNotaCredito6 = "UPDATE cj_cc_nota_credito_detalle_motivo 
							SET precio_unitario = precio_unitario/100000
							WHERE id_nota_credito = $idNotaCredito2 ";
		$rsNota6 = mysql_query($queryNotaCredito6);
		if (!$rsNota6) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito6);*/

		//TABLA7
		$queryNotaCredito7 = "UPDATE cj_cc_nota_credito_detalle 
							SET precio_unitario = precio_unitario/100000
							WHERE id_nota_credito = $idNotaCredito2 ";
		$rsNota7 = mysql_query($queryNotaCredito7);
		if (!$rsNota7) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito7);

		
		//TABLA8
		$queryReconversion = "INSERT INTO cj_cc_notacredito_reconversion (id_notacredito,id_usuario) VALUES ($idNotaCredito2,$id_usuario)";
		$rsReconversion = mysql_query($queryReconversion);
		if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);

		$mensaje = "Items Actualizados";
		$objResponse->alert("$mensaje");
		$objResponse->script("location.reload()");
		return $objResponse;			
	}else{
		return $objResponse->alert("Los items de la nota de crédito ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");		
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $calcularDcto = "false"){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	if (isset($arrayObjPieDetalle)) {
		$i = 0;
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle_".$valorPieDetalle,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle_".$valorPieDetalle,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObjPieDetalle) > 0) ? implode("|",$arrayObjPieDetalle) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	if (isset($arrayObjGasto)) {
		$i = 0;
		foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmGasto:".$valorGasto, "className", $clase." textoGris_11px");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indiceIva => $valorIva) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valorIva."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdNotaCredito'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valorPieDetalle]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valorPieDetalle]);
			$txtTotalItm = $txtCantItm * $txtPrecioItm;
			
			$txtSubTotal += $txtTotalItm;
			$subTotalDescuentoItm += $txtCantItm * $hddMontoDescuentoItm;
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1 || isset($frmDcto['txtIdNotaCredito'])) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]);
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valorPieDetalle]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valorPieDetalle]);
			
			$hddTotalDescuentoItm = ($hddTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					
					if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
						
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]]] = $valorIvaItm[1];
						$arrayIdIvaItm[] = ($hddIdIvaItm > 0) ? $hddIdIvaItm : -1;
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
					}
				}
			}
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
				valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if (($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
					$txtTotalExento += $txtTotalNetoItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
			
			$objResponse->assign("txtTotalItm".$valorPieDetalle, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $txtCantItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valorPieDetalle]);
		}
	}
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s
				AND id_modo_gasto IN (1);", 
				valTpDato($frmTotalDcto['hddIdGasto'.$valorGasto], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				if ($frmTotalDcto['hddTipoGasto'.$valorGasto] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
					$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valorGasto]);
					$txtMontoGasto = ($txtSubTotal == 0) ? 0 : $txtPorcGasto * ($txtSubTotal / 100);
					$objResponse->assign('txtMontoGasto'.$valorGasto, "value", number_format($txtMontoGasto, 2, ".", ","));
				} else if ($frmTotalDcto['hddTipoGasto'.$valorGasto] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
					$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valorGasto]);
					$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
					$objResponse->assign('txtPorcGasto'.$valorGasto, "value", number_format($txtPorcGasto, 2, ".", ","));
				}
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$arrayPosIvaItm = array(-1);
				$arrayIdIvaItm = array(-1);
				$arrayIvaItm = array(-1);
				$arrayEstatusIvaItm = array(-1);
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indiceIvaGasto => $valorIvaGasto) {
						$valorIvaGasto = explode(":", $valorIvaGasto);
						
						if ($valorIvaGasto[0] == $valorGasto) {
							$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]]] = $valorIvaGasto[1];
							$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							$arrayEstatusIvaItm[] = $frmTotalDcto['hddEstatusIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
						}
					}
				}
				
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);",
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valorGasto.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if (($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIva += $txtMontoGasto; break;
							default : $gastosNoAfecta += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
						default : $gastosNoAfecta += $txtMontoGasto; break;
					}
				} else if (!($totalRowsIva > 0)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIva += $txtMontoGasto; break;
						default : $gastosNoAfecta += $txtMontoGasto; break;
					}
				}
			}
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
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
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += round(doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva), 2);
	
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtGastosConIva", "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva", "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalOrden,2,".",","));
	$objResponse->assign("txtMontoPorPagar","value",number_format($txtTotalOrden,2,".",","));
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGastos = sprintf("SELECT * FROM pg_gastos
			WHERE pg_gastos.id_gasto = %s;",
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
	
	if (in_array($calcularDcto, array("1", "true"))) {
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function cargarDcto($idDocumento, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
	$queryNotaCred = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s",
		valTpDato($idDocumento, "int"));
	$rsNotaCred = mysql_query($queryNotaCred);
	if (!$rsNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNotaCred = mysql_fetch_assoc($rsNotaCred);
	
	// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
	$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
	// BUSCA EL MOVIMIENTO DE LA NOTA DE CRÉDITO
	$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s;",
		valTpDato($rowNotaCred['id_clave_movimiento'], "int"));
	$rsClaveMov = mysql_query($queryClaveMov);
	if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
	
	// VERIFICA SI LA NOTA DE CREDITO ESTA EN ESTATUS DE APROBADA
	if (in_array($rowNotaCred['estatus_nota_credito'], array("",1))) {
		$idFactura = $rowNotaCred['idDocumento'];
		
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFact = sprintf("SELECT
			vw_iv_fact_vent.*,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_facturas_venta vw_iv_fact_vent
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_fact_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE vw_iv_fact_vent.idFactura = %s;",
			valTpDato($idFactura, "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idEmpresa = $rowFact['id_empresa'];
		$idCliente = $rowFact['id_cliente'];
		$idPedido = $rowFact['id_pedido_venta'];
	
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }

		// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			errorInsertarArticulo($objResponse); return $objResponse->alert($ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		// BUSCA LOS DATOS DEL PEDIDO
		$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta WHERE id_pedido_venta = %s",
			valTpDato($idPedido, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idCondicionPago = $rowPedido['condicion_pago'];
		$idClaveMovimiento = $rowPedido['id_clave_movimiento'];
		
		$queryNotaCredDet = sprintf("SELECT * FROM cj_cc_nota_credito_detalle WHERE id_nota_credito = %s;",
			valTpDato($idDocumento, "int"));
		$rsNotaCredDet = mysql_query($queryNotaCredDet);
		if (!$rsNotaCredDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		$arrayObj = NULL;
		while ($rowNotaCredDet = mysql_fetch_assoc($rsNotaCredDet)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$idArticulo = $rowNotaCredDet['id_articulo'];
			
			// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
			$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = %s
				AND fact_vent_det.id_articulo = %s
				AND fact_vent_det.precio_unitario = %s;",
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($rowNotaCredDet['precio_unitario'], "real_inglesa"));
			$rsFacturaDet = mysql_query($queryFacturaDet);
			if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
		
			// BUSCA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
			$queryFacturaDetImpuesto = sprintf("SELECT * FROM cj_cc_factura_detalle_impuesto WHERE id_factura_detalle = %s;",
				valTpDato($rowFacturaDet['id_factura_detalle'], "int"));
			$rsFacturaDetImpuesto = mysql_query($queryFacturaDetImpuesto);
			if (!$rsFacturaDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
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
			
			$hddIdArticuloCosto = $rowKardex['id_articulo_costo'];
			
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_venta_det
			WHERE ped_venta_det.id_pedido_venta = %s
				AND ped_venta_det.id_articulo = %s
				AND ped_venta_det.id_casilla = %s
				AND ped_venta_det.cantidad = %s
				AND (ped_venta_det.id_articulo_almacen_costo = %s OR ped_venta_det.id_articulo_almacen_costo IS NULL);",
				valTpDato($idPedido, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($rowKardex['id_casilla'], "int"),
				valTpDato($rowKardex['cantidad'], "real_inglesa"),
				valTpDato($rowKardex['id_articulo_almacen_costo'], "int"));
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
			
			// SI ES POR REPOSICION O COSTO PROMEDIO BUSCA EL UNICO LOTE DE LA UBICACION
			// O PRIMER LOTE PORQUE QUE NO TIENE ASIGNADO ALGUNO, DEBIDO A QUE SON DEVOLUCIONES DE FACTURAS DE CUANDO NO SE MANEJABAN LOTES
			if (in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
				// BUSCA EL LOTE PARA LA DEVOLUCION
				$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
				WHERE vw_iv_art_almacen_costo.id_articulo = %s
					AND vw_iv_art_almacen_costo.id_casilla = %s
					AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
				ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
				$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				
				$hddIdArticuloAlmacenCosto = $rowArtCosto['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowArtCosto['id_articulo_costo'];
			}
			
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
			
			$objResponse->script(sprintf(
			"$('#trItmPieDetalle').before('".
				"<tr align=\"left\" id=\"trItmDetalle_%s\" class=\"textoGris_11px %s\">".
					"<td title=\"trItmDetalle_%s\">".
						"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td id=\"tdNumItmDetalle_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
					"<td>%s</td>".
					"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
						"<span id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</span>".
						"%s</td>".
					"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
					"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" title=\"Costo Unitario\" value=\"%s\"/></td>".
					"<td id=\"tdIvaItm%s\">%s</td>".
					"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdNotaCredDet%s\" name=\"hddIdNotaCredDet%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdPedDet%s\" name=\"hddIdPedDet%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" readonly=\"readonly\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" readonly=\"readonly\" title=\"Lote\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" readonly=\"readonly\" title=\"Casilla\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdKardexItm%s\" name=\"hddIdKardexItm%s\" readonly=\"readonly\" title=\"Kardex\" value=\"%s\"/></td>".
				"</tr>');",
				$contFila, $clase,
					$contFila,
						$contFila,
					$contFila, $contFila,
					elimCaracter(utf8_encode($rowArticulo['codigo_articulo']), ";"),
					$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
						$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
						((in_array($ResultConfig12, array(1,2)) || $hddIdArticuloCosto == 0) ? "" : "<div id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</div>"),
					$contFila, $contFila, number_format($rowNotaCredDet['cantidad'], 2, ".", ","),
					$contFila, $contFila, number_format($rowNotaCredDet['precio_unitario'], 2, ".", ","),
						$contFila, $contFila, $rowNotaCredDet['costo_compra'],
					$contFila, $ivaUnidad,
					$contFila, $contFila, number_format(($rowNotaCredDet['cantidad'] * $rowNotaCredDet['precio_unitario']), 2, ".", ","),
						$contFila, $contFila, $rowNotaCredDet['id_nota_credito_detalle'],
						$contFila, $contFila, $hddIdPedidoVentaDetalle,
						$contFila, $contFila, $idArticulo,
						$contFila, $contFila, $hddIdArticuloAlmacenCosto,
						$contFila, $contFila, $hddIdArticuloCosto,
						$contFila, $contFila, $hddIdPrecioItm,
						$contFila, $contFila, $idCasilla,
						$contFila, $contFila, $rowKardex['id_kardex']));
			
			$arrayObj[] = $contFila;
		}
		
		// CARGA LOS DATOS DEL CLIENTE
		$queryCliente = sprintf("SELECT
			cliente.id AS id_cliente,
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
		
		$objResponse->assign("tdGastos","innerHTML",formularioGastos(true,$idDocumento,"NOTA_CREDITO"));
		
		// DATOS DE LA NOTA DE CREDITO
		$objResponse->assign("txtIdNotaCredito","value",$rowNotaCred['idNotaCredito']);
		$objResponse->assign("txtFechaNotaCredito","value",date(spanDateFormat));
		$objResponse->assign("txtNumeroNotaCredito","value",$rowNotaCred['numeracion_nota_credito']);
		$objResponse->call("selectedOption","lstTipoClave",2);
		$objResponse->script("byId('lstTipoClave').onchange = function(){ selectedOption(this.id,'".(2)."'); };");
		$objResponse->assign("hddIdClaveMovimiento","value",$rowClaveMov['id_clave_movimiento']);
		$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowClaveMov['descripcion']));
		$objResponse->assign("txtObservacionNotaCredito","value",utf8_encode($rowNotaCred['observacionesNotaCredito']));
		
		// DATOS DEL CLIENTE
		$objResponse->assign("txtIdCliente","value",$rowCliente['id_cliente']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
		$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
		$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
		
		// DATOS DE LA FACTURA
		$objResponse->assign("txtIdEmpresa","value",$rowFact['id_empresa']);
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowFact['nombre_empresa']));
		$objResponse->assign("txtIdFactura","value",$rowFact['idFactura']);
		$objResponse->assign("txtFechaFactura","value",date(spanDateFormat, strtotime($rowFact['fechaRegistroFactura'])));
		$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat, strtotime($rowFact['fechaVencimientoFactura'])));
		$objResponse->assign("txtNumeroFactura","value",$rowFact['numeroFactura']);
		$objResponse->assign("txtNumeroControlFactura","value",$rowFact['numeroControl']);
		$objResponse->assign("txtIdPedido","value",$rowFact['id_pedido_venta']);
		$objResponse->assign("hddFechaPedido","value",date("Y-m-d", strtotime($rowPedido['fecha'])));
		$objResponse->assign("hddIdMoneda","value",utf8_encode($rowMoneda['idmoneda']));
		$objResponse->assign("txtMoneda","value",utf8_encode($rowMoneda['descripcion']));
		$objResponse->assign("hddIdEmpleado","value",$rowFact['id_empleado_preparador']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowFact['nombre_empleado']));
		$objResponse->assign("txtTipoClaveFactura","value","3.- VENTA");
		$objResponse->assign("hddIdClaveMovimientoFactura","value",$rowPedido['id_clave_movimiento']);
		$objResponse->assign("txtClaveMovimientoFactura","value",utf8_encode($rowPedido['descripcion_clave_movimiento']));
		$objResponse->assign("txtDescuento","value",number_format($rowFact['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFact['subtotal_descuento'], 2, ".", ","));
		
		$Result1 = buscarNumeroControl($idEmpresa, $rowClaveMov['id_clave_movimiento']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->assign("txtNumeroControlNotaCredito","value",($Result1[1]));
		}
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");
	} else {
		$objResponse->alert("Esta Nota de Crédito no puede ser generada nuevamente");
		$objResponse->script("byId('btnCancelar').click();");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cjrs_devolucion_repuestos_form","insertar")) { errorGuardarDcto($objResponse); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	
	$idFactura = $frmDcto['txtIdFactura'];
	$idNotaCredito = $frmDcto['txtIdNotaCredito'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['hddIdClaveMovimiento'];		
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idEmpleado = $rowFact['idVendedor'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	
	// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
	$queryNotaCred = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$rsNotaCred = mysql_query($queryNotaCred);
	if (!$rsNotaCred) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNotaCred = mysql_fetch_assoc($rsNotaCred);
	
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
	
	if ($rowNumeracion['numero_actual'] == "") { errorGuardarDcto($objResponse); return $objResponse->alert("No se ha configurado la numeracion de notas de credito"); }
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$numeroActualNota = $numeroActual;
		
	// INSERTA LOS DATOS DE LA NOTA DE CRÉDITO
	$updateSQL = sprintf("UPDATE cj_cc_notacredito SET
		numeracion_nota_credito = %s, 
		numeroControl = %s,
		fechaNotaCredito = %s,
		idDepartamentoNotaCredito = %s,
		estatus_nota_credito = %s,
		impreso = %s
	WHERE idNotaCredito = %s;",
		valTpDato($numeroActualNota, "text"),
		valTpDato($frmDcto['txtNumeroControlNotaCredito'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato($idModulo, "int"),
		valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($idNotaCredito, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA CREDITO");
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($frmDcto['hddIdClaveMovimiento'], "int"),
		valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato($frmDcto['txtIdCliente'], "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($rowFact['condicionDePago'], "boolean")); // 0 = Credito, 1 = Contado
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	
	// INSERTA EL DETALLE DEL DOCUMENTO
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if ($frmListaArticulo['hddIdNotaCredDet'.$valorPieDetalle] > 0) {
				// BUSCA LOS DATOS DEL DETALLE DE LA NOTA DE CREDITO
				$queryNotaCredDet = sprintf("SELECT * FROM cj_cc_nota_credito_detalle WHERE id_nota_credito_detalle = %s;",
					valTpDato($frmListaArticulo['hddIdNotaCredDet'.$valorPieDetalle], "int"));
				$rsNotaCredDet = mysql_query($queryNotaCredDet);
				
				if (!$rsNotaCredDet) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$rowNotaCredDet = mysql_fetch_assoc($rsNotaCredDet);
				
				$idArticulo = $rowNotaCredDet['id_articulo'];
				$idCasilla = $frmListaArticulo['hddIdCasilla'.$valorPieDetalle];
				$hddIdKardexItm = $frmListaArticulo['hddIdKardexItm'.$valorPieDetalle];
				
				// CANTIDADES DE LA NOTA DE CREDITO
				$cantPedida = $rowNotaCredDet['cantidad'];
				$cantPendiente = $rowNotaCredDet['pendiente'];
				$cantDevuelta = doubleval($cantPedida) - doubleval($cantPendiente);
				$precioUnitario = str_replace(",","",$frmListaArticulo['txtPrecioItm'.$valorPieDetalle]);
				$costoUnitario = $rowNotaCredDet['costo_compra'];
				
				// BUSCA LA INFORMACION EN EL KARDEX
				$queryKardex = sprintf("SELECT * FROM iv_kardex kardex WHERE kardex.id_kardex = %s;",
					valTpDato($hddIdKardexItm, "int"));
				$rsKardex = mysql_query($queryKardex);
				if (!$rsKardex) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsKardex = mysql_num_rows($rsKardex);
				$rowKardex = mysql_fetch_assoc($rsKardex);
				
				$hddIdArticuloCosto = $rowKardex['id_articulo_costo'];
				
				// SI ES POR REPOSICION O COSTO PROMEDIO BUSCA EL UNICO LOTE DE LA UBICACION
				// O PRIMER LOTE PORQUE QUE NO TIENE ASIGNADO ALGUNO, DEBIDO A QUE SON DEVOLUCIONES DE FACTURAS DE CUANDO NO SE MANEJABAN LOTES
				if (in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
					// BUSCA EL LOTE PARA LA DEVOLUCION
					$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
					WHERE vw_iv_art_almacen_costo.id_articulo = %s
						AND vw_iv_art_almacen_costo.id_casilla = %s
						AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
					ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"));
					$rsArtCosto = mysql_query($queryArtCosto);
					if (!$rsArtCosto) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
					$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
					
					$hddIdArticuloAlmacenCosto = $rowArtCosto['id_articulo_almacen_costo'];
					$hddIdArticuloCosto = $rowArtCosto['id_articulo_costo'];
				} else {
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
					if (!$rsArtAlmCosto) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$hddIdArticuloAlmacenCosto = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					}
					
					// BUSCA EL LOTE PARA LA DEVOLUCION
					$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
					WHERE vw_iv_art_almacen_costo.id_articulo_almacen_costo = %s
						AND vw_iv_art_almacen_costo.id_articulo_costo = %s
					ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"));
					$rsArtCosto = mysql_query($queryArtCosto);
					if (!$rsArtCosto) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
					$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				}
				
				$costoUnitarioDet = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
				$costoUnitarioKardex = (in_array($ResultConfig12, array(1,2))) ? $costoUnitario : $costoUnitarioDet;
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
					valTpDato($idModulo, "int"),
					valTpDato($idNotaCredito, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
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
					valTpDato($frmTotalDcto['txtObservacionNotaCredito'], "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) {
					errorGuardarDcto($objResponse);
					if (mysql_errno() == 1048) {
						return $objResponse->alert("Existen artículos los cuales no tienen una ubicación asignada");
					} else {
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
				}
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL DETALLE DEL MOVIMIENTO
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idKardex, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
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
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// EDITA EL ESTADO DEL DETALLE DE LA NOTA DE CREDITO
				$updateSQL = sprintf("UPDATE cj_cc_nota_credito_detalle SET
					id_articulo_almacen_costo = %s,
					id_articulo_costo = %s,
					estatus = %s
				WHERE id_nota_credito_detalle = %s;",
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato(1, "int"), // 0 = En Espera, 1 = Recibido
					valTpDato($frmListaArticulo['hddIdNotaCredDet'.$valorPieDetalle], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
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
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// CALCULO DE LAS COMISIONES
	$Result1 = devolverComision($idNotaCredito);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	
	if (in_array($rowFact['estadoFactura'],array(0,2))) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
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
		if (!$rsAperturaCaja) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		if ($rowFact['estadoFactura'] == 0) { // 0 = No Cancelado
			if ($rowFact['saldoFactura'] == doubleval($rowNotaCred['saldoNotaCredito'])) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = doubleval($rowNotaCred['saldoNotaCredito']);
			} else if ($rowFact['saldoFactura'] > doubleval($rowNotaCred['saldoNotaCredito'])) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = doubleval($rowNotaCred['saldoNotaCredito']);
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
		} else if ($rowFact['estadoFactura'] == 2) { // 2 = Parcialmente Cancelado
			if ($rowFact['saldoFactura'] == doubleval($rowNotaCred['saldoNotaCredito'])) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = doubleval($rowFact['saldoFactura']);
			} else if ($rowFact['saldoFactura'] > doubleval($rowNotaCred['saldoNotaCredito'])) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = doubleval($rowNotaCred['saldoNotaCredito']);
			} else if ($rowFact['saldoFactura'] < doubleval($rowNotaCred['saldoNotaCredito'])) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = doubleval($rowFact['saldoFactura']);
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				//"cbxPosicionPago" => $valor,
				//"hddIdPago" => $hddIdPago,
			"txtIdFormaPago" => 8, // 8 = Nota de Crédito
			"txtIdNumeroDctoPago" => $idNotaCredito,
				//"txtNumeroDctoPago" => $frmListaPagos['txtNumeroDctoPago'.$valor2],
				//"txtIdBancoCliente" => $frmListaPagos['txtIdBancoCliente'.$valor2],
				//"txtCuentaClientePago" => $frmListaPagos['txtCuentaClientePago'.$valor2],
				//"txtIdBancoCompania" => $frmListaPagos['txtIdBancoCompania'.$valor2],
				//"txtIdCuentaCompaniaPago" => $frmListaPagos['txtIdCuentaCompaniaPago'.$valor2],
				//"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valor2]),
				//"txtFechaDeposito" => $frmListaPagos['txtFechaDeposito'.$valor2],
				//"txtTipoTarjeta" => $frmListaPagos['txtTipoTarjeta'.$valor2],
				//"hddObjDetalleDeposito" => $frmDetallePago['hddObjDetalleDeposito'],
				//"hddObjDetalleDepositoFormaPago" => $frmDetallePago['hddObjDetalleDepositoFormaPago'],
				//"hddObjDetalleDepositoBanco" => $frmDetallePago['hddObjDetalleDepositoBanco'],
				//"hddObjDetalleDepositoNroCuenta" => $frmDetallePago['hddObjDetalleDepositoNroCuenta'],
				//"hddObjDetalleDepositoNroCheque" => $frmDetallePago['hddObjDetalleDepositoNroCheque'],
				//"hddObjDetalleDepositoMonto" => $frmDetallePago['hddObjDetalleDepositoMonto'],
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
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
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
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarNotaCredito($idNotaCredito);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	}
	
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
											- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
													WHERE cxc_ant.idCliente = cliente_emp.id_cliente
														AND cxc_ant.id_empresa = cliente_emp.id_empresa
														AND cxc_ant.estadoAnticipo IN (1,2)
														AND cxc_ant.estatus = 1), 0)
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
							- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
									WHERE cxc_ant.idCliente = cliente_emp.id_cliente
										AND cxc_ant.id_empresa = cliente_emp.id_empresa
										AND cxc_ant.estadoAnticipo IN (1,2)
										AND cxc_ant.estatus = 1), 0)
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
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasRe")) { generarNotasRe($idNotaCredito,"",""); } break;
					case 1 : if (function_exists("generarNotasVentasSe")) { generarNotasVentasSe($idNotaCredito,"",""); } break;
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
					case 3 : if (function_exists("generarNotasVentasAd")) { generarNotasVentasAd($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	errorGuardarDcto($objResponse);
	$objResponse->alert("Nota de Crédito Guardada con Éxito");
	
	$objResponse->script("
	window.location.href='cjrs_devolucion_venta_list.php';
	verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=".$idNotaCredito."', 960, 550);");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"reconversion");

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

function errorGuardarDcto($objResponse){
	$objResponse->script("
	byId('btnGuardar').disabled = false;
	byId('btnCancelar').disabled = false;");
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