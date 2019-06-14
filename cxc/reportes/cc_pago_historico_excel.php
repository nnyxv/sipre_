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
	$rs = mysql_query(sprintf("SELECT * FROM tipodedocumentos WHERE idTipoDeDocumento IN (%s);", valTpDato($valCadBusq[3], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayTipoDcto[] = $row['descripcionTipoDeDocumento'];
	}
	$arrayCriterioBusqueda[] = "Tipo de Dcto.: ".((isset($arrayTipoDcto)) ? implode(", ", $arrayTipoDcto) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s);", valTpDato($valCadBusq[4], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayModulo[] = $row['descripcionModulo'];
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM formapagos WHERE idFormaPago IN (%s);", valTpDato($valCadBusq[5], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayFormaPago[] = $row['nombreFormaPago'];
	}
	$arrayCriterioBusqueda[] = "Forma de Pago: ".((isset($arrayFormaPago)) ? implode(", ", $arrayFormaPago) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[6];
}

////////// CRITERIO DE BUSQUEDA //////////
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (0,1,3)
AND cxc_pago.estatus IN (1,2)");

$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2,4,5)
AND cxc_pago.estatus IN (1,2)");

$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
$sqlBusq3 .= $cond.sprintf("cxc_pago.estatus IN (1,2)");

$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
$sqlBusq4 .= $cond.sprintf("cxc_pago.estatus IN (1,2)");

$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
$sqlBusq5 .= $cond.sprintf("cxc_ch.estatus IN (1,2)");

