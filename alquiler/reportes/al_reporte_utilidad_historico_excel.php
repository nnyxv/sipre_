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

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);
			
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = q.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(q.fecha_dcto) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == 1) {// solo facturas sin devolucion
		$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC'");
	} else if ($valCadBusq[3] == 2) {// solo facturas con devolucion
		$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC')");
	}
}
	
// BUSQUEDA PARA VEHICULO sqlBusq2
if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[4] == "1"){
		$sqlBusq2 .= $cond.sprintf("id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_contrato_venta)");			
	} else if ($valCadBusq[4] == "2") {
		$sqlBusq2 .= $cond.sprintf("id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_servicio_mantenimiento_compra)");
	} else if ($valCadBusq[4] == "3") {
		$sqlBusq2 .= $cond.sprintf("(id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_contrato_venta)
		OR id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_servicio_mantenimiento_compra))");
	}
}	

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(placa LIKE %s
	OR serial_carroceria LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

$query = sprintf("SELECT 
	uni_fis.id_unidad_fisica,
	uni_fis.placa,
	uni_fis.serial_carroceria,		
	marca.nom_marca,
	modelo.nom_modelo,
	version.nom_version,
	ano.nom_ano,
	uni_bas.nom_uni_bas
FROM an_unidad_fisica uni_fis
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
	INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
	INNER JOIN an_version version ON (uni_bas.ver_uni_bas = version.id_version)
	INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano) 
	%s ORDER BY id_unidad_fisica ASC", $sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro. Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Marca");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Modelo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Versión");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Venta");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Costo");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Utilidad");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":J".$contFila)->applyFromArray($styleArrayColumna);

while ($rowUnidad = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 = $cond.sprintf("(q.id_unidad_fisica = %s)",
		valTpDato($rowUnidad['id_unidad_fisica'], "int"));
	
	// FACTURAS VENTA - NOTAS DE CREDITO VENTA
	$query = sprintf("SELECT IFNULL(SUM(total_neto), 0) AS total_venta
	FROM (
		SELECT 
			contrato.id_empresa,
			cxc_fact.fechaRegistroFactura AS fecha_dcto,
			unidad.id_unidad_fisica,
			'FA' AS tipo_dcto,
			(cxc_fact.subtotalFactura - cxc_fact.descuentoFactura) AS total_neto
		FROM al_contrato_venta contrato
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)		
			
		UNION ALL
		
		SELECT 
			contrato.id_empresa,
			cxc_nc.fechaNotaCredito AS fecha_dcto,
			unidad.id_unidad_fisica,
			'NC' AS tipo_dcto,
			((cxc_nc.subtotalNotaCredito - cxc_nc.subtotal_descuento)) * -1 AS total_neto
		FROM al_contrato_venta contrato
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4			
			INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
		WHERE cxc_nc.tipoDocumento = 'FA'
		) AS q %s %s", $sqlBusq, $sqlBusq3);
	$rsVenta = mysql_query($query);
	if (!$rsVenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$rowVenta = mysql_fetch_assoc($rsVenta);
		
	// FACTURAS COMPRA - NOTAS DE CREDITO COMPRA
	$query = sprintf("SELECT IFNULL(SUM(costo), 0) AS total_costo
	FROM (
		SELECT 
			cxp_fact.id_empresa,
			cxp_fact.fecha_origen AS fecha_dcto,
			unidad.id_unidad_fisica,
			'FA' AS tipo_dcto,
			serv_mant_compra.costo,
			(cxp_fact.subtotal_factura - cxp_fact.subtotal_descuento) AS total_neto				
		FROM cp_factura cxp_fact
			INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
			INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
			LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
			
		UNION ALL
		
		SELECT 
			cxp_nc.id_empresa,
			cxp_nc.fecha_notacredito AS fecha_dcto,
			unidad.id_unidad_fisica,
			'NC' AS tipo_dcto,
			serv_mant_compra.costo,
			((cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento)) * -1 AS total_neto
		FROM cp_notacredito cxp_nc
			INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento = 'FA')
			INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
			INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
			LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
		) AS q %s %s", $sqlBusq, $sqlBusq3);
	$rsCompra = mysql_query($query);
	if (!$rsCompra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$rowCompra = mysql_fetch_assoc($rsCompra);
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $rowUnidad['id_unidad_fisica']);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($rowUnidad['placa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($rowUnidad['serial_carroceria']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($rowUnidad['nom_marca']));
	
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($rowUnidad['nom_modelo']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($rowUnidad['nom_version']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($rowUnidad['nom_uni_bas']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $rowVenta['total_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $rowCompra['total_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $rowVenta['total_venta']-$rowCompra['total_costo']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":J".$contFila)->applyFromArray($clase);

	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);	
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);	
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$arrayTotal[5] += 1;

	$totalFacturas += $rowVenta['total_venta'];
	$totalCosto += $rowCompra['total_costo'];
	$totalUtilidad += $rowVenta['total_venta']-$rowCompra['total_costo'];
}

$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":J".$ultimo);

$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total de totales:");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $totalFacturas);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $totalCosto);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $totalUtilidad);

$objPHPExcel->getActiveSheet()->SetCellValue("G".($contFila + 2), "Total Venta:");
$objPHPExcel->getActiveSheet()->SetCellValue("G".($contFila + 3), "Total Costo:");
$objPHPExcel->getActiveSheet()->SetCellValue("G".($contFila + 4), "Total Utilidad:");
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 2), $totalFacturas);
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 3), $totalCosto);
$objPHPExcel->getActiveSheet()->SetCellValue("J".($contFila + 4), $totalUtilidad);

$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 2))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 3))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".($contFila + 4))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("G".($contFila + 2).":"."J".($contFila + 2))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("G".($contFila + 3).":"."J".($contFila + 3))->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("G".($contFila + 4).":"."J".($contFila + 4))->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."J".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":G".$contFila);

for ($col = "A"; $col <= "J"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "J");

//FECHA
if($valCadBusq[1] != "" && $valCadBusq[2] != ""){
	$fecha = "Desde: ".date(spanDateFormat, strtotime($valCadBusq[1]))." Hasta: ".date(spanDateFormat, strtotime($valCadBusq[2]));
}
$objPHPExcel->getActiveSheet()->SetCellValue("G6", $fecha);
$objPHPExcel->getActiveSheet()->getStyle("G6")->applyFromArray($styleArrayTitulo);

$tituloDcto = "Histórico Reporte Utilidad";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:J7");

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