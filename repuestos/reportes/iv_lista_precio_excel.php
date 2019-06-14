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
$sqlBusq .= $cond.sprintf("precio.lista_precio = 1");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("emp_precio.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("precio.id_precio IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

$queryPrecio = sprintf("SELECT DISTINCT precio.*
FROM pg_empresa_precios emp_precio
	INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s
ORDER BY precio.id_precio ASC;", $sqlBusq);
$rsPrecio = mysql_query($queryPrecio);
if (!$rsPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsPrecio = mysql_num_rows($rsPrecio);


$sqlBusq = "";
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.posee_iva = %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
}

if (in_array(1,explode(",",$valCadBusq[5]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica > 0");
}

if (in_array(2,explode(",",$valCadBusq[5]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica <= 0");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[7], "text"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s
	OR (SELECT COUNT(art_costo.id_articulo_costo) FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = vw_iv_art_emp.id_articulo
			AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
			AND art_costo.id_articulo_costo LIKE %s) > 0)",
		valTpDato($valCadBusq[8], "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	vw_iv_art_emp.id_empresa,
	vw_iv_art_emp.id_articulo,
	vw_iv_art_emp.codigo_articulo,
	vw_iv_art_emp.descripcion,
	art.posee_iva,
	vw_iv_art_emp.cantidad_disponible_fisica,
	vw_iv_art_emp.cantidad_disponible_logica
FROM vw_iv_articulos_empresa vw_iv_art_emp
	INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s
ORDER BY art.codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, ".");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, (in_array(1,explode(",",$valCadBusq[2]))) ? "Código" : "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, (in_array(2,explode(",",$valCadBusq[2]))) ? "Unid. Disponible" : "");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Lote");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, (in_array(2,explode(",",$valCadBusq[2]))) ? "Unid. Disponible" : "");
$ultColum = "F";
while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
	$ultColum++;
	$objPHPExcel->getActiveSheet()->setCellValue($ultColum.$contFila, utf8_encode($rowPrecio['descripcion_precio']));
	$ultColum++;
	$objPHPExcel->getActiveSheet()->setCellValue($ultColum.$contFila, utf8_encode("Impuesto"));
	$ultColum++;
	$objPHPExcel->getActiveSheet()->setCellValue($ultColum.$contFila, utf8_encode("Precio Total"));
	
	$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$ultColum.$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFilaColor, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	$contFilaColor++;
	
	$idEmpresa = $row['id_empresa'];
	$idArticulo = $row['id_articulo'];
	
	$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
	WHERE vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0
	ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", 
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	
	$rowspan = ($totalRowsArtCosto > 0) ? "rowspan=\"".$totalRowsArtCosto."\"" : "";
	
	$imgAplicaIva = ($row['posee_iva'] == 1) ? "Si Aplica Impuesto" : "";
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgAplicaIva);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, (in_array(1,explode(",",$valCadBusq[2]))) ? elimCaracter(utf8_encode($row['codigo_articulo']),";") : "");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['descripcion']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, (in_array(2,explode(",",$valCadBusq[2]))) ? $row['cantidad_disponible_logica'] : "");
	$contFila2 = 0;
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$contFila2++;
		
		$classDisponible = ($rowArtCosto['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		$classDisponible = ($rowArtCosto['estatus_almacen_venta'] == 1) ? $classDisponible : "class=\"divMsjInfo4\"";
		
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, ($rowArtCosto['id_articulo_costo']));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, (in_array(2,explode(",",$valCadBusq[2]))) ? $rowArtCosto['cantidad_disponible_logica'] : "");
			
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		if ($arrayIdPrecio) {
			$ultColum = "F";
			
			$contFila3 = 0;
			foreach ($arrayIdPrecio as $indice => $valor) {
				$style = (fmod($contFila2, 2) == 0) ? "" : "font-weight:bold";
				$contFila3++;
				
				$queryArtPrecio = sprintf("SELECT
					art_precio.id_articulo_precio,
					art_precio.id_precio,
					art_precio.precio AS precio_unitario,
					
					(SELECT iva.observacion
					FROM iv_articulos_impuesto art_impsto
						INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
					WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
						AND art_impsto.id_articulo = art_precio.id_articulo
					LIMIT 1) AS descripcion_impuesto,
					
					(SELECT SUM(iva.iva)
					FROM iv_articulos_impuesto art_impsto
						INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
					WHERE iva.tipo IN (6,9,2)
						AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
					
					(art_precio.precio * (SELECT SUM(iva.iva)
										FROM iv_articulos_impuesto art_impsto
											INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
										WHERE iva.tipo IN (6,9,2)
											AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
					
					moneda.abreviacion AS abreviacion_moneda
				FROM iv_articulos_precios art_precio
					INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
				WHERE art_precio.id_articulo = %s
					AND art_precio.id_articulo_costo = %s
					AND art_precio.id_precio = %s;",
					valTpDato($row['id_articulo'], "int"),
					valTpDato($rowArtCosto['id_articulo_costo'], "int"),
					valTpDato($arrayIdPrecio[$indice][0], "int"));
				$rsArtPrecio = mysql_query($queryArtPrecio);
				if (!$rsArtPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
				
				$ultColum++;
				$objPHPExcel->getActiveSheet()->setCellValue($ultColum.$contFila, ($rowArtPrecio['precio_unitario']));
				$objPHPExcel->getActiveSheet()->getStyle($ultColum.$contFila)->getNumberFormat()->setFormatCode('"'.$rowArtPrecio['abreviacion_moneda'].'"#,##0.00');
				$ultColum++;
				$objPHPExcel->getActiveSheet()->setCellValue($ultColum.$contFila, ($rowArtPrecio['monto_impuesto']));
				$objPHPExcel->getActiveSheet()->getStyle($ultColum.$contFila)->getNumberFormat()->setFormatCode('"'.$rowArtPrecio['abreviacion_moneda'].'"#,##0.00');
				$ultColum++;
				$objPHPExcel->getActiveSheet()->setCellValue($ultColum.$contFila, ($rowArtPrecio['precio_unitario'] + $rowArtPrecio['monto_impuesto']));
				$objPHPExcel->getActiveSheet()->getStyle($ultColum.$contFila)->getNumberFormat()->setFormatCode('"'.$rowArtPrecio['abreviacion_moneda'].'"#,##0.00');
			}
		}
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$ultColum.$contFila)->applyFromArray($clase);
		
		$contFila += ($totalRowsArtCosto > 1) ? 1 : 0;
	}
	
	if ($totalRowsArtCosto > 1) {
		$objPHPExcel->getActiveSheet()->mergeCells("A".($contFila - $totalRowsArtCosto).":A".($contFila - 1));
		$objPHPExcel->getActiveSheet()->mergeCells("B".($contFila - $totalRowsArtCosto).":B".($contFila - 1));
		$objPHPExcel->getActiveSheet()->mergeCells("C".($contFila - $totalRowsArtCosto).":C".($contFila - 1));
		$objPHPExcel->getActiveSheet()->mergeCells("D".($contFila - $totalRowsArtCosto).":D".($contFila - 1));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".($contFila - $totalRowsArtCosto))->getAlignment()->applyFromArray(
			array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			)
		);
		$objPHPExcel->getActiveSheet()->getStyle("B".($contFila - $totalRowsArtCosto))->getAlignment()->applyFromArray(
			array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			)
		);
		$objPHPExcel->getActiveSheet()->getStyle("C".($contFila - $totalRowsArtCosto))->getAlignment()->applyFromArray(
			array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			)
		);
		$objPHPExcel->getActiveSheet()->getStyle("D".($contFila - $totalRowsArtCosto))->getAlignment()->applyFromArray(
			array(
				'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			)
		);
		$objPHPExcel->getActiveSheet()->getStyle("C".($contFila - $totalRowsArtCosto))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
		$objPHPExcel->getActiveSheet()->getStyle("D".($contFila - $totalRowsArtCosto))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$contFila -= 1;
	} else {
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$ultColum.$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
}
$ultFila = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".$ultColum.$ultFila);

for ($col = "A"; $col != $ultColum; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, $ultColum);

$tituloDcto = "Listado de Precios";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".$ultColum."7");

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