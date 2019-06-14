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

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);
			
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = q.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(q.fecha_dcto) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == 1) {// solo facturas sin devolucion
		$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC' AND q.activa = 1");
	} else if ($valCadBusq[3] == 2) {// solo facturas con devolucion
		$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC' OR q.activa = 0 OR q.activa IS NULL)");
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.nro_dcto LIKE %s
	OR q.nro_control_dcto LIKE %s
	OR q.placa LIKE %s
	OR q.serial_carroceria LIKE %s
	OR q.nombre_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

$query = sprintf("SELECT * 
FROM (
	SELECT 
		cxp_fact.id_factura AS id_dcto,
		cxp_fact.fecha_origen AS fecha_dcto,
		'FA' AS tipo_dcto,
		cxp_fact.numero_factura_proveedor AS nro_dcto,
		cxp_fact.numero_control_factura AS nro_control_dcto,
		cxp_fact.activa,
		cxp_fact.id_proveedor,
		prov.nombre AS nombre_proveedor,			
		cxp_fact.id_empresa,
		unidad.placa,
		unidad.serial_carroceria,
		serv_mant.descripcion_servicio_mantenimiento,
		serv_mant_compra.costo,			
		
		(cxp_fact.subtotal_factura - cxp_fact.subtotal_descuento) AS total_neto,
		cxp_fact.total_cuenta_pagar AS total,
		
		(SELECT SUM(cp_factura_iva.subtotal_iva) FROM cp_factura_iva
		WHERE cp_factura_iva.id_factura = cxp_fact.id_factura) AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
	FROM cp_factura cxp_fact
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
		INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
		LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
		
	UNION ALL
	
	SELECT 
		cxp_nc.id_notacredito AS id_dcto,
		cxp_nc.fecha_notacredito AS fecha_dcto,
		'NC' AS tipo_dcto,
		cxp_nc.numero_nota_credito AS nro_dcto,
		cxp_nc.numero_control_notacredito AS nro_control_dcto,
		0 AS activa,
		cxp_nc.id_proveedor,
		prov.nombre AS nombre_proveedor,			
		cxp_nc.id_empresa,			
		unidad.placa,
		unidad.serial_carroceria,
		serv_mant.descripcion_servicio_mantenimiento,
		(serv_mant_compra.costo) * -1 AS costo,	
		
		((cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento)) * -1 AS total_neto,
		(cxp_nc.total_cuenta_pagar * -1) AS total,
		
		((SELECT SUM(cp_notacredito_iva.subtotal_iva_notacredito) FROM cp_notacredito_iva
		WHERE cp_notacredito_iva.id_notacredito = cxp_nc.id_notacredito)) * -1 AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa			
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento = 'FA')
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)			
		INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
		INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
		LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
	) AS q
	%s ORDER BY fecha_dcto, nro_control_dcto ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Tipo Dcto");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Nro. Dcto");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Servicio / Mantenimiento");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Total");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Costo Unidad");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":J".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, date(spanDateFormat, strtotime($row['fecha_dcto'])));
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['tipo_dcto']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['nro_dcto']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['nro_control_dcto'], PHPExcel_Cell_DataType::TYPE_STRING);
	
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['placa']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['serial_carroceria']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['descripcion_servicio_mantenimiento']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['total']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['costo']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":J".$contFila)->applyFromArray($clase);

	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);	
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$arrayTotal[5] += 1;
	//documento se repite tanto como detalles de costo tenga el vehiculo
	if(!in_array($row['tipo_dcto'].$row['id_dcto'], $arrayDuplicado)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
		
		$arrayTotal[19] += $row['total'];
	}
	
	$arrayDuplicado[] = $row['tipo_dcto'].$row['id_dcto'];
	
	$totalCosto += $row['costo'];
}

$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":J".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total de totales:");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal[19]);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $totalCosto);

$objPHPExcel->getActiveSheet()->SetCellValue("H".($contFila + 2), "Total Neto:");
$objPHPExcel->getActiveSheet()->SetCellValue("H".($contFila + 3), "Total Impuesto:");
$objPHPExcel->getActiveSheet()->SetCellValue("H".($contFila + 4), "Total Factura(s):");
$objPHPExcel->getActiveSheet()->SetCellValue("H".($contFila + 5), "Total Costo:");
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 2), $totalNeto);
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 3), $totalIva);
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 4), $totalFacturas);
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 5), $totalCosto);

$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 2))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 3))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 4))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 5))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->getStyle("H".($contFila + 2).":"."J".($contFila + 2))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("H".($contFila + 3).":"."J".($contFila + 3))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("H".($contFila + 4).":"."J".($contFila + 4))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("H".($contFila + 5).":"."J".($contFila + 5))->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."J".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":H".$contFila);

for ($col = "A"; $col <= "J"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "J");

//FECHA
if($valCadBusq[1] != "" && $valCadBusq[2] != ""){
	$fecha = "Desde: ".date(spanDateFormat, strtotime($valCadBusq[1]))." Hasta: ".date(spanDateFormat, strtotime($valCadBusq[2]));
}
$objPHPExcel->getActiveSheet()->SetCellValue("H6", $fecha);
$objPHPExcel->getActiveSheet()->getStyle("H6")->applyFromArray($styleArrayTitulo);

$tituloDcto = "Histórico Reporte Gastos";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:J7");

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