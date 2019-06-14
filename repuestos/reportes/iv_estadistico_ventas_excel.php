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
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("query.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_anual.ano = %s",
		valTpDato($valCadBusq[1], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cierre_anual_p.ano = (%s - 1)",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("art_emp.clasificacion IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[5], "int"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	query.id_cierre_anual,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	art_emp.clasificacion,
	query.id_empresa,
	query.id_articulo,
	query.ano,
	query.ano_p,
	SUM(query.enero) AS enero,
	SUM(query.enero_p) AS enero_p,
	SUM(query.febrero) AS febrero,
	SUM(query.febrero_p) AS febrero_p,
	SUM(query.marzo) AS marzo,
	SUM(query.marzo_p) AS marzo_p,
	SUM(query.abril) AS abril,
	SUM(query.abril_p) AS abril_p,
	SUM(query.mayo) AS mayo,
	SUM(query.mayo_p) AS mayo_p,
	SUM(query.junio) AS junio,
	SUM(query.junio_p) AS junio_p,
	SUM(query.julio) AS julio,
	SUM(query.julio_p) AS julio_p,
	SUM(query.agosto) AS agosto,
	SUM(query.agosto_p) AS agosto_p,
	SUM(query.septiembre) AS septiembre,
	SUM(query.septiembre_p) AS septiembre_p,
	SUM(query.octubre) AS octubre,
	SUM(query.octubre_p) AS octubre_p,
	SUM(query.noviembre) AS noviembre,
	SUM(query.noviembre_p) AS noviembre_p,
	SUM(query.diciembre) AS diciembre,
	SUM(query.diciembre_p) AS diciembre_p,
	SUM(query.cantidad_saldo) AS cantidad_saldo,
	SUM(query.cantidad_saldo_p) AS cantidad_saldo_p,
	SUM(query.total) AS total,
	SUM(query.total_p) AS total_p,
	SUM(query.promedio) AS promedio,
	SUM(query.promedio_p) AS promedio_p,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM iv_articulos art
	INNER JOIN (SELECT
		cierre_anual.id_cierre_anual,
		cierre_anual.id_empresa,
		cierre_anual.id_articulo,
		cierre_anual.ano,
		(cierre_anual.ano - 1) AS ano_p,
		cierre_anual.enero,
		0 AS enero_p,
		cierre_anual.febrero,
		0 AS febrero_p,
		cierre_anual.marzo,
		0 AS marzo_p,
		cierre_anual.abril,
		0 AS abril_p,
		cierre_anual.mayo,
		0 AS mayo_p,
		cierre_anual.junio,
		0 AS junio_p,
		cierre_anual.julio,
		0 AS julio_p,
		cierre_anual.agosto,
		0 AS agosto_p,
		cierre_anual.septiembre,
		0 AS septiembre_p,
		cierre_anual.octubre,
		0 AS octubre_p,
		cierre_anual.noviembre,
		0 AS noviembre_p,
		cierre_anual.diciembre,
		0 AS diciembre_p,
		cierre_anual.cantidad_saldo,
		0 AS cantidad_saldo_p,
		
		(IFNULL(cierre_anual.enero, 0) + IFNULL(cierre_anual.febrero, 0) + IFNULL(cierre_anual.marzo, 0) + IFNULL(cierre_anual.abril, 0) + IFNULL(cierre_anual.mayo, 0) + IFNULL(cierre_anual.junio, 0) + IFNULL(cierre_anual.julio, 0) + IFNULL(cierre_anual.agosto, 0) + IFNULL(cierre_anual.septiembre, 0) + IFNULL(cierre_anual.octubre, 0) + IFNULL(cierre_anual.noviembre, 0) + IFNULL(cierre_anual.diciembre, 0)) AS total,
		0 AS total_p,
		
		((IFNULL(cierre_anual.enero, 0) + IFNULL(cierre_anual.febrero, 0) + IFNULL(cierre_anual.marzo, 0) + IFNULL(cierre_anual.abril, 0) + IFNULL(cierre_anual.mayo, 0) + IFNULL(cierre_anual.junio, 0) + IFNULL(cierre_anual.julio, 0) + IFNULL(cierre_anual.agosto, 0) + IFNULL(cierre_anual.septiembre, 0) + IFNULL(cierre_anual.octubre, 0) + IFNULL(cierre_anual.noviembre, 0) + IFNULL(cierre_anual.diciembre, 0)) / 12) AS promedio,
		0 AS promedio_p
	FROM iv_cierre_anual cierre_anual %s
		
	UNION
	
	SELECT 
		cierre_anual_p.id_cierre_anual,
		cierre_anual_p.id_empresa,
		cierre_anual_p.id_articulo,
		(cierre_anual_p.ano + 1) AS ano,
		cierre_anual_p.ano AS ano_p,
		0 AS enero,
		cierre_anual_p.enero,
		0 AS febrero,
		cierre_anual_p.febrero,
		0 AS marzo,
		cierre_anual_p.marzo,
		0 AS abril,
		cierre_anual_p.abril,
		0 AS mayo,
		cierre_anual_p.mayo,
		0 AS junio,
		cierre_anual_p.junio,
		0 AS julio,
		cierre_anual_p.julio,
		0 AS agosto,
		cierre_anual_p.agosto,
		0 AS septiembre,
		cierre_anual_p.septiembre,
		0 AS octubre,
		cierre_anual_p.octubre,
		0 AS noviembre,
		cierre_anual_p.noviembre,
		0 AS diciembre,
		cierre_anual_p.diciembre,
		0 AS cantidad_saldo,
		cierre_anual_p.cantidad_saldo,
		
		0 AS total,
		(IFNULL(cierre_anual_p.enero, 0) + IFNULL(cierre_anual_p.febrero, 0) + IFNULL(cierre_anual_p.marzo, 0) + IFNULL(cierre_anual_p.abril, 0) + IFNULL(cierre_anual_p.mayo, 0) + IFNULL(cierre_anual_p.junio, 0) + IFNULL(cierre_anual_p.julio, 0) + IFNULL(cierre_anual_p.agosto, 0) + IFNULL(cierre_anual_p.septiembre, 0) + IFNULL(cierre_anual_p.octubre, 0) + IFNULL(cierre_anual_p.noviembre, 0) + IFNULL(cierre_anual_p.diciembre, 0)) AS total,
		
		0 AS promedio,
		((IFNULL(cierre_anual_p.enero, 0) + IFNULL(cierre_anual_p.febrero, 0) + IFNULL(cierre_anual_p.marzo, 0) + IFNULL(cierre_anual_p.abril, 0) + IFNULL(cierre_anual_p.mayo, 0) + IFNULL(cierre_anual_p.junio, 0) + IFNULL(cierre_anual_p.julio, 0) + IFNULL(cierre_anual_p.agosto, 0) + IFNULL(cierre_anual_p.septiembre, 0) + IFNULL(cierre_anual_p.octubre, 0) + IFNULL(cierre_anual_p.noviembre, 0) + IFNULL(cierre_anual_p.diciembre, 0)) / 12) AS promedio
	FROM iv_cierre_anual cierre_anual_p %s) AS query ON (art.id_articulo = query.id_articulo)
	LEFT JOIN iv_articulos_empresa art_emp ON (query.id_empresa = art_emp.id_empresa AND query.id_articulo = art_emp.id_articulo)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (query.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
GROUP BY
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	art.clasificacion,
	query.id_empresa,
	query.id_articulo,
	query.ano,
	query.ano_p
ORDER BY art.clasificacion ASC", $sqlBusq, $sqlBusq2, $sqlBusq3);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$query);

$contFila = 0;

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "");

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Clasif.");

/*------------------------------------------------------------------------------------*/
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Ultima Fecha de Compra");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Ultima Fecha de Venta");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Costo Promedio");
/*------------------------------------------------------------------------------------*/
$col1 = "G";
if (in_array(1,explode(",",$valCadBusq[2]))) {
	for ($i = 1; $i <= 12; $i++) {
		$col1++;
		($i == 1) ? $colXAnoAct = $col1 : "";
		($i == 1) ? $colYAnoAct = ($contFila - 1) : "";
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, $mes[$i]);
	}
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, "Total");
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, "Prom");
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, "Saldo");
	$objPHPExcel->getActiveSheet()->mergeCells($colXAnoAct.$colYAnoAct.":".$col1.$colYAnoAct);
}

