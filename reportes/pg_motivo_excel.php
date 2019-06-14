<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../connections/conex.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$lstModuloAdministracion = array("CP" => "Cuentas por Pagar", "CC" => "Cuentas por Cobrar", "TE" => "Tesoreria", "CJ" => "Caja");
	foreach ($lstModuloAdministracion as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[0]))) {
			$arrayModuloAdministracion[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModuloAdministracion)) ? implode(", ", $arrayModuloAdministracion) : "");
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$lstTipoTransaccion = array("I" => "Ingreso", "E" => "Egreso");
	foreach ($lstTipoTransaccion as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[1]))) {
			$arrayTipoTransaccion[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Tipo Transacción: ".((isset($arrayTipoTransaccion)) ? implode(", ", $arrayTipoTransaccion) : "");
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[2];
}

////////// CRITERIO DE BUSQUEDA //////////
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("motivo.modulo IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[0])."'", "defined", "'".str_replace(",","','",$valCadBusq[0])."'"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("motivo.ingreso_egreso IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[1])."'", "defined", "'".str_replace(",","','",$valCadBusq[1])."'"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(motivo.descripcion LIKE %s)",
		valTpDato("%".$valCadBusq[2]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT motivo.*,
	(CASE motivo.modulo
		WHEN 'CC' THEN	'Cuentas por Cobrar'
		WHEN 'CP' THEN	'Cuentas por Pagar'
		WHEN 'CJ' THEN	'Caja'
		WHEN 'TE' THEN	'Tesorería'
	END) AS descripcion_modulo_transaccion,
	
	(CASE motivo.ingreso_egreso
		WHEN 'I' THEN	'Ingreso'
		WHEN 'E' THEN	'Egreso'
	END) AS descripcion_tipo_transaccion,
	
	((SELECT COUNT(cxc_nd_det_motivo.id_motivo) FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
		WHERE cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		+ (SELECT COUNT(cxc_nc_det_motivo.id_motivo) FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			WHERE cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		+ (SELECT COUNT(cxp_nd_det_motivo.id_motivo) FROM cp_notacargo_detalle_motivo cxp_nd_det_motivo
			WHERE cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
		+ (SELECT COUNT(cxp_nc_det_motivo.id_motivo) FROM cp_notacredito_detalle_motivo cxp_nc_det_motivo
			WHERE cxp_nc_det_motivo.id_motivo = motivo.id_motivo)
		+ (SELECT COUNT(te_dep.id_motivo) FROM te_depositos te_dep
			WHERE te_dep.id_motivo = motivo.id_motivo)
		+ (SELECT COUNT(te_nc.id_motivo) FROM te_nota_credito te_nc
			WHERE te_nc.id_motivo = motivo.id_motivo)
		+ (SELECT COUNT(te_nd.id_motivo) FROM te_nota_debito te_nd
			WHERE te_nd.id_motivo = motivo.id_motivo)) AS cantidad_documentos
FROM pg_motivo motivo %s
ORDER BY motivo.id_motivo DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Id");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripcion");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Módulo");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Tipo Transacción");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Cant. Dctos.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, $row['id_motivo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['descripcion']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, ($row['descripcion_modulo_transaccion']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, ($row['descripcion_tipo_transaccion']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['cantidad_documentos']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":E".$ultimo);
	
cabeceraExcel($objPHPExcel, $idEmpresa, "E", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Motivos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:E7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:E9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

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