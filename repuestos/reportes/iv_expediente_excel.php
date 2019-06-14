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
$sqlBusq .= $cond.sprintf("cxp_fact_det.id_factura = %s",
	valTpDato($valCadBusq[0], "int"));

//iteramos para los resultados
$query = sprintf("SELECT 
	cxp_fact_det.id_factura,
	cxp_fact.id_empresa,
	cxp_fact_imp.numero_expediente,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	cxp_fact_det.cantidad,
	cxp_fact_det_imp.costo_unitario,
	cxp_fact_det_imp.gasto_unitario,
	cxp_fact_det.peso_unitario,
	cxp_fact_imp.tasa_cambio,
	cxp_fact_imp.tasa_cambio_diferencia,
	cxp_fact_det_imp.porcentaje_grupo,
	cxp_fact_det_imp.gastos_import_nac_unitario,
	cxp_fact_det_imp.gastos_import_unitario,
	moneda_origen.abreviacion AS abreviacion_moneda_origen,
	moneda_local.abreviacion AS abreviacion_moneda_local,
	
	(cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) AS costo_cif,
	
	((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) AS costo_cif_nacional,
	
	(((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100 AS tarifa_adv,
	
	(((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
		+ ((((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100)
		+ cxp_fact_det_imp.gastos_import_nac_unitario
		+ cxp_fact_det_imp.gastos_import_unitario) AS costo_unitario_final,
		
	((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia) AS costo_cif_diferencia
FROM cp_factura_detalle_importacion cxp_fact_det_imp
	INNER JOIN cp_factura_detalle cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle = cxp_fact_det.id_factura_detalle)
	INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
	INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact_det.id_factura = cxp_fact_imp.id_factura)
	INNER JOIN pg_monedas moneda_origen ON (cxp_fact_imp.id_moneda_tasa_cambio = moneda_origen.idmoneda)
	INNER JOIN cp_factura cxp_fact ON (cxp_fact_imp.id_factura = cxp_fact.id_factura)
	INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++; 
$primero = $contFila;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Cant.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "% ADV");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Costo Unit. FOB");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Gasto Unit.");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Costo Unit. CIF");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Tasa Cambio");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Costo Unit. CIF");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Tarifa Unit. ADV");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Gastos Unit. Importación");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Otros Cargos Unit.");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Costo Unit. Final");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Peso Unit. (g)");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Tasa Cambio Dif.");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Costo Unit. CIF Dif.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$idEmpresa = $row['id_empresa'];
	$tituloDcto = "Expediente ".str_replace("/"," ",$row['numero_expediente']);
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter(utf8_encode($row['codigo_articulo']),";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['descripcion']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['cantidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['porcentaje_grupo']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['costo_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['gasto_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['costo_cif']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['tasa_cambio']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['costo_cif_nacional']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['tarifa_adv']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['gastos_import_nac_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['gastos_import_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['costo_unitario_final']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['peso_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['tasa_cambio_diferencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['costo_cif_diferencia']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":P".$ultimo);

for ($col = "A"; $col != "P"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "P");

$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:P7");

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