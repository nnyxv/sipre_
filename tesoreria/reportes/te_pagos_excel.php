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
$idEmpresa = $_GET['empresa'];
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

////////// CRITERIO DE BUSQUEDA //////////
$valForm['buscarDocumento'] = $_GET["tipoDocumento"];
$valForm['hddIdEmpresa'] = $_GET["empresa"];
$valForm['hddBePro'] = $_GET["proveedor"];
$valForm['txtFecha'] = $_GET["fecha1"];
$valForm['txtFecha1'] = $_GET["fecha2"];
$valForm['txtBusq'] = $_GET["txtBusq"];

$valBusq = sprintf("%s|%s|%s|%s|%s",
	$valForm['hddIdEmpresa'],
	$valForm['hddBePro'],
	$valForm['txtFecha'],
	$valForm['txtFecha1'],
	$valForm['txtBusq']);
$valCadBusq = explode("|", $valBusq);

if ($valForm['buscarDocumento'] == 1){
	if ($valCadBusq[0] == ''){
		//$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	} else if ($valCadBusq[0] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_factura.id_empresa = '".$valCadBusq[0]."'";
	}
		
	if ($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_factura.id_proveedor = '".$valCadBusq[1]."'";
	}
		
	if ($valCadBusq[2] != '' && $valCadBusq[3] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(cp_factura.fecha_origen) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])),"text"));
	}
		
	if ($valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if (strpos($valCadBusq[4],",")){
			$arrayNumeros = implode("','", array_map('trim',explode(",",$valCadBusq[4])));
			$sqlBusq .= $cond."cp_factura.numero_factura_proveedor IN ('".$arrayNumeros."')";
		} else{
			$sqlBusq .= $cond."cp_factura.numero_factura_proveedor LIKE '%".$valCadBusq[4]."%'";
		}
	}
} else if ($valForm['buscarDocumento'] == 2){
    if ($valCadBusq[0] == ''){
		//$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	} else if ($valCadBusq[0] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_notadecargo.id_empresa = '".$valCadBusq[0]."'";
	}
		
	if ($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_notadecargo.id_proveedor = '".$valCadBusq[1]."'";
	}
		
	if ($valCadBusq[2] != '' && $valCadBusq[3] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(cp_notadecargo.fecha_origen_notacargo) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])),"text"));
	}
		
	if ($valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if (strpos($valCadBusq[4],",")){
			$arrayNumeros = implode("','", array_map('trim',explode(",",$valCadBusq[4])));
			$sqlBusq .= $cond."cp_notadecargo.numero_notacargo IN ('".$arrayNumeros."')";
		} else{
			$sqlBusq .= $cond."cp_notadecargo.numero_notacargo LIKE '%".$valCadBusq[4]."%'";
		}
	}
}

