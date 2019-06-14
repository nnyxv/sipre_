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
	
$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
$rsGrupoEstado = mysql_query($queryGrupoEstado);
if (!$rsGrupoEstado) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_cxp_as.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_cxp_as.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp_as.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
}

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("DATE(vw_cxp_as.fecha_origen) <= %s",
	valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));

if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ROUND(vw_cxp_as.saldoFactura, 2) > 0",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((CASE
		WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
			(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
													WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
														AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
														AND cxp_pago.fecha_pago <= %s
														AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
		WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
			(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
													WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
														AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
														AND cxp_pago.fecha_pago <= %s
														AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
	END) > 0
		AND NOT ((CASE
				WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
					(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
					WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
						AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
						AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))) 
				WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
					(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
					WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
						AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
						AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s)))
			END) < %s
				AND ((vw_cxp_as.tipoDocumento IN ('FA','ND') AND vw_cxp_as.estadoFactura IN (1))
					OR (vw_cxp_as.tipoDocumento IN ('AN','NC') AND vw_cxp_as.estadoFactura IN (3)))))",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	 // 1 = Detallado por Empresa, 2 = Consolidado
	$groupBy = ($valCadBusq[4] == 1) ? "GROUP BY vw_cxp_as.id_empresa, vw_cxp_as.id_proveedor" : "GROUP BY vw_cxp_as.id_proveedor";
} else {
	$groupBy = "GROUP BY vw_cxp_as.id_empresa, vw_cxp_as.id_proveedor";
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp_as.id_modulo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp_as.tipoDocumento IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$arrayDiasVencidos = NULL;
	if (in_array("corriente",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																						WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("desde1",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																						WHERE grupo_ec.idGrupoEstado = 1)
		AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
															WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("desde2",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																						WHERE grupo_ec.idGrupoEstado = 1)
		AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
															WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("desde3",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																						WHERE grupo_ec.idGrupoEstado = 1)
		AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
															WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	if (in_array("masDe",explode(",",$valCadBusq[7]))) {
		$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																						WHERE grupo_ec.idGrupoEstado = 1))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_cxp_as.numeroFactura LIKE %s
	OR prov.nombre LIKE %s)",
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"));
}

//iteramos para los resultados
if (in_array($valCadBusq[3],array(1))) {
	$query = sprintf("SELECT
		vw_cxp_as.*,
		prov.nombre AS nombre_proveedor,
		(CASE
			WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
				IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND cxp_pago.fecha_pago <= %s
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
			WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
				IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND cxp_pago.fecha_pago <= %s
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
		END) AS total_pagos,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_antiguedad_saldo vw_cxp_as
		INNER JOIN cp_proveedor prov ON (vw_cxp_as.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	ORDER BY prov.nombre ASC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
} else {
	$query = sprintf("SELECT
		vw_cxp_as.*,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_antiguedad_saldo vw_cxp_as
		INNER JOIN cp_proveedor prov ON (vw_cxp_as.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s
	ORDER BY prov.nombre ASC", $sqlBusq, $groupBy);
}
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFilaY = 0;

$contFilaY++;
$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFilaY, "Estado de Cuenta al ".$valCadBusq[2], PHPExcel_Cell_DataType::TYPE_STRING);
$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayTitulo);

$contFilaY++;
$primero = $contFilaY;

if (in_array($valCadBusq[3],array(1))) {
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, "");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, "Empresa");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFilaY, "Fecha Registro");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, "Fecha Dcto. Proveedor");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, "Fecha Venc. Dcto.");
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "Tipo Dcto.");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "Nro. Dcto.");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY, "Id");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFilaY, "Proveedor");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFilaY, "Saldo");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFilaY, "Cta. Corriente");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, "De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, "Mas de ".$rowGrupoEstado['masDe']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":P".$contFilaY)->applyFromArray($styleArrayColumna);
} else {
	$contColum = "A";
	if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Proveedor, 4 = General por Dcto.
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, ((in_array($valCadBusq[3],array(3))) ? "Id" : ""));
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Empresa");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Fecha Registro");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Fecha Dcto. Proveedor");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Fecha Venc. Dcto.");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Tipo Dcto.");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Nro. Dcto.");
		if (!in_array($valCadBusq[3],array(3))) {
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Id");
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Proveedor");
		}
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Saldo");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Cta. Corriente");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFilaY, "Mas de ".$rowGrupoEstado['masDe']);
	} else {
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "");
		($valCadBusq[4] == 1) ? $objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Empresa") : "";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Id");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Proveedor");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Saldo");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Cta. Corriente");
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFilaY, "Mas de ".$rowGrupoEstado['masDe']);
	}
	$contColumUlt = $contColum;
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($styleArrayColumna);
}
$objPHPExcel->getActiveSheet()->mergeCells((in_array($valCadBusq[3],array(1))) ? "A".($contFilaY-1).":P".($contFilaY-1) : "A".($contFilaY-1).":".($contColumUlt).($contFilaY-1));

