<?php

function reconversion($idNotaCredito){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	$idNotaCredito2 = $idNotaCredito;

	//Verifico si la nota de carggo ya tiene reconversion
	$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_notacredito = $idNotaCredito2";
	$rsValidacion = mysql_query($queryValidacion);
	$numReg = mysql_num_rows($rsValidacion);

	$queryConsulta = "SELECT * FROM cp_pagos_documentos WHERE id_documento = $idNotaCredito2";
	$rsConsulta = mysql_query($queryConsulta);
	$valor = mysql_fetch_array($rsConsulta);
	$numReg1 = mysql_num_rows($rsConsulta);

	
	$queryValidacion2 = "SELECT * FROM cj_cc_notacredito WHERE id_notacredito = $idNotaCredito2 ";
	$rsValidacion2 = mysql_query($queryValidacion2);
	$numReg2 = mysql_fetch_array($rsValidacion2);
	
	$fechaRegistro = $numReg2['fecha_registro_notacredito'];
	//if ($rsValidacion2) return $objResponse->alert($fechaRegistro);
	$dateTime_fechaReconversion = '2018-08-20';

	if($fechaRegistro < $dateTime_fechaReconversion){	 
		if($numReg == 0){
	
			$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_factura =".$valor['id_documento'];
			$rsValidacion = mysql_query($queryValidacion);
			$numReg = mysql_num_rows($rsValidacion);
	
			
			//TABLA1
			$queryNotaCredito1 = "UPDATE cp_notacredito 
								SET monto_exento_notacredito = monto_exento_notacredito/100000,
									monto_exonerado_notacredito = monto_exonerado_notacredito/100000,
									subtotal_notacredito = subtotal_notacredito/100000,
									subtotal_descuento =  subtotal_descuento/100000,
									total_cuenta_pagar = total_cuenta_pagar/100000,
									saldo_notacredito = saldo_notacredito/100000
								WHERE id_notacredito = $idNotaCredito2 ";
			$rsNota1 = mysql_query($queryNotaCredito1);
			if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito1);
	
			//TABLA1
			$queryNotaCredito2 = "UPDATE cp_notacredito_detalle_motivo 
								SET precio_unitario = precio_unitario/100000
								WHERE id_notacredito = $idNotaCredito2 ";
			$rsNota2 = mysql_query($queryNotaCredito2);
			if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCredito2);
	
	
	
	
		
	if ($numReg1>0) {
				$queryFactura5 = "UPDATE cp_pagos_documentos 
								SET monto_cancelado = monto_cancelado/100000
								WHERE id_documento = $idNotaCredito2 ";
				$rsNota5 = mysql_query($queryFactura5);
				if (!$rsNota5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura5);
			}
			//TABLA6
				$queryReconversion = "INSERT INTO cp_reconversion (id_notacredito,id_usuarios) VALUES ($idNotaCredito2,$id_usuario)";
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
		return $objResponse->alert("No está permitido reconvertir una nota de credito con fecha igual o posterior al 20 de Agosto de 2018");
	}
}

