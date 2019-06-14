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
$sqlBusq .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (3)");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) > 0");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") { 
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nc.numero_nota_credito = %s 
	OR cxp_nc.numero_control_notacredito = %s)",
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
	$sqlBusq .= $cond.sprintf("cxp_nc.fecha_registro_notacredito BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[4])),"date"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(prov.id_proveedor LIKE %s
	OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR cxp_nc.numero_nota_credito LIKE %s
	OR cxp_nc.numero_control_notacredito LIKE %s
	OR cxp_nc.observacion_notacredito LIKE %s
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

$query = sprintf("SELECT cxp_nc.*,
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
	
	(CASE cxp_nc.estado_notacredito
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Sin Asignar'
		WHEN 2 THEN 'Asignado Parcial'
		WHEN 3 THEN 'Asignado'
	END) AS descripcion_estado_nota_credito,
	
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	
	motivo.id_motivo,
	motivo.descripcion AS descripcion_motivo,
	
	(CASE
		WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
			cxp_fact.numero_factura_proveedor
	END) AS numero_factura_proveedor,
	
	(CASE
		WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
			cxp_fact.fecha_factura_proveedor
	END) AS fecha_factura_proveedor,
	
	(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
		WHERE cxp_fact_det.id_factura = cxp_nc.id_documento) AS cant_items,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.id_nota_credito = cxp_nc.id_notacredito
	LIMIT 1) AS idRetencionCabezera,
	
	(IFNULL(cxp_nc.subtotal_notacredito, 0)
		- IFNULL(cxp_nc.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
				FROM cp_notacredito_gastos cxp_nc_gasto
				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
					AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	(IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
			FROM cp_notacredito_iva cxp_nc_iva
			WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total_iva,
	
	(IFNULL(cxp_nc.subtotal_notacredito, 0)
		- IFNULL(cxp_nc.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
					AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total,
	
	vw_iv_usuario.nombre_empleado,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_notacredito cxp_nc
	INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento LIKE 'FA')
	INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
	LEFT JOIN ga_orden_compra orden_compra ON (cxp_fact.id_orden_compra = orden_compra.id_orden_compra)
	LEFT JOIN ga_solicitud_compra solicitud_compra ON (orden_compra.id_solicitud_compra = solicitud_compra.id_solicitud_compra)
	LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
	LEFT JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
	LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_fact.id_empleado_creador = vw_iv_usuario.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s 
ORDER BY cxp_nc.id_notacredito DESC", $sqlBusq);	
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Nota de Crédito Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Nota de Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Fecha Factura");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Orden");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Nro. Solicitud");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Justificación");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Subtotal Nota Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Descuento Nota Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Total Nota Crédito");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":V".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_departamento_notacredito']) {
		case 0 : $imgPedidoModulo = "Repuestos"; break;
		case 1 : $imgPedidoModulo = "Servicios"; break;
		case 2 : $imgPedidoModulo = "Vehículos"; break;
		case 3 : $imgPedidoModulo = "Administración"; break;
		case 4 : $imgPedidoModulo = "Alquiler"; break;
		default : $imgPedidoModulo = $row['id_departamento_notacredito'];
	}
	
	$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "Compras" : "Creada por CxP";
	
	switch($row['estado_notacredito']) {
		case 0 : $class = "class=\"divMsjError\""; break;
		case 1 : $class = "class=\"divMsjInfo\""; break;
		case 2 : $class = "class=\"divMsjAlerta\""; break;
		case 3 : $class = "class=\"divMsjInfo3\""; break;
		case 4 : $class = "class=\"divMsjInfo4\""; break;
	}
	
	$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fecha_registro_notacredito'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_notacredito'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numero_nota_credito'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['numero_control_notacredito'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['numero_factura_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila, $row['num_orden'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['num_solicitud'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, utf8_encode($row['rif_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, utf8_encode($row['nit_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['observacion_factura']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, utf8_encode($row['justificacion_compra']));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['subtotal_notacredito']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['subtotal_descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['total_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $row['total']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":V".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);	
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
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
	$arrayTotal[15] += $row['saldo_notacredito'];
	$arrayTotal[16] += $row['subtotal_notacredito'];
	$arrayTotal[17] += $row['subtotal_descuento'];
	$arrayTotal[18] += $row['total_neto'];
	$arrayTotal[19] += $row['total_iva'];
	$arrayTotal[20] += $row['total'];
}

$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":V".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->SetCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $cantFact);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal[13]);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal[16]);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal[17]);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal[18]);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal[19]);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal[20]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila.":"."V".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":E".$contFila);

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

$tituloDcto = "Histórico Devolución Facturas de Compras";
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