if (in_array(2,explode(",",$valCadBusq[2]))) {
	for ($i = 1; $i <= 12; $i++) {
		$col1++;
		($i == 1) ? $colXAnoAnt = $col1 : "";
		($i == 1) ? $colYAnoAnt = ($contFila - 1) : "";
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, $mes[$i]);
	}
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, "Total");
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, "Prom");
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, "Saldo");
	$objPHPExcel->getActiveSheet()->mergeCells($colXAnoAnt.$colYAnoAnt.":".$col1.$colYAnoAnt);
}

$objPHPExcel->getActiveSheet()->getStyle("A".($contFila - 1).":".$col1.($contFila - 1))->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col1.$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
/*-------------------------------------------------------------------------------------------------------------------------------------*/
	$query_compra = "SELECT MAX(iv_pedido_compra.fecha) AS ultima_fecha 
				FROM iv_pedido_compra 
					INNER JOIN iv_pedido_compra_detalle
						ON iv_pedido_compra.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra 
				WHERE iv_pedido_compra_detalle.id_articulo =" . $row['id_articulo'] . " AND estatus_pedido_compra = 3";
	$rsCompra = mysql_query($query_compra);
	if (!$rsCompra ) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$query_compra);
	$rowCompra = mysql_fetch_assoc($rsCompra);

	$query_venta = "SELECT MAX(iv_pedido_venta.fecha) AS ultima_fecha 
				FROM iv_pedido_venta 
					INNER JOIN iv_pedido_venta_detalle
						ON iv_pedido_venta.id_pedido_venta = iv_pedido_venta_detalle.id_pedido_venta 
				WHERE iv_pedido_venta_detalle.id_articulo =" . $row['id_articulo'] . " AND estatus_pedido_venta = 3";
	$rsventa = mysql_query($query_venta);
	if (!$rsventa ) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$query_venta);
	$rowVenta = mysql_fetch_assoc($rsventa);

	$query_costo = "SELECT costo_promedio FROM iv_articulos_costos 
	WHERE id_empresa=" .  $row['id_empresa'] . " AND id_articulo =" . $row['id_articulo'];
	$rscosto = mysql_query($query_costo);
	if (!$rscosto ) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$query_costo);
	$rowCosto = mysql_fetch_assoc($rscosto);
