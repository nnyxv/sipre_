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

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	for ($cont = 1; $cont <= 24; $cont++) {
		$lstMesesSinRotacion[$cont] = $cont;
	}
	foreach ($lstMesesSinRotacion as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[1]))) {
			$arrayMesesSinRotacion[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Meses Sin Rotación: ".((isset($arrayMesesSinRotacion)) ? implode(", ", $arrayMesesSinRotacion) : "");
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	foreach (explode(",", $valCadBusq[2]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM iv_tipos_articulos WHERE id_tipo_articulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayTipoArticulo[] = $row['descripcion'];
		}
	}
	$arrayCriterioBusqueda[] = "Tipo de Artículo: ".((isset($arrayTipoArticulo)) ? implode(", ", $arrayTipoArticulo) : "");
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$lstVerClasificacion = array("A" => "A", "B" => "B", "C" => "C", "D" => "D", "E" => "E", "F" => "F");
	foreach ($lstVerClasificacion as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayVerClasificacion[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Clasificación: ".((isset($arrayVerClasificacion)) ? implode(", ", $arrayVerClasificacion) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$lstTipoMovimiento = array(1 => "Compra", 2 => "Entrada", 3 => "Venta", 4 => "Salida");
	foreach ($lstTipoMovimiento as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[4]))) {
			$arrayTipoMovimiento[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Tipo Mov.: ".((isset($arrayTipoMovimiento)) ? implode(", ", $arrayTipoMovimiento) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$lstSaldoArt = array(3 => "Disponible", 4 => "No Disponible", 5 => "Reservada");
	foreach ($lstSaldoArt as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[5]))) {
			$arraySaldoArt[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Saldo Artículo: ".((isset($arraySaldoArt)) ? implode(", ", $arraySaldoArt) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$arrayCriterioBusqueda[] = "Código: ".$valCadBusq[6];
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[7];
}

////////// CRITERIO DE BUSQUEDA //////////
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(estatus_articulo_almacen = 1
OR (estatus_articulo_almacen IS NULL AND existencia > 0)
OR (estatus_articulo_almacen IS NULL AND cantidad_reservada > 0))");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.id_casilla IS NOT NULL");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(((SELECT
			DATE(kardex.fecha_movimiento) AS fecha_movimiento
		FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
			AND (CASE kardex.tipo_movimiento
					WHEN 1 THEN
						(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
						WHERE cxp_fact.id_factura = kardex.id_documento)
					WHEN 2 THEN
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN
								(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
								WHERE iv_ve.id_vale_entrada = kardex.id_documento)
							WHEN 2 THEN
								(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
								WHERE cxc_nc.idDepartamentoNotaCredito = 0
									AND cxc_nc.idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN
						(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = kardex.id_documento)
					WHEN 4 THEN
						(CASE kardex.id_modulo
							WHEN 0 THEN
								(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
								WHERE iv_vs.id_vale_salida = kardex.id_documento)
							WHEN 1 THEN
								(SELECT sa_vs.id_empresa AS id_empresa FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = kardex.id_documento)
						END)
			END) = vw_iv_art_emp_ubic.id_empresa
			AND kardex.tipo_movimiento IN (%s)
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
		LIMIT 1) < DATE_SUB(CURDATE(), INTERVAL %s MONTH))
	OR (SELECT
			DATE(kardex.fecha_movimiento) AS fecha_movimiento
		FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
			AND (CASE kardex.tipo_movimiento
					WHEN 1 THEN
						(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
						WHERE cxp_fact.id_factura = kardex.id_documento)
					WHEN 2 THEN
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN
								(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
								WHERE iv_ve.id_vale_entrada = kardex.id_documento)
							WHEN 2 THEN
								(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
								WHERE cxc_nc.idDepartamentoNotaCredito = 0
									AND cxc_nc.idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN
						(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = kardex.id_documento)
					WHEN 4 THEN
						(CASE kardex.id_modulo
							WHEN 0 THEN
								(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
								WHERE iv_vs.id_vale_salida = kardex.id_documento)
							WHEN 1 THEN
								(SELECT sa_vs.id_empresa AS id_empresa FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = kardex.id_documento)
						END)
			END) = vw_iv_art_emp_ubic.id_empresa
			AND kardex.tipo_movimiento IN (%s)
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
		LIMIT 1) IS NULL)",
		valTpDato((($valCadBusq[4] != "-1" && $valCadBusq[4] != "") ? $valCadBusq[4] : "1,2,3,4"), "campo"),
		valTpDato($valCadBusq[1], "campo"),
		valTpDato((($valCadBusq[4] != "-1" && $valCadBusq[4] != "") ? $valCadBusq[4] : "1,2,3,4"), "campo"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
		valTpDato($valCadBusq[2], "campo"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.clasificacion IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
}

if (in_array(3, explode(",",$valCadBusq[5]))
|| in_array(4, explode(",",$valCadBusq[5]))
|| in_array(5, explode(",",$valCadBusq[5]))) {
	$arrayBusq = "";
	if (in_array(3, explode(",",$valCadBusq[5]))) {
		$arrayBusq[] = sprintf("vw_iv_art_emp_ubic.cantidad_disponible_fisica > 0");
	}
	if (in_array(4, explode(",",$valCadBusq[5]))) {
		$arrayBusq[] = sprintf("vw_iv_art_emp_ubic.cantidad_disponible_fisica <= 0");
	}
	if (in_array(5, explode(",",$valCadBusq[5]))) {
		$arrayBusq[] = sprintf("vw_iv_art_emp_ubic.cantidad_reservada > 0");
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[6], "text"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_art_emp_ubic.id_articulo = %s
	OR vw_iv_art_emp_ubic.descripcion LIKE %s
	OR vw_iv_art_emp_ubic.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[7], "int"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT vw_iv_art_emp_ubic.*,
	
	(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
	WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
	
	art.fecha_registro,
	
	PERIOD_DIFF(DATE_FORMAT(NOW(), '%s'), DATE_FORMAT(art.fecha_registro, '%s')) AS meses_registro,
	
	(SELECT
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp
					INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_emp_ubic.id_empresa)
			WHEN 1 THEN	art_costo.costo
			WHEN 2 THEN	art_costo.costo_promedio
			WHEN 3 THEN
				IF((SELECT
						(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
							+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
							- SUM(art_costo2.cantidad_salida * art_costo2.costo))
						/ (SUM(art_costo2.cantidad_inicio)
							+ SUM(art_costo2.cantidad_entrada)
							- SUM(art_costo2.cantidad_salida))
					FROM iv_articulos_costos art_costo2
					WHERE art_costo2.id_articulo = vw_iv_art_emp_ubic.id_articulo
						AND art_costo2.id_empresa = vw_iv_art_emp_ubic.id_empresa
						AND art_costo2.estatus = 1
					ORDER BY art_costo2.fecha_registro DESC), (SELECT
																	(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
																		+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
																		- SUM(art_costo2.cantidad_salida * art_costo2.costo))
																	/ (SUM(art_costo2.cantidad_inicio)
																		+ SUM(art_costo2.cantidad_entrada)
																		- SUM(art_costo2.cantidad_salida))
																FROM iv_articulos_costos art_costo2
																WHERE art_costo2.id_articulo = vw_iv_art_emp_ubic.id_articulo
																	AND art_costo2.id_empresa = vw_iv_art_emp_ubic.id_empresa
																	AND art_costo2.estatus = 1
																ORDER BY art_costo2.fecha_registro DESC), art_costo.costo_promedio)
		END)
	FROM iv_articulos_costos art_costo
	WHERE art_costo.id_articulo = vw_iv_art_emp_ubic.id_articulo
		AND art_costo.id_empresa = vw_iv_art_emp_ubic.id_empresa
	ORDER BY art_costo.id_articulo_costo
	DESC LIMIT 1) AS costo,
	
	(SELECT
		DATE(kardex.fecha_movimiento) AS fecha_movimiento
	FROM iv_kardex kardex
	WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
		AND (CASE kardex.tipo_movimiento
				WHEN 1 THEN
					(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
					WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
							WHERE iv_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN
							(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idDepartamentoNotaCredito = 0
								AND cxc_nc.idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN
					(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN
					(CASE kardex.id_modulo
						WHEN 0 THEN
							(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
							WHERE iv_vs.id_vale_salida = kardex.id_documento)
						WHEN 1 THEN
							(SELECT sa_vs.id_empresa AS id_empresa FROM sa_vale_salida sa_vs
							WHERE sa_vs.id_vale_salida = kardex.id_documento)
					END)
			END) = vw_iv_art_emp_ubic.id_empresa
		AND kardex.tipo_movimiento IN (%s)
	ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
	LIMIT 1) AS fecha_movimiento,
	
	(SELECT
		(CASE kardex.tipo_movimiento
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END)
	FROM iv_kardex kardex
	WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
		AND (CASE kardex.tipo_movimiento
				WHEN 1 THEN
					(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
					WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
							WHERE iv_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN
							(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idDepartamentoNotaCredito = 0
								AND cxc_nc.idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN
					(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN
					(CASE kardex.id_modulo
						WHEN 0 THEN
							(SELECT iv_sa.id_empresa AS id_empresa FROM iv_vale_salida iv_sa
							WHERE iv_sa.id_vale_salida = kardex.id_documento)
						WHEN 1 THEN
							(SELECT sa_vs.id_empresa AS id_empresa FROM sa_vale_salida sa_vs
							WHERE sa_vs.id_vale_salida = kardex.id_documento)
					END)
			END) = vw_iv_art_emp_ubic.id_empresa
		AND kardex.tipo_movimiento IN (%s)
	ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
	LIMIT 1) AS tipo_movimiento,
	
	PERIOD_DIFF(DATE_FORMAT(NOW(), '%s'), DATE_FORMAT((SELECT
														DATE(kardex.fecha_movimiento) AS fecha_movimiento
													FROM iv_kardex kardex
													WHERE kardex.id_articulo = vw_iv_art_emp_ubic.id_articulo
														AND (CASE kardex.tipo_movimiento
																WHEN 1 THEN
																	(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
																	WHERE cxp_fact.id_factura = kardex.id_documento)
																WHEN 2 THEN
																	(CASE kardex.tipo_documento_movimiento
																		WHEN 1 THEN
																			(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
																			WHERE iv_ve.id_vale_entrada = kardex.id_documento)
																		WHEN 2 THEN
																			(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
																			WHERE cxc_nc.idDepartamentoNotaCredito = 0
																				AND cxc_nc.idNotaCredito = kardex.id_documento)
																	END)
																WHEN 3 THEN
																	(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
																	WHERE cxc_fact.idFactura = kardex.id_documento)
																WHEN 4 THEN
																	(CASE kardex.id_modulo
																		WHEN 0 THEN
																			(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
																			WHERE iv_vs.id_vale_salida = kardex.id_documento)
																		WHEN 1 THEN
																			(SELECT sa_vs.id_empresa AS id_empresa FROM sa_vale_salida sa_vs
																			WHERE sa_vs.id_vale_salida = kardex.id_documento)
																	END)
															END) = vw_iv_art_emp_ubic.id_empresa
														AND kardex.tipo_movimiento IN (%s)
													ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) DESC
													LIMIT 1), '%s')) AS meses_sin_rotacion
	
FROM vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic
	INNER JOIN iv_articulos art ON (vw_iv_art_emp_ubic.id_articulo = art.id_articulo) %s
ORDER BY CONCAT(descripcion_almacen, ubicacion) ASC",
	"%Y%m",
	"%Y%m",
	valTpDato((($valCadBusq[4] != "-1" && $valCadBusq[4] != "") ? $valCadBusq[4] : "1,2,3,4"), "campo"),
	valTpDato((($valCadBusq[4] != "-1" && $valCadBusq[4] != "") ? $valCadBusq[4] : "1,2,3,4"), "campo"),
	"%Y%m",
	valTpDato((($valCadBusq[4] != "-1" && $valCadBusq[4] != "") ? $valCadBusq[4] : "1,2,3,4"), "campo"),
	"%Y%m",
	$sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Código Prov.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Clasif.");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Creación");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Meses de Creación");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Fecha Ult. Mov");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Tipo Mov");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Meses Sin Rotacion");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Ubicación");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Costo");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Unid. Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Valor Disponible");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Unid. Reservada");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Valor Reservada");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":R".$contFila)->applyFromArray($styleArrayColumna);

$objPHPExcel->getActiveSheet()->mergeCells("K".$contFila.":M".$contFila);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	$costoUnit = $row['costo'];
	
	$cantKardex = 0;
	$subTotalKardex = $cantKardex * $costoUnit;
	
	$cantDisponible = $row['cantidad_disponible_fisica']; // SALDO - RESERVADAS
	$subTotalDisponible = $cantDisponible * $costoUnit;
	
	$cantReservada = $row['cantidad_reservada'];
	$subTotalReservada = $cantReservada * $costoUnit;
	
	$cantDiferencia = $row['existencia'] - 0;
	$subTotalDiferencia = $cantDiferencia * $costoUnit;
	
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($row['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $row['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("C".$contFila, $row['codigo_articulo_prov'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['unidad'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['clasificacion'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, (($row['fecha_registro']) ? date(spanDateFormat, strtotime($row['fecha_registro'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['meses_registro']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($row['fecha_movimiento']) ? date(spanDateFormat, strtotime($row['fecha_movimiento'])) : ""));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['tipo_movimiento'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['meses_sin_rotacion']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['descripcion_almacen'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, str_replace("-[]", "", $row['ubicacion']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("M".$contFila, (($row['estatus_articulo_almacen'] == 1) ? "" : "(Inactiva)"), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $costoUnit);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $cantDisponible);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $subTotalDisponible);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $cantReservada);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $subTotalReservada);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":R".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.cAbrevMoneda.'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":R".$ultimo);

for ($col = "A"; $col != "R"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "R", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Artículos Sin Rotación";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:R7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:R9");
}

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