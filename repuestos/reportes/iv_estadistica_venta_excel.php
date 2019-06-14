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

$frmBuscar = json_decode($_GET['frmBuscar'], true);
$idEmpresa = $valCadBusq[0];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0,1,2)");

$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0,1,2)");

$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.estatus_nota_credito IN (2)");

if ($frmBuscar['lstEmpresa'] != "-1" && $frmBuscar['lstEmpresa'] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($frmBuscar['lstEmpresa'], "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
		valTpDato($frmBuscar['lstEmpresa'], "int"));
}

if ($frmBuscar['lstAplicaLibro'] != "-1" && $frmBuscar['lstAplicaLibro'] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.aplicaLibros = %s",
		valTpDato($frmBuscar['lstAplicaLibro'], "boolean"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.aplicaLibros = %s",
		valTpDato($frmBuscar['lstAplicaLibro'], "boolean"));
}

if (($frmBuscar['cbxNoCancelado'] != "-1" && $frmBuscar['cbxNoCancelado'] != "")
|| ($frmBuscar['cbxCancelado'] != "-1" && $frmBuscar['cbxCancelado'] != "")
|| ($frmBuscar['cbxParcialCancelado'] != "-1" && $frmBuscar['cbxParcialCancelado'] != "")
|| ($frmBuscar['cbxNoCanceladoNC'] != "-1" && $frmBuscar['cbxNoCanceladoNC'] != "")
|| ($frmBuscar['cbxCanceladoNC'] != "-1" && $frmBuscar['cbxCanceladoNC'] != "")
|| ($frmBuscar['cbxParcialCanceladoNC'] != "-1" && $frmBuscar['cbxParcialCanceladoNC'] != "")
|| ($frmBuscar['cbxAsignadoNC'] != "-1" && $frmBuscar['cbxAsignadoNC'] != "")) {
	if ($frmBuscar['cbxNoCancelado'] != "-1" && $frmBuscar['cbxNoCancelado'] != "") $array[] = $frmBuscar['cbxNoCancelado'];
	if ($frmBuscar['cbxCancelado'] != "-1" && $frmBuscar['cbxCancelado'] != "") $array[] = $frmBuscar['cbxCancelado'];
	if ($frmBuscar['cbxParcialCancelado'] != "-1" && $frmBuscar['cbxParcialCancelado'] != "") $array[] = $frmBuscar['cbxParcialCancelado'];
	(!$array) ? $array[] = "-1": "";

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.estadoFactura IN (%s)",
		valTpDato(implode(",",$array), "campo"));
	
	if ($frmBuscar['cbxNoCanceladoNC'] != "-1" && $frmBuscar['cbxNoCanceladoNC'] != "") $array2[] = $frmBuscar['cbxNoCanceladoNC'];
	if ($frmBuscar['cbxCanceladoNC'] != "-1" && $frmBuscar['cbxCanceladoNC'] != "") $array2[] = $frmBuscar['cbxCanceladoNC'];
	if ($frmBuscar['cbxParcialCanceladoNC'] != "-1" && $frmBuscar['cbxParcialCanceladoNC'] != "") $array2[] = $frmBuscar['cbxParcialCanceladoNC'];
	if ($frmBuscar['cbxAsignadoNC'] != "-1" && $frmBuscar['cbxAsignadoNC'] != "") $array2[] = $frmBuscar['cbxAsignadoNC'];
	(!$array2) ? $array2[] = "-1": "";
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.estadoNotaCredito IN (%s)",
		valTpDato(implode(",",$array2), "campo"));
}

if ($frmBuscar['txtFechaDesde'] != "" && $frmBuscar['txtFechaHasta'] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.fechaRegistroFactura BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaDesde'])),"date"),
		valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaHasta'])),"date"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.fechaNotaCredito BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaDesde'])),"date"),
		valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaHasta'])),"date"));
}

