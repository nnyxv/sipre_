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
	$sqlBusq .= $cond.sprintf("cierre_mensual.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mensual.id_cierre_mensual = %s",
		valTpDato($valCadBusq[1], "int"));
}

$queryAnalisisInv = sprintf("SELECT *
FROM iv_analisis_inventario analisis_inv
	INNER JOIN iv_cierre_mensual cierre_mensual ON (analisis_inv.id_cierre_mensual = cierre_mensual.id_cierre_mensual) %s", $sqlBusq);
$rsAnalisisInv = mysql_query($queryAnalisisInv);
if (!$rsAnalisisInv) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowAnalisisInv = mysql_fetch_assoc($rsAnalisisInv);
$idAnalisisInv = $rowAnalisisInv['id_analisis_inventario'];

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("analisis_inv.id_analisis_inventario = %s",
	valTpDato($idAnalisisInv, "int"));

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != ""
&& ($valCadBusq[3] == "-1" || $valCadBusq[3] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv_det.cantidad_existencia > 0");
}

if (($valCadBusq[2] == "-1" || $valCadBusq[2] == "")
&& $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv_det.cantidad_existencia <= 0");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv_det.clasificacion LIKE %s",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[5], "text"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(analisis_inv_det.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[6], "int"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	analisis_inv_det.id_analisis_inventario_detalle,
	analisis_inv_det.id_analisis_inventario,
	analisis_inv_det.id_articulo,
	analisis_inv_det.cantidad_existencia,
	analisis_inv_det.cantidad_disponible_logica,
	analisis_inv_det.cantidad_disponible_fisica,
	analisis_inv_det.costo,
	(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
	(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
	analisis_inv_det.promedio_diario,
	analisis_inv_det.promedio_mensual,
	(analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) AS inventario_recomendado,
	(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario)) AS sobre_stock,
	((analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) - analisis_inv_det.cantidad_existencia) AS sugerido,
	analisis_inv_det.clasificacion,
	art.codigo_articulo,
	art.codigo_articulo_prov,
	art.descripcion
FROM iv_cierre_mensual cierre_mensual
	INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
	INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo) %s
ORDER BY clasificacion DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Unid. Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Costo Unitario");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Costo Total");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Meses Existencia");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Prom. Día");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Venta Mensual");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Inv. Recom.");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Sobre Stock");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Suger.");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Clasif.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['codigo_articulo_prov'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['cantidad_existencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['costo_total']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, (($row['promedio_mensual'] > 0) ? $row['meses_existencia'] : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['promedio_diario']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['promedio_mensual']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['inventario_recomendado']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($row['cantidad_existencia'] > $row['inventario_recomendado']) ? $row['sobre_stock'] : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($row['cantidad_existencia'] < $row['inventario_recomendado']) ? $row['sugerido'] : 0));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":M".$ultimo);

for ($col = "A"; $col != "M"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M");

$tituloDcto = "Análisis de Inventario";
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