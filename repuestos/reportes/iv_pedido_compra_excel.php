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

$idPedido = $_GET['idPedido'];

// BUSCA LOS DATOS DEL PEDIDO
$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_compra WHERE id_pedido_compra = %s",
	valTpDato($idPedido, "int"));
$rsPedido = mysql_query($queryPedido);
if (!$rsPedido) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPedido = mysql_fetch_assoc($rsPedido);
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro. Referencia");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Código Arancelario");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "% Arancelario");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Ped.");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Recib.");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Pend.");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Costo Unit.");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "% Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Total");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Almacen");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Id Cliente");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":N".$contFila)->applyFromArray($styleArrayColumna);
 
//iteramos para los resultados
$query = sprintf("SELECT 
	ped_comp_det.*,
	vw_iv_art_datos_bas.codigo_articulo,
	vw_iv_art_datos_bas.descripcion,
	vw_iv_art_datos_bas.codigo_articulo_prov,
	arancel_familia.codigo_arancel,
	
	(SELECT SUM(ped_comp_det_impsto.impuesto) FROM iv_pedido_compra_detalle_impuesto ped_comp_det_impsto
	WHERE ped_comp_det_impsto.id_pedido_compra_detalle = ped_comp_det.id_pedido_compra_detalle) AS porc_iva
FROM iv_pedido_compra_detalle ped_comp_det
	INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (ped_comp_det.id_articulo = vw_iv_art_datos_bas.id_articulo)
	LEFT JOIN pg_arancel_familia arancel_familia ON (ped_comp_det.id_arancel_familia = arancel_familia.id_arancel_familia)
WHERE id_pedido_compra = %s
ORDER BY id_pedido_compra_detalle ASC;",
	valTpDato($idPedido, "int"));
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$porcIva = ($row['porc_iva'] > 0 && $row['estatus_iva'] == 1) ? $row['porc_iva'] : "-";
	
	// BUSCA LA UBICACION PREDETERMINADA DEL ARTICULO
	$queryUbicPredet = sprintf("SELECT * FROM vw_iv_articulos_almacen
	WHERE id_empresa = %s
		AND id_articulo = %s
		AND casilla_predeterminada = 1;",
		valTpDato($rowPedido['id_empresa'], "int"),
		valTpDato($row['id_articulo'], "int"));
	$rsUbicPredet = mysql_query($queryUbicPredet);
	if (!$rsUbicPredet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowUbicPredet = mysql_fetch_assoc($rsUbicPredet);
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, $rowPedido['id_pedido_compra_referencia'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, elimCaracter(utf8_encode($row['codigo_articulo']),";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, utf8_encode($row['descripcion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['codigo_arancel']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['porcentaje_grupo']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['cantidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, ($row['cantidad'] - $row['pendiente']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "=F".$contFila."-G".$contFila);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['precio_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $porcIva);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, ($row['cantidad'] * $row['precio_unitario']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, utf8_encode($rowUbicPredet['descripcion_almacen']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, str_replace("-[]", "", utf8_encode($rowUbicPredet['ubicacion'])), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['id_cliente']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":N".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$rowPedido['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$rowPedido['abreviacion_moneda_origen'].'"#,##0.00');
}
$ultimo = $contFila;
//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":L".$ultimo);

for ($col = "A"; $col != "N"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

$tituloDcto = "Pedido Compra Nro. Ref ".$rowPedido['id_pedido_compra_referencia'];

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