if ($frmBuscar['lstEmpleadoVendedor'] != "-1" && $frmBuscar['lstEmpleadoVendedor'] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idVendedor = %s",
		valTpDato($frmBuscar['lstEmpleadoVendedor'], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("fact_vent.idVendedor = %s",
		valTpDato($frmBuscar['lstEmpleadoVendedor'], "int"));
}

if ($frmBuscar['lstClaveMovimiento'] != "-1" && $frmBuscar['lstClaveMovimiento'] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
	WHERE mov.id_tipo_movimiento = 3
		AND mov.id_documento = fact_vent.idFactura) = %s",
		valTpDato($frmBuscar['lstClaveMovimiento'], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
	WHERE mov.id_tipo_movimiento = 2
		AND mov.tipo_documento_movimiento = 2
		AND mov.id_documento = nota_cred.idNotaCredito
	LIMIT 1) = %s",
		valTpDato($frmBuscar['lstClaveMovimiento'], "int"));
}

if ($frmBuscar['txtCriterio'] != "-1" && $frmBuscar['txtCriterio'] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(numeroFactura LIKE %s
	OR numeroControl LIKE %s)",
		valTpDato("%".$frmBuscar['txtCriterio']."%", "text"),
		valTpDato("%".$frmBuscar['txtCriterio']."%", "text"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(nota_cred.numeracion_nota_credito LIKE %s
	OR nota_cred.numeroControl LIKE %s
	OR numeroFactura LIKE %s)",
		valTpDato("%".$frmBuscar['txtCriterio']."%", "text"),
		valTpDato("%".$frmBuscar['txtCriterio']."%", "text"),
		valTpDato("%".$frmBuscar['txtCriterio']."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	fact_vent.fechaRegistroFactura,
	empleado.cedula AS ci_empleado,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	
	COUNT(fact_vent.idFactura) AS cantidad_facturas,
	
	SUM(IFNULL((SELECT COUNT(fact_vent_det.id_factura) FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = fact_vent.idFactura), 0)) AS cant_items,
	
	SUM(IFNULL((SELECT SUM(fact_vent_det.cantidad) FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = fact_vent.idFactura), 0)) AS cant_piezas,
	
	SUM((IFNULL(fact_vent.subtotalFactura, 0)
		- IFNULL(fact_vent.descuentoFactura, 0))) AS total_neto_factura_venta,
	
	SUM(IFNULL((SELECT SUM(fact_vent_det.cantidad * fact_vent_det.costo_compra) FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = fact_vent.idFactura), 0)) AS total_costo_factura_venta,
	
	(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
	WHERE mov.id_tipo_movimiento = 3
		AND mov.id_documento = fact_vent.idFactura) AS id_clave_movimiento,
	
	(SELECT clave_mov.descripcion
	FROM iv_movimiento mov
		INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento)
	WHERE mov.id_tipo_movimiento = 3
		AND mov.id_documento = fact_vent.idFactura) AS descripcion_clave_movimiento
	
FROM cj_cc_encabezadofactura fact_vent
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado) %s
GROUP BY
	fact_vent.fechaRegistroFactura,
	empleado.cedula,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido),
	(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
	WHERE mov.id_tipo_movimiento = 3
		AND mov.id_documento = fact_vent.idFactura)

UNION

SELECT 
	nota_cred.fechaNotaCredito AS fechaRegistroFactura,
	empleado.cedula AS ci_empleado,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	
	(-1) * COUNT(nota_cred.idNotaCredito) AS cantidad_notas_credito,
	
	(-1) * SUM(IFNULL((SELECT COUNT(nota_cred_det.id_nota_credito) FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito), 0)) AS cant_items,
	
	(-1) * SUM(IFNULL((SELECT SUM(nota_cred_det.cantidad) FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito), 0)) AS cant_piezas,
	
	(-1) * SUM((IFNULL(nota_cred.subtotalNotaCredito, 0)
		- IFNULL(nota_cred.subtotal_descuento, 0))) AS total_neto_devolucion_venta,
	
	(-1) * SUM(IFNULL((SELECT SUM(nota_cred_det.cantidad * nota_cred_det.costo_compra) FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito), 0)) AS total_costo_devolucion_venta,
	
	(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
	WHERE mov.id_tipo_movimiento = 2
		AND mov.tipo_documento_movimiento = 2
		AND mov.id_documento = nota_cred.idNotaCredito
	LIMIT 1) AS id_clave_movimiento,
	
	(SELECT clave_mov.descripcion
	FROM iv_movimiento mov
		INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento)
	WHERE mov.id_tipo_movimiento = 2
		AND mov.tipo_documento_movimiento = 2
		AND mov.id_documento = nota_cred.idNotaCredito
	LIMIT 1) AS descripcion_clave_movimiento
	
