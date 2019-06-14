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
$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
	valTpDato("2", "campo"));

$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
	valTpDato("2", "campo"));

$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
$sqlBusq4 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
	valTpDato("2", "campo"));

$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
$sqlBusq6 .= $cond.sprintf("(cxp_nc.id_departamento_notacredito IN (%s)
AND cxp_nc.tipo_documento LIKE 'FA')",
	valTpDato("2", "campo"));

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(vale_ent.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vale_ent.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("(vale_sal.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vale_sal.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("(cxp_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	if ($valCadBusq[3] == "2") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("an_ped_vent.fecha_entrega BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	} else if ($valCadBusq[3] == "3") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("DATE(cxc_fact.fecha_pagada) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	} else if ($valCadBusq[3] == "4") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("DATE(cxc_fact.fecha_cierre) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("NULL BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("DATE(vale_ent.fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("DATE(cxc_nc.fechaNotaCredito) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("DATE(vale_sal.fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_fact.estatus_factura IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.estatus_documento IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.id_tipo_movimiento IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("vale_ent.id_clave_movimiento IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
		
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nc.id_clave_movimiento IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_fact.id_clave_movimiento IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
		
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("vale_sal.id_clave_movimiento IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.id_empleado_vendedor IN (%s)",
		valTpDato($valCadBusq[8], "campo"));
}

if ($valCadBusq[9] != "" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("(query.numero_documento LIKE %s
	OR query.numero_control_documento LIKE %s
	OR query.ci_cliente LIKE %s
	OR query.nombre_cliente LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT query.*,
	
	(CASE
		WHEN (query.tipoDocumento IN ('FA','ND')) THEN
			(CASE query.estado_pago_documento
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END)
		WHEN (query.tipoDocumento IN ('AN','NC','CH','TB')) THEN
			(CASE query.estado_pago_documento
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
				WHEN 4 THEN 'No Cancelado (Asignado)'
			END)
	END) AS estado_documento,
	
	clave_mov.clave,
	clave_mov.descripcion,
	vw_pg_empleado_cierre.nombre_empleado AS nombre_empleado_cierre
FROM (SELECT 
		cxp_fact.id_factura AS id_documento,
		cxp_fact.numero_factura_proveedor AS numero_documento,
		cxp_fact.numero_control_factura AS numero_control_documento,
		cxp_fact.fecha_factura_proveedor AS fecha_documento,
		cxp_fact.fecha_origen AS fecha_registro,
		cxp_fact.id_modulo,
		prov.id_proveedor AS id_cliente,
		CONCAT_WS('-', prov.lrif, prov.rif) AS ci_cliente,
		prov.nombre AS nombre_cliente,
		NULL AS id_empleado_vendedor,
		cxp_fact.estatus_factura AS estado_pago_documento,
		'FA' AS tipoDocumento,
		1 AS id_tipo_movimiento,
		NULL AS tipo_documento_movimiento,
		NULL AS id_clave_movimiento,
		NULL AS numero_pedido,
		NULL AS estatus_documento,
		NULL AS id_empleado_cierre,
		NULL AS fecha_cierre,
		NULL AS observacion_cierre
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor) %s
	
	UNION
		
	SELECT 
		vale_ent.id_vale_entrada,
		vale_ent.numeracion_vale_entrada,
		vale_ent.numeracion_vale_entrada,
		vale_ent.fecha,
		vale_ent.fecha,
		2 AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		NULL AS id_empleado_vendedor,
		NULL AS estado_pago_documento,
		'VE' AS tipoDocumento,
		2 AS id_tipo_movimiento,
		1 AS tipo_documento_movimiento,
		vale_ent.id_clave_movimiento,
		NULL AS numero_pedido,
		NULL AS estatus_documento,
		NULL AS id_empleado_cierre,
		NULL AS fecha_cierre,
		NULL AS observacion_cierre
	FROM an_vale_entrada vale_ent
		INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id) %s
	
	UNION
	
	SELECT 
		cxc_nc.idNotaCredito,
		cxc_nc.numeracion_nota_credito,
		cxc_nc.numeroControl,
		cxc_nc.fechaNotaCredito,
		cxc_nc.fechaNotaCredito,
		cxc_nc.idDepartamentoNotaCredito,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nc.id_empleado_vendedor,
		cxc_nc.estadoNotaCredito AS estado_pago_documento,
		'NC' AS tipoDocumento,
		2 AS id_tipo_movimiento,
		2 AS tipo_documento_movimiento,
		cxc_nc.id_clave_movimiento,
		NULL AS numero_pedido,
		cxc_nc.estatus_nota_credito AS estatus_documento,
		NULL AS id_empleado_cierre,
		NULL AS fecha_cierre,
		NULL AS observacion_cierre
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id) %s
	
	UNION
		
	SELECT
		cxc_fact.idFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idDepartamentoOrigenFactura,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_fact.idVendedor AS id_empleado_vendedor,
		cxc_fact.estadoFactura AS estado_pago_documento,
		'FA' AS tipoDocumento,
		3 AS id_tipo_movimiento,
		NULL AS tipo_documento_movimiento,
		cxc_fact.id_clave_movimiento,
		an_ped_vent.numeracion_pedido AS numero_pedido,
		cxc_fact.estatus_factura AS estatus_documento,
		cxc_fact.id_empleado_cierre,
		cxc_fact.fecha_cierre,
		cxc_fact.observacion_cierre
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2) %s
	
	UNION
		
	SELECT 
		vale_sal.id_vale_salida,
		vale_sal.numeracion_vale_salida,
		vale_sal.numeracion_vale_salida,
		vale_sal.fecha,
		vale_sal.fecha,
		2 AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		NULL AS id_empleado_vendedor,
		NULL AS estado_pago_documento,
		'VS' AS tipoDocumento,
		4 AS id_tipo_movimiento,
		1 AS tipo_documento_movimiento,
		vale_sal.id_clave_movimiento,
		NULL AS numero_pedido,
		NULL AS estatus_documento,
		NULL AS id_empleado_cierre,
		NULL AS fecha_cierre,
		NULL AS observacion_cierre
	FROM an_vale_salida vale_sal
		INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id) %s
	
	UNION
		
	SELECT 
		cxp_nc.id_notacredito,
		cxp_nc.numero_nota_credito,
		cxp_nc.numero_control_notacredito,
		cxp_nc.fecha_notacredito,
		cxp_nc.fecha_registro_notacredito,
		cxp_nc.id_departamento_notacredito,
		prov.id_proveedor AS id_cliente,
		CONCAT_WS('-', prov.lrif, prov.rif) AS ci_cliente,
		prov.nombre AS nombre_cliente,
		NULL AS id_empleado_vendedor,
		estado_notacredito AS estado_pago_documento,
		'NC' AS tipoDocumento,
		4 AS id_tipo_movimiento,
		2 AS tipo_documento_movimiento,
		NULL AS id_clave_movimiento,
		NULL AS numero_pedido,
		NULL AS estatus_documento,
		NULL AS id_empleado_cierre,
		NULL AS fecha_cierre,
		NULL AS observacion_cierre
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor) %s) AS query
	LEFT JOIN pg_clave_movimiento clave_mov ON (query.id_clave_movimiento = clave_mov.id_clave_movimiento)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_cierre ON (query.id_empleado_cierre = vw_pg_empleado_cierre.id_empleado) %s
ORDER BY query.id_tipo_movimiento, query.id_documento ASC;", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>SQL: ".$query);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	if ($row['id_tipo_movimiento'] == 1) { // 1 = Compra
		$queryDetalle = sprintf("SELECT
			vw_iv_modelo.nom_uni_bas,
			CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
			1 AS cantidad,
			cxp_fact_det_unidad.costo_unitario AS precio_unitario,
			cxp_fact_det_unidad.costo_unitario AS costo_compra,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			NULL AS id_tipo_accesorio,
			kardex.idKardex AS id_kardex
		FROM cp_factura_detalle_unidad cxp_fact_det_unidad
			INNER JOIN an_solicitud_factura ped_comp_det ON (cxp_fact_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			INNER JOIN an_unidad_fisica uni_fis ON (cxp_fact_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad
				AND ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			LEFT JOIN an_kardex kardex ON (cxp_fact_det_unidad.id_factura = kardex.id_documento
				AND kardex.idUnidadFisica = uni_fis.id_unidad_fisica
				AND kardex.tipoMovimiento IN (1))
		WHERE cxp_fact_det_unidad.id_factura = %s
		
		UNION
		
		SELECT 
			acc.nom_accesorio,
			acc.des_accesorio,
			cxp_fact_det_acc.cantidad,
			cxp_fact_det_acc.costo_unitario AS precio_unitario,
			cxp_fact_det_acc.costo_unitario AS costo_compra,
			NULL AS serial_carroceria,
			NULL AS serial_motor,
			NULL AS serial_chasis,
			NULL AS placa,
			NULL AS condicion_unidad,
			NULL AS id_tipo_accesorio,
			NULL AS id_kardex
		FROM cp_factura_detalle_accesorio cxp_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxp_fact_det_acc.id_factura = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_documento'], "int"));
	} else if ($row['id_tipo_movimiento'] == 2) { // 2 = Entrada
		if ($row['tipo_documento_movimiento'] == 1) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
				1 AS cantidad,
				subtotal_factura AS precio_unitario,
				subtotal_factura AS costo_compra,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_chasis,
				uni_fis.placa,
				cond_unidad.descripcion AS condicion_unidad,
				NULL AS id_tipo_accesorio,
				kardex.idKardex AS id_kardex
			FROM an_vale_entrada vale_ent
				INNER JOIN an_unidad_fisica uni_fis ON (vale_ent.id_unidad_fisica = uni_fis.id_unidad_fisica)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
				INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
				LEFT JOIN an_kardex kardex ON (vale_ent.id_vale_entrada = kardex.id_documento
					AND kardex.idUnidadFisica = uni_fis.id_unidad_fisica
					AND kardex.tipoMovimiento IN (2)
					AND kardex.tipo_documento_movimiento IN (1))
			WHERE vale_ent.id_vale_entrada = %s;",
				valTpDato($row['id_documento'], "int"));
		} else if ($row['tipo_documento_movimiento'] == 2) {
			$queryDetalle = sprintf("SELECT q.*,
				(CASE q.id_tipo_accesorio
					WHEN 1 THEN	'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
				END) AS descripcion_tipo_accesorio
			FROM (
					SELECT
						vw_iv_modelo.nom_uni_bas,
						CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
						1 AS cantidad,
						cxc_nc_det_vehic.precio_unitario,
						cxc_nc_det_vehic.costo_compra,
						NULL AS id_tipo_accesorio,
						uni_fis.serial_carroceria,
						uni_fis.serial_motor,
						uni_fis.serial_chasis,
						uni_fis.placa,
						cond_unidad.descripcion AS condicion_unidad,
						kardex.idKardex AS id_kardex
					FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
						INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
					INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
					LEFT JOIN an_kardex kardex ON (cxc_nc_det_vehic.id_nota_credito = kardex.id_documento
						AND kardex.idUnidadFisica = uni_fis.id_unidad_fisica
						AND kardex.tipoMovimiento IN (2)
						AND kardex.tipo_documento_movimiento IN (2))
					WHERE cxc_nc_det_vehic.id_nota_credito = %s
					
					UNION
					
					SELECT 
						art.codigo_articulo,
						art.descripcion,
						cxc_nc_det.cantidad,
						(IFNULL(cxc_nc_det.precio_unitario, 0)
							+ IFNULL(cxc_nc_det.pmu_unitario, 0)) AS precio_unitario,
						cxc_nc_det.costo_compra,
						NULL AS id_tipo_accesorio,
						NULL AS serial_carroceria,
						NULL AS serial_motor,
						NULL AS serial_chasis,
						NULL AS placa,
						NULL AS condicion_unidad,
						NULL AS id_kardex
					FROM cj_cc_nota_credito_detalle cxc_nc_det
						INNER JOIN iv_articulos art ON (cxc_nc_det.id_articulo = art.id_articulo)
					WHERE cxc_nc_det.id_nota_credito = %s
					
					UNION
					
					SELECT 
						acc.nom_accesorio,
						acc.des_accesorio,
						cxc_nc_det_acc.cantidad,
						cxc_nc_det_acc.precio_unitario,
						cxc_nc_det_acc.costo_compra,
						cxc_nc_det_acc.id_tipo_accesorio,
						NULL AS serial_carroceria,
						NULL AS serial_motor,
						NULL AS serial_chasis,
						NULL AS placa,
						NULL AS condicion_unidad,
						NULL AS id_kardex
					FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
						INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
					WHERE cxc_nc_det_acc.id_nota_credito = %s) AS q;",
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"));
		}
	} else if ($row['id_tipo_movimiento'] == 3) { // 3 = Venta
		$queryDetalle = sprintf("SELECT q.*,
			(CASE q.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			cxc_nd.idNotaCargo,
			cxc_nd.numeroNotaCargo,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
			
			(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
			
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_nd.estadoNotaCargo,
			(CASE cxc_nd.estadoNotaCargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS descripcion_estado_nota_cargo
		FROM (
				SELECT
					vw_iv_modelo.nom_uni_bas,
					CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
					1 AS cantidad,
					cxc_fact_det_vehic.precio_unitario,
					cxc_fact_det_vehic.costo_compra,
					NULL AS id_tipo_accesorio,
					NULL AS id_nota_cargo_cxc,
					uni_fis.serial_carroceria,
					uni_fis.serial_motor,
					uni_fis.serial_chasis,
					uni_fis.placa,
					cond_unidad.descripcion AS condicion_unidad,
					kardex.idKardex AS id_kardex
				FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
					INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
					LEFT JOIN an_kardex kardex ON (cxc_fact_det_vehic.id_factura = kardex.id_documento
						AND kardex.idUnidadFisica = uni_fis.id_unidad_fisica
						AND kardex.tipoMovimiento IN (3))
				WHERE cxc_fact_det_vehic.id_factura = %s
				
				UNION
				
				SELECT 
					art.codigo_articulo,
					art.descripcion,
					cxc_fact_det.cantidad,
					(IFNULL(cxc_fact_det.precio_unitario, 0)
						+ IFNULL(cxc_fact_det.pmu_unitario, 0)) AS precio_unitario,
					cxc_fact_det.costo_compra,
					NULL AS id_tipo_accesorio,
					NULL AS id_nota_cargo_cxc,
					NULL AS serial_carroceria,
					NULL AS serial_motor,
					NULL AS serial_chasis,
					NULL AS placa,
					NULL AS condicion_unidad,
					NULL AS id_kardex
				FROM cj_cc_factura_detalle cxc_fact_det
					INNER JOIN iv_articulos art ON (cxc_fact_det.id_articulo = art.id_articulo)
				WHERE cxc_fact_det.id_factura = %s
				
				UNION
				
				SELECT 
					acc.nom_accesorio,
					acc.des_accesorio,
					cxc_fact_det_acc.cantidad,
					cxc_fact_det_acc.precio_unitario,
					cxc_fact_det_acc.costo_compra,
					cxc_fact_det_acc.id_tipo_accesorio,
					cxc_fact_det_acc.id_nota_cargo_cxc,
					NULL AS serial_carroceria,
					NULL AS serial_motor,
					NULL AS serial_chasis,
					NULL AS placa,
					NULL AS condicion_unidad,
					NULL AS id_kardex
				FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
					INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
				WHERE cxc_fact_det_acc.id_factura = %s) AS q
			LEFT JOIN cj_cc_notadecargo cxc_nd ON (q.id_nota_cargo_cxc = cxc_nd.idNotaCargo)
			LEFT JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id);",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_documento'], "int"));
	} else if ($row['id_tipo_movimiento'] == 4) { // 4 = Salida
		if ($row['tipo_documento_movimiento'] == 1) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
				1 AS cantidad,
				subtotal_factura AS precio_unitario,
				subtotal_factura AS costo_compra,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_chasis,
				uni_fis.placa,
				cond_unidad.descripcion AS condicion_unidad,
				NULL AS id_tipo_accesorio,
				kardex.idKardex AS id_kardex
			FROM an_vale_salida vale_sal
				INNER JOIN an_unidad_fisica uni_fis ON (vale_sal.id_unidad_fisica = uni_fis.id_unidad_fisica)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
				INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
				LEFT JOIN an_kardex kardex ON (vale_sal.id_vale_salida = kardex.id_documento
					AND kardex.idUnidadFisica = uni_fis.id_unidad_fisica
					AND kardex.tipoMovimiento IN (3)
					AND kardex.tipo_documento_movimiento IN (1))
			WHERE vale_sal.id_vale_salida = %s;",
				valTpDato($row['id_documento'], "int"));
		} else if ($row['tipo_documento_movimiento'] == 2) {
			$queryDetalle = sprintf("SELECT
				vw_iv_modelo.nom_uni_bas,
				CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
				1 AS cantidad,
				cxp_fact_det_unidad.costo_unitario AS precio_unitario,
				cxp_fact_det_unidad.costo_unitario AS costo_compra,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_chasis,
				uni_fis.placa,
				cond_unidad.descripcion AS condicion_unidad,
				NULL AS id_tipo_accesorio,
				kardex.idKardex AS id_kardex
			FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				INNER JOIN an_solicitud_factura ped_comp_det ON (cxp_fact_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
				INNER JOIN an_unidad_fisica uni_fis ON (cxp_fact_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad
					AND ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (cxp_fact_det_unidad.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
				INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det_unidad.id_factura = cxp_nc.id_documento AND cxp_nc.tipo_documento LIKE 'FA')
				LEFT JOIN an_kardex kardex ON (cxp_nc.id_notacredito = kardex.id_documento
					AND kardex.idUnidadFisica = uni_fis.id_unidad_fisica
					AND kardex.tipoMovimiento IN (4)
					AND kardex.tipo_documento_movimiento IN (2))
			WHERE cxp_nc.id_notacredito = %s
				AND cxp_nc.tipo_documento LIKE 'FA'
			
			UNION
			
			SELECT 
				acc.nom_accesorio,
				acc.des_accesorio,
				cxp_fact_det_acc.cantidad,
				cxp_fact_det_acc.costo_unitario AS precio_unitario,
				cxp_fact_det_acc.costo_unitario AS costo_compra,
				NULL AS serial_carroceria,
				NULL AS serial_motor,
				NULL AS serial_chasis,
				NULL AS placa,
				NULL AS condicion_unidad,
				NULL AS id_tipo_accesorio,
				NULL AS id_kardex
			FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
				INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det_acc.id_factura = cxp_nc.id_documento AND cxp_nc.tipo_documento LIKE 'FA')
			WHERE cxp_nc.id_notacredito = %s
				AND cxp_nc.tipo_documento LIKE 'FA';",
				valTpDato($row['id_documento'], "int"),
				valTpDato($row['id_documento'], "int"));
		}
	}
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	
	if ($totalRowsDetalle > 0) {
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro. Dcto:");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['numero_documento']));
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Nro. Control / Folio:");
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['numero_control_documento']));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Dcto.:");
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat, strtotime($row['fecha_documento'])));
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Fecha Registro:");
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, date(spanDateFormat, strtotime($row['fecha_registro'])));
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, (($row['estatus_documento'] == 2) ? "Venta Cerrada" : ""));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
		$objPHPExcel->getActiveSheet()->mergeCells("J".$contFila.":L".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Prov./Clnte./Emp.:");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['ci_cliente']));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_cliente']));
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['estado_documento']));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Orden:");
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['numero_pedido']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Clave Mov.:");
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['clave'].") ".$row['descripcion']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
		$objPHPExcel->getActiveSheet()->mergeCells("C".$contFila.":D".$contFila);
		
		if ($row['id_empleado_cierre'] > 0) {
			$contFila++;
			$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Observación Cierre:");
			$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['observacion_cierre']));
			$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Cierre:");
			$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat, strtotime($row['fecha_cierre'])));
			$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Empleado Cierre:");
			$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['nombre_empleado_cierre']));
			
			$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->applyFromArray($styleArrayCampo);
			
			$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			
			$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":E".$contFila);
		}
		
		$contFila++;
		$primero = $contFila;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Cantidad");
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Costo Unit.");
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $spanPrecioUnitario);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Importe Precio");
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Dscto.");
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Neto");
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Importe Costo");
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Utl.");
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "%Utl.");
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "%Dscto.");
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayColumna);
	}
	
	$arrayTotal = NULL;
	$contFila2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio_unitario'];
		$descuento = $rowDetalle['porcentaje_descuento'] * $importePrecio / 100;
		$neto = $importePrecio - $descuento;
		
		$importeCosto = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $rowDetalle['cantidad'] * $rowDetalle['costo_compra'];
		
		$porcUtilidad = 0;
		if ($importePrecio > 0) {
			$utilidad = $neto - $importeCosto;
			$porcUtilidad = $utilidad * 100 / $importePrecio;
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($rowDetalle['nom_uni_bas']));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($rowDetalle['vehiculo']));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $rowDetalle['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowDetalle['costo_compra']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $rowDetalle['precio_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $importePrecio);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $descuento);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $neto);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $importeCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $utilidad);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $porcUtilidad);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $rowDetalle['porcentaje_descuento']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotal[3] += $rowDetalle['cantidad'];
		$arrayTotal[6] += $importePrecio;
		$arrayTotal[7] += $descuento;
		$arrayTotal[8] += $neto;
		$arrayTotal[9] += $importeCosto;
		$arrayTotal[10] += $utilidad;
	}
	$ultimo = $contFila;
	
	if ($totalRowsDetalle > 0) {
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotal[3]);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotal[6]);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal[7]);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal[8]);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal[9]);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal[10]);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($arrayTotal[6] > 0) ? $arrayTotal[10] * 100 / $arrayTotal[6] : 0));
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($arrayTotal[6] > 0) ? $arrayTotal[7] * 100 / $arrayTotal[6] : 0));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
					
		$arrayTotalFinal[3] += $arrayTotal[3];
	}
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}

for ($col = "A"; $col != "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "L");

$tituloDcto = "Listado de Movimientos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:L7");

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