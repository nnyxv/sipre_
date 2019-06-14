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
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.aplica_libros = %s",
		valTpDato($valCadBusq[1], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.estatus_factura IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modo_compra = %s",
		valTpDato($valCadBusq[6], "int"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s
	OR cxp_fact.numero_control_factura LIKE %s
	OR cxp_fact.observacion_factura LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT cxp_fact.*,
	(SELECT orden_tot.id_orden_tot FROM sa_orden_tot orden_tot
	WHERE orden_tot.id_factura = cxp_fact.id_factura) AS id_orden_tot,
	
	(CASE cxp_fact.estatus_factura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_factura,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	
	(CASE id_modulo
		WHEN 1 THEN
			IFNULL((SELECT COUNT(orden_tot.id_factura)
					FROM sa_orden_tot orden_tot
						INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
					WHERE orden_tot.id_factura = cxp_fact.id_factura), 0)
		WHEN 2 THEN
			IFNULL((SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
					WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura), 0)
				+ IFNULL((SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
						WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura), 0)
		ELSE
			IFNULL((SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
					WHERE cxp_fact_det.id_factura = cxp_fact.id_factura), 0)
	END) AS cant_items,
	
	(SELECT SUM(cxp_fact_det.cantidad) FROM cp_factura_detalle cxp_fact_det
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_piezas,
	
	moneda_local.abreviacion AS abreviacion_moneda_local,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = cxp_fact.id_factura
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
	WHERE reten_cheque.id_factura = cxp_fact.id_factura
		AND reten_cheque.tipo IN (0)
		AND reten_cheque.anulado IS NULL) AS id_retencion_cheque,
	
	(SELECT
		cxp_nd.id_notacargo
	FROM cp_notadecargo cxp_nd
		INNER JOIN an_unidad_fisica uni_fis ON (cxp_nd.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
		INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		INNER JOIN pg_modulos modulo ON (cxp_nd.id_modulo = modulo.id_modulo)
	WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura) AS id_nota_cargo_planmayor,
	
	IFNULL((SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
			WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
				AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0) AS total_gastos,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	(IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total_impuestos,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) FROM cp_factura_iva cxp_fact_iva
				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
	
	cxp_fact.activa,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_factura cxp_fact
	INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
	LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxp_fact.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY id_factura DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->SetCellValue("E".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->SetCellValue("F".$contFila, "Fecha Factura Proveedor");
$objPHPExcel->getActiveSheet()->SetCellValue("G".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->SetCellValue("H".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Id Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Id Empleado Registro");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Empleado Registro");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Piezas");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Subtotal Factura");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Descuento Factura");
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, "Gastos");
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, "Impuestos");
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Z".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgPedidoModulo = ("Repuestos"); break;
		case 1 : $imgPedidoModulo = ("Servicios"); break;
		case 2 : $imgPedidoModulo = ("Vehículos"); break;
		case 3 : $imgPedidoModulo = ("Administración"); break;
		default : $imgPedidoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "Creada por CxP";
	
	switch($row['activa']) {
		case "" : $imgEstatusRegistroCompra = "Compra Registrada (Con Devolución)"; break;
		case 1 : $imgEstatusRegistroCompra = "Compra Registrada"; break;
		default : $imgEstatusRegistroCompra = "";
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusRegistroCompra);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_origen'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat, strtotime($row['fecha_vencimiento'])));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['numero_factura_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($row['id_nota_cargo_planmayor'] > 0) ? "Factura por Plan Mayor" : ""));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['id_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['rif_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['nit_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['observacion_factura']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("O".$contFila, $row['id_empleado_creador'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['nombre_empleado_creador']));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['descripcion_estado_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['cant_piezas']);
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['saldo_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['subtotal_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $row['subtotal_descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $row['total_gastos']);
	$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $row['total_impuestos']);
	$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $row['total']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Z".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);;
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	$arrayTotal['cant_documentos'] += 1;
	$arrayTotal['cant_items'] += $row['cant_items'];
	$arrayTotal['cant_piezas'] += $row['cant_piezas'];
	$arrayTotal['saldo_factura'] += $row['saldo_factura'];
	$arrayTotal['subtotal_factura'] += $row['subtotal_factura'];
	$arrayTotal['subtotal_descuento'] += $row['subtotal_descuento'];
	$arrayTotal['total_gastos'] += $row['total_gastos'];
	$arrayTotal['total_neto'] += $row['total_neto'];
	$arrayTotal['total_impuestos'] += $row['total_impuestos'];
	$arrayTotal['total'] += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":Z".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal['cant_documentos']);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal['cant_items']);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal['cant_piezas']);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal['saldo_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal['subtotal_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal['subtotal_descuento']);
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $arrayTotal['total_gastos']);
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $arrayTotal['total_neto']);
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $arrayTotal['total_impuestos']);
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $arrayTotal['total']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."P".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila.":"."Z".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":P".$contFila);

for ($col = "A"; $col != "Z"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "Z", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Factura de Compra";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:Z7");

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