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
	$lstTipoFecha = array(1 => "De Registro", 2 => "De Anulación");
	foreach ($lstTipoFecha as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayTipoFecha[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Filtrar por Fecha: ".((isset($arrayTipoFecha)) ? implode(", ", $arrayTipoFecha) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstEstatus = array(0 => "Anulado", 1 => "Activo");
	foreach ($lstEstatus as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayEstatus[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estatus: ".((isset($arrayEstatus)) ? implode(", ", $arrayEstatus) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$lstEstadoAnticipo = array(0 => "No Cancelado", 1 => "Cancelado (No Asignado)", 2 => "Asignado Parcial", 3 => "Asignado", 4 => "No Cancelado (Asignado)");
	foreach ($lstEstadoAnticipo as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[5]))) {
			$arrayEstadoAnticipo[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Anticipo: ".((isset($arrayEstadoAnticipo)) ? implode(", ", $arrayEstadoAnticipo) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	foreach (explode(",", $valCadBusq[6]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = $row['descripcionModulo'];
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM cj_conceptos_formapago concepto_forma_pago WHERE concepto_forma_pago.id_concepto IN (%s);",
		valTpDato($valCadBusq[7], "campo")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayConceptoPago[] = $row['descripcion'];
	}
	$arrayCriterioBusqueda[] = "Concepto de Pago: ".((isset($arrayConceptoPago)) ? implode(", ", $arrayConceptoPago) : "");
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[8];
}

////////// CRITERIO DE BUSQUEDA //////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_ant.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_ant.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == "2") {
		$sqlBusq .= $cond.sprintf("DATE(cxc_ant.fecha_anulado) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else {
		$sqlBusq .= $cond.sprintf("cxc_ant.fechaAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.estatus = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.estadoAnticipo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(SELECT COUNT(cxc_pago.id_concepto)
	FROM cj_cc_detalleanticipo cxc_pago
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
	WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
		AND cxc_pago.id_forma_pago IN (11)
		AND cxc_pago.id_concepto IN (%s)) > 0",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
			AND cxc_pago.id_forma_pago IN (11)) LIKE %s
	OR cxc_ant.observacionesAnticipo LIKE %s
	OR cxc_ant.motivo_anulacion LIKE %s)",
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxc_ant.idAnticipo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_ant.montoNetoAnticipo,
	cxc_ant.totalPagadoAnticipo,
	IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
	cxc_ant.fechaAnticipo,
	cxc_ant.numeroAnticipo,
	cxc_ant.idDepartamento,
	IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
	(CASE cxc_ant.estatus
		WHEN 1 THEN
			(CASE cxc_ant.estadoAnticipo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
				WHEN 4 THEN 'No Cancelado (Asignado)'
			END)
		ELSE
			'Anulado'
	END) AS descripcion_estado_anticipo,
	cxc_ant.observacionesAnticipo,
	
	cxc_ant.id_empleado_creador,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	cxc_ant.fecha_anulado,
	cxc_ant.id_empleado_anulado,
	vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
	cxc_ant.motivo_anulacion,
	
	(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
	FROM cj_cc_detalleanticipo cxc_pago
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
	WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
		AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
	
	cxc_ec_nd.idEstadoDeCuenta AS id_estado_cuenta_nota_cargo,
	cxc_ec_nd.tipoDocumentoN AS id_tipo_documento_nota_cargo,
	cxc_ec_nd.tipoDocumento AS tipo_documento_nota_cargo,
	cxc_nd.idNotaCargo,
	cxc_nd.id_empresa,
	cxc_nd.fechaRegistroNotaCargo,
	cxc_nd.fechaVencimientoNotaCargo,
	cxc_nd.numeroNotaCargo,
	cxc_nd.numeroControlNotaCargo,
	cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo_nota_cargo,
	cxc_nd.observacionNotaCargo,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
		INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
	cxc_ant.estatus
FROM cj_cc_anticipo cxc_ant
	INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ant.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'AN')
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_ant.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_ant.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	LEFT JOIN cj_cc_notadecargo cxc_nd ON ((cxc_ant.idAnticipo = cxc_nd.id_anticipo_anulado AND cxc_nd.id_anticipo_anulado IS NOT NULL)
			OR (cxc_ant.idAnticipo = cxc_nd.id_anticipo_bono AND cxc_nd.id_anticipo_bono IS NOT NULL))
		LEFT JOIN cj_cc_estadocuenta cxc_ec_nd ON (cxc_nd.idNotaCargo = cxc_ec_nd.idDocumento AND cxc_ec_nd.tipoDocumento LIKE 'ND')
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY idAnticipo DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$queryFormaPago = ("SELECT * FROM formapagos ORDER BY nombreFormaPago ASC");
$rsFormaPago = mysql_query($queryFormaPago);
if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFormaPago = mysql_num_rows($rsFormaPago);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Formas de Pago");

$objPHPExcel->getActiveSheet()->getStyle("O".$contFila.":".ultimaColumnaExcel("O", $totalRowsFormaPago).$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("O".$contFila.":".ultimaColumnaExcel("O", $totalRowsFormaPago).$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Estatus");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Anticipo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Concepto Forma de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Motivo Anulación");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Estado Anticipo");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Saldo Anticipo");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Total Anticipo");

$contColum = "N";
while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
	$contColum++;
	
	$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowFormaPago['nombreFormaPago']);
}
$contColumUlt = $contColum;

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['idDepartamento']) {
		case 0 : $imgDctoModulo = "Repuestos"; break;
		case 1 : $imgDctoModulo = "Servicios"; break;
		case 2 : $imgDctoModulo = "Vehículos"; break;
		case 3 : $imgDctoModulo = "Administración"; break;
		case 4 : $imgDctoModulo = "Alquiler"; break;
		case 5 : $imgDctoModulo = "Financiamiento"; break;
		default : $imgDctoModulo = $row['idDepartamento'];
	}
	
	switch($row['estatus']) {
		case 0 : $imgEstatus = "Anticipo Anulado"; break;
		case 1 : $imgEstatus = "Anticipo Activo"; break;
		default : $imgEstatus = $row['estatus'];
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaAnticipo'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['numeroAnticipo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, utf8_encode($row['id_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, utf8_encode($row['ci_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, utf8_encode($row['descripcion_concepto_forma_pago']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['motivo_anulacion']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['observacionesAnticipo']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['descripcion_estado_anticipo']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['saldoAnticipo']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['montoNetoAnticipo']);
	
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$contColum = "N";
	while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
		$contColum++;
		
		$queryDctoPago = sprintf("SELECT *
		FROM (SELECT 
				cxc_pago.idAnticipo,
				cxc_pago.fechaPagoAnticipo,
				cxc_pago.id_forma_pago,
				cxc_pago.montoDetalleAnticipo
			FROM cj_cc_detalleanticipo cxc_pago) AS query
		WHERE query.idAnticipo = %s
			AND query.id_forma_pago = %s",
			valTpDato($row['idAnticipo'], "int"),
			valTpDato($rowFormaPago['idFormaPago'], "int"));
		$rsDctoPago = mysql_query($queryDctoPago);
		if (!$rsDctoPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsDctoPago = mysql_num_rows($rsDctoPago);
		$totalPagosDcto = 0;
		while ($rowDctoPago = mysql_fetch_assoc($rsDctoPago)) {
			$totalPagosDcto += $rowDctoPago['montoDetalleAnticipo'];
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $totalPagosDcto);
		
		$arrayTotalPago[$contColum] += $totalPagosDcto;
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$contColum = "N";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	}
	
	
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$contColum = "N";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	}
	
	$arrayTotal[9] += $row['saldoAnticipo'];
	$arrayTotal[10] += $row['montoNetoAnticipo'];
	$cont = 10;
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
$contColum = "L";
if (isset($arrayTotalPago)) {
	foreach ($arrayTotal as $indice => $valor) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $arrayTotal[$indice]);
		
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."L".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayResaltarTotal);;

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."L".$contFila);

for ($col = "A"; $col != ($contColumUlt); $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, ($contColumUlt), true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Anticipo";
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