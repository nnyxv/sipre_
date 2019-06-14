<?php

function reconversion($idNotaCargo){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	$idNotaCargo2 = $idNotaCargo;

	//Verifico si la nota de carggo ya tiene reconversion
	$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_notacargo = $idNotaCargo2";
	$rsValidacion = mysql_query($queryValidacion);
	$numReg = mysql_num_rows($rsValidacion);

	$queryValidacion2 = "SELECT * FROM cj_cc_notadecargo WHERE id_notacargo = $idNotaCargo2 ";
	$rsValidacion2 = mysql_query($queryValidacion2);
	$numReg2 = mysql_fetch_array($rsValidacion2);
	
	$fechaRegistro = $numReg2['fecha_origen_notacargo'];
	//if ($rsValidacion2) return $objResponse->alert($fechaRegistro);
	$dateTime_fechaReconversion = '2018-08-20';

	
	if($fechaRegistro < $dateTime_fechaReconversion){
		if($numReg == 0){
	
			//TABLA1
			$queryNotaCargo1 = "UPDATE cp_notacargo_detalle_motivo 
								SET precio_unitario = precio_unitario/100000
								WHERE id_notacargo = $idNotaCargo2 ";
			$rsNota1 = mysql_query($queryNotaCargo1);
			if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo1);
	
			//TABLA2
			$queryNotaCargo2 = "UPDATE cp_notadecargo 
								SET monto_exento_notacargo = monto_exento_notacargo/100000,
								monto_exonerado_notacargo = monto_exonerado_notacargo/100000,
								subtotal_notacargo = subtotal_notacargo/100000,
								subtotal_descuento_notacargo = subtotal_descuento_notacargo/100000,
								total_cuenta_pagar = total_cuenta_pagar/100000,
								saldo_notacargo = saldo_notacargo/100000
								WHERE id_notacargo = $idNotaCargo2 ";
			$rsNota2 = mysql_query($queryNotaCargo2);
			if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo2);
	
			//TABLA3
			$queryNotaCargo3 = "UPDATE cp_pagos_documentos 
								SET monto_cancelado = monto_cancelado/100000
								WHERE id_documento_pago = $idNotaCargo2 ";
			$rsNota3 = mysql_query($queryNotaCargo3);
			if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo3);
	
			
	
			//TABLA6
				$queryReconversion = "INSERT INTO cp_reconversion (id_notacargo,id_usuarios) VALUES ($idNotaCargo2,$id_usuario)";
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
		
	
			$mensaje = "Items Actualizados";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;
				
		}else{
			return $objResponse->alert("Los items de esta factura ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");
			
		}
	}else{
		return $objResponse->alert("No está permitido reconvertir una nota de cargo con fecha igual o posterior al 20 de Agosto de 2018");
	}
}

function asignarAnticipo($idAnticipo, $nombreObjeto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT 
		cxp_ant.id_anticipo,
		cxp_ant.fechaanticipo,
		cxp_ant.numeroAnticipo,
		prov.nombre,
		cxp_ant.total,
		cxp_ant.saldoanticipo,
		cxp_ant.estado
	FROM cp_anticipo cxp_ant
		INNER JOIN cp_proveedor prov ON (cxp_ant.id_proveedor = prov.id_proveedor)
	WHERE cxp_ant.id_anticipo = %s",
		valTpDato($idAnticipo,"int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$idAnticipo);
	$objResponse->assign("txtNumero".$nombreObjeto,"value",$rowDcto['numeroAnticipo']);
	$objResponse->assign("txtFecha".$nombreObjeto,"value",date(spanDateFormat, strtotime($rowDcto['fechaanticipo'])));
	$objResponse->assign("txtEstado".$nombreObjeto,"value",$rowDcto['estado']);
	$objResponse->assign("txtTotal".$nombreObjeto,"value",number_format($rowDcto['total'], 2, ".", ","));
	$objResponse->assign("txtSaldo".$nombreObjeto,"value",number_format($rowDcto['saldoanticipo'], 2, ".", ","));
	
	$objResponse->script("
	byId('btnCancelarLista".$nombreObjeto."').click();");
	
	return $objResponse;
}

function asignarDepartamento($frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('txtNumeroNotaCargo').className = 'inputInicial';");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFechaProveedor","innerHTML","<input type=\"text\" id=\"txtFechaProveedor\" name=\"txtFechaProveedor\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
			
			$objResponse->script("
			byId('txtNumeroControl').readOnly = false;
			byId('txtNumeroControl').className = 'inputHabilitado';
			byId('txtFechaProveedor').readOnly = true;
			byId('txtFechaProveedor').className = 'inputInicial';");
		} else {
			$objResponse->script("
			byId('txtNumeroControl').readOnly = true;
			byId('txtNumeroControl').className = 'inputInicial';
			byId('txtFechaProveedor').readOnly = false;
			byId('txtFechaProveedor').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFechaProveedor').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFechaProveedor\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"torqoise\"
			});");
			
			$objResponse->assign("txtNumeroNotaCargo","value","");
			$objResponse->assign("txtNumeroControl","value","");
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = false;
		byId('txtNumeroNotaCargo').className = 'inputHabilitado';
		byId('txtNumeroControl').readOnly = false;
		byId('txtNumeroControl').className = 'inputHabilitado';");
		
		$objResponse->assign("txtNumeroControl","value","");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFechaProveedor","innerHTML","<input type=\"text\" id=\"txtFechaProveedor\" name=\"txtFechaProveedor\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
			
			$objResponse->script("
			byId('txtFechaProveedor').readOnly = true;
			byId('txtFechaProveedor').className = 'inputInicial';");
		} else {
			$objResponse->script("
			byId('txtFechaProveedor').readOnly = false;
			byId('txtFechaProveedor').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFechaProveedor').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFechaProveedor\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"torqoise\"
			});");
			
			$objResponse->assign("txtFechaProveedor","value","");
		}
	}
	
	return $objResponse;
}

