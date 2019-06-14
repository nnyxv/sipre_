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
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == "2") {
		$sqlBusq .= $cond.sprintf("an_ped_vent.fecha_entrega BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else if ($valCadBusq[3] == "3") {
		$sqlBusq .= $cond.sprintf("DATE(cxc_fact.fecha_pagada) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else if ($valCadBusq[3] == "4") {
		$sqlBusq .= $cond.sprintf("DATE(cxc_fact.fecha_cierre) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else {
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE %s",
		valTpDato($valCadBusq[5], "text"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	switch ($valCadBusq[6]) {
		case 1 : // Vehiculo
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_vehic2.id_factura)
			FROM cj_cc_factura_detalle_vehiculo fact_det_vehic2 WHERE fact_det_vehic2.id_factura = cxc_fact.idFactura) > 0");
			break;
		case 2 : // Adicionales
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
			WHERE fact_det_acc2.id_factura = cxc_fact.idFactura
				AND acc.id_tipo_accesorio IN (1)) > 0");
			break;
		case 3 : // Accesorios
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
			WHERE fact_det_acc2.id_factura = cxc_fact.idFactura
				AND acc.id_tipo_accesorio IN (2)) > 0");
			break;
	}
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR an_ped_vent.id_pedido LIKE %s
	OR an_ped_vent.numeracion_pedido LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT(uni_bas.nom_uni_bas,': ', modelo.nom_modelo, ' - ', vers.nom_version) LIKE %s
	OR placa LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxp_fact.id_factura AS id_factura_compra,
	cxc_fact.idFactura,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	an_ped_vent.id_pedido,
	an_ped_vent.numeracion_pedido,
	an_ped_vent.fecha_entrega,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
	uni_fis.placa,
	ped_comp_det.flotilla,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
	cxc_fact.calculoIvaFactura AS total_iva,
	
	(IFNULL(cxc_fact.subtotalFactura, 0)
		- IFNULL(cxc_fact.descuentoFactura, 0)
		+ IFNULL(cxc_fact.calculoIvaFactura, 0)
		+ IFNULL(cxc_fact.calculoIvaDeLujoFactura, 0)
	) AS total,
	
	(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
	FROM cj_cc_factura_detalle_accesorios fact_det_acc2 WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
	cxc_fact.anulada,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE (cxp_fact_gasto.id_factura = cxp_fact.id_factura)), 0)
		+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva cxp_fact_iva
				WHERE (cxp_fact_iva.id_factura = cxp_fact.id_factura)), 0)
	) AS total_compra
FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
	RIGHT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			LEFT JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
	LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	INNER JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2) %s
ORDER BY numeroControl DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Vehículo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Fecha Entrega");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Adicionales");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Total Factura");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Total Factura Compra");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
		
	switch($row['id_modulo']) {
		case 0 : $imgPedidoModulo = "Repuestos"; break;
		case 1 : $imgPedidoModulo = "Servicios"; break;
		case 2 : $imgPedidoModulo = "Vehículos"; break;
		case 3 : $imgPedidoModulo = "Administración"; break;
		default : $imgPedidoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['id_pedido'] > 0) ? "" : "Creada por CxC";
		
	$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Factura";
		
	switch ($row['flotilla']) {
		case 0 : $imgEstatusUnidadAsignacion = "Vehículo Normal"; break;
		case 1 : $imgEstatusUnidadAsignacion = "Vehículo por Flotilla"; break;
		default : $imgEstatusUnidadAsignacion = "";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $imgEstatusUnidadAsignacion);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['numeroFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['numeroControl']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['numeracion_pedido']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['nombre_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['vehiculo']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, date(spanDateFormat, strtotime($row['fecha_entrega'])));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['placa']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['cantidad_accesorios']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['total']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['total_compra']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":O".$ultimo);

for ($col = "A"; $col != "O"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "O");

$tituloDcto = "Unidades Vendidas";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:O7");

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

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