<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == "2") {
		$sqlBusq .= $cond.sprintf("an_ped_vent.fecha_entrega BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else if ($valCadBusq[3] == "3") {
		$sqlBusq .= $cond.sprintf("DATE(cxc_fact.fecha_pagada) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else if ($valCadBusq[3] == "4") {
		$sqlBusq .= $cond.sprintf("DATE(cxc_fact.fecha_cierre) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else {
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.aplicaLibros = %s",
		valTpDato($valCadBusq[5], "boolean"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.condicionDePago = %s",
		valTpDato($valCadBusq[6], "boolean"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.anulada IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.estadoFactura IN (%s)",
		valTpDato($valCadBusq[8], "campo"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$arrayBusq = "";
	if (in_array(1, explode(",",$valCadBusq[9]))) { // Vehiculo
		$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_vehic2.id_factura)
								FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic2 WHERE cxc_fact_det_vehic2.id_factura = cxc_fact.idFactura) > 0");
	}
	if (in_array(2, explode(",",$valCadBusq[9]))) { // Adicionales
		$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_acc2.id_factura)
								FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc2
									INNER JOIN an_accesorio acc ON (cxc_fact_det_acc2.id_accesorio = acc.id_accesorio)
								WHERE cxc_fact_det_acc2.id_factura = cxc_fact.idFactura
									AND acc.id_tipo_accesorio IN (1)) > 0");
	}
	if (in_array(3, explode(",",$valCadBusq[9]))) { // Accesorios
		$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_acc2.id_factura)
								FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc2
									INNER JOIN an_accesorio acc ON (cxc_fact_det_acc2.id_accesorio = acc.id_accesorio)
								WHERE cxc_fact_det_acc2.id_factura = cxc_fact.idFactura
									AND acc.id_tipo_accesorio IN (2)) > 0");
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
}

if (in_array(1, explode(",",$valCadBusq[10]))
|| in_array(2, explode(",",$valCadBusq[10]))
|| in_array(3, explode(",",$valCadBusq[10]))
|| in_array(4, explode(",",$valCadBusq[10]))
|| in_array(5, explode(",",$valCadBusq[10]))) {
	$arrayBusq = "";
	if (in_array(1, explode(",",$valCadBusq[10]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																	FROM cj_cc_anticipo cxc_ant
																		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																	WHERE cxc_pago.id_concepto IN (1,6))) > 0");
	} else if (in_array(2, explode(",",$valCadBusq[10]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																	FROM cj_cc_anticipo cxc_ant
																		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																	WHERE cxc_pago.id_concepto IN (2))) > 0");
	} else if (in_array(3, explode(",",$valCadBusq[10]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																	FROM cj_cc_anticipo cxc_ant
																		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																	WHERE cxc_pago.id_concepto IN (7,8,9))) > 0");
	} else if (in_array(4, explode(",",$valCadBusq[10]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(tradein_cxc.id_nota_cargo_cxc)
								FROM an_pagos cxc_pago
									INNER JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
									INNER JOIN an_tradein tradein ON (cxc_ant.idAnticipo = tradein.id_anticipo)
									INNER JOIN an_tradein_cxc tradein_cxc ON (tradein.id_tradein = tradein_cxc.id_tradein
										AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
								WHERE cxc_pago.id_factura = cxc_fact.idFactura) > 0");
	} else if (in_array(5, explode(",",$valCadBusq[10]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT tradein_cxc.id_anticipo FROM an_tradein_cxc tradein_cxc
																	WHERE tradein_cxc.id_anticipo IS NOT NULL
																		AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)) > 0");
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("an_ped_vent.id_banco_financiar IN (%s)",
		valTpDato($valCadBusq[11], "campo"));
}

if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR an_ped_vent.id_pedido LIKE %s
	OR an_ped_vent.numeracion_pedido LIKE %s
	OR pres_vent.id_presupuesto LIKE %s
	OR pres_vent.numeracion_presupuesto LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT(vw_iv_modelo.nom_uni_bas,': ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) LIKE %s
	OR uni_fis.placa LIKE %s
	OR poliza.nombre_poliza LIKE %s
	OR pres_acc.id_presupuesto_accesorio LIKE %s)",
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"),
		valTpDato("%".$valCadBusq[12]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT 
	cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxc_fact.idFactura,
	cxc_fact.id_empresa,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.fechaVencimientoFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
	cxc_fact.condicionDePago AS condicion_pago,
	an_ped_vent.id_pedido,
	an_ped_vent.numeracion_pedido,
	an_ped_vent.fecha_entrega,
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	cliente.id AS id_cliente,
	cliente.direccion AS direccion,
	cliente.telf AS telefono,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	vw_iv_modelo.nom_ano,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	cond_unidad.descripcion AS condicion_unidad,
	cxc_fact.estadoFactura,
	(CASE cxc_fact.estadoFactura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_factura,
	banco.nombreBanco,
	poliza.nombre_poliza,
	an_ped_vent.monto_seguro,
	pres_acc.id_presupuesto_accesorio,
	vw_pg_empleado.nombre_empleado,
	cxc_fact_det_vehic.precio_unitario,
	cxc_fact_det_vehic.costo_compra,
	an_ped_vent.vexacc1 AS subtotal_accesorios,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
	
	IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
			WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)
		+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
					WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
		+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
					WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
	
	cxc_fact.saldoFactura,
	cxc_fact.anulada,
	cxc_fact.fecha_pagada,
	cxc_fact.fecha_cierre,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
	LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto AND cxc_fact.idDepartamentoOrigenFactura = 2)
	LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
	LEFT JOIN an_presupuesto_accesorio pres_acc ON (an_ped_vent.id_presupuesto = pres_acc.id_presupuesto)
	LEFT JOIN an_poliza poliza ON (an_ped_vent.id_poliza = poliza.id_poliza)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY cxc_fact.numeroControl DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Fecha Pagada");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Fecha Cierre");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Fecha Entrega");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Nro. Presupuesto");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Dirección");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Vehículo");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Año Vehículo");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Entidad Bancaria");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, "Condición");
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, "Vendedor");
$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, "Seguro");
$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, "Monto del Seguro");
$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, "Precio Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, "Costo Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, "Subtotal Accesorios");
$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("AH".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":AH".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgPedidoModulo = "Factura Repuestos"; break;
		case 1 : $imgPedidoModulo = "Factura Servicios"; break;
		case 2 : $imgPedidoModulo = "Factura Vehículos"; break;
		case 3 : $imgPedidoModulo = "Factura Administración"; break;
		default : $imgPedidoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['id_pedido'] > 0) ? "" : "Creada por CxC";
		
	$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Factura";
		
	switch ($row['flotilla']) {
		case 0 : $imgEstatusUnidadAsignacion = "Vehículo Normal"; break;
		case 1 : $imgEstatusUnidadAsignacion = "Vehículo por Flotilla"; break;
		default : $imgEstatusUnidadAsignacion = "";
	}
			
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $imgEstatusUnidadAsignacion);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fechaVencimientoFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, (($row['fecha_pagada'] != "") ? date(spanDateFormat, strtotime($row['fecha_pagada'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($row['fecha_cierre'] != "") ? date(spanDateFormat, strtotime($row['fecha_cierre'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($row['fecha_entrega'] != "") ? date(spanDateFormat, strtotime($row['fecha_entrega'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['numeroFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['numeroControl']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['numeracion_pedido']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['numeracion_presupuesto']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['id_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, utf8_encode($row['direccion']));
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, utf8_encode($row['telefono']));
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, utf8_encode($row['vehiculo']));
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['nom_ano']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['nombreBanco']);
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, (($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO"));
	$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $row['descripcion_estado_factura']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("X".$contFila, $row['serial_carroceria'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $row['condicion_unidad']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("Z".$contFila, $row['placa'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, utf8_encode($row['nombre_empleado']));
	$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, $row['nombre_poliza']);
	$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, $row['monto_seguro']);
	$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, $row['precio_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, $row['costo_compra']);
	$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, $row['subtotal_accesorios']);
	$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, $row['saldoFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("AH".$contFila, $row['total']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":AG".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("AA".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->getStyle("AB".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AC".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AD".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AE".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AF".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AG".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AH".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	
	$arrayTotal['cant_documentos'] += 1;
	$arrayTotal['monto_seguro'] += $row['monto_seguro'];
	$arrayTotal['precio_unitario'] += $row['precio_unitario'];
	$arrayTotal['costo_compra'] += $row['costo_compra'];
	$arrayTotal['subtotal_accesorios'] += $row['subtotal_accesorios'];
	$arrayTotal['saldo_factura'] += $row['saldoFactura'];
	$arrayTotal['total'] += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":AG".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, $arrayTotal['cant_documentos']);
$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, $arrayTotal['monto_seguro']);
$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, $arrayTotal['precio_unitario']);
$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, $arrayTotal['costo_compra']);
$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, $arrayTotal['subtotal_accesorios']);
$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, $arrayTotal['saldo_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("AH".$contFila, $arrayTotal['total']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."AH".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("AC".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AD".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AE".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AF".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AG".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AH".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":Z".$contFila);

for ($col = "A"; $col != "AH"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "AH");

$tituloDcto = "Histórico Reporte Venta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:AH7");

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE ".cVERSION);
//$objPHPExcel->getProperties()->setLastModifiedBy("autor");
$objPHPExcel->getProperties()->setTitle($tituloDcto);
//$objPHPExcel->getProperties()->setSubject("Asunto");
//$objPHPExcel->getProperties()->setDescription("Descripcion");

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
header('Cache-Control: max-age=0');
 
//Creamos el Archivo .xlsx
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>