function asignarEmpleado($idEmpleado, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT vw_pg_empleado.* FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarFechaRegistro($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Nota de Débito de CxP)
	$queryConfig404 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 404 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig404 = mysql_query($queryConfig404);
	if (!$rsConfig404) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig404 = mysql_num_rows($rsConfig404);
	$rowConfig404 = mysql_fetch_assoc($rsConfig404);
	
	$valor = explode("|",$rowConfig404['valor']);
	
	$txtFechaProveedor = explode("-",date("Y-m-d",strtotime($frmDcto['txtFechaProveedor'])));
	if ($txtFechaProveedor[1] > 0 && $txtFechaProveedor[2] > 0 && $txtFechaProveedor[0] > 0) {
		if (checkdate($txtFechaProveedor[1], $txtFechaProveedor[2], $txtFechaProveedor[0])) { // EVALUA QUE LA FECHA EXISTA
			$txtFechaRegistroCompra = date(spanDateFormat);
			$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
			if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
				if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
					if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
					|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
						$txtFechaRegistroCompra = $txtFechaProveedor;
					} else {
						$objResponse->script("byId('cbxFechaRegistro').checked = false;");
						$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
					}
				} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
				} else {
					$txtFechaRegistroCompra = $txtFechaProveedor;
				}
			} else if ($frmDcto['cbxFechaRegistro'] == 1) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
			}
			
			$objResponse->assign("txtFechaRegistroCompra","value",$txtFechaRegistroCompra);
		} else {
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
	}
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $nombreObjeto, $asigDescuento = "true", $cerrarVentana = "true") {
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
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowProv['nombre_proveedor']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value",$rowProvCredito['diascredito']);
		
		$objResponse->call("selectedOption","lstTipoPago",1);
	} else {
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value","0");
		
		$objResponse->call("selectedOption","lstTipoPago",0);
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
	return $objResponse;
}

function asignarMetodoPago($idMetodoPago){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('fieldsetTransferencia').style.display = 'none';
	byId('fieldsetCheque').style.display = 'none';
	byId('fieldsetAnticipo').style.display = 'none';
	byId('fieldsetNotaCredito').style.display = 'none';");
	
	switch($idMetodoPago) {
		case 1 : $objResponse->script("byId('fieldsetTransferencia').style.display = '';"); break;
		case 2 : $objResponse->script("byId('fieldsetCheque').style.display = '';"); break;
		case 3 : $objResponse->script("byId('fieldsetAnticipo').style.display = '';"); break;
		case 4 : $objResponse->script("byId('fieldsetNotaCredito').style.display = '';"); break;
	}
	
	return $objResponse;
}

