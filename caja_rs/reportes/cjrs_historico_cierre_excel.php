<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
require_once ("../inc_caja.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$rs = mysql_query(sprintf("SELECT IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa FROM vw_iv_empresas_sucursales vw_iv_emp_suc WHERE vw_iv_emp_suc.id_empresa_reg = %s;", valTpDato($valCadBusq[0], "int")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayEmpresa[] = $row['nombre_empresa'];
	}
	$arrayCriterioBusqueda[] = "Empresa: ".((isset($arrayEmpresa)) ? implode(", ", $arrayEmpresa) : "");
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$arrayCriterioBusqueda[] = "Fecha: Desde ".$valCadBusq[1]." Hasta ".$valCadBusq[2];
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$lstVerificacion = array(0 => "Caja No Verificada", 1 => "Caja Verificada");
	foreach ($lstVerificacion as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayVerificacion[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estatus: ".((isset($arrayVerificacion)) ? implode(", ", $arrayVerificacion) : "");
}

////////// CRITERIO DE BUSQUEDA //////////
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cierre.idCierre = (SELECT MAX(cierre2.idCierre) FROM ".$cierreCajaPpal." cierre2 WHERE cierre2.id = apertura.id)
AND apertura.statusAperturaCaja IN (0)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre.fechaCierre BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == 0) { // Caja No Verificada
		$sqlBusq .= $cond.sprintf("cierre.idCierre NOT IN (SELECT id_cierre FROM cj_verificacion_cierre verif_cierre
		WHERE verif_cierre.id_caja = %s
			AND verif_cierre.id_empresa = apertura.id_empresa)",
			valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	} else if ($valCadBusq[3] == 1) { // Caja Verificada
		$sqlBusq .= $cond.sprintf("cierre.idCierre IN (SELECT id_cierre FROM cj_verificacion_cierre verif_cierre
		WHERE verif_cierre.id_caja = %s
			AND verif_cierre.id_empresa = apertura.id_empresa)",
			valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	}
}

//iteramos para los resultados
$query = sprintf("SELECT
	apertura.id,
	apertura.id_empresa,
	apertura.idCaja,
	apertura.fechaAperturaCaja,
	CONCAT_WS(' ', apertura.fechaAperturaCaja, apertura.horaApertura) AS ejecucion_apertura,
	apertura.id_usuario AS idUsuarioApertura,
	cierre.idCierre,
	cierre.fechaCierre,
	CONCAT_WS(' ', cierre.fechaEjecucionCierre, cierre.horaEjecucionCierre) AS ejecucion_cierre,
	cierre.id_usuario AS idUsuarioCierre,
	cierre.tipoCierre,
	(CASE cierre.tipoCierre
		WHEN 0 THEN 'Caja Cerrada'
		WHEN 1 THEN 'Caja Abierta'
		WHEN 2 THEN 'Caja Cierre Parcial'
	END) AS descripcion_tipo_cierre,
	SUM(cierre.cargaEfectivoCaja) AS cargaEfectivoCaja,
	SUM(cierre.saldoCaja) AS saldoCaja,
	SUM(cierre.saldoEfectivo) AS saldoEfectivo,
	SUM(cierre.saldoCheques) AS saldoCheques,
	SUM(cierre.saldoDepositos) AS saldoDepositos,
	SUM(cierre.saldoTransferencia) AS saldoTransferencia,
	SUM(cierre.saldoTarjetaCredito) AS saldoTarjetaCredito,
	SUM(cierre.saldoTarjetaDebito) AS saldoTarjetaDebito,
	SUM(cierre.saldoAnticipo) AS saldoAnticipo,
	SUM(cierre.saldoNotaCredito) AS saldoNotaCredito,
	SUM(cierre.saldoRetencion) AS saldoRetencion,
	SUM(cierre.saldoOtro) AS saldoOtro,
	cierre.observacion,
	
	(SELECT nombre_usuario FROM pg_usuario usuario
	WHERE usuario.id_usuario = apertura.id_usuario) AS usuario_apertura,
	
	(SELECT nombre_usuario FROM pg_usuario usuario
	WHERE usuario.id_usuario = cierre.id_usuario) AS usuario_cierre,
	
	(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
	WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
								WHERE usuario.id_usuario = apertura.id_usuario)) AS empleado_apertura,
								
	(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
	WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
								WHERE usuario.id_usuario = cierre.id_usuario)) AS empleado_cierre,
	
	(SELECT MAX(accion) AS accion FROM cj_verificacion_cierre verif_cierre
	WHERE verif_cierre.id_caja = apertura.idCaja
		AND verif_cierre.id_apertura = apertura.id
		AND verif_cierre.id_cierre = cierre.idCierre
		AND verif_cierre.id_empresa = apertura.id_empresa
	LIMIT 1) AS accion_verif_cierre,
								
	(SELECT COUNT(cxc_fact.fechaRegistroFactura) FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.fechaRegistroFactura = cierre.fechaCierre
		AND idDepartamentoOrigenFactura IN (%s)
	GROUP BY cxc_fact.fechaRegistroFactura) AS cant_fact_cred,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM ".$apertCajaPpal." apertura
	INNER JOIN ".$cierreCajaPpal." cierre ON (apertura.id = cierre.id)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cierre.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
GROUP BY apertura.id, cierre.fechaCierre
ORDER BY CONCAT_WS(' ', apertura.id, apertura.fechaAperturaCaja) DESC",
	valTpDato($idModuloPpal, "campo"),
	$sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha Apertura");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Empleado Apertura");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Ejecución Apertura");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Empleado Cierre");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Ejecución Cierre");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Saldo de Caja");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Carga Efectivo");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Saldo Efectivo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Saldo Cheques");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Saldo Depositos");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Saldo Transferencia");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Saldo Tarjeta Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Saldo Tarjeta Débito");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Saldo Anticipo");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Saldo Nota Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Saldo Retención");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Saldo Otro");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch ($row['accion_verif_cierre']) {
		case 1 : $estatus = "Aprobada"; break;
		case 2 : $estatus = "Validada"; break;
		default : $estatus = "No Verificada"; break;
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $estatus);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, date(spanDateFormat, strtotime($row['fechaAperturaCaja'])));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['empleado_apertura']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat." H:i:s", strtotime($row['ejecucion_apertura'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['empleado_cierre']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat." H:i:s", strtotime($row['ejecucion_cierre'])));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['saldoCaja']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['cargaEfectivoCaja']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['saldoEfectivo']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $row['saldoCheques']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['saldoDepositos']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['saldoTransferencia']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['saldoTarjetaCredito']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['saldoTarjetaDebito']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['saldoAnticipo']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['saldoNotaCredito']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['saldoRetencion']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['saldoOtro']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	
	$arrayTotal[8] += $row['saldoCaja'];
	$arrayTotal[9] += $row['cargaEfectivoCaja'];
	$arrayTotal[10] += $row['saldoEfectivo'];
	$arrayTotal[11] += $row['saldoCheques'];
	$arrayTotal[12] += $row['saldoDepositos'];
	$arrayTotal[13] += $row['saldoTransferencia'];
	$arrayTotal[14] += $row['saldoTarjetaCredito'];
	$arrayTotal[15] += $row['saldoTarjetaDebito'];
	$arrayTotal[16] += $row['saldoAnticipo'];
	$arrayTotal[17] += $row['saldoNotaCredito'];
	$arrayTotal[18] += $row['saldoRetencion'];
	$arrayTotal[19] += $row['saldoOtro'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":S".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal[8]);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal[9]);
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal[10]);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotal[11]);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotal[12]);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotal[13]);
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotal[14]);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $arrayTotal[15]);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $arrayTotal[16]);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal[17]);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal[18]);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal[19]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."G".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila.":"."S".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."G".$contFila);

for ($col = "A"; $col != "S"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "S", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Cierre (".$nombreCajaPpal.")";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:S7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:S9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

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