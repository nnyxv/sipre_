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
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if (in_array(1,explode(",",$valCadBusq[1]))) {
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		COUNT(art_alm2.id_articulo)
	FROM iv_estantes estante2
		INNER JOIN iv_calles calle2 ON (estante2.id_calle = calle2.id_calle)
		INNER JOIN iv_almacenes alm2 ON (calle2.id_almacen = alm2.id_almacen)
		INNER JOIN iv_tramos tramo2 ON (estante2.id_estante = tramo2.id_estante)
		INNER JOIN iv_casillas casilla2 ON (tramo2.id_tramo = casilla2.id_tramo)
		LEFT JOIN iv_articulos_almacen art_alm2 ON (art_alm2.id_casilla = casilla2.id_casilla)
		LEFT JOIN iv_articulos art2 ON (art_alm2.id_articulo = art2.id_articulo)
	WHERE art_alm2.id_articulo = (SELECT iv_articulos.id_articulo
						FROM iv_articulos
							INNER JOIN iv_articulos_almacen ON (iv_articulos.id_articulo = iv_articulos_almacen.id_articulo)
						WHERE iv_articulos_almacen.id_casilla = casilla.id_casilla
							AND iv_articulos_almacen.estatus = 1)
		AND ((art_alm2.estatus = 1 AND art_alm2.id_articulo IS NOT NULL)
			OR (art_alm2.estatus IS NULL AND art_alm2.id_articulo IS NOT NULL AND (cantidad_entrada - cantidad_salida - cantidad_reservada) > 0))
		%s) = 1", $sqlBusqEstatus);
}

if (in_array(2,explode(",",$valCadBusq[1]))) {
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		COUNT(art_alm2.id_articulo)
	FROM iv_estantes estante2
		INNER JOIN iv_calles calle2 ON (estante2.id_calle = calle2.id_calle)
		INNER JOIN iv_almacenes alm2 ON (calle2.id_almacen = alm2.id_almacen)
		INNER JOIN iv_tramos tramo2 ON (estante2.id_estante = tramo2.id_estante)
		INNER JOIN iv_casillas casilla2 ON (tramo2.id_tramo = casilla2.id_tramo)
		LEFT JOIN iv_articulos_almacen art_alm2 ON (art_alm2.id_casilla = casilla2.id_casilla)
		LEFT JOIN iv_articulos art2 ON (art_alm2.id_articulo = art2.id_articulo)
	WHERE art_alm2.id_articulo = (SELECT iv_articulos.id_articulo
						FROM iv_articulos
							INNER JOIN iv_articulos_almacen ON (iv_articulos.id_articulo = iv_articulos_almacen.id_articulo)
						WHERE iv_articulos_almacen.id_casilla = casilla.id_casilla
							AND iv_articulos_almacen.estatus = 1)
		AND ((art_alm2.estatus = 1 AND art_alm2.id_articulo IS NOT NULL)
			OR (art_alm2.estatus IS NULL AND art_alm2.id_articulo IS NOT NULL AND (cantidad_entrada - cantidad_salida - cantidad_reservada) > 0))
		%s) > 1", $sqlBusqEstatus);
}

if (in_array(1,explode(",",$valCadBusq[2]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT art.id_articulo
	FROM iv_articulos art
		INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
	WHERE art_alm.id_casilla = casilla.id_casilla
		AND art_alm.estatus = 1) IS NULL",
		valTpDato($valCadBusq[4], "text"));
}

if (in_array(2,explode(",",$valCadBusq[2]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT art.id_articulo
	FROM iv_articulos art
		INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
	WHERE art_alm.id_casilla = casilla.id_casilla
		AND art_alm.estatus = 1) IS NOT NULL",
		valTpDato($valCadBusq[4], "text"));
}

