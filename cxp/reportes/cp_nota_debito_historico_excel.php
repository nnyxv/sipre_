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
	$lstAplicaLibro = array(0 => "No", 1 => "Si");
	foreach ($lstAplicaLibro as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayAplicaLibro[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Aplica Libro: ".((isset($arrayAplicaLibro)) ? implode(", ", $arrayAplicaLibro) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstEstadoNotaDebito = array(0 => "No Cancelado", 1 => "Cancelado", 2 => "Cancelado Parcial");
	foreach ($lstEstadoNotaDebito as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayEstadoNotaDebito[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Nota de Débito: ".((isset($arrayEstadoNotaDebito)) ? implode(", ", $arrayEstadoNotaDebito) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	foreach (explode(",", $valCadBusq[5]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = $row['descripcionModulo'];
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM pg_motivo motivo WHERE motivo.id_motivo = %s;", valTpDato($valCadBusq[6], "int")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMotivo[] = $row['descripcion'];
	}
	$arrayCriterioBusqueda[] = "Motivo: ".((isset($arrayMotivo)) ? implode(", ", $arrayMotivo) : "");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[7];
}

////////// CRITERIO DE BUSQUEDA //////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nd.fecha_origen_notacargo BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nd.aplica_libros_notacargo = %s",
		valTpDato($valCadBusq[3], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nd.estatus_notacargo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT motivo.id_motivo
	FROM cp_notacargo_detalle_motivo cxp_nd_det_motivo
		INNER JOIN pg_motivo motivo ON (cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxp_nd_det_motivo.id_notacargo = cxp_nd.id_notacargo) IN (%s)",
		valTpDato($valCadBusq[6], "int"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nd.numero_notacargo LIKE %s
	OR cxp_nd.numero_control_notacargo LIKE %s
	OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR cxp_nd.observacion_notacargo LIKE %s
	OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cp_notacargo_detalle_motivo cxp_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxp_nd_det_motivo.id_notacargo = cxp_nd.id_notacargo) LIKE %s
	OR (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT cxp_nd.*,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	(CASE cxp_nd.estatus_notacargo
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_nota_cargo,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cp_notacargo_detalle_motivo cxp_nd_det_motivo
		INNER JOIN pg_motivo motivo ON (cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxp_nd_det_motivo.id_notacargo = cxp_nd.id_notacargo) AS descripcion_motivo,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.id_nota_cargo = cxp_nd.id_notacargo
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT
		cxp_fact.id_factura
	FROM an_unidad_fisica uni_fis
		INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		INNER JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) AS id_factura_planmayor,
	
	(SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
	WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) AS serial_carroceria,
	
	IFNULL((SELECT SUM(cxp_nd_gasto.monto) FROM cp_notacargo_gastos cxp_nd_gasto
			WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
				AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0) AS total_gastos,
	
	(IFNULL(cxp_nd.subtotal_notacargo, 0)
		- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
		+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) FROM cp_notacargo_gastos cxp_nd_gasto
				WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
					AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) FROM cp_notacargo_iva cxp_nd_iva
			WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0) AS total_impuestos,
	
	(IFNULL(cxp_nd.subtotal_notacargo, 0)
		- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
		+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) FROM cp_notacargo_gastos cxp_nd_gasto
				WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
					AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) FROM cp_notacargo_iva cxp_nd_iva
				WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0)) AS total,
	
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_proveedor prov
	INNER JOIN cp_notadecargo cxp_nd ON (prov.id_proveedor = cxp_nd.id_proveedor)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxp_nd.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY cxp_nd.id_notacargo DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Venc. Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Id Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Motivo");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Id Empleado Registro");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Empleado Registro");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Estado Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Saldo Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Subtotal Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Descuento Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, "Gastos");
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, "Impuestos");
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, "Total Nota de Débito");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Z".$contFila)->applyFromArray($styleArrayColumna);

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
	$imgPlanMayor = ($row['id_factura_planmayor'] > 0 || $row['id_detalles_pedido_compra'] > 0) ? "Nota de Débito de Factura por Plan Mayor" : "";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgDctoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fecha_origen_notacargo'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_notacargo'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_vencimiento_notacargo'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $imgPlanMayor);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numero_notacargo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['numero_control_notacargo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['id_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['rif_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['nit_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['descripcion_motivo']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['serial_carroceria']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['observacion_notacargo']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("Q".$contFila, $row['id_empleado_creador'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, utf8_encode($row['nombre_empleado_creador']));
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, utf8_encode($row['descripcion_estado_nota_cargo']));
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['saldo_notacargo']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['subtotal_notacargo']);
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $row['subtotal_descuento_notacargo']);
	$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $row['total_gastos']);
	$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $row['total_impuestos']);
	$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $row['total']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Z".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	$arrayTotal['cant_documentos'] += 1;
	$arrayTotal['saldo_notacargo'] += $row['saldo_notacargo'];
	$arrayTotal['subtotal_notacargo'] += $row['subtotal_notacargo'];
	$arrayTotal['subtotal_descuento_notacargo'] += $row['subtotal_descuento_notacargo'];
	$arrayTotal['total_gastos'] += $row['total_gastos'];
	$arrayTotal['total_neto'] += $row['total_neto'];
	$arrayTotal['total_impuestos'] += $row['total_impuestos'];
	$arrayTotal['total'] += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":Y".$ultimo);

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal['cant_documentos']);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal['saldo_notacargo']);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal['subtotal_notacargo']);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal['subtotal_descuento_notacargo']);
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $arrayTotal['total_gastos']);
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $arrayTotal['total_neto']);
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $arrayTotal['total_impuestos']);
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $arrayTotal['total']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."R".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila.":"."Z".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."R".$contFila);

for ($col = "A"; $col != "Z"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "Z", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Notas de Débito de Cuentas por Pagar";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:Z7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:Z9");
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