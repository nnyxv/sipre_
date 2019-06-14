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
	$arrayCriterioBusqueda[] = "Estado Nota de Crédito: ".((isset($arrayEstadoNotaDebito)) ? implode(", ", $arrayEstadoNotaDebito) : "");
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
	$sqlBusq .= $cond.sprintf("(cxp_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.fecha_registro_notacredito BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.aplica_libros_notacredito = %s",
		valTpDato($valCadBusq[3], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.estado_notacredito IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT motivo.id_motivo
	FROM cp_notacredito_detalle_motivo cxp_nc_det_motivo
		INNER JOIN pg_motivo motivo ON (cxp_nc_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxp_nc_det_motivo.id_notacredito = cxp_nc.id_notacredito) IN (%s)",
		valTpDato($valCadBusq[6], "int"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nc.numero_nota_credito LIKE %s
	OR cxp_nc.numero_control_notacredito LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s
	OR cxp_nd.numero_notacargo LIKE %s
	OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR cxp_nc.observacion_notacredito LIKE %s
	OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cp_notacredito_detalle_motivo cxp_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxp_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxp_nc_det_motivo.id_notacredito = cxp_nc.id_notacredito) LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT cxp_nc.*,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	(CASE cxp_nc.estado_notacredito
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Sin Asignar'
		WHEN 2 THEN 'Asignado Parcial'
		WHEN 3 THEN 'Asignado'
	END) AS descripcion_estado_nota_credito,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cp_notacredito_detalle_motivo cxp_nc_det_motivo
		INNER JOIN pg_motivo motivo ON (cxp_nc_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxp_nc_det_motivo.id_notacredito = cxp_nc.id_notacredito) AS descripcion_motivo,
	
	(CASE
		WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
			cxp_fact.numero_factura_proveedor
		WHEN cxp_nc.tipo_documento LIKE 'ND' THEN
			cxp_nd.numero_notacargo
	END) AS numero_factura_proveedor,
	
	(CASE
		WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
			cxp_fact.numero_control_factura
		WHEN cxp_nc.tipo_documento LIKE 'ND' THEN
			cxp_nd.numero_control_notacargo
	END) AS numero_control_factura_proveedor,
	
	(CASE
		WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
			cxp_fact.fecha_factura_proveedor
		WHEN cxp_nc.tipo_documento LIKE 'ND' THEN
			cxp_nd.fecha_notacargo
	END) AS fecha_factura_proveedor,
	
	(CASE
		WHEN ((SELECT COUNT(*) FROM cp_notacredito_detalle_motivo cxp_nc_det_motivo
				WHERE cxp_nc_det_motivo.id_notacredito = cxp_nc.id_notacredito) = 0) THEN
			(CASE
				WHEN (cxp_nc.id_departamento_notacredito IN (0,3) AND cxp_fact.subtotal_factura = cxp_nc.subtotal_notacredito) THEN
					IFNULL((SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
							WHERE cxp_fact_det.id_factura = cxp_nc.id_documento), 2)
				WHEN (cxp_nc.id_departamento_notacredito IN (1) AND cxp_fact.subtotal_factura = cxp_nc.subtotal_notacredito) THEN
					IFNULL((SELECT COUNT(orden_tot.id_factura)
							FROM sa_orden_tot orden_tot
								INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
							WHERE orden_tot.id_factura = cxp_nc.id_documento), 2)
				WHEN (cxp_nc.id_departamento_notacredito IN (2) AND cxp_fact.subtotal_factura = cxp_nc.subtotal_notacredito) THEN
					IFNULL((SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
							WHERE cxp_fact_det_unidad.id_factura = cxp_nc.id_documento), 2)
						+ IFNULL((SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
								WHERE cxp_fact_det_acc.id_factura = cxp_nc.id_documento), 2)
			END)
	END) AS cant_items,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.id_nota_credito = cxp_nc.id_notacredito
	LIMIT 1) AS idRetencionCabezera,
	
	IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) FROM cp_notacredito_gastos cxp_nc_gasto
			WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
				AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0) AS total_gastos,
	
	(IFNULL(cxp_nc.subtotal_notacredito, 0)
		- IFNULL(cxp_nc.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) FROM cp_notacredito_gastos cxp_nc_gasto
				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
					AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) FROM cp_notacredito_iva cxp_nc_iva
			WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0) AS total_impuestos,
	
	(IFNULL(cxp_nc.subtotal_notacredito, 0)
		- IFNULL(cxp_nc.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) FROM cp_notacredito_gastos cxp_nc_gasto
				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
					AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) FROM cp_notacredito_iva cxp_nc_iva
				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total,
	
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_proveedor prov
	INNER JOIN cp_notacredito cxp_nc ON (prov.id_proveedor = cxp_nc.id_proveedor)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxp_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	LEFT JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento LIKE 'FA')
	LEFT JOIN cp_notadecargo cxp_nd ON (cxp_nc.id_documento = cxp_nd.id_notacargo AND cxp_nc.tipo_documento LIKE 'ND') %s
