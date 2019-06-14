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

/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(estatus_pedido_venta IN (4)
OR estatus_pedido_venta IS NULL);*/

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("nota_cred.estatus_nota_credito IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(nota_cred.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = nota_cred.id_empresa))",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[0],"int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nota_cred.aplicaLibros = %s",
		valTpDato($valCadBusq[1], "boolean"));
}

if (($valCadBusq[2] != "-1" && $valCadBusq[2] != "")
|| ($valCadBusq[3] != "-1" && $valCadBusq[3] != "")
|| ($valCadBusq[4] != "-1" && $valCadBusq[4] != "")
|| ($valCadBusq[5] != "-1" && $valCadBusq[5] != "")) {
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") $array[] = $valCadBusq[2];
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") $array[] = $valCadBusq[3];
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") $array[] = $valCadBusq[4];
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") $array[] = $valCadBusq[5];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nota_cred.estadoNotaCredito IN (%s)",
		valTpDato(implode(",",$array), "campo"));
}

if ($valCadBusq[6] != "" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nota_cred.fechaNotaCredito BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[7])),"date"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_fact_venta.id_empleado_preparador = %s",
		valTpDato($valCadBusq[8],"int"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nota_cred.id_clave_movimiento = %s",
		valTpDato($valCadBusq[9],"date"));
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s
	OR nota_cred.numeroControl LIKE %s
	OR numeroFactura LIKE %s
	OR id_pedido_venta_propio LIKE %s
	OR id_pedido_venta_referencia LIKE %s
	OR ci_cliente LIKE %s
	OR nombre_cliente LIKE %s)",
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"),
		valTpDato("%".$valCadBusq[10]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	vw_iv_fact_venta.fechaRegistroFactura,
	vw_iv_fact_venta.numeroFactura,
	vw_iv_fact_venta.id_pedido_venta_propio,
	vw_iv_fact_venta.id_pedido_venta_referencia,
	
	(SELECT ped_vent.estatus_pedido_venta FROM iv_pedido_venta ped_vent
	WHERE ped_vent.id_pedido_venta = vw_iv_fact_venta.id_pedido_venta) AS estatus_pedido_venta,
	
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	
	(SELECT COUNT(nota_cred_det.id_nota_credito)
	FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) AS cant_items,
	
	(SELECT SUM(nota_cred_det.cantidad) AS pedidos
	FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) AS pedidos,
	
	(SELECT SUM(nota_cred_det.pendiente) AS pendientes
	FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) AS pendientes,
	
	(SELECT vale_ent.id_vale_entrada FROM iv_vale_entrada vale_ent
	WHERE vale_ent.id_documento = nota_cred.idNotaCredito
		AND tipo_vale_entrada = 3) AS id_vale,
	
	nota_cred.idNotaCredito,
	nota_cred.numeracion_nota_credito,
	nota_cred.fechaNotaCredito,
	nota_cred.numeroControl,
	nota_cred.idDepartamentoNotaCredito,
	nota_cred.subtotalNotaCredito,
	
	(IFNULL(nota_cred.subtotalNotaCredito, 0)
		- IFNULL(nota_cred.subtotal_descuento, 0)) AS total_neto,
	
	(IFNULL(nota_cred.montoNetoNotaCredito, 0)
		- IFNULL(nota_cred.subtotalNotaCredito, 0)
		- IFNULL(nota_cred.subtotal_descuento, 0)) AS total_iva,
	
	nota_cred.montoNetoNotaCredito AS total,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_notacredito nota_cred
	LEFT JOIN vw_iv_facturas_venta vw_iv_fact_venta ON (nota_cred.idDocumento = vw_iv_fact_venta.idFactura)
	INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (nota_cred.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY nota_cred.numeroControl DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Nota Créd.");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Fecha Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Referencia");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$imgPedidoModulo = "";
	$imgEstatusPedido = "";
	if ($row['id_pedido_venta_propio'] > 0) {
		$imgPedidoModulo = "R";
		
		if ($row['estatus_pedido_venta'] == 4)
			$imgEstatusPedido = "Nota de Crédito";
	} else {
		$imgPedidoModulo = "CxC";
		$imgEstatusPedido = "";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['nombre_empresa']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaNotaCredito'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['numeracion_nota_credito']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['numeroControl']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, (($row['fechaRegistroFactura'] != "") ? date(spanDateFormat, strtotime($row['fechaRegistroFactura'])) : "xx-xx-xxxx"));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['numeroFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['id_pedido_venta_propio']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['id_pedido_venta_referencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['nombre_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO"));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['total_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['total']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$cantFact++;
	$totalItems += $row['cant_items'];
	$totalNeto += $row['total_neto'];
	$totalIva += $row['total_iva'];
	$totalFacturacion += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":O".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $cantFact);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $totalItems);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $totalNeto);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $totalIva);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $totalFacturacion);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."P".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":D".$contFila);

for ($col = "A"; $col != "P"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "P");

$tituloDcto = "Histórico de Notas de Crédito";
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