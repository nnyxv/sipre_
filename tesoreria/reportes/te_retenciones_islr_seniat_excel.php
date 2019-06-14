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
$fechaPeriodo = implode("", array_reverse(explode("-",$valCadBusq[2])));
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

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
	vw_te_retencion_cheque.rif_proveedor,
	vw_te_retencion_cheque.nombre,
	vw_te_retencion_cheque.numero_control_factura,
	vw_te_retencion_cheque.numero_factura,
	vw_te_retencion_cheque.id_factura,
	vw_te_retencion_cheque.codigo,
	vw_te_retencion_cheque.subtotal_factura,
	vw_te_retencion_cheque.monto_retenido,
	vw_te_retencion_cheque.porcentaje_retencion,
	vw_te_retencion_cheque.id_retencion_cheque,
	vw_te_retencion_cheque.base_imponible_retencion,
	vw_te_retencion_cheque.tipo,
	vw_te_retencion_cheque.tipo_documento, 
	vw_te_retencion_cheque.anulado,
	vw_te_retencion_cheque.fecha_registro
FROM vw_te_retencion_cheque %s
ORDER BY id_retencion_cheque ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__.$query);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Agente de Retención: ".$rowEmp['nombre_empresa']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "RIF Agente");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, str_replace("-","",$rowEmp['rif']));

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":F".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":F".$contFila);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("H".$contFila.":I".$contFila);

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Periodo");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $fechaPeriodo);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":F".$contFila);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("H".$contFila.":I".$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Nro. Comprobante (Generado Sistema)");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "RIF Retenido");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Numero Factura");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Numero Control");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Codigo Concepto");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Base imponible");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Porcentaje Retencion");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":I".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$cont++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $cont);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['id_retencion_cheque']);	
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, (str_replace("-","",$row['rif_proveedor'])));	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['numero_factura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, str_replace("00-","",$row['numero_control_factura']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_registro'])));	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['codigo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['base_imponible_retencion']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['porcentaje_retencion']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":I".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
	
for ($col = "A"; $col != "I"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "I", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Retenciones ISLR (SENIAT)";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:I7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:I9");
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