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
$sqlBusq .= $cond.sprintf("art_alm.estatus IS NULL");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != ""
&& ($valCadBusq[2] == "-1" || $valCadBusq[2] == "")) {
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		COUNT(art_alm2.id_articulo)
	FROM iv_articulos_almacen art_alm2
		INNER JOIN iv_casillas casilla2 ON (art_alm2.id_casilla = casilla2.id_casilla)
	WHERE art_alm2.id_articulo = art.id_articulo 
		AND art_alm2.estatus IS NULL %s) = 1", $sqlBusqEstatus);
}

if (($valCadBusq[1] == "-1" || $valCadBusq[1] == "")
&& $valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		COUNT(art_alm2.id_articulo)
	FROM iv_articulos_almacen art_alm2
		INNER JOIN iv_casillas casilla2 ON (art_alm2.id_casilla = casilla2.id_casilla)
	WHERE art_alm2.id_articulo = art.id_articulo
		AND art_alm2.estatus IS NULL %s) > 1", $sqlBusqEstatus);
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != ""
&& ($valCadBusq[4] == "-1" || $valCadBusq[4] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(art_alm2.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm2
	WHERE art_alm2.id_casilla = casilla.id_casilla
		AND art_alm2.estatus = 1) = 0",
		valTpDato($valCadBusq[4], "text"));
}

if (($valCadBusq[3] == "-1" || $valCadBusq[3] == "")
&& $valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(art_alm2.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm2
	WHERE art_alm2.id_casilla = casilla.id_casilla
		AND art_alm2.estatus = 1) > 0",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != ""
&& ($valCadBusq[6] == "-1" || $valCadBusq[6] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera) > 0");
}

if (($valCadBusq[5] == "-1" || $valCadBusq[5] == "")
&& $valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera) <= 0");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("casilla.estatus = %s",
		valTpDato($valCadBusq[7], "int"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen = %s",
		valTpDato($valCadBusq[8], "int"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("calle.id_calle = %s",
		valTpDato($valCadBusq[9], "int"));
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estante.id_estante = %s",
		valTpDato($valCadBusq[10], "int"));
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tramo.id_tramo = %s",
		valTpDato($valCadBusq[11], "int"));
}

if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("casilla.id_casilla = %s",
		valTpDato($valCadBusq[12], "int"));
}

if ($valCadBusq[13] != "-1" && $valCadBusq[13] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[13], "text"));
}

if ($valCadBusq[14] != "-1" && $valCadBusq[14] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s)",
		valTpDato($valCadBusq[14], "int"),
		valTpDato("%".$valCadBusq[14]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	alm.id_empresa,
	alm.id_almacen,
	alm.descripcion AS descripcion_almacen,
	alm.estatus,
	calle.id_calle,
	estante.id_estante ,
	tramo.id_tramo,
	casilla.id_casilla,
	CONCAT_WS('-', calle.descripcion_calle, estante.descripcion_estante, tramo.descripcion_tramo, casilla.descripcion_casilla) AS ubicacion,
	casilla.estatus AS estatus_casilla,
	
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	
	(SELECT art_emp.clasificacion
	FROM iv_articulos_empresa art_emp
	WHERE art_emp.id_articulo = art.id_articulo
		AND art_emp.id_empresa = alm.id_empresa) AS clasificacion,
	
	(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera) AS cantidad_disponible_logica
FROM iv_estantes estante
	INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
	INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
	INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
	INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
	INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
	INNER JOIN iv_articulos art ON (art_alm.id_articulo = art.id_articulo) %s
ORDER BY CONCAT(descripcion_almacen, ubicacion) ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Estatus");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Almacén");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Unid. Disponible");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":H".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch ($row['estatus_casilla']) {
		case 0 : $imgEstatus = "Inactiva"; break;
		case 1 : $imgEstatus = "Activa"; break;
		default : $imgEstatus = "";
	}
	
	$queryCasilla = sprintf("SELECT
		casilla.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_casillas casilla
	WHERE casilla.id_casilla = %s;",
		valTpDato($row['id_casilla'], "int"));
	$rsCasilla = mysql_query($queryCasilla);
	if (!$rsCasilla) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsCasilla = mysql_num_rows($rsCasilla);
	$rowCasilla = mysql_fetch_assoc($rsCasilla);
	
	//$imgEstatusArticuloAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "" : "<span class=\"textoRojoNegrita_10px\">".htmlentities("Relacion Inactiva")."</span>";
	
	$classUbic = "";
	$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "Ocupada" : "Disponible";
	$classUbic = ($rowCasilla['estatus'] == 0) ? "Inactiva" : $classUbic;
	$ocupada = ($rowCasilla['cantidad_ocupada'] > 0) ? "*" : "";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['descripcion_almacen']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, str_replace("-[]", "", $row['ubicacion'].$ocupada));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $classUbic);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['descripcion']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['clasificacion']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['cantidad_disponible_logica']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":H".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":H".$ultimo);

for ($col = "A"; $col != "H"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "H");

$tituloDcto = "Histórico de Relación Artículo con Ubicación";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:H7");

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