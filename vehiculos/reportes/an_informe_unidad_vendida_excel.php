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
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = fact_vent.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idVendedor LIKE %s",
		valTpDato($valCadBusq[3],"text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.anulada LIKE %s",
		valTpDato($valCadBusq[4],"text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	switch ($valCadBusq[5]) {
		case 1 : // Vehiculo
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_vehic2.id_factura)
			FROM cj_cc_factura_detalle_vehiculo fact_det_vehic2 WHERE fact_det_vehic2.id_factura = fact_vent.idFactura) > 0");
			break;
		case 2 : // Adicionales
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
			WHERE fact_det_acc2.id_factura = fact_vent.idFactura
				AND acc.id_tipo_accesorio IN (1)) > 0");
			break;
		case 3 : // Accesorios
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
			WHERE fact_det_acc2.id_factura = fact_vent.idFactura
				AND acc.id_tipo_accesorio IN (2)) > 0");
			break;
	}
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(fact_vent.numeroFactura LIKE %s
	OR fact_vent.numeroControl LIKE %s
	OR numeroPedido LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT(uni_bas.nom_uni_bas,': ', modelo.nom_modelo, ' - ', vers.nom_version) LIKE %s
	OR placa LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	fact_comp.id_factura AS id_factura_compra,
	fact_vent.idFactura,
	fact_vent.fechaRegistroFactura,
	fact_vent.numeroFactura,
	fact_vent.numeroControl,
	fact_vent.numeroPedido,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
	uni_fis.placa,
	ped_comp_det.flotilla,
	
	ano.nom_ano,
	uni_fis.serial_carroceria,
	cliente.correo,
	cliente.ciudad,
	cliente.telf,
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
	
	(SELECT SUM(fact_vent_det_acc.cantidad * fact_vent_det_acc.precio_unitario)
	FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
	WHERE fact_vent_det_acc.id_factura = fact_vent.idFactura) AS total_neto_accesorios,
	
	(SELECT fact_vent_det_vehic.precio_unitario
	FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
	WHERE fact_vent_det_vehic.id_factura = fact_vent.idFactura) AS total_neto_vehiculo,
	
	(fact_vent.subtotalFactura - fact_vent.descuentoFactura) AS total_neto_factura_venta,
	(fact_vent.calculoIvaFactura + fact_vent.calculoIvaDeLujoFactura) AS total_iva_factura_venta,
	
	(IFNULL(fact_vent.subtotalFactura, 0)
		- IFNULL(fact_vent.descuentoFactura, 0)
		+ IFNULL(fact_vent.calculoIvaFactura, 0)
		+ IFNULL(fact_vent.calculoIvaDeLujoFactura, 0)
	) AS total_factura_venta,
	
	(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
	FROM cj_cc_factura_detalle_accesorios fact_det_acc2 WHERE fact_det_acc2.id_factura = fact_vent.idFactura) AS cantidad_accesorios,
	fact_vent.anulada,
	
	(IFNULL(fact_comp.subtotal_factura, 0)
		- IFNULL(fact_comp.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
				FROM cp_factura_gasto fact_compra_gasto
				WHERE (fact_compra_gasto.id_factura = fact_comp.id_factura)), 0)
		+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva fact_compra_iva
				WHERE (fact_compra_iva.id_factura = fact_comp.id_factura)), 0)
	) AS total_compra
FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
	RIGHT JOIN cj_cc_encabezadofactura fact_vent ON (fact_vent_det_acc.id_factura = fact_vent.idFactura)
	LEFT JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (fact_vent.idFactura = fact_vent_det_vehic.id_factura)
	INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
	LEFT JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			LEFT JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
	INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
	INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
	INNER JOIN an_pedido ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido) %s
ORDER BY numeroControl DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Vehículo");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Año");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Fecha Venta");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Total Adicionales");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Total Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Total Factura");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Correo");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Ciudad");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Nro. Factura Venta");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Nro. Pedido Venta");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
		
	$imgPedidoModulo = "";
	if ($row['numeroPedido'] == "" || $row['numeroPedido'] == "0") {
		$imgPedidoModulo = "Factura CxC";
	} else {
		$imgPedidoModulo = "Factura Vehículos";
	}
		
	$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Factura";
		
	switch ($row['flotilla']) {
		case 0 : $imgEstatusUnidadAsignacion = "Vehículo Normal"; break;
		case 1 : $imgEstatusUnidadAsignacion = "Vehículo por Flotilla"; break;
		default : $imgEstatusUnidadAsignacion = "";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusUnidadAsignacion);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['ci_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['nombre_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['placa']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['vehiculo']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['nom_ano']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['serial_carroceria']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['total_neto_accesorios']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['total_neto_vehiculo']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['total_iva_factura_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['total_factura_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['correo']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['ciudad']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['telf']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['numeroFactura']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['numeracion_pedido']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":S".$ultimo);

for ($col = "A"; $col != "S"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "S");

$tituloDcto = "Informe Unidades Vendidas";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:S7");

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