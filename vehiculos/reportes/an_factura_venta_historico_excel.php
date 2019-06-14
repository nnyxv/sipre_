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
	$lstTipoFecha = array(1 => "De Registro", 2 => "De Entrega", 3 => "De Pagada", 4 => "De Cierre");
	foreach ($lstTipoFecha as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayTipoFecha[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Filtrar por Fecha: ".((isset($arrayTipoFecha)) ? implode(", ", $arrayTipoFecha) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	foreach (explode(",", $valCadBusq[4]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM vw_pg_empleados empleado WHERE id_empleado = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayVendedor[] = $row['nombre_empleado'];
		}
	}
	$arrayCriterioBusqueda[] = "Vendedor: ".((isset($arrayVendedor)) ? implode(", ", $arrayVendedor) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$lstAplicaLibro = array(0 => "No", 1 => "Si");
	foreach ($lstAplicaLibro as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[5]))) {
			$arrayAplicaLibro[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Aplica Libro: ".((isset($arrayAplicaLibro)) ? implode(", ", $arrayAplicaLibro) : "");
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
	$lstEstadoPedido = array("NO" => "Factura", "SI" => "Factura (Con Devolución)");
	foreach ($lstEstadoPedido as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[7]))) {
			$arrayEstadoPedido[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Ver: ".((isset($arrayEstadoPedido)) ? implode(", ", $arrayEstadoPedido) : "");
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$lstEstadoFactura = array(0 => "No Cancelado", 1 => "Cancelado", 2 => "Cancelado Parcial");
	foreach ($lstEstadoFactura as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[8]))) {
			$arrayEstadoFactura[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Factura: ".((isset($arrayEstadoFactura)) ? implode(", ", $arrayEstadoFactura) : "");
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	foreach (explode(",", $valCadBusq[9]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = $row['descripcionModulo'];
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	foreach (explode(",", $valCadBusq[10]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM sa_tipo_orden tipo_orden WHERE id_tipo_orden = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayTipoOrden[] = $row['nombre_tipo_orden'];
		}
	}
	$arrayCriterioBusqueda[] = "Tipo de Orden: ".((isset($arrayTipoOrden)) ? implode(", ", $arrayTipoOrden) : "");
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$lstItemFactura = array(1 => "Vehículo", 2 => "Adicionales", 3 => "Accesorios");
	foreach ($lstItemFactura as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[11]))) {
			$arrayItemFactura[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Item Facturado: ".((isset($arrayItemFactura)) ? implode(", ", $arrayItemFactura) : "");
}

if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
	$lstItemPago = array(1 => "Bono", 2 => "Trade-In", 3 => "PND", 4 => "Upside Down", 5 => "Ajuste Trade-In");
	foreach ($lstItemPago as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[12]))) {
			$arrayItemPago[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Item Pago: ".((isset($arrayItemPago)) ? implode(", ", $arrayItemPago) : "");
}

if ($valCadBusq[13] != "-1" && $valCadBusq[13] != "") {
	foreach (explode(",", $valCadBusq[13]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM an_condicion_unidad condicion WHERE id_condicion_unidad = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayCondicion[] = $row['descripcion'];
		}
	}
	$arrayCriterioBusqueda[] = "Condición: ".((isset($arrayCondicion)) ? implode(", ", $arrayCondicion) : "");
}

if ($valCadBusq[14] != "-1" && $valCadBusq[14] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[14];
}

////////// CRITERIO DE BUSQUEDA //////////
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
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[9], "campo"));
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_tipo_orden IN (%s)",
		valTpDato($valCadBusq[10], "campo"));
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$arrayBusq = "";
	if (in_array(1, explode(",",$valCadBusq[11]))) { // Vehiculo
		$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_vehic2.id_factura)
								FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic2 WHERE cxc_fact_det_vehic2.id_factura = cxc_fact.idFactura) > 0");
	}
	if (in_array(2, explode(",",$valCadBusq[11]))) { // Adicionales
		$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_acc2.id_factura)
								FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc2
									INNER JOIN an_accesorio acc ON (cxc_fact_det_acc2.id_accesorio = acc.id_accesorio)
								WHERE cxc_fact_det_acc2.id_factura = cxc_fact.idFactura
									AND acc.id_tipo_accesorio IN (1)) > 0");
	}
	if (in_array(3, explode(",",$valCadBusq[11]))) { // Accesorios
		$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_acc2.id_factura)
								FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc2
									INNER JOIN an_accesorio acc ON (cxc_fact_det_acc2.id_accesorio = acc.id_accesorio)
								WHERE cxc_fact_det_acc2.id_factura = cxc_fact.idFactura
									AND acc.id_tipo_accesorio IN (2)) > 0");
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
}

