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
	
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	((CASE kardex.tipoMovimiento
		WHEN 1 THEN -- COMPRA
			(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
		WHEN 2 THEN -- ENTRADA
			(CASE kardex.tipo_documento_movimiento
				WHEN 1 THEN -- ENTRADA CON VALE
					(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
					(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
				WHEN 3 THEN -- ENTRADA CON CONTRATO
					(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
			END)
		WHEN 3 THEN -- VENTA
			(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
		WHEN 4 THEN -- SALIDA
			(CASE kardex.tipo_documento_movimiento
				WHEN 1 THEN -- SALIDA CON VALE
					(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
				WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
					(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
				WHEN 3 THEN -- SALIDA CON CONTRATO
					(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
			END)
	END) = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
										WHEN 1 THEN -- COMPRA
											(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
										WHEN 2 THEN -- ENTRADA
											(CASE kardex.tipo_documento_movimiento
												WHEN 1 THEN -- ENTRADA CON VALE
													(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
												WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
													(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
												WHEN 3 THEN -- ENTRADA CON CONTRATO
													(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
											END)
										WHEN 3 THEN -- VENTA
											(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
										WHEN 4 THEN -- SALIDA
											(CASE kardex.tipo_documento_movimiento
												WHEN 1 THEN -- SALIDA CON VALE
													(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
												WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
													(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
												WHEN 3 THEN -- SALIDA CON CONTRATO
													(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
											END)
									END)))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(DATE(fechaMovimiento) BETWEEN %s AND %s
	OR (SELECT
			SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * k.cantidad) AS saldo_anterior
		FROM an_kardex k
			INNER JOIN an_unidad_fisica uni_fis ON (k.idUnidadFisica = uni_fis.id_unidad_fisica)
		WHERE k.idUnidadBasica = vw_iv_modelo.id_uni_bas
			AND DATE(k.fechaMovimiento) < %s) > 0)",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));
}

if ($valCadBusq[3] != "" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_marca LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_chasis LIKE %s
	OR uni_fis.placa LIKE %s)",
		valTpDato($valCadBusq[4], "int"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT 
	vw_iv_modelo.id_uni_bas,
	vw_iv_modelo.nom_uni_bas,
	CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
FROM an_kardex kardex
	INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas) %s
GROUP BY 1,2,3
ORDER BY vw_iv_modelo.nom_uni_bas ASC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
$nroHoja = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idUnidadBasica = $row['id_uni_bas'];
	
	//Trabajamos con la hoja activa secundaria
	if ($nroHoja > 0 && $_GET['lstOrientacionExcel'] == 1) {
		$objPHPExcel->createSheet(NULL, $nroHoja);
		$contFilaY = 0;
	}
	$objPHPExcel->setActiveSheetIndex($nroHoja);
	
	if ($_GET['lstOrientacionExcel'] == 2) {
		$contFilaY++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Kardex ".$row['nom_uni_bas']);
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayTitulo);
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":Q".$contFilaY);
	}
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Unidad Básica:");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, $row['nom_uni_bas']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "C: Compra");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "E: Entrada");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY, "E-NC: Entrada por Nota de Crédito");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "E-TRNS.ALM: Entrada por Transferencia de Almacen");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":B".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("C".$contFilaY.":F".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("I".$contFilaY.":L".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("M".$contFilaY.":P".$contFilaY);
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Descripción:");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, $row['vehiculo']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "V: Venta");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "S: Salida");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY, "S-GRNTA: Salida por Garantía");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "S-TRNS.ALM: Salida por Transferencia de Almacen");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":B".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("C".$contFilaY.":F".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("I".$contFilaY.":L".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("M".$contFilaY.":P".$contFilaY);
	
	$contFilaY++;
	$primero = $contFilaY;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "     ");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, "     ");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, "Fecha");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFilaY, "Empresa");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, $spanSerialCarroceria);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, $spanSerialMotor);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "T");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "Nro. Documento");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFilaY, "C/P/N");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, "E/S");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "Lote");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, "Saldo");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $spanPrecioUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, "Costo Unit.");
	
	$objPHPExcel->getActiveSheet()->mergeCells("H".$contFilaY.":I".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("J".$contFilaY.":L".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":Q".$contFilaY)->applyFromArray($styleArrayColumna);
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("k.idUnidadBasica = %s
	AND DATE(k.fechaMovimiento) < %s",
		valTpDato($idUnidadBasica, "int"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));
		
	if ($valCadBusq[3] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[3], "int"));
	}
	
	$queryArticuloSaldoAnt = sprintf("SELECT
		SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * k.cantidad) AS saldo_anterior,
		SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * 
			(CASE
				WHEN (k.tipoMovimiento = 1) THEN
					k.costo + k.costo_cargo - k.subtotal_descuento
				WHEN (k.tipoMovimiento = 2) THEN
					(CASE
						WHEN (k.tipo_documento_movimiento = 1) THEN
							k.costo + k.costo_cargo - k.subtotal_descuento
						WHEN (k.tipo_documento_movimiento = 2) THEN
							k.precio - k.subtotal_descuento
						WHEN (k.tipo_documento_movimiento = 3) THEN
							k.costo + k.costo_cargo - k.subtotal_descuento
					END)
				WHEN (k.tipoMovimiento = 3) THEN
					k.precio - k.subtotal_descuento
				WHEN (k.tipoMovimiento = 4) THEN
					(CASE
						WHEN (k.tipo_documento_movimiento = 1) THEN
							k.precio - k.subtotal_descuento
						WHEN (k.tipo_documento_movimiento = 2) THEN
							k.costo + k.costo_cargo - k.subtotal_descuento
						WHEN (k.tipo_documento_movimiento = 3) THEN
							k.precio - k.subtotal_descuento
					END)
			END)
		) AS total_precio_saldo_anterior,
		SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * 
			(CASE
				WHEN (k.tipoMovimiento = 1) THEN
					k.costo + k.costo_cargo - k.subtotal_descuento
				WHEN (k.tipoMovimiento = 2) THEN
					(CASE
						WHEN (k.tipo_documento_movimiento = 1) THEN
							k.costo + k.costo_cargo - k.subtotal_descuento
						WHEN (k.tipo_documento_movimiento = 2) THEN
							k.costo
						WHEN (k.tipo_documento_movimiento = 3) THEN
							k.costo + k.costo_cargo - k.subtotal_descuento
					END)
				WHEN (k.tipoMovimiento = 3) THEN
					k.costo
				WHEN (k.tipoMovimiento = 4) THEN
					(CASE
						WHEN (k.tipo_documento_movimiento = 1) THEN
							k.costo
						WHEN (k.tipo_documento_movimiento = 2) THEN
							k.costo + k.costo_cargo - k.subtotal_descuento
						WHEN (k.tipo_documento_movimiento = 3) THEN
							k.costo
					END)
			END)
		) AS total_costo_saldo_anterior
	FROM an_kardex k
		INNER JOIN an_unidad_fisica uni_fis ON (k.idUnidadFisica = uni_fis.id_unidad_fisica) %s", $sqlBusq2);
	$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
	if (!$rsArticuloSaldoAnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
	$totalEntrada = 0;
	$totalValorEntradaPrecio = 0;
	$totalValorEntradaCosto = 0;
	$totalSalida = 0;
	$totalValorSalidaPrecio = 0;
	$totalValorSalidaCosto = 0;
	$entradaSalida = 0;
	$contFilaY2 = 0;
	if ($rowArticuloSaldoAnt['saldo_anterior'] != 0) {
		$clase = (fmod($contFilaY2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		$contFilaY2++;
		
		$totalEntrada = $rowArticuloSaldoAnt['saldo_anterior'];
		$totalValorEntradaPrecio = $rowArticuloSaldoAnt['total_precio_saldo_anterior'];
		$totalValorEntradaCosto = $rowArticuloSaldoAnt['total_costo_saldo_anterior'];
		
		$entradaSalida = $rowArticuloSaldoAnt['saldo_anterior'];
		
		$cantSaldoAnterior = $rowArticuloSaldoAnt['saldo_anterior'];
		$totalValorSaldoAnteriorPrecio = $rowArticuloSaldoAnt['total_precio_saldo_anterior'];
		$totalValorSaldoAnteriorCosto = $rowArticuloSaldoAnt['total_costo_saldo_anterior'];
		
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, "Saldo Anterior al Intervalo de Fecha Seleccionado:");
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $cantSaldoAnterior);
		$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalValorEntradaPrecio);
		$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, $totalValorEntradaCosto);
		
		$objPHPExcel->getActiveSheet()->mergeCells("B".$contFilaY.":I".$contFilaY);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":Q".$contFilaY)->applyFromArray($clase);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY.":I".$contFilaY)->applyFromArray($styleArrayCampo);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
	
	$sqlBusq4 = "";
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("uni_bas.id_uni_bas = %s",
		valTpDato($row['id_uni_bas'],"int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("
		((CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
											WHEN 1 THEN -- COMPRA
												(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- ENTRADA CON VALE
														(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
														(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
													WHEN 3 THEN -- ENTRADA CON CONTRATO
														(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
												END)
											WHEN 3 THEN -- VENTA
												(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
											WHEN 4 THEN -- SALIDA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- SALIDA CON VALE
														(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
													WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
														(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
													WHEN 3 THEN -- SALIDA CON CONTRATO
														(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
												END)
										END)))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("DATE(fechaMovimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_marca LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$queryDetalle = sprintf("SELECT
		kardex.idKardex,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) AS id_empresa,
		
		(SELECT
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		WHERE vw_iv_emp_suc.id_empresa_reg = (CASE kardex.tipoMovimiento
												WHEN 1 THEN -- COMPRA
													(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
												WHEN 2 THEN -- ENTRADA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- ENTRADA CON VALE
															(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
														WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
															(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
														WHEN 3 THEN -- ENTRADA CON CONTRATO
															(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
													END)
												WHEN 3 THEN -- VENTA
													(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
												WHEN 4 THEN -- SALIDA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- SALIDA CON VALE
															(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
														WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
															(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
														WHEN 3 THEN -- SALIDA CON CONTRATO
															(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
													END)
											END)) AS nombre_empresa,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		cond_unidad.descripcion AS condicion_unidad,
		kardex.id_documento,
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT an_ve.numeracion_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						(SELECT al_contrato.numero_contrato_venta FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT an_vs.numeracion_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT al_contrato.numero_contrato_venta FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) AS numero_documento,
		
		2 AS id_modulo,
		(CASE 2
			WHEN 0 THEN		'R'
			WHEN 1 THEN		'S'
			WHEN 2 THEN		'V'
			WHEN 3 THEN		'C'
			WHEN 4 THEN		'AL'
		END) AS nombre_modulo,
		
		kardex.tipoMovimiento,
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN	'C'
			WHEN 2 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(CASE (SELECT an_ve.tipo_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
							WHEN 4 THEN		'E-TRNS.ALM'
							ELSE			'E'
						END)
					WHEN 2 THEN
						'E-NC'
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						'E-ALQUILER'
				END)
			WHEN 3 THEN 'V'
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(CASE (SELECT an_vs.tipo_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
							WHEN 4 THEN		'S-TRNS.ALM'
							ELSE			'S'
						END)
					WHEN 2 THEN
						'S-NC'
					WHEN 3 THEN -- SALIDA CON CONTRATO
						'S-ALQUILER'
				END)
		END) AS nombre_tipo_movimiento,
		
		kardex.claveKardex,
		kardex.tipo_documento_movimiento,
		kardex.estadoKardex,
		kardex.fechaMovimiento,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT prov.id_proveedor AS idPCE
				FROM cp_factura cxp_fact
					INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
				WHERE cxp_fact.id_factura = kardex.id_documento
					AND cxp_fact.id_modulo IN (2))
			WHEN 2 THEN -- ENTRADA
				(SELECT cliente.id AS idPCE FROM cj_cc_cliente cliente
				WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
										WHEN 1 THEN -- ENTRADA CON VALE
											(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
										WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
											(SELECT cxc_nc.idCliente
											FROM cj_cc_notacredito cxc_nc
												INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
											WHERE cxc_nc.idNotaCredito = kardex.id_documento
												AND cxc_nc.idDepartamentoNotaCredito IN (2)
												AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
										WHEN 3 THEN -- ENTRADA CON CONTRATO
											(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
									END))
			WHEN 3 THEN -- VENTA
				(SELECT cliente.id AS idPCE
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT cliente.id AS idPCE
						FROM an_vale_salida vale_sal
							INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
						WHERE vale_sal.id_vale_salida = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT cliente.id AS idPCE
						FROM al_contrato_venta al_contrato
							INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
						WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) AS idPCE,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT CONCAT_WS('-', prov.lrif, prov.rif) AS ciPCE
				FROM cp_factura cxp_fact
					INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
				WHERE cxp_fact.id_factura = kardex.id_documento
					AND cxp_fact.id_modulo IN (2))
			WHEN 2 THEN -- ENTRADA
				(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE FROM cj_cc_cliente cliente
				WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
										WHEN 1 THEN -- ENTRADA CON VALE
											(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
										WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
											(SELECT cxc_nc.idCliente
											FROM cj_cc_notacredito cxc_nc
												INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
											WHERE cxc_nc.idNotaCredito = kardex.id_documento
												AND cxc_nc.idDepartamentoNotaCredito IN (2)
												AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
										WHEN 3 THEN -- ENTRADA CON CONTRATO
											(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
									END))
			WHEN 3 THEN -- VENTA
				(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
						FROM an_vale_salida vale_sal
							INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
						WHERE vale_sal.id_vale_salida = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
						FROM al_contrato_venta al_contrato
							INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
						WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) AS ciPCE,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN
				(SELECT prov.nombre
				FROM cp_factura cxp_fact
					INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
				WHERE cxp_fact.id_factura = kardex.id_documento
					AND cxp_fact.id_modulo IN (2))
			WHEN 2 THEN
				(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE FROM cj_cc_cliente cliente
				WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
										WHEN 1 THEN -- ENTRADA CON VALE
											(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
										WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
											(SELECT cxc_nc.idCliente
											FROM cj_cc_notacredito cxc_nc
												INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
											WHERE cxc_nc.idNotaCredito = kardex.id_documento
												AND cxc_nc.idDepartamentoNotaCredito IN (2)
												AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
										WHEN 3 THEN -- ENTRADA CON CONTRATO
											(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
									END))
			WHEN 3 THEN
				(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
						FROM an_vale_salida vale_sal
							INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
						WHERE vale_sal.id_vale_salida = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
						FROM al_contrato_venta al_contrato
							INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
						WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) AS nombrePCE,
		
		(CASE kardex.tipoMovimiento
			WHEN 2 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT an_ve.tipo_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
				END)
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						(SELECT an_vs.tipo_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
				END)
		END) AS tipo_vale,
		
		kardex.cantidad,
		kardex.precio,
		kardex.costo,
		kardex.costo_cargo,
		kardex.porcentaje_descuento,
		kardex.subtotal_descuento,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN
				uni_fis.precio_compra
			WHEN 2 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						(SELECT cxc_fact_det_vehic.precio_unitario
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
						WHERE cxc_nc.idNotaCredito = kardex.id_documento
							AND cxc_nc.idDepartamentoNotaCredito IN (2)
							AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						uni_fis.precio_compra
				END)
			WHEN 3 THEN
				(SELECT cxc_fact_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
				WHERE cxc_fact_det_vehic.id_factura = kardex.id_documento
					AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						uni_fis.precio_compra
					WHEN 3 THEN -- SALIDA CON CONTRATO
						uni_fis.precio_compra
				END)
		END) AS precio_unidad_dcto,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN
				uni_fis.precio_compra
			WHEN 2 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						(SELECT cxc_fact_det_vehic.costo_compra
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
						WHERE cxc_nc.idNotaCredito =kardex.id_documento
							AND cxc_nc.idDepartamentoNotaCredito IN (2)
							AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						uni_fis.precio_compra
				END)
			WHEN 3 THEN
				(SELECT cxc_fact_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
				WHERE cxc_fact_det_vehic.id_factura = kardex.id_documento
					AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
			WHEN 4 THEN
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN
						uni_fis.precio_compra
					WHEN 2 THEN
						uni_fis.precio_compra
					WHEN 3 THEN -- SALIDA CON CONTRATO
						uni_fis.precio_compra
				END)
		END) AS costo_unidad_dcto
	FROM an_unidad_fisica uni_fis
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_kardex kardex ON (uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas) %s
	ORDER BY kardex.fechaMovimiento ASC, kardex.idKardex ASC", $sqlBusq4);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	$contFilaY2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFilaY2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		$contFilaY2++;
			
		$idEmpresa = $rowDetalle['id_empresa'];
		$idModulo = $rowDetalle['id_modulo'];
			
		switch ($rowDetalle['tipoMovimiento']) {
			case 1 : // COMPRA
				$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
				$precioUnitario = $costoUnitario; break;
			case 2 : // ENTRADA
				switch($rowDetalle['tipo_documento_movimiento']) {
					case 1 : // VALE
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
						break;
					case 2 : // NOTA CREDITO
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
					case 3 : // CONTRATO
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
						break;
				}
				break;
			case 3 : // VENTA
				$costoUnitario = $rowDetalle['costo'];
				$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
			case 4 : // SALIDA
				switch($rowDetalle['tipo_documento_movimiento']) {
					case 1 : // VALE
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
					case 2 : // NOTA CREDITO
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = $costoUnitario;
						break;
					case 3 : // CONTRATO
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
				}
				break;
		}
		
		if ($rowDetalle['estadoKardex'] == 0) {
			$totalEntrada += $rowDetalle['cantidad'];
			$totalValorEntradaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
			$totalValorEntradaCosto += $rowDetalle['cantidad'] * $costoUnitario;
			$entradaSalida += $rowDetalle['cantidad'];
		} else if ($rowDetalle['estadoKardex'] == 1) {
			$totalSalida += $rowDetalle['cantidad'];
			$totalValorSalidaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
			$totalValorSalidaCosto += $rowDetalle['cantidad'] * $costoUnitario;
			$entradaSalida -= $rowDetalle['cantidad'];
		}
		
		$imgInterAlmacen = ($rowDetalle['nombre_tipo_movimiento'] == "E-TRNS.ALN" || $rowDetalle['nombre_tipo_movimiento'] == "S-TRNS.ALN") ? "TRNS.ALN" : " ";
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, $contFilaY2); //$rowDetalle['idKardex']
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, $imgInterAlmacen);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, date(spanDateFormat,strtotime($rowDetalle['fechaMovimiento'])));
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFilaY, utf8_encode($rowDetalle['nombre_empresa']));
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, utf8_encode($rowDetalle['serial_carroceria']));
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, utf8_encode($rowDetalle['serial_motor']));
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, utf8_encode($rowDetalle['nombre_tipo_movimiento']));
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, utf8_encode($rowDetalle['nombre_modulo']));
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY, utf8_encode($rowDetalle['numero_documento']));
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFilaY, utf8_encode($rowDetalle['idPCE']));
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFilaY, utf8_encode($rowDetalle['ciPCE']));
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFilaY, utf8_encode($rowDetalle['nombrePCE']));
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFilaY, $rowDetalle['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, $rowDetalle['id_articulo_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $entradaSalida);
		$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $precioUnitario);
		$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, $costoUnitario);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":Q".$contFilaY)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
	$ultimo = $contFilaY;
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Totales ".$row['nom_uni_bas'].":");
	
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "E #:");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $totalEntrada);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalValorEntradaPrecio);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, $totalValorEntradaCosto);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":I".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY.":"."Q".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "S #:");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, $totalSalida);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, $totalValorSalidaPrecio);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, $totalValorSalidaCosto);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":I".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY.":"."Q".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFilaY, "Valor en Inventario:");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFilaY, ($totalEntrada - $totalSalida));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFilaY, ($totalValorEntradaPrecio - $totalValorSalidaPrecio));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFilaY, ($totalValorEntradaCosto - $totalValorSalidaCosto));
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFilaY.":I".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFilaY.":"."Q".$contFilaY)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":Q".$ultimo);
	
	$arrayResumenInventario[] = array(
		"nom_uni_bas" => $row['nom_uni_bas'],
		"total_entrada" => $totalEntrada,
		"total_valor_entrada_precio" => $totalValorEntradaPrecio,
		"total_valor_entrada_costo" => $totalValorEntradaCosto,
		"total_salida" => $totalSalida,
		"total_valor_salida_precio" => $totalValorSalidaPrecio,
		"total_valor_salida_costo" => $totalValorSalidaCosto);
	
	if ($_GET['lstOrientacionExcel'] == 1 || ($_GET['lstOrientacionExcel'] == 2 && $contFila == $totalRows)) {
		cabeceraExcel($objPHPExcel, $idEmpresa, "Q");
	
		$tituloDcto = ($_GET['lstOrientacionExcel'] == 1) ? $row['nom_uni_bas'] : "Listado Kardex ";
		$tituloHoja = ($_GET['lstOrientacionExcel'] == 1) ? "Kardex ".$row['nom_uni_bas'] : "Listado Kardex";
		$tituloHoja .= " (".$valCadBusq[1]." al ".$valCadBusq[2].")";
		$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloHoja);
		$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
		$objPHPExcel->getActiveSheet()->mergeCells("A7:P7");
		
		//Titulo del libro y seguridad
		$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
		$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
		$objPHPExcel->getSecurity()->setLockWindows(true);
		$objPHPExcel->getSecurity()->setLockStructure(true);
		
		$nroHoja++;
	} else {
		cabeceraExcel($objPHPExcel, $idEmpresa, "Q", false);
		
		$contFilaY++;
		$contFilaY++;
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "");
	}
}

