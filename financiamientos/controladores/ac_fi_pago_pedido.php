<?php 

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();

	$tipoMotivo = 'I' ; // TIPO DE MOTIVOS PARA INGRESOS DE CXC

	$valBusq = sprintf("%s|%s|%s",
			$tipoMotivo,
			$frmBuscarMotivo['hddObjDestinoMotivo'],
			$frmBuscarMotivo['txtCriterioBuscarMotivo']);

	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));

	return $objResponse;
}


function calcularDcto($frmListaMotivo, $cuotaMotivo,$contMotivo = '') {

	$objResponse = new xajaxResponse();


	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbx'.$cuotaMotivo];

	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;

			$objResponse->assign("trItmMotivo$cuotaMotivo:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmMotivo$cuotaMotivo:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjItmMotivo","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));


	// CALCULA EL SUBTOTAL
	$txtSubTotal = '';
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$cuotaMotivo.$valor]);
			$txtSubTotal += $txtTotalItm;
		}
	}

	//CALCULA EL SALDO ACTUAL
	if (isset($arrayObj)) {
		$saldo = $frmListaMotivo['hddTotalSaldo'.$cuotaMotivo] - $txtSubTotal;
	}else{
		$objResponse->script(sprintf("xajax_cargarCampos(xajax.getFormValues('frmMotivos'),'Motivos','%s','','activo');",$cuotaMotivo));

	}
	$objResponse->assign("txtSubTotal$cuotaMotivo","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtTotalSaldo$cuotaMotivo","value",number_format($saldo, 2, ".", ","));

	$objResponse->script(sprintf("xajax_validarFrmMotivo('%s',xajax.getFormValues('frmMotivos'),'%s');",$contMotivo,$cuotaMotivo));

	return $objResponse;
}



