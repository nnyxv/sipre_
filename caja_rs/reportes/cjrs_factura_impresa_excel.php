<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
require_once ("../inc_caja.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$rs = mysql_query(sprintf("SELECT IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa FROM vw_iv_empresas_sucursales vw_iv_emp_suc WHERE vw_iv_emp_suc.id_empresa_reg = %s;", valTpDato($valCadBusq[0], "int")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayEmpresa[] = $row['nombre_empresa'];
	}
	$arrayCriterioBusqueda[] = "Empresa: ".((isset($arrayEmpresa)) ? implode(", ", $arrayEmpresa) : "");
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$arrayCriterioBusqueda[] = "Fecha: Desde ".$valCadBusq[1]." Hasta ".$valCadBusq[2];
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	foreach (explode(",", $valCadBusq[3]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM vw_pg_empleados empleado WHERE id_empleado = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayVendedor[] = $row['nombre_empleado'];
		}
	}
	$arrayCriterioBusqueda[] = "Vendedor: ".((isset($arrayVendedor)) ? implode(", ", $arrayVendedor) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstAplicaLibro = array(0 => "No", 1 => "Si");
	foreach ($lstAplicaLibro as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayAplicaLibro[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Aplica Libro: ".((isset($arrayAplicaLibro)) ? implode(", ", $arrayAplicaLibro) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$lstEstadoFactura = array(0 => "No Cancelado", 1 => "Cancelado", 2 => "Cancelado Parcial");
	foreach ($lstEstadoFactura as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[5]))) {
			$arrayEstadoFactura[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Factura: ".((isset($arrayEstadoFactura)) ? implode(", ", $arrayEstadoFactura) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$lstTipoPago = array(0 => "Crédito", 1 => "Contado");
	foreach ($lstTipoPago as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[6]))) {
			$arrayTipoPago[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Tipo Pago: ".((isset($arrayTipoPago)) ? implode(", ", $arrayTipoPago) : "");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	foreach (explode(",", $valCadBusq[7]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = $row['descripcionModulo'];
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$lstEstadoPedido = array("NO" => "Factura", "SI" => "Factura (Con Devolución)");
	foreach ($lstEstadoPedido as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[8]))) {
			$arrayEstadoPedido[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Ver: ".((isset($arrayEstadoPedido)) ? implode(", ", $arrayEstadoPedido) : "");
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$lstEstadoFiscal = array(1 => "Impresa", 2 => "No Impresa");
	foreach ($lstEstadoFiscal as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[9]))) {
			$arrayEstadoFiscal[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Fiscal: ".((isset($arrayEstadoFiscal)) ? implode(", ", $arrayEstadoFiscal) : "");
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[10];
}

////////// CRITERIO DE BUSQUEDA //////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

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
	$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.aplicaLibros = %s",
		valTpDato($valCadBusq[4], "boolean"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.estadoFactura IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.condicionDePago = %s",
		valTpDato($valCadBusq[6], "boolean"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE %s",
		valTpDato($valCadBusq[8], "text"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$arrayConsecutivoFiscal = explode(",",$valCadBusq[9]);
	if (count($arrayConsecutivoFiscal) == 1) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if (in_array(1,$arrayConsecutivoFiscal) && !in_array(2,$arrayConsecutivoFiscal)) {
			$sqlBusq .= $cond.sprintf("cxc_fact.consecutivo_fiscal IS NOT NULL");
		} else if (!in_array(1,$arrayConsecutivoFiscal) && in_array(2,$arrayConsecutivoFiscal)) {
			$sqlBusq .= $cond.sprintf("cxc_fact.consecutivo_fiscal IS NULL");
		}
	}
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR cxc_fact.consecutivo_fiscal LIKE %s
	OR cxc_fact.serial_impresora LIKE %s
	OR ped_vent.id_pedido_venta_propio LIKE %s
	OR orden.numero_orden LIKE %s
	OR an_ped_vent.numeracion_pedido LIKE %s)",
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxc_fact.idFactura,
	cxc_fact.id_empresa,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.fechaVencimientoFactura,
	cxc_fact.fecha_pagada,
	cxc_fact.fecha_cierre,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
	vw_pg_empleado_vendedor.id_empleado AS id_empleado_vendedor,
	vw_pg_empleado_vendedor.nombre_empleado AS nombre_empleado_vendedor,
	cxc_fact.condicionDePago,
	cxc_fact.numeroPedido,
	
	(SELECT an_ped_vent2.id_pedido FROM an_pedido an_ped_vent2
	WHERE an_ped_vent2.id_factura_cxc = cxc_fact.idFactura
		AND an_ped_vent2.estado_pedido IN (0,1,2,3,4)) AS id_pedido_reemplazo,
	
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	cond_unidad.descripcion AS condicion_unidad,
	ped_comp_det.flotilla,
	cxc_fact.estadoFactura,
	(CASE cxc_fact.estadoFactura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_factura,
	cxc_fact.aplicaLibros,
	cxc_fact.anulada,
	cxc_fact.estatus_factura,
	cxc_fact.subtotalFactura,
	cxc_fact.descuentoFactura,
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
	IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
			WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_impuestos,
	cxc_fact.montoTotalFactura,
	cxc_fact.saldoFactura,
	cxc_fact.observacionFactura,
	
	vw_pg_empleado_creador.id_empleado AS id_empleado_creador,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
	
	(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN		ped_vent.fecha
		WHEN 1 THEN		orden.tiempo_orden
		WHEN 2 THEN		an_ped_vent.fecha
		ELSE			NULL
	END) AS fecha_pedido,
	(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN		NULL
		WHEN 1 THEN		NULL
		WHEN 2 THEN		an_ped_vent.fecha_reserva_venta
		ELSE			NULL
	END) AS fecha_reserva_venta,
	(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN		NULL
		WHEN 1 THEN		orden.tiempo_entrega
		WHEN 2 THEN		an_ped_vent.fecha_entrega
		ELSE			NULL
	END) AS fecha_entrega,
	(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN		ped_vent.id_pedido_venta_propio
		WHEN 1 THEN		orden.numero_orden
		WHEN 2 THEN		an_ped_vent.numeracion_pedido
		ELSE			NULL
	END) AS numero_pedido,
	ped_vent.estatus_pedido_venta,
	tipo_orden.nombre_tipo_orden,
	banco.nombreBanco,
	
	(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN
			IFNULL((SELECT COUNT(cxc_fact_det.id_factura) FROM cj_cc_factura_detalle cxc_fact_det
					WHERE cxc_fact_det.id_factura = cxc_fact.idFactura), 0)
		WHEN 1 THEN
			(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
					WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
						WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
						WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
						WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
		WHEN 2 THEN
			(IFNULL((SELECT COUNT(cxc_fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
					WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(cxc_fact_det_vehic.id_factura) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						WHERE cxc_fact_det_vehic.id_factura = cxc_fact.idFactura), 0))
		WHEN 3 THEN
			IFNULL((SELECT COUNT(cxc_fact_det_adm.id_factura) FROM cj_cc_factura_detalle_adm cxc_fact_det_adm
				WHERE cxc_fact_det_adm.id_factura = cxc_fact.idFactura), 0)
	END) AS cant_items,
	
	(CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 2 THEN
			IFNULL((SELECT COUNT(cxc_fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
					WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)
	END) AS cant_accesorios,
	
	cxc_fact.consecutivo_fiscal,
	cxc_fact.serial_impresora,
	cxc_fact.fecha_impresora,
	cxc_fact.hora_impresora,
	CONCAT_WS('/', cxc_fact.consecutivo_fiscal, cxc_fact.serial_impresora) AS consecutivo_serial,
	CONCAT_WS(' ', cxc_fact.fecha_impresora, cxc_fact.hora_impresora) AS fecha_hora
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
	LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
	LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
		LEFT JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
	LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_vendedor ON (cxc_fact.idVendedor = vw_pg_empleado_vendedor.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_fact.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY idFactura DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$queryFormaPago = ("SELECT * FROM formapagos ORDER BY nombreFormaPago ASC");
$rsFormaPago = mysql_query($queryFormaPago);
if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFormaPago = mysql_num_rows($rsFormaPago);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Formas de Pago");
$contColumFP = "R";
for ($i = 1; $i < $totalRowsFormaPago; $i++) { $contColumFP++; }
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila.":".($contColumFP).$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("R".$contFila.":".($contColumFP).$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Pedido / Orden");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Fecha / Hora Impresora");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Consecutivo Fiscal / Serial Impresora");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Total Factura");

$contColum = "Q";
while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
	$contColum++;
	
	$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowFormaPago['nombreFormaPago']);
}
$contColumUlt = $contColum;

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgDctoModulo = "Repuestos"; break;
		case 1 : $imgDctoModulo = "Servicios"; break;
		case 2 : $imgDctoModulo = "Vehículos"; break;
		case 3 : $imgDctoModulo = "Administración"; break;
		case 4 : $imgDctoModulo = "Alquiler"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "Creada por CxC";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgDctoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fechaVencimientoFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['numeroControl'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numero_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['descripcion_estado_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['fecha_hora']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['consecutivo_serial']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['saldoFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['montoTotalFactura']);
	
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$contColum = "Q";
	while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
		$contColum++;
		
		$queryDctoPago = sprintf("SELECT *
		FROM (SELECT 
				cxc_pago.id_factura,
				cxc_pago.fechaPago,
				cxc_pago.formaPago,
				cxc_pago.montoPagado
			FROM sa_iv_pagos cxc_pago
			
			UNION
			
			SELECT 
				cxc_pago.id_factura,
				cxc_pago.fechaPago,
				cxc_pago.formaPago,
				cxc_pago.montoPagado
			FROM an_pagos cxc_pago) AS query
		WHERE query.id_factura = %s
			AND query.formaPago = %s",
			valTpDato($row['idFactura'], "int"),
			valTpDato($rowFormaPago['idFormaPago'], "int"));
		$rsDctoPago = mysql_query($queryDctoPago);
		if (!$rsDctoPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsDctoPago = mysql_num_rows($rsDctoPago);
		$totalPagosDcto = 0;
		while ($rowDctoPago = mysql_fetch_assoc($rsDctoPago)) {
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowDctoPago['montoPagado']);
			$totalPagosDcto += $rowDctoPago['montoPagado'];
		}
		
		$arrayTotalPago[$contColum] += $totalPagosDcto;
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$contColum = "Q";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	}
	
	
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$contColum = "Q";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	}
	
	$arrayTotal[12] += $row['cant_items'];
	$arrayTotal[13] += $row['saldoFactura'];
	$arrayTotal[14] += $row['montoTotalFactura'];
	$cont = 14;
	if (isset($arrayTotalPago)) {
		foreach ($arrayTotalPago as $indice => $valor) {
			$cont++;
			$arrayTotal[$cont] = $arrayTotalPago[$indice];
		}
	}
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".($contColumUlt).$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$contColum = "N";
if (isset($arrayTotalPago)) {
	foreach ($arrayTotal as $indice => $valor) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $arrayTotal[$indice]);
		
		if ($indice == 12) {
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		} else {
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
		}
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."N".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("O".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."N".$contFila);

for ($col = "A"; $col != ($contColumUlt); $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, ($contColumUlt), true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Facturas Impresas";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".($contColumUlt)."7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:".($contColumUlt)."9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE 3.0");
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