<?php
function buscar($frmBuscar){
	$objResponse = new xajaxResponse();

	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmBuscar['lstEmpresa'];
	}	
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listadoCheques(0, "", "ASC", $valBusq));

	return $objResponse;
}

function cargarPagina($idEmpresa){
	
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$objResponse->script("
		byId('trEmpresa').style.display = 'none';");
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->script("
		byId('trEmpresa').style.display = '';");
	}
	
	return $objResponse;
}

function guardarNotaCargo($numeroCheque,$monto,$idCliente,$idBanco){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
		
	mysql_query("START TRANSACTION");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
	//VERIFICA QUE EL CHEQUE SE ENCUENTRE DEPOSITADO
	$sqlSelectCheque = sprintf("SELECT *
	FROM
		an_detalledeposito
	WHERE
		numeroCheque LIKE %s AND banco = %s",
			valTpDato($numeroCheque, "text"),
			valTpDato($idBanco, "int"));
	$rsSelectCheque = mysql_query($sqlSelectCheque);
	if (!$rsSelectCheque) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlSelectCheque);
	$rowSelectCheque = mysql_fetch_array($rsSelectCheque);
	$totalRowsCheque = mysql_num_rows($rsSelectCheque);
	if ($totalRowsCheque > 0) {
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_numeracion = %s
			AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC
		LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 13 : 23), "int"), // 13 = Nota Cargo Vehículos, 23 = Nota Cargo Repuestos y Servicios
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActual = $rowNumeracion['numero_actual'];
		
		$queryInsertNotaCargo = sprintf("INSERT INTO cj_cc_notadecargo (id_empresa, idCliente, numeroNotaCargo, numeroControlNotaCargo, fechaRegistroNotaCargo, fechaVencimientoNotaCargo, idDepartamentoOrigenNotaCargo, referencia_nota_cargo, idBanco, id_motivo, tipoNotaCargo, diasDeCreditoNotaCargo, estadoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, observacionNotaCargo, subtotalNotaCargo, fletesNotaCargo, interesesNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($numeroActual, "int"),
			valTpDato($numeroCheque, "text"),
			valTpDato("NOW()", "campo"),
			valTpDato("NOW()", "campo"),
			valTpDato("2", "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administracion
			valTpDato(0, "text"), // 0 = Cheque Devuelto, 1 = Otros
			valTpDato($idBanco, "int"),
			valTpDato(364, "int"), // 364 = CHEQUE DEVUELTO DE CLIENTE (Motivo Fijo)
			valTpDato(0, "int"), // 0 = Credito, 1 = Contado
			valTpDato(0, "int"),
			valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
			valTpDato($monto, "double"),
			valTpDato($monto, "double"),
			valTpDato('DEVOLUCION DE CHEQUE '.$numeroCheque, "text"),
			valTpDato($monto, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "double"),
			valTpDato($monto, "double"),
			valTpDato(0, "double"),
			valTpDato(0, "int"), // 0 = No, 1 = Si
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$rsInsertNotaCargo = mysql_query($queryInsertNotaCargo);
		if (!$rsInsertNotaCargo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryInsertNotaCargo);
		$idNotaCargo = mysql_insert_id();
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		//INSERCCION cj_cc_estado_cuenta
		$sqlInsertEstadoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN) VALUES ('ND', %s, NOW(), 2)",
			valTpDato($idNotaCargo, "int"));
		$rsInsertEstadoCuenta = mysql_query($sqlInsertEstadoCuenta);
		if (!$rsInsertEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuenta);
		//FIN INSERCCION cj_cc_estado_cuenta
		
		//INSERCCION DE LA NOTA DE CARGO EN TESORERIA Y REBAJAR EL SALDO DE LA CUENTA
		$sqlSelectDatosBanco = sprintf("SELECT
			idBancoAdepositar AS idBancoAdepositar,
			(SELECT nombreBanco FROM bancos ba WHERE ba.idBanco = deta.idBancoAdepositar) AS nombreBanco,
			(SELECT idCuentas FROM cuentas cu WHERE cu.numeroCuentaCompania LIKE deta.numeroCuentaBancoAdepositar) AS idCuenta
		FROM
			an_detalledeposito deta
		WHERE
			numeroCheque LIKE %s AND banco = %s ",
				valTpDato($numeroCheque, "text"),
				valTpDato($idBanco, "int"));
		$rsSelectDatosBanco = mysql_query($sqlSelectDatosBanco);
		if (!$rsSelectDatosBanco) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlSelectDatosBanco);
		$rowSelectDatosBanco = mysql_fetch_array($rsSelectDatosBanco);
				
		//CONSULTAR EL NUMERO DE FOLIO DE NOTA DEBITO EN TESORERIA
		$sqlNumeroFolio = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 1");
		$rsNumeroFolio = mysql_query($sqlNumeroFolio);
		if (!$rsNumeroFolio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlNumeroFolio);
		$rowNumeroFolio = mysql_fetch_array($rsNumeroFolio);
		
		$updateNumeroFolio = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 1");
		$rsUpdateNumeroFolio = mysql_query($updateNumeroFolio);
		if (!$rsUpdateNumeroFolio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateNumeroFolio);
		
		$sqlInsertNotaDebitoTesoreria = sprintf("INSERT INTO te_nota_debito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, fecha_movimiento_banco, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_debito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_debito, id_motivo)
		VALUES (%s, NOW(), %s, 0, %s, NOW(), NOW(), 0, 2, %s, %s, %s, 0, %s, 1, %s, 515)",
			valTpDato($rowSelectDatosBanco['idCuenta'], "int"),
			valTpDato($rowNumeroFolio['numero_actual'], "int"),
			valTpDato("CHEQUE DEVUELTO ".$rowSelectDatosBanco['nombreBanco'], "text"),
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idUsuario, "int"),
			valTpDato($monto, "double"),
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroCheque, "text"));
		$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
		if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertNotaDebitoTesoreria);
		$idNotaDebito = mysql_insert_id();
		
		$sqlInsertEstadoCuentaTesoreria = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
		VALUES('ND', %s, NOW(), %s, %s, %s, 0, %s, 1, %s, 2, 0)",
			valTpDato($idNotaDebito, "int"),
			valTpDato($rowSelectDatosBanco['idCuenta'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($monto, "double"),
			valTpDato($numeroCheque, "text"),
			valTpDato("CHEQUE DEVUELTO ".$rowSelectDatosBanco['nombreBanco'], "text"));
		$rsInsertEstadoCuentaTesoreria = mysql_query($sqlInsertEstadoCuentaTesoreria);
		if (!$rsInsertEstadoCuentaTesoreria) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlInsertEstadoCuentaTesoreria);
	
		$sqlSaldoCuenta = sprintf("SELECT saldo_tem FROM cuentas WHERE idCuentas = %s",valTpDato($rowSelectDatosBanco['idCuenta'], "int"));
		$rsSaldoCuenta = mysql_query($sqlSaldoCuenta);
		if (!$rsSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlSaldoCuenta);
		$rowSaldoCuenta = mysql_fetch_array($rsSaldoCuenta);
		
		$sqlUpdateSaldoCuenta = sprintf("UPDATE cuentas SET saldo_tem = %s WHERE idCuentas = %s",
										valTpDato($rowSaldoCuenta['saldo_tem'] - $monto, "double"),
										valTpDato($rowSelectDatosBanco['idCuenta'], "int"));
		$rsUpdateSaldoCuenta = mysql_query($sqlUpdateSaldoCuenta);
		if (!$rsUpdateSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$sqlUpdateSaldoCuenta);
		//FIN INSERCCION DE LA NOTA DE CARGO EN TESORERIA Y REBAJAR EL SALDO DE LA CUENTA
		
		$objResponse->alert("Pago cargado correctamente");
		
		$objResponse->script(sprintf("window.location.href='cj_devolucion_cheque_list.php';"));
		
		$objResponse->script(sprintf("verVentana('reportes/cjvh_comprobante_devolucion_nota_cargo.php?valBusq=%s|%s',960,550)", $idEmpresa, $idNotaCargo));

	} else {
		$objResponse->alert('El Cheque que intenta devolver aun no ha sido depositado.');
	}
	
	mysql_query("COMMIT");
	
	// MODIFICADO ERNESTO
	if (function_exists("generarNotasCargoVe")) { generarNotasCargoVe($idNotaCargo,"",""); }
	if (function_exists("generarNotaDebitoTe")) { generarNotaDebitoTe($idNotaDebito,"",""); }
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function imprimirDevolucionCheque($frmBuscar){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$idEmpresa = $frmBuscar['lstEmpresa'];
	}
		
	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjvh_devolucion_cheque_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listadoCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqAnticipo .= $cond.sprintf("cj_cc_anticipo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$sqlBusqNotaCargo .= $cond.sprintf("cj_cc_notadecargo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
			
		$sqlBusqFactura .= $cond.sprintf("cj_cc_encabezadofactura.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
		
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqAnticipo .= $cond.sprintf("cj_cc_anticipo.fechaAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$sqlBusqNotaCargo .= $cond.sprintf("cj_det_nota_cargo.fechaPago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$sqlBusqFactura .= $cond.sprintf("cxc_pago.fechaPago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqAnticipo .= $cond.sprintf("(cj_cc_detalleanticipo.numeroControlDetalleAnticipo LIKE %s
		OR (SELECT CONCAT_WS(' ',nombre,apellido) FROM cj_cc_cliente WHERE id = cj_cc_anticipo.idCliente) LIKE %s)",
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
		
		$sqlBusqNotaCargo .= $cond.sprintf("(cj_det_nota_cargo.numeroDocumento LIKE %s
		OR (SELECT CONCAT_WS(' ',nombre,apellido) FROM cj_cc_cliente WHERE id = cj_cc_notadecargo.idCliente) LIKE %s)",
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
		
		$sqlBusqFactura .= $cond.sprintf("(cxc_pago.numeroDocumento LIKE %s
		OR (SELECT CONCAT_WS(' ',nombre,apellido) FROM cj_cc_cliente WHERE id = cj_cc_encabezadofactura.idCliente) LIKE %s)",
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		'ANTICIPO' AS tipoDocumento,
		cj_cc_anticipo.idAnticipo AS idDocumento,
		cj_cc_anticipo.idCliente AS idCliente,
		cj_cc_anticipo.id_empresa AS idEmpresa,
		cj_cc_anticipo.numeroAnticipo AS numeroDocumento,
		cj_cc_detalleanticipo.bancoClienteDetalleAnticipo AS idBanco,
		bancos.nombreBanco AS nombreBanco,
		cj_cc_detalleanticipo.numeroControlDetalleAnticipo AS numeroCheque,
		cj_cc_detalleanticipo.montoDetalleAnticipo AS montoCheque,
		cj_cc_anticipo.fechaAnticipo AS fechaCheque,
		CONCAT_WS(' ', cj_cc_cliente.nombre,cj_cc_cliente.apellido) AS nombreCliente,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombreEmpresa
	FROM cj_cc_anticipo
		INNER JOIN cj_cc_detalleanticipo ON (cj_cc_detalleanticipo.idAnticipo = cj_cc_anticipo.idAnticipo)
		INNER JOIN cj_cc_cliente ON (cj_cc_anticipo.idCliente = cj_cc_cliente.id)
		INNER JOIN bancos ON (cj_cc_detalleanticipo.bancoClienteDetalleAnticipo = bancos.idBanco)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_anticipo.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cj_cc_detalleanticipo.tipoPagoDetalleAnticipo = 'CH'
		AND cj_cc_anticipo.idDepartamento IN (%s)
		AND cj_cc_detalleanticipo.numeroControlDetalleAnticipo NOT IN (SELECT cxc_nd.numeroControlNotaCargo
											FROM cj_cc_notadecargo cxc_nd
											WHERE cxc_nd.numeroControlNotaCargo = cj_cc_detalleanticipo.numeroControlDetalleAnticipo
												AND cxc_nd.idCliente = cj_cc_anticipo.idCliente
												AND cxc_nd.idBanco = cj_cc_detalleanticipo.bancoClienteDetalleAnticipo
												AND cxc_nd.montoTotalNotaCargo = cj_cc_detalleanticipo.montoDetalleAnticipo)
		AND cj_cc_anticipo.estatus = 1 %s
		
	UNION
	
	SELECT 
		'NOTA DE CARGO' AS tipoDocumento,
		cj_cc_notadecargo.idNotaCargo AS idDocumento,
		cj_cc_notadecargo.idCliente AS idCliente,
		cj_cc_notadecargo.id_empresa AS idEmpresa,
		cj_cc_notadecargo.numeroNotaCargo AS numeroDocumento,
		cj_det_nota_cargo.bancoOrigen AS idBanco,
		bancos.nombreBanco AS nombreBanco,
		cj_det_nota_cargo.numeroDocumento AS numeroCheque,
		cj_det_nota_cargo.monto_pago AS montoCheque,
		cj_det_nota_cargo.fechaPago AS fechaCheque,
		CONCAT_WS(' ', cj_cc_cliente.nombre,cj_cc_cliente.apellido) AS nombreCliente, 
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombreEmpresa
	FROM cj_cc_notadecargo
		INNER JOIN cj_det_nota_cargo ON (cj_cc_notadecargo.idNotaCargo = cj_det_nota_cargo.idNotaCargo)
		INNER JOIN cj_cc_cliente ON (cj_cc_notadecargo.idCliente = cj_cc_cliente.id)
		INNER JOIN bancos ON (cj_det_nota_cargo.bancoOrigen = bancos.idBanco)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_notadecargo.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cj_det_nota_cargo.idFormaPago = 2
		AND cj_cc_notadecargo.idDepartamentoOrigenNotaCargo IN (%s)
		AND cj_det_nota_cargo.numeroDocumento NOT IN (SELECT cxc_nd.numeroControlNotaCargo
											FROM cj_cc_notadecargo cxc_nd
											WHERE cxc_nd.numeroControlNotaCargo = cj_det_nota_cargo.numeroDocumento
												AND cxc_nd.idCliente = cj_cc_notadecargo.idCliente
												AND cxc_nd.idBanco = cj_det_nota_cargo.bancoOrigen
												AND cxc_nd.montoTotalNotaCargo = cj_det_nota_cargo.monto_pago) %s
		
	UNION
	
	SELECT 
		'FACTURA' AS tipoDocumento,
		cj_cc_encabezadofactura.idFactura AS idDocumento,
		cj_cc_encabezadofactura.idCliente AS idCliente,
		cj_cc_encabezadofactura.id_empresa AS idEmpresa,
		cxc_pago.numeroFactura AS numeroDocumento,
		cxc_pago.bancoOrigen AS idBanco,
		bancos.nombreBanco AS nombreBanco,
		cxc_pago.numeroDocumento AS numeroCheque,
		cxc_pago.montoPagado AS montoCheque,
		cxc_pago.fechaPago AS fechaCheque,
		CONCAT_WS(' ', cj_cc_cliente.nombre,cj_cc_cliente.apellido) AS nombreCliente, 
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombreEmpresa
	FROM cj_cc_encabezadofactura
		INNER JOIN an_pagos cxc_pago ON (cj_cc_encabezadofactura.idFactura = cxc_pago.id_factura)
		INNER JOIN cj_cc_cliente ON (cj_cc_encabezadofactura.idCliente = cj_cc_cliente.id) 
		INNER JOIN bancos ON (cxc_pago.bancoOrigen = bancos.idBanco)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_encabezadofactura.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_pago.formaPago = 2
		AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (%s)
		AND cxc_pago.numeroDocumento NOT IN (SELECT cxc_nd.numeroControlNotaCargo
											FROM cj_cc_notadecargo cxc_nd
											WHERE cxc_nd.numeroControlNotaCargo = cxc_pago.numeroDocumento
												AND cxc_nd.idCliente = cj_cc_encabezadofactura.idCliente
												AND cxc_nd.idBanco = cxc_pago.bancoOrigen
												AND cxc_nd.montoTotalNotaCargo = cxc_pago.montoPagado) %s",
			valTpDato($idModuloPpal, "campo"), $sqlBusqAnticipo,
			valTpDato($idModuloPpal, "campo"), $sqlBusqNotaCargo,
			valTpDato($idModuloPpal, "campo"), $sqlBusqFactura);
								
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "20%", $pageNum, "nombreEmpresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "8%", $pageNum, "fechaCheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "8%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "8%", $pageNum, "numeroCheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cheque");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "20%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "20%", $pageNum, "nombreCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "8%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Documento");
		$htmlTh .= ordenarCampo("xajax_listadoCheques", "8%", $pageNum, "montoCheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		$contFila++;
		
		$numeroCheque = $row['numeroCheque'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreEmpresa'])."</td>";
				$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaCheque']))."</td>";
				$htmlTb .= "<td align=\"right\">".$row['numeroDocumento']."</td>";
				$htmlTb .= "<td align=\"right\">".$row['numeroCheque']."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreBanco'])."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreCliente'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipoDocumento'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($row['montoCheque'],2,".",",")."</td>";
				$htmlTb .= "<td align=\"center\" title='Generar Nota Cargo'><img class=\"puntero\" src=\"../img/iconos/arrow_rotate_clockwise.png\" onClick=\"xajax_validarAperturaCaja(".valTpDato($numeroCheque, "text").",".$row['montoCheque'].",".$row['idCliente'].",".$row['idBanco'].")\"/></a></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr class=\"noprint\">";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoCheques(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarDevolucionCheque($numeroCheque,$montoCheque,$idCliente,$idBanco){
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse, "cj_devolucion_cheque_list", "insertar")){
		$objResponse->script("if (confirm(\"Seguro desea generar una nota de cargo por el cheque ".valTpDato($numeroCheque, "text")."?\")){
				xajax_guardarNotaCargo(".valTpDato($numeroCheque, "text").",".valTpDato($montoCheque, "double").",".valTpDato($idCliente, "int").",".valTpDato($idBanco, "int").")
			}");
	}
	
	return $objResponse;
}

function validarAperturaCaja($numeroCheque = "", $montoCheque = "" ,$idCliente = "", $idBanco = ""){
	$objResponse = new xajaxResponse();
	
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal."
	WHERE statusAperturaCaja <> 0
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja);
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		$objResponse->script("location.href='cj_cierre_caja.php'");
	} else {
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal."
		WHERE fechaAperturaCaja = %s
			AND statusAperturaCaja <> 0
			AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura);
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
			$objResponse->script("location.href='cj_apertura_caja.php'");
		}
	}
	
	if ($idCliente > 0) {
		$objResponse->loadCommands(validarDevolucionCheque($numeroCheque,$montoCheque,$idCliente,$idBanco));
	}
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"guardarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"imprimirDevolucionCheque");
$xajax->register(XAJAX_FUNCTION,"listadoCheques");
$xajax->register(XAJAX_FUNCTION,"validarDevolucionCheque");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
?>