function cargarCampos($idPedido = null) {

	$objResponse = new xajaxResponse();
	
	if($idPedido == null){
		return $objResponse;
	}

	$query = sprintf("SELECT 
						pedido.id_pedido_financiamiento,
						CONCAT_WS(' ',emp.nombre_empleado,emp.apellido) AS empleado,
						pedido.estatus_pedido,
						pedido.id_cliente,
						pedido.id_empresa,
						pedido.id_notadecargo_cxc,
						(SELECT CASE int_mora.descripcion_interes_mora
							  WHEN 1 THEN 'Cuotas Fijas'
							  WHEN 2 THEN 'Porcentajes Fijos'
						 ELSE 'NO TIENE'
						 END ) AS tipo_interes_mora,
						CONCAT_WS(' ',int_mora.valor_interes_mora,(SELECT CASE int_mora.descripcion_interes_mora
							  WHEN 1 THEN '".cAbrevMoneda."'
							  WHEN 2 THEN '%s'
						 ELSE 'NO TIENE'
						 END )) AS valor_interes_mora,
						cliente.id,
						CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
						CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
						cliente.telf,
						cliente.nit AS nit_cliente,
						cliente.direccion,
						cliente.telf,
						cliente.otrotelf,
						pedido.numeracion_pedido AS numeracion,
						empresa.nombre_empresa AS empresa,
						pedido.id_empresa AS id_emepresa,
						pedido.fecha_financiamiento AS fecha_inicial,
						pedido.fecha_fin_financiamiento AS fecha_final,
						(CASE pedido.tipo_interes
							WHEN 1 THEN 'SIMPLE'
							WHEN 2 THEN 'COMPUESTO'
						END) AS tipo_interes,
						pedido.tipo_interes AS id_tipo_interes,
						pedido.interes_financiamiento AS interes,
						CONCAT_WS(' ',pedido.cuotas_duracion, 
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_duracion_plazo)) AS duracion,
						(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_frecuencia_plazo) AS frecuencia,
						pedido.numero_pagos,
						pedido.monto_financiamiento_documentos AS total_inicial,
						pedido.total_adicionales AS total_adicionales,
						pedido.total_intereses AS total_intereses,
						pedido.interes_efectivo,
						pedido.id_frecuencia_plazo,
						pedido.total_monto_financiar AS total_cuotas
					FROM fi_pedido pedido
						INNER JOIN cj_cc_cliente cliente ON (pedido.id_cliente = cliente.id)
						INNER JOIN pg_empresa empresa ON (pedido.id_empresa = empresa.id_empresa)
						INNER JOIN fi_plazos plazo ON (pedido.id_duracion_plazo = plazo.id_plazo)
						INNER JOIN pg_empleado emp ON (pedido.id_empleado = emp.id_empleado)
						LEFT JOIN fi_intereses_mora int_mora ON (pedido.id_interes_mora = int_mora.id_interes_mora)
					WHERE pedido.id_pedido_financiamiento = %s",'%', valTpDato($idPedido, "int"));

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rs);
	$rowPedido = mysql_fetch_assoc($rs);
	
	
	$idNotaDeCargo = $rowPedido['id_notadecargo_cxc'];
	
	//RESUMEN DEL PEDIDO
	
		//DATOS GENERALES   
		
			$objResponse->assign("txtEmpresa","value",utf8_encode($rowPedido['empresa']));
			$objResponse->assign("hddIdEmpresa","value",utf8_encode($rowPedido['id_emepresa']));
			$objResponse->assign("txtUsuario","value",utf8_encode($rowPedido['empleado']));
			$objResponse->assign("txtPedido","value",utf8_encode($rowPedido['numeracion']));
			$objResponse->assign("txtFechaFinanciar","value",date(spanDateFormat,strtotime($rowPedido['fecha_inicial'])));
			$objResponse->assign("txtFechaCulminar","value",date(spanDateFormat,strtotime($rowPedido['fecha_final'])));
	
		//DATOS DEL CLIENTE
		
			$objResponse->assign("txtIdCliente","value",$rowPedido['id']);
			$objResponse->assign("txtNombreCliente","value",utf8_encode($rowPedido['nombre_cliente']));
			$objResponse->assign("txtDireccionCliente","value",utf8_encode(elimCaracter($rowPedido['direccion']),";"));
			$objResponse->assign("txtTelefonoCliente","value",$rowPedido['telf']);
			$objResponse->assign("txtOtroTelefonoCliente","value",$rowPedido['otrotelf']);
			$objResponse->assign("txtNITCliente","value",$rowPedido['nit_cliente']);
			$objResponse->assign("txtRifCliente","value",$rowPedido['ci_cliente']);

		//DATOS DE FINANCIAMIENTO
		
			$objResponse->assign("txtInteresFinanciar","value",$rowPedido['interes']);
			$objResponse->assign("txtFrecuenciaPago","value",$rowPedido['frecuencia']);
			$objResponse->assign("txtMontoPagoDocumentos","value",number_format($rowPedido['total_cuotas'],2,".",","));
			$objResponse->assign("txtTipoInteres","value",$rowPedido['tipo_interes']);
			$objResponse->assign("txtCuotasFinanciar","value",$rowPedido['duracion']);
			$objResponse->assign("txtNumeroPagos","value",$rowPedido['numero_pagos']);
		
			
			
		//DATOS INTERES POR MORA
			
			$objResponse->assign("txtTipoInteresMora","value",$rowPedido['tipo_interes_mora']);
			$objResponse->assign("txtValorInteresMora","value",$rowPedido['valor_interes_mora']);
			
	// AVANCE DE FINANCIAMIENTO
			
			$queryAmortizacion = sprintf("SELECT * FROM fi_amortizacion WHERE id_pedido_financiamiento = %s", 
							valTpDato($idPedido, "int"));
			$rs = mysql_query($queryAmortizacion);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$totalRows = mysql_num_rows($rs);
			
			$cuotaPendiente = 0; $cuotaPagada = 0; $saldoActual = 0; $montoAmort = 0;  $interesesPagados = 0; $adicionalesPagados = 0;
			
			while($row = mysql_fetch_assoc($rs)){
				$amortizaciones[] = $row;
				if($row['estado_cuota'] == 0 || $row['estado_cuota'] == 2){ //SI NO ESTA PAGADO O ESTA ATRASADO
					$cuotaPendiente++;
				}else{
					$cuotaPagada++;
					$montoAmort += $row['amortizacion_cuota'];
					$interesesPagados += $row['interes_cuota'];
					$adicionalesPagados += $row['adicional_cuota'];
				}
			}
			
			$objResponse->loadCommands(comprobarPagos($cuotaPendiente,$idPedido));
			
			$cuotaTotal = $cuotaPendiente+$cuotaPagada;
			$interesesPagados = number_format($interesesPagados,2,".",",");
			$montoPagado = number_format($montoAmort + $interesesPagados +$adicionalesPagados, 2,".",",");
			$montoAmort = number_format($montoAmort,2,".",",");
			$saldoActual = number_format($rowPedido['total_cuotas'] + $rowPedido['total_adicionales'] - str_replace(",", "", $montoPagado),2,".",",");
			$totalAdicionales = number_format($rowPedido['total_adicionales'], 2,".",",");
			$adicionalesPagados = number_format($adicionalesPagados,2,".",",");
			$adicionalActual = number_format(str_replace(",", "", $totalAdicionales) - str_replace(",", "", $adicionalesPagados),2,".",",");
			
			//DATOS DE TIEMPO
			
			$objResponse->assign("txtCuotasPendientes","value","$cuotaPendiente");
			$objResponse->assign("txtCuotasAmortizadas","value","$cuotaPagada");
			$objResponse->assign("txtCuotasTotales","value","$cuotaTotal");
				
			//DATOS DE CUOTAS
			
			$objResponse->assign("txtMontoPagado","value",$montoPagado);
			$objResponse->assign("txtMontoAmortizado","value",$montoAmort);
			$objResponse->assign("txtInteresesPagados","value",$interesesPagados);
			if($totalAdicionales > 0 || $totalAdicionales != null){
				$objResponse->script("byId('trAdicionalesAmortizados').style.display = '';");
				$objResponse->script("byId('trSaldoAdicionales').style.display = '';");
				$objResponse->assign("txtAdicionalesAmortizados","value",$adicionalesPagados);
				$objResponse->assign("txtSaldoAdicionales","value",$adicionalActual);
			}
			
			$objResponse->assign("txtSaldoTotal","value",$saldoActual);
			
			//FONDOS DE DATOS DE TIEMPO
			$percent = calcularWidth($cuotaPendiente, $cuotaTotal);
			$objResponse->script("byId('txtCuotasPendientes').style.backgroundSize = '$percent% 95%';");
			$percent = calcularWidth($cuotaPagada, $cuotaTotal);
			$objResponse->script("byId('txtCuotasAmortizadas').style.backgroundSize = '$percent% 95%';");
			$objResponse->script("byId('txtCuotasTotales').style.backgroundSize = '100% 95%';");

			//FONDOS DE DATOS DE CUOTAS
			
			$percent = calcularWidth(str_replace(",", "", $montoPagado), $rowPedido['total_cuotas']);
			$objResponse->script("byId('txtMontoPagado').style.backgroundSize = '$percent% 95%';");
			$percent = calcularWidth(str_replace(",", "", $montoAmort), $rowPedido['total_inicial']);
			$objResponse->script("byId('txtMontoAmortizado').style.backgroundSize = '$percent% 95%';");
			$percent = calcularWidth(str_replace(",", "", $interesesPagados), $rowPedido['total_intereses']);
			$objResponse->script("byId('txtInteresesPagados').style.backgroundSize = '$percent% 95%';");
			$percent = calcularWidth(str_replace(",", "", $adicionalesPagados), $rowPedido['total_adicionales']);
			$objResponse->script("byId('txtAdicionalesAmortizados').style.backgroundSize = '$percent% 95%';");
			$percent = calcularWidth(str_replace(",", "", $saldoActual), $rowPedido['total_cuotas']);
			$objResponse->script("byId('txtSaldoTotal').style.backgroundSize = '$percent% 95%';");
			$percent = calcularWidth(str_replace(",", "", $adicionalActual), $rowPedido['total_adicionales']);
			$objResponse->script("byId('txtSaldoAdicionales').style.backgroundSize = '$percent% 95%';");
	
			
	//PAGO RAPIDO
	
			$queryAmort = sprintf("SELECT * 
					FROM fi_amortizacion amort
					WHERE amort.id_pedido_financiamiento = %s
						AND amort.estado_cuota IN(0,2)", // estado_cuota = 0, que aun no esten pagadas y 2 = ATRASADAS
					valTpDato($rowPedido['id_pedido_financiamiento'], "int"));
		
			$rs = mysql_query($queryAmort);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$totalRows = mysql_num_rows($rs);
			
			$cont = 0;
			
			while($rowAmort = mysql_fetch_assoc($rs)){
				
				
				//VARIABLES
				
				$idCuota = $rowAmort['id_amortizacion'];
				
				//ASIGNANDO EL ID DE LA NOTA DE CARGO DE LA CUOTA MOROSA
				
				$queryMorosa = sprintf("SELECT
							amort_mora.id_notadecargo_cxc
				FROM  fi_amortizacion amort
				INNER JOIN fi_amortizacion_interesmora amort_mora ON (amort.id_amortizacion = amort_mora.id_amortizacion)
				WHERE amort.id_amortizacion = %s",
						valTpDato($idCuota, "int"));
				
				$rsMorosa = mysql_query($queryMorosa);
				if (!$rsMorosa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				$totalRows = mysql_num_rows($rsMorosa);
				$rowAmortizacion = mysql_fetch_assoc($rsMorosa);
				
				$idNotaDeCargo = $rowAmortizacion['id_notadecargo_cxc'];
				
				$periodo = $rowAmort['periodo_cuota'];
				$fecha = $rowAmort['fecha_cuota'];
				$amortizacion = number_format($rowAmort['amortizacion_cuota'],2,".",",");
				$interes = number_format($rowAmort['interes_cuota'],2,".",",");
				$cuota = number_format($rowAmort['monto_cuota'],2,".",",");
				//COMPROBANDO ADICIONALES
				
				($cont == 0) ? $valor = "Actual": $valor = "Siguiente" ; 
				
				if($rowPedido['total_adicionales'] != null){
					$objResponse->script("byId('trAdicionales$valor').style.display = '';");
					$adicionales = number_format($rowAmort['adicional_cuota'],2,".",",");
				}
				//PAGO ACTUAL
				
				$objResponse->assign("txtPeriodo$valor","value","$periodo");
				$objResponse->assign("txtFechaLimite$valor","value",date(spanDateFormat,strtotime($fecha)));
				$objResponse->assign("txtCuotaAmortizacion$valor","value","$amortizacion");
				if($rowPedido['total_adicionales'] != null){
					$objResponse->assign("txtAdicionales$valor","value","$adicionales");
				}
				$objResponse->assign("txtIntereses$valor","value","$interes");
				$objResponse->assign("txtCuota$valor","value","$cuota");
				$objResponse->script("byId('txtCuota$valor').style.font = 'bold';");
				
				if($cont == 1){
					$objResponse->assign("txtPagoCuota$valor", "disable", true);
					break;
				}
				$cont++;			
				
				//CARGANDO BOTON DE PAGO PERZONALIZADO
				

				$html ="<table width=\"100%\">";
					$html .="<tr width=\"100%\">";
						if($rowAmort['estado_cuota'] == 2){ //Cuotas con intereses por mora
							
							$valorTipoInteresMora = $rowPedido['tipo_interes_mora'];
							
							$valorInteresMora = cargarCamposInteresMora($idPedido, $periodo);
							
							switch($valorTipoInteresMora){
								case 'Cuotas Fijas': //Cuotas Fijas
									$valorInteresMora = cAbrevMoneda." ".$valorInteresMora;
									break;
								case 'Porcentajes Fijos': //Porcentajes Fijos
									$valorInteresMora = $valorInteresMora." %";
									break;
							}
							
							$html .="<td width=\"100%\"  colspan=\"3\" >";
								$html .="<table width=\"100%\">";
									$html .= "<tr width=\"100%\">";
										$html .="<td  width=\"60%\" class=\"divResaltarRojo\" style=\"text-align: center\">PAGO ATRASADO CON  <b>".strtoupper($valorTipoInteresMora)."</b></td>";
										$html .="<td  width=\"10%\" style=\"text-align: center\"><input id=\"txtSaldoAdicionales\" value=\"$valorInteresMora\"  style=\"text-align: center\" size=\"10\" readonly=\"readonly\"  type=\"text\"></td>";
										$html .="<td width=\"30%\" style=\"text-align: center;\" onclick=\"xajax_pagar('$idCuota','$idNotaDeCargo','mora');\" class=\"divResaltarNaranja puntero\"><table width=\"100%\"><tr width=\"100%\"><td width=\"80%\" >PAGAR CUOTA</td><td width=\"20%\" ><img src=\"../img/iconos/money_add.png\" title=\"Pagar Interes Mora\" class=\"puntero\" id=\"imgPedido\" align=\"center\"></td></tr></table></td>";
									$html .= "</tr>";
								$html .="</table>";
							$html .= "</td>";
							
						} else {
							$html .= "<td width=\"33%\"></td>";
							$html .="<td width=\"33%\" align=\"center\" onclick=\"xajax_pagar('$idCuota','$idNotaDeCargo','cuota');\" class=\"tdSelecBotonSucces puntero\"><table width=\"100%\"><tr width=\"100%\"><td width=\"75%\" style=\"text-align: right;\">PAGAR CUOTA</td><td width=\"25%\" style=\"text-align: left;\"><img src=\"../img/iconos/money_add.png\" title=\"Pagar Interes Mora\" class=\"puntero\" id=\"imgPedido\" align=\"center\"></td></tr></table></td>";
							$html .= "<td width=\"33%\"></td>";
						}
					$html .="</tr>";
				$html .="</table>";
				$objResponse->assign("txtPagoCuota$valor", "innerHTML", $html);
			}
			
	//CUADRO DE AMORTIZACIONES
	
			$objResponse->loadCommands(mostrarAmortizaciones($rowPedido,$amortizaciones));
			
			return $objResponse;

}

