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

if ($valCadBusq[2] != "") {
	$arrayCriterioBusqueda[] = "Generar al: ".$valCadBusq[2];
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstTipoDetalle = array(1 => "Detallado por Empresa", 2 => "Consolidado");
	foreach ($lstTipoDetalle as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayTipoDetalle[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado de Cuenta: ".((isset($arrayTipoDetalle)) ? implode(", ", $arrayTipoDetalle) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s);", valTpDato($valCadBusq[5], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayModulo[] = $row['descripcionModulo'];
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM tipodedocumentos WHERE abreviatura_tipo_documento IN (%s);",
		valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayTipoDcto[] = utf8_encode($row['descripcionTipoDeDocumento']);
	}
	$arrayCriterioBusqueda[] = "Tipo de Dcto.: ".((isset($arrayTipoDcto)) ? implode(", ", $arrayTipoDcto) : "");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	foreach (explode(",", $valCadBusq[7]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM gruposestadocuenta;"));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			($valor == "corriente") ? $arrayDiasVencidos[] = "Cta. Corriente" : "";
			($valor == "desde1") ? $arrayDiasVencidos[] = "De ".$row['desde1']." a ".$row['hasta1'] : "";
			($valor == "desde2") ? $arrayDiasVencidos[] = "De ".$row['desde2']." a ".$row['hasta2'] : "";
			($valor == "desde3") ? $arrayDiasVencidos[] = "De ".$row['desde3']." a ".$row['hasta3'] : "";
			($valor == "masDe") ? $arrayDiasVencidos[] = "Mas de ".$row['masDe'] : "";
		}
	}
	$arrayCriterioBusqueda[] = "Días Vencidos: ".((isset($arrayDiasVencidos)) ? implode(", ", $arrayDiasVencidos) : "");
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM cj_conceptos_formapago concepto_forma_pago WHERE concepto_forma_pago.id_concepto IN (%s);",
		valTpDato($valCadBusq[8], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayConceptoPago[] = $row['descripcion'];
	}
	$arrayCriterioBusqueda[] = "Concepto de Pago: ".((isset($arrayConceptoPago)) ? implode(", ", $arrayConceptoPago) : "");
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM pg_motivo motivo WHERE motivo.id_motivo IN (%s);", valTpDato($valCadBusq[9], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMotivo[] = $row['id_motivo'].".- ".$row['descripcion'];
	}
	$arrayCriterioBusqueda[] = "Motivo: ".((isset($arrayMotivo)) ? implode(", ", $arrayMotivo) : "");
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	foreach (explode(",", $valCadBusq[10]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM an_condicion_unidad condicion WHERE id_condicion_unidad = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayCondicion[] = $row['descripcion'];
		}
	}
	$arrayCriterioBusqueda[] = "Condición: ".((isset($arrayCondicion)) ? implode(", ", $arrayCondicion) : "");
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM fi_tipo tipo_financ WHERE tipo_financ.id_tipo IN (%s);", valTpDato($valCadBusq[11], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMotivo[] = $row['id_tipo'].".- ".$row['nombre_tipo'];
	}
	$arrayCriterioBusqueda[] = "Tipo de Financiamiento: ".((isset($arrayMotivo)) ? implode(", ", $arrayMotivo) : "");
}

if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[12];
}
	
$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
$rsGrupoEstado = mysql_query($queryGrupoEstado);
if (!$rsGrupoEstado) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);

////////// CRITERIO DE BUSQUEDA //////////
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.idCliente = %s",
		valTpDato($valCadBusq[1], "int"));
}

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
	valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));

