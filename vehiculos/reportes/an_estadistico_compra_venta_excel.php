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
$sqlBusq .= $cond.sprintf("kardex.tipoMovimiento IN (1)");

$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("kardex.tipoMovimiento IN (3)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("unidad_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("kardex.fechaMovimiento BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("kardex.fechaMovimiento BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_marca LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s)",
		valTpDato($valCadBusq[3], "int"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	vw_iv_modelo.id_uni_bas,
	vw_iv_modelo.nom_uni_bas,
	CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	vw_iv_modelo.nom_ano,
	SUM(kardex_compra_venta.cantidad_compra) AS total_cantidad_compra,
	SUM(kardex_compra_venta.cantidad_compra * kardex_compra_venta.costo) AS total_costo_compra,
	SUM(kardex_compra_venta.cantidad_venta) AS total_cantidad_venta,
	SUM(kardex_compra_venta.cantidad_venta * kardex_compra_venta.precio) AS total_precio_venta
FROM sa_unidad_empresa unidad_emp
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
	LEFT JOIN (SELECT 
			kardex.idKardex,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
					END)
			END) AS id_empresa,
			kardex.idUnidadBasica,
			0 AS cantidad_venta,
			0 AS precio,
			kardex.cantidad AS cantidad_compra,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					uni_fis.precio_compra
				WHEN 2 THEN -- ENTRADA
					(CASE tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							uni_fis.precio_compra
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT fact_vent_det_vehic.costo_compra
							FROM cj_cc_notacredito nota_cred
								INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
							WHERE nota_cred.idNotaCredito =kardex.id_documento
								AND nota_cred.idDepartamentoNotaCredito = 2
								AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT fact_vent_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
					WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
						AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN -- SALIDA
					(CASE tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							uni_fis.precio_compra
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							uni_fis.precio_compra
					END)
			END) AS costo
		FROM an_kardex kardex
			INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica) %s
		
		UNION 
		
		SELECT 
			kardex.idKardex,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
					END)
			END) AS id_empresa,
			kardex.idUnidadBasica,
			kardex.cantidad AS cantidad_venta,
			(CASE tipoMovimiento
				WHEN 1 THEN -- COMPRA
					uni_fis.precio_compra
				WHEN 2 THEN -- ENTRADA
					(CASE tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							uni_fis.precio_compra
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT fact_vent_det_vehic.precio_unitario
							FROM cj_cc_notacredito nota_cred
								INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
							WHERE nota_cred.idNotaCredito = kardex.id_documento
								AND nota_cred.idDepartamentoNotaCredito = 2
								AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT fact_vent_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
					WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
						AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN -- SALIDA
					(CASE tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							uni_fis.precio_compra
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							uni_fis.precio_compra
					END)
			END) AS precio,
			0 AS cantidad_compra,
			0 AS costo
		FROM an_kardex kardex
			INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica) %s) kardex_compra_venta ON (vw_iv_modelo.id_uni_bas = kardex_compra_venta.idUnidadBasica)
			AND (unidad_emp.id_empresa = kardex_compra_venta.id_empresa) %s
GROUP BY vw_iv_modelo.id_uni_bas
HAVING SUM(kardex_compra_venta.cantidad_compra) > 0
	OR SUM(kardex_compra_venta.cantidad_venta) > 0
ORDER BY vw_iv_modelo.nom_uni_bas ASC", $sqlBusq, $sqlBusq2, $sqlBusq3);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Unidad Básica");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Año");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Cantidad Compra");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Total Compra");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Cantidad Venta");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Total Venta");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":G".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($row['nom_uni_bas']));
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['vehiculo']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nom_ano']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['total_cantidad_compra']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['total_costo_compra']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['total_cantidad_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['total_precio_venta']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":G".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
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

cabeceraExcel($objPHPExcel, $idEmpresa, "G");

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