if (in_array(3,explode(",",$valCadBusq[2]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		(IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
	FROM iv_articulos art
		INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
	WHERE art_alm.id_casilla = casilla.id_casilla
		AND art_alm.estatus = 1) > 0");
}

if (in_array(4,explode(",",$valCadBusq[2]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT (IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
	FROM iv_articulos art
		INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
	WHERE art_alm.id_casilla = casilla.id_casilla
		AND art_alm.estatus = 1) <= 0");
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("casilla.estatus = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("calle.id_calle = %s",
		valTpDato($valCadBusq[5], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estante.id_estante = %s",
		valTpDato($valCadBusq[6], "int"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tramo.id_tramo = %s",
		valTpDato($valCadBusq[7], "int"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("casilla.id_casilla = %s",
		valTpDato($valCadBusq[8], "int"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("query.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[9], "text"));
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(query.id_articulo = %s
	OR query.id_articulo_costo LIKE %s
	OR query.descripcion LIKE %s)",
		valTpDato($valCadBusq[10], "int"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"));
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
	query.id_articulo,
	query.codigo_articulo,
	query.descripcion,
	(SELECT art_emp.clasificacion FROM iv_articulos_empresa art_emp
	WHERE art_emp.id_articulo = query.id_articulo
		AND art_emp.id_empresa = alm.id_empresa) AS clasificacion,
	query.estatus_articulo_almacen,
	query.id_articulo_almacen_costo,
	query.id_articulo_costo,
	query.cantidad_disponible_logica,
	(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = alm.id_empresa)
		WHEN 1 THEN	query.costo
		WHEN 2 THEN	query.costo_promedio
		WHEN 3 THEN	query.costo
	END) AS costo_unitario,
	query.abreviacion_moneda_local AS abreviacion_moneda_local
FROM iv_almacenes alm
	INNER JOIN iv_calles calle ON (alm.id_almacen = calle.id_almacen)
	INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
	INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
	INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
	LEFT JOIN (SELECT 
			art_almacen.id_articulo_almacen,
			art_almacen.id_casilla,
			art_almacen.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			art_almacen.sustituido,
			art_almacen.estatus AS estatus_articulo_almacen,
			art_almacen_costo.id_articulo_almacen_costo,
			art_almacen_costo.id_articulo_costo,
			IFNULL(art_almacen_costo.cantidad_reservada, 0) AS cantidad_reservada,
			IFNULL(art_almacen_costo.cantidad_inicio, 0) + IFNULL(art_almacen_costo.cantidad_entrada, 0) - IFNULL(art_almacen_costo.cantidad_salida, 0) - IFNULL(art_almacen_costo.cantidad_reservada, 0) - IFNULL(art_almacen_costo.cantidad_espera, 0) - IFNULL(art_almacen_costo.cantidad_bloqueada, 0) AS cantidad_disponible_logica,
			art_costo.costo,
			art_costo.costo_promedio,
			art_costo.estatus AS estatus_articulo_costo,
			moneda_local.abreviacion AS abreviacion_moneda_local,
			moneda_origen.abreviacion AS abreviacion_moneda_origen
		FROM iv_articulos_almacen art_almacen
			INNER JOIN iv_articulos art ON (art_almacen.id_articulo = art.id_articulo)
			LEFT JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
			LEFT JOIN iv_articulos_costos art_costo ON (art_almacen_costo.id_articulo_costo = art_costo.id_articulo_costo)
			LEFT JOIN pg_monedas moneda_local ON (art_costo.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_origen ON (art_costo.id_moneda_origen = moneda_origen.idmoneda)) AS query ON (casilla.id_casilla = query.id_casilla)
				AND ((query.estatus_articulo_almacen = 1 AND query.estatus_articulo_costo = 1)
					OR (query.estatus_articulo_almacen IS NULL AND query.cantidad_disponible_logica > 0)) %s
ORDER BY CONCAT(descripcion_almacen, ubicacion, IFNULL(query.id_articulo_costo, 0)) ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Estatus");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Almacén");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Lote");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Costo");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Unid. Disponible");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":I".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch ($row['estatus_casilla']) {
		case 0 : $imgEstatus = "Inactiva"; break;
		case 1 : $imgEstatus = "Activa"; break;
		default : $imgEstatus = "";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['descripcion_almacen'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, str_replace("-[]", "", $row['ubicacion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['id_articulo_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['costo_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['cantidad_disponible_logica']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":I".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);

for ($col = "A"; $col != "I"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "I");

$tituloDcto = "Ubicaciones";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:I7");

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