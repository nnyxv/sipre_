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

$sqlBusq = "";
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus_articulo_empresa = %s",
			valTpDato($valCadBusq[1], "boolean"));
	}
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modo_compra = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(codigo_articulo REGEXP %s
	OR vw_iv_art.id_articulo IN (SELECT art_cod_sust.id_articulo
								FROM iv_articulos_codigos_sustitutos art_cod_sust
									INNER JOIN iv_articulos art_sust ON (art_cod_sust.id_articulo_sustituido = art_sust.id_articulo)
								WHERE art_sust.codigo_articulo REGEXP %s) )",
		valTpDato($valCadBusq[3], "text"),
		valTpDato($valCadBusq[3], "text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_articulo = %s
	OR descripcion LIKE %s
	OR codigo_articulo_prov LIKE %s
	OR codigo_arancel LIKE %s)",
		valTpDato($valCadBusq[4], "int"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

//iteramos para los resultados
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$query = sprintf("SELECT vw_iv_art.*,
		arancel_fam.codigo_arancel,
		arancel_fam.descripcion_arancel,
		arancel_grup.porcentaje_grupo
	FROM pg_arancel_grupo arancel_grup
		RIGHT JOIN pg_arancel_familia arancel_fam ON (arancel_grup.id_arancel_grupo = arancel_fam.id_arancel_grupo)
		RIGHT JOIN vw_iv_articulos_empresa_datos_basicos vw_iv_art ON (arancel_fam.id_arancel_familia = vw_iv_art.id_arancel_familia) %s
	ORDER BY id_articulo DESC", $sqlBusq);
} else {
	$query = sprintf("SELECT vw_iv_art.*,
		arancel_fam.codigo_arancel,
		arancel_fam.descripcion_arancel,
		arancel_grup.porcentaje_grupo
	FROM pg_arancel_grupo arancel_grup
		RIGHT JOIN pg_arancel_familia arancel_fam ON (arancel_grup.id_arancel_grupo = arancel_fam.id_arancel_grupo)
		RIGHT JOIN vw_iv_articulos_datos_basicos vw_iv_art ON (arancel_fam.id_arancel_familia = vw_iv_art.id_arancel_familia) %s
	ORDER BY id_articulo DESC", $sqlBusq);
}
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Tipo de Artículo");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Clasif.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter(utf8_encode($row['codigo_articulo']),";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, utf8_encode($row['descripcion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, utf8_encode($row['codigo_articulo_prov']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, utf8_encode($row['tipo_articulo']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, utf8_encode($row['clasificacion']), PHPExcel_Cell_DataType::TYPE_STRING);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":E".$ultimo);

cabeceraExcel($objPHPExcel, $idEmpresa, "E");

$tituloDcto = "Artículos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:E7");

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