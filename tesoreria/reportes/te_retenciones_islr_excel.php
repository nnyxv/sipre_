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

////////// CRITERIO DE BUSQUEDA //////////
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_te_retencion_cheque.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}
	
if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_te_retencion_cheque.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
}
	
if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE_FORMAT(vw_te_retencion_cheque.fecha_registro, %s) = %s",
		valTpDato('%Y/%m', "text"),
		valTpDato(date("Y/m",strtotime('01-'.$valCadBusq[2])), "text"));
}
	
if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_te_retencion_cheque.numero_factura LIKE %s 
	OR vw_te_retencion_cheque.numero_control_factura LIKE %s)",
		valTpDato('%'.$valCadBusq[3].'%', 'text'),
		valTpDato('%'.$valCadBusq[3].'%', 'text'));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if($valCadBusq[4] == 1){
		$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado = 1 ");
	}else{
		$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado IS NULL ");
	}
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_te_retencion_cheque.tipo_documento = %s",
		valTpDato($valCadBusq[5], "int"));
}

//iteramos para los resultados
$query = sprintf("SELECT 
	vw_te_retencion_cheque.id_retencion_cheque,
	vw_te_retencion_cheque.id_cheque,
	vw_te_retencion_cheque.rif_proveedor,
	vw_te_retencion_cheque.nombre,
	vw_te_retencion_cheque.numero_control_factura,
	vw_te_retencion_cheque.numero_factura,
	vw_te_retencion_cheque.id_factura,
	vw_te_retencion_cheque.codigo,
	vw_te_retencion_cheque.subtotal_factura,
	vw_te_retencion_cheque.monto_retenido,
	vw_te_retencion_cheque.porcentaje_retencion,
	vw_te_retencion_cheque.descripcion,
	vw_te_retencion_cheque.base_imponible_retencion,
	vw_te_retencion_cheque.sustraendo_retencion,
	vw_te_retencion_cheque.tipo,
	vw_te_retencion_cheque.tipo_documento, 
	vw_te_retencion_cheque.anulado,
	vw_te_retencion_cheque.fecha_registro
FROM vw_te_retencion_cheque %s
ORDER BY vw_te_retencion_cheque.id_retencion_cheque ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Estado");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "RIF Retenido");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Tipo Doc.");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Documento");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Tipo Pago");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Monto Operación");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Nro Comprobante");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Retención");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Código Concepto");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Base Retención");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Monto Retenido");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Porcentaje Retención");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Sustraendo Retención");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$contb++;
	if ($row['anulado'] == 1){// NULL = Activo, 1 = Anulado
		$imgAnulado = "Anulado";
	} else{
		$imgAnulado = "Activo";
	}
	
	$tipoDocumento = ($row["tipo"] == 0) ? "FA" : "ND"; // 0 = FA, 1 = ND
	
	$tipoPago = ""; // NULL = ninguno, 0 = CH, 1 = TR
	if($row["tipo_documento"] == 0){
		$tipoPago = "CH";
	}elseif($row["tipo_documento"] == 1){
		$tipoPago = "TR";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $contb);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgAnulado);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, date(spanDateFormat, strtotime($row['fecha_registro'])));	
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['rif_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['nombre']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($tipoDocumento));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['numero_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['numero_control_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($tipoPago));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['subtotal_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['id_retencion_cheque']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['descripcion']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, ($row['codigo']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, ($row['base_imponible_retencion']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, ($row['monto_retenido']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, ($row['porcentaje_retencion']));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, ($row['sustraendo_retencion']));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	$cont += $row['monto_retenido'];
}

$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":Q".$ultimo);
	
for ($col = "A"; $col != "Q"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "Q", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Retenciones ISLR";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:Q7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:Q9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE 3.0");
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