function cargarFrmInteresMora ($frmPagoPedido) {
	
	$objResponse = new xajaxResponse();
	
	$idPedido = $frmPagoPedido['hddIdPedido'];
	$cuotasMora = implode(",",$frmPagoPedido['hddCuotasMora']);
	
	$queryMora = sprintf("SELECT *
							FROM fi_amortizacion amort
						 WHERE amort.id_pedido_financiamiento = %s 
						 AND amort.periodo_cuota IN (%s);",
				valTpDato($idPedido, "int"),
				$cuotasMora);
	
	$rsInteresMora = mysql_query($queryMora);
	if (!$rsInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsInteresMora);
	
	
	while($rowIntMora = mysql_fetch_assoc($rsInteresMora)){
		
		$perIntMora = $periodos[] = $rowIntMora['periodo_cuota'];
		
		$html .= "<tr>".
					"<td>".	
						"<fieldset><legend class=\"legend\">Interes de Mora del Periodo ".$perIntMora."</legend>".
							"<table border=\"0\" width=\"100%\">".	
								"<tr id=\"trListaMotivo".$perIntMora."\" align=\"left\">".
										"<td>".
											"<table border=\"0\" width=\"100%\">".
												"<tr>".
													"<td align=\"left\" colspan=\"20\">".
														"<a class=\"modalImg\" id=\"aAgregarMotivo\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this,$perIntMora);\">".
															"<button type=\"button\" title=\"Agregar Motivo\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/add.png\"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>".
														"</a>".
														"<button type=\"button\" id=\"btnQuitarMotivo\" name=\"btnQuitarMotivo\" onclick=\"xajax_eliminarMotivoLote(xajax.getFormValues('frmMotivos'),'$perIntMora');\" title=\"Eliminar Artículo\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/delete.png\"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>".
													"</td>".
												"</tr>".
												"<tr align=\"center\" class=\"tituloColumna\">".
													"<td><input type=\"checkbox\" id=\"cbxItm$perIntMora\" onclick=\"selecAllChecks(this.checked,this.id,'frmMotivos');\"/></td>".
													"<td width=\"4%\">Nro.</td>".
													"<td width=\"14%\">Código</td>".
													"<td width=\"40%\">Descripción</td>".
													"<td width=\"16%\">Módulo</td>".
													"<td width=\"16%\">Tipo Transacción</td>".
													"<td width=\"10%\">Total</td>".
													"<td><input type=\"hidden\" id=\"hddObjItmMotivo$perIntMora\" name=\"hddObjItmMotivo$perIntMora\" readonly=\"readonly\" title=\"hddObjItmMotivo$perIntMora\"/></td>".
												"</tr>".
												"<tr id=\"trItmPie$perIntMora\"></tr>".
											"</table>".
										"</td>".
									"</tr>".
									"<tr width=\"100%\">".
										"<td width=\"100%\" align=\"right\">".
											"<input type=\"hidden\" id=\"hddObj$perIntMora\" name=\"hddObj$perIntMora\" readonly=\"readonly\"/>".
											"<table border=\"0\" width=\"100%\">".
												"<tr width=\"100%\">".
													"<td valign=\"top\" width=\"50%\">".
														"<table width=\"100%\">".
															"<tr align=\"left\">".
																"<td class=\"tituloCampo\">Observación:</td>".
															"</tr>".
															"<tr align=\"left\">".
																"<td><textarea class=\"inputHabilitado\" id=\"txtObservacion$perIntMora\" name=\"txtObservacion$perIntMora\" rows=\"3\" style=\"width:99%\"></textarea></td>".
															"</tr>".
														"</table>".
													"</td>".
													"<td valign=\"top\" width=\"50%\">".
														"<table border=\"0\" width=\"100%\">".
															"<tr height=\"35px\"></tr>".
															"<tr align=\"right\" class=\"trResaltarTotal\">".
																"<td class=\"tituloCampo\" width=\"36%\">Subtotal:</td>".
																"<td style=\"border-top:1px solid;\" id=\"tdSubTotalMoneda$perIntMora\" width=\"42%\"></td>".
																"<td style=\"border-top:1px solid;\" width=\"22%\"><input type=\"text\" id=\"txtSubTotal$perIntMora\" name=\"txtSubTotal$perIntMora\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2);\" readonly=\"readonly\" style=\"text-align:right\"/></td>".
															"</tr>".
															"<tr align=\"right\" class=\"trResaltarTotal3\">".
																"<td class=\"tituloCampo\">Saldo Disponible:</td>".
																"<td id=\"tdTotalSaldoMoneda$perIntMora\"></td>".
																"<td><input type=\"text\" id=\"txtTotalSaldo$perIntMora\" name=\"txtTotalSaldo$perIntMora\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\"/></td>".
																"<input type=\"hidden\" id=\"hddTotalSaldo$perIntMora\" name=\"hddTotalSaldo$perIntMora\"/>".
															"</tr>".
														"</table>".
													"</td>".
												"</tr>".
											"</table>".
										"</td>".
									"</tr>".
								"</table>".
							"</fieldset>".
						"</td>".
					"</tr>";
		$contMora--;	
	}
	
	$html .= "<tr>".
				"<td align=\"right\"><hr>".
					"<button type=\"button\" id=\"btnGuardarInteresMora\" name=\"btnGuardarInteresMora\"  onclick=\"xajax_validarSaldosInteresMora(xajax.getFormValues('frmMotivos'),xajax.getFormValues('frmPagoPedido'));\">Guardar</button>".
				"</td>".
			"</tr>";
	
	$objResponse->assign("tblMotivosIntMora","innerHTML",$html);
	
	for($i = 0; $i <= $totalRows-1;$i++){
		$arraySaldo = cargarCamposInteresMora($idPedido,$periodos[$i]);
		$objResponse->assign("txtTotalSaldo$periodos[$i]", "value", number_format($arraySaldo,2,".",","));
		$objResponse->assign("hddTotalSaldo$periodos[$i]", "value", $arraySaldo);
	}
	
	return $objResponse;
}

