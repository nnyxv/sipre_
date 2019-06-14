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
	$sqlBusq .= $cond.sprintf("(art_emp.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = art_emp.id_empresa))",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[0],"int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(art_emp.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = art_emp.id_empresa))",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[0],"int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[1], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_emp.clasificacion LIKE %s",
		valTpDato($valCadBusq[2], "text"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("art_emp.clasificacion LIKE %s",
		valTpDato($valCadBusq[2], "text"));
}

if (in_array(1,explode(",",$valCadBusq[3]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(query.cantidad_entrada - query.cantidad_salida) > 0");
}

if (in_array(2,explode(",",$valCadBusq[3]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(query.cantidad_entrada - query.cantidad_salida) <= 0");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[4], "text"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT art.*,
	
	query.cantidad_entrada,
	(query.cantidad_entrada * query.costo_unitario) AS valor_entrada,
	
	query.cantidad_salida,
	(query.cantidad_salida * query.costo_unitario) AS valor_salida,
	
	(query.cantidad_entrada - query.cantidad_salida) AS existencia,
	((query.cantidad_entrada - query.cantidad_salida) * query.costo_unitario) AS valor_existencia
		
FROM iv_articulos_empresa art_emp
	INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo)
	INNER JOIN (SELECT
					alm.id_empresa,
					art_alm.id_articulo,
					SUM(art_alm.cantidad_entrada) AS cantidad_entrada,
					SUM(art_alm.cantidad_salida) AS cantidad_salida,
					(SELECT
						(CASE (SELECT valor FROM pg_configuracion_empresa config_emp
									INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
								WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = art_costo.id_empresa)
							WHEN 1 THEN	art_costo.costo
							WHEN 2 THEN	art_costo.costo_promedio
							WHEN 3 THEN
								IF((SELECT
										(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
											+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
											- SUM(art_costo2.cantidad_salida * art_costo2.costo))
										/ (SUM(art_costo2.cantidad_inicio)
											+ SUM(art_costo2.cantidad_entrada)
											- SUM(art_costo2.cantidad_salida))
									FROM iv_articulos_costos art_costo2
									WHERE art_costo2.id_articulo = art_costo.id_articulo
										AND art_costo2.id_empresa = art_costo.id_empresa
										AND art_costo2.estatus = 1
									ORDER BY art_costo2.fecha_registro DESC), (SELECT
																					(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
																						+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
																						- SUM(art_costo2.cantidad_salida * art_costo2.costo))
																					/ (SUM(art_costo2.cantidad_inicio)
																						+ SUM(art_costo2.cantidad_entrada)
																						- SUM(art_costo2.cantidad_salida))
																				FROM iv_articulos_costos art_costo2
																				WHERE art_costo2.id_articulo = art_costo.id_articulo
																					AND art_costo2.id_empresa = art_costo.id_empresa
																					AND art_costo2.estatus = 1
																				ORDER BY art_costo2.fecha_registro DESC), art_costo.costo_promedio)
						END)
					FROM iv_articulos_costos art_costo
					WHERE art_costo.id_articulo = art_alm.id_articulo
						AND art_costo.id_empresa = alm.id_empresa
					ORDER BY art_costo.id_articulo_costo DESC LIMIT 1) AS costo_unitario
				FROM iv_estantes estante
					INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
					INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
					INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
				GROUP BY alm.id_empresa, art_alm.id_articulo) AS query ON (query.id_empresa = art_emp.id_empresa)
					AND (query.id_articulo = art_emp.id_articulo) %s
ORDER BY art.codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Entradas");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Salidas");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Actual");

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":D".$contFila);
$objPHPExcel->getActiveSheet()->mergeCells("E".$contFila.":F".$contFila);
$objPHPExcel->getActiveSheet()->mergeCells("G".$contFila.":H".$contFila);
$objPHPExcel->getActiveSheet()->mergeCells("I".$contFila.":J".$contFila);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Unidades");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Importe");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Unidades");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Importe");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Unidades");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Importe");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":J".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter(utf8_encode($row['codigo_articulo']),";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, utf8_encode($row['descripcion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, utf8_encode($row['codigo_articulo_prov']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, utf8_encode($row['clasificacion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['cantidad_entrada']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['valor_entrada']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['cantidad_salida']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['valor_salida']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['existencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['valor_existencia']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":J".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');
	
	// TOTALES
	$arrayTotal[5] += $row['cantidad_entrada'];
	$arrayTotal[6] += $row['valor_entrada'];
	$arrayTotal[7] += $row['cantidad_salida'];
	$arrayTotal[8] += $row['valor_salida'];
	$arrayTotal[9] += $row['existencia'];
	$arrayTotal[10] += $row['valor_existencia'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":J".$ultimo);

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotal[5]);
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotal[6]);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal[7]);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal[8]);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal[9]);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal[10]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":"."J".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":D".$contFila);

cabeceraExcel($objPHPExcel, $idEmpresa, "J");

$tituloDcto = "Catálogo Valuado";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
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