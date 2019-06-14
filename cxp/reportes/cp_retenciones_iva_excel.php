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
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("retencion.fechaComprobante BETWEEN %s AND %s",
	valTpDato(date("Y-m-d", strtotime($valCadBusq[0])), "date"),
	valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));

//iteramos para los resultados
$query = sprintf("SELECT 
	retencion.idRetencionCabezera,
	retencion.numeroComprobante,
	retencion.fechaComprobante,
	retencion.anoPeriodoFiscal,
	retencion.mesPeriodoFiscal,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	vw_iv_emp_suc.id_empresa_reg,
	vw_iv_emp_suc.nombre_empresa,
	vw_iv_emp_suc.rif,
	retencion_det.idRetencionDetalle,
	retencion_det.idRetencionCabezera,
	retencion_det.fechaFactura,
	cxc_fact.id_factura,
	(CASE
		WHEN retencion_det.id_nota_cargo IS NOT NULL THEN
			cxp_nd.numero_notacargo
		WHEN retencion_det.id_nota_credito IS NOT NULL THEN
			cxp_nc.numero_nota_credito
		ELSE
			cxc_fact.numero_factura_proveedor
	END) AS numero_factura_proveedor,
	(CASE
		WHEN retencion_det.id_nota_cargo IS NOT NULL THEN
			cxp_nd.numero_control_notacargo
		WHEN retencion_det.id_nota_credito IS NOT NULL THEN
			cxp_nc.numero_control_notacredito
		ELSE
			cxc_fact.numero_control_factura
	END) AS numero_control_factura,
	retencion_det.id_nota_cargo,
	cxp_nd.numero_notacargo,
	retencion_det.id_nota_credito,
	cxp_nc.numero_nota_credito,
	retencion_det.tipoDeTransaccion,
	IF (retencion_det.id_nota_cargo IS NULL AND retencion_det.id_nota_credito IS NULL, '0', cxc_fact.numero_factura_proveedor) AS numeroFacturaAfectada,
	retencion_det.totalCompraIncluyendoIva,
	retencion_det.comprasSinIva,
	retencion_det.baseImponible,
	SUM(retencion_det.porcentajeAlicuota) AS porcentajeAlicuota,
	SUM(retencion_det.impuestoIva) AS impuestoIva,
	SUM(retencion_det.IvaRetenido) AS IvaRetenido,
	retencion_det.porcentajeRetencion
FROM cp_proveedor prov
	INNER JOIN cp_retencioncabezera retencion ON (prov.id_proveedor = retencion.idProveedor)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (retencion.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	INNER JOIN cp_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
	INNER JOIN cp_factura cxc_fact ON (retencion_det.idFactura = cxc_fact.id_factura)
	LEFT JOIN cp_notacredito cxp_nc ON (retencion_det.id_nota_credito = cxp_nc.id_notacredito)
	LEFT JOIN cp_notadecargo cxp_nd ON (retencion_det.id_nota_cargo = cxp_nd.id_notacargo) %s
GROUP by retencion_det.idRetencionCabezera, cxc_fact.id_factura, retencion_det.id_nota_cargo, retencion_det.id_nota_credito
ORDER BY cxc_fact.id_factura ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "R.I.F."); // R.I.F. (Formato general) 10 dígitos
$objPHPExcel->getActiveSheet()->SetCellValue("B".$contFila, "Período Impositivo"); // Período Impositivo Año y Mes (Formato general) 6 dígitos
$objPHPExcel->getActiveSheet()->SetCellValue("C".$contFila, "Fecha del Documento"); // Fecha del Documento Año-Mes-Día (Formato texto) 10 dígitos	
$objPHPExcel->getActiveSheet()->SetCellValue("D".$contFila, "Tipo de Operación"); // Tipo de Operación V=ventas C=compras (Formato general) 1 dígito
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Tipo de Dcto."); // Tipo de Dcto. 01 = Factura 02 = N.Debito 03 = N.Credito 04 = Certificación 05 = Importación 06 = Exportación (Formato texto) 2 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "R.I.F. del Vendedor"); // R.I.F. del Vendedor (Formato general) 10 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. de la Factura"); // Nro. de la Factura (Formato general) hasta 20 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. de Control"); // Nro. de Control (Formato general) hasta 20 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Monto Total Facturado"); // Monto Total Facturado (Formato general) hasta 15 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Base Imponible"); // Base Imponible (Formato general) hasta 15 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Impuesto Retenido"); // Impuesto Retenido (Formato general) hasta 15 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Nro. Documento Afectado"); // Nro. Documento Afectado (Formato general) hasta 20 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Nro. del Comprobante"); // Nro. del Comprobante (Formato texto)(alfanumérico) 14 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Monto Exento"); // Monto Exento (Formato general) hasta 15 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Alícuota Impositiva"); // Alícuota Impositiva (Formato general) hasta 3 dígitos
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Nro. de Expediente"); // Nro. de Expediente (Formato general )hasta 15 dígitos

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$signo = (in_array($row['tipoDeTransaccion'], array(1,2))) ? 1 : (-1);
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, str_replace("-","",$row['rif']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['anoPeriodoFiscal'].str_pad($row['mesPeriodoFiscal'], 2, "0", STR_PAD_LEFT), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, date("Y-m-d", strtotime($row['fechaFactura'])), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, "C", PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, str_pad($row['tipoDeTransaccion'], 2, "0", STR_PAD_LEFT), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, str_replace ("-","",$row['rif_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['numero_factura_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numero_control_factura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $signo * number_format($row['totalCompraIncluyendoIva'], 2, ".", ""), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $signo * number_format($row['baseImponible'], 2, ".", ""), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $signo * number_format($row['IvaRetenido'], 2, ".", ""), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, $row['numeroFacturaAfectada'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, $row['numeroComprobante'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("N".$contFila, $signo * number_format($row['comprasSinIva'], 2, ".", ""), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("O".$contFila, $signo * number_format($row['porcentajeAlicuota'], 2, ".", ""), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("P".$contFila, 0, PHPExcel_Cell_DataType::TYPE_STRING);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	/*$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);*/
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":P".$ultimo);

for ($col = "A"; $col != "P"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "P");

$tituloDcto = "Retención de Impuesto";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:P7");

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