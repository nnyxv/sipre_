<?php


function cerrarCaja($frmCerrar){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $nombreCajaPpal;
	global $idModuloPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
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
		$andEmpresaAPER = sprintf(" AND ape.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
			
		$andEmpresaPagoCorte = sprintf(" WHERE q.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$groupBy = sprintf(", q.id_empresa");
		
		$andEmpresaPagoFA = sprintf(" AND cxc_fact.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoND = sprintf(" AND cxc_nd.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoAN = sprintf(" AND cxc_ant.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoCH = sprintf(" AND cxc_ch.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoTB = sprintf(" AND cxc_tb.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$andEmpresaTE = sprintf(" AND id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresaAPER = "";
		
		$andEmpresaPagoCorte = "";
		$groupBy = "";
		
		$andEmpresaPagoFA = "";
		$andEmpresaPagoND = "";
		$andEmpresaPagoAN = "";
		$andEmpresaPagoCH = "";
		$andEmpresaPagoTB = "";
		
		$andEmpresaTE = "";
	}
	
	// COPIAR apertura EN cierre
	$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape WHERE ape.statusAperturaCaja = %s %s",
		valTpDato(1, "int"), // 0 = Cerrada, 1 = Abierta, 2 = Cerrada Parcialmente
		$andEmpresaAPER);
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAperturaCaja = mysql_num_rows($rsPago);
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	$idApertura = $rowAperturaCaja['id'];
	$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];
	
	// Se inserta la fecha de apertura en la fecha de cierre y se inserta la fecha real de cierre en campo: fechaEjecucionCierre (campo informatico para auditorias, revisiones, controles...)
	// Para que el listado del historico de cierres no se descuadre.
	$fechaCierre = $rowAperturaCaja['fechaAperturaCaja']; // FECHA DE APERTURA, PARA EVITAR DIFERENCIAS EN EL HISTORICO DE CIERRES DE CAJA
	$fechaEjecucionCierre = date("Y-m-d"); // FECHA REAL DEL CIERRE DE LA CAJA
	
	$sqlCopiarAperturaEnCierre = sprintf("INSERT INTO ".$cierreCajaPpal." (id, tipoCierre, fechaCierre, horaEjecucionCierre, fechaEjecucionCierre, cargaEfectivoCaja, saldoCaja, saldoEfectivo, saldoCheques, saldoDepositos, saldoTransferencia, saldoTarjetaCredito, saldoTarjetaDebito, saldoAnticipo, saldoNotaCredito, saldoRetencion, saldoOtro, id_usuario, id_empresa, observacion)
	VALUES(%s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idApertura, "int"),
		valTpDato(1, "int"),
		valTpDato($fechaCierre, "date"),
		valTpDato($fechaEjecucionCierre, "date"),
		valTpDato($rowAperturaCaja['cargaEfectivoCaja'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoCaja'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoEfectivo'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoCheques'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoDepositos'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoTransferencia'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoTarjetaCredito'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoTarjetaDebito'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoAnticipo'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoNotaCredito'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoRetencion'], "real_inglesa"),
		valTpDato($rowAperturaCaja['saldoOtro'], "real_inglesa"),
		valTpDato($idUsuario, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($frmCerrar['txtObservacionCierre'], "text"));
	$rsCopiarAperturaEnCierre = mysql_query($sqlCopiarAperturaEnCierre);
	if (!$rsCopiarAperturaEnCierre) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idCierre = mysql_insert_id();
	
	$queryFormaPago = sprintf("SELECT
		q.formaPago,
		q.tipoDoc,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = q.tipoDoc) AS nombre_tipo_documento,
		q.id_empresa,
		forma_pago.nombreFormaPago
	FROM (SELECT
			cxc_fact.id_empresa AS id_empresa,
			1  AS tipoDoc,
			cxc_pago.formaPago AS formaPago
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre IN (0,2)
			AND cxc_pago.fechaPago = %s
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		GROUP BY cxc_fact.id_empresa, cxc_pago.formaPago
		
		UNION
		
		SELECT
			cxc_fact.id_empresa AS id_empresa,
			1  AS tipoDoc,
			cxc_pago.formaPago AS formaPago
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre IN (0,2)
			AND cxc_pago.fechaPago = %s
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		GROUP BY cxc_fact.id_empresa, cxc_pago.formaPago
		
		UNION
		
		SELECT
			cxc_nd.id_empresa AS id_empresa,
			2  AS tipoDoc,
			cxc_pago.idFormaPago AS formaPago
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
		WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre IN (0,2)
			AND cxc_pago.fechaPago = %s
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		GROUP BY cxc_nd.id_empresa, cxc_pago.idFormaPago
		
		UNION
		
		SELECT
			cxc_ant.id_empresa AS id_empresa,
			4  AS tipoDoc,
			cxc_pago.id_forma_pago AS formaPago
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre in (0,2)
			AND cxc_pago.fechaPagoAnticipo = %s
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		GROUP BY cxc_ant.id_empresa, cxc_pago.id_forma_pago
		
		UNION
		
		SELECT
			cxc_ch.id_empresa AS id_empresa,
			5 AS tipoDoc,
			2 AS formaPago
		FROM cj_cc_cheque cxc_ch
		WHERE cxc_ch.idCaja IN (%s) AND cxc_ch.tomadoEnCierre in (0,2)
			AND cxc_ch.fecha_cheque = %s
			AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
		GROUP BY cxc_ch.id_empresa, 3
		
		UNION
		
		SELECT
			cxc_tb.id_empresa AS id_empresa,
			6 AS tipoDoc,
			4 AS formaPago
		FROM cj_cc_transferencia cxc_tb
		WHERE cxc_tb.idCaja IN (%s) AND cxc_tb.tomadoEnCierre in (0,2)
			AND cxc_tb.fecha_transferencia = %s
			AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
		GROUP BY cxc_tb.id_empresa, 3) AS q
		INNER JOIN formapagos forma_pago ON (q.formaPago = forma_pago.idFormaPago) ".$andEmpresaPagoCorte."
	GROUP BY q.formaPago ".$groupBy."
	ORDER BY q.formaPago",
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date")); //return $objResponse->alert($queryFormaPago);
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowFormaPago = mysql_fetch_array($rsFormaPago)){
		$nombreFormaPago = $rowFormaPago['nombreFormaPago'];
		$idFormaPago = $rowFormaPago['formaPago'];
		
		$contTC = 0;
		$contTD = 0;
		$arrayCuentaTarjetaCredito = array();
		$arrayCuentaTarjetaDebito = array();
		
		$queryPago = "SELECT 
			cxc_pago.idPago,
			'FACTURA' AS tipoDoc,
			cxc_fact.idDepartamentoOrigenFactura AS idDepartamento,
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.numeroFactura AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.tomadoEnCierre,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'an_pagos' AS tabla_pago,
			'idPago' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPago = '".$fechaApertura."'
			AND cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
			AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.formaPago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
			AND cxc_pago.tomadoEnComprobante = 1
			AND cxc_pago.tomadoEnCierre IN (0,1,2)
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
			AND cxc_pago.formaPago = ".$idFormaPago."
			".$andEmpresaPagoFA."
		
		UNION
		
		SELECT 
			cxc_pago.idPago,
			'FACTURA' AS tipoDoc,
			cxc_fact.idDepartamentoOrigenFactura AS idDepartamento,
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.numeroFactura AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.tomadoEnCierre,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'sa_iv_pagos' AS tabla_pago,
			'idPago' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPago = '".$fechaApertura."'
			AND cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
			AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.formaPago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
			AND cxc_pago.tomadoEnComprobante = 1
			AND cxc_pago.tomadoEnCierre IN (0,1,2)
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
			AND cxc_pago.formaPago = ".$idFormaPago."
			".$andEmpresaPagoFA."
		
		UNION
		
		SELECT 
			cxc_pago.id_det_nota_cargo AS idPago,
			'NOTA DEBITO' AS tipoDoc,
			cxc_nd.idDepartamentoOrigenNotaCargo AS idDepartamento,
			cxc_nd.idNotaCargo AS id_documento_pagado,
			cxc_nd.numeroNotaCargo AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.idFormaPago AS formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.monto_pago AS montoPagado,
			cxc_pago.tomadoEnCierre,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'cj_det_nota_cargo' AS tabla_pago,
			'id_det_nota_cargo' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago on (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_nd.idDepartamentoOrigenNotaCargo = recibo.id_departamento AND recibo.idTipoDeDocumento = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPago = '".$fechaApertura."'
			AND cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
			AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.idFormaPago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
			AND cxc_pago.tomadoEnComprobante = 1
			AND cxc_pago.tomadoEnCierre IN (0,1,2)
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
			AND cxc_pago.idFormaPago = ".$idFormaPago."
			".$andEmpresaPagoND."
			
		UNION
		
		SELECT 
			cxc_pago.idDetalleAnticipo AS idPago,
			CONCAT_WS(' ', 'ANTICIPO', IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS tipoDoc,
			cxc_ant.idDepartamento AS idDepartamento,
			cxc_ant.idAnticipo AS id_documento_pagado,
			cxc_ant.numeroAnticipo AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPagoAnticipo AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			cxc_pago.id_forma_pago AS formaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.id_concepto AS id_concepto,
			cxc_pago.numeroControlDetalleAnticipo AS numero_documento_pago,
			cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo AS montoPagado,
			cxc_pago.tomadoEnCierre,
			cxc_ant.estatus AS estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'cj_cc_detalleanticipo' AS tabla_pago,
			'idDetalleAnticipo' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago on (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion AND cxc_ant.idDepartamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'AN')
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
			AND cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
			AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
			AND (cxc_pago.id_forma_pago NOT IN (2,4)
				OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
				OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
			AND cxc_pago.tomadoEnCierre IN (0,1,2)
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
			AND cxc_pago.id_forma_pago = ".$idFormaPago."
			".$andEmpresaPagoAN."
		
		UNION
		
		SELECT 
			cxc_ch.id_cheque AS idPago,
			'CHEQUE' AS tipoDoc,
			cxc_ch.id_departamento AS idDepartamento,
			NULL AS id_documento_pagado,
			'-' AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_ch.fecha_cheque AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			2 AS formaPago,
			(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
			NULL AS id_concepto,
			cxc_ch.numero_cheque AS numero_documento_pago,
			cxc_ch.id_banco_cliente AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			1 AS bancoDestino,
			'-' AS nombre_banco_destino,
			'-' AS cuentaEmpresa,
			cxc_ch.idCaja,
			cxc_ch.total_pagado_cheque AS montoPagado,
			cxc_ch.tomadoEnCierre,
			cxc_ch.estatus AS estatus,
			cxc_ch.estatus AS estatus_pago,
			DATE(cxc_ch.fecha_anulado) AS fecha_anulado,
			'cj_cc_cheque' AS tabla_pago,
			'id_cheque' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_cheque cxc_ch
			INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
			INNER JOIN bancos banco_origen on (cxc_ch.id_banco_cliente = banco_origen.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ch.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
			AND cxc_ch.idCaja IN (".valTpDato($idCajaPpal, "campo").")
			AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
			AND cxc_ch.tomadoEnComprobante = 1
			AND cxc_ch.tomadoEnCierre IN (0,1,2)
			AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
			AND 2 = ".$idFormaPago."
			".$andEmpresaPagoCH."
		
		UNION
		
		SELECT 
			cxc_tb.id_transferencia AS idPago,
			'TRANSFERENCIA' AS tipoDoc,
			cxc_tb.id_departamento AS idDepartamento,
			NULL AS id_documento_pagado,
			'-' AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_tb.fecha_transferencia AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			4 AS formaPago,
			(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
			NULL AS id_concepto,
			cxc_tb.numero_transferencia AS numero_documento_pago,
			cxc_tb.id_banco_cliente AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_tb.id_banco_compania AS bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_tb.cuenta_compania AS cuentaEmpresa,
			cxc_tb.idCaja,
			cxc_tb.total_pagado_transferencia AS montoPagado,
			cxc_tb.tomadoEnCierre,
			cxc_tb.estatus AS estatus,
			cxc_tb.estatus AS estatus_pago,
			DATE(cxc_tb.fecha_anulado) AS fecha_anulado,
			'cj_cc_transferencia' AS tabla_pago,
			'id_transferencia' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_transferencia cxc_tb
			INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
			INNER JOIN bancos banco_origen on (cxc_tb.id_banco_cliente = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_tb.id_banco_compania = banco_destino.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_tb.id_transferencia = recibo.idDocumento AND cxc_tb.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'TB')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_tb.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
			AND cxc_tb.idCaja IN (".valTpDato($idCajaPpal, "campo").")
			AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
			AND cxc_tb.tomadoEnComprobante = 1
			AND cxc_tb.tomadoEnCierre IN (0,1,2)
			AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
			AND 4 = ".$idFormaPago."
			".$andEmpresaPagoTB.";";
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPago = mysql_num_rows($rsPago);
		while ($rowPago = mysql_fetch_array($rsPago)){
			if (in_array($rowPago['tabla_pago'],array("an_pagos","sa_iv_pagos"))){
				$tablaDepositoDet = "an_det_pagos_deposito_factura";
			} else if ($rowPago['tabla_pago'] == "cj_det_nota_cargo"){
				$tablaDepositoDet = "cj_det_pagos_deposito_nota_cargo";
			} else if ($rowPago['tabla_pago'] == "cj_cc_detalleanticipo"){
				$tablaDepositoDet = "cj_cc_det_pagos_deposito_anticipos";
			} else if ($rowPago['tabla_pago'] == "cj_cc_cheque"){
				$tablaDepositoDet = "";
			} else if ($rowPago['tabla_pago'] == "cj_cc_transferencia"){
				$tablaDepositoDet = "";
			}
			$idTabla = $rowPago['campo_id_pago'];
			
			if (in_array($rowPago['tomadoEnCierre'], array(0,2))) { // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				if (in_array($idFormaPago, array(1,2))) { // 1 = Efectivo, 2 = Cheques
					$updateSQL = sprintf("UPDATE %s SET tomadoEnCierre = 1 WHERE %s = %s",
						$rowPago['tabla_pago'], $idTabla, $rowPago['idPago']);
				} else {
					$updateSQL = sprintf("UPDATE %s SET tomadoEnCierre = 2 WHERE %s = %s",
						$rowPago['tabla_pago'], $idTabla, $rowPago['idPago']);
				}
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			} 
			
			$updateSQL = sprintf("UPDATE %s SET idCierre = %s WHERE %s = %s",
				$rowPago['tabla_pago'], $idCierre, $idTabla, $rowPago['idPago']);
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			if ($rowPago['estatus_pago'] == 1) { // Null = Anulado, 1 = Activo, 2 = Pendiente
				if ($idFormaPago == 3) { // 3 = Deposito
					$montoTotalEfectivoDeposito = 0;
					$montoTotalChequeDeposito = 0;
					
					// CONSULTA MONTO TOTAL EFECTIVO Y CHEQUE
					$sqlSelectMontos = sprintf("SELECT SUM(monto) AS monto, idFormaPago FROM %s WHERE %s = %s GROUP BY idFormaPago",
						$tablaDepositoDet,
						$idTabla,
						valTpDato($rowPago['idPago'], "int"));
					$rsSelectMontos = mysql_query($sqlSelectMontos);
					if (!$rsSelectMontos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					while ($rowSelectMontos = mysql_fetch_array($rsSelectMontos)){
						if ($rowSelectMontos['idFormaPago'] == 1) {
							$montoTotalEfectivoDeposito = $rowSelectMontos['monto'];
						} else if ($rowSelectMontos['idFormaPago'] == 2) {
							$montoTotalChequeDeposito = $rowSelectMontos['monto'];
						}
					}
					
					// CONSULTA PARA EL ID DE LA CUENTA
					$queryCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
						valTpDato($rowPago['cuentaEmpresa'], "text"));
					$rsCuenta = mysql_query($queryCuenta);
					if (!$rsCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowCuenta = mysql_fetch_array($rsCuenta);
					
					// CONSULTA CORRELATIVO NUMERO DE FOLIO
					$sqlSelectFolioTesoreriaDeposito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 2");
					$rsSelectFolioTesoreriaDeposito = mysql_query($sqlSelectFolioTesoreriaDeposito);
					if (!$rsSelectFolioTesoreriaDeposito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowSelectFolioTesoreriaDeposito = mysql_fetch_array($rsSelectFolioTesoreriaDeposito);
					
					$folioDeposito = $rowSelectFolioTesoreriaDeposito['numero_actual'];
					
					// AUMENTAR EL CORRELATIVO DEL FOLIO
					$sqlUpdateFolioTesoreriaDeposito = "UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 2";
					$rsUpdateFolioTesoreriaDeposito = mysql_query($sqlUpdateFolioTesoreriaDeposito);
					if (!$rsUpdateFolioTesoreriaDeposito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					$observacionDepositos = trim(sprintf('INGRESO %s DIA %s DEPOSITO CLIENTE (%s)',
						$nombreCajaPpal,
						date(spanDateFormat, strtotime($fechaApertura)),
						$rowPago['numero_documento_pago']));
					
					// INSERTAR EL DEPOSITO EN TESORERIA
					$sqlInsertDepositoTesoreria = sprintf("INSERT INTO te_depositos (id_numero_cuenta, fecha_registro, fecha_aplicacion, numero_deposito_banco, estado_documento, origen, id_usuario, monto_total_deposito, id_empresa, desincorporado, monto_efectivo, monto_cheques_total, observacion, folio_deposito)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($rowCuenta['idCuentas'], "int"),
						valTpDato($fechaApertura, "date"),
						valTpDato($fechaApertura, "date"),
						valTpDato($rowPago['numero_documento_pago'], "text"),
						valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
						valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
						valTpDato($idUsuario, "int"),
						valTpDato($rowPago['montoPagado'], "double"),
						valTpDato($idEmpresa, "int"),
						valTpDato(1, "int"), // 0 = Desincorporado, 1 = Activo
						valTpDato($montoTotalEfectivoDeposito, "int"),
						valTpDato($montoTotalChequeDeposito, "int"),
						valTpDato($observacionDepositos, "text"),
						valTpDato($folioDeposito, "int"));
					$rsInsertDepositoTesoreria = mysql_query($sqlInsertDepositoTesoreria);
					if (!$rsInsertDepositoTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idDeposito = mysql_insert_id();
					
					// INSERTAR DETALLES DEPOSITO
					$sqlSelectDetallesDepositoPago = sprintf("SELECT idBanco, numero_cuenta, numero_cheque, monto FROM %s WHERE %s = %s AND idFormaPago = 2",
						$tablaDepositoDet,
						$idTabla,
						valTpDato($rowPago['idPago'], "int"));
					$rsSelectDetallesDepositoPago = mysql_query($sqlSelectDetallesDepositoPago);
					if (!$rsSelectDetallesDepositoPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					while ($rowSelectDetallesDepositoPago = mysql_fetch_array($rsSelectDetallesDepositoPago)){
						$sqlInsertDetalleDeposito = sprintf("INSERT INTO te_deposito_detalle (id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto)
						VALUES (%s, %s, %s, %s, %s)",
							valTpDato($idDeposito, "int"),
							valTpDato($rowSelectDetallesDepositoPago['idBanco'], "int"),
							valTpDato($rowSelectDetallesDepositoPago['numero_cuenta'], "text"),
							valTpDato($rowSelectDetallesDepositoPago['numero_cheque'], "text"),
							valTpDato($rowSelectDetallesDepositoPago['monto'], "double"));
						$rsInsertDetalleDeposito = mysql_query($sqlInsertDetalleDeposito);
						if (!$rsInsertDetalleDeposito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
					
					// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA
					$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato("DP", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
						valTpDato($idDeposito, "int"),
						valTpDato("NOW()", "campo"),
						valTpDato($rowCuenta['idCuentas'], "int"),
						valTpDato($idEmpresa, "int"),
						valTpDato($rowPago['montoPagado'], "double"),
						valTpDato(1, "int"), // 1 = Suma, 0 = Resta
						valTpDato($rowPago['numero_documento_pago'], "text"),
						valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
						valTpDato($observacionDepositos, "text"),
						valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
						valTpDato(0, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// AFECTAR EL SALDO EN CUENTA
					$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s;",
						valTpDato($rowPago['montoPagado'], "double"),
						valTpDato($rowCuenta['idCuentas'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
				} else if ($idFormaPago == 4) { // 4 = Transferencia Bancaria
					// CONSULTA PARA EL ID DE LA CUENTA
					$queryCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
						valTpDato($rowPago['cuentaEmpresa'], "text"));
					$rsCuenta = mysql_query($queryCuenta);
					if (!$rsCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowCuenta = mysql_fetch_array($rsCuenta);
					
					// CONSULTA CORRELATIVO NUMERO DE FOLIO
					$queryNumeracionTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 3");
					$rsNumeracionTesoreria = mysql_query($queryNumeracionTesoreria);
					if (!$rsNumeracionTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowNumeracionTesoreria = mysql_fetch_array($rsNumeracionTesoreria);
					
					$folioNotaCredito = $rowNumeracionTesoreria['numero_actual'];
					
					// AUMENTAR EL CORRELATIVO DEL FOLIO
					$sqlUpdateFolioTesoreriaNotaCredito = "UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 3";
					$rsUpdateFolioTesoreriaNotaCredito = mysql_query($sqlUpdateFolioTesoreriaNotaCredito );
					if (!$rsUpdateFolioTesoreriaNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					$observacionTransferencia = trim(sprintf('INGRESO %s DIA %s TRANSFERENCIA (%s)',
						$nombreCajaPpal,
						date(spanDateFormat, strtotime($fechaApertura)),
						$rowPago['numero_documento_pago']));
					
					// INSERTAR LA NOTA DE CREDITO EN TESORERIA
					$sqlInsertNotaCreditoTesoreria = sprintf("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_tesoreria, id_beneficiario_proveedor, observaciones, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_credito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($rowCuenta['idCuentas'], "int"),
						valTpDato($fechaApertura, "date"),
						valTpDato($fechaApertura, "date"),
						valTpDato($folioNotaCredito, "int"),
						valTpDato(0, "int"),
						valTpDato($observacionTransferencia, "text"),
						valTpDato(0, "int"),
						valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
						valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)						
						valTpDato($idUsuario, "int"),
						valTpDato($rowPago['montoPagado'], "double"),
						valTpDato(" ", "text"),
						valTpDato($idEmpresa, "int"),
						valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
						valTpDato($rowPago['numero_documento_pago'], "text"),
						valTpDato(4, "int"), // 1 = Normal, 2 = TD, 3 = TC, 4 = TR
						valTpDato(289, "int")); // 289 = CIERRE DE CAJA (Motivo Fijo)
					$rsInsertNotaCreditoTesoreria = mysql_query($sqlInsertNotaCreditoTesoreria);
					if (!$rsInsertNotaCreditoTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idNotaCredito = mysql_insert_id();
					
					$sqlInsertDetalleMotivo = sprintf("INSERT INTO te_nota_credito_detalle_motivo (id_nota_credito, id_motivo, cantidad, precio_unitario)
					VALUES (%s, %s, %s, %s)",
						valTpDato($idNotaCredito, "int"),
						valTpDato(289, "int"), // 289 = CIERRE DE CAJA (Motivo Fijo)
						valTpDato(1, "int"),
						valTpDato($rowPago['montoPagado'], "real_inglesa"));
					$rsInsertDetalleMotivo = mysql_query($sqlInsertDetalleMotivo);
					if (!$rsInsertDetalleMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA
					$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato("NC", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
						valTpDato($idNotaCredito, "int"),
						valTpDato("NOW()", "campo"),
						valTpDato($rowCuenta['idCuentas'], "int"),
						valTpDato($idEmpresa, "int"),
						valTpDato($rowPago['montoPagado'], "double"),
						valTpDato(1, "int"), // 1 = Suma, 0 = Resta
						valTpDato($rowPago['numero_documento_pago'], "text"),
						valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
						valTpDato($observacionTransferencia, "text"),
						valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
						valTpDato(0, "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// AFECTAR EL SALDO EN CUENTA
					$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s;",
						valTpDato($rowPago['montoPagado'], "double"),
						valTpDato($rowCuenta['idCuentas'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
				} else if ($idFormaPago == 5) { // 5 = Tarjeta de Credito
					//CONSULTA PARA EL ID DE LA CUENTA
					$queryCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
						valTpDato($rowPago['cuentaEmpresa'], "text"));
					$rsCuenta = mysql_query($queryCuenta);
					if (!$rsCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowCuenta = mysql_fetch_array($rsCuenta);
					
					$pos = array_search($rowCuenta['idCuentas'],$arrayCuentaTarjetaCredito);
					if (in_array($rowCuenta['idCuentas'],$arrayCuentaTarjetaCredito)){
						$arrayMontoTarjetaCredito[$pos] += $rowPago['montoPagado'];
					} else {
						$arrayCuentaTarjetaCredito[$contTC] = $rowCuenta['idCuentas'];
						$arrayMontoTarjetaCredito[$contTC] = $rowPago['montoPagado'];
						$contTC++;
					}
				} else if ($idFormaPago == 6) { // 6 = Tarjeta de Debito
					// CONSULTA PARA EL ID DE LA CUENTA
					$queryCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
						valTpDato($rowPago['cuentaEmpresa'], "text"));
					$rsCuenta = mysql_query($queryCuenta);
					if (!$rsCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowCuenta = mysql_fetch_array($rsCuenta);
					
					$pos = array_search($rowCuenta['idCuentas'],$arrayCuentaTarjetaDebito);
					if (in_array($rowCuenta['idCuentas'],$arrayCuentaTarjetaDebito)){
						$arrayMontoTarjetaDebito[$pos] += $rowPago['montoPagado'];
					} else {
						$arrayCuentaTarjetaDebito[$contTD] = $rowCuenta['idCuentas'];
						$arrayMontoTarjetaDebito[$contTD] = $rowPago['montoPagado'];
						$contTD++;
					}
				}
			}
		}
		
		if (isset($arrayCuentaTarjetaCredito)) {
			foreach($arrayCuentaTarjetaCredito as $indiceTC => $valorTC) {
				$idCuenta = $valorTC;
				
				// CONSULTA EL PORCENTAJE DE RETENCION DEL PUNTO POR TC
				$queryRetencionPunto = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
				WHERE id_cuenta = %s
					AND id_tipo_tarjeta NOT IN (6)
				GROUP BY id_cuenta",
					valTpDato($idCuenta, "int"));
				$rsRetencionPunto = mysql_query($queryRetencionPunto);
				if (!$rsRetencionPunto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowRetencionPunto = mysql_fetch_array($rsRetencionPunto);
				
				$porcRetencionComision = $rowRetencionPunto['porcentaje_comision'];
				$porcRetencionISLR = $rowRetencionPunto['porcentaje_islr'];
				
				
				// CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA CREDITO
				$queryNumeracionTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 3;");
				$rsNumeracionTesoreria = mysql_query($queryNumeracionTesoreria);
				if (!$rsNumeracionTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowNumeracionTesoreria = mysql_fetch_array($rsNumeracionTesoreria);
				
				$idNumeraciones = $rowNumeracionTesoreria['id_folios'];
				$folioNotaCredito = $rowNumeracionTesoreria['numero_actual'];
				
				// AUMENTAR EL CORRELATIVO DEL FOLIO NOTA CREDITO
				$updateSQL = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = %s;",
					valTpDato($idNumeraciones, "int"));
				$Result1 = mysql_query($updateSQL );
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowIva = mysql_fetch_assoc($rsIva);
				
				$montoOriginalTC = $arrayMontoTarjetaCredito[$indiceTC];
				$montoComisionPunto = $montoOriginalTC * $porcRetencionComision / 100;
				
				$arrayNotaDebito = NULL;
				if (in_array(idArrayPais,array(1,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$montoRetencionISLR = ($montoOriginalTC / (1 + ($rowIva['iva'] / 100))) * ($porcRetencionISLR / 100);
					
					// A TESORERIA VA EL MONTO MENOS LA COMISION Y EL ISLR
					$montoNotaCreditoTC = $montoOriginalTC - $montoComisionPunto - $montoRetencionISLR;
					
				} else if (in_array(idArrayPais,array(2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$montoRetencionISLR = $montoComisionPunto * ($porcRetencionISLR / 100);
					
					$arrayNotaDebito[] = array(
						"id_motivo" => 16, // 16 = COMISIONES BANCARIAS (Motivo Fijo)
						"monto_total" => $montoComisionPunto);
					
					$arrayNotaDebito[] = array(
						"id_motivo" => 16, // 16 = COMISIONES BANCARIAS (Motivo Fijo)
						"monto_total" => $montoRetencionISLR);
					
					// A TESORERIA VA EL MONTO COMPLETO SIN RESTAR LA COMISION Y EL ISLR
					$montoNotaCreditoTC = $montoOriginalTC;
					
					if (isset($arrayNotaDebito)) {
						foreach ($arrayNotaDebito as $indice => $valor) {
							$idMotivoNotaDebitoTC = $arrayNotaDebito[$indice]['id_motivo'];
							$montoNotaDebitoTC = $arrayNotaDebito[$indice]['monto_total'];
							
							if ($montoNotaDebitoTC > 0) {
								// CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA DEBITO
								$queryNumeracionTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 1;");
								$rsNumeracionTesoreria = mysql_query($queryNumeracionTesoreria);
								if (!$rsNumeracionTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								$rowNumeracionTesoreria = mysql_fetch_array($rsNumeracionTesoreria);
								
								$idNumeraciones = $rowNumeracionTesoreria['id_folios'];
								$folioNotaDebito = $rowNumeracionTesoreria['numero_actual'];
								
								// AUMENTAR EL CORRELATIVO DEL FOLIO NOTA DEBITO
								$updateSQL = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = %s;",
									valTpDato($idNumeraciones, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								
								$observacionTC = trim(sprintf("EGRESO %s DIA %s COMISION BANCARIA POR TARJETA DE CREDITO",
									$nombreCajaPpal,
									date(spanDateFormat, strtotime($fechaApertura))));
								
								// INSERTAR LA NOTA DE DEBITO EN TESORERIA
								$sqlInsertNotaDebitoTesoreria = sprintf("INSERT INTO te_nota_debito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_debito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_debito, id_motivo)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
									valTpDato($idCuenta, "int"),
									valTpDato($fechaApertura, "date"),
									valTpDato($folioNotaDebito, "int"),
									valTpDato(0, "int"),
									valTpDato($observacionTC, "text"),
									valTpDato($fechaApertura, "date"),
									valTpDato(0, "int"),
									valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
									valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
									valTpDato($_SESSION['idUsuarioSysGts'], "int"),
									valTpDato($montoNotaDebitoTC, "double"),
									valTpDato(" ", "text"), // 0 = Beneficiario, 1 = Proveedor
									valTpDato($idEmpresa, "int"),
									valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
									valTpDato(0, "int"),
									valTpDato($idMotivoNotaDebitoTC, "int")); // (Motivo Fijo)
								$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
								if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								$idNotaDebito = mysql_insert_id();
								
								$sqlInsertDetalleMotivo = sprintf("INSERT INTO te_nota_debito_detalle_motivo (id_nota_debito, id_motivo, cantidad, precio_unitario)
								VALUES (%s, %s, %s, %s)",
									valTpDato($idNotaDebito, "int"),
									valTpDato($idMotivoNotaDebitoTC, "int"), // (Motivo Fijo)
									valTpDato(1, "int"),
									valTpDato($montoNotaDebitoTC, "real_inglesa"));
								$rsInsertDetalleMotivo = mysql_query($sqlInsertDetalleMotivo);
								if (!$rsInsertDetalleMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								
								// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA DEBITO)
								$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
									valTpDato("ND", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
									valTpDato($idNotaDebito, "int"),
									valTpDato("NOW()", "campo"),
									valTpDato($idCuenta, "int"),
									valTpDato($idEmpresa, "int"),
									valTpDato($montoNotaDebitoTC, "double"),
									valTpDato(0, "int"), // 1 = Suma, 0 = Resta
									valTpDato(0, "int"),
									valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
									valTpDato($observacionTC, "text"),
									valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
									valTpDato(0, "int"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							
								// AFECTAR EL SALDO EN CUENTA
								$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem - %s WHERE idCuentas = %s;",
									valTpDato($montoNotaDebitoTC, "double"),
									valTpDato($idCuenta, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							}
						}
					}
				}
				
				$observacionTC = trim(sprintf("INGRESO %s DIA %s POR TARJETA DE CREDITO",
					$nombreCajaPpal,
					date(spanDateFormat, strtotime($fechaApertura))));
				
				// INSERTAR LA NOTA DE CREDITO EN TESORERIA
				$sqlInsertNotaCreditoTesoreria = sprintf("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_tesoreria, id_beneficiario_proveedor, observaciones, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_credito, monto_original_nota_credito, porcentaje_comision, porcentaje_islr, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idCuenta, "int"),
					valTpDato($fechaApertura, "date"),
					valTpDato($fechaApertura, "date"),
					valTpDato($folioNotaCredito, "int"),
					valTpDato(0, "int"),
					valTpDato($observacionTC, "text"),
					valTpDato(0, "int"),
					valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
					valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
					valTpDato($idUsuario, "int"),
					valTpDato($montoNotaCreditoTC, "double"),
					valTpDato($montoOriginalTC, "double"),
					valTpDato($porcRetencionComision, "double"),
					valTpDato($porcRetencionISLR, "double"),
					valTpDato(" ", "text"), // 0 = Beneficiario, 1 = Proveedor
					valTpDato($idEmpresa, "int"),
					valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
					valTpDato(0, "int"),
					valTpDato(3, "int"), // 1 = Normal, 2 = TD, 3 = TC, 4 = TR
					valTpDato(289, "int")); // 289 = PRESTAMOS RECIBIDOS (Motivo Fijo)
				$rsInsertNotaCreditoTesoreria = mysql_query($sqlInsertNotaCreditoTesoreria);
				if (!$rsInsertNotaCreditoTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idNotaCredito = mysql_insert_id();
				
				$sqlInsertDetalleMotivo = sprintf("INSERT INTO te_nota_credito_detalle_motivo (id_nota_credito, id_motivo, cantidad, precio_unitario)
				VALUES (%s, %s, %s, %s)",
					valTpDato($idNotaCredito, "int"),
					valTpDato(289, "int"), // 289 = CIERRE DE CAJA (Motivo Fijo)
					valTpDato(1, "int"),
					valTpDato($montoNotaCreditoTC, "real_inglesa"));
				$rsInsertDetalleMotivo = mysql_query($sqlInsertDetalleMotivo);
				if (!$rsInsertDetalleMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA CREDITO)
				$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato("NC", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
					valTpDato($idNotaCredito, "int"),
					valTpDato("NOW()", "campo"),
					valTpDato($idCuenta, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($montoNotaCreditoTC, "double"),
					valTpDato(1, "int"), // 1 = Suma, 0 = Resta
					valTpDato(0, "int"),
					valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
					valTpDato($observacionTC, "text"),
					valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
					valTpDato(0, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// AFECTAR EL SALDO EN CUENTA
				$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s;",
					valTpDato($montoNotaCreditoTC, "double"),
					valTpDato($idCuenta, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		
		if (isset($arrayCuentaTarjetaDebito)){
			foreach($arrayCuentaTarjetaDebito as $indiceTD => $valorTD){
				$idCuenta = $valorTD;
				
				// CONSULTA EL PORCENTAJE DE RETENCION DEL PUNTO POR TD
				$queryRetencionPunto = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
				WHERE id_cuenta = %s
					AND id_tipo_tarjeta IN (6)
				GROUP BY id_cuenta",
					valTpDato($idCuenta, "int"));
				$rsRetencionPunto = mysql_query($queryRetencionPunto);
				if (!$rsRetencionPunto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowRetencionPunto = mysql_fetch_array($rsRetencionPunto);
				
				$porcRetencionComision = $rowRetencionPunto['porcentaje_comision'];
				$porcRetencionISLR = $rowRetencionPunto['porcentaje_islr'];
				
				
				// CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA CREDITO
				$queryNumeracionTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 3;");
				$rsNumeracionTesoreria = mysql_query($queryNumeracionTesoreria);
				if (!$rsNumeracionTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowNumeracionTesoreria = mysql_fetch_array($rsNumeracionTesoreria);
				
				$idNumeraciones = $rowNumeracionTesoreria['id_folios'];
				$folioNotaCredito = $rowNumeracionTesoreria['numero_actual'];
				
				// AUMENTAR EL CORRELATIVO DEL FOLIO NOTA CREDITO
				$updateSQL = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = %s;",
					valTpDato($idNumeraciones, "int"));
				$Result1 = mysql_query($updateSQL );
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				$montoOriginalTD = $arrayMontoTarjetaDebito[$indiceTD];
				$montoComisionPunto = $montoOriginalTD * $porcRetencionComision / 100;
				
				$arrayNotaDebito = NULL;
				if (in_array(idArrayPais,array(1,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					// A TESORERIA VA EL MONTO MENOS LA COMISION Y EL ISLR
					$montoNotaCreditoTD = $montoOriginalTD - $montoComisionPunto;
					
				} else if (in_array(idArrayPais,array(2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$montoRetencionISLR = $montoComisionPunto * ($porcRetencionISLR / 100);
					
					$arrayNotaDebito[] = array(
						"id_motivo" => 16, // 16 = COMISIONES BANCARIAS (Motivo Fijo)
						"monto_total" => $montoComisionPunto);
					
					$arrayNotaDebito[] = array(
						"id_motivo" => 16, // 16 = COMISIONES BANCARIAS (Motivo Fijo)
						"monto_total" => $montoRetencionISLR);
					
					// A TESORERIA VA EL MONTO COMPLETO SIN RESTAR LA COMISION Y EL ISLR
					$montoNotaCreditoTD = $montoOriginalTD;
					
					if (isset($arrayNotaDebito)) {
						foreach ($arrayNotaDebito as $indice => $valor) {
							$idMotivoNotaDebitoTD = $arrayNotaDebito[$indice]['id_motivo'];
							$montoNotaDebitoTD = $arrayNotaDebito[$indice]['monto_total'];
							
							if ($montoNotaDebitoTD > 0) {
								// CONSULTA CORRELATIVO NUMERO DE FOLIO NOTA DEBITO
								$queryNumeracionTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 1;");
								$rsNumeracionTesoreria = mysql_query($queryNumeracionTesoreria);
								if (!$rsNumeracionTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								$rowNumeracionTesoreria = mysql_fetch_array($rsNumeracionTesoreria);
								
								$idNumeraciones = $rowNumeracionTesoreria['id_folios'];
								$folioNotaDebito = $rowNumeracionTesoreria['numero_actual'];
								
								// AUMENTAR EL CORRELATIVO DEL FOLIO NOTA DEBITO
								$updateSQL = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = %s;",
									valTpDato($idNumeraciones, "int"));
								$Result1 = mysql_query($updateSQL );
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								
								$observacionTD = trim(sprintf("EGRESO %s DIA %s COMISION BANCARIA POR TARJETA DE DEBITO",
									$nombreCajaPpal,
									date(spanDateFormat, strtotime($fechaApertura))));
								
								// INSERTAR LA NOTA DE DEBITO EN TESORERIA
								$sqlInsertNotaDebitoTesoreria = sprintf("INSERT INTO te_nota_debito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_debito, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_debito, id_motivo)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
									valTpDato($idCuenta, "int"),
									valTpDato($fechaApertura, "date"),
									valTpDato($folioNotaDebito, "int"),
									valTpDato(0, "int"),
									valTpDato($observacionTD, "text"),
									valTpDato($fechaApertura, "date"),
									valTpDato(0, "int"),
									valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
									valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
									valTpDato($idUsuario, "int"),
									valTpDato($montoNotaDebitoTD, "double"),
									valTpDato(" ", "text"), // 0 = Beneficiario, 1 = Proveedor
									valTpDato($idEmpresa, "int"),
									valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
									valTpDato(0, "int"),
									valTpDato($idMotivoNotaDebitoTD, "int")); // (Motivo Fijo)
								$rsInsertNotaDebitoTesoreria = mysql_query($sqlInsertNotaDebitoTesoreria);
								if (!$rsInsertNotaDebitoTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								$idNotaDebito = mysql_insert_id();
								
								$sqlInsertDetalleMotivo = sprintf("INSERT INTO te_nota_debito_detalle_motivo (id_nota_debito, id_motivo, cantidad, precio_unitario)
								VALUES (%s, %s, %s, %s)",
									valTpDato($idNotaDebito, "int"),
									valTpDato($idMotivoNotaDebitoTD, "int"), // (Motivo Fijo)
									valTpDato(1, "int"),
									valTpDato($montoNotaDebitoTD, "real_inglesa"));
								$rsInsertDetalleMotivo = mysql_query($sqlInsertDetalleMotivo);
								if (!$rsInsertDetalleMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								
								// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA DEBITO)
								$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
									valTpDato("ND", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
									valTpDato($idNotaDebito, "int"),
									valTpDato("NOW()", "campo"),
									valTpDato($idCuenta, "int"),
									valTpDato($idEmpresa, "int"),
									valTpDato($montoNotaDebitoTD, "double"),
									valTpDato(0, "int"), // 1 = Suma, 0 = Resta
									valTpDato(0, "int"),
									valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
									valTpDato($observacionTD, "text"),
									valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
									valTpDato(0, "int"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							
								// AFECTAR EL SALDO EN CUENTA
								$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem - %s WHERE idCuentas = %s;",
									valTpDato($montoNotaDebitoTD, "double"),
									valTpDato($idCuenta, "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							}
						}
					}
				}
				
				$observacionTD = trim(sprintf("INGRESO %s DIA %s POR TARJETA DE DEBITO",
					$nombreCajaPpal,
					date(spanDateFormat, strtotime($fechaApertura))));
				
				// INSERTAR LA NOTA DE CREDITO EN TESORERIA
				$sqlInsertNotaCreditoTesoreria = sprintf("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_tesoreria, id_beneficiario_proveedor, observaciones, folio_estado_cuenta_banco, estado_documento, origen, id_usuario, monto_nota_credito, monto_original_nota_credito, porcentaje_comision, control_beneficiario_proveedor, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idCuenta, "int"),
					valTpDato($fechaApertura, "date"),
					valTpDato($fechaApertura, "date"),
					valTpDato($folioNotaCredito, "int"),
					valTpDato(0, "int"),
					valTpDato($observacionTD, "text"),
					valTpDato(0, "int"),
					valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
					valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
					valTpDato($idUsuario, "int"),
					valTpDato($montoNotaCreditoTD, "double"),
					valTpDato($montoOriginalTD, "double"),
					valTpDato($porcRetencionComision, "double"),
					valTpDato(" ", "text"),
					valTpDato($idEmpresa, "int"),
					valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
					valTpDato(0, "int"),
					valTpDato(2, "int"), // 1 = Normal, 2 = TD, 3 = TC, 4 = TR
					valTpDato(289, "int")); // 289 = PRESTAMOS RECIBIDOS (Motivo Fijo)
				$rsInsertNotaCreditoTesoreria = mysql_query($sqlInsertNotaCreditoTesoreria);
				if (!$rsInsertNotaCreditoTesoreria) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idNotaCredito = mysql_insert_id();
				
				$sqlInsertDetalleMotivo = sprintf("INSERT INTO te_nota_credito_detalle_motivo (id_nota_credito, id_motivo, cantidad, precio_unitario)
				VALUES (%s, %s, %s, %s)",
					valTpDato($idNotaCredito, "int"),
					valTpDato(289, "int"), // 289 = CIERRE DE CAJA (Motivo Fijo)
					valTpDato(1, "int"),
					valTpDato($montoNotaCreditoTD, "real_inglesa"));
				$rsInsertDetalleMotivo = mysql_query($sqlInsertDetalleMotivo);
				if (!$rsInsertDetalleMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA (NOTA CREDITO)
				$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato("NC", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
					valTpDato($idNotaCredito, "int"),
					valTpDato("NOW()", "campo"),
					valTpDato($idCuenta, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato($montoNotaCreditoTD, "double"),
					valTpDato(1, "int"), // 1 = Suma, 0 = Resta
					valTpDato(0, "int"),
					valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
					valTpDato($observacionTD, "text"),
					valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
					valTpDato(0, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// AFECTAR EL SALDO EN CUENTA
				$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s;",
					valTpDato($montoNotaCreditoTD, "double"),
					valTpDato($idCuenta, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
	}
	
	// LE ASIGNA EL ID DEL CIERRE A LOS PAGOS RESTANTES
	$updateSQL = sprintf("UPDATE an_pagos SET
		idCierre = %s
	WHERE fechaPago = %s
		AND idCaja = %s
		AND id_apertura = %s
		AND (idCierre IS NULL OR idCierre = 0);",
		valTpDato($idCierre, "int"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// LE ASIGNA EL ID DEL CIERRE A LOS PAGOS RESTANTES
	$updateSQL = sprintf("UPDATE sa_iv_pagos SET
		idCierre = %s
	WHERE fechaPago = %s
		AND idCaja = %s
		AND id_apertura = %s
		AND (idCierre IS NULL OR idCierre = 0);",
		valTpDato($idCierre, "int"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// LE ASIGNA EL ID DEL CIERRE A LOS PAGOS RESTANTES
	$updateSQL = sprintf("UPDATE cj_det_nota_cargo SET
		idCierre = %s
	WHERE fechaPago = %s
		AND idCaja = %s
		AND id_apertura = %s
		AND (idCierre IS NULL OR idCierre = 0);",
		valTpDato($idCierre, "int"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// LE ASIGNA EL ID DEL CIERRE A LOS PAGOS RESTANTES
	$updateSQL = sprintf("UPDATE cj_cc_detalleanticipo SET
		idCierre = %s
	WHERE fechaPagoAnticipo = %s
		AND idCaja = %s
		AND id_apertura = %s
		AND (idCierre IS NULL OR idCierre = 0);",
		valTpDato($idCierre, "int"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// LE ASIGNA EL ID DEL CIERRE A LOS PAGOS RESTANTES
	$updateSQL = sprintf("UPDATE cj_cc_cheque SET
		idCierre = %s
	WHERE fecha_cheque = %s
		AND idCaja = %s
		AND id_apertura = %s
		AND (idCierre IS NULL OR idCierre = 0);",
		valTpDato($idCierre, "int"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// LE ASIGNA EL ID DEL CIERRE A LOS PAGOS RESTANTES
	$updateSQL = sprintf("UPDATE cj_cc_transferencia SET
		idCierre = %s
	WHERE fecha_transferencia = %s
		AND idCaja = %s
		AND id_apertura = %s
		AND (idCierre IS NULL OR idCierre = 0);",
		valTpDato($idCierre, "int"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idApertura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	
	// ACTUALIZA LA FECHA DE EJECUCION DEL CIERRE DE LA CAJA
	$sql = sprintf("UPDATE ".$cierreCajaPpal." SET fechaEjecucionCierre = %s,
		horaEjecucionCierre = NOW()
	WHERE id = %s;",
		valTpDato($fechaEjecucionCierre, "date"),
		valTpDato($idApertura, "int"));
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// CAMBIA ESTATUS DE CAJA (0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL)
	$sqlUpdateApertura = sprintf("UPDATE ".$apertCajaPpal." SET statusAperturaCaja = 0 WHERE id = %s",
		valTpDato($idApertura, "int"));
	$rsUpdateApertura = mysql_query($sqlUpdateApertura);
	if (!$rsUpdateApertura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// CONSULTA TODOS ENCABEZADOS DE LOS PAGOS GENERADOS AL DIA
	$sqlSelectEncabezadoPagoFACT = sprintf("SELECT * FROM cj_cc_encabezado_pago_v WHERE fecha_pago = %s",
		valTpDato($fechaApertura, "date"));
	$rsSelectEncabezadoPagoFACT = mysql_query($sqlSelectEncabezadoPagoFACT);
	if (!$rsSelectEncabezadoPagoFACT) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$sqlSelectEncabezadoPagoNC = sprintf("SELECT * FROM cj_cc_encabezado_pago_nc_v WHERE fecha_pago = %s",
		valTpDato($fechaApertura, "date"));
	$rsSelectEncabezadoPagoNC = mysql_query($sqlSelectEncabezadoPagoNC);
	if (!$rsSelectEncabezadoPagoNC) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// CONSULTA LAS NOTAS DE CREDITO GENERADAS POR TARJETAS DE DEBITO / CREDITO
	$sqlNotaCreditoTe = sprintf("SELECT * FROM te_nota_credito
	WHERE fecha_registro = %s
		AND origen = %s %s",
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
		$andEmpresaTE);
	$rssqlNotaCreditoTe = mysql_query($sqlNotaCreditoTe);
	if (!$rssqlNotaCreditoTe) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->alert("Caja cerrada exitosamente.");
	
	mysql_query("COMMIT;");
	
	// ENVIA A CONTABILIDAD LOS PAGOS DE FACTURAS DEL DIA
	while ($rowFACT = mysql_fetch_assoc($rsSelectEncabezadoPagoFACT)) {
		// MODIFICADO ERNESTO
		if (in_array($idCajaPpal,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$idEncabezadoPagoFACT = $rowFACT['id_encabezado_v'];
			if (function_exists("generarCajasEntradaVe")) { generarCajasEntradaVe($idEncabezadoPagoFACT,"",""); }
		} else {
			$idEncabezadoPagoFACT = $rowFACT['id_encabezado_rs'];
			if (function_exists("generarCajasEntradaRe")) { generarCajasEntradaRe($idEncabezadoPagoFACT,"",""); }
		}
		// MODIFICADO ERNESTO
	}
	
	// ENVIA A CONTABILIDAD LOS PAGOS DE NOTAS DE CARGO DEL DIA
	while ($rowNC = mysql_fetch_assoc($rsSelectEncabezadoPagoNC)) {
		// MODIFICADO ERNESTO
		if (in_array($idCajaPpal,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$idEncabezadoPagoNC = $rowNC['id_encabezado_nc_v'];
			if (function_exists("generarCajasEntradaNotasCargoVe")) { generarCajasEntradaNotasCargoVe($idEncabezadoPagoNC,"",""); }
		} else {
			$idEncabezadoPagoNC = $rowNC['id_encabezado_nc_rs'];
			if (function_exists("generarCajasEntradaNotasCargoRe")) { generarCajasEntradaNotasCargoRe($idEncabezadoPagoNC,"",""); }
		}
		// MODIFICADO ERNESTO
	}
	
	// ENVIA A CONTABILIDAD LOS PAGOS DE NOTAS DE CARGO DEL DIA
	while ($rowTe = mysql_fetch_assoc($rssqlNotaCreditoTe)) {
		$idNotaCreditoTe = $rowTe['id_nota_credito'];
		// MODIFICADO ERNESTO
		if (function_exists("generarNotaCreditoTe_2")) { generarNotaCreditoTe_2($idNotaCreditoTe,"",""); }
		// MODIFICADO ERNESTO
	}
	
	//ENVIA A CONTABILIDAD LAS COMISIONES BANCARIAS DEL DIA, GENERAL
	// MODIFICADO ERNESTO
	if (function_exists("generarComisionesBancarias")) { generarComisionesBancarias(0,$fechaApertura,$fechaApertura); }
	// MODIFICADO ERNESTO
	
	$objResponse->script("window.location.href = 'index.php'");
	
	return $objResponse;
}

function validarDepositos($acc){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $idModuloPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
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
		$andEmpresaFA = sprintf(" AND cxc_fact.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaND = sprintf(" AND cxc_nd.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaAN = sprintf(" AND cxc_ant.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaCH = sprintf(" AND cxc_ch.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresaFA = "";
		$andEmpresaND = "";
		$andEmpresaAN = "";
		$andEmpresaCH = "";
	}
			
	// VERIFICA SI SE HICIERON LOS DEPOSITOS DEL DIA ANTERIOR
	// FACTURAS
	$queryFactura = "SELECT *
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
	WHERE cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.formaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
		AND cxc_pago.formaPago IN (1,2)
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1) ".$andEmpresaFA;
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaVH = mysql_num_rows($rsFactura);
	
	// FACTURAS
	$queryFactura = "SELECT *
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
	WHERE cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.formaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
		AND cxc_pago.formaPago IN (1,2)
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1) ".$andEmpresaFA;
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaRS = mysql_num_rows($rsFactura);
	
	// NOTAS DE CARGO
	$queryNotaCargo = "SELECT *
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)	
	WHERE cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.idFormaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
		AND cxc_pago.idFormaPago IN (1,2)
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1) ".$andEmpresaND;
	$rsNotaCargo = mysql_query($queryNotaCargo);
	if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsNotaCargo = mysql_num_rows($rsNotaCargo);
	
	// ANTICIPOS
	$queryAnticipo = "SELECT *
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)	
	WHERE cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.id_forma_pago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
		AND cxc_pago.tipoPagoDetalleAnticipo IN ('EF','CH')
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1)
		AND cxc_ant.estatus = 1 ".$andEmpresaAN;
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	
	// CHEQUES
	$queryCheque = "SELECT * FROM cj_cc_cheque cxc_ch
	WHERE cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_ch.tomadoEnCierre IN (1)
		AND cxc_ch.estatus = 1 ".$andEmpresaCH;
	$rsCheque = mysql_query($queryCheque);
	if (!$rsCheque) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCheque = mysql_num_rows($rsCheque);
	
	if ($totalRowsFacturaVH > 0 || $totalRowsFacturaRS > 0 || $totalRowsNotaCargo > 0 || $totalRowsAnticipo > 0 || $totalRowsCheque > 0) {
		$objResponse->alert("No se ha realizado el deposito a Bancos. No se puede realizar el Cierre de Caja.");
		if (in_array($idCajaPpal, array(1))){
			$objResponse->script("window.location.href = 'cj_depositos_form.php'");
		} else if (in_array($idCajaPpal, array(2))){
			$objResponse->script("window.location.href = 'cjrs_depositos_form.php'");
		}
	} else {
		if ($acc == 2) { // 2 = Corte de Caja
			if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
				$andSql = sprintf(" AND id_empresa = %s",
					valTpDato($idEmpresa, "int"));
				$andSql2 = sprintf(" AND ape.id_empresa = %s",
					valTpDato($idEmpresa, "int"));
			} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
				$andSql = "";
				$andSql2 = "";
			}
			
			$queryFacturaSinPago = sprintf("SELECT
				numerofactura,
				id_empresa
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE idDepartamentoOrigenFactura IN (%s)
				AND condicionDePago = 1
				AND saldofactura <> 0 %s
				AND fechaRegistroFactura = (SELECT ape.fechaAperturaCaja
											FROM ".$apertCajaPpal." ape
												INNER JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
											WHERE cierre.idCierre = (SELECT MAX(cierre2.idCierre) AS maximo
																	FROM ".$apertCajaPpal." ape2
																		INNER JOIN ".$cierreCajaPpal." cierre2 ON (ape2.id = cierre2.id)
																	WHERE ape2.idCaja = %s %s))",
				valTpDato($idModuloPpal, "campo"),
				$andSql,
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				$andSql2);
			$rsFacturaSinPago = mysql_query($queryFacturaSinPago);
			if (!$rsFacturaSinPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFacturaSinPago = mysql_num_rows($rsFacturaSinPago);
			if ($totalRowsFacturaSinPago > 0) {
				while ($rowFacturaSinPago = mysql_fetch_array($rsFacturaSinPago)) {
					$arrayFacturaSinPago[] = $rowFacturaSinPago['numerofactura'];
				}
				$objResponse->script(
				"byId('facurasSinPagos').innerHTML = 'Los Numeros de Facturas que se encuentran sin pagos son las siguientes:&nbsp;<font color=red>".implode(", ",$arrayFacturaSinPago)."</font>';".
				"byId('infoPagosNoRealizados').style.visibility = 'visible';".
				"byId('btnImprimir').disabled = true;".
				"byId('btnCerrarCaja').disabled = true;");
			}
		
			$objResponse->script("
			byId('btnCerrarCaja').style.display = '';");
		}
		
		$objResponse->script("
		byId('divButtons').style.display = '';");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cerrarCaja");
$xajax->register(XAJAX_FUNCTION,"validarDepositos");
?>