if (isset($arrayResumenInventario)) {
	$objPHPExcel->createSheet(NULL, $nroHoja);
	$contFilaY = 0;
	
	$objPHPExcel->setActiveSheetIndex($nroHoja);
	
	$contFilaY++;
	$primero = $contFilaY;
	
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, "Entrada");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, "Salida");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "Disponible");
	
	$objPHPExcel->getActiveSheet()->mergeCells("B".$contFilaY.":D".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("E".$contFilaY.":G".$contFilaY);
	$objPHPExcel->getActiveSheet()->mergeCells("H".$contFilaY.":I".$contFilaY);
	
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFilaY.":I".$contFilaY)->applyFromArray($styleArrayColumna);
	
	$contFilaY++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, "Unidad Básica");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, "Cant. Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, "Valor Precio Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFilaY, "Valor Costo Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, "Cant. Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, "Valor Precio Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, "Valor Costo Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY, "Cant. Unid.");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY, "Valor Costo Unid.");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":I".$contFilaY)->applyFromArray($styleArrayColumna);
	
	$contFilaY2 = 0;
	foreach ($arrayResumenInventario as $indiceResumenInventario => $valorResumenInventario) {
		$clase = (fmod($contFilaY2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFilaY++;
		$contFilaY2++;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFilaY, utf8_encode($arrayResumenInventario[$indiceResumenInventario]['nom_uni_bas']));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFilaY, $arrayResumenInventario[$indiceResumenInventario]['total_entrada']);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFilaY, $arrayResumenInventario[$indiceResumenInventario]['total_valor_entrada_precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFilaY, $arrayResumenInventario[$indiceResumenInventario]['total_valor_entrada_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFilaY, $arrayResumenInventario[$indiceResumenInventario]['total_salida']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFilaY, $arrayResumenInventario[$indiceResumenInventario]['total_valor_salida_precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFilaY, $arrayResumenInventario[$indiceResumenInventario]['total_valor_salida_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFilaY,
			($arrayResumenInventario[$indiceResumenInventario]['total_entrada']
				- $arrayResumenInventario[$indiceResumenInventario]['total_salida']));
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFilaY,
			($arrayResumenInventario[$indiceResumenInventario]['total_valor_entrada_costo']
				- $arrayResumenInventario[$indiceResumenInventario]['total_valor_salida_costo']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY.":I".$contFilaY)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFilaY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFilaY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	}
	
	cabeceraExcel($objPHPExcel, $idEmpresa, "I");

	$tituloDcto = "Resúmen Kardex";
	$tituloHoja = "Resúmen Kardex";
	$tituloHoja .= " (".$valCadBusq[1]." al ".$valCadBusq[2].")";
	$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloHoja);
	$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A7:I7");
	
	//Titulo del libro y seguridad
	$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
	$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
	$objPHPExcel->getSecurity()->setLockWindows(true);
	$objPHPExcel->getSecurity()->setLockStructure(true);
}

$tituloDcto = "ERP KARDEX";
$objPHPExcel->setActiveSheetIndex(0);
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