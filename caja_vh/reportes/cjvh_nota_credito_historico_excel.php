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
	$lstEstadoNotaCredito = array(0 => "No Cancelado", 1 => "Cancelado (No Asignado)", 2 => "Asignado Parcial", 3 => "Asignado");
	foreach ($lstEstadoNotaCredito as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[5]))) {
			$arrayEstadoNotaCredito[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Nota de Crédito: ".((isset($arrayEstadoNotaCredito)) ? implode(", ", $arrayEstadoNotaCredito) : "");
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
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[7];
}

////////// CRITERIO DE BUSQUEDA //////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_nc.estatus_nota_credito IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.fechaNotaCredito BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.id_empleado_vendedor IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.aplicaLibros = %s",
		valTpDato($valCadBusq[4], "boolean"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.estadoNotaCredito IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nc.numeracion_nota_credito LIKE %s
	OR cxc_nc.numeroControl LIKE %s
	OR cxc_fact.numeroFactura LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR cxc_nc.observacionesNotaCredito LIKE %s
	OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	cxc_ec.idEstadoDeCuenta AS id_estado_cuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxc_nc.idNotaCredito,
	cxc_nc.id_empresa,
	cxc_nc.fechaNotaCredito,
	cxc_nc.numeracion_nota_credito,
	cxc_nc.numeroControl,
	cxc_nc.idDepartamentoNotaCredito AS id_modulo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_nc.estadoNotaCredito,
	(CASE cxc_nc.estadoNotaCredito
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado No Asignado'
		WHEN 2 THEN 'Asignado Parcial'
		WHEN 3 THEN 'Asignado'
	END) AS descripcion_estado_nota_credito,
	cxc_nc.aplicaLibros,
	
	cxc_ec2.idEstadoDeCuenta AS id_estado_cuenta_factura,
	cxc_ec2.tipoDocumentoN AS tipo_documento_n_factura,
	cxc_ec2.tipoDocumento AS tipo_documento_factura,
	cxc_fact.idFactura,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl AS numero_control_factura,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo_factura,
	
	cxc_nc.subtotalNotaCredito,
	cxc_nc.subtotal_descuento,
	(IFNULL(cxc_nc.subtotalNotaCredito, 0)
		- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
	IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
			WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
	cxc_nc.montoNetoNotaCredito,
	cxc_nc.saldoNotaCredito,
	cxc_nc.observacionesNotaCredito,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
		INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo,
	
	(CASE cxc_nc.idDepartamentoNotaCredito
		WHEN 0 THEN
			IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
					WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
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
			(IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
					WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
				+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
						WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0))
		WHEN 3 THEN
			IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
					WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
	END) AS cant_items,
	
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_notacredito cxc_nc
	INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'NC')
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	LEFT JOIN cj_cc_estadocuenta cxc_ec2 ON (cxc_fact.idFactura = cxc_ec2.idDocumento AND cxc_ec2.tipoDocumento LIKE 'FA')
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY cxc_nc.idNotaCredito DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Fecha Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Motivo");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Estado Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Saldo Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Total Nota de Crédito");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":R".$contFila)->applyFromArray($styleArrayColumna);

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
	
	$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "Creada por CxC";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgDctoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaNotaCredito'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['numeracion_nota_credito'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numeroControl'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, ((strtotime($row['fechaRegistroFactura'])) ? date(spanDateFormat, strtotime($row['fechaRegistroFactura'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['id_cliente'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['ci_cliente'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['observacionesNotaCredito']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['descripcion_estado_nota_credito']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['saldoNotaCredito']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['total']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":R".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	
	$arrayTotal[12] += $row['cant_items'];
	$arrayTotal[13] += $row['saldoNotaCredito'];
	$arrayTotal[14] += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":O".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $arrayTotal[12]);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal[13]);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal[14]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."O".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila.":"."R".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."O".$contFila);

for ($col = "A"; $col != "R"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "R", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Notas de Crédito de Venta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:R7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:R9");
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