function asignarDepartamento($frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroNotaCredito').readOnly = true;
		byId('txtNumeroNotaCredito').className = 'inputInicial';");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			//$objResponse->assign("tdtxtFechaProveedor","innerHTML","<input type=\"text\" id=\"txtFechaProveedor\" name=\"txtFechaProveedor\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
			
			$objResponse->script("
			byId('txtNumeroControl').readOnly = false;
			byId('txtNumeroControl').className = 'inputHabilitado';");
			/*$objResponse->script("
			byId('txtFechaProveedor').readOnly = true;
			byId('txtFechaProveedor').className = 'inputInicial';");*/
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
			
			$objResponse->assign("txtNumeroNotaCredito","value","");
			$objResponse->assign("txtNumeroControl","value","");
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("
		byId('txtNumeroNotaCredito').readOnly = false;
		byId('txtNumeroNotaCredito').className = 'inputHabilitado';
		byId('txtNumeroControl').readOnly = false;
		byId('txtNumeroControl').className = 'inputHabilitado';");
		
		$objResponse->assign("txtNumeroControl","value","");
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			//$objResponse->assign("tdtxtFechaProveedor","innerHTML","<input type=\"text\" id=\"txtFechaProveedor\" name=\"txtFechaProveedor\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
			
			/*$objResponse->script("
			byId('txtFechaProveedor').readOnly = true;
			byId('txtFechaProveedor').className = 'inputInicial';");*/
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
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Nota de Crédito de CxP)
	$queryConfig405 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 405 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig405 = mysql_query($queryConfig405);
	if (!$rsConfig405) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig405 = mysql_num_rows($rsConfig405);
	$rowConfig405 = mysql_fetch_assoc($rsConfig405);
	
	$valor = explode("|",$rowConfig405['valor']);
	
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

function asignarFacturaCompra($idFacturaCompra, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DE LA FACTURA DE COMPRA
	$queryFactura = sprintf("SELECT
		cxp_fact.id_factura,
		cxp_fact.id_proveedor,
		cxp_fact.numero_factura_proveedor,
		cxp_fact.numero_control_factura,
		cxp_fact.fecha_origen,
		cxp_fact.fecha_factura_proveedor,
		cxp_fact.id_modulo,
		cxp_fact.activa,
		cxp_fact.estatus_factura,
		(CASE cxp_fact.estatus_factura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		(CASE cxp_fact.tipo_pago
			WHEN 0 THEN 'Contado'
			WHEN 1 THEN 'Crédito'
		END) AS tipo_pago_factura,
		cxp_fact.saldo_factura,
		modulo.descripcionModulo,
		(CASE cxp_fact.aplica_libros
			WHEN 0 THEN 'NO'
			WHEN 1 THEN 'SI'
		END) AS aplica_libros_factura,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)
		) AS total
	FROM cp_factura cxp_fact
		INNER JOIN pg_modulos modulo ON (cxp_fact.id_modulo = modulo.id_modulo)
	WHERE id_factura = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
		
	// ASIGNA LOS DATOS DE LA FACTURA
	switch($rowFactura['estatus_factura']) {
		case 0 : $claseEstatus = "divMsjError"; break;
		case 1 : $claseEstatus = "divMsjInfo"; break;
		case 2 : $claseEstatus = "divMsjAlerta"; break;
	}
	
	switch($rowFactura['activa']) {
		case 1 : $claseActiva = ""; break;
		default : $claseActiva = "divMsjError"; break;
	}
	
	$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura']);
	$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtNumeroControlFactura","value",$rowFactura['numero_control_factura']);
	$objResponse->assign("txtFechaFactura","value",date(spanDateFormat, strtotime($rowFactura['fecha_factura_proveedor'])));
	$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat, strtotime($rowFactura['fecha_origen'])));
	$objResponse->assign("txtTipoPago","value",$rowFactura['tipo_pago_factura']);
	$objResponse->assign("txtModulo","value",$rowFactura['descripcionModulo']);
	$objResponse->assign("txtAplicaLibro","value",$rowFactura['aplica_libros_factura']);
	/*$objResponse->assign("txtIdMotivo","value",$rowFactura['id_motivo']);
	$objResponse->assign("txtMotivo","value",$rowFactura['descripcion']);*/
	$objResponse->script(sprintf("byId('tdtxtEstatusFactura').className = '%s';", $claseEstatus));
	$objResponse->assign("txtEstatusFactura","value",$rowFactura['descripcion_estado_factura']);
	$objResponse->script(sprintf("byId('tdtxtActivaFactura').className = '%s';", $claseActiva));
	$objResponse->assign("txtActivaFactura","value",(($rowFactura['activa'] == 1) ? "" : "Anulada"));
	$objResponse->assign("txtTotalFacturaCompra","value",number_format($rowFactura['total'], 2, ".", ","));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->loadCommands(asignarProveedor($rowFactura['id_proveedor'], "Prov", "false"));
		$objResponse->loadCommands(cargaLstModulo($rowFactura['id_modulo'], true));
		
		$objResponse->script("
		byId('btnCancelarListaFacturaCompra').click();");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
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

function buscarFacturaCompra($frmBuscarFacturaCompra, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarFacturaCompra['txtCriterioBuscarFacturaCompra']);
	
	$objResponse->loadCommands(listaFacturaCompra(0, "id_factura", "DESC", $valBusq));
	
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
	
	$contFila2 = $arrayObj2[count($arrayObj2)-1];
	
	// SUMA LOS PAGOS
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice2 => $valor2) {
			$txtTotalPago += ($frmListaPagoDcto['hddEstatusPago'.$valor2] == 1) ? str_replace(",","",$frmListaPagoDcto['txtMontoPago'.$valor2]) : 0;
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
	$txtGastosConIva = round(str_replace(",", "", $frmTotalDcto['txtGastosConIva']),2);
	$txtGastosSinIva = round(str_replace(",", "", $frmTotalDcto['txtGastosSinIva']),2);
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
			// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO
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
			if (/*$totalRows > 0 &&*/ $txtBaseImpIva > 0) {//Comentado totalrows para cuando es 1 solo iva
				$txtBaseImpIvaVenta = $txtBaseImpIva;
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
	
	$txtTotalOrden = $txtSubTotal - $txtSubTotalDescuento + $totalSubtotalIva + $txtGastosConIva + $txtGastosSinIva;
	$totalDctoPorPagar = (str_replace(",", "", $frmTotalDcto['txtTotalPorPagar']) > 0 || $frmDcto['lstModoCompra'] == 2) ? str_replace(",", "", $frmTotalDcto['txtTotalPorPagar']) : $txtTotalOrden;
	$txtTotalSaldo = $totalDctoPorPagar - $txtTotalPago;
	if (!($frmDcto['txtIdNotaCredito'] > 0)) {
		$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaVenta + $txtGastosConIva;
	}
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
	
	if ($frmDcto['txtIdNotaCredito'] > 0 || $frmTotalDcto['txtIdFactura'] > 0) {
		if ($frmDcto['txtIdNotaCredito'] > 0) {
			$objResponse->script("
			byId('txtIdEmpresa').className = 'inputInicial';
			byId('txtIdEmpresa').readOnly = true;
			byId('aListarEmpresa').style.display = 'none';");
		}
		$objResponse->script("
		byId('txtIdProv').className = 'inputInicial';
		byId('txtIdProv').readOnly = true;
		byId('aListarProv').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = ''
		
		byId('txtIdProv').className = 'inputHabilitado';
		byId('txtIdProv').readOnly = false;
		byId('aListarProv').style.display = '';");
	}
	
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

function formNotaCredito($idNotaCredito, $frmListaPagoDcto, $hddTipo) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	$contFila2 = $arrayObj2[count($arrayObj2)-1];
	
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
		
		byId('btnNotaCreditoPDF').style.display = 'none';
		
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
		
		byId('trListaPagoDcto').style.display = '';
		
		byId('aAgregarMotivo').style.display = 'none';
		byId('btnQuitarMotivo').style.display = 'none';
		byId('btnGuardar').style.display = 'none';
		
		byId('fieldsetGastos').style.display = 'none';
		byId('fieldsetGastosImportación').style.display = 'none';
		byId('fieldsetDatosImportación').style.display = 'none';
		byId('fieldsetFactura').style.display = 'none';
		
		byId('aListarFacturaCompra').style.display = 'none';
		byId('trTotalRegistroCompra').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
		$queryNotaCredito = sprintf("SELECT *,
			(CASE cxp_nc.estado_notacredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Sin Asignar'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS estado_nota_credito,
		
			(SELECT SUM(IFNULL(cxp_nc_gasto.monto_gasto_notacredito, 0)) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
			WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
				AND cxp_nc_gasto.id_modo_gasto IN (1,3) 
				AND cxp_nc_gasto.iva_notacredito > 0) AS subtotal_gastos_con_iva,
			
			(SELECT SUM(IFNULL(cxp_nc_gasto.monto_gasto_notacredito, 0)) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
			WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
				AND cxp_nc_gasto.id_modo_gasto IN (1,3) 
				AND (cxp_nc_gasto.iva_notacredito = 0 OR cxp_nc_gasto.iva_notacredito IS NULL)) AS subtotal_gastos_sin_iva
		FROM cp_notacredito cxp_nc
		WHERE id_notacredito = %s;",
			valTpDato($idNotaCredito, "text"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCredito = mysql_fetch_assoc($rsNotaCredito);
		
		// BUSCA LOS DATOS DE LA FACTURA DE LA NOTA DE CREDITO
		if ($rowNotaCredito['tipo_documento'] == "FA") {
			$objResponse->loadCommands(asignarFacturaCompra($rowNotaCredito['id_documento'], "false"));
			$objResponse->script("byId('fieldsetFactura').style.display = '';");
			$objResponse->script(sprintf("byId('aVerFactura').href = 'cp_factura_form.php?id=%s&vw=v';", $rowNotaCredito['id_documento']));
		}
		
		if ($_GET['vw'] != "v") {
			$objResponse->script("
			byId('btnGuardar').style.display = '';");
		}
		
		switch($rowNotaCredito['estado_notacredito']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
			case 3 : $claseEstatus = "divMsjInfo2"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCredito['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarProveedor($rowNotaCredito['id_proveedor'], "Prov", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowNotaCredito['id_empleado_creador']));
		
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat, strtotime($rowNotaCredito['fecha_registro_notacredito'])));
		$objResponse->assign("txtIdNotaCredito","value",$rowNotaCredito['id_notacredito']);
		$objResponse->assign("txtNumeroNotaCredito","value",utf8_encode($rowNotaCredito['numero_nota_credito']));
		$objResponse->assign("txtNumeroControl","value",utf8_encode($rowNotaCredito['numero_control_notacredito']));
		$objResponse->assign("txtFechaProveedor","value",date(spanDateFormat, strtotime($rowNotaCredito['fecha_notacredito'])));
		$objResponse->call("selectedOption","lstTipoPago",0);
		$objResponse->loadCommands(cargaLstModulo($rowNotaCredito['id_departamento_notacredito'], true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCredito['aplica_libros_notacredito']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatus","value",$rowNotaCredito['estado_nota_credito']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowNotaCredito['observacion_notacredito']));
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'".$rowNotaCredito['aplica_libros_notacredito']."');
		}");
		
		$objResponse->script("
		byId('lstTipoPago').onchange = function() {
			selectedOption(this.id,'".(0)."');
		}");
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("NC",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = "NC";
		$objDcto->tipoDocumentoMovimiento = (in_array("NC",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $rowNotaCredito['id_departamento_notacredito'];
		$objDcto->idDocumento = $rowNotaCredito['id_notacredito'];
		$objDcto->mostrarDocumento = "verVentanaPDF";
		$aVerDcto = $objDcto->verDocumento();
		
		$objResponse->script("
		byId('btnNotaCreditoPDF').style.display = '';
		byId('btnNotaCreditoPDF').onclick = function() { ".$aVerDcto." }");
		
		// CARGA EL DETALLE DE LA NOTA DE DEBITO
		$queryNotaCreditoDet = sprintf("SELECT * FROM cp_notacredito_detalle_motivo WHERE id_notacredito = %s
		ORDER BY id_notacredito_detalle_motivo ASC;",
			valTpDato($idNotaCredito, "int"));
		$rsNotaCreditoDet = mysql_query($queryNotaCreditoDet);
		if (!$rsNotaCreditoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowNotaCreditoDet = mysql_fetch_assoc($rsNotaCreditoDet)) {
			$Result1 = insertarItemMotivo($contFila, $rowNotaCreditoDet['id_notacredito_detalle_motivo'], $rowNotaCreditoDet['id_motivo'], $rowNotaCreditoDet['precio_unitario']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		// CARGA LOS GASTOS
		$queryGasto = sprintf("SELECT
			cxp_nc_gasto.id_gastos_notacredito,
			gasto.nombre,
			cxp_nc_gasto.porcentaje_monto,
			cxp_nc_gasto.monto_gasto_notacredito,
			cxp_nc_gasto.id_iva_notacredito,
			cxp_nc_gasto.iva_notacredito,
			cxp_nc_gasto.id_modo_gasto,
			cxp_nc_gasto.afecta_documento
		FROM cp_notacredito_gastos cxp_nc_gasto
			INNER JOIN pg_gastos gasto ON (cxp_nc_gasto.id_gastos_notacredito = gasto.id_gasto)
		WHERE cxp_nc_gasto.id_notacredito = %s
			AND cxp_nc_gasto.id_modo_gasto IN (1);",
			valTpDato($idNotaCredito, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsGasto = mysql_num_rows($rsGasto);
		$indice = 0;
		while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
			$indice++;
			
			$html = "";
			if ($rowGasto['id_iva_notacredito'] > 0) {
				$html .= "<table id=\"trIvaGasto".$indice."\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img id=\"imgIvaGasto".$indice."\" name=\"imgIvaGasto".$indice."\" src=\"../img/iconos/accept.png\"/>"."</td>";
					$html .= "<td align=\"right\">";
						$html .= sprintf("<input type=\"text\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">",
							$indice, $indice, $rowGasto['iva_notacredito']);
					$html .= "</td>";
					$html .= "<td>%</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			if ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) {
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trGasto:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trGasto:%s\">%s:</div>".
						"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
						"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td>%s</td>".
				"</tr>';
				
				obj = byId('trGasto:%s');
				if(obj == undefined)
					$('#trItmPieGastos').before(elemento);",
				$indice,
					$indice, utf8_encode($rowGasto['nombre']),
						$indice, $indice, $rowGasto['id_gasto'],
						$indice,
					$indice, $indice, $rowGasto['porcentaje_monto'], "%",
					$indice, $indice, number_format(round($rowGasto['monto_gasto_notacredito'],2), 2, ".", ","),
					$html,
				
				$indice));
		}
		if ($totalRowsGasto > 0) {
			$objResponse->script("byId('fieldsetGastos').style.display = '';");
		}
		
		// CARGA LOS GASTOS POR IMPORTACION
		$queryGasto = sprintf("SELECT
			cxp_nc_gasto.id_gastos_notacredito,
			gasto.nombre,
			cxp_nc_gasto.porcentaje_monto,
			cxp_nc_gasto.monto_gasto_notacredito,
			cxp_nc_gasto.id_iva_notacredito,
			cxp_nc_gasto.iva_notacredito,
			cxp_nc_gasto.id_modo_gasto,
			cxp_nc_gasto.afecta_documento
		FROM cp_notacredito_gastos cxp_nc_gasto
			INNER JOIN pg_gastos gasto ON (cxp_nc_gasto.id_gastos_notacredito = gasto.id_gasto)
		WHERE cxp_nc_gasto.id_notacredito = %s
			AND cxp_nc_gasto.id_modo_gasto IN (3);",
			valTpDato($idFactura, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsGasto = mysql_num_rows($rsGasto);
		$indice = 0;
		while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
			$indice++;
			
			$html = "";
			if ($rowGasto['id_iva_notacredito'] > 0) {
				$html .= "<table id=\"trIvaGasto".$indice."\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img id=\"imgIvaGasto".$indice."\" name=\"imgIvaGasto".$indice."\" src=\"../img/iconos/accept.png\" title=\"Aplica Impuesto\"/>"."</td>";
					$html .= "<td align=\"right\">";
						$html .= sprintf("<input type=\"text\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">",
							$indice, $indice, $rowGasto['iva_notacredito']);
					$html .= "</td>";
					$html .= "<td>%</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			if ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) {
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trGastoImportacion:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trGastoImportacion:%s\">%s:</div>".
						"<input type=\"hidden\" id=\"hddIdGastoImportacion%s\" name=\"hddIdGastoImportacion%s\" value=\"%s\"/>".
						"<input id=\"cbxGastoImportacion\" name=\"cbxGastoImportacion[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtPorcGastoImportacion%s\" name=\"txtPorcGastoImportacion%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td><input type=\"text\" id=\"txtMontoGastoImportacion%s\" name=\"txtMontoGastoImportacion%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td>%s</td>".
				"</tr>';
				
				obj = byId('trGastoImportacion:%s');
				if(obj == undefined)
					$('#trItmPieGastosImportacion').before(elemento);",
				$indice,
					$indice, utf8_encode($rowGasto['nombre']),
						$indice, $indice, $rowGasto['id_gasto'],
						$indice,
					$indice, $indice, $rowGasto['porcentaje_monto'], "%",
					$indice, $indice, number_format(round($rowGasto['monto_gasto_notacredito'],2), 2, ".", ","),
					$html,
				
				$indice));
		}
		if ($totalRowsGasto > 0) {
			$objResponse->script("byId('fieldsetGastosImportación').style.display = '';");
		}
		if ($rowNotaCredito['subtotal_advalorem'] > 0) {
			$objResponse->script("byId('fieldsetDatosImportación').style.display = '';");
		}
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT
			cxp_nc_iva.id_notacredito_iva,
			cxp_nc_iva.id_notacredito,
			cxp_nc_iva.baseimponible_notacredito,
			cxp_nc_iva.subtotal_iva_notacredito,
			cxp_nc_iva.id_iva_notacredito,
			cxp_nc_iva.iva_notacredito,
			iva.observacion
		FROM cp_notacredito_iva cxp_nc_iva
			INNER JOIN pg_iva iva ON (cxp_nc_iva.id_iva_notacredito = iva.idIva)
		WHERE cxp_nc_iva.id_notacredito = %s
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
				$indice,
					$indice, utf8_encode($rowIva['observacion']),
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
		$objResponse->assign("txtGastosConIva","value",number_format($rowNotaCredito['subtotal_gastos_con_iva'], 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($rowNotaCredito['subtotal_gastos_sin_iva'], 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowNotaCredito['monto_exento_notacredito'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowNotaCredito['monto_exonerado_notacredito'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCredito['saldo_notacredito'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$query = sprintf("SELECT * FROM cp_pagos_documentos pago_dcto
		WHERE (tipo_pago LIKE 'NC' AND id_documento = %s)
			OR (tipo_documento_pago LIKE 'NC' AND id_documento_pago = %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idNotaCredito, "int"));
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
		byId('txtNumeroNotaCredito').className = 'inputHabilitado';
		byId('txtNumeroControl').className = 'inputHabilitado';
		byId('txtFechaProveedor').className = 'inputHabilitado';
		byId('lstTipoPago').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('btnNotaCreditoPDF').style.display = 'none';
		
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
		
		byId('trListaPagoDcto').style.display = 'none';
		
		byId('fieldsetGastos').style.display = 'none';
		byId('fieldsetGastosImportación').style.display = 'none';
		byId('fieldsetDatosImportación').style.display = 'none';
		byId('fieldsetFactura').style.display = 'none';
		
		byId('aListarFacturaCompra').style.display = 'none';
		byId('trTotalRegistroCompra').style.display = 'none';");
		
		if ($hddTipo == 1) { // 1 = Desde Registro de Compra
			$objResponse->script("
			byId('fieldsetFactura').style.display = '';
			byId('aListarFacturaCompra').style.display = '';
			byId('trTotalRegistroCompra').style.display = '';
			
			byId('aVerFactura').style.display = 'none';");
		}
		
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
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
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
				$indiceIva,
					$indiceIva, utf8_encode($rowIva['observacion']),
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
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->assign("txtSubTotal","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format(0, 2, ".", ","));
		$objResponse->assign("hddTipo","value",$hddTipo);
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
	$idNotaCredito = $frmDcto['txtIdNotaCredito'];
	$idFacturaCompra = $frmTotalDcto['txtIdFactura'];
	$hddTipoDocumento = ($idFacturaCompra > 0) ? "FA" : "NC";
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCredito > 0) {
		if (!xvalidaAcceso($objResponse,"cp_nota_credito_captura_list","editar")) { return $objResponse; }
		
	} else {
		if (!xvalidaAcceso($objResponse,"cp_nota_credito_captura_list","insertar")) { return $objResponse; }
		
		if ($idFacturaCompra > 0 && str_replace(",", "", $frmDcto['txtTotalOrden']) > str_replace(",", "", $frmTotalDcto['txtTotalFacturaCompra'])) {
			return $objResponse->alert("La devolución no puede tener un monto mayor al del registro de compra");
		} else if (!($idFacturaCompra > 0) && str_replace(",", "", $frmTotalDcto['txtSubTotal']) > 0 && !(str_replace(",", "", $frmTotalDcto['txtTotalOrden']) > 0)) {
			return $objResponse->alert("La Nota de Crédito tiene un saldo inválido");
		}
		
		// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Nota de Crédito de CxP)
		$queryConfig405 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 405 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsConfig405 = mysql_query($queryConfig405);
		if (!$rsConfig405) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig405 = mysql_num_rows($rsConfig405);
		$rowConfig405 = mysql_fetch_assoc($rsConfig405);
		
		$valor = explode("|",$rowConfig405['valor']);
		
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
				$idNumeraciones = 9; // 9 = Nota Crédito CxP
			} else {
				$idNumeraciones = 9; // 9 = Nota Crédito CxP
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
			$numeroActual = $frmDcto['txtNumeroNotaCredito'];
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
		
		// INSERTAR LOS DATOS DE LA NOTA DE CRÉDITO
		$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, fecha_registro_notacredito, id_proveedor, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, subtotal_descuento, total_cuenta_pagar, saldo_notacredito, aplica_libros_notacredito, id_empleado_creador)
		VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroActual, "text"),
			valTpDato($numeroActualControl, "text"),
			valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato($frmDcto['lstModulo'], "int"),
			valTpDato($idFacturaCompra, "int"),
			valTpDato($hddTipoDocumento, "text"),
			valTpDato(1, "int"), // 0 = No Cancelado, 1 = Sin Asignar, 2 = Asignado Parcial, 3 = Asignado
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCredito = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL DETALLE DEL DOCUMENTO
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if (!($frmListaMotivo['hddIdNotaCreditoDet'.$valor] > 0)) {
					$idMotivo = $frmListaMotivo['hddIdMotivoItm'.$valor];
					$precioUnitario = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$valor]);
					
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
				}
			}
			
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
		}
		
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
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
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if ($idFacturaCompra > 0) {
			// CREACION DE LA RETENCION DEL IMPUESTO
			// BUSCA EL COMPROBANTE DE RETENCION DE LA FACTURA DE COMPRA A DEVOLVER
			$queryRetencionDetalle = sprintf("SELECT
				retencion.idRetencionCabezera,
				retencion.numeroComprobante,
				retencion.fechaComprobante,
				IF(retencion_det.id_nota_credito IS NULL, retencion_det.porcentajeRetencion, AVG(retencion_det.porcentajeRetencion)) AS porcentajeRetencion,
				SUM(retencion_det.IvaRetenido) AS IvaRetenido
			FROM cp_retenciondetalle retencion_det
				INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
			WHERE retencion_det.idFactura = %s
			GROUP BY retencion_det.idFactura;",
				valTpDato($idFacturaCompra, "int"));
			$rsRetencionDetalle = mysql_query($queryRetencionDetalle);
			if (!$rsRetencionDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsRetencionDetalle = mysql_num_rows($rsRetencionDetalle);
			$rowRetencionDetalle = mysql_fetch_assoc($rsRetencionDetalle);
			
			// VERIFICA QUE LA DEVOLUCION PERTENEZCA AL MISMO PERIODO FISCAL DE LA FACTURA DE COMPRA
			if ($totalRowsRetencionDetalle > 0
			&& ((date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) <= 15 && date("d", strtotime($frmDcto['txtFechaRegistroCompra'])) <= 15)
				|| (date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) > 15 && date("d", strtotime($frmDcto['txtFechaRegistroCompra'])) > 15))
			&& date(str_replace("d","01",spanDateFormat), strtotime($rowRetencionDetalle['fechaComprobante'])) == date(str_replace("d","01",spanDateFormat), strtotime($frmDcto['txtFechaRegistroCompra']))) {
				$idRetencionCabezera = $rowRetencionDetalle['idRetencionCabezera'];
				$porcRetencion = $rowRetencionDetalle['porcentajeRetencion'];
				$ivaRetenido = $rowRetencionDetalle['IvaRetenido'];
				
				$comprasSinIva = str_replace(",", "", $frmTotalDcto['txtTotalExento']) + str_replace(",", "", $frmTotalDcto['txtTotalExonerado']);
				
				if (isset($arrayObjIva)) {
					foreach ($arrayObjIva as $indice => $valor) {
						if (str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
							$ivaRetenido = round(($porcRetencion * str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor])) / 100, 2);
							
							// INSERTA EL DETALLE DE LA RETENCION
							$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, id_nota_cargo, id_nota_credito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idRetencionCabezera, "int"),
								valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
								valTpDato($idFacturaCompra, "int"),
								valTpDato($frmDcto['txtNumeroControl'], "text"),
								valTpDato("", "text"),
								valTpDato($idNotaCredito, "int"),
								valTpDato("03", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
								valTpDato($idFacturaCompra, "int"),
								valTpDato((-1) * str_replace(",", "", $frmTotalDcto['txtTotalOrden']), "real_inglesa"),
								valTpDato((-1) * $comprasSinIva, "real_inglesa"),
								valTpDato((-1) * str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]), "real_inglesa"),
								valTpDato((-1) * str_replace(",", "", $frmTotalDcto['txtIva'.$valor]), "real_inglesa"),
								valTpDato((-1) * str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]), "real_inglesa"),
								valTpDato((-1) * $ivaRetenido, "real_inglesa"),
								valTpDato((-1) * $porcRetencion, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
							
							// INSERTA EL PAGO DEBIDO A LA RETENCION
							$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idNotaCredito, "int"),
								valTpDato("NC", "text"),
								valTpDato("RETENCION", "text"),
								valTpDato($idRetencionCabezera, "int"),
								valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroCompra'])), "date"),
								valTpDato($rowRetencionDetalle['numeroComprobante'], "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato($ivaRetenido, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
							
							// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
							$updateSQL = sprintf("UPDATE cp_notacredito SET
								saldo_notacredito = (saldo_notacredito - %s)
							WHERE id_notacredito = %s;",
								valTpDato($ivaRetenido, "real_inglesa"),
								valTpDato($idNotaCredito, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						}
					}
				}
			}
			
			// BUSCA LOS DATOS DE LA NOTA DE CREDITO
			$queryNotaCredito = sprintf("SELECT * FROM cp_notacredito cxp_nc
			WHERE cxp_nc.id_notacredito = %s;",
				valTpDato($idNotaCredito, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
			$rowNotaCredito = mysql_fetch_assoc($rsNotaCredito);
			
			// BUSCA LOS DATOS DE LA FACTURA A DEVOLVER
			$queryFactura = sprintf("SELECT * FROM cp_factura cxp_fact
			WHERE cxp_fact.id_factura = %s;",
				valTpDato($idFacturaCompra, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFactura = mysql_num_rows($rsFactura);
			$rowFactura = mysql_fetch_assoc($rsFactura);
			
			if ($rowFactura['estatus_factura'] == 0 || $rowFactura['estatus_factura'] == 2) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
				if (doubleval($rowFactura['saldo_factura']) >= doubleval($rowNotaCredito['saldo_notacredito'])) {
					// INSERTA EL PAGO DEBIDO A LA NOTA DE CREDITO
					$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
					VALUES (%s, %s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaCompra, "int"),
						valTpDato("FA", "text"),
						valTpDato("NC", "text"),
						valTpDato($idNotaCredito, "int"),
						valTpDato($rowNotaCredito['numero_nota_credito'], "text"),
						valTpDato("-", "text"),
						valTpDato("-", "text"),
						valTpDato("-", "text"),
						valTpDato("-", "text"),
						valTpDato($rowNotaCredito['saldo_notacredito'], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else if (doubleval($rowFactura['saldo_factura']) < doubleval($rowNotaCredito['saldo_notacredito'])) {
					// INSERTA EL PAGO DEBIDO A LA NOTA DE CREDITO
					$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
					VALUES (%s, %s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaCompra, "int"),
						valTpDato("FA", "text"),
						valTpDato("NC", "text"),
						valTpDato($idNotaCredito, "int"),
						valTpDato($rowNotaCredito['numero_nota_credito'], "text"),
						valTpDato("-", "text"),
						valTpDato("-", "text"),
						valTpDato("-", "text"),
						valTpDato("-", "text"),
						valTpDato($rowFactura['saldo_factura'], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
				
				// ACTUALIZA EL SALDO DE LA FACTURA
				$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
					saldo_factura = (IFNULL(cxp_fact.subtotal_factura, 0)
									- IFNULL(cxp_fact.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
											FROM cp_factura_gasto cxp_fact_gasto
											WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
												AND cxp_fact_gasto.id_modo_gasto IN (1,3)
												AND cxp_fact_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
											FROM cp_factura_iva cxp_fact_iva
											WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)
									) - IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
										WHERE pago_dcto.id_documento_pago = cxp_fact.id_factura
											AND pago_dcto.tipo_documento_pago LIKE 'FA'
											AND pago_dcto.estatus = 1), 0)
				WHERE id_factura = %s
					AND estatus_factura NOT IN (1);",
					valTpDato($idFacturaCompra, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
				$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
					saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
										- IFNULL(cxp_nc.subtotal_descuento, 0)
										+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
												WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
										+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
												WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
										) - IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE (tipo_pago LIKE 'NC' AND id_documento = cxp_nc.id_notacredito)
												OR (tipo_documento_pago LIKE 'NC' AND id_documento_pago = cxp_nc.id_notacredito)), 0)
				WHERE id_notacredito = %s
					AND estado_notacredito NOT IN (3);",
					valTpDato($idNotaCredito, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
			estatus_factura = (CASE
								WHEN (ROUND(saldo_factura, 2) = 0 OR ROUND(saldo_factura, 2) < 0) THEN
									1
								WHEN (ROUND(saldo_factura, 2) > 0 AND ROUND(saldo_factura, 2) < (IFNULL(cxp_fact.subtotal_factura, 0)
																			- IFNULL(cxp_fact.subtotal_descuento, 0)
																			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
																				FROM cp_factura_gasto cxp_fact_gasto
																				WHERE (cxp_fact_gasto.id_factura = cxp_fact.id_factura)), 0)
																			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
																				FROM cp_factura_iva cxp_fact_iva
																				WHERE (cxp_fact_iva.id_factura = cxp_fact.id_factura)), 0))) THEN
									2
								ELSE
									0
							END)
		WHERE id_factura = %s;",
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
		$querySaldoDcto = sprintf("SELECT cxp_fact.*,
			prov.nombre AS nombre_proveedor
		FROM cp_factura cxp_fact
			INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		WHERE cxp_fact.id_factura = %s
			AND cxp_fact.saldo_factura < 0;",
			valTpDato($idFacturaCompra, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numero_factura_proveedor']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
		
		
		// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
		$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
			estado_notacredito = (CASE
									WHEN (saldo_notacredito = 0) THEN
										3
									WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(cxp_nc.subtotal_notacredito, 0)
												- IFNULL(cxp_nc.subtotal_descuento, 0)
												+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
														WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
												+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
														WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))) THEN
										2
									WHEN (saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
												- IFNULL(cxp_nc.subtotal_descuento, 0)
												+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
														WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
												+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
														WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))) THEN
										1
									ELSE
										0
								END)
		WHERE id_notacredito = %s;",
			valTpDato($idNotaCredito, "int"));
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
			valTpDato($idNotaCredito, "int"));
		$rsSaldoDcto = mysql_query($querySaldoDcto);
		if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
		$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
		$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
		if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Crédito Nro. ".$rowSaldoDcto['numero_nota_credito']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
		
		mysql_query("COMMIT;");
		
		$objResponse->assign("txtIdNotaCredito","value",$idNotaCredito);
		
		// MODIFICADO ERNESTO
		if ($rowNotaCredito['subtotal_notacredito'] < $rowFactura['subtotal_factura']) {
			if(function_exists("generarNotasCreditoCpReParcial")) { generarNotasCreditoCpReParcial($idNotaCredito,"",""); }
		} else {
			switch ($frmDcto['lstModulo']) {
				case 0 : if (function_exists("generarNotasCreditoCpRe")) { generarNotasCreditoCpRe($idNotaCredito,"",""); } break;
				case 1 : if (function_exists("generarNotasCreditoCpSe")) { generarNotasCreditoCpSe($idNotaCredito,"",""); } break;
				case 2 : if (function_exists("generarNotasCreditoCpVe")) { generarNotasCreditoCpVe($idNotaCredito,"",""); } break;
				case 3 : if (function_exists("generarNotasCreditoCpAd")) { generarNotasCreditoCpAd($idNotaCredito,"",""); } break;
			}
		}
		// MODIFICADO ERNESTO
	}
	
	$objResponse->alert("Nota de Crédito Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['rbtRetencion'] == 1) ? 0 : 1;
	
	$objResponse->script("verVentana('reportes/cp_nota_credito_pdf.php?valBusq=".$idNotaCredito."', 960, 550);");
	
	$objResponse->script(sprintf("window.location.href='cp_nota_credito_historico_list.php';"));
	
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
	
	$rowConfig5['valor'] = 1;
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
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

function listaFacturaCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activa = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = cxp_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_fact.numero_control_factura LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxp_fact.id_factura,
		cxp_fact.fecha_origen,
		cxp_fact.fecha_factura_proveedor,
		cxp_fact.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		cxp_fact.id_modulo,
		cxp_fact.activa,
		cxp_fact.estatus_factura,
		(CASE cxp_fact.estatus_factura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		
		(CASE id_modulo
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
		END) AS cant_items,
		
		cxp_fact.saldo_factura,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto = 1
						AND cxp_fact_gasto.afecta_documento = 1), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)
		) AS total,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
		LIMIT 1) AS idRetencionCabezera
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "38%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "10%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "6%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "10%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "12%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0 || $row['id_orden_tot'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
			
		switch($row['activa']) {
			case "" : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devolución)\"/>"; break;
			case 1 : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			default : $imgEstatusRegistroCompra = "";
		}
		
		switch($row['estatus_factura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarFacturaCompra%s\" onclick=\"xajax_asignarFacturaCompra('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusRegistroCompra."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_factura'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['saldo_factura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaFacturaCompra","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarFacturaCompra");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarFacturaCompra");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivo");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivoLote");
$xajax->register(XAJAX_FUNCTION,"formNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarMotivo");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaFacturaCompra");
$xajax->register(XAJAX_FUNCTION,"reconversion");

function insertarItemMetodoPago($contFila, $idPago = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoProveedorPago = "", $txtCuentaProveedorPago = "", $txtMontoPago = "") {
	global $raiz;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("SELECT pago_dcto.*,
			(CASE 
				WHEN pago_dcto.tipo_pago LIKE 'NC' THEN
					(CASE
						WHEN pago_dcto.tipo_documento_pago LIKE 'FA' THEN
							(SELECT cxp_fact.id_modulo FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = pago_dcto.id_documento_pago)
						WHEN pago_dcto.tipo_documento_pago LIKE 'ND' THEN
							(SELECT cxp_nd.id_modulo FROM cp_notadecargo cxp_nd WHERE cxp_nd.id_notacargo = pago_dcto.id_documento_pago)
					END)
			END) AS id_modulo,
		
			(CASE 
				WHEN pago_dcto.tipo_pago LIKE 'NC' THEN
					pago_dcto.tipo_documento_pago
				WHEN pago_dcto.tipo_documento_pago LIKE 'NC' THEN
					pago_dcto.tipo_pago
			END) AS tipo_documento_pago,
			
			(CASE 
				WHEN pago_dcto.tipo_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_documento_pago
						WHEN 'FA' THEN
							(SELECT cxp_fact.id_modulo FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = pago_dcto.id_documento_pago)
						WHEN 'ND' THEN
							(SELECT cxp_nd.id_modulo FROM cp_notadecargo cxp_nd WHERE cxp_nd.id_notacargo = pago_dcto.id_documento_pago)
					END)
				WHEN pago_dcto.tipo_documento_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_pago
						WHEN 'NC' THEN
							(SELECT cxp_nc.id_departamento_notacredito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
						WHEN 'AN' THEN
							(SELECT cxp_ant.idDepartamento FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = pago_dcto.id_documento)
						ELSE
							pago_dcto.numero_documento
					END)
			END) AS id_modulo_documento_pagado,
			
			(CASE 
				WHEN pago_dcto.tipo_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_documento_pago
						WHEN 'FA' THEN
							(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = pago_dcto.id_documento_pago)
						WHEN 'ND' THEN
							(SELECT cxp_nd.numero_notacargo FROM cp_notadecargo cxp_nd WHERE cxp_nd.id_notacargo = pago_dcto.id_documento_pago)
					END)
				WHEN pago_dcto.tipo_documento_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_pago
						WHEN 'NC' THEN
							(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
						WHEN 'AN' THEN
							(SELECT cxp_ant.numeroAnticipo FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = pago_dcto.id_documento)
						ELSE
							pago_dcto.numero_documento
					END)
			END) AS numero_documento_pagado,
			
			(CASE 
				WHEN pago_dcto.tipo_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_documento_pago
						WHEN 'ND' THEN
							(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
							FROM cp_notadecargo cxp_nd
								INNER JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
							WHERE cxp_nd.id_notacargo = pago_dcto.id_documento_pago)
					END)
				WHEN pago_dcto.tipo_documento_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_pago
						WHEN 'NC' THEN
							(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
							FROM cp_notacredito cxp_nc
								INNER JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
							WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
					END)
			END) AS descripcion_motivo,
			
			(CASE 
				WHEN pago_dcto.tipo_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_documento_pago
						WHEN 'FA' THEN
							(SELECT cxp_fact.observacion_factura FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = pago_dcto.id_documento_pago)
						WHEN 'ND' THEN
							(SELECT cxp_nd.observacion_notacargo FROM cp_notadecargo cxp_nd WHERE cxp_nd.id_notacargo = pago_dcto.id_documento_pago)
					END)
				WHEN pago_dcto.tipo_pago LIKE 'ISLR' THEN
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
				WHEN pago_dcto.tipo_documento_pago LIKE 'NC' THEN
					(CASE pago_dcto.tipo_pago
						WHEN 'NC' THEN
							(SELECT cxp_nc.observacion_notacredito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = pago_dcto.id_documento)
						WHEN 'AN' THEN
							(SELECT cxp_ant.observaciones FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = pago_dcto.id_documento)
						ELSE
							pago_dcto.numero_documento
					END)
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
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['tipo_documento_pago'] : $txtMetodoPago;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento_pago'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento_pagado'] : $txtNumeroDctoPago;
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
	
	switch($row['id_modulo_documento_pagado']) {
		case "" : $imgDctoModulo = ""; break;
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
		default : $imgDctoModulo = $row['id_modulo_documento_pagado'];
	}
	
	switch ($txtMetodoPago) {
		case "RETENCION" :
			$aVerDcto = "<a href=\"javascript:verVentana(\'../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$row['id_documento']."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Comprobante de Retención\"/><a>"; break;
		case "FA" :
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
			$objDcto->tipoDocumento = "FA";
			$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pagado'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
		case "ND" :
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("ND",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
			$objDcto->tipoDocumento = "ND";
			$objDcto->tipoDocumentoMovimiento = (in_array("ND",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pagado'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
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

function insertarItemMotivo($contFila, $hddIdNotaCreditoDet = "", $idMotivo = "", $precioUnitario = "") {
	$contFila++;
	
	if ($hddIdNotaCreditoDet > 0) {
		
	}
	
	$idMotivo = ($idMotivo == "" && $totalRowsNotaCreditoDet > 0) ? $rowNotaCreditoDet['id_motivo'] : $idMotivo;
	$precioUnitario = ($precioUnitario == "" && $totalRowsNotaCreditoDet > 0) ? $rowNotaCreditoDet['precio_unitario'] : $precioUnitario;
	$aClassReadonly = ($hddIdNotaCreditoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompleto\"";
	$aEliminar = ($hddIdNotaCreditoDet > 0) ? "" :
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
				"<input type=\"hidden\" id=\"hddIdNotaCreditoDet%s\" name=\"hddIdNotaCreditoDet%s\" readonly=\"readonly\" value=\"%s\"/>".
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
				$contFila, $contFila, $hddIdNotaCreditoDet,
				$contFila, $contFila, $idMotivo,
		
		$contFila,
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>