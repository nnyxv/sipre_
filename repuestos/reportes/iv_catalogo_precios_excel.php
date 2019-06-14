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
$sqlBusq .= $cond.sprintf("precio.porcentaje <> 0 AND precio.estatus IN (1,2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}
	
$queryPrecio = sprintf("SELECT *
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

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion LIKE %s",
		valTpDato($valCadBusq[3], "text"));
}

if (in_array(1,explode(",",$valCadBusq[4]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica > 0");
}

if (in_array(2,explode(",",$valCadBusq[4]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica <= 0");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[5], "text"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s
	OR (SELECT COUNT(art_costo.id_articulo_costo) FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = vw_iv_art_emp.id_articulo
			AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
			AND art_costo.id_articulo_costo LIKE %s) > 0)",
		valTpDato($valCadBusq[6], "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT vw_iv_art_emp.*,
	art.posee_iva
FROM vw_iv_articulos_empresa vw_iv_art_emp
	INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s
ORDER BY art.codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Aplica Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Unid. Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Lote");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Unid. Disponible");
$col1 = "G";
while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
	$col1++;
	$objPHPExcel->getActiveSheet()->setCellValue($col1.$contFila, $rowPrecio['descripcion_precio']);
	$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col1.$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$idEmpresa = $row['id_empresa'];
	$idArticulo = $row['id_articulo'];
	
	$queryArtCosto = sprintf("SELECT
		vw_iv_art_almacen_costo.id_articulo_costo,
		SUM(cantidad_disponible_logica) AS cantidad_disponible_logica
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
	WHERE vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0
	GROUP BY vw_iv_art_almacen_costo.id_articulo_costo
	ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", 
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, (($row['posee_iva'] == 1) ? "Si" : "No"));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['cantidad_disponible_logica']);
	if ($arrayIdPrecio) {
		$contFila2 = 0;
		$col2 = "E";
		if ($totalRowsArtCosto > 0) {
			while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
				$contFila2++;
				
				$col2++;
				$objPHPExcel->getActiveSheet()->setCellValue($col2.$contFila, $rowArtCosto['id_articulo_costo']);
				$col2++;
				$objPHPExcel->getActiveSheet()->setCellValue($col2.$contFila, $rowArtCosto['cantidad_disponible_logica']);
				
				$contFila3 = 0;
				foreach ($arrayIdPrecio as $indice => $valor) {
					$contFila3++;
					
					$queryPrecio = sprintf("SELECT
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
					$rsPrecio = mysql_query($queryPrecio);
					if (!$rsPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
					$rowPrecio = mysql_fetch_assoc($rsPrecio);
					
					$col2++;
					$objPHPExcel->getActiveSheet()->setCellValue($col2.$contFila, $rowPrecio['precio_unitario']);
					
					$objPHPExcel->getActiveSheet()->getStyle($col2.$contFila)->getNumberFormat()->setFormatCode('"'.$rowPrecio['abreviacion_moneda'].'"#,##0.00');
				}
			}
		}
	}
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col1.$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".$col1.$ultimo);

for ($col = "A"; $col != $col1; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, $col1);

$tituloDcto = "Catálogo de Precios";
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