function cargarCamposInteresMora($idPedido,$periodo) {
	
	
	$query = sprintf("SELECT
						*,
						(SELECT
							 amort.monto_cuota
							FROM fi_amortizacion amort
						WHERE amort.id_pedido_financiamiento = %s
						AND amort.periodo_cuota = %s) AS monto_amort
				FROM  fi_pedido pedido
				INNER JOIN fi_intereses_mora int_mora ON (pedido.id_interes_mora = int_mora.id_interes_mora)
				WHERE pedido.id_pedido_financiamiento = %s", 
			valTpDato($idPedido, "int"),
			valTpDato($periodo, "int"),
			valTpDato($idPedido, "int"));

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rs);
	$rowPedido = mysql_fetch_assoc($rs);
	
	//CALCULANDO EL INTERES DE ACUERDO AL TIPO DE INTERES DE MORA SELECCIONADO EN EL PEDIDO
	
	$tipoInteresMora = $rowPedido['descripcion_interes_mora'];
	
	switch($tipoInteresMora){
		case 1: //Cuotas Fijas
			$saldoInteresMora = $rowPedido['valor_interes_mora'];
		break;
		case 2: //Porcentajes Fijos
			$saldoInteresMora = $rowPedido['monto_amort']*($rowPedido['valor_interes_mora']/100);
		break;
	}
	
	return $saldoInteresMora;
}

function comprobarPagos($cuotaPendiente,$idPedido) {
	
	$objResponse = new xajaxResponse();
	
	if($cuotaPendiente == 0){
		
		$updateSQL = sprintf("UPDATE fi_pedido SET
					estatus_pedido = 2
					WHERE id_pedido_financiamiento = %s;",
				valTpDato($idPedido, "int")); //Pedido Pagado
		
		$rsUpdateSQL = mysql_query($updateSQL);
		if (!$rsUpdateSQL) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	
	$objResponse->alert("Pagos de financiamiento completado.");
	$redireccion = sprintf("window.location='fi_historico_list?id=%s'",valTpDato($idPedido, "int"));
	$objResponse->script($redireccion);	
	
	}
	
	return $objResponse;
	
}

function dibujarCuadroAmortizacion($frmFinDetalle){
	
	$objResponse = new xajaxResponse();
	
	$pagos = $frmFinDetalle['txtNumeroPagos'] ;
	
	for($i = 0; $i <= $pagos; $i++){
		$widthInteresCuota[] = floatval(calcularWidth(str_replace(",", "", $frmFinDetalle['interes'][$i]),str_replace(",", "", $frmFinDetalle['interes'][0])));
		$widthAmortizacionCuota[] = floatval(calcularWidth(str_replace(",", "", $frmFinDetalle['amortizacion'][$i]),str_replace(",", "", $frmFinDetalle['amortizacion'][$pagos-1])));
		$widthCuotaFinal[] = floatval(calcularWidth($frmFinDetalle['cuota'][$i],$frmFinDetalle['cuota'][$pagos-1]));
		$widthCapitalVivo[] = floatval(calcularWidth(str_replace(",", "", $frmFinDetalle['capitalVivo'][$i]),str_replace(",", "", $frmFinDetalle['capitalVivo'][0])));
	}
	
	for($i = 0; $i <= $pagos; $i++){
		
		$objResponse->script("byId('interes$i').style.backgroundSize = '$widthInteresCuota[$i]% 95%';");
		$objResponse->script("byId('amortizacion$i').style.backgroundSize = '$widthAmortizacionCuota[$i]% 95%';");
		$objResponse->script("byId('capital$i').style.backgroundSize = '$widthCapitalVivo[$i]% 95%';");
		
	}
	
	return $objResponse;
	
}

