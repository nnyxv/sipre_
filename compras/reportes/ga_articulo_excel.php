<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
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

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_articulo = %s
	OR (estatus_articulo IS NULL AND %s IS NULL))",
		valTpDato($valCadBusq[1], "text"),
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[2], "text"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_articulo = %s
	OR codigo_articulo LIKE %s
	OR descripcion LIKE %s)",
		valTpDato($valCadBusq[3],"int"),
		valTpDato("%".$valCadBusq[3]."%","text"),
		valTpDato("%".$valCadBusq[3]."%","text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_articulo = %s",
		valTpDato($valCadBusq[4], "text"));
}

$query = ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") ? 
"SELECT vw_ga_articulos_empresa.*, ga_secciones.descripcion AS descripcion_seccion, ga_subsecciones.descripcion AS descripcion_subseccion 
FROM vw_ga_articulos_empresa 
INNER JOIN ga_secciones ON (vw_ga_articulos_empresa.id_seccion = ga_secciones.id_seccion)
INNER JOIN ga_subsecciones ON (vw_ga_articulos_empresa.id_subseccion = ga_subsecciones.id_subseccion) " : 

"SELECT vw_ga_articulos.*, ga_secciones.descripcion AS descripcion_seccion, ga_subsecciones.descripcion AS descripcion_subseccion 
FROM vw_ga_articulos
INNER JOIN ga_secciones ON (vw_ga_articulos.id_seccion = ga_secciones.id_seccion)
INNER JOIN ga_subsecciones ON (vw_ga_articulos.id_subseccion = ga_subsecciones.id_subseccion) ";
$query .= $sqlBusq;

$rs = mysql_query($query);       
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Estatus");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Tipo Artículo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Marca");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Sección");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Sub Sección");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Saldo");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Pedida a Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Futura");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Clasif.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch ($row['estatus_articulo']) {
		case 0 : $imgEstatus = "Inactivo"; break;
		case 1 : $imgEstatus = "Activo"; break;
		default : $imgEstatus = "";
	}
	
	switch($row['clasificacion']) {
		case 'A' : $imgClasificacion .= "A"; break;
		case 'B' : $imgClasificacion .= "B"; break;
		case 'C' : $imgClasificacion .= "C"; break;
		case 'D' : $imgClasificacion .= "D"; break;
		case 'E' : $imgClasificacion .= "E"; break;
		case 'F' : $imgClasificacion .= "F"; break;
		default : $imgClasificacion = "";
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, utf8_encode(elimCaracter($row['codigo_articulo'],"-")), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, utf8_encode($row['codigo_articulo_prov']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['descripcion']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['tipo_articulo']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['marca']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['descripcion_seccion']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['descripcion_subseccion']));	
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, valTpDato($row['existencia'],"cero_por_vacio"));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, valTpDato($row['cantidad_disponible_logica'],"cero_por_vacio"));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, valTpDato($row['cantidad_pedida'],"cero_por_vacio"));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, valTpDato($row['cantidad_futura'],"cero_por_vacio"));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $imgClasificacion);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);	
	
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}

$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":M".$ultimo);

for ($col = "A"; $col <= "M"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M");

//FECHA
if($valCadBusq[3] != "" && $valCadBusq[4] != ""){
	$fecha = "Desde: ".date(spanDateFormat, strtotime($valCadBusq[3]))." Hasta: ".date(spanDateFormat, strtotime($valCadBusq[4]));
}

$objPHPExcel->getActiveSheet()->SetCellValue("N6", $fecha);
$objPHPExcel->getActiveSheet()->getStyle("N6")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("N6:R6");

$tituloDcto = "Listado de Artículos";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:V7");

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