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
	$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_costo.estatus = %s",
		valTpDato($valCadBusq[1], "int"));
} else if ($valCadBusq[1] != "-1") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_costo.estatus IS NULL");
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[2], "text"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art_costo.id_articulo_costo LIKE %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[3], "int"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT art_costo.*,
	art_emp.id_empresa,
	prov.id_proveedor,
	prov.nombre AS nombre_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	art.codigo_articulo_prov,
	art.id_tipo_articulo,
	art_emp.clasificacion,
	(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0)) AS existencia,
	IFNULL(art_costo.cantidad_reservada, 0) AS cantidad_reservada,
	(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
	IFNULL(art_costo.cantidad_espera, 0) AS cantidad_espera,
	IFNULL(art_costo.cantidad_bloqueada, 0) AS cantidad_bloqueada,
	(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0) - IFNULL(art_costo.cantidad_espera, 0) - IFNULL(art_costo.cantidad_bloqueada, 0)) AS cantidad_disponible_logica,
	moneda_local.abreviacion AS abreviacion_moneda_local,
	moneda_origen.abreviacion AS abreviacion_moneda_origen,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM iv_articulos art
	INNER JOIN iv_articulos_empresa art_emp ON (art.id_articulo = art_emp.id_articulo)
	LEFT JOIN iv_articulos_costos art_costo ON (art_emp.id_empresa = art_costo.id_empresa)
		AND (art_emp.id_articulo = art_costo.id_articulo)
	LEFT JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
	LEFT JOIN pg_monedas moneda_local ON (art_costo.id_moneda = moneda_local.idmoneda)
	LEFT JOIN pg_monedas moneda_origen ON (art_costo.id_moneda_origen = moneda_origen.idmoneda)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY art.id_articulo DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, ".");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Lote");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Costo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Costo Promedio");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Costo Origen");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Unid. Disponible");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch ($row['estatus']) {
		case 0 : $imgEstatus = "Inactivo"; break;
		case 1 : $imgEstatus = "Activo"; break;
		default : $imgEstatus = "";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['nombre_empresa']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, (($row['fecha'] != "") ? date(spanDateFormat,strtotime($row['fecha'])) : "xx-xx-xxxx"));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['nombre_proveedor']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, elimCaracter($row['codigo_articulo'],";"));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['descripcion']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['codigo_articulo_prov']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['clasificacion']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['id_articulo_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['costo_promedio']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['costo_origen']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['cantidad_disponible_logica']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":M".$ultimo);

for ($col = "A"; $col != "M"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M");

$tituloDcto = "Costos";
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