if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(
	(CASE
		WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
			(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
					AND cxc_ant.estatus IN (1)) = 1
				AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
			
			OR
			
			(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
			WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
				AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
				AND cxc_ant.estadoAnticipo IN (4)
				AND cxc_ant.estatus IN (1)) = 1)
		WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
			((SELECT cxc_ch.estatus FROM cj_cc_cheque cxc_ch
				WHERE cxc_ch.id_cheque = vw_cxc_as.idFactura
					AND cxc_ch.estatus IN (1)) = 1
				AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
		WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
			((SELECT cxc_tb.estatus FROM cj_cc_transferencia cxc_tb
				WHERE cxc_tb.id_transferencia = vw_cxc_as.idFactura
					AND cxc_tb.estatus IN (1)) = 1
				AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
		ELSE
			ROUND(vw_cxc_as.saldoFactura, 2) > 0
	END))");
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((CASE
		WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
			(CASE
				WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			END)
			
		WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
			(vw_cxc_as.montoTotal
				- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			
		WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
			(vw_cxc_as.montoTotal
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			
		WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
			(vw_cxc_as.montoTotal
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (8)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
		
		WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
			(vw_cxc_as.montoTotal
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (2)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			
		WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
			(vw_cxc_as.montoTotal
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (4)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
	END) > 0
		AND NOT ((CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
							(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
						AND (cxc_pago.estatus IN (1)
							OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(SELECT MAX(q.fechaPago)
					FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.formaPago = 7
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
							WHERE cxc_pago.formaPago = 7
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idFormaPago = 7
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
					WHERE q.numeroDocumento = vw_cxc_as.idFactura)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT MAX(q.fechaPago)
					FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.formaPago = 8
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
							WHERE cxc_pago.formaPago = 8
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idFormaPago = 8
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							
							UNION
							
							SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_forma_pago IN (8)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
					WHERE q.numeroDocumento = vw_cxc_as.idFactura)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					(SELECT MAX(q.fechaPago)
					FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.formaPago IN (2)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
							WHERE cxc_pago.formaPago IN (2)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idFormaPago IN (2)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							
							UNION
							
							SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_forma_pago IN (2)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
					WHERE q.id_cheque = vw_cxc_as.idFactura)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					(SELECT MAX(q.fechaPago)
					FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.formaPago IN (4)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
							WHERE cxc_pago.formaPago IN (4)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							UNION
							
							SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idFormaPago IN (4)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
							
							UNION
							
							SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_forma_pago IN (4)
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
					WHERE q.id_transferencia = vw_cxc_as.idFactura)
			END) < %s
				AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
					OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	// 1 = Detallado por Empresa, 2 = Consolidado
	$groupBy = ($valCadBusq[4] == 1) ? "GROUP BY vw_cxc_as.id_empresa, vw_cxc_as.idCliente" : "GROUP BY vw_cxc_as.idCliente";
} else {
	$groupBy = "GROUP BY vw_cxc_as.id_empresa, vw_cxc_as.idCliente";
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.tipoDocumento IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$arrayDiasVencidos = NULL;
	if (in_array("corriente",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("desde1",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
		AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
															WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("desde2",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
		AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
															WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("desde3",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
		AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
															WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("masDe",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	IF (vw_cxc_as.tipoDocumento IN ('AN'),
		(SELECT cxc_pago.id_concepto
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
			AND cxc_pago.id_forma_pago IN (11))
		, NULL) IN (%s)",
		valTpDato($valCadBusq[8], "campo"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
			(SELECT motivo.id_motivo
			FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura
				AND motivo.id_motivo IN (%s))
		WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
			(SELECT motivo.id_motivo
			FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura
				AND motivo.id_motivo IN (%s))
	END) IN (%s)",
		valTpDato($valCadBusq[9], "campo"),
		valTpDato($valCadBusq[9], "campo"),
		valTpDato($valCadBusq[9], "campo"));
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
			(SELECT uni_fis.id_condicion_unidad
			FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
				INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			WHERE cxc_fact_det_vehic.id_factura = vw_cxc_as.idFactura)
		WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
			(SELECT uni_fis.id_condicion_unidad
			FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
				INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			WHERE cxc_nc_det_vehic.id_nota_credito = vw_cxc_as.idFactura)
	END) IN (%s)",
		valTpDato($valCadBusq[10], "campo"));
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (5)) THEN
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT COUNT(ped_financ.id_tipo) FROM fi_pedido ped_financ
					WHERE ped_financ.id_notadecargo_cxc = vw_cxc_as.idFactura
						AND ped_financ.id_tipo IN (%s)) > 0
				ELSE
					1
			END)
		ELSE 
			1
	END)",
		valTpDato($valCadBusq[11], "campo"));
}

if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR (CASE vw_cxc_as.idDepartamentoOrigenFactura
		WHEN 0 THEN		ped_vent.id_pedido_venta_propio
		WHEN 1 THEN		orden.numero_orden
		WHEN 2 THEN		an_ped_vent.numeracion_pedido
		ELSE			NULL
	END) LIKE %s
	OR IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
			, NULL) LIKE %s
	OR IF (vw_cxc_as.tipoDocumento IN ('AN'),
		(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
			AND cxc_pago.id_forma_pago IN (11))
		, NULL) LIKE %s
	OR (CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
		END) LIKE %s
	OR vw_cxc_as.observacionFactura LIKE %s)",
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
if (in_array($valCadBusq[3],array(1))) {
	$query = sprintf("SELECT
		vw_cxc_as.*,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		(CASE vw_cxc_as.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido,
		
		IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
		END) AS descripcion_motivo,
		
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
						IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (8)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (2)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (4)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
		END) AS total_pagos,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo vw_cxc_as
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (vw_cxc_as.numeroPedido = ped_vent.id_pedido_venta AND vw_cxc_as.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (vw_cxc_as.numeroPedido = orden.id_orden AND vw_cxc_as.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (vw_cxc_as.numeroPedido = an_ped_vent.id_pedido AND vw_cxc_as.idDepartamentoOrigenFactura = 2)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	ORDER BY CONCAT_WS(' ', cliente.nombre, cliente.apellido) ASC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
} else {
	$query = sprintf("SELECT
		vw_cxc_as.*,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo vw_cxc_as
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (vw_cxc_as.numeroPedido = ped_vent.id_pedido_venta AND vw_cxc_as.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (vw_cxc_as.numeroPedido = orden.id_orden AND vw_cxc_as.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (vw_cxc_as.numeroPedido = an_ped_vent.id_pedido AND vw_cxc_as.idDepartamentoOrigenFactura = 2)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s
	ORDER BY CONCAT_WS(' ', cliente.nombre, cliente.apellido) ASC", $sqlBusq, $groupBy);
}
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFilaY = 0;

/*$contFilaY++;
$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFilaY, "Estado de Cuenta al ".$valCadBusq[2], PHPExcel_Cell_DataType::TYPE_STRING);
$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayTitulo);*/

$contFilaY++;
$primero = $contFilaY;

if (in_array($valCadBusq[3],array(1))) {
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, "");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, "Empresa");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFilaY, "Fecha Registro");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, "Fecha Venc. Dcto.");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, "Tipo Dcto.");
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "Nro. Dcto.");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "Nro. Pedido / Orden");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY, "Id");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFilaY, "Cliente");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFilaY, "Concepto Forma de Pago");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFilaY, "Motivo");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "Observación");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "Saldo");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, "Cta. Corriente");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, "De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, "De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFilaY, "De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFilaY, "Mas de ".$rowGrupoEstado['masDe']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":S".$contFilaY)->applyFromArray($styleArrayColumna);
} else {
	$contColum = "A";
	if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, ((in_array($valCadBusq[3],array(3))) ? "Id" : ""));
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Empresa");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Fecha Registro");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Fecha Venc. Dcto.");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Tipo Dcto.");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Nro. Dcto.");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Nro. Pedido / Orden");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Id");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Cliente");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Concepto Forma de Pago");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Motivo");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Observación");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Saldo");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Cta. Corriente");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFilaY, "Mas de ".$rowGrupoEstado['masDe']);
	} else {
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "");
		($valCadBusq[4] == 1) ? $objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Empresa") : "";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Id");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Cliente");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Saldo");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Cta. Corriente");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFilaY, "Mas de ".$rowGrupoEstado['masDe']);
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($styleArrayColumna);
}
/*$objPHPExcel->getActiveSheet()->mergeCells((in_array($valCadBusq[3],array(1))) ? "A".($contFilaY-1).":O".($contFilaY-1) : "A".($contFilaY-1).":".($contColumUlt).($contFilaY-1));*/

