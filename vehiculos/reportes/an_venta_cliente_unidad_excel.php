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
	$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.aplicaLibros = %s",
		valTpDato($valCadBusq[1], "boolean"));
}

if (($valCadBusq[2] != "-1" && $valCadBusq[2] != "")
|| ($valCadBusq[3] != "-1" && $valCadBusq[3] != "")
|| ($valCadBusq[4] != "-1" && $valCadBusq[4] != "")) {
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") $array[] = $valCadBusq[2];
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") $array[] = $valCadBusq[3];
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") $array[] = $valCadBusq[4];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.estadoFactura IN (%s)",
		valTpDato(implode(",",$array), "campo"));
}

if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_vent.id_empleado_preparador = %s",
		valTpDato($valCadBusq[7], "int"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
	WHERE vw_pg_clave_movimiento.tipo IN (3)
		AND mov.id_documento = fact_vent.idFactura) = %s",
		valTpDato($valCadBusq[8], "int"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(fact_vent.numeroFactura LIKE %s
	OR fact_vent.numeroControl LIKE %s
	OR ped_vent.numeracion_pedido LIKE %s
	OR pres_vent.numeracion_presupuesto LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	fact_vent.idCliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
FROM cj_cc_encabezadofactura fact_vent
	INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
	INNER JOIN an_pedido ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto) %s
ORDER BY numeroControl DESC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("fact_vent.idCliente = %s",
		valTpDato($row['idCliente'], "int"));
	
	$queryFacturaVenta = sprintf("SELECT fact_vent.*,
		ped_vent.numeracion_pedido,
		pres_vent.numeracion_presupuesto,
		vw_pg_empleado.nombre_empleado,
		vw_iv_modelo.nom_uni_bas,
		CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		1 AS cantidad,
		fact_vent_det_vehic.precio_unitario
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
		INNER JOIN an_pedido ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (fact_vent.idVendedor = vw_pg_empleado.id_empleado)
		INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (fact_vent.idFactura = fact_vent_det_vehic.id_factura)   
		INNER JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas) %s %s
	ORDER BY numeroControl DESC", $sqlBusq, $sqlBusq2);
	$rsFacturaVenta = mysql_query($queryFacturaVenta);
	if (!$rsFacturaVenta) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFacturaVenta = mysql_num_rows($rsFacturaVenta);
	
	if ($totalRowsFacturaVenta > 0) {
		$contFila++;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Cliente:");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_cliente']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":M".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $spanClienteCxC);
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['ci_cliente']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		
		$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":M".$contFila);
		
		$contFila++;
		$primero = $contFila;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Unidad Básica");
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Cantidad");
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Precio");
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha");
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Departamento");
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Factura");
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Control");
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Pedido");
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Referencia");
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Estatus");
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Tipo Pago");
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Vendedor");
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);
	}
	
	$arrayTotal = NULL;
	$contFila2 = 0;
	while ($rowFacturaVenta = mysql_fetch_assoc($rsFacturaVenta)) {
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		switch($rowFacturaVenta['idDepartamentoOrigenFactura']) {
			case 0 : $imgDctoModulo = "Repuestos"; break;
			case 1 : $imgDctoModulo = "Servicios"; break;
			case 2 : $imgDctoModulo = "Vehículos"; break;
			case 3 : $imgDctoModulo = "Administración"; break;
			default : $imgDctoModulo = $row['idDepartamentoOrigenFactura'];
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($rowFacturaVenta['nom_uni_bas']));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($rowFacturaVenta['vehiculo']));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $rowFacturaVenta['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowFacturaVenta['precio_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($rowFacturaVenta['fechaRegistroFactura'])));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, ($imgDctoModulo));
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($rowFacturaVenta['numeroFactura']));
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($rowFacturaVenta['numeroControl']));
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($rowFacturaVenta['numeracion_pedido']));
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($rowFacturaVenta['numeracion_presupuesto']));
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($rowFacturaVenta['anulada'] == "NO") ? "": "ANULADA"));
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($rowFacturaVenta['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO"));
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $rowFacturaVenta['nombre_empleado']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		if ($rowFacturaVenta['anulada'] == "NO") {
			$arrayTotal[3] += $rowFacturaVenta['cantidad'];
		}
	}
	$ultimo = $contFila;
	
	if ($totalRowsFacturaVenta > 0) {
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotal[3]);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."M".$contFila)->applyFromArray($styleArrayResaltarTotal);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
		
		$contFila++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
					
		$arrayTotalFinal[3] += $arrayTotal[3];
	}
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total de Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotalFinal[3]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo2);
$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."M".$contFila)->applyFromArray($styleArrayResaltarTotal2);

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);

for ($col = "A"; $col != "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M");

$tituloDcto = "Ventas por Cliente (Unidad)";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:M7");

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