if ($valForm['buscarDocumento'] == 1){
	//iteramos para los resultados
	$query = sprintf("SELECT 
		pg_empresa.nombre_empresa,
		CONCAT_WS('-',cp_proveedor.lrif,cp_proveedor.rif) as rif_proveedor,
		cp_proveedor.nombre,
		'FA' as tipo_documento,
		cp_factura.fecha_origen,
		cp_factura.numero_factura_proveedor,
		cp_factura.subtotal_factura,
		
		IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva
				WHERE id_factura = cp_factura.id_factura),0) AS iva_factura,
		
		cp_factura.monto_exento,
		cp_factura.subtotal_descuento,
		
		(cp_factura.subtotal_factura
			+ cp_factura.subtotal_descuento
			+ IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva
						WHERE id_factura = cp_factura.id_factura),0)) AS monto_factura,
		
		IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle
				WHERE idFactura = cp_factura.id_factura),0) AS retencion_iva,
		
		IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque
				WHERE id_factura = cp_factura.id_factura AND tipo = 0 AND anulado IS NULL),0) AS retencion_islr,		
		
		((cp_factura.subtotal_factura
			+ cp_factura.subtotal_descuento
			+ IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva
						WHERE id_factura = cp_factura.id_factura),0))
			- cp_factura.subtotal_descuento
			- IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle
						WHERE idFactura = cp_factura.id_factura),0)
			- IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque
						WHERE id_factura = cp_factura.id_factura AND tipo = 0 AND anulado IS NULL),0)) AS monto_pagar
	FROM cp_factura 
		INNER JOIN cp_proveedor ON cp_factura.id_proveedor = cp_proveedor.id_proveedor
		INNER JOIN pg_empresa ON cp_factura.id_empresa = pg_empresa.id_empresa %s
	ORDER BY numero_factura_proveedor ASC", $sqlBusq);
} else if ($valForm['buscarDocumento'] == 2){
	//iteramos para los resultados
	$query = sprintf("SELECT 
		pg_empresa.nombre_empresa,
		CONCAT_WS('-',cp_proveedor.lrif,cp_proveedor.rif) as rif_proveedor,
		cp_proveedor.nombre,
		'ND' as tipo_documento,
		cp_notadecargo.fecha_origen_notacargo,
		cp_notadecargo.numero_notacargo,
		cp_notadecargo.subtotal_notacargo,
		
		IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva
				WHERE id_notacargo = cp_notadecargo.id_notacargo),0) AS iva_factura,
		
		cp_notadecargo.monto_exento_notacargo,
		cp_notadecargo.subtotal_descuento_notacargo,
		
		(cp_notadecargo.subtotal_notacargo
			+ cp_notadecargo.subtotal_descuento_notacargo
			+ IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva
						WHERE id_notacargo = cp_notadecargo.id_notacargo),0)) AS monto_factura,
		
		IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle
				WHERE id_nota_cargo = cp_notadecargo.id_notacargo),0) AS retencion_iva,
		
		IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque
				WHERE id_factura = cp_notadecargo.id_notacargo AND tipo = 1 AND anulado IS NULL),0) AS retencion_islr,		
		
		((cp_notadecargo.subtotal_notacargo
			+ cp_notadecargo.subtotal_descuento_notacargo
			+ IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva
						WHERE id_notacargo = cp_notadecargo.id_notacargo),0))
			- cp_notadecargo.subtotal_descuento_notacargo
			- IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle
						WHERE id_nota_cargo = cp_notadecargo.id_notacargo),0)
			- IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque
						WHERE id_factura = cp_notadecargo.id_notacargo AND tipo = 1 AND anulado IS NULL),0)) AS monto_pagar
	FROM cp_notadecargo 
		INNER JOIN cp_proveedor ON cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor
		INNER JOIN pg_empresa ON cp_notadecargo.id_empresa = pg_empresa.id_empresa %s
	ORDER BY numero_notacargo ASC", $sqlBusq);
}
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $spanCI."-".$spanRIF);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Tipo Doc.");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro Documento");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Sub Total");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Iva");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Excento");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Descuento");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Monto");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Retención Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Retención ISLR");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Monto a Pagar");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":N".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, ($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, ($row['nombre']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, ($row['rif_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, ($row['tipo_documento']));
	if ($valForm['buscarDocumento'] == 1){
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_origen'])));
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numero_factura_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['subtotal_factura']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['iva_factura']);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['monto_exento']);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['subtotal_descuento']);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['monto_factura']);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['retencion_iva']);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['retencion_islr']);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['monto_pagar']);
	} else if ($valForm['buscarDocumento'] == 2){
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_origen_notacargo'])));
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numero_notacargo'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['subtotal_notacargo']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['iva_factura']);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['monto_exento']);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['subtotal_descuento']);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['monto_factura']);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['retencion_iva']);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['retencion_islr']);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['monto_pagar']);
	}
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":N".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	if ($valForm['buscarDocumento'] == 1){
		$arrayTotales["subtotal_factura"] += $row['subtotal_factura'];
		$arrayTotales["iva_factura"] += $row['iva_factura'];
		$arrayTotales["monto_exento"] += $row['monto_exento'];
		$arrayTotales["subtotal_descuento"] += $row['subtotal_descuento'];
		$arrayTotales["monto_factura"] += $row['monto_factura'];
		$arrayTotales["retencion_iva"] += $row['retencion_iva'];
		$arrayTotales["retencion_islr"] += $row['retencion_islr'];
		$arrayTotales["monto_pagar"] += $row['monto_pagar'];
	} else if ($valForm['buscarDocumento'] == 2){
		$arrayTotales["subtotal_notacargo"] += $row['subtotal_notacargo'];
		$arrayTotales["iva_factura"] += $row['iva_factura'];
		$arrayTotales["monto_exento"] += $row['monto_exento'];
		$arrayTotales["subtotal_descuento"] += $row['subtotal_descuento'];
		$arrayTotales["monto_factura"] += $row['monto_factura'];
		$arrayTotales["retencion_iva"] += $row['retencion_iva'];
		$arrayTotales["retencion_islr"] += $row['retencion_islr'];
		$arrayTotales["monto_pagar"] += $row['monto_pagar'];
	}
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":N".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
if ($valForm['buscarDocumento'] == 1){
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotales['subtotal_factura']);
} else if ($valForm['buscarDocumento'] == 2){
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotales['subtotal_notacargo']);
}
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotales['iva_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotales['monto_exento']);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotales['subtotal_descuento']);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotales['monto_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotales['retencion_iva']);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotales['retencion_islr']);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotales['monto_pagar']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."F".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":"."N".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."F".$contFila);

for ($col = "A"; $col != "N"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "N", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Listado de Pagos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:N7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:N9");
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