ORDER BY cxp_nc.id_notacredito DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Tipo Documento");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Fecha Factura / Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Factura / Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Nro. Control Factura / Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Id Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Motivo");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Id Empleado Registro");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Empleado Registro");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Estado Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Saldo Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, "Subtotal Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, "Descuento Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, "Gastos");
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, "Impuestos");
$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, "Total Nota de Crédito");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":AB".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_departamento_notacredito']) {
		case 0 : $imgDctoModulo = "Repuestos"; break;
		case 1 : $imgDctoModulo = "Servicios"; break;
		case 2 : $imgDctoModulo = "Vehículos"; break;
		case 3 : $imgDctoModulo = "Administración"; break;
		case 4 : $imgDctoModulo = "Alquiler"; break;
		default : $imgDctoModulo = $row['id_departamento_notacredito'];
	}
	
	$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "Creada por CxC";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgDctoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fecha_registro_notacredito'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_notacredito'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numero_nota_credito'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['numero_control_notacredito'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($row['tipo_documento'] != "NC") ? "(".$row['tipo_documento'].") " : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($row['fecha_factura_proveedor'] != "") ? date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['numero_factura_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['numero_control_factura_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, $row['id_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, $row['rif_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['nit_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['descripcion_motivo']));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, utf8_encode($row['serial_carroceria']));
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, utf8_encode($row['observacion_notacredito']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("S".$contFila, $row['id_empleado_creador'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, utf8_encode($row['nombre_empleado_creador']));
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, utf8_encode($row['descripcion_estado_nota_credito']));
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $row['saldo_notacredito']);
	$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $row['subtotal_notacredito']);
	$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $row['subtotal_descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $row['total_gastos']);
	$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, $row['total_impuestos']);
	$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, $row['total']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":AB".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AA".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("AB".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	$arrayTotal['cant_documentos'] += 1;
	$arrayTotal['saldo_notacredito'] += $row['saldo_notacredito'];
	$arrayTotal['subtotal_notacredito'] += $row['subtotal_notacredito'];
	$arrayTotal['subtotal_descuento'] += $row['subtotal_descuento'];
	$arrayTotal['total_gastos'] += $row['total_gastos'];
	$arrayTotal['total_neto'] += $row['total_neto'];
	$arrayTotal['total_impuestos'] += $row['total_impuestos'];
	$arrayTotal['total'] += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":AB".$ultimo);

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal['cant_documentos']);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal['saldo_notacredito']);
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $arrayTotal['subtotal_notacredito']);
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $arrayTotal['subtotal_descuento']);
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $arrayTotal['total_gastos']);
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $arrayTotal['total_neto']);
$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, $arrayTotal['total_impuestos']);
$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, $arrayTotal['total']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."T".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila.":"."AB".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AA".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("AB".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."T".$contFila);

for ($col = "A"; $col != "AB"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "AB", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Notas de Crédito de Cuentas por Pagar";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:AB7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:AB9");
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