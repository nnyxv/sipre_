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
//$idEmpresa = $valCadBusq[0];
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp_det.id_factura_compra = %s",
		valTpDato($valCadBusq[0], "int"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	(SELECT id_empresa FROM iv_factura_compra fact_comp
	WHERE fact_comp.id_factura_compra = fact_comp_det.id_factura_compra) AS id_empresa,
	
	(SELECT numero_factura_proveedor FROM iv_factura_compra fact_comp
	WHERE fact_comp.id_factura_compra = fact_comp_det.id_factura_compra) AS numero_factura_proveedor, 
	
	vw_iv_casilla.descripcion_almacen,
	vw_iv_casilla.ubicacion,
	art.codigo_articulo,
	art.descripcion,
	arancel_familia.codigo_arancel,
	fact_comp_det.cantidad,
	fact_comp_det.pendiente,
	ped_comp.id_pedido_compra_referencia,
	fact_comp_det.precio_unitario,
	fact_comp_det.gasto_unitario,
	fact_comp_det.peso_unitario,
	fact_comp_det.iva,
	arancel_grupo.porcentaje_grupo
FROM iv_factura_compra_detalle fact_comp_det
	INNER JOIN vw_iv_casillas vw_iv_casilla ON (fact_comp_det.id_casilla = vw_iv_casilla.id_casilla)
	INNER JOIN iv_articulos art ON (fact_comp_det.id_articulo = art.id_articulo)
	LEFT JOIN pg_arancel_familia arancel_familia ON (art.id_arancel_familia = arancel_familia.id_arancel_familia)
	INNER JOIN iv_pedido_compra ped_comp ON (fact_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
	INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_familia.id_arancel_grupo = arancel_grupo.id_arancel_grupo) %s
ORDER BY id_factura_compra_detalle ASC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro.");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Almacén");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Código Arancel");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Ped.");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Recib.");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Pend.");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Ref.");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Costo Unit.");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "% Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "% ADV");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Peso Unit. (g)");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Gasto");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Total");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	$contFila2++;
	
	$idEmpresa = $row['id_empresa'];
	$numeroFacturaProveedor = $row['numero_factura_proveedor'];
	$cantPedida = doubleval($row['cantidad']);
	$cantRecibida = doubleval($row['pendiente']);
	$costoUnitario = doubleval($row['precio_unitario']);
	$porcIvaArt = doubleval($row['iva']);
	$porcIvaArt = ($porcIvaArt > 0) ? $porcIvaArt : "-";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $contFila2);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['descripcion_almacen'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, str_replace("-[]", "", $row['ubicacion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['codigo_arancel'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $cantPedida);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $cantRecibida);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, ($cantPedida - $cantRecibida));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['id_pedido_compra_referencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $costoUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $porcIvaArt);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['porcentaje_grupo']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['peso_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, ($cantRecibida * $row['gasto_unitario']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, ($cantRecibida * $costoUnitario));
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$totalFacturacion += ($cantRecibida * $costoUnitario);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":P".$ultimo);

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total de Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $totalFacturacion);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo2);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."P".$contFila)->applyFromArray($styleArrayResaltarTotal2);
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

for ($col = "A"; $col != "P"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "P");

$tituloDcto = "Reg. Compra"." ".$numeroFacturaProveedor;
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:P7");

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