if (in_array(1, explode(",",$valCadBusq[12]))
|| in_array(2, explode(",",$valCadBusq[12]))
|| in_array(3, explode(",",$valCadBusq[12]))
|| in_array(4, explode(",",$valCadBusq[12]))
|| in_array(5, explode(",",$valCadBusq[12]))) {
	$arrayBusq = "";
	if (in_array(1, explode(",",$valCadBusq[12]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																	FROM cj_cc_anticipo cxc_ant
																		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																	WHERE cxc_pago.id_concepto IN (1,6))) > 0");
	} else if (in_array(2, explode(",",$valCadBusq[12]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																	FROM cj_cc_anticipo cxc_ant
																		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																	WHERE cxc_pago.id_concepto IN (2))) > 0");
	} else if (in_array(3, explode(",",$valCadBusq[12]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = cxc_fact.idFactura
									AND cxc_pago.formaPago IN (7)
									AND cxc_pago.estatus IN (1,2)
									AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																	FROM cj_cc_anticipo cxc_ant
																		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																	WHERE cxc_pago.id_concepto IN (7,8,9))) > 0");
	} else if (in_array(4, explode(",",$valCadBusq[12]))) {
		$arrayBusq[] = sprintf("(SELECT COUNT(tradein_cxc.id_nota_cargo_cxc)
								FROM an_pagos cxc_pago
									INNER JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
									INNER JOIN an_tradein tradein ON (cxc_ant.idAnticipo = tradein.id_anticipo)
									INNER JOIN an_tradein_cxc tradein_cxc ON (tradein.id_tradein = tradein_cxc.id_tradein
										AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
								WHERE cxc_pago.id_factura = cxc_fact.idFactura) > 0");
	} else if (in_array(5, explode(",",$valCadBusq[12]))) {
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

if ($valCadBusq[13] != "-1" && $valCadBusq[13] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
		valTpDato($valCadBusq[13], "campo"));
}

if ($valCadBusq[14] != "-1" && $valCadBusq[14] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR an_ped_vent.id_pedido LIKE %s
	OR pres_vent.id_presupuesto LIKE %s
	OR pres_vent.numeracion_presupuesto LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_chasis LIKE %s
	OR uni_fis.placa LIKE %s
	OR (CASE cxc_fact.idDepartamentoOrigenFactura
		WHEN 0 THEN		ped_vent.id_pedido_venta_propio
		WHEN 1 THEN		orden.numero_orden
		WHEN 2 THEN		an_ped_vent.numeracion_pedido
		ELSE			NULL
	END) LIKE %s
	OR cxc_fact.observacionFactura LIKE %s)",
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"),
		valTpDato("%".$valCadBusq[14]."%", "text"));
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
	CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
	CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
	cliente.tipo,
	cliente.ciudad,
	cliente.direccion,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
	cliente.reputacionCliente + 0 AS id_reputacion_cliente,
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
	cxc_fact.observacionFactura,
	cxc_fact.montoTotalFactura,
	cxc_fact.saldoFactura,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
	
	IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
			WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_impuestos,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)
		+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
					WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
		+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
					WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
	
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
	END) AS cant_accesorios
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
ORDER BY LPAD(CONVERT(numeroControl, SIGNED), 10, 0) DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$queryFormaPago = ("SELECT * FROM formapagos ORDER BY nombreFormaPago ASC");
$rsFormaPago = mysql_query($queryFormaPago);
if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFormaPago = mysql_num_rows($rsFormaPago);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("AH".$contFila, "Formas de Pago");

