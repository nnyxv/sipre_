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
	$sqlBusq .= $cond.sprintf("q.id_empleado_creador = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[4] == 1) {// solo facturas sin devolucion
		$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC' AND q.anulada = 'NO'");
	} else if ($valCadBusq[4] == 2) {// solo facturas con devolucion
		$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC' OR q.anulada = 'SI')");
	}
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.numero_contrato_venta LIKE %s		
	OR q.nro_dcto LIKE %s
	OR q.nro_control_dcto LIKE %s
	OR q.placa LIKE %s
	OR q.serial_carroceria LIKE %s
	OR q.nombre_cliente LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

$query = sprintf("SELECT * 
FROM (
	SELECT 
		cxc_fact.idFactura AS id_dcto,
		cxc_fact.fechaRegistroFactura AS fecha_dcto,
		'FA' AS tipo_dcto,
		cxc_fact.numeroFactura AS nro_dcto,
		cxc_fact.numeroControl AS nro_control_dcto,
		cxc_fact.anulada,
		contrato.fecha_creacion AS fecha_contrato,
		contrato.numero_contrato_venta,
		contrato.id_tipo_contrato,
		tipo_contrato.nombre_tipo_contrato,
		contrato.id_empleado_creador,
		empleado.nombre_empleado,
		contrato.id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,	
		contrato.id_contrato_venta,
		contrato.id_empresa,
		contrato.id_unidad_fisica,
		contrato.estatus_contrato_venta,
		unidad.placa,
		unidad.serial_carroceria,
		(cxc_fact.subtotalFactura - cxc_fact.descuentoFactura) AS total_neto,
		cxc_fact.montoTotalFactura AS total,
		
		(SELECT SUM(cj_cc_factura_iva.subtotal_iva) FROM cj_cc_factura_iva
		WHERE cj_cc_factura_iva.id_factura = cxc_fact.idFactura) AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
	FROM al_contrato_venta contrato
		INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)		
		INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)		
		
	UNION ALL
	
	SELECT 
		cxc_nc.idNotaCredito AS id_dcto,
		cxc_nc.fechaNotaCredito AS fecha_dcto,
		'NC' AS tipo_dcto,
		cxc_nc.numeracion_nota_credito AS nro_dcto,
		cxc_nc.numeroControl AS nro_control_dcto,
		'NO' AS anulada,
		contrato.fecha_creacion AS fecha_contrato,
		contrato.numero_contrato_venta,
		contrato.id_tipo_contrato,
		tipo_contrato.nombre_tipo_contrato,
		contrato.id_empleado_creador,
		empleado.nombre_empleado,
		contrato.id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,	
		contrato.id_contrato_venta,
		contrato.id_empresa,
		contrato.id_unidad_fisica,
		contrato.estatus_contrato_venta,
		unidad.placa,
		unidad.serial_carroceria,
		((cxc_nc.subtotalNotaCredito - cxc_nc.subtotal_descuento)) * -1 AS total_neto,
		(cxc_nc.montoNetoNotaCredito * -1) AS total,
		((SELECT SUM(cj_cc_nota_credito_iva.subtotal_iva) FROM cj_cc_nota_credito_iva
		WHERE cj_cc_nota_credito_iva.id_nota_credito = cxc_nc.idNotaCredito)) * -1 AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
	FROM al_contrato_venta contrato
		INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)		
		INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4			
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	WHERE cxc_nc.tipoDocumento = 'FA'
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
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Contrato");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Contrato");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Tipo Contrato");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Vendedor");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Total");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, date(spanDateFormat, strtotime($row['fecha_dcto'])));
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['tipo_dcto']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['nro_dcto']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['nro_control_dcto'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['numero_contrato_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_contrato'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['nombre_tipo_contrato']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['nombre_empleado']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['placa']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['serial_carroceria']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['total']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($clase);

	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$arrayTotal[5] += 1;
	$arrayTotal[19] += $row['total'];
	
	$totalNeto += $row['total_neto'];
	$totalIva += $row['total_iva'];
	$totalFacturas += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":L".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total de totales:");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotal[19]);

$objPHPExcel->getActiveSheet()->SetCellValue("K".($contFila + 2), "Total Neto:");
$objPHPExcel->getActiveSheet()->SetCellValue("K".($contFila + 3), "Total Impuesto:");
$objPHPExcel->getActiveSheet()->SetCellValue("K".($contFila + 4), "Total Factura(s):");
$objPHPExcel->getActiveSheet()->SetCellValue("L".($contFila + 2), $totalNeto);
$objPHPExcel->getActiveSheet()->SetCellValue("L".($contFila + 3), $totalIva);
$objPHPExcel->getActiveSheet()->SetCellValue("L".($contFila + 4), $totalFacturas);

$objPHPExcel->getActiveSheet()->getStyle("L".($contFila + 2))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("L".($contFila + 3))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("L".($contFila + 4))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->getStyle("K".($contFila + 2).":"."L".($contFila + 2))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("K".($contFila + 3).":"."L".($contFila + 3))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("K".($contFila + 4).":"."L".($contFila + 4))->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":K".$contFila);

for ($col = "A"; $col <= "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "L");

//FECHA
if($valCadBusq[1] != "" && $valCadBusq[2] != ""){
	$fecha = "Desde: ".date(spanDateFormat, strtotime($valCadBusq[1]))." Hasta: ".date(spanDateFormat, strtotime($valCadBusq[2]));
}
$objPHPExcel->getActiveSheet()->SetCellValue("I6", $fecha);
$objPHPExcel->getActiveSheet()->getStyle("I6")->applyFromArray($styleArrayTitulo);

$tituloDcto = "Histórico Reporte Venta";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:L7");

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