while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$numeroSiniestro = "";
	$totalSaldo = 0;
	$totalCorriente = 0;
	$totalEntre1 = 0;
	$totalEntre2 = 0;
	$totalEntre3 = 0;
	$totalMasDe = 0;
	
	if (in_array($valCadBusq[3],array(1))) {
		$clase = (fmod($contFilaY, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		
		$fecha1 = strtotime($valCadBusq[2]);
		$fecha2 = strtotime($row['fecha_vencimiento']);
		
		$dias = ($fecha1 - $fecha2) / 86400;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = ("Repuestos"); break;
			case 1 : $imgPedidoModulo = ("Servicios"); break;
			case 2 : $imgPedidoModulo = ("Vehículos"); break;
			case 3 : $imgPedidoModulo = ("Administración"); break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
			$totalSaldo += $row['total_cuenta_pagar'] - $row['total_pagos'];
		} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
			$totalSaldo -= $row['total_cuenta_pagar'] - $row['total_pagos'];
		}
		
		if ($dias < $rowGrupoEstado['desde1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalCorriente += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalCorriente -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde1'] && $dias <= $rowGrupoEstado['hasta1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre1 += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre1 -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde2'] && $dias <= $rowGrupoEstado['hasta2']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre2 += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre2 -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde3'] && $dias <= $rowGrupoEstado['hasta3']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre3 += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre3 -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else {
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalMasDe += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalMasDe -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, $contFila);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFilaY, $imgPedidoModulo, PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFilaY, utf8_encode($row['nombre_empresa']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFilaY, date(spanDateFormat, strtotime($row['fecha_origen'])), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFilaY, date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFilaY, date(spanDateFormat, strtotime($row['fecha_vencimiento'])), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFilaY, utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*"), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("H".$contFilaY, $row['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFilaY, $row['id_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFilaY, utf8_encode($row['nombre_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFilaY, $totalSaldo);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFilaY, $totalCorriente);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, $totalEntre1);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $totalEntre2);
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $totalEntre3);
		$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalMasDe);
			
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":P".$contFilaY)->applyFromArray($clase);
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotalPagina[8] += $totalSaldo;
		$arrayTotalPagina[9] += $totalCorriente;
		$arrayTotalPagina[10] += $totalEntre1;
		$arrayTotalPagina[11] += $totalEntre2;
		$arrayTotalPagina[12] += $totalEntre3;
		$arrayTotalPagina[13] += $totalMasDe;
	} else {
		$totalSaldoProv = 0;
		$totalCorrienteProv = 0;
		$totalEntre1Prov = 0;
		$totalEntre2Prov = 0;
		$totalEntre3Prov = 0;
		$totalMasDeProv = 0;
		
		$sqlBusq2 = "";
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			if ($valCadBusq[4] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.id_empresa = %s)",
					valTpDato($row['id_empresa'], "int"));
			} else {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vw_cxp_as.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.id_empresa = %s)",
				valTpDato($row['id_empresa'], "int"));
		}
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxp_as.id_proveedor = %s",
			valTpDato($row['id_proveedor'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("DATE(vw_cxp_as.fecha_origen) <= %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("ROUND(vw_cxp_as.saldoFactura, 2) > 0",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
				WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
					(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
															WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
																AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
																AND cxp_pago.fecha_pago <= %s
																AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
				WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
					(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
															WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
																AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
																AND cxp_pago.fecha_pago <= %s
																AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
			END) > 0
				AND NOT ((CASE
						WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
							(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))) 
						WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
							(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s)))
					END) < %s
						AND ((vw_cxp_as.tipoDocumento IN ('FA','ND') AND vw_cxp_as.estadoFactura IN (1))
							OR (vw_cxp_as.tipoDocumento IN ('AN','NC') AND vw_cxp_as.estadoFactura IN (3)))))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
			
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_as.id_modulo IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_as.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$arrayDiasVencidos = NULL;
			if (in_array("corriente",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde1",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde2",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde3",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("masDe",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.numeroFactura LIKE %s
			OR prov.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"));
		}
		
		$queryEstado = sprintf("SELECT
			vw_cxp_as.*,
			prov.nombre AS nombre_proveedor,
			(CASE
				WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
					IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND cxp_pago.fecha_pago <= %s
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
				WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
					IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND cxp_pago.fecha_pago <= %s
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
			END) AS total_pagos,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cp_antiguedad_saldo vw_cxp_as
			INNER JOIN cp_proveedor prov ON (vw_cxp_as.id_proveedor = prov.id_proveedor)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			$sqlBusq2);
		$rsEstado = mysql_query($queryEstado);
		if (!$rsEstado) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEstado = mysql_num_rows($rsEstado);
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
			if ($contFila > 1) {
				$contFilaY++;
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, " ");
			}
			
			$contFilaY++;
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->SetCellValue(($contColum++).$contFilaY, (($contFila) + (($pageNum) * $maxRows)));
			$objPHPExcel->getActiveSheet()->SetCellValue(($contColum++).$contFilaY, $row['id_proveedor']);
			$objPHPExcel->getActiveSheet()->SetCellValue(($contColum).$contFilaY, utf8_encode($row['nombre_proveedor']));
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY)->applyFromArray($styleArrayCampo);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			
			$contColum = "C";
			$objPHPExcel->getActiveSheet()->mergeCells(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY);
			
			$contFila2 = 0;
		}
		
		while ($rowEstado = mysql_fetch_array($rsEstado)) {
			$totalSaldo = 0;
			$totalCorriente = 0;
			$totalEntre1 = 0;
			$totalEntre2 = 0;
			$totalEntre3 = 0;
			$totalMasDe = 0;
			
			$fecha1 = strtotime($valCadBusq[2]);
			$fecha2 = strtotime($rowEstado['fecha_vencimiento']);
			
			$dias = ($fecha1 - $fecha2) / 86400;
			
			switch($rowEstado['id_modulo']) {
				case 0 : $imgPedidoModulo = ("Repuestos"); break;
				case 1 : $imgPedidoModulo = ("Servicios"); break;
				case 2 : $imgPedidoModulo = ("Vehículos"); break;
				case 3 : $imgPedidoModulo = ("Administración"); break;
				default : $imgPedidoModulo = $row['id_modulo'];
			}
			
			if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalSaldo += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
			} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalSaldo -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
			}
			
			if ($dias < $rowGrupoEstado['desde1']){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalCorriente += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalCorriente -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde1']) && ($dias <= $rowGrupoEstado['hasta1'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre1 += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre1 -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde2']) && ($dias <= $rowGrupoEstado['hasta2'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre2 += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre2 -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde3']) && ($dias <= $rowGrupoEstado['hasta3'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre3 += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre3 -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else {
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalMasDe += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalMasDe -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			}
			
			$contColum = "A";
			if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Proveedor, 4 = General por Dcto.
				$clase = (fmod($contFilaY, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
				$contFilaY++;
				$contFila2++;
				
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $contFila2);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $imgPedidoModulo, PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['nombre_empresa']), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, date(spanDateFormat, strtotime($rowEstado['fecha_origen'])), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, date(spanDateFormat, strtotime($rowEstado['fecha_factura_proveedor'])), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, date(spanDateFormat, strtotime($rowEstado['fecha_vencimiento'])), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['tipoDocumento']).(($rowEstado['idEstadoCuenta'] > 0) ? "" : "*"), PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $rowEstado['numeroFactura'], PHPExcel_Cell_DataType::TYPE_STRING);
				if (!in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
					$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $rowEstado['id_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
					$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($rowEstado['nombre_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
				}
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalSaldo);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalCorriente);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre1);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre2);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre3);
				$objPHPExcel->getActiveSheet()->setCellValue(($contColum).$contFilaY, $totalMasDe);
				
				$contColum = "A";
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($clase);
				$contColum = "A";
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
				if (!in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
					$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
					$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);		
				}
				
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			}
			
			$totalSaldoProv += $totalSaldo;
			$totalCorrienteProv += $totalCorriente;
			$totalEntre1Prov += $totalEntre1;
			$totalEntre2Prov += $totalEntre2;
			$totalEntre3Prov += $totalEntre3;
			$totalMasDeProv += $totalMasDe;
		}
		
		$contColum = "A";
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
			$contFilaY++;
			
			$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($row['nombre_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
			
			$contColum = "I";
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalSaldoProv);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalCorrienteProv);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre1Prov);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre2Prov);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre3Prov);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalMasDeProv);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":H".$contFilaY)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":H".$contFilaY);
			$contColum = "I";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($styleArrayResaltarTotal);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			
			$contColum = "I";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Proveedor, 4 = General por Dcto.
		} else {
			$clase = (fmod($contFilaY, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
			$contFilaY++;
			
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $contFila);
			($valCadBusq[4] == 1) ? $objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $row['nombre_empresa'], PHPExcel_Cell_DataType::TYPE_STRING) : "";
			$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, $row['id_proveedor'], PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->setCellValueExplicit(($contColum++).$contFilaY, utf8_encode($row['nombre_proveedor']), PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalSaldoProv);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalCorrienteProv);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre1Prov);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre2Prov);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalEntre3Prov);
			$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $totalMasDeProv);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($clase);
			
			$contColum = "A";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			($valCadBusq[4] == 1) ? $objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT) : "";
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		}
		
		$arrayTotalPagina[4] += $totalSaldoProv;
		$arrayTotalPagina[5] += $totalCorrienteProv;
		$arrayTotalPagina[6] += $totalEntre1Prov;
		$arrayTotalPagina[7] += $totalEntre2Prov;
		$arrayTotalPagina[8] += $totalEntre3Prov;
		$arrayTotalPagina[9] += $totalMasDeProv;
	}
}
$ultimo = $contFilaY;
if (in_array($valCadBusq[3],array(1))) {
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Totales:");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFilaY, $arrayTotalPagina[8]);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFilaY, $arrayTotalPagina[9]);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, $arrayTotalPagina[10]);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $arrayTotalPagina[11]);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $arrayTotalPagina[12]);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $arrayTotalPagina[13]);
		
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":J".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":J".$contFilaY);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFilaY.":P".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
} else {
	$contFilaY++;
	
	$contColum = "A";
	$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, "Totales:");
	if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
		$contColum = "I";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[4]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[5]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[6]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[7]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[8]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[9]);
		
		$contColum = "A";
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":H".$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":H".$contFilaY);
		$contColum = "I";
	} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Proveedor, 4 = General por Dcto.
		$contColum = "K";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[4]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[5]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[6]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[7]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[8]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[9]);
		
		$contColum = "A";
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":J".$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":J".$contFilaY);
		$contColum = "K";
	} else {
		$contColum = ($valCadBusq[4] == 1) ? "E" : "D";
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[4]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[5]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[6]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[7]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[8]);
		$objPHPExcel->getActiveSheet()->setCellValue(($contColum++).$contFilaY, $arrayTotalPagina[9]);
		
		$contColum = "A";
		$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":".(($valCadBusq[4] == 1) ? "D" : "C").$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->mergeCells(($contColum).$contFilaY.":".(($valCadBusq[4] == 1) ? "D" : "C").$contFilaY);
		$contColum = ($valCadBusq[4] == 1) ? "E" : "D";
	}
	$objPHPExcel->getActiveSheet()->getStyle(($contColum).$contFilaY.":".($contColumUlt).$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle(($contColum++).$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}

$objPHPExcel->getActiveSheet()->setAutoFilter((in_array($valCadBusq[3],array(1))) ? "A".$primero.":P".$ultimo : "A".$primero.":".($contColumUlt).$ultimo);

cabeceraExcel($objPHPExcel, $idEmpresa, (in_array($valCadBusq[3],array(1))) ? "P" : ($contColumUlt));

$tituloDcto = "Estado de Cuenta CxP (Antigüedad de Saldos)";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells((in_array($valCadBusq[3],array(1))) ? "A7:P7" : "A7:".($contColumUlt)."7");

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