$objPHPExcel->getActiveSheet()->getStyle("AH".$contFila.":".ultimaColumnaExcel("AH", $totalRowsFormaPago).$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("AH".$contFila.":".ultimaColumnaExcel("AH", $totalRowsFormaPago).$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Fecha Pagada");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Fecha Cierre");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Fecha Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Fecha Reserva Venta");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Fecha de Entrega");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Nro. Pedido / Orden");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Tipo de Orden");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $spanEmail);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, "Condición");
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, "Entidad Bancaria");
$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, "Id Empleado Vendedor");
$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, "Empleado Vendedor");
$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, "Total Factura");

$contColum = "AG";
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
		case 5 : $imgDctoModulo = "Financiamiento"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['cant_items'] > 0 || $row['id_orden_tot'] > 0) ? "" : "Creada por CxC";
	
	if (in_array($row['id_modulo'],array(0))) {
		switch($row['estatus_pedido_venta']) {
			case 2 : $imgEstatusPedido = "Pedido Aprobado"; break;
			case 3 : $imgEstatusPedido = "Factura"; break;
			case 4 : $imgEstatusPedido = "Factura (Con Devolución)"; break;
			case 5 : $imgEstatusPedido = "Anulado"; break;
			default : $imgEstatusPedido = "";
		}
	} else {
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Factura";
	}
		
	switch ($row['flotilla']) {
		case 0 : $imgEstatusUnidadAsignacion = "Vehículo Normal"; break;
		case 1 : $imgEstatusUnidadAsignacion = "Vehículo por Flotilla"; break;
		default : $imgEstatusUnidadAsignacion = "";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $imgEstatusUnidadAsignacion);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat, strtotime($row['fechaVencimientoFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($row['fecha_pagada'] != "") ? date(spanDateFormat, strtotime($row['fecha_pagada'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($row['fecha_cierre'] != "") ? date(spanDateFormat, strtotime($row['fecha_cierre'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['numeroControl'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($row['fecha_pedido'] != "") ? date(spanDateFormat, strtotime($row['fecha_pedido'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, (($row['fecha_reserva_venta'] != "") ? date(spanDateFormat, strtotime($row['fecha_reserva_venta'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, (($row['fecha_entrega'] != "") ? date(spanDateFormat, strtotime($row['fecha_entrega'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("O".$contFila, $row['numero_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['nombre_tipo_orden']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("Q".$contFila, $row['id_cliente'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("R".$contFila, utf8_encode($row['ci_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, utf8_encode($row['telf']));
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, utf8_encode($row['correo']));
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, utf8_encode($row['observacionFactura']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("W".$contFila, $row['serial_carroceria'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $row['condicion_unidad']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("Y".$contFila, $row['placa'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, utf8_encode($row['nombreBanco']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("AA".$contFila, $row['id_empleado_vendedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, utf8_encode($row['nombre_empleado_vendedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, utf8_encode($row['descripcion_estado_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, $row['saldoFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, $row['montoTotalFactura']);
	
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$contColum = "AG";
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
			$totalPagosDcto += $rowDctoPago['montoPagado'];
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $totalPagosDcto);
		
		$arrayTotalPago[$contColum] += $totalPagosDcto;
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("AA".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("AB".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("AC".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("AD".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("AE".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("AF".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("AG".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$contColum = "AG";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	}
	
	
	$objPHPExcel->getActiveSheet()->getStyle("AE".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("AF".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AG".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$contColum = "AG";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	}
	
	$arrayTotal['cant_items'] += $row['cant_items'];
	$arrayTotal['saldoFactura'] += $row['saldoFactura'];
	$arrayTotal['montoTotalFactura'] += $row['montoTotalFactura'];
	if (isset($arrayTotalPago)) {
		foreach ($arrayTotalPago as $indice => $valor) {
			$arrayTotal[$indice] = $arrayTotalPago[$indice];
		}
	}
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".($contColumUlt).$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$contColum = "AD";
if (isset($arrayTotalPago)) {
	foreach ($arrayTotal as $indice => $valor) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $arrayTotal[$indice]);
		
		if ($indice == 'cant_items') {
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		} else {
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
		}
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."AD".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("AE".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."AD".$contFila);

for ($col = "A"; $col != ($contColumUlt); $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, ($contColumUlt), true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Factura de Venta";
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