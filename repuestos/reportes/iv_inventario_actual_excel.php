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
$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("((vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
	AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo = 1
	AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
	AND vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL)
OR (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
	AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo IS NULL
	AND vw_iv_art_almacen_costo.estatus_articulo_costo IS NULL
	AND vw_iv_art_almacen_costo.id_articulo_costo IS NULL)
OR (vw_iv_art_almacen_costo.cantidad_disponible_logica > 0)
OR (vw_iv_art_almacen_costo.cantidad_reservada > 0))");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_art_almacen_costo.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
		valTpDato($valCadBusq[1], "campo"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.clasificacion IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[2])."'", "defined", "'".str_replace(",","','",$valCadBusq[2])."'"));
}

if (in_array(1,explode(",",$valCadBusq[3]))
|| in_array(2,explode(",",$valCadBusq[3]))
|| in_array(3,explode(",",$valCadBusq[3]))
|| in_array(4,explode(",",$valCadBusq[3]))
|| in_array(5,explode(",",$valCadBusq[3]))) {
	$arrayBusq = array();
	if (in_array(1,explode(",",$valCadBusq[3]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	}
	
	if (in_array(2,explode(",",$valCadBusq[3]))) {
		$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica <= 0");
	}
	
	if (in_array(3,explode(",",$valCadBusq[3]))) {
		$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_reservada > 0");
	}
	
	if (in_array(4,explode(",",$valCadBusq[3]))) {
		$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_espera > 0");
	}
	
	if (in_array(5,explode(",",$valCadBusq[3]))) {
		$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_bloqueada > 0");
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".sprintf(implode(" OR ", $arrayBusq)).")";
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_articulo = %s
	OR vw_iv_art_almacen_costo.id_articulo_costo LIKE %s
	OR vw_iv_art_almacen_costo.descripcion LIKE %s
	OR vw_iv_art_almacen_costo.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[5], "int"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
	
	(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
	WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
	
	(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
		WHEN 1 THEN	vw_iv_art_almacen_costo.costo
		WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
		WHEN 3 THEN	vw_iv_art_almacen_costo.costo
	END) AS costo
FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
	INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s
ORDER BY CONCAT(descripcion_almacen, ubicacion) ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Lote");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Costo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Unid. Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Valor Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Unid. Reservada (Serv.)");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Valor Reservada (Serv.)");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Unid. Espera por Facturar");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Valor Espera por Facturar");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Unid. Bloqueada");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Valor Bloqueada");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":R".$contFila)->applyFromArray($styleArrayColumna);

$objPHPExcel->getActiveSheet()->mergeCells("F".$contFila.":H".$contFila);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$costoUnitario = $row['costo'];
	
	$cantKardex = 0;
	$subTotalKardex = $cantKardex * $costoUnitario;
	
	$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
	$subTotalDisponible = $cantDisponible * $costoUnitario;
	
	$cantReservada = $row['cantidad_reservada'];
	$subTotalReservada = $cantReservada * $costoUnitario;
	
	$cantDiferencia = $row['existencia'] - 0;
	$subTotalDiferencia = $cantDiferencia * $costoUnitario;
	
	$cantEspera = $row['cantidad_espera'];
	$subTotalEspera = $cantEspera * $costoUnitario;
	
	$cantBloqueada = $row['cantidad_bloqueada'];
	$subTotalBloqueada = $cantBloqueada * $costoUnitario;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter(utf8_encode($row['codigo_articulo']),";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, utf8_encode($row['descripcion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['codigo_articulo_prov'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['unidad']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['clasificacion']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['descripcion_almacen']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, str_replace("-[]", "", utf8_encode($row['ubicacion'])));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($row['estatus_articulo_almacen'] == 1) ? "" : "(Relación Inactiva)"));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['id_articulo_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $costoUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $cantDisponible);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $subTotalDisponible);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $cantReservada);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $subTotalReservada);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $cantEspera);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $subTotalEspera);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $cantBloqueada);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $subTotalBloqueada);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":R".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	// TOTALES
	$arrayTotalPagina[10] += $cantDisponible;
	$arrayTotalPagina[11] += $subTotalDisponible;
	$arrayTotalPagina[12] += $cantReservada;
	$arrayTotalPagina[13] += $subTotalReservada;
	$arrayTotalPagina[14] += $cantEspera;
	$arrayTotalPagina[15] += $subTotalEspera;
	$arrayTotalPagina[16] += $cantBloqueada;
	$arrayTotalPagina[17] += $subTotalBloqueada;
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":R".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotalPagina[10]);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotalPagina[11]);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotalPagina[12]);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotalPagina[13]);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $arrayTotalPagina[14]);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $arrayTotalPagina[15]);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotalPagina[16]);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotalPagina[17]);
	
$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":J".$contFila);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("K".$contFila.":"."R".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

for ($col = "A"; $col != "R"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "R");

$tituloDcto = "Inventario Actual";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:R7");

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