function asignarNotaCredito($idNotaCredito, $nombreObjeto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT 
		cxp_nc.id_notacredito,
		cxp_nc.fecha_notacredito,
		cxp_nc.numero_nota_credito,
		prov.nombre,
		
		(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
					FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
					FROM cp_notacredito_iva cxp_nc_iva
					WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
		) AS total,
		
		cxp_nc.saldo_notacredito,
		cxp_nc.estado_notacredito
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
	WHERE cxp_nc.id_notacredito = %s",
		valTpDato($idNotaCredito,"int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$idNotaCredito);
	$objResponse->assign("txtNumero".$nombreObjeto,"value",utf8_encode($rowDcto['numero_nota_credito']));
	$objResponse->assign("txtFecha".$nombreObjeto,"value",date(spanDateFormat, strtotime($rowDcto['fecha_notacredito'])));
	$objResponse->assign("txtEstado".$nombreObjeto,"value",$rowDcto['estado_notacredito']);
	$objResponse->assign("txtTotal".$nombreObjeto,"value",number_format($rowDcto['total'], 2, ".", ","));
	$objResponse->assign("txtSaldo".$nombreObjeto,"value",number_format($rowDcto['saldo_notacredito'], 2, ".", ","));
	
	$objResponse->script("
	byId('btnCancelarLista".$nombreObjeto."').click();");
	
	return $objResponse;
}

function buscarAnticipo($frmBuscarAnticipo, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarAnticipo['hddObjDestinoAnticipo'],
		$frmDcto['txtIdProv'],
		$frmBuscarAnticipo['txtCriterioBuscarAnticipo']);
	
	$objResponse->loadCommands(listaAnticipo(0, "numeroAnticipo", "ASC", $valBusq));
		
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

function buscarNotaCredito($frmBuscarNotaCredito, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarNotaCredito['hddObjDestinoNotaCredito'],
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdProv'],
		$frmBuscarNotaCredito['txtCriterioBuscarNotaCredito']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "numero_nota_credito", "ASC", $valBusq));
		
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

function calcularDcto($frmDcto, $frmListaMotivo, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmMotivo:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmMotivo:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjItmMotivo","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice2 => $valor2) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valor2,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor2,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjPago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	$objResponse->assign("hddObjIva","value",((count($arrayObjIva) > 0) ? implode("|",$arrayObjIva) : ""));
	
	// SUMA LOS PAGOS
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice2 => $valor2) {
			$txtTotalPago += ($frmListaPagoDcto['hddEstatusPago'.$valor2] == 1) ? str_replace(",", "", $frmListaPagoDcto['txtMontoPago'.$valor2]) : 0;
		}
	}
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$valor]);
			
			$txtSubTotal += $txtTotalItm;
		}
	} else {
		$txtSubTotal = round(str_replace(",", "", $frmTotalDcto['txtSubTotal']),2);
	}
	
	$txtDescuento = round(str_replace(",", "", $frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
	$txtTotalExento = round(str_replace(",", "", $frmTotalDcto['txtTotalExento']),2);
	$txtTotalExonerado = round(str_replace(",", "", $frmTotalDcto['txtTotalExonerado']),2);
	
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
	
	if (isset($frmTotalDcto['cbxIva'])) {
		foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
			// BUSCA EL IVA DE COMPRA POR DEFECTO PARA CALCULAR EL EXENTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 AND iva.idIva = %s ORDER BY iva",
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBaseImpIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
			$txtIva = str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
			$txtSubTotalIva = $txtBaseImpIva * $txtIva / 100;
			
			$objResponse->assign("txtSubTotalIva".$valor,"value",number_format($txtSubTotalIva, 2, ".", ","));
			
			$totalSubtotalIva += round($txtSubTotalIva, 2);
			
			// BUSCA LA BASE IMPONIBLE MAYOR
			if ($totalRows > 0 && $txtBaseImpIva > 0) {
				$txtBaseImpIvaCompra = $txtBaseImpIva;
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
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalNotaCargo = $txtSubTotal - $txtSubTotalDescuento + $totalSubtotalIva;
	$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaCompra;
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalNotaCargo", "value", number_format($txtTotalNotaCargo, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstBanco($selId = "", $nombreObjeto = "", $onChange = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM bancos ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuenta($idBanco, $selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM cuentas WHERE idBanco = %s ORDER BY numeroCuentaCompania ASC",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idCuentas']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".utf8_encode($row['numeroCuentaCompania'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function eliminarMetodoPago($frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaPagoDcto['cbxItm'])) {
		foreach ($frmListaPagoDcto['cbxItm'] as $indice2 => $valor2) {
			$objResponse->script("
			fila = document.getElementById('trItmPago:".$valor2."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function eliminarMotivo($trItmMotivo, $frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($trItmMotivo) && $trItmMotivo > 0) {
		$objResponse->script("
		fila = document.getElementById('trItmMotivo:".$trItmMotivo."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
		
		$objResponse->script("xajax_eliminarMotivo('', xajax.getFormValues('frmListaMotivo'));");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function eliminarMotivoLote($frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaMotivo['cbxItm'])) {
		foreach ($frmListaMotivo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmMotivo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formNotaCargo($idNotaCargo, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	$contFila2 = $arrayObj2[count($arrayObj2)-1];
	
	if ($idNotaCargo > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarProv').style.display = 'none';
		byId('txtIdProv').readOnly = true;
		byId('txtIdProv').className = 'inputInicial';
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('txtNumeroNotaCargo').className = 'inputInicial';
		byId('lblNroAutomatico').style.display = 'none';
		byId('txtNumeroControl').readOnly = true;
		byId('txtNumeroControl').className = 'inputInicial';
		byId('txtFechaProveedor').readOnly = true;
		byId('txtFechaProveedor').className = 'inputInicial';
		byId('lblFechaRegistro').style.display = 'none';
		byId('lstTipoPago').className = 'inputInicial';
		byId('lstAplicaLibro').className = 'inputInicial';
		byId('txtObservacion').readOnly = true;
		byId('txtObservacion').className = 'inputInicial';
		
		byId('btnNotaCargoPDF').style.display = 'none';
		
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
		
		byId('trBtnListaPagoDcto').style.display = 'none';
		byId('trListaPagoDcto').style.display = '';
		
		byId('aAgregarMotivo').style.display = 'none';
		byId('btnQuitarMotivo').style.display = 'none';
		byId('btnGuardar').style.display = 'none';
		
		byId('fieldsetPlanMayor').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA NOTA DE CARGO
		$queryNotaCargo = sprintf("SELECT cxp_nd.*,
			(CASE cxp_nd.estatus_notacargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_nota_cargo
		FROM cp_notadecargo cxp_nd
		WHERE id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
		// BUSCA LOS DATOS DE LA FACTURA POR PLAN MAYOR
		$queryFactura = sprintf("SELECT
			cxp_fact.id_factura,
			cxp_fact.numero_factura_proveedor,
			cxp_fact.numero_control_factura,
			cxp_fact.fecha_origen,
			cxp_fact.fecha_factura_proveedor,
			cxp_fact.estatus_factura,
			(CASE cxp_fact.estatus_factura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_factura,
			(CASE cxp_fact.tipo_pago
				WHEN 0 THEN 'Contado'
				WHEN 1 THEN 'Crédito'
			END) AS tipo_pago_factura,
			cxp_fact.saldo_factura,
			modulo.descripcionModulo,
			(CASE cxp_fact.aplica_libros
				WHEN 0 THEN 'NO'
				WHEN 1 THEN 'SI'
			END) AS aplica_libros_factura
		FROM cp_notadecargo cxp_nd
			INNER JOIN an_unidad_fisica uni_fis ON (cxp_nd.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
			INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			INNER JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			INNER JOIN pg_modulos modulo ON (cxp_fact.id_modulo = modulo.id_modulo)
		WHERE cxp_nd.id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFactura = mysql_num_rows($rsFactura);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		if ($totalRowsFactura > 0) {
			$objResponse->script("byId('fieldsetPlanMayor').style.display = '';");
		}
		
		if ($rowNotaCargo['saldo_notacargo'] > 0 && $_GET['vw'] != "v" && $_GET['vw'] != "e") {
			$objResponse->script("
			byId('trBtnListaPagoDcto').style.display = '';
			
			byId('btnGuardar').style.display = '';");
		}
		
		switch($rowNotaCargo['estatus_notacargo']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCargo['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarProveedor($rowNotaCargo['id_proveedor'], "Prov", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowNotaCargo['id_empleado_creador']));
		
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat, strtotime($rowNotaCargo['fecha_origen_notacargo'])));
		$objResponse->assign("txtIdNotaCargo","value",$rowNotaCargo['id_notacargo']);
		$objResponse->assign("txtNumeroNotaCargo","value",utf8_encode($rowNotaCargo['numero_notacargo']));
		$objResponse->assign("txtNumeroControl","value",utf8_encode($rowNotaCargo['numero_control_notacargo']));
		$objResponse->assign("txtFechaProveedor","value",date(spanDateFormat, strtotime($rowNotaCargo['fecha_notacargo'])));
		$objResponse->call("selectedOption","lstTipoPago",$rowNotaCargo['tipo_pago_notacargo']);
		$objResponse->loadCommands(cargaLstModulo($rowNotaCargo['id_modulo'], true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCargo['aplica_libros_notacargo']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatus","value",$rowNotaCargo['estado_nota_cargo']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowNotaCargo['observacion_notacargo']));
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'".$rowNotaCargo['aplica_libros_notacargo']."');
		}");
		
		$objResponse->script("
		byId('lstTipoPago').onchange = function() {
			selectedOption(this.id,'".$rowNotaCargo['tipo_pago_notacargo']."');
		}");
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("ND",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = "ND";
		$objDcto->tipoDocumentoMovimiento = (in_array("ND",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $rowNotaCargo['id_modulo'];
		$objDcto->idDocumento = $rowNotaCargo['id_notacargo'];
		$objDcto->mostrarDocumento = "verVentanaPDF";
		$aVerDcto = $objDcto->verDocumento();
		
		$objResponse->script("
		byId('btnNotaCargoPDF').style.display = '';
		byId('btnNotaCargoPDF').onclick = function() { ".$aVerDcto." }");
		
		// CARGA EL DETALLE DE LA NOTA DE DEBITO
		$queryNotaDebitoDet = sprintf("SELECT * FROM cp_notacargo_detalle_motivo WHERE id_notacargo = %s
		ORDER BY id_notacargo_detalle_motivo ASC;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaDebitoDet = mysql_query($queryNotaDebitoDet);
		if (!$rsNotaDebitoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowNotaDebitoDet = mysql_fetch_assoc($rsNotaDebitoDet)) {
			$Result1 = insertarItemMotivo($contFila, $rowNotaDebitoDet['id_notacargo_detalle_motivo'], $rowNotaDebitoDet['id_motivo'], $rowNotaDebitoDet['precio_unitario']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		// ASIGNA LOS DATOS DE LA FACTURA
		switch($rowFactura['estatus_factura']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
		}
		
		$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura']);
		$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
		$objResponse->assign("txtNumeroControlFactura","value",$rowFactura['numero_control_factura']);
		$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat, strtotime($rowFactura['fecha_origen'])));
		$objResponse->assign("txtFechaFactura","value",date(spanDateFormat, strtotime($rowFactura['fecha_factura_proveedor'])));
		$objResponse->assign("txtTipoPago","value",$rowFactura['tipo_pago_factura']);
		$objResponse->assign("txtModulo","value",$rowFactura['descripcionModulo']);
		$objResponse->assign("txtAplicaLibro","value",$rowFactura['aplica_libros_factura']);
		/*$objResponse->assign("txtIdMotivo","value",$rowFactura['id_motivo']);
		$objResponse->assign("txtMotivo","value",$rowFactura['descripcion']);*/
		$objResponse->script(sprintf("byId('tdtxtEstatusFactura').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatusFactura","value",$rowFactura['estado_factura']);
		
		$objResponse->script(sprintf("byId('aVerFactura').href = 'cp_factura_form.php?id=%s&vw=v';", $rowFactura['id_factura']));
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT
			cxp_nd_iva.id_notacarg_iva,
			cxp_nd_iva.id_notacargo,
			cxp_nd_iva.baseimponible,
			cxp_nd_iva.subtotal_iva,
			cxp_nd_iva.id_iva,
			cxp_nd_iva.iva,
			iva.observacion
		FROM cp_notacargo_iva cxp_nd_iva
			INNER JOIN pg_iva iva ON (cxp_nd_iva.id_iva = iva.idIva)
		WHERE cxp_nd_iva.id_notacargo = %s
		ORDER BY iva",
			valTpDato($idNotaCargo, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contFila++;
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
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
				$contFila,
					$contFila, utf8_encode($rowIva['observacion']),
						$contFila, $contFila, $rowIva['id_iva'],
						$contFila,
					$contFila, $contFila, number_format(round($rowIva['baseimponible'],2), 2, ".", ","),
					$contFila, $contFila, $rowIva['iva'], "%",
					$contFila, $contFila, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$contFila,
				
				$contFila,
				
				$contFila));
		}
		
		$porcDescuento = $rowNotaCargo['subtotal_descuento_notacargo'] * 100 / $rowNotaCargo['subtotal_notacargo'];
		$objResponse->assign("txtSubTotal","value",number_format($rowNotaCargo['subtotal_notacargo'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCargo['subtotal_descuento_notacargo'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowNotaCargo['monto_exento_notacargo'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowNotaCargo['monto_exonerado_notacargo'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCargo['saldo_notacargo'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$query = sprintf("SELECT * FROM cp_pagos_documentos pago_dcto
		WHERE tipo_documento_pago LIKE 'ND'
			AND id_documento_pago = %s;",
			valTpDato($idNotaCargo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemMetodoPago($contFila2, $row['id_pago']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila2 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila2;
			}
		}
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdProv').className = 'inputHabilitado';
		byId('txtNumeroNotaCargo').className = 'inputHabilitado';
		byId('txtNumeroControl').className = 'inputHabilitado';
		byId('txtFechaProveedor').className = 'inputHabilitado';
		byId('lstTipoPago').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('btnNotaCargoPDF').style.display = 'none';
		
		byId('rbtInicialMonto').checked = true;
		
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('trBtnListaPagoDcto').style.display = 'none';
		byId('trListaPagoDcto').style.display = 'none';
		
		byId('fieldsetPlanMayor').style.display = 'none';");
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contFila++;
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
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
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$contFila,
					$contFila, utf8_encode($rowIva['observacion']),
						$contFila, $contFila, $rowIva['idIva'],
						$contFila, $contFila, $rowIva['lujo'],
						$contFila,
					$contFila, $contFila, number_format(round(0,2), 2, ".", ","),
					$contFila, $contFila, $rowIva['iva'], "%",
					$contFila, $contFila, number_format(round(0,2), 2, ".", ","),
				
				$contFila,
				
				$contFila,
				
				$contFila));
		}
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->assign("txtSubTotal","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format(0, 2, ".", ","));
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
		
		$objResponse->script("
		jQuery(function($){
			$(\"#txtFechaProveedor\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFechaProveedor\",
			dateFormat:\"".spanDatePick."\",
			cellColorScheme:\"torqoise\"
		});");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaMotivo, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idNotaCargo = $frmDcto['txtIdNotaCargo'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCargo > 0) {
		if (!xvalidaAcceso($objResponse,"cp_nota_cargo_captura_list","editar")) { return $objResponse; }
		
		if (isset($arrayObj2)) {
			foreach ($arrayObj2 as $indice2 => $valor2) {
				if ($frmListaPagoDcto['hddIdPago'.$valor2] == 0) {
					$insertSQL = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
					VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato('ND', "text"),
						valTpDato($frmListaPagoDcto['txtMetodoPago'.$valor2], "text"),
						valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "text"),
						valTpDato(date("Y-m-d", strtotime($frmListaPagoDcto['txtFechaPago'.$valor2])), "text"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmListaPagoDcto['txtNumeroDctoPago'.$valor2], "text"),
						valTpDato($frmListaPagoDcto['txtBancoProveedorPago'.$valor2], "text"),
						valTpDato($frmListaPagoDcto['txtBancoCompaniaPago'.$valor2], "text"),
						valTpDato($frmListaPagoDcto['txtCuentaProveedorPago'.$valor2], "text"),
						valTpDato($frmListaPagoDcto['txtCuentaCompaniaPago'.$valor2], "text"),
						valTpDato($frmListaPagoDcto['txtMontoPago'.$valor2], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					switch ($frmListaPagoDcto['txtMetodoPago'.$valor2]) {
						case "Transferencia" :
							break;
						case "Cheque" :
							break;
						case "AN" :
							// VERIFICA SI EL SALDO DEL ANTICIPO ES MAYOR AL MONTO
							$query = sprintf("SELECT * FROM cp_anticipo
							WHERE id_anticipo = %s
								AND saldoanticipo >= %s;",
								valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"),
								valTpDato($frmListaPagoDcto['txtMontoPago'.$valor2], "real_inglesa"));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							if ($totalRows > 0) {
								// ACTUALIZA EL SALDO DEL ANTICIPO
								$updateSQL = sprintf("UPDATE cp_anticipo SET
									saldoanticipo = saldoanticipo - %s
								WHERE id_anticipo = %s;",
									valTpDato($frmListaPagoDcto['txtMontoPago'.$valor2], "real_inglesa"),
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// ACTUALIZA EL ESTATUS DEL ANTICIPO
								$updateSQL = sprintf("UPDATE cp_anticipo SET
									estado = (CASE
													WHEN saldoanticipo = 0 THEN	3
													WHEN saldoanticipo > 0 THEN	2
												END)
								WHERE id_anticipo = %s;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
							} else {
								return $objResponse->alert("No posee saldo suficiente en el Anticipo, elimine dicho pago y agréguelo nuevamente");
							}
							break;
						case "NC" :
							// VERIFICA SI EL SALDO DE LA NOTA DE CREDITO ES MAYOR AL MONTO
							$query = sprintf("SELECT * FROM cp_notacredito
							WHERE id_notacredito = %s
								AND saldo_notacredito >= %s;",
								valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"),
								valTpDato($frmListaPagoDcto['txtMontoPago'.$valor2], "real_inglesa"));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							if ($totalRows > 0) {
								// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
								$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
									saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
														- IFNULL(cxp_nc.subtotal_descuento, 0)
														+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
																FROM cp_notacredito_gastos cxp_nc_gasto
																WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
														+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
																FROM cp_notacredito_iva cxp_nc_iva
																WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
														) - IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
															WHERE ((tipo_pago LIKE 'NC' AND id_documento = cxp_nc.id_notacredito)
																	OR (tipo_documento_pago LIKE 'NC' AND id_documento_pago = cxp_nc.id_notacredito))
																AND pago_dcto.estatus = 1), 0)
								WHERE id_notacredito = %s
									AND estado_notacredito NOT IN (3);",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Sin Asignar, 2 = Asignado Parcial, 3 = Asignado)
								$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
									estado_notacredito = (CASE
															WHEN (saldo_notacredito = 0) THEN
																3
															WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(cxp_nc.subtotal_notacredito, 0)
																		- IFNULL(cxp_nc.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
																				FROM cp_notacredito_gastos cxp_nc_gasto
																				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
																		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
																				FROM cp_notacredito_iva cxp_nc_iva
																				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))) THEN
																2
															WHEN (saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
																		- IFNULL(cxp_nc.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
																				FROM cp_notacredito_gastos cxp_nc_gasto
																				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
																		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
																				FROM cp_notacredito_iva cxp_nc_iva
																				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))) THEN
																1
															ELSE
																0
														END)
								WHERE id_notacredito = %s;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// VERIFICA EL SALDO DE LA NOTA DE CREDITO A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxp_nc.*,
									prov.nombre AS nombre_proveedor
								FROM cp_notacredito cxp_nc
									INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
								WHERE cxp_nc.id_notacredito = %s
									AND cxp_nc.saldo_notacredito < 0;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2], "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Crédito Nro. ".$rowSaldoDcto['numero_nota_credito']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
							} else {
								return $objResponse->alert("No posee saldo suficiente en la Nota de Crédito, elimine dicho pago y agréguelo nuevamente");
							}
							break;
					}
					
					// ACTUALIZA EL SALDO DE LA NOTA DE CARGO
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						saldo_notacargo = saldo_notacargo - %s
					WHERE id_notacargo = %s;",
						valTpDato($frmListaPagoDcto['txtMontoPago'.$valor2], "real_inglesa"),
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL ESTATUS DE LA NOTA DE CARGO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						estatus_notacargo = (CASE
												WHEN (saldo_notacargo = 0 OR saldo_notacargo < 0) THEN
													1
												WHEN (saldo_notacargo > 0) THEN
													2
											END)
					WHERE id_notacargo = %s;",
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// VERIFICA EL SALDO DE LA NOTA DE CARGO A VER SI ESTA NEGATIVO
					$querySaldoDcto = sprintf("SELECT cxp_nd.*,
						prov.nombre AS nombre_proveedor
					FROM cp_notadecargo cxp_nd
						INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
					WHERE cxp_nd.id_notacargo = %s
						AND cxp_nd.saldo_notacargo < 0;",
						valTpDato($idNotaCargo, "int"));
					$rsSaldoDcto = mysql_query($querySaldoDcto);
					if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
					$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
					$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
					if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Débito Nro. ".utf8_encode($rowSaldoDcto['numero_notacargo'])." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
				}
			}
		}
		
		mysql_query("COMMIT;");
	} else {
		if (!xvalidaAcceso($objResponse,"cp_nota_cargo_captura_list","insertar")) { return $objResponse; }
		
		// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Nota de Débito de CxP)
		$queryConfig404 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 404 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsConfig404 = mysql_query($queryConfig404);
		if (!$rsConfig404) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig404 = mysql_num_rows($rsConfig404);
		$rowConfig404 = mysql_fetch_assoc($rsConfig404);
		
		$valor = explode("|",$rowConfig404['valor']);
		
		$txtFechaRegistroCompra = date(spanDateFormat);
		$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
		if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
			if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
				&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
			|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
				if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
				|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
					$txtFechaRegistroCompra = $txtFechaProveedor;
				} else {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
				}
			} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
				&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
			|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			} else {
				$txtFechaRegistroCompra = $txtFechaProveedor;
			}
		} else if ($frmDcto['cbxFechaRegistro'] == 1) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
		}
		
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("byId('txtPrecioItm".$valor."').className = 'inputCompleto'");
			
			if (!($frmListaMotivo['txtPrecioItm'.$valor] > 0)) { $arrayCantidadInvalida[] = "txtPrecioItm".$valor; }
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
		
		if ($frmDcto['cbxNroAutomatico'] == 1) {
			// NUMERACION DEL DOCUMENTO
			if (in_array($idModulo,array(0,1,2,3,4)) && $frmDcto['lstAplicaLibro'] == 1){
				$idNumeraciones = 3; // 3 = Nota Cargo CxP
			} else {
				$idNumeraciones = 3; // 3 = Nota Cargo CxP
			}
			
			// NUMERACION DEL DOCUMENTO
			$queryNumeracion = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
											WHERE clave_mov.id_clave_movimiento = %s)
					OR emp_num.id_numeracion = %s)
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($idNumeraciones, "int"),
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
			
			if (in_array($idModulo,array(0,1,2,3,4)) && $frmDcto['lstAplicaLibro'] == 1){
				$numeroActualControl = $frmDcto['txtNumeroControl'];
			} else {
				$numeroActualControl = $numeroActual;
			}
		} else {
			$numeroActual = $frmDcto['txtNumeroNotaCargo'];
			$numeroActualControl = $frmDcto['txtNumeroControl'];
		}
		
		// BUSCA LOS DATOS DEL PROVEEDOR
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
		
		// INSERTAR LOS DATOS DE LA NOTA DE CARGO
		$insertSQL = sprintf("INSERT INTO cp_notadecargo (id_empresa, numero_notacargo, numero_control_notacargo, fecha_notacargo, fecha_vencimiento_notacargo, fecha_origen_notacargo, id_proveedor, id_modulo, estatus_notacargo, observacion_notacargo, tipo_pago_notacargo, monto_exento_notacargo, monto_exonerado_notacargo, subtotal_notacargo, subtotal_descuento_notacargo, total_cuenta_pagar, saldo_notacargo, aplica_libros_notacargo, chasis, id_detalles_pedido_compra, id_empleado_creador)
		VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroActual, "text"),
			valTpDato($numeroActualControl, "text"),
			valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
			valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato($frmDcto['lstModulo'], "int"),
			valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmDcto['lstTipoPago'], "int"), // 0 = Contado, 1 = Credito
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalNotaCargo'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalNotaCargo'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"), // 1 = Si, 0 = No
			valTpDato("", "text"),
			valTpDato("", "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCargo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL DETALLE DEL DOCUMENTO
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if (!($frmListaMotivo['hddIdNotaCargoDet'.$valor] > 0)) {
					$idMotivo = $frmListaMotivo['hddIdMotivoItm'.$valor];
					$precioUnitario = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$valor]);
					
					$insertSQL = sprintf("INSERT INTO cp_notacargo_detalle_motivo (id_notacargo, id_motivo, precio_unitario)
					VALUE (%s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato($idMotivo, "int"),
						valTpDato($precioUnitario, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idNotaDebitoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			// ACTUALIZA EL MOTIVO DE LA NOTA DE DEBITO
			$updateSQL = sprintf("UPDATE cp_notadecargo SET
				id_motivo = %s
			WHERE id_notacargo = %s;",
				valTpDato($idMotivo, "int"),
				valTpDato($idNotaCargo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indiceIva => $valorIva) {
				if (str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]) != 0) {
					$insertSQL = sprintf("INSERT INTO cp_notacargo_iva (id_notacargo, baseimponible, subtotal_iva, id_iva, iva)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valorIva], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valorIva], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valorIva], "real_inglesa"),
						valTpDato($frmTotalDcto['txtIva'.$valorIva], "real_inglesa"));
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
			valTpDato("ND", "text"),
			valTpDato($idNotaCargo, "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");
			
		$objResponse->assign("txtIdNotaCargo","value",$idNotaCargo);
		
		// MODIFICADO ERNESTO
		switch ($frmDcto['lstModulo']) {
			case 0 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
			case 1 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
			case 2 : if (function_exists("generarNotasCargoCpVe")) { generarNotasCargoCpVe($idNotaCargo,"",""); } break;
			case 3 : if (function_exists("generarNotasCargoCpAd")) { generarNotasCargoCpAd($idNotaCargo,"",""); } break;
		}
		// MODIFICADO ERNESTO
	}
	
	
	$objResponse->alert("Nota de Débito Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['rbtRetencion'] == 1) ? 0 : 1;
	
	$objResponse->script("verVentana('reportes/cp_nota_cargo_pdf.php?valBusq=".$idNotaCargo."', 960, 550);");
	
	$objResponse->script(sprintf("window.location.href='cp_nota_cargo_historico_list.php';"));
	
	return $objResponse;
}

function insertarMetodoPago($frmMetodoPago, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	$arrayMetodoPago = array(1 => "Transferencia", 2 => "Cheque", 3 => "AN", 4 => "NC");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	$contFila2 = $arrayObj2[count($arrayObj2)-1];
	
	switch($frmMetodoPago['lstMetodoPago']) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
		case 1 : // Transferencia
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoCompaniaTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoCompaniaPago = $row['nombreBanco'];
			
			// BUSCA LOS DATOS DE LA CUENTA
			$query = sprintf("SELECT * FROM cuentas
			WHERE idCuentas = %s;",
				valTpDato($frmMetodoPago['lstCuentaCompaniaTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtCuentaCompaniaPago = $row['numeroCuentaCompania'];
			
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoProveedorTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoProveedorPago = $row['nombreBanco'];
			
			$Result1 = insertarItemMetodoPago($contFila2, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], "", $frmMetodoPago['txtNumeroTransferencia'], $txtBancoCompaniaPago, $txtCuentaCompaniaPago, $txtBancoProveedorPago, $frmMetodoPago['txtCuentaProveedorTransferencia'], str_replace(",", "", $frmMetodoPago['txtMontoTransferencia']));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila2 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila2;
			}
			break;
		case 2 : // Cheque
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoCompaniaCheque'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoCompaniaPago = $row['nombreBanco'];
			
			// BUSCA LOS DATOS DE LA CUENTA
			$query = sprintf("SELECT * FROM cuentas
			WHERE idCuentas = %s;",
				valTpDato($frmMetodoPago['lstCuentaCompaniaCheque'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtCuentaCompaniaPago = $row['numeroCuentaCompania'];
			
			$Result1 = insertarItemMetodoPago($contFila2, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], "", $frmMetodoPago['txtNumeroCheque'], $txtBancoCompaniaPago, $txtCuentaCompaniaPago, "-", "-", str_replace(",", "", $frmMetodoPago['txtMontoCheque']));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila2 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila2;
			}
			break;
		case 3 : // AN
			// VERIFICA SI EL SALDO DEL ANTICIPO ES MAYOR AL MONTO
			$query = sprintf("SELECT * FROM cp_anticipo
			WHERE id_anticipo = %s
				AND saldoanticipo >= %s;",
				valTpDato($frmMetodoPago['txtIdAnticipo'], "int"),
				valTpDato($frmMetodoPago['txtMontoAnticipo'], "real_inglesa"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$Result1 = insertarItemMetodoPago($contFila2, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], $frmMetodoPago['txtIdAnticipo'], $frmMetodoPago['txtNumeroAnticipo'], "-", "-", "-", "-", str_replace(",", "", $frmMetodoPago['txtMontoAnticipo']));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila2 = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj2[] = $contFila2;
				}
			} else {
				$objResponse->loadCommands(asignarAnticipo($frmMetodoPago['txtIdAnticipo'], "Anticipo"));
				
				return $objResponse->alert("No posee saldo suficiente en el Anticipo");
			}
			break;
		case 4 : // NC
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indice2 => $valor2) {
					if (!($frmListaPagoDcto['hddIdPago'.$valor2] > 0)
					&& $frmListaPagoDcto['txtMetodoPago'.$valor2] == $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']]
					&& $frmListaPagoDcto['txtIdNumeroDctoPago'.$valor2] == $frmMetodoPago['txtIdNotaCredito']) {
						return $objResponse->alert("Este item ya se encuentra incluido");
					}
				}
			}
			
			// VERIFICA SI EL SALDO DE LA NOTA DE CREDITO ES MAYOR AL MONTO
			$query = sprintf("SELECT * FROM cp_notacredito
			WHERE id_notacredito = %s
				AND saldo_notacredito >= %s;",
				valTpDato($frmMetodoPago['txtIdNotaCredito'], "int"),
				valTpDato($frmMetodoPago['txtMontoNotaCredito'], "real_inglesa"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$Result1 = insertarItemMetodoPago($contFila2, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], $frmMetodoPago['txtIdNotaCredito'], $frmMetodoPago['txtNumeroNotaCredito'], "-", "-", "-", "-", str_replace(",", "", $frmMetodoPago['txtMontoNotaCredito']));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila2 = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj2[] = $contFila2;
				}
			} else {
				$objResponse->loadCommands(asignarNotaCredito($frmMetodoPago['txtIdNotaCredito'], "NotaCredito"));
				
				return $objResponse->alert("No posee saldo suficiente en la Nota de Crédito");
			}
			break;
	}
	
	$objResponse->script("
	byId('btnCancelarMetodoPago').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarMotivo($idMotivo, $frmDcto, $frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Venta)
	$queryConfig5 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 5 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig5 = mysql_query($queryConfig5);
	if (!$rsConfig5) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig5 = mysql_fetch_assoc($rsConfig5);
	
	if ($hddNumeroArt == "") {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = $frmListaMotivo['cbx'];
		$contFila = $arrayObj[count($arrayObj)-1];
		
		foreach ($arrayObj as $indice => $valor){
			if ($frmListaMotivo['hddIdMotivoItm'.$valor] == $idMotivo) {
				return $objResponse->alert("El motivo seleccionado ya se encuentra agregado");
			}
		}
		
		if (count($arrayObj) < $rowConfig5['valor']) {
			$Result1 = insertarItemMotivo($contFila, "", $idMotivo, $precioUnitario);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert(("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por documento"));
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_ant.estado NOT IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_ant.numeroAnticipo LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxp_ant.id_anticipo,
		cxp_ant.fechaanticipo,
		cxp_ant.numeroAnticipo,
		prov.nombre,
		cxp_ant.total,
		cxp_ant.saldoanticipo,
		cxp_ant.estado
	FROM cp_anticipo cxp_ant
		INNER JOIN cp_proveedor prov ON (cxp_ant.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "fechaanticipo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "18%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarAnticipo('".$row['id_anticipo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaanticipo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td>".utf8_decode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CP'
	AND ingreso_egreso LIKE 'E'");
	
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
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarMotivo%s\" onclick=\"validarInsertarMotivo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_motivo']);
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

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.estado_notacredito NOT IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = cxp_nc.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = cxp_nc.id_empresa))",
		valTpDato($valCadBusq[1], "int"),
		valTpDato($valCadBusq[1], "int"),
		valTpDato($valCadBusq[1], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq[2], "int"));
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_nc.numero_nota_credito LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxp_nc.id_notacredito,
		cxp_nc.fecha_notacredito,
		cxp_nc.numero_nota_credito,
		prov.nombre AS nombre_proveedor,
		cxp_nc.observacion_notacredito,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
					FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
					FROM cp_notacredito_iva cxp_nc_iva
					WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
		) AS total,
		
		cxp_nc.saldo_notacredito,
		cxp_nc.estado_notacredito,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_proveedor prov
		INNER JOIN cp_notacredito cxp_nc ON (prov.id_proveedor = cxp_nc.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "fecha_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Nota de Crédito Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "numero_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "44%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "saldo_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Saldo Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCredito('".$row['id_notacredito']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_notacredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_nota_credito'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacion_notacredito']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_notacredito'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_notacredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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

$xajax->register(XAJAX_FUNCTION,"asignarAnticipo");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstBanco");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"eliminarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivo");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivoLote");
$xajax->register(XAJAX_FUNCTION,"formNotaCargo");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"insertarMotivo");
$xajax->register(XAJAX_FUNCTION,"listaAnticipo");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"reconversion");

function insertarItemMetodoPago($contFila, $idPago = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoProveedorPago = "", $txtCuentaProveedorPago = "", $txtMontoPago = "") {
	global $raiz;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("SELECT pago_dcto.*,
			
			(CASE pago_dcto.tipo_pago
				WHEN 'NC' THEN
					(SELECT cxp_nc.id_departamento_notacredito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
				WHEN 'AN' THEN
					(SELECT cxp_ant.idDepartamento FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = pago_dcto.id_documento)
				WHEN 'TRANSFERENCIA' THEN
					NULL
				WHEN 'CHEQUE' THEN
					NULL
				ELSE
					NULL
			END) AS id_modulo_documento_pago,
			
			(CASE pago_dcto.tipo_pago
				WHEN 'NC' THEN
					(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
				WHEN 'AN' THEN
					(SELECT cxp_ant.numeroAnticipo FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = pago_dcto.id_documento)
				WHEN 'TRANSFERENCIA' THEN
					IFNULL((SELECT transf.numero_transferencia FROM te_transferencia transf WHERE transf.id_transferencia = pago_dcto.id_documento),
						pago_dcto.numero_documento)
				WHEN 'CHEQUE' THEN
					IFNULL((SELECT cheque.numero_cheque FROM te_cheques cheque WHERE cheque.id_cheque = pago_dcto.id_documento),
						pago_dcto.numero_documento)
				ELSE
					pago_dcto.numero_documento
			END) AS numero_documento,
			
			(CASE pago_dcto.tipo_pago
				WHEN 'NC' THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cp_notacredito cxp_nc
						INNER JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
					WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
			END) AS descripcion_motivo,
			
			(CASE pago_dcto.tipo_pago
				WHEN 'NC' THEN
					(SELECT cxp_nc.observacion_notacredito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
				WHEN 'ISLR' THEN
					(SELECT
						(CASE ret_cheque.tipo_documento
							WHEN 0 THEN
								CONCAT('RETENCION DEL CHEQUE NRO.', IFNULL(cheque.numero_cheque, cheque_anulado.numero_cheque))
							WHEN 1 THEN
								CONCAT('RETENCION DE LA TRANSFERENCIA NRO.', IFNULL(transferencia.numero_transferencia, transferencia_anulada.numero_transferencia))
						END)
					FROM te_retencion_cheque ret_cheque
						LEFT JOIN te_cheques cheque ON (ret_cheque.id_cheque = cheque.id_cheque
							AND ret_cheque.tipo_documento = 0)
						LEFT JOIN te_cheques_anulados cheque_anulado ON (ret_cheque.id_cheque = cheque_anulado.id_cheque
							AND ret_cheque.tipo_documento = 0)
						LEFT JOIN te_transferencias_anuladas transferencia_anulada ON (ret_cheque.id_cheque = transferencia_anulada.id_transferencia_anulada
							AND ret_cheque.tipo_documento = 1)
						LEFT JOIN te_transferencia transferencia ON (ret_cheque.id_cheque = transferencia.id_transferencia
							AND ret_cheque.tipo_documento = 1)
					WHERE ret_cheque.id_retencion_cheque = pago_dcto.id_documento)
			END) AS observacion_documento,
			
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM cp_pagos_documentos pago_dcto
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (pago_dcto.id_empleado_creador = vw_pg_empleado.id_empleado)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (pago_dcto.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE pago_dcto.id_pago = %s;",
			valTpDato($idPago, "int"));
		$rs = mysql_query($query);
		if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	} else {
		$cbxItm = sprintf("<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>",
			$contFila);
	}
	
	$classMontoPago = ($row['estatus'] != 1 && $totalRows > 0) ? "class=\"divMsjError\"" : "";
	
	$txtFechaPago = ($txtFechaPago == "" && $totalRows > 0) ? $row['fecha_pago'] : $txtFechaPago;
	$txtHoraPago = ($txtHoraPago == "" && $totalRows > 0) ? $row['tiempo_registro'] : $txtHoraPago;
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['tipo_pago'] : $txtMetodoPago;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['banco_compania'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ? $row['cuenta_compania'] : $txtCuentaCompaniaPago;
	$txtBancoProveedorPago = ($txtBancoProveedorPago == "" && $totalRows > 0) ? $row['banco_proveedor'] : $txtBancoProveedorPago;
	$txtCuentaProveedorPago = ($txtCuentaProveedorPago == "" && $totalRows > 0) ? $row['cuenta_proveedor'] : $txtCuentaProveedorPago;
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['monto_cancelado'] : $txtMontoPago;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$row['observacion_documento']))))."</span></div>" : "";
	$estatusPago = ($row['estatus'] != 1 && $totalRows > 0) ? "<div align=\"center\">PAGO ANULADO</div>" : "";
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por: </span><span class=\"textoNegrita_9px\">".$row['nombre_empleado_anulado']."</span></div><div align=\"center\">(".date(spanDateFormat." h:i:s a", strtotime($row['fecha_anulado'])).")</div>" : "";
	
	switch($row['id_modulo_documento_pago']) {
		case "" : $imgDctoModulo = ""; break;
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	switch ($txtMetodoPago) {
		case "NC" :
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("NC",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
			$objDcto->tipoDocumento = "NC";
			$objDcto->tipoDocumentoMovimiento = (in_array("NC",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pago'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
		case "RETENCION" :
			$aVerDcto = "<a href=\"javascript:verVentana(\'../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$txtIdNumeroDctoPago."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Comprobante de Retención\"/><a>"; break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr id=\"trItmPago:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItmPago:%s\">%s".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"<div>%s</div>".
				"<div>%s</div></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr>".
				"<td nowrap=\"nowrap\">%s</td>".
				"<td>%s</td>".
				"<td width=\"%s\"><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoProveedorPago%s\" name=\"txtBancoProveedorPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCuentaProveedorPago%s\" name=\"txtCuentaProveedorPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtMontoPago%s\" name=\"txtMontoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			$classMontoPago, $contFila, $contFila, utf8_encode(date(spanDateFormat, strtotime($txtFechaPago))),
				utf8_encode(date("h:i:s a", strtotime($txtHoraPago))),
				$empleadoCreadorPago,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$estatusPago,
			$classMontoPago, "100%",
				$aVerDcto,
				$imgDctoModulo,
				"100%", $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				$observacionDctoPago,
				$empleadoAnuladoPago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoProveedorPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCuentaProveedorPago),
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPago, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemMotivo($contFila, $hddIdNotaCargoDet = "", $idMotivo = "", $precioUnitario = "") {
	$contFila++;
	
	if ($hddIdNotaCargoDet > 0) {
		
	}
	
	$idMotivo = ($idMotivo == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['id_motivo'] : $idMotivo;
	$precioUnitario = ($precioUnitario == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['precio_unitario'] : $precioUnitario;
	$aClassReadonly = ($hddIdNotaCargoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompleto\"";
	$aEliminar = ($hddIdNotaCargoDet > 0) ? "" :
		sprintf("<a id=\"aEliminarItm:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>",
			$contFila);
	
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
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItmMotivo:%s\" title=\"trItmMotivo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmMotivo:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmMotivo:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdNotaCargoDet%s\" name=\"hddIdNotaCargoDet%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdMotivoItm%s\" name=\"hddIdMotivoItm%s\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');
		
		byId('txtPrecioItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEliminarItm:%s').onclick = function() {
			xajax_eliminarMotivo('%s', xajax.getFormValues('frmListaMotivo'));
		}",
		$contFila, $contFila, $clase,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			$rowMotivo['id_motivo'],
			$contFila, $contFila, $rowMotivo['descripcion'],
			$rowMotivo['descripcion_modulo_transaccion'],
			$rowMotivo['descripcion_tipo_transaccion'],
			$contFila, $contFila, $aClassReadonly, number_format($precioUnitario, 2, ".", ","),
			$aEliminar,
				$contFila, $contFila, $hddIdNotaCargoDet,
				$contFila, $contFila, $idMotivo,
		
		$contFila,
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>