/*-------------------------------------------------------------------------------------------------------------------------------------*/
	
	if (in_array(1,explode(",",$valCadBusq[2]))) {
		$arrayAnoActual = array(
			"ano" => $row['ano'],
			"enero" => $row['enero'],
			"febrero" => $row['febrero'],
			"marzo" => $row['marzo'],
			"abril" => $row['abril'],
			"mayo" => $row['mayo'],
			"junio" => $row['junio'],
			"julio" => $row['julio'],
			"agosto" => $row['agosto'],
			"septiembre" => $row['septiembre'],
			"octubre" => $row['octubre'],
			"noviembre" => $row['noviembre'],
			"diciembre" => $row['diciembre'],
			"total" => $row['total'],
			"promedio" => $row['promedio'],
			"cantidad_saldo" => $row['cantidad_saldo']);
	}
	if (in_array(2,explode(",",$valCadBusq[2]))) {
		$arrayAnoAnterior = array(
			"ano" => $row['ano_p'],
			"enero" => $row['enero_p'],
			"febrero" => $row['febrero_p'],
			"marzo" => $row['marzo_p'],
			"abril" => $row['abril_p'],
			"mayo" => $row['mayo_p'],
			"junio" => $row['junio_p'],
			"julio" => $row['julio_p'],
			"agosto" => $row['agosto_p'],
			"septiembre" => $row['septiembre_p'],
			"octubre" => $row['octubre_p'],
			"noviembre" => $row['noviembre_p'],
			"diciembre" => $row['diciembre_p'],
			"total" => $row['total_p'],
			"promedio" => $row['promedio_p'],
			"cantidad_saldo" => $row['cantidad_saldo_p']);
	}
	
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, $row['nombre_empresa'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	/*------------------------------------------------------------------------------------------------------------------------------*/
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $rowCompra['ultima_fecha'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $rowVenta['ultima_fecha'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $rowCosto['costo_promedio'], PHPExcel_Cell_DataType::TYPE_STRING);
	/*------------------------------------------------------------------------------------------------------------------------------*/
	
	$col1 = "G";
	if (count($arrayAnoActual) > 0) {
		$objPHPExcel->getActiveSheet()->setCellValue($colXAnoAct.$colYAnoAct, "Año ".$arrayAnoActual['ano']);
		for ($i = 1; $i <= 12; $i++) {
			$col1++;
			$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoActual[strtolower($mes[$i])],"cero_por_vacio"));
			$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		}
		$col1++;
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoActual['total'],"cero_por_vacio"));
		$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$col1++;
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoActual['promedio'],"cero_por_vacio"));
		$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$col1++;
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoActual['cantidad_saldo'],"cero_por_vacio"));
		$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
	
	if (count($arrayAnoAnterior) > 0) {
		$objPHPExcel->getActiveSheet()->setCellValue($colXAnoAnt.$colYAnoAnt, "Año ".$arrayAnoAnterior['ano']);
		for ($i = 1; $i <= 12; $i++) {
			$col1++;
			$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoAnterior[strtolower($mes[$i])],"cero_por_vacio"));
			$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		}
		$col1++;
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoAnterior['total'],"cero_por_vacio"));
		$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$col1++;
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoAnterior['promedio'],"cero_por_vacio"));
		$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$col1++;
		$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, valTpDato($arrayAnoAnterior['cantidad_saldo'],"cero_por_vacio"));
		$objPHPExcel->getActiveSheet()->getStyle($col1.$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col1.$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".$col1.$ultimo);

for ($col = "A"; $col != $col1; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, $col1);

$tituloDcto = "Estadistico de Ventas";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".$col1."7");

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