$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
$sqlBusq6 .= $cond.sprintf("cxc_tb.estatus IN (1,2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(cxc_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("(cxc_ant.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_ant.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("(cxc_ch.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_ch.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("(cxc_tb.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_tb.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_pago.fechaPagoAnticipo BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("cxc_ch.fecha_cheque BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxc_tb.fecha_transferencia BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.idTipoDeDocumento IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("cxc_ch.id_departamento IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxc_tb.id_departamento IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_pago.idFormaPago ",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_pago.id_forma_pago IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("2 IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("4 IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
	
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("query.idFormaPago IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR (CASE cxc_pago.formaPago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) LIKE %s
	OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
			AND cxc_pago.id_forma_pago IN (11)) LIKE %s
	OR (SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
			AND deposito_det.idTipoDocumento = 1
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR (CASE cxc_pago.formaPago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) LIKE %s
	OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
			AND cxc_pago.id_forma_pago IN (11)) LIKE %s
	OR (SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
			AND deposito_det.idTipoDocumento = 1
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
	OR cxc_nd.numeroControlNotaCargo LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR (CASE cxc_pago.idFormaPago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) LIKE %s
	OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
			AND cxc_pago.id_forma_pago IN (11)) LIKE %s
	OR (SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.id_det_nota_cargo
			AND deposito_det.idTipoDocumento = 2
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR (CASE cxc_pago.id_forma_pago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			ELSE
				cxc_pago.numeroControlDetalleAnticipo
		END) LIKE %s
	OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_pago.numeroControlDetalleAnticipo
			AND cxc_pago.id_forma_pago IN (11)) LIKE %s
	OR (SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idDetalleAnticipo
			AND deposito_det.idTipoDocumento = 4
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("(cxc_ch.numero_cheque LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR (SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_ch.id_cheque
			AND deposito_det.idTipoDocumento = 5
			AND deposito_det.idCaja = cxc_ch.idCaja
			AND deposito_det.anulada LIKE 'NO') LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("(cxc_tb.numero_transferencia LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR (SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_tb.id_transferencia
			AND deposito_det.idTipoDocumento = 6
			AND deposito_det.idCaja = cxc_tb.idCaja
			AND deposito_det.anulada LIKE 'NO') LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
	
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("(query.numeroFactura LIKE %s
	OR query.numeroControl LIKE %s
	OR query.numero_documento LIKE %s
	OR query.ci_cliente LIKE %s
	OR query.nombre_cliente LIKE %s
	OR query.descripcion_concepto_forma_pago LIKE %s
	OR query.numero_deposito LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT query.*,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM (SELECT
		cxc_fact.idFactura AS id_documento_pagado,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idCliente,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		cxc_fact.condicionDePago,
		cxc_fact.numeroPedido,
		1 AS idTipoDeDocumento,
		'FA' AS tipoDocumento,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
		cxc_fact.saldoFactura,
		cxc_fact.montoTotalFactura,
		cxc_pago.idPago,
		
		(CASE cxc_pago.formaPago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) AS numero_documento,
		
		NULL AS id_concepto,
		cxc_pago.montopagado,
		recibo.idComprobante AS id_recibo_pago,
		recibo.numeroComprobante AS nro_comprobante,
		forma_pago.idFormaPago,
		forma_pago.nombreFormaPago,
		
		(CASE cxc_pago.formaPago
			WHEN 7 THEN
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo det_anticipo
					INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
					INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
				WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
					AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
			ELSE
				NULL
		END) AS descripcion_concepto_forma_pago,
		
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_pago.fechapago,
		
		(SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
			AND deposito_det.idTipoDocumento = 1
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
		
		cxc_pago.fecha_anulado,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco_origen ON (cxc_pago.bancoOrigen = banco_origen.idBanco)
		INNER JOIN bancos banco_destino ON (cxc_pago.bancoDestino = banco_destino.idBanco)
		INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
		INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1) %s
	
	UNION
	
	SELECT
		cxc_fact.idFactura AS id_documento_pagado,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idCliente,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		cxc_fact.condicionDePago,
		cxc_fact.numeroPedido,
		1 AS idTipoDeDocumento,
		'FA' AS tipoDocumento,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
		cxc_fact.saldoFactura,
		cxc_fact.montoTotalFactura,
		cxc_pago.idPago,
		
		(CASE cxc_pago.formaPago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) AS numero_documento,
		
		NULL AS id_concepto,
		cxc_pago.montopagado,
		recibo.idComprobante AS id_recibo_pago,
		recibo.numeroComprobante AS nro_comprobante,
		forma_pago.idFormaPago,
		forma_pago.nombreFormaPago,
		
		(CASE cxc_pago.formaPago
			WHEN 7 THEN
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo det_anticipo
					INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
					INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
				WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
					AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
			ELSE
				NULL
		END) AS descripcion_concepto_forma_pago,
		
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_pago.fechapago,
		
		(SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
			AND deposito_det.idTipoDocumento = 1
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
		
		cxc_pago.fecha_anulado,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco_origen ON (cxc_pago.bancoOrigen = banco_origen.idBanco)
		INNER JOIN bancos banco_destino ON (cxc_pago.bancoDestino = banco_destino.idBanco)
		INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
		INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1) %s
	
	UNION
	
	SELECT 
		cxc_nd.idNotaCargo,
		cxc_nd.id_empresa,
		cxc_nd.fechaRegistroNotaCargo,
		cxc_nd.numeroNotaCargo,
		cxc_nd.numeroControlNotaCargo,
		cxc_nd.idCliente,
		cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
		0 AS condicionDePago,
		NULL AS numeroPedido,
		2 AS idTipoDeDocumento,
		'ND' AS tipoDocumento,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 2) AS tipo_documento_pagado,
		cxc_nd.saldoNotaCargo,
		cxc_nd.montoTotalNotaCargo,
		cxc_pago.id_det_nota_cargo AS idPago,
				
		(CASE cxc_pago.idFormaPago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			ELSE
				cxc_pago.numeroDocumento
		END) AS numero_documento,
		
		NULL AS id_concepto,
		cxc_pago.monto_pago,
		recibo.idComprobante AS id_recibo_pago,
		recibo.numeroComprobante AS nro_comprobante,
		forma_pago.idFormaPago,
		forma_pago.nombreFormaPago,
		
		(CASE cxc_pago.idFormaPago
			WHEN 7 THEN
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo det_anticipo
					INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
					INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
				WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
					AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
			ELSE
				NULL
		END) AS descripcion_concepto_forma_pago,
		
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_pago.fechapago,
		
		(SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.id_det_nota_cargo
			AND deposito_det.idTipoDocumento = 2
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
		
		cxc_pago.fecha_anulado,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
		INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
		INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco_origen ON (cxc_pago.bancoOrigen = banco_origen.idBanco)
		INNER JOIN bancos banco_destino ON (cxc_pago.bancoDestino = banco_destino.idBanco)
		INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
		INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND recibo.idTipoDeDocumento = 2) %s
	
	UNION
	
	SELECT
		cxc_ant.idAnticipo,
		cxc_ant.id_empresa,
		cxc_ant.fechaAnticipo,
		cxc_ant.numeroAnticipo,
		'-' AS numeroControl,
		cxc_ant.idCliente,
		cxc_ant.idDepartamento AS id_modulo,
		0 AS condicionDePago,
		NULL AS numeroPedido,
		4 AS idTipoDeDocumento,
		'AN' AS tipoDocumento,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 4) AS tipo_documento_pagado,
		cxc_ant.saldoAnticipo,
		cxc_ant.montoNetoAnticipo,
		cxc_pago.idDetalleAnticipo AS idPago,
				
		(CASE cxc_pago.id_forma_pago
			WHEN 7 THEN
				(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
			WHEN 8 THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			ELSE
				cxc_pago.numeroControlDetalleAnticipo
		END) AS numero_documento,
		
		cxc_pago.id_concepto,
		cxc_pago.montoDetalleAnticipo,
		recibo.idReporteImpresion AS id_recibo_pago,
		recibo.numeroReporteImpresion AS nro_comprobante,
		forma_pago.idFormaPago,
		forma_pago.nombreFormaPago,
		
		(CASE cxc_pago.id_forma_pago
			WHEN 7 THEN
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo det_anticipo
					INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
					INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
				WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo
					AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
			ELSE
				concepto_forma_pago.descripcion
		END) AS descripcion_concepto_forma_pago,
		
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_pago.fechaPagoAnticipo,
		
		(SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idDetalleAnticipo
			AND deposito_det.idTipoDocumento = 4
			AND deposito_det.idCaja = cxc_pago.idCaja
			AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
		
		cxc_pago.fecha_anulado,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
		LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		INNER JOIN bancos banco_origen ON (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
		INNER JOIN bancos banco_destino ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
		INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion) %s
	
	UNION
	
	SELECT
		cxc_ch.id_cheque,
		cxc_ch.id_empresa,
		cxc_ch.fecha_cheque,
		cxc_ch.numero_cheque,
		'-' AS numeroControl,
		cxc_ch.id_cliente,
		cxc_ch.id_departamento AS id_modulo,
		1 AS condicionDePago,
		NULL AS numeroPedido,
		5 AS idTipoDeDocumento,
		'CH' AS tipoDocumento,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 5) AS tipo_documento_pagado,
		cxc_ch.saldo_cheque,
		cxc_ch.monto_neto_cheque,
		cxc_ch.id_cheque AS idPago,
		'-' AS numero_documento,
		NULL AS id_concepto,
		cxc_ch.monto_neto_cheque,
		recibo.idReporteImpresion AS id_recibo_pago,
		recibo.numeroReporteImpresion AS nro_comprobante,
		2 AS idFormaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
		NULL AS descripcion_concepto_forma_pago,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_ch.fecha_cheque,
		
		(SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_ch.id_cheque
			AND deposito_det.idTipoDocumento = 5
			AND deposito_det.idCaja = cxc_ch.idCaja
			AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
		
		cxc_ch.fecha_anulado,
		cxc_ch.estatus AS estatus_pago
	FROM cj_cc_cheque cxc_ch
		INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
		INNER JOIN bancos banco_origen on (cxc_ch.id_banco_cliente = banco_origen.idBanco)
		INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH') %s
	
	UNION
	
	SELECT
		cxc_tb.id_transferencia,
		cxc_tb.id_empresa,
		cxc_tb.fecha_transferencia,
		cxc_tb.numero_transferencia,
		'-' AS numeroControl,
		cxc_tb.id_cliente,
		cxc_tb.id_departamento AS id_modulo,
		1 AS condicionDePago,
		NULL AS numeroPedido,
		6 AS idTipoDeDocumento,
		'TB' AS tipoDocumento,
		(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 6) AS tipo_documento_pagado,
		cxc_tb.saldo_transferencia,
		cxc_tb.monto_neto_transferencia,
		cxc_tb.id_transferencia AS idPago,
		'-' AS numero_documento,
		NULL AS id_concepto,
		cxc_tb.monto_neto_transferencia,
		recibo.idReporteImpresion AS id_recibo_pago,
		recibo.numeroReporteImpresion AS nro_comprobante,
		4 AS idFormaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
		NULL AS descripcion_concepto_forma_pago,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_tb.fecha_transferencia,
		
		(SELECT deposito_det.numeroDeposito
		FROM an_encabezadodeposito deposito
			INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_tb.id_transferencia
			AND deposito_det.idTipoDocumento = 6
			AND deposito_det.idCaja = cxc_tb.idCaja
			AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
		
		cxc_tb.fecha_anulado,
		cxc_tb.estatus AS estatus_pago
	FROM cj_cc_transferencia cxc_tb
		INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
		INNER JOIN bancos banco_origen on (cxc_tb.id_banco_cliente = banco_origen.idBanco)
		INNER JOIN pg_reportesimpresion recibo ON (cxc_tb.id_transferencia = recibo.idDocumento AND cxc_tb.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'TB') %s
	) AS query
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (query.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY CONCAT(query.fechapago, query.idPago) DESC", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Tipo de Dcto.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Dcto.");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Fecha Pago");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Nro. Recibo");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Forma de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Estatus");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Nro. Planilla Depósito");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Monto Pagado");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Saldo Dcto.");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Total Dcto.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($styleArrayColumna);

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
	
	$classPago = "";
	$estatusPago = "";
	if ($row['estatus_pago'] == NULL && $row['fechaPago'] == $row['fecha_anulado']){ // Null = Anulado, 1 = Activo, 2 = Pendiente
		$classPago = "divMsjError";
		$estatusPago = " PAGO ANULADO";
	} else if ($row['estatus_pago'] == 2) {
		$classPago = "divMsjAlerta";
		$estatusPago = " PAGO PENDIENTE";
	} else if (in_array($row['id_concepto'], array(6,7,8))) {
		$classPago = "divMsjAlerta";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, ($row['tipo_documento_pagado']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numeroControl'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['id_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, date(spanDateFormat, strtotime($row['fechapago'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, $row['nro_comprobante'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['nombreFormaPago'].((strlen($row['descripcion_concepto_forma_pago']) > 0) ? " (".utf8_encode($row['descripcion_concepto_forma_pago']).")" : "")));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $estatusPago);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("O".$contFila, $row['numero_documento'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("P".$contFila, $row['numero_deposito'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['montopagado']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['saldoFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['montoTotalFactura']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	$arrayTotal[14] += $row['montopagado'];
	$arrayTotal[15] += $row['saldoFactura'];
	$arrayTotal[16] += $row['montoTotalFactura'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":S".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal[14]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."P".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila.":"."S".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."P".$contFila);

for ($col = "A"; $col != "S"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "S", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Pagos Cargados";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:S7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:S9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

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