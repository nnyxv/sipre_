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
$sqlBusq .= $cond.sprintf("(estatus_pedido_venta NOT IN (0,1,2)
OR estatus_pedido_venta IS NULL)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_facturas_venta.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_facturas_venta.id_empresa))",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[0],"int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("aplicaLibros = %s",
		valTpDato($valCadBusq[1], "boolean"));
}

if (($valCadBusq[2] != "-1" && $valCadBusq[2] != "")
|| ($valCadBusq[3] != "-1" && $valCadBusq[3] != "")
|| ($valCadBusq[4] != "-1" && $valCadBusq[4] != "")) {
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") $array[] = $valCadBusq[2];
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") $array[] = $valCadBusq[3];
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") $array[] = $valCadBusq[4];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT fact_vent.estadoFactura FROM cj_cc_encabezadofactura fact_vent
	WHERE fact_vent.idFactura = vw_iv_facturas_venta.idFactura) IN (%s)",
		valTpDato(implode(",",$array), "campo"));
}

if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado_preparador = %s",
		valTpDato($valCadBusq[7],"int"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
	WHERE vw_pg_clave_movimiento.tipo = 3
		AND mov.id_documento = vw_iv_facturas_venta.idFactura) = %s",
		valTpDato($valCadBusq[8], "int"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(numeroFactura LIKE %s
	OR numeroControl LIKE %s
	OR id_pedido_venta_propio LIKE %s
	OR id_pedido_venta_referencia LIKE %s
	OR numero_siniestro LIKE %s
	OR ci_cliente LIKE %s
	OR nombre_cliente LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT *,
	(SELECT COUNT(ped_venta_det.id_pedido_venta)
	FROM iv_pedido_venta_detalle ped_venta_det
	WHERE (ped_venta_det.id_pedido_venta = vw_iv_facturas_venta.id_pedido_venta)) AS cant_items,
	
	(SELECT SUM(ped_venta_det.cantidad) AS pedidos
	FROM iv_pedido_venta_detalle ped_venta_det
	WHERE (ped_venta_det.id_pedido_venta = vw_iv_facturas_venta.id_pedido_venta)) AS pedidos,
	
	(SELECT SUM(ped_venta_det.pendiente) AS pendientes
	FROM iv_pedido_venta_detalle ped_venta_det
	WHERE (ped_venta_det.id_pedido_venta = vw_iv_facturas_venta.id_pedido_venta)) AS pendientes,
	
	(IFNULL(vw_iv_facturas_venta.subtotal, 0)
		- IFNULL(vw_iv_facturas_venta.subtotal_descuento, 0)) AS total_neto,
	
	(IFNULL((CASE
		WHEN (estatus_pedido_venta IS NULL) THEN
			calculoIvaFactura
		WHEN (estatus_pedido_venta IS NOT NULL) THEN
			(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
				FROM iv_pedido_venta_iva ped_iva
				WHERE (ped_iva.id_pedido_venta = vw_iv_facturas_venta.id_pedido_venta))
	END), 0)) AS total_iva,
	
	(IFNULL(vw_iv_facturas_venta.subtotal, 0)
		- IFNULL(vw_iv_facturas_venta.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
				FROM iv_pedido_venta_gasto ped_gasto
				WHERE (ped_gasto.id_pedido_venta = vw_iv_facturas_venta.id_pedido_venta)), 0)
		+ IFNULL((CASE
			WHEN (estatus_pedido_venta IS NULL) THEN
				calculoIvaFactura
			WHEN (estatus_pedido_venta IS NOT NULL) THEN
				(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_venta_iva ped_iva
					WHERE (ped_iva.id_pedido_venta = vw_iv_facturas_venta.id_pedido_venta))
		END), 0)
	) AS total,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_facturas_venta
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_facturas_venta.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY numeroControl DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Referencia");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Siniestro");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$imgPedidoModulo = "";
	$imgEstatusPedido = "";
	if ($row['estatus_pedido_venta'] == "") {
		$imgPedidoModulo = "CxC";
		$imgEstatusPedido = "";
	} else {
		$imgPedidoModulo = "R";
		
		switch($row['estatus_pedido_venta']) {
			case 2 : $imgEstatusPedido = "Pedido Aprobado"; break;
			case 3 : $imgEstatusPedido = "Factura"; break;
			case 4 : $imgEstatusPedido = "Factura (Con Devolución)"; break;
		}
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['nombre_empresa']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['numeroFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['numeroControl']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['id_pedido_venta_propio']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['id_pedido_venta_referencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['numero_siniestro']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['nombre_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO"));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['total_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['total']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
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
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $totalItems);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $totalNeto);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $totalIva);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $totalFacturacion);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."O".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":D".$contFila);

for ($col = "A"; $col != "O"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "O");

$tituloDcto = "Histórico de Facturas de Venta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:O7");

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