FROM cj_cc_notacredito nota_cred
	LEFT JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura)
		AND (nota_cred.tipoDocumento LIKE 'FA')
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado) %s
GROUP BY
	nota_cred.fechaNotaCredito,
	empleado.cedula,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido),
(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
WHERE mov.id_tipo_movimiento = 2
	AND mov.tipo_documento_movimiento = 2
	AND mov.id_documento = nota_cred.idNotaCredito
LIMIT 1)

ORDER BY 1 DESC, 2 DESC", $sqlBusq, $sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$existeClaveMov = false;
	if (isset($arrayClaveMov)) {
		foreach ($arrayClaveMov as $indice => $valor) {
			if ($row['id_clave_movimiento'] == $arrayClaveMov[$indice]['id_clave_movimiento']) {
				$existeClaveMov = true;
			}
		}
	}
	
	if ($existeClaveMov == false) {
		$arrayClaveMov[] = array(
			"id_clave_movimiento" => $row['id_clave_movimiento'],
			"descripcion_clave_movimiento" => $row['descripcion_clave_movimiento']);
	}
}

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro.");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Fecha Venta");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $spanCI);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Empleado");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Monto Venta");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Costo Venta");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Cantidad Facturas\n(Nro Facturas\nEmitidas)");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Cantidad Items\n(Sumatoria de los\nRenglones Facturados)");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Cantidad Piezas\n(Sumatorias de las\nPiezas Facturadas)");
if (isset($arrayClaveMov)) {
	$col = "I";
	foreach($arrayClaveMov as $indiceClaveMov => $valorClaveMov) {
		$col++;
		$objPHPExcel->getActiveSheet()->setCellValue($col.$contFila, str_replace(" ","\n",utf8_encode($arrayClaveMov[$indiceClaveMov]['descripcion_clave_movimiento'])));
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col.$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col.$contFila)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col.$contFila)->getAlignment()->setWrapText(true);

$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	$contFila2++;
	
	$clase = ($row['cantidad_facturas'] >= 0) ? $clase : $styleArrayFilaError;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $contFila2);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, date(spanDateFormat,strtotime($row['fechaRegistroFactura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['ci_empleado']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['nombre_empleado']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['total_neto_factura_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['total_costo_factura_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['cantidad_facturas']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['cant_piezas']);
	if (isset($arrayClaveMov)) {
		$col = "I";
		foreach($arrayClaveMov as $indiceClaveMov => $valorClaveMov) {
			$col++;
			if ($row['id_clave_movimiento'] == $arrayClaveMov[$indiceClaveMov]['id_clave_movimiento']) {
				$objPHPExcel->getActiveSheet()->setCellValue($col.$contFila, 1);
				
				$arrayTotal[8 + $indiceClaveMov] += 1;
			} else {
				$objPHPExcel->getActiveSheet()->setCellValue($col.$contFila, 0);
			}
			$objPHPExcel->getActiveSheet()->getStyle($col.$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		}
	}
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".$col.$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			
	$arrayTotal[3] += $row['total_neto_factura_venta'];
	$arrayTotal[4] += $row['total_costo_factura_venta'];
	$arrayTotal[5] += $row['cantidad_facturas'];
	$arrayTotal[6] += $row['cant_items'];
	$arrayTotal[7] += $row['cant_piezas'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotal[3]);
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotal[4]);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal[5]);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal[6]);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal[7]);
if (isset($arrayClaveMov)) {
	$col = "I";
	foreach($arrayClaveMov as $indiceClaveMov => $valorClaveMov) {
		$col++;
		$objPHPExcel->getActiveSheet()->setCellValue($col.$contFila, $arrayTotal[8 + $indiceClaveMov]);
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":".$col.$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":D".$contFila);

for ($col2 = "A"; $col2 != $col; $col2++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col2)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, $col);

$tituloDcto = "Estadisticas de Ventas";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".$col."7");

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