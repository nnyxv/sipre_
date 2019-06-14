<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
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

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (3)");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) > 0");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") { 
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.numero_factura_proveedor = %s 
	OR cxp_fact.numero_control_factura = %s)",
		valTpDato($valCadBusq[1], "text"),
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") { 
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(solicitud_compra.numero_solicitud = %s
	OR orden_compra.id_orden_compra = %s)",
		valTpDato($valCadBusq[2], "int"),			
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[4])),"date"));
}	

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(prov.id_proveedor LIKE %s
	OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s
	OR cxp_fact.numero_control_factura LIKE %s
	OR cxp_fact.observacion_factura LIKE %s
	OR solicitud_compra.numero_solicitud LIKE %s
	OR orden_compra.id_orden_compra LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

$query = sprintf("SELECT cxp_fact.*,
	solicitud_compra.numero_solicitud,
	solicitud_compra.justificacion_compra,
	orden_compra.id_orden_compra,
	
	IF (solicitud_compra.numero_solicitud IS NULL,
		'-',
		CONCAT_WS('-', (SELECT empresa.codigo_empresa 
						FROM pg_empresa empresa WHERE empresa.id_empresa = cxp_fact.id_empresa), 
						solicitud_compra.numero_solicitud) 
	) AS num_solicitud,
	
	IFNULL(orden_compra.id_orden_compra,'-') AS num_orden,
	
	(CASE cxp_fact.estatus_factura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_factura,
	
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	
	(CASE id_modulo
		WHEN 1 THEN
			(SELECT COUNT(orden_tot.id_factura)
			FROM sa_orden_tot orden_tot
				INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
			WHERE orden_tot.id_factura = cxp_fact.id_factura)
		WHEN 2 THEN
			(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
			WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			+
			(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
			WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
		ELSE
			(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
			WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
	END) AS cant_items,
	
	(SELECT SUM(cxp_fact_det.cantidad) FROM cp_factura_detalle cxp_fact_det
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_piezas,
	
	moneda_local.abreviacion AS abreviacion_moneda_local,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = cxp_fact.id_factura
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
	WHERE reten_cheque.id_factura = cxp_fact.id_factura
		AND reten_cheque.tipo IN (0)
		AND reten_cheque.anulado IS NULL) AS id_retencion_cheque,
	
	(SELECT
		nota_cargo.id_notacargo
	FROM cp_notadecargo nota_cargo
		INNER JOIN an_unidad_fisica uni_fis ON (nota_cargo.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
		INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN pg_motivo motivo ON (nota_cargo.id_motivo = motivo.id_motivo)
		INNER JOIN pg_modulos modulo ON (nota_cargo.id_modulo = modulo.id_modulo)
	WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura) AS id_nota_cargo_planmayor,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	(IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
			FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total_iva,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva cxp_fact_iva
				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
	
	cxp_fact.activa,
	vw_iv_usuario.nombre_empleado,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_factura cxp_fact
	INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
	LEFT JOIN ga_orden_compra orden_compra ON (cxp_fact.id_orden_compra = orden_compra.id_orden_compra)
	LEFT JOIN ga_solicitud_compra solicitud_compra ON (orden_compra.id_solicitud_compra = solicitud_compra.id_solicitud_compra)
	LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
	LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_fact.id_empleado_creador = vw_iv_usuario.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s 
ORDER BY cxp_fact.id_factura DESC", $sqlBusq);	
$rs = mysql_query($query);       
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Factura Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Orden");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Nro. Solicitud");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Justificación");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Subtotal Factura");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Descuento Factura");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Total Factura");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":V".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgPedidoModulo = ("Repuestos"); break;
		case 1 : $imgPedidoModulo = ("Servicios"); break;
		case 2 : $imgPedidoModulo = ("Vehículos"); break;
		case 3 : $imgPedidoModulo = ("Administración"); break;
		default : $imgPedidoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "Compras" : "Creada por CxP";
	
	switch($row['activa']) {
		case "" : $imgEstatusRegistroCompra = "Compra Registrada (Con Devolución)"; break;
		case 1 : $imgEstatusRegistroCompra = "Compra Registrada"; break;
		default : $imgEstatusRegistroCompra = "";
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusRegistroCompra);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_origen'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat, strtotime($row['fecha_vencimiento'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, $row['numero_factura_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['numero_control_factura'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['num_orden'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['num_solicitud'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, utf8_encode($row['rif_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, utf8_encode($row['nit_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['observacion_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['justificacion_compra']));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['subtotal_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['subtotal_descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['total_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $row['total']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":V".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$cantFact++;
	$arrayTotal[13] += $row['cant_items'];
	$arrayTotal[14] += $row['cant_piezas'];
	$arrayTotal[15] += $row['saldo_factura'];
	$arrayTotal[16] += $row['subtotal_factura'];
	$arrayTotal[17] += $row['subtotal_descuento'];
	$arrayTotal[18] += $row['total_neto'];
	$arrayTotal[19] += $row['total_iva'];
	$arrayTotal[20] += $row['total'];
}

$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":V".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $cantFact);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal[13]);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal[16]);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal[17]);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal[18]);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal[19]);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal[20]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila.":"."V".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":G".$contFila);

for ($col = "A"; $col <= "V"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "V");

//FECHA
if($valCadBusq[3] != "" && $valCadBusq[4] != ""){
	$fecha = "Desde: ".date(spanDateFormat, strtotime($valCadBusq[3]))." Hasta: ".date(spanDateFormat, strtotime($valCadBusq[4]));
}

$objPHPExcel->getActiveSheet()->SetCellValue("N6", $fecha);
$objPHPExcel->getActiveSheet()->getStyle("N6")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("N6:R6");

$tituloDcto = "Histórico Facturas de Compras";
$objPHPExcel->getActiveSheet()->SetCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:V7");

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