function eliminarMotivo($trItmMotivo, $frmListaMotivos,$cuotaMotivo) {
	$objResponse = new xajaxResponse();

	if (isset($trItmMotivo) && $trItmMotivo > 0) {
		$objResponse->script("
				fila = document.getElementById('trItmMotivo$cuotaMotivo:".$trItmMotivo."');
		padre = fila.parentNode;
		padre.removeChild(fila);");

		$objResponse->script(sprintf("xajax_eliminarMotivo('', xajax.getFormValues('frmMotivos'),'%s');",$cuotaMotivo));
	}

	$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmMotivos'),'%s','');",$cuotaMotivo));

	return $objResponse;
}


function eliminarMotivoLote($frmListaMotivos,$cuotaMotivo) {
	$objResponse = new xajaxResponse();

	if (count($frmListaMotivos['cbxItm'.$cuotaMotivo]) > 0) {
		foreach ($frmListaMotivos['cbxItm'.$cuotaMotivo] as $indiceItm => $valorItm) {
			$objResponse->script("
					fila = document.getElementById('trItmMotivo$cuotaMotivo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmMotivos'),'%s','');",$cuotaMotivo));
	}
	
	return $objResponse;
}

function guardarInteresMora($frmMotivos,$frmPagoPedido) {
	
	$objResponse = new xajaxResponse();	
	
	//QUERY DEL PEDIDO CON TODOS LOS DATOS
	mysql_query("START TRANSACTION;");
	
//	DESCONCATENA PARA SABER CUANTOS ITEMS DE MOTIVOS HAY AGREGADOS

	for($i=0 ; $i < count($frmPagoPedido['hddCuotasMora']) ; $i++){
		
		$cuotaMora = $frmPagoPedido['hddCuotasMora'][$i];
		$idCuota = $frmPagoPedido['hddIdCuotasMora'][$i];
		$arrayObj = $frmMotivos['cbx'.$cuotaMora];
		
		// NUMERACION DE NOTA DE CARGO INTERES DE MORA (CUOTAS ATRASADAS)
		$queryNumeracionInteresMora = sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato( 53 , "int"), // 53 = Numeracion de Nota de Cargo Financiamiento
				valTpDato($frmPagoPedido['hddIdEmpresa'], "int"),
				valTpDato($frmPagoPedido['hddIdEmpresa'], "int"));
		
		$rsNumeracionInteresMora = mysql_query($queryNumeracionInteresMora);
		if (!$rsNumeracionInteresMora) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracionInteresMora = mysql_fetch_assoc($rsNumeracionInteresMora);
		
		$idEmpresaNumeracionInteresMora = $rowNumeracionInteresMora['id_empresa_numeracion'];
		$idNumeracionesInteresMora = $rowNumeracionInteresMora['id_numeracion'];
		$numeroActualInteresMora = $rowNumeracionInteresMora['prefijo_numeracion'].$rowNumeracionInteresMora['numero_actual'];
		$numeroSiguienteInteresMora = $numeroActualInteresMora+1;
		
		
		// ACTUALIZA LA NUMERACIÓN DE LA NOTA DE CARGO (PEDIDO DE FINANCIAMIENTO)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
					WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeracionInteresMora, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		//CREANDO LA NOTA DE CARGO ASOCIADA AL PEDIDO DE FINANCIAMIENTO
		
		$observacionInteresMora = 'NOTA DE CARGO POR INTERES DE MORA DE LA CUOTA '.strtoupper($cuotaMora).'. '.strtoupper($frmMotivos['txtObservacion'.$cuotaMora]);
		
		$queryAmortizacion = sprintf("SELECT * 
										FROM fi_amortizacion 
									WHERE id_pedido_financiamiento = %s
									AND periodo_cuota = %s",
				valTpDato($frmPagoPedido['hddIdPedido'], "int"),
				valTpDato($cuotaMora, "int"));
		$rs = mysql_query($queryAmortizacion);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$totalRows = mysql_num_rows($rs);
		$rowAmortizacion = mysql_fetch_assoc($rs);
		
		$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (id_empresa, idCliente, numeroNotaCargo, numeroControlNotaCargo, fechaRegistroNotaCargo, fechaVencimientoNotaCargo, idDepartamentoOrigenNotaCargo, tipoNotaCargo, estadoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, subtotalNotaCargo, observacionNotaCargo, id_empleado_creador)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($frmPagoPedido['hddIdEmpresa'], "int"),
				valTpDato($frmPagoPedido['txtIdCliente'], "int"),
				valTpDato($numeroActualInteresMora, "text"),
				valTpDato($numeroActualInteresMora, "text"),
				valTpDato(date("Y-m-d", strtotime($rowAmortizacion['fecha_cuota'])), "date"),
				valTpDato(date("Y-m-d", strtotime($frmPagoPedido['txtFechaCulminar'])), "date"),
				valTpDato(5, "int"), // 5 = MODULO DE FINANCIAMIENTO
				valTpDato(0, "int"), // 0 = Credito
				valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
				valTpDato($frmMotivos['hddTotalSaldo'.$cuotaMora], "real_inglesa"),
				valTpDato($frmMotivos['hddTotalSaldo'.$cuotaMora], "real_inglesa"),
				valTpDato($frmMotivos['hddTotalSaldo'.$cuotaMora], "real_inglesa"),
				valTpDato($observacionInteresMora, "text"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idNotaCargo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
				valTpDato("ND", "text"),
				valTpDato($idNotaCargo, "int"),
				valTpDato(date("Y-m-d", strtotime($rowAmortizacion['fecha_cuota'])), "date"),
				valTpDato("2", "int")); //1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTA EL INTERES DE MORA DE LA CUOTA PENDIENTE
		$insertSQL = sprintf("INSERT INTO fi_amortizacion_interesmora (id_pedido_financiamiento, id_amortizacion, id_interes_mora, id_notadecargo_cxc, estado_pago)
		VALUE (%s, %s, %s, %s, %s);",
				valTpDato($frmPagoPedido['hddIdPedido'], "int"),
				valTpDato($idCuota, "int"),
				valTpDato($frmPagoPedido['hddIdInteresMora'], "int"),
				valTpDato($idNotaCargo, "int"),
				valTpDato("0", "int")); // PAGO PENDIENTE POR REALIZAR
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTA EL DETALLE DEL DOCUMENTO MOTIVOS
		if (count($arrayObj) > 0) {
			foreach($arrayObj as $indice => $valor) {
				$idMotivo = $frmMotivos['hddIdMotivoItm'.$cuotaMora.$valor];
				$precioUnitario = str_replace(",", "", $frmMotivos['txtPrecioItm'.$cuotaMora.$valor]);

				$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_detalle_motivo (id_nota_cargo, id_motivo, cantidad, precio_unitario)
									VALUE (%s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato($idMotivo, "int"),
						valTpDato("1", "int"),
						valTpDato($precioUnitario, "real_inglesa"));
					
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idNotaDebitoDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}

		}
		
		//ACTUALIZANDO EL ESTADO DE LA CUOTA DE AMORTIZACION A ATRASADA
		
		$updateCuotaSQL = sprintf("UPDATE fi_amortizacion SET 
					estado_cuota = %s
							WHERE id_amortizacion = %s",
						valTpDato("2", $theType), //ASIGNANDO CUOTA A UN ESTADO 2 =  ATRASADO
						valTpDato($idCuota, $theType));
		$Result1 = mysql_query($updateCuotaSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	//RECARGANDO LA PAGINA
	
	$objResponse->script(" byId('hddVerificarMotivoInteresMora').value = 1;
							window.location.reload();");
	
	return $objResponse;
}

function insertarItemMotivo($contFila, $hddIdNotaCreditoDet = "", $idMotivo = "", $precioUnitario = "",$cuotaMotivo = "") {
	$contFila++;

	$idMotivo = ($idMotivo == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['id_motivo'] : $idMotivo;
	$precioUnitario = ($precioUnitario == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['precio_unitario'] : $precioUnitario;
	$aClassReadonly = ($hddIdNotaCreditoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompleto\"";
	$aEliminar = ($hddIdNotaCreditoDet > 0) ? "" :
	sprintf("<a id=\"aEliminarItm%s:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>",
			$cuotaMotivo,$contFila);

	// BUSCA LOS DATOS DEL ARTICULO
	$queryMotivo = sprintf("SELECT motivo.*,
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
	FROM pg_motivo motivo WHERE id_motivo = %s;",
			valTpDato($idMotivo, "int"));
	$rsMotivo = mysql_query($queryMotivo);
	if (!$rsMotivo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsMotivo = mysql_num_rows($rsMotivo);
	$rowMotivo = mysql_fetch_assoc($rsMotivo);

	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie%s').before('".
			"<tr align=\"left\" id=\"trItmMotivo%s:%s\" title=\"trItmMotivo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmMotivo%s:%s\"><input id=\"cbxItm%s\" name=\"cbxItm%s[]\" type=\"checkbox\" value=\"%s\"/>".
			"<input id=\"cbx%s\" name=\"cbx%s[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmMotivo%s:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s%s\" name=\"txtDescItm%s%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s%s\" class=\"inputHabilitado\"name=\"txtPrecioItm%s%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s".
			"<input type=\"hidden\" id=\"hddIdNotaCreditoDet%s%s\" name=\"hddIdNotaCreditoDet%s%s\" readonly=\"readonly\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddIdMotivoItm%s%s\" name=\"hddIdMotivoItm%s%s\" readonly=\"readonly\" value=\"%s\"></td>".
			"</tr>');

		byId('txtPrecioItm%s%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmMotivos'),'%s',this.id);
		}
		byId('aEliminarItm%s:%s').onclick = function() {
			xajax_eliminarMotivo('%s', xajax.getFormValues('frmMotivos'),'%s');
		}",
			$cuotaMotivo,
			$cuotaMotivo,$contFila, $contFila, $clase,
			$cuotaMotivo,$contFila,$cuotaMotivo, $cuotaMotivo,$contFila,
			$cuotaMotivo,$cuotaMotivo,$contFila,
			$cuotaMotivo,$contFila, $contFila,
			$rowMotivo['id_motivo'],
			$cuotaMotivo,$contFila, $cuotaMotivo,$contFila, $rowMotivo['descripcion'],
			$rowMotivo['descripcion_modulo_transaccion'],
			$rowMotivo['descripcion_tipo_transaccion'],
			$cuotaMotivo,$contFila,$cuotaMotivo, $contFila, $aClassReadonly, number_format($precioUnitario, 2, ".", ","),
			$aEliminar,
			$cuotaMotivo,$contFila,$cuotaMotivo, $contFila, $hddIdNotaCreditoDet,
			$cuotaMotivo,$contFila,$cuotaMotivo, $contFila, $idMotivo,

			$cuotaMotivo,$contFila,
			$cuotaMotivo,
			$cuotaMotivo,$contFila,
			$contFila,$cuotaMotivo);

	return array(true, $htmlItmPie, $contFila);
}



function insertarMotivo($idMotivo, $frmListaMotivos,$cuotaMotivo) {
	$objResponse = new xajaxResponse();


	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivos['cbx'.$cuotaMotivo];
	$contFila = $arrayObj[count($arrayObj)-1];
	foreach ($arrayObj as $indice => $valor){
		if ($frmListaMotivos['hddIdMotivoItm'.$cuotaMotivo.$valor] == $idMotivo) {
			return $objResponse->alert("El motivo seleccionado ya se encuentra agregado");
		}
	}

	$Result1 = insertarItemMotivo($contFila, "", $idMotivo, $precioUnitario,$cuotaMotivo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj[] = $contFila;
	}

	$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmMotivos'),'%s','');",$cuotaMotivo));	
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CC'
	AND ingreso_egreso LIKE '%s'", $valCadBusq[0]);

	$periodoCuota = $valCadBusq[1];
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
				valTpDato("%".$valCadBusq[2]."%", "text"));
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
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Módulo"));
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Transacción"));
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
		$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarMotivo%s\" onclick=\"xajax_insertarMotivo('%s', xajax.getFormValues('frmMotivos'),'%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_motivo'],
				$periodoCuota);
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
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
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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

	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}




function  mostrarAmortizaciones($frmPedido,$frmAmortizaciones){

	$objResponse = new xajaxResponse();

	
	$queryFrecuencia = sprintf("SELECT * FROM fi_plazos WHERE id_plazo = %s;",
			$frmPedido['id_frecuencia_plazo']);

	$rsFrecuencia = mysql_query($queryFrecuencia);
	if (!$rsFrecuencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__."\nQUERY :".$queryFrecuencia);
	$rowPlazos =  mysql_fetch_assoc($rsFrecuencia);

	$cuota = $rowPlazos['nombre_plazo'];
	
	if($frmPedido['total_adicionales'] != null){
		$widthAnterior = 2;
		$widthAdicional = 10;
	}
	
          $htmlTh = "<td>";
                   $htmlTh .="<form id=\"frmCuadro\" name=\"frmCuadro\" >";
                	$htmlTh .="<table width=\"100%\">";
                    	$htmlTh .="<tr>";
                            $htmlTh .="<td id=\"tdCuotasAmortizaciones\">";
                                $htmlTh .="<table border=\"0\" class=\"tablaStripped\" cellpadding=\"2\" width=\"100%\">";
		                  		 $htmlTh .="<caption class=\"tituloPaginaFinanciamientos\" style=\"text-align : center;\">$tituloTabla</caption>";
                                    $htmlTh .="<tr class=\"tituloColumna\">";
                                        $htmlTh .="<td width='6%'>Periodo $cuota</td>";
                                        $htmlTh .="<td width='".(10-$widthAnterior)."%'>Cuota $cuota</td>";
                                        $htmlTh .="<td width='".(10-$widthAnterior)."%'>Fecha $cuota</td>";
                                        if($frmPedido['total_adicionales'] != null) {
                                        	$htmlTh .="<td width='".$widthAdicional."%'>Adicionales $cuota</td>";											
										}		
                                        $htmlTh .="<td width='".(14-$widthAnterior)."%'>Intereses $cuota</td>";
                                        $htmlTh .="<td width='".(22-$widthAnterior)."%'>Cuota Amortizacion</td>";
                                        $htmlTh .="<td width='".(22-$widthAnterior)."%'>Capital Vivo</td>";
                                        $htmlTh .="<td width='6%'>Estatus</td>";
                                        $htmlTh .="<td width='6%'>Pagar</td>";
                                    $htmlTh .="</tr>";
	 
	$dibujar['txtNumeroPagos'] = $numeroPagos = $frmPedido['numero_pagos'];
	$swCuota = true;
	
	for($i = 0; $i <= $numeroPagos-1; $i++){
			
		$pos = $i;
	
		$numeroSemanas = $rowPlazos['semanas'];
		 
		switch ($frmAmortizaciones[$i]['estado_cuota']){
			case 0: $claseEstado = 'divResaltarAmarillo'; $estado = 'PENDIENTE'; break;
			case 1: $claseEstado = 'divResaltarAzul';  $estado = 'CANCELADO'; break;
			case 2: $claseEstado = 'divResaltarRojo';  $estado = 'ATRASADO'; break;
		}
		
		
		$periodo = $frmAmortizaciones[$i]['periodo_cuota'];
		$fecha = $frmAmortizaciones[$i]['fecha_cuota'];
		$idAmortizacion = $frmAmortizaciones[$i]['id_amortizacion'];
		$idNotaDeCargo = $frmPedido['id_notadecargo_cxc'];
		
		$claseResaltar = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
		
		$dibujar['cuota'][] = $cuotaFinal = number_format($frmAmortizaciones[$i]['monto_cuota'], 2 , ".",",");
		$dibujar['interes'][] = $interesCuota = number_format($frmAmortizaciones[$i]['interes_cuota'], 2 , ".",",");
		$dibujar['capitalVivo'][] = $capitalVivo = number_format($frmAmortizaciones[$i]['capital_vivo_cuota'], 2 , ".",",");
		$dibujar['amortizacion'][] = $amortCuota = number_format($frmAmortizaciones[$i]['amortizacion_cuota'], 2 , ".",",");
		
		$adicionalesFin = number_format($frmAmortizaciones[$i]['adicional_cuota'], 2 , ".",",");

		$htmlBody .="<tr class=\"textoGris_11px trResaltarCuadro $claseResaltar\">";
		$htmlBody .="<td  id='periodo$i'>$periodo</td>";
		$htmlBody .="<td  id='cuota$i'>$cuotaFinal</td>";
		$htmlBody .="<td  id='fecha$i'>".date(spanDateFormat,strtotime($fecha))."</td>";
		if($frmPedido['total_adicionales'] != null) {
			$htmlBody .="<td width='".$widthAdicional."%' id='adicional$i' class=\"\">$adicionalesFin</td><input type=\"hidden\" id=\"adicional[]\" name =\"adicional[]\"value=\"$adicionalesFin\">";
		}
		$htmlBody .="<td  id='interes$i' class=\"tdCuadroIntereses\">$interesCuota</td>";
		$htmlBody .="<td  id='amortizacion$i' class=\"tdCuadroAmortizaciones\">$amortCuota</td>";
		$htmlBody .="<td  id='capital$i' class=\"tdCuadroCapital\">$capitalVivo</td>";
		$htmlBody .="<td  class=\"$claseEstado\">$estado</td>";
		if($frmAmortizaciones[$i]['estado_cuota'] == 0 && $swCuota == true && $swAtraso != true){ // 0 = Pendiente y cuota a pagar
			$htmlBody .="<td onclick=\"xajax_pagar('$idAmortizacion','$idNotaDeCargo','cuota');\" class=\"tdSelecBotonSucces puntero\"><img src=\"../img/iconos/money_add.png\" title=\"Pagar Cuota\" class=\"puntero\" id=\"imgPedido\" align=\"center\"></td>";
			$swCuota = false;
		}else if($frmAmortizaciones[$i]['estado_cuota'] == 0){ // 0 = pendiente
			$htmlBody .="<td class=\"divResaltarGris puntero\"><img src=\"../img/iconos/money_add.png\" title=\"Cuota Pendiente por pagar\" class=\"puntero\" id=\"imgPedido\" align=\"center\"></td>";				
		}
		if($frmAmortizaciones[$i]['estado_cuota'] == 1 ){ // 1 = Pagado
			$htmlBody .="<td><img src=\"../img/minselect.png\" title=\"Pagado\" class=\"puntero\" id=\"imgPedido\" align=\"center\"></td>";
		}
		if($frmAmortizaciones[$i]['estado_cuota'] == 2 ){ // 1 = Atrasado
			$swAtraso = true;
			$query = sprintf("SELECT
							amort_mora.id_notadecargo_cxc
				FROM  fi_amortizacion amort
				INNER JOIN fi_amortizacion_interesmora amort_mora ON (amort.id_amortizacion = amort_mora.id_amortizacion)
				WHERE amort.id_amortizacion = %s",
					valTpDato($idAmortizacion, "int"));
			
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$totalRows = mysql_num_rows($rs);
			$rowAmortizacion = mysql_fetch_assoc($rs);
			
			$idNotaDeCargo = $rowAmortizacion['id_notadecargo_cxc'];
			
			$htmlBody .="<td onclick=\"xajax_pagar('$idAmortizacion','$idNotaDeCargo','mora');\" class=\"divResaltarNaranja puntero\"><img src=\"../img/iconos/money_add.png\" title=\"Pagar Interes de Mora\" class=\"puntero\" id=\"imgPedido\" align=\"center\"></td>";
		}
		$htmlBody .="</tr>";
	}

	$htmlFin .="</table>";
	$htmlFin .="</form>";
	$htmlFin .="</td>";

	$objResponse->assign("cuadroAmortizaciones", "innerHTML", $htmlTh.$htmlBody.$htmlFin);
	$objResponse->loadCommands(dibujarCuadroAmortizacion($dibujar));


	return $objResponse;
}

function pagar($idCuota,$idNotaCargo,$tipoPago) {
	
	$objResponse = new xajaxResponse();
	
	$idPedido = $_GET['id'];

	switch($tipoPago){
		
		case 'cuota':
		
			$updateSQL = sprintf("UPDATE fi_pedido SET
						estatus_pedido = 1
			WHERE id_pedido_financiamiento = %s;", // estaus_pedido = 1 = parcialmente pagado
						valTpDato($idPedido, "int"));
				
			$rsUpdateSQL = mysql_query($updateSQL);
			if (!$rsUpdateSQL) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				
			$redireccion = sprintf("window.open('../caja_vh/cj_nota_cargo_por_pagar_form.php?id=%s&idCuota=%s')",
						valTpDato($idNotaCargo, "int"),
						valTpDato($idCuota, "int"));
		break;
		
		case 'mora':
			$redireccion = sprintf("window.open('../caja_vh/cj_nota_cargo_por_pagar_form.php?id=%s&idCuotaMora=%s&idPedidoFinanciamiento=%s')",
						valTpDato($idNotaCargo, "int"),
						valTpDato($idCuota, "int"),
						valTpDato($idPedido, "int"));
		break;
	}
	
	$objResponse->script($redireccion);
	
	$idPedido = $_GET['id'];
	$objResponse->loadCommands(cargarCampos($idPedido));
	return $objResponse;
	
}

function validarFrmMotivo($idMotivo,$frmMotivos,$cuotaMotivo) {
	$objResponse = new xajaxResponse();

	$saldoActual = str_replace(",", "", $frmMotivos['txtTotalSaldo'.$cuotaMotivo]);

	if($saldoActual < 0){
		$objResponse->script("byId('$idMotivo').value = '0.00';");
		$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmMotivos'),'%s','');",$cuotaMotivo));
		$objResponse->alert("El subtotal es mayor al saldo del interes de mora de la cuota $cuotaMotivo");
	}

	return $objResponse;
}

function validarInteresMora($idPedido) {

	$objResponse = new xajaxResponse();
	
		
	if($idPedido == null){
		return $objResponse;
	}
	
	//VERIFIFCANDO SI TIENE INTERES POR MORA

	$queryVerfificarInteresMora = sprintf("SELECT 
											pedido.id_interes_mora
										  FROM fi_pedido AS pedido
										  WHERE pedido.id_pedido_financiamiento = %s
										  AND pedido.id_interes_mora IS NOT NULL",
						valTpDato($idPedido, "int"));
	
	$rsVerifiMora = mysql_query($queryVerfificarInteresMora);
	if (!$rsVerifiMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__."\n\nQUERY:\n".$queryVerfificarInteresMora);
	$checkInteresMora = mysql_num_rows($rsVerifiMora); //SI TIENE INTERES POR MORA SE EJECUTA
	
	if($checkInteresMora == 1){
		
		$queryAmortizacion = sprintf("SELECT 
									*
							FROM fi_amortizacion amort
							INNER JOIN fi_pedido ped ON (amort.id_pedido_financiamiento = ped.id_pedido_financiamiento)
							WHERE amort.id_pedido_financiamiento = %s", 
						valTpDato($idPedido, "int"));
		
		$rsAmort = mysql_query($queryAmortizacion);
		if (!$rsAmort) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$totalRows = mysql_num_rows($rsAmort);
		
		$cantidadIntMoras = 0;
		
		while($row = mysql_fetch_assoc($rsAmort)){
			
			//VERIFICANDO SI HA SIDO PAGADO UN INTERES PREVIAMENTE EN LA CUOTA
			
			$queryInteresMora = sprintf("SELECT
									estado_pago
							FROM fi_amortizacion_interesmora 
							WHERE id_pedido_financiamiento = %s
							AND id_amortizacion = %s",
					valTpDato($idPedido, "int"),
					valTpDato($row['id_amortizacion'], "int"));
			
			$rsInteresMora = mysql_query($queryInteresMora);
			if (!$rsInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$rowIntMora = mysql_fetch_assoc($rsInteresMora);
			
			$idInteresMora = $row['id_interes_mora'];
			if($row['estado_cuota'] != 1 && ($rowIntMora['estado_pago'] == null)){ //SI NO ESTA PAGADA /  / NO HA TENIDO INTERES POR MORA
				if((date("Y-m-d") > $row['fecha_cuota']) && $row['estado_cuota'] != 2 ){ //SI PASA LA FECHA / NO ESTA ATRASADA / NO ESTA APROBADA
				$htmlItmPie .= sprintf("$('#trFooter').before('<input type=\"hidden\" id=\"hddCuotasMora[]\" name=\"hddCuotasMora[]\" value=\"%s\" />".
						"<input type=\"hidden\" id=\"hddIdCuotasMora[]\" name=\"hddIdCuotasMora[]\" value=\"%s\" />');",  
				$row['periodo_cuota'],
				$row['id_amortizacion']);
				
				$cantidadIntMoras++;
				}
			}
		}
	}
	
	if( $cantidadIntMoras > 0){ 
		
		//CAMBIA EL ESTADO DEL PEDIDO A ATRASADO
		
		$updateCuotaSQL = sprintf("UPDATE fi_pedido SET
					estatus_pedido = %s
							WHERE id_pedido_financiamiento = %s",
				valTpDato("4", "int"), //ASIGNANDO PEDIDO A UN ESTADO 4 =  ATRASADO
				valTpDato($idPedido, "int"));
		$Result1 = mysql_query($updateCuotaSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".__FILE__); }
		
		//ABRO EL MODAL DONDE SE ASIGNARAN LOS MOTIVOS DE LOS INTERESES POR MORA 
		
		$objResponse->script($htmlItmPie);
		$objResponse->script(" byId('hddIdPedido').value = '".$idPedido."';	
							   byId('hddVerificarMotivoInteresMora').value = 1;
							   byId('aAgregarMotivo').click();
							   byId('aAgregarMotivo').click();");
		$objResponse->assign("hddIdInteresMora", "value", $idInteresMora);
	}		
	return $objResponse;
}

function validarSaldosInteresMora($frmMotivos,$frmPagoPedido) {
	
	$objResponse = new xajaxResponse();	
	
	for($i=0 ; $i < count($frmPagoPedido['hddCuotasMora']) ; $i++){
		$cuotaMora = $frmPagoPedido['hddCuotasMora'][$i];
		if($frmMotivos['txtTotalSaldo'.$cuotaMora ] != 0.00){
			return $objResponse->script("alert('Todavia queda Saldo Total Pendiente en la Cuota $cuotaMora');");
		}
	}
	
	return $objResponse->script("xajax_guardarInteresMora(xajax.getFormValues('frmMotivos'),xajax.getFormValues('frmPagoPedido'));");
	
}

$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargarCampos");
$xajax->register(XAJAX_FUNCTION,"cargarFrmInteresMora");
$xajax->register(XAJAX_FUNCTION,"comprobarPagos");
$xajax->register(XAJAX_FUNCTION,"dibujarCuadroAmortizacion");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivo");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivoLote");
$xajax->register(XAJAX_FUNCTION,"guardarInteresMora");
$xajax->register(XAJAX_FUNCTION,"insertarItemMotivo");
$xajax->register(XAJAX_FUNCTION,"insertarMotivo");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"mostrarAmortizaciones");
$xajax->register(XAJAX_FUNCTION,"pagar");
$xajax->register(XAJAX_FUNCTION,"validarFrmMotivo");
$xajax->register(XAJAX_FUNCTION,"validarInteresMora");
$xajax->register(XAJAX_FUNCTION,"validarSaldosInteresMora");

function calcularWidth($valorCelda,$valorFinal){

	$porcentajeWidth = $valorCelda*100/$valorFinal;

	return $porcentajeWidth;
}

?>