while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$numeroSiniestro = "";
	$totalSaldo = 0;
	$totalCorriente = 0;
	$totalEntre1 = 0;
	$totalEntre2 = 0;
	$totalEntre3 = 0;
	$totalMasDe = 0;
	
	if (in_array($valCadBusq[3],array(1))) {
		$clase = (fmod($contFilaY, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		
		$fecha1 = strtotime($valCadBusq[2]);
		$fecha2 = strtotime($row['fechaVencimientoFactura']);
		
		$dias = ($row['fechaVencimientoFactura'] != "") ? ($fecha1 - $fecha2) / 86400 : "";
		
		switch($row['idDepartamentoOrigenFactura']) {
			case 0 : $imgPedidoModulo = ("Repuestos"); break;
			case 1 : $imgPedidoModulo = ("Servicios"); break;
			case 2 : $imgPedidoModulo = ("Vehículos"); break;
			case 3 : $imgPedidoModulo = ("Administración"); break;
			case 4 : $imgPedidoModulo = ("Alquiler"); break;
			case 5 : $imgPedidoModulo = ("Financiamiento"); break;
			default : $imgPedidoModulo = $row['idDepartamentoOrigenFactura'];
		}
		
		if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
			$totalSaldo += $row['montoTotal'] - $row['total_pagos'];
		} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
			$totalSaldo -= $row['montoTotal'] - $row['total_pagos'];
		}
		
		if ($dias < $rowGrupoEstado['desde1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalCorriente += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalCorriente -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde1'] && $dias <= $rowGrupoEstado['hasta1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre1 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre1 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde2'] && $dias <= $rowGrupoEstado['hasta2']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre2 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre2 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde3'] && $dias <= $rowGrupoEstado['hasta3']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre3 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre3 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else {
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalMasDe += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalMasDe -= $row['montoTotal'] - $row['total_pagos'];
			}
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, $contFila);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFilaY, $imgPedidoModulo, PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFilaY, utf8_encode($row['nombre_empresa']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFilaY, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFilaY, date(spanDateFormat, strtotime($row['fechaVencimientoFactura'])), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFilaY, utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*"), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFilaY, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFilaY, $row['numero_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFilaY, $row['idCliente'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFilaY, utf8_encode($row['nombre_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFilaY, utf8_encode($row['descripcion_concepto_forma_pago']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFilaY, utf8_encode($row['descripcion_motivo']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFilaY, utf8_encode($row['observacionFactura']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $totalSaldo);
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $totalCorriente);
		$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalEntre1);
		$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, $totalEntre2);
		$objPHPExcel->getActiveSheet()->setCellValue("R".$contFilaY, $totalEntre3);
		$objPHPExcel->getActiveSheet()->setCellValue("S".$contFilaY, $totalMasDe);
			
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":S".$contFilaY)->applyFromArray($clase);
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("R".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("S".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotalPagina['total_saldo'] += $totalSaldo;
		$arrayTotalPagina['total_corriente'] += $totalCorriente;
		$arrayTotalPagina['total_entre1'] += $totalEntre1;
		$arrayTotalPagina['total_entre2'] += $totalEntre2;
		$arrayTotalPagina['total_entre3'] += $totalEntre3;
		$arrayTotalPagina['total_mas_de'] += $totalMasDe;
	} else {
		$totalSaldoCliente = 0;
		$totalCorrienteCliente = 0;
		$totalEntre1Cliente = 0;
		$totalEntre2Cliente = 0;
		$totalEntre3Cliente = 0;
		$totalMasDeCliente = 0;
		
		$sqlBusq2 = "";
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			if ($valCadBusq[4] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s)",
					valTpDato($row['id_empresa'], "int"));
			} else {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s)",
				valTpDato($row['id_empresa'], "int"));
		}
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($row['idCliente'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
						WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
							AND cxc_ant.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
					
					OR
					
					(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
						AND cxc_ant.estadoAnticipo IN (4)
						AND cxc_ant.estatus IN (1)) = 1)
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					((SELECT cxc_ch.estatus FROM cj_cc_cheque cxc_ch
						WHERE cxc_ch.id_cheque = vw_cxc_as.idFactura
							AND cxc_ch.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					((SELECT cxc_tb.estatus FROM cj_cc_transferencia cxc_tb
						WHERE cxc_tb.id_transferencia = vw_cxc_as.idFactura
							AND cxc_tb.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				ELSE
					ROUND(vw_cxc_as.saldoFactura, 2) > 0
			END))");
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							(vw_cxc_as.montoTotal
								- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
											AND cxc_pago.fechaPago <= %s
											AND (cxc_pago.estatus IN (1)
												OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
							(vw_cxc_as.montoTotal
								- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
											AND cxc_pago.fechaPago <= %s
											AND (cxc_pago.estatus IN (1)
												OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (8)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (2)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (4)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			END) > 0
				AND NOT ((CASE
						WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
							(CASE
								WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
									(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
								WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
									(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
							END)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
							(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) 
							
						WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.numeroDocumento = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (8)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.numeroDocumento = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.id_cheque = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.id_transferencia = vw_cxc_as.idFactura)
					END) < %s
						AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
							OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
			
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxc_as.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$arrayDiasVencidos = NULL;
			if (in_array("corriente",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde1",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde2",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde3",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("masDe",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT cxc_pago.id_concepto
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) IN (%s)",
				valTpDato($valCadBusq[8], "campo"));
		}
		
		if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT motivo.id_motivo
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura
						AND motivo.id_motivo IN (%s))
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT motivo.id_motivo
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura
						AND motivo.id_motivo IN (%s))
			END) IN (%s)",
				valTpDato($valCadBusq[9], "campo"),
				valTpDato($valCadBusq[9], "campo"),
				valTpDato($valCadBusq[9], "campo"));
		}
		
		if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(SELECT uni_fis.id_condicion_unidad
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_fact_det_vehic.id_factura = vw_cxc_as.idFactura)
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT uni_fis.id_condicion_unidad
					FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_nc_det_vehic.id_nota_credito = vw_cxc_as.idFactura)
			END) IN (%s)",
				valTpDato($valCadBusq[10], "campo"));
		}
		
		if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (5)) THEN
					(CASE
						WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
							(SELECT COUNT(ped_financ.id_tipo) FROM fi_pedido ped_financ
							WHERE ped_financ.id_notadecargo_cxc = vw_cxc_as.idFactura
								AND ped_financ.id_tipo IN (%s)) > 0
						ELSE
							1
					END)
				ELSE 
					1
			END)",
				valTpDato($valCadBusq[11], "campo"));
		}
		
		if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR (CASE vw_cxc_as.idDepartamentoOrigenFactura
				WHEN 0 THEN		ped_vent.id_pedido_venta_propio
				WHEN 1 THEN		orden.numero_orden
				WHEN 2 THEN		an_ped_vent.numeracion_pedido
				ELSE			NULL
			END) LIKE %s
			OR IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
					(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
					WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																								WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
					, NULL) LIKE %s
			OR IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) LIKE %s
			OR (CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
					WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
				END) LIKE %s
			OR vw_cxc_as.observacionFactura LIKE %s)",
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"));
		}
		
		$queryEstado = sprintf("SELECT
			vw_cxc_as.*,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			(CASE vw_cxc_as.idDepartamentoOrigenFactura
				WHEN 0 THEN		ped_vent.id_pedido_venta_propio
				WHEN 1 THEN		orden.numero_orden
				WHEN 2 THEN		an_ped_vent.numeracion_pedido
				ELSE			NULL
			END) AS numero_pedido,
			
			IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
				(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
				WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
														WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																							WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
				, NULL) AS numero_siniestro,
			
			IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) AS descripcion_concepto_forma_pago,
			
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
			END) AS descripcion_motivo,
			
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
							IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (8)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
			END) AS total_pagos,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cc_antiguedad_saldo vw_cxc_as
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
			LEFT JOIN iv_pedido_venta ped_vent ON (vw_cxc_as.numeroPedido = ped_vent.id_pedido_venta AND vw_cxc_as.idDepartamentoOrigenFactura = 0)
			LEFT JOIN sa_orden orden ON (vw_cxc_as.numeroPedido = orden.id_orden AND vw_cxc_as.idDepartamentoOrigenFactura = 1)
			LEFT JOIN an_pedido an_ped_vent ON (vw_cxc_as.numeroPedido = an_ped_vent.id_pedido AND vw_cxc_as.idDepartamentoOrigenFactura = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			$sqlBusq2);
		$rsEstado = mysql_query($queryEstado);
		if (!$rsEstado) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEstado = mysql_num_rows($rsEstado);
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			if ($contFila > 1) {
				$contFilaY++;
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, " ");
			}
			
			$contFilaY++;
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->SetCellValue(($contColum++).$contFilaY, (($contFila) + (($pageNum) * $maxRows)));
			$objPHPExcel->getActiveSheet()->SetCellValue(($contColum++).$contFilaY, $row['idCliente']);
			$objPHPExcel->getActiveSheet()->SetCellValue(($contColum).$contFilaY, utf8_encode($row['nombre_cliente']));
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY)->applyFromArray($styleArrayCampo);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			
			$contColum = "C";
			$objPHPExcel->getActiveSheet()->mergeCells(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY);
			
			$contFila2 = 0;
		}
		
		while ($rowEstado = mysql_fetch_array($rsEstado)) {
			$totalSaldo = 0;
			$totalCorriente = 0;
			$totalEntre1 = 0;
			$totalEntre2 = 0;
			$totalEntre3 = 0;
			$totalMasDe = 0;
			
			$fecha1 = strtotime($valCadBusq[2]);
			$fecha2 = strtotime($rowEstado['fechaVencimientoFactura']);
			
			$dias = ($rowEstado['fechaVencimientoFactura'] != "") ? ($fecha1 - $fecha2) / 86400 : "";
			
			switch($rowEstado['idDepartamentoOrigenFactura']) {
				case 0 : $imgPedidoModulo = ("Repuestos"); break;
				case 1 : $imgPedidoModulo = ("Servicios"); break;
				case 2 : $imgPedidoModulo = ("Vehículos"); break;
				case 3 : $imgPedidoModulo = ("Administración"); break;
				case 4 : $imgPedidoModulo = ("Alquiler"); break;
				case 5 : $imgPedidoModulo = ("Financiamiento"); break;
				default : $imgPedidoModulo = $row['idDepartamentoOrigenFactura'];
			}
			
			if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalSaldo += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
			} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalSaldo -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
			}
			
			if ($dias < $rowGrupoEstado['desde1']){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalCorriente += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalCorriente -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde1']) && ($dias <= $rowGrupoEstado['hasta1'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre1 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre1 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde2']) && ($dias <= $rowGrupoEstado['hasta2'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre2 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre2 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde3']) && ($dias <= $rowGrupoEstado['hasta3'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre3 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre3 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else {
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalMasDe += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalMasDe -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			}
			
			$contColum = "A";
			if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
				$clase = (fmod($contFilaY, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
				$contFilaY++;
				$contFila2++;
				
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $contFila2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $imgPedidoModulo, PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['nombre_empresa']), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, date(spanDateFormat, strtotime($rowEstado['fechaRegistroFactura'])), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, date(spanDateFormat, strtotime($rowEstado['fechaVencimientoFactura'])), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['tipoDocumento']).(($rowEstado['idEstadoCuenta'] > 0) ? "" : "*"), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $rowEstado['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $rowEstado['numero_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $rowEstado['idCliente'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['nombre_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['descripcion_concepto_forma_pago']), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['descripcion_motivo']), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['observacionFactura']), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalSaldo);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalCorriente);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre1);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre2);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre3);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFilaY, $totalMasDe);
				
				$contColum = "A";
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($clase);
				$contColum = "A";
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);		
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
				
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			}
			
			$totalSaldoCliente += $totalSaldo;
			$totalCorrienteCliente += $totalCorriente;
			$totalEntre1Cliente += $totalEntre1;
			$totalEntre2Cliente += $totalEntre2;
			$totalEntre3Cliente += $totalEntre3;
			$totalMasDeCliente += $totalMasDe;
		}
		
		$contColum = "A";
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$contFilaY++;
			
			$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($row['nombre_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
			
			$contColum = "N";
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalSaldoCliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalCorrienteCliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre1Cliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre2Cliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre3Cliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalMasDeCliente);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":M".$contFilaY)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":M".$contFilaY);
			$contColum = "N";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($styleArrayResaltarTotal);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			
			$contColum = "N";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
		} else {
			$clase = (fmod($contFilaY, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
			$contFilaY++;
			
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $contFila);
			($valCadBusq[4] == 1) ? $objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $row['nombre_empresa'], PHPExcel_Cell_DataType::TYPE_STRING) : "";
			$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $row['idCliente'], PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($row['nombre_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalSaldoCliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalCorrienteCliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre1Cliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre2Cliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre3Cliente);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalMasDeCliente);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($clase);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			($valCadBusq[4] == 1) ? $objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT) : "";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		}
		
		$arrayTotalPagina['total_saldo'] += $totalSaldoCliente;
		$arrayTotalPagina['total_corriente'] += $totalCorrienteCliente;
		$arrayTotalPagina['total_entre1'] += $totalEntre1Cliente;
		$arrayTotalPagina['total_entre2'] += $totalEntre2Cliente;
		$arrayTotalPagina['total_entre3'] += $totalEntre3Cliente;
		$arrayTotalPagina['total_mas_de'] += $totalMasDeCliente;
	}
}
$ultimo = $contFilaY;
if (in_array($valCadBusq[3],array(1))) {
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Totales:");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $arrayTotalPagina['total_saldo']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $arrayTotalPagina['total_corriente']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $arrayTotalPagina['total_entre1']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, $arrayTotalPagina['total_entre2']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFilaY, $arrayTotalPagina['total_entre3']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFilaY, $arrayTotalPagina['total_mas_de']);
		
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":M".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":M".$contFilaY);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY.":S".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
} else {
	$contFilaY++;
	
	$contColum = "A";
	$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Totales:");
	if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
		$contColum = "N";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_saldo']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_corriente']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_entre1']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_entre2']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_entre3']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_mas_de']);
		
		$contColum = "A";
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":M".$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":M".$contFilaY);
		$contColum = "N";
	} else {
		$contColum = ($valCadBusq[4] == 1) ? "E" : "D";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_saldo']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_corriente']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_entre1']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_entre2']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_entre3']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina['total_mas_de']);
		
		$contColum = "A";
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":".(($valCadBusq[4] == 1) ? "D" : "C").$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":".(($valCadBusq[4] == 1) ? "D" : "C").$contFilaY);
		$contColum = ($valCadBusq[4] == 1) ? "E" : "D";
	}
	$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}

$objPHPExcel->getActiveSheet()->setAutoFilter((in_array($valCadBusq[3],array(1))) ? "A".$primero.":S".$ultimo : "A".$primero.":".($contColumUlt).$ultimo);

cabeceraExcel($objPHPExcel, $idEmpresa, (in_array($valCadBusq[3],array(1))) ? "S" : ($contColumUlt), true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Estado de Cuenta CxC (Antigüedad de Saldos) al ".$valCadBusq[2];
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells((in_array($valCadBusq[3],array(1))) ? "A7:S7" : "A7:".($contColumUlt)."7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells((in_array($valCadBusq[3],array(1))) ? "A9:S9" : "A9:".($contColumUlt)."9");
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