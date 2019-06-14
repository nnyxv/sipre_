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
	$lstAplicaLibro = array(0 => "No", 1 => "Si");
	foreach ($lstAplicaLibro as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayAplicaLibro[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Aplica Libro: ".((isset($arrayAplicaLibro)) ? implode(", ", $arrayAplicaLibro) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstEstadoNotaCargo = array(0 => "No Cancelado", 1 => "Cancelado", 2 => "Cancelado Parcial");
	foreach ($lstEstadoNotaCargo as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayEstadoNotaCargo[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estado Nota de Débito: ".((isset($arrayEstadoNotaCargo)) ? implode(", ", $arrayEstadoNotaCargo) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	foreach (explode(",", $valCadBusq[5]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = $row['descripcionModulo'];
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[6];
}

////////// CRITERIO DE BUSQUEDA //////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idDepartamentoOrigenNotaCargo IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.aplicaLibros = %s",
		valTpDato($valCadBusq[3], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.estadoNotaCargo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
	OR cxc_nd.numeroControlNotaCargo LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR cxc_nd.observacionNotaCargo LIKE %s
	OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_chasis LIKE %s
	OR uni_fis.placa LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	cxc_nd.idNotaCargo,
	cxc_nd.numeroNotaCargo,
	cxc_nd.numeroControlNotaCargo,
	cxc_nd.fechaRegistroNotaCargo,
	cxc_nd.fechaVencimientoNotaCargo,
	cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_nd.estadoNotaCargo,
	(CASE cxc_nd.estadoNotaCargo
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_nota_cargo,
	cxc_nd.montoTotalNotaCargo,
	cxc_nd.saldoNotaCargo,
	cxc_nd.observacionNotaCargo,
	cxc_nd.aplicaLibros,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
		INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
	
	(IFNULL(cxc_nd.subtotalNotaCargo, 0)
		- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
	
	(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
		+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
	
	(IFNULL(cxc_nd.subtotalNotaCargo, 0)
		- IFNULL(cxc_nd.descuentoNotaCargo, 0)
		+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
			+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
	
	uni_fis.id_unidad_fisica,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_notadecargo cxc_nd
	INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY idNotaCargo DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$queryFormaPago = ("SELECT * FROM formapagos ORDER BY nombreFormaPago ASC");
$rsFormaPago = mysql_query($queryFormaPago);
if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFormaPago = mysql_num_rows($rsFormaPago);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Formas de Pago");

$objPHPExcel->getActiveSheet()->getStyle("O".$contFila.":".ultimaColumnaExcel("O", $totalRowsFormaPago).$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("O".$contFila.":".ultimaColumnaExcel("O", $totalRowsFormaPago).$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Venc. Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Motivo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Estado Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Saldo Nota de Débito");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Total Nota de Débito");

$contColum = "N";
while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
	$contColum++;
	
	$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowFormaPago['nombreFormaPago']);
}
$contColumUlt = $contColum;

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgDctoModulo = "Repuestos"; break;
		case 1 : $imgDctoModulo = "Servicios"; break;
		case 2 : $imgDctoModulo = "Vehículos"; break;
		case 3 : $imgDctoModulo = "Administración"; break;
		case 4 : $imgDctoModulo = "Alquiler"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgDctoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, date(spanDateFormat, strtotime($row['fechaRegistroNotaCargo'])));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fechaVencimientoNotaCargo'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['numeroNotaCargo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, $row['numeroControlNotaCargo'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['id_cliente'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFila, utf8_encode($row['ci_cliente']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['descripcion_motivo']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['observacionNotaCargo']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['descripcion_estado_nota_cargo']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['saldoNotaCargo']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['total']);
	
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$contColum = "N";
	while ($rowFormaPago = mysql_fetch_assoc($rsFormaPago)) {
		$contColum++;
		
		$queryDctoPago = sprintf("SELECT *
		FROM (SELECT 
				cxc_pago.idNotaCargo,
				cxc_pago.fechaPago,
				cxc_pago.idFormaPago,
				cxc_pago.monto_pago
			FROM cj_det_nota_cargo cxc_pago) AS query
		WHERE query.idNotaCargo = %s
			AND query.idFormaPago = %s",
			valTpDato($row['idNotaCargo'], "int"),
			valTpDato($rowFormaPago['idFormaPago'], "int"));
		$rsDctoPago = mysql_query($queryDctoPago);
		if (!$rsDctoPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsDctoPago = mysql_num_rows($rsDctoPago);
		$totalPagosDcto = 0;
		while ($rowDctoPago = mysql_fetch_assoc($rsDctoPago)) {
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $rowDctoPago['monto_pago']);
			$totalPagosDcto += $rowDctoPago['monto_pago'];
		}
		
		$arrayTotalPago[$contColum] += $totalPagosDcto;
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":".($contColumUlt).$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$contColum = "N";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	}
	
	
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	$contColum = "N";
	for ($cont = 1; $cont <= $totalRowsFormaPago; $cont++) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_origen'].'"#,##0.00');
	}
	
	$arrayTotal[9] += $row['saldoNotaCargo'];
	$arrayTotal[10] += $row['total'];
	$cont = 10;
	if (isset($arrayTotalPago)) {
		foreach ($arrayTotalPago as $indice => $valor) {
			$cont++;
			$arrayTotal[$cont] = $arrayTotalPago[$indice];
		}
	}
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":".($contColumUlt).$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$contColum = "L";
if (isset($arrayTotalPago)) {
	foreach ($arrayTotal as $indice => $valor) {
		$contColum++;
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFila, $arrayTotal[$indice]);
		
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	}
}

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."L".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("M".$contFila.":".($contColumUlt).$contFila)->applyFromArray($styleArrayResaltarTotal);;

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."L".$contFila);

for ($col = "A"; $col != ($contColumUlt); $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, ($contColumUlt), true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Histórico de Notas de Débito";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:".($contColumUlt)."7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:".($contColumUlt)."9");
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