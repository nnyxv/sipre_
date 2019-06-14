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
	$sqlBusq .= $cond.sprintf("unidad_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_bas.catalogo = %s",
		valTpDato($valCadBusq[5], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT vw_iv_modelo.*,
	uni_bas.clv_uni_bas
FROM an_uni_bas uni_bas
	LEFT JOIN sa_unidad_empresa unidad_emp ON (uni_bas.id_uni_bas = unidad_emp.id_unidad_basica)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas) %s
ORDER BY uni_bas.id_uni_bas DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código Unidad Básica");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Clave Unidad Básica");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Descripción Unidad Básica");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Marca");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Modelo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Versión");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Año");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "En Catálogo");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Clase");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Uso");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "# Puertas");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "# Cilindros");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Cilindrada Cm3");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Caballos de Fuerza (HP)");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Transmisión");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Combustible");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Capacidad (Kg)");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Años de Garantía");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $spanKilometraje." de Garantía");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":T".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, $row['nom_uni_bas'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['clv_uni_bas'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['des_uni_bas']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['nom_marca']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['nom_modelo']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['nom_version']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['nom_ano']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['catalogo']));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":T".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":T".$ultimo);

for ($col = "A"; $col != "T"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "T");

$tituloDcto = "Unidades Básicas";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:T7");

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