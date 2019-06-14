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
$sqlBusq .= $cond.sprintf("kardex.tipo_movimiento IN (1)");

$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("kardex.tipo_movimiento IN (3)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("vw_iv_art_emp_datos_basicos.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(kardex.fecha_movimiento) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("DATE(kardex.fecha_movimiento) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("vw_iv_art_emp_datos_basicos.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[3], "text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(vw_iv_art_emp_datos_basicos.id_articulo = %s
	OR vw_iv_art_emp_datos_basicos.descripcion LIKE %s
	OR vw_iv_art_emp_datos_basicos.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[4], "int"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	vw_iv_art_emp_datos_basicos.id_articulo,
	vw_iv_art_emp_datos_basicos.codigo_articulo,
	vw_iv_art_emp_datos_basicos.descripcion,
	vw_iv_art_emp_datos_basicos.clasificacion,
	SUM(kardex_compra_venta.cantidad_compra) AS total_cantidad_compra,
	SUM(kardex_compra_venta.cantidad_compra * (kardex_compra_venta.costo + kardex_compra_venta.costo_cargo - kardex_compra_venta.subtotal_descuento)) AS total_costo_compra,
	SUM(kardex_compra_venta.cantidad_venta) AS total_cantidad_venta,
	SUM(kardex_compra_venta.cantidad_venta * (kardex_compra_venta.precio - kardex_compra_venta.subtotal_descuento)) AS total_precio_venta
FROM vw_iv_articulos_empresa_datos_basicos vw_iv_art_emp_datos_basicos
	LEFT JOIN (SELECT 
			kardex.id_kardex,
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN -- REPUESTOS
									(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN -- SERVICIOS
									(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) AS id_empresa,
			kardex.id_articulo,
			0 AS cantidad_venta,
			0 AS precio,
			kardex.cantidad AS cantidad_compra,
			kardex.costo,
			kardex.costo_cargo,
			kardex.subtotal_descuento
		FROM iv_kardex kardex %s
		
		UNION 
		
		SELECT 
			kardex.id_kardex,
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN -- REPUESTOS
									(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN -- SERVICIOS
									(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) AS id_empresa,
			kardex.id_articulo,
			kardex.cantidad AS cantidad_venta,
			kardex.precio,
			0 AS cantidad_compra,
			0 AS costo,
			0 AS costo_cargo,
			kardex.subtotal_descuento
		FROM iv_kardex kardex %s) kardex_compra_venta ON (vw_iv_art_emp_datos_basicos.id_articulo = kardex_compra_venta.id_articulo)
			AND (vw_iv_art_emp_datos_basicos.id_empresa = kardex_compra_venta.id_empresa) %s
GROUP BY vw_iv_art_emp_datos_basicos.id_articulo
HAVING SUM(kardex_compra_venta.cantidad_compra) > 0
	OR SUM(kardex_compra_venta.cantidad_venta) > 0
ORDER BY vw_iv_art_emp_datos_basicos.clasificacion ASC", $sqlBusq, $sqlBusq2, $sqlBusq3);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Cantidad Compra");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Total Compra");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Cantidad Venta");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Total Venta");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":G".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['descripcion']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['clasificacion']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['total_cantidad_compra']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['total_costo_compra']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['total_cantidad_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['total_precio_venta']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":G".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	// TOTALES
	$arrayTotalPagina[5] += $row['total_cantidad_compra'];
	$arrayTotalPagina[6] += $row['total_costo_compra'];
	$arrayTotalPagina[7] += $row['total_cantidad_venta'];
	$arrayTotalPagina[8] += $row['total_precio_venta'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":G".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotalPagina[5]);
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotalPagina[6]);
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotalPagina[7]);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotalPagina[8]);
	
$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila.":G".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

for ($col = "A"; $col != "G"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "H");

$tituloDcto = "Estadístico de Compras y Ventas";
$tituloHoja = $tituloDcto;
$tituloHoja .= " (".$valCadBusq[1]." al ".$valCadBusq[2].")";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloHoja);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:G7");

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