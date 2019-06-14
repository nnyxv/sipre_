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
	$sqlBusq .= $cond.sprintf("(vw_iv_art_sol_vent.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_art_sol_vent.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_datos_basicos.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_art_datos_basicos.id_articulo = %s
	OR vw_iv_art_sol_vent.id_orden LIKE %s
	OR vw_iv_art_sol_vent.id_articulo_costo LIKE %s
	OR vw_iv_art_datos_basicos.descripcion LIKE %s
	OR vw_iv_art_datos_basicos.codigo_articulo_prov LIKE %s
	OR vw_iv_art_sol_vent.nombre_cliente LIKE %s)",
		valTpDato($valCadBusq[2], "int"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	vw_iv_art_datos_basicos.id_articulo,
	vw_iv_art_datos_basicos.codigo_articulo,
	vw_iv_art_datos_basicos.descripcion
FROM vw_iv_articulos_solicitud_venta vw_iv_art_sol_vent
	INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_basicos ON (vw_iv_art_sol_vent.id_articulo = vw_iv_art_datos_basicos.id_articulo) %s
ORDER BY vw_iv_art_datos_basicos.codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idArticulo = $row['id_articulo'];
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código:");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, elimCaracter($row['codigo_articulo'],";"));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":L".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Descripción:");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, ($row['descripcion']));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":L".$contFila);
	
	$contFila++;
	$primero = $contFila;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Gecha Orden");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Nro. Orden");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Tipo Orden");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Cliente");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Almacen");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Ubicación");
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Cantidad");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Lote");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $spanPrecioUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Costo Unit.");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Importe Precio");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Importe Costo");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayColumna);
	
	$sqlBusq = NULL;
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_iv_art_sol_vent.id_articulo = %s",
		valTpDato($idArticulo, "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_sol_vent.id_orden LIKE %s
		OR vw_iv_art_sol_vent.id_articulo_costo LIKE %s
		OR vw_iv_art_sol_vent.nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$queryDet = sprintf("SELECT *,
		
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo_unitario
		
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		INNER JOIN vw_iv_articulos_solicitud_venta vw_iv_art_sol_vent ON (vw_iv_art_almacen_costo.id_articulo_costo = vw_iv_art_sol_vent.id_articulo_costo
			AND vw_iv_art_almacen_costo.id_articulo_almacen_costo = vw_iv_art_sol_vent.id_articulo_almacen_costo) %s", $sqlBusq);
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contFila2 = 0;
	$arrayTotalRenglon = NULL;
	while ($rowDet = mysql_fetch_assoc($rsDet)) {
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, date(spanDateFormat, strtotime($rowDet['tiempo_orden'])));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $rowDet['id_orden']);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $rowDet['nombre_tipo_orden']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowDet['nombre_cliente']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($rowDet['descripcion_almacen']));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode(str_replace("-[]", "", $rowDet['ubicacion'])));
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $rowDet['total_cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $rowDet['id_articulo_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $rowDet['precio_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $rowDet['costo_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $rowDet['total_cantidad'] * $rowDet['precio_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $rowDet['total_cantidad'] * $rowDet['costo_unitario']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$rowDet['abreviacion_moneda_local'].'"#,##0.00');
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$rowDet['abreviacion_moneda_local'].'"#,##0.00');
		
		$arrayTotalRenglon[6] += $rowDet['total_cantidad'];
		$arrayTotalRenglon[9] += $rowDet['total_cantidad'] * $rowDet['precio_unitario'];
		$arrayTotalRenglon[10] += $rowDet['total_cantidad'] * $rowDet['costo_unitario'];
	}
	$ultimo = $contFila;
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Totales ".elimCaracter($row['codigo_articulo'],";").":");
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotalRenglon[6]);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotalRenglon[9]);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotalRenglon[10]);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$rowDet['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$rowDet['abreviacion_moneda_local'].'"#,##0.00');
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":F".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
	
	// TOTALES
	$arrayTotalPagina[6] += $arrayTotalRenglon[6];
	$arrayTotalPagina[9] += $arrayTotalRenglon[9];
	$arrayTotalPagina[10] += $arrayTotalRenglon[10];
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total Página:");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotalPagina[6]);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotalPagina[9]);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotalPagina[10]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo2);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal2);

$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":F".$contFila);

for ($col = "A"; $col != "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "L");

$tituloDcto = "Artículos Reservados";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:K7");

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