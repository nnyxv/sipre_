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
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (0)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.id_empresa = %s",
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
	$sqlBusq .= $cond.sprintf("ped_vent.id_empleado_preparador IN (%s)",
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
	$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
	WHERE vw_pg_clave_movimiento.tipo = 3
		AND mov.id_documento = cxc_fact.idFactura) IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR ped_vent.id_pedido_venta_propio LIKE %s
	OR ped_vent.id_pedido_venta_referencia LIKE %s
	OR pres_vent.numero_siniestro LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cxc_fact.idCliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta)
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN iv_presupuesto_venta pres_vent ON (ped_vent.id_presupuesto_venta = pres_vent.id_presupuesto_venta) %s
ORDER BY numeroControl DESC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idCliente = %s",
		valTpDato($row['idCliente'], "int"));
	
	$queryFacturaVenta = sprintf("SELECT *,
		
		(SELECT COUNT(cxc_fact_det.id_factura)
		FROM cj_cc_factura_detalle cxc_fact_det
		WHERE cxc_fact_det.id_factura = cxc_fact.idFactura) AS cant_items,
		
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)
			+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
					FROM iv_pedido_venta_gasto ped_gasto
					WHERE (ped_gasto.id_pedido_venta = ped_vent.id_pedido_venta)), 0)
		) AS subtotal_neto,
		
		IFNULL((CASE
			WHEN (ped_vent.estatus_pedido_venta IS NULL) THEN
				calculoIvaFactura
			WHEN (ped_vent.estatus_pedido_venta IS NOT NULL) THEN
				(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_venta_iva ped_iva
					WHERE (ped_iva.id_pedido_venta = ped_vent.id_pedido_venta))
		END), 0) AS subtotal_iva,
		
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)
			+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
					FROM iv_pedido_venta_gasto ped_gasto
					WHERE (ped_gasto.id_pedido_venta = ped_vent.id_pedido_venta)), 0)
			+ IFNULL((CASE
						WHEN (ped_vent.estatus_pedido_venta IS NULL) THEN
							calculoIvaFactura
						WHEN (ped_vent.estatus_pedido_venta IS NOT NULL) THEN
							(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
								FROM iv_pedido_venta_iva ped_iva
								WHERE (ped_iva.id_pedido_venta = ped_vent.id_pedido_venta))
					END), 0)
		) AS total_factura
		
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN iv_presupuesto_venta pres_vent ON (ped_vent.id_presupuesto_venta = pres_vent.id_presupuesto_venta)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.id_empleado_preparador = vw_pg_empleado.id_empleado) %s %s
	ORDER BY numeroControl DESC", $sqlBusq, $sqlBusq2);
	$rsFacturaVenta = mysql_query($queryFacturaVenta);
	if (!$rsFacturaVenta) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFacturaVenta = mysql_num_rows($rsFacturaVenta);
	
	if ($totalRowsFacturaVenta > 0) {
		$contFila++;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Cliente:");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_cliente']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":M".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $spanClienteCxC);
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['ci_cliente']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":M".$contFila);
		
		$contFila++;
		$primero = $contFila;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Fecha");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Departamento");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Nro. Factura");
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Nro. Control");
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Pedido");
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Referencia");
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Estatus");
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Tipo Pago");
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Vendedor");
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Items");
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Total Neto");
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Impuesto");
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Total Factura");
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);
	}
	
	$arrayTotal = NULL;
	$contFila2 = 0;
	while ($rowFacturaVenta = mysql_fetch_assoc($rsFacturaVenta)) {
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		switch($rowFacturaVenta['idDepartamentoOrigenFactura']) {
			case 0 : $imgDctoModulo = "Repuestos"; break;
			case 1 : $imgDctoModulo = "Servicios"; break;
			case 2 : $imgDctoModulo = "Vehículos"; break;
			case 3 : $imgDctoModulo = "Administración"; break;
			default : $imgDctoModulo = $row['idDepartamentoOrigenFactura'];
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, date(spanDateFormat,strtotime($rowFacturaVenta['fechaRegistroFactura'])));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($imgDctoModulo));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($rowFacturaVenta['numeroFactura']));
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($rowFacturaVenta['numeroControl']));
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($rowFacturaVenta['id_pedido_venta_propio']));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($rowFacturaVenta['id_pedido_venta_referencia']));
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, (($rowFacturaVenta['anulada'] == "NO") ? "": "ANULADA"));
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($rowFacturaVenta['condicionDePago'] == 0) ? "CRÉDITO": "CONTADO"));
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $rowFacturaVenta['nombre_empleado']);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $rowFacturaVenta['cant_items']);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $rowFacturaVenta['subtotal_neto']);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $rowFacturaVenta['subtotal_iva']);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $rowFacturaVenta['total_factura']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		if ($rowFacturaVenta['anulada'] == "NO") {
			$arrayTotal[2] += 1;
			$arrayTotal[9] += $rowFacturaVenta['cant_items'];
			$arrayTotal[10] += $rowFacturaVenta['subtotal_neto'];
			$arrayTotal[11] += $rowFacturaVenta['subtotal_iva'];
			$arrayTotal[12] += $rowFacturaVenta['total_factura'];
		}
	}
	$ultimo = $contFila;
	
	if ($totalRowsFacturaVenta > 0) {
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotal[2]);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal[9]);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotal[10]);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotal[11]);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotal[12]);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."M".$contFila)->applyFromArray($styleArrayResaltarTotal);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
	}
				
	$arrayTotalFinal[2] += $arrayTotal[2];
	$arrayTotalFinal[9] += $arrayTotal[9];
	$arrayTotalFinal[10] += $arrayTotal[10];
	$arrayTotalFinal[11] += $arrayTotal[11];
	$arrayTotalFinal[12] += $arrayTotal[12];
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total de Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotalFinal[2]);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotalFinal[9]);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotalFinal[10]);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotalFinal[11]);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotalFinal[12]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo2);
$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."M".$contFila)->applyFromArray($styleArrayResaltarTotal2);

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);

for ($col = "A"; $col != "M"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M");

$tituloDcto = "Ventas por Cliente";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:M7");

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