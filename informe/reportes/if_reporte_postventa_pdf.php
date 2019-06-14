<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Legal');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
//$pdf->nombreRegistrado = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];
$anoCierre = $valCadBusq[1];
$nroDecimales = ($_GET['lstDecimalPDF'] == 1) ? 2 : 0;

$sqlBusq = " ";
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
														FROM pg_empresa emp
															LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
														ORDER BY emp.id_empresa_padre ASC
														LIMIT 1)");
}

// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 300 AND config_emp.status = 1 %s;", $sqlBusq);
$rsConfig300 = mysql_query($queryConfig300);
if (!$rsConfig300) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig300 = mysql_num_rows($rsConfig300);
$rowConfig300 = mysql_fetch_assoc($rsConfig300);

// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Servicios))
$queryConfig302 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 302 AND config_emp.status = 1 %s;", $sqlBusq);
$rsConfig302 = mysql_query($queryConfig302);
if (!$rsConfig302) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig302 = mysql_num_rows($rsConfig302);
$rowConfig302 = mysql_fetch_assoc($rsConfig302);

// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Latoneria y Pintura))
$queryConfig303 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 303 AND config_emp.status = 1 %s;", $sqlBusq);
$rsConfig303 = mysql_query($queryConfig303);
if (!$rsConfig303) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig303 = mysql_num_rows($rsConfig303);
$rowConfig303 = mysql_fetch_assoc($rsConfig303);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(recepcion.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = recepcion.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(recepcion.fecha_entrada) = %s
		AND YEAR(recepcion.fecha_entrada) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	// ENTRADA DE VEHICULOS
	$queryValeRecepcion = sprintf("SELECT tipo_vale.*
	FROM sa_recepcion recepcion
		LEFT JOIN sa_tipo_vale tipo_vale ON (recepcion.id_tipo_vale = tipo_vale.id_tipo_vale) %s", $sqlBusq);
	$rsValeRecepcion = mysql_query($queryValeRecepcion);
	if (!$rsValeRecepcion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalTipoOrdenAbierta = 0;
	while ($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
		$arrayValeRecepcion[$mesCierre][$rowValeRecepcion['id_tipo_vale']] = array(
			$rowValeRecepcion['descripcion'],
			$arrayValeRecepcion[$mesCierre][$rowValeRecepcion['id_tipo_vale']][1] + 1);
	}
}

// MANO DE OBRA SERVICIOS
$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (1);");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$cont = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$arrayMODet = NULL;
	
	$arrayMODet[0] = $row['descripcion_operador'];
	
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// SOLO APLICA PARA LAS MANO DE OBRA
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("operador = %s
		AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
			valTpDato($row['id_operador'],"int"));
			
		$queryMO = sprintf("SELECT
			operador,
			SUM(total_tempario_orden) AS total_tempario_orden
		FROM (
			SELECT a.operador,
				(CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END) AS total_tempario_orden
			FROM sa_v_informe_final_tempario a %s %s
					
			UNION ALL
					
			SELECT
				a.operador,
				(-1) * (CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END) AS total_tempario_orden
			FROM sa_v_informe_final_tempario_dev a %s %s
						
			UNION ALL
			
			SELECT
				a.operador,
				(CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END) AS total_tempario_orden
			FROM sa_v_vale_informe_final_tempario a %s %s
			
			UNION ALL
			
			SELECT
				a.operador,
				(-1) * (CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END) AS total_tempario_orden
				
			FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
		GROUP BY operador",
			$sqlBusq, $sqlBusq2,
			$sqlBusq, $sqlBusq2,
			$sqlBusq, $sqlBusq2,
			$sqlBusq, $sqlBusq2);
		$rsMO = mysql_query($queryMO);
		if (!$rsMO) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowMO = mysql_fetch_assoc($rsMO);
		
		$arrayMODet[$mesCierre] = round($rowMO['total_tempario_orden'],2);
	}
	
	$arrayMOServ[] = $arrayMODet;
}

// VENTA DE REPUESTOS POR SERVICIOS
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig302['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryRep = sprintf("SELECT
		SUM(total_repuesto_orden) AS total_repuesto_orden
	FROM (
		SELECT
			(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
		FROM sa_v_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
		FROM sa_v_informe_final_repuesto_dev a %s
		
		UNION ALL
		
		SELECT
			(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);
		
	$arrayRepServ[$mesCierre] = round($rowRep['total_repuesto_orden'],2);
}

// VENTA DE TOT POR SERVICIOS
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig302['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryTot = sprintf("SELECT
		SUM(total_tot_orden) AS total_tot_orden
	FROM (
		SELECT
			(monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
		FROM sa_v_informe_final_tot a %s
			
		UNION ALL
		
		SELECT
			(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
		FROM sa_v_informe_final_tot_dev a %s
		
		UNION ALL
		
		SELECT
			(monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
		FROM sa_v_vale_informe_final_tot a %s
		
		UNION ALL
		
		SELECT
			(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
		FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsTot = mysql_query($queryTot);
	if (!$rsTot) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowTot = mysql_fetch_assoc($rsTot);
	
	$arrayTotServ[$mesCierre] = round($rowTot['total_tot_orden'],2);
}

// VENTA DE NOTAS POR SERVICIOS
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig302['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryNota = sprintf("SELECT
		SUM(precio) AS total_notas_orden
	FROM (
		SELECT
			precio
		FROM sa_v_informe_final_notas a %s
			
		UNION ALL
		
		SELECT
			(-1) * precio
		FROM sa_v_informe_final_notas_dev a %s
		
		UNION ALL
		
		SELECT
			precio
		FROM sa_v_vale_informe_final_notas a %s
		
		UNION ALL
		
		SELECT
			(-1) * precio
		FROM sa_v_vale_informe_final_notas_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsNota = mysql_query($queryNota);
	if (!$rsNota) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNota = mysql_fetch_assoc($rsNota);
	
	$arrayNotasServ[$mesCierre] = round($rowNota['total_notas_orden'],2);
}

// COSTO DE VENTAS REPUESTOS POR SERVICIOS
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig302['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryRep = sprintf("SELECT
		SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
	FROM (
		SELECT
			(costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_informe_final_repuesto_dev a %s
		
		UNION ALL
		
		SELECT
			(costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);
	
	$arrayCostoRepServ[$mesCierre] = round($rowRep['total_costo_repuesto_orden'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTA DE REPUESTO
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
	AND fact_vent.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND nota_cred.aplicaLibros = 1
	AND nota_cred.estatus_nota_credito = 2");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = fact_vent.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = nota_cred.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$query = sprintf("SELECT
		IFNULL(fact_vent.subtotalFactura,0) - IFNULL(fact_vent.descuentoFactura,0) AS neto
	FROM cj_cc_encabezadofactura fact_vent %s
	
	UNION ALL
	
	SELECT
		(-1) * (IFNULL(nota_cred.subtotalNotaCredito,0) - IFNULL(nota_cred.subtotal_descuento,0)) AS neto
	FROM cj_cc_notacredito nota_cred %s;",
		$sqlBusq,
		$sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalVentaRepuesto = 0;
	while ($rowDetalle = mysql_fetch_assoc($rs)) {
		$totalVentaRepuesto += round($rowDetalle['neto'],2);
	}
	
	$arrayRepMost[$mesCierre] = $totalVentaRepuesto;
}

// COSTO DE VENTAS REPUESTOS
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
	AND fact_vent.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND nota_cred.aplicaLibros = 1
	AND nota_cred.estatus_nota_credito = 2");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = fact_vent.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = nota_cred.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$query = sprintf("SELECT
		(SELECT
			SUM((fact_vent_det.costo_compra * fact_vent_det.cantidad)) AS costo_total
		FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
	FROM cj_cc_encabezadofactura fact_vent %s
		
	UNION ALL
	
	SELECT
		((SELECT
			SUM((nota_cred_det.costo_compra * nota_cred_det.cantidad)) AS costo_total
		FROM cj_cc_nota_credito_detalle nota_cred_det
		WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) * (-1)) AS neto
	FROM cj_cc_notacredito nota_cred %s;",
		$sqlBusq,
		$sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalCostoRepMost = 0;
	while ($rowDetalle = mysql_fetch_assoc($rs)) {
		$totalCostoRepMost += round($rowDetalle['neto'],2);
	}
	
	$arrayCostoRepMost[$mesCierre] = $totalCostoRepMost;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// LATONERÍA Y PINTURA
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MANO DE OBRA LATONERÍA Y PINTURA
$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (2,3);");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$cont = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$arrayMODet = NULL;
	$arrayCostoMODet = NULL;
	
	$arrayMODet[0] = $row['descripcion_operador'];
	$arrayCostoMODet[0] = "costo";
	
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// SOLO APLICA PARA LAS MANO DE OBRA
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("operador = %s
		AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
			valTpDato($row['id_operador'],"int"));
		
		$queryMO = sprintf("SELECT
			operador,
			SUM(total_tempario_orden) AS total_tempario_orden,
			SUM(total_costo_tempario_orden) AS total_costo_tempario_orden
		FROM (
			SELECT a.operador,
				(CASE a.id_modo
					WHEN 1 THEN
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN
						a.precio
				END) AS total_tempario_orden,
				(CASE a.id_modo
				WHEN 1 THEN -- UT
					a.costo
				WHEN 2 THEN -- PRECIO
					a.costo
			END) AS total_costo_tempario_orden
			FROM sa_v_informe_final_tempario a %s %s
					
			UNION ALL
					
			SELECT
				a.operador,
				(-1) * (CASE a.id_modo
					WHEN 1 THEN
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN
						a.precio
				END) AS total_tempario_orden,
				(-1) * (CASE a.id_modo
					WHEN 1 THEN -- UT
						a.costo
					WHEN 2 THEN -- PRECIO
						a.costo
				END) AS total_costo_tempario_orden
			FROM sa_v_informe_final_tempario_dev a %s %s
						
			UNION ALL
			
			SELECT
				a.operador,
				(CASE a.id_modo
					WHEN 1 THEN
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN
						a.precio
				END) AS total_tempario_orden,
				(CASE a.id_modo
					WHEN 1 THEN -- UT
						a.costo
					WHEN 2 THEN -- PRECIO
						a.costo
				END) AS total_costo_tempario_orden
			FROM sa_v_vale_informe_final_tempario a %s %s
						
			UNION ALL
			
			SELECT
				a.operador,
				(-1) * (CASE a.id_modo
					WHEN 1 THEN
						(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
					WHEN 2 THEN
						a.precio
				END) AS total_tempario_orden,
				(-1) * (CASE a.id_modo
					WHEN 1 THEN -- UT
						a.costo
					WHEN 2 THEN -- PRECIO
						a.costo
				END) AS total_costo_tempario_orden
			FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
		GROUP BY operador",
			$sqlBusq, $sqlBusq2,
			$sqlBusq, $sqlBusq2,
			$sqlBusq, $sqlBusq2,
			$sqlBusq, $sqlBusq2);
		$rsMO = mysql_query($queryMO);
		if (!$rsMO) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowMO = mysql_fetch_assoc($rsMO);
		
		$arrayMODet[$mesCierre] = round($rowMO['total_tempario_orden'],2);
		
		// COSTO MANO DE OBRA (Incluye Materiales) NO DISCRIMINA POR OPERADOR
		$arrayCostoMOLatPint[$mesCierre] += round($rowMO['total_costo_tempario_orden'],2);
	}
	
	$arrayMOLatPint[] = $arrayMODet;
}

// VENTA DE REPUESTOS POR LATONERÍA Y PINTURA
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig303['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig303['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryRep = sprintf("SELECT
		SUM(total_repuesto_orden) AS total_repuesto_orden
	FROM (
		SELECT
			(precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
		FROM sa_v_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
		FROM sa_v_informe_final_repuesto_dev a %s
		
		UNION ALL
		
		SELECT
			(precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);
	
	$arrayRepLatPint[$mesCierre] = round($rowRep['total_repuesto_orden'],2);
}

// VENTA DE TOT POR LATONERÍA Y PINTURA
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig303['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig303['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryTot = sprintf("SELECT
		SUM(total_tot_orden) AS total_tot_orden
	FROM (
		SELECT
			monto_total + ((porcentaje_tot * monto_total) / 100) AS total_tot_orden
		FROM sa_v_informe_final_tot a %s
			
		UNION ALL
		
		SELECT
			(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
		FROM sa_v_informe_final_tot_dev a %s
		
		UNION ALL
		
		SELECT
			monto_total + ((porcentaje_tot * monto_total) / 100) AS total_tot_orden
		FROM sa_v_vale_informe_final_tot a %s
		
		UNION ALL
		
		SELECT
			(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
		FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsTot = mysql_query($queryTot);
	if (!$rsTot) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowTot = mysql_fetch_assoc($rsTot);
	
	$arrayTotLatPint[$mesCierre] = round($rowTot['total_tot_orden'],2);
}

// COSTO DE VENTAS REPUESTOS POR LATONERÍA Y PINTURA
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig303['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig303['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	$queryRep = sprintf("SELECT
		SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
	FROM (
		SELECT
			(costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_informe_final_repuesto_dev a %s
		
		UNION ALL
		
		SELECT
			(costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT
			(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
		FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);
	
	$arrayCostoRepLatPint[$mesCierre] = round($rowRep['total_costo_repuesto_orden'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// INVENTARIO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$arrayClasifInvDet[0] = "A";
$arrayClasifInv[] = $arrayClasifInvDet;
$arrayClasifInvDet[0] = "B";
$arrayClasifInv[] = $arrayClasifInvDet;
$arrayClasifInvDet[0] = "C";
$arrayClasifInv[] = $arrayClasifInvDet;
$arrayClasifInvDet[0] = "D";
$arrayClasifInv[] = $arrayClasifInvDet;
$arrayClasifInvDet[0] = "E";
$arrayClasifInv[] = $arrayClasifInvDet;
$arrayClasifInvDet[0] = "F";
$arrayClasifInv[] = $arrayClasifInvDet;

for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayClasifInv)) {
		foreach ($arrayClasifInv as $indice => $valor) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq = $cond.sprintf("(cierre_mens.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cierre_mens.id_empresa))",
				valTpDato($idEmpresa,"int"),
				valTpDato($idEmpresa,"int"));
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cierre_mens.mes = %s
			AND cierre_mens.ano = %s)",
				valTpDato($mesCierre,"int"),
				valTpDato($anoCierre,"int"));
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
			OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
				valTpDato($arrayClasifInv[$indice][0],"text"), valTpDato($arrayClasifInv[$indice][0],"text"),
				valTpDato($arrayClasifInv[$indice][0],"text"));

			$queryDetalle = sprintf("SELECT
					analisis_inv_det.id_analisis_inventario,
					analisis_inv_det.cantidad_existencia,
					analisis_inv_det.cantidad_disponible_logica,
					analisis_inv_det.cantidad_disponible_fisica,
					analisis_inv_det.costo,
					(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia)	 AS costo_total,
					(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
					analisis_inv_det.promedio_diario,
					analisis_inv_det.promedio_mensual,
					(analisis_inv_det.promedio_mensual * 2) AS inventario_recomendado,
					(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * 2)) AS sobre_stock,
					((analisis_inv_det.promedio_mensual * 2) - analisis_inv_det.cantidad_existencia) AS sugerido,
					analisis_inv_det.clasificacion
				FROM iv_analisis_inventario_detalle analisis_inv_det
					INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
					INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
					INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s", $sqlBusq);
				//return $objResponse->alert($queryDetalle);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$nroArt = 0;
			$exist = 0;
			$costoInv = 0;
			$promVenta = 0;
			$mesesExist = 0;
			while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
				$costoInv += $rowDetalle['costo_total'];
			}
			
			$arrayClasifInv[$indice][$mesCierre] = $costoInv;
		}
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(978, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE POST-VENTA (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("PRODUCCIÓN TALLER (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 24, " ", STR_PAD_BOTH),$textColor);
$posX = 120;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($mes[$mesCierre]),0,13)), 13, " ", STR_PAD_BOTH),$textColor);
	$posX += 65;
}
imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode("TOTALES AÑO"),0,18)), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
		
// ENTRADA DE VEHICULOS
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Entrada de Vehículos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
$posX = 120;
$totalValeRecepcion = 0;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(formatoNumero(round($arrayValeRecepcion[$mesCierre][1][1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	$posX += 65;
	$totalValeRecepcion += $arrayValeRecepcion[$mesCierre][1][1];
}
imagestring($img,1,900,$posY,str_pad(formatoNumero(round($totalValeRecepcion,2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
// MANO DE OBRA SERVICIOS
if (isset($arrayMOServ)) {
	foreach ($arrayMOServ as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayMOServ[$indice][0],0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayMOServ[$indice][12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayMOServ[$indice]),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
	}
}
		
// VENTA DE REPUESTOS POR SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Repuestos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayRepServ[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayRepServ[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayRepServ[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayRepServ[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayRepServ[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayRepServ[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayRepServ[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayRepServ[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayRepServ[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayRepServ[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayRepServ[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayRepServ[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayRepServ),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
// TOT POR SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Trabajos Otros Talleres"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayTotServ[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayTotServ[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayTotServ[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayTotServ[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayTotServ[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayTotServ[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayTotServ[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotServ[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayTotServ[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayTotServ[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayTotServ[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayTotServ[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayTotServ),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
// NOTAS POR SERVICIOS
if ($rowConfig300['valor'] == 1) {
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Notas"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
	imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayNotasServ[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayNotasServ[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayNotasServ[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayNotasServ[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayNotasServ[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayNotasServ[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayNotasServ[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayNotasServ[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayNotasServ[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayNotasServ[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayNotasServ[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayNotasServ[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayNotasServ),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
		
// TOTAL SERVICIOS
$totalMOServ = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
		}
	}
}
$posY += 10; $posX = 55;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Total Servicios"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$totalServicios = $totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre] + $arrayTotServ[$mesCierre];
	$totalServicios += ($rowConfig300['valor'] == 1) ? $arrayNotasServ[$mesCierre] : 0;
	
	$posX += 65;
	imagestring($img,1,$posX,$posY,str_pad(formatoNumero($totalServicios, 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
}
$totalTotalServicios = array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ);
$totalTotalServicios += ($rowConfig300['valor'] == 1) ? array_sum($arrayNotasServ) : 0;
$posX += 65;
imagestring($img,1,$posX,$posY,str_pad(formatoNumero($totalTotalServicios, 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// COSTO DE VENTAS REPUESTOS POR SERVICIOS
$posY += 10; $posX = 55;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Costo de Ventas Repuestos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$posX += 65;
	imagestring($img,1,$posX,$posY,str_pad(formatoNumero($arrayCostoRepServ[$mesCierre], 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
}
$posX += 65;
imagestring($img,1,$posX,$posY,str_pad(formatoNumero(array_sum($arrayCostoRepServ), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// UTILIDAD REPUESTOS POR SERVICIOS
$posY += 10; $posX = 55;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Rep."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$posX += 65;
	imagestring($img,1,$posX,$posY,str_pad(formatoNumero($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre], 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
}
$posX += 65;
imagestring($img,1,$posX,$posY,str_pad(formatoNumero(array_sum($arrayRepServ) - array_sum($arrayCostoRepServ), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
// UTILIDAD DEPARTAMENTO DE SERVICIOS
$totalMOServ = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
		}
	}
}
$posY += 10; $posX = 55;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Dep."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	$totalUtilidadBrutaDep = $totalMOServ[$mesCierre] + ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]);
	$totalUtilidadBrutaDep += ($rowConfig300['valor'] == 1) ? $arrayNotasServ[$mesCierre] : 0;
	
	$posX += 65;
	imagestring($img,1,$posX,$posY,str_pad(formatoNumero($totalUtilidadBrutaDep, 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
}
$totalTotalUtilidadBrutaDep = array_sum($totalMOServ) + (array_sum($arrayRepServ) - array_sum($arrayCostoRepServ));
$totalTotalUtilidadBrutaDep += ($rowConfig300['valor'] == 1) ? array_sum($arrayNotasServ) : 0;
$posX += 65;
imagestring($img,1,$posX,$posY,str_pad(formatoNumero($totalTotalUtilidadBrutaDep, 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// REPUESTO VS TOTAL POR SERVICIOS
$totalMOServ = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
		}
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Repuestos Vs Total"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round(((($totalMOServ[1] + $arrayRepServ[1]) > 0) ? (($arrayRepServ[1] * 100) / ($totalMOServ[1] + $arrayRepServ[1])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round(((($totalMOServ[2] + $arrayRepServ[2]) > 0) ? (($arrayRepServ[2] * 100) / ($totalMOServ[2] + $arrayRepServ[2])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round(((($totalMOServ[3] + $arrayRepServ[3]) > 0) ? (($arrayRepServ[3] * 100) / ($totalMOServ[3] + $arrayRepServ[3])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round(((($totalMOServ[4] + $arrayRepServ[4]) > 0) ? (($arrayRepServ[4] * 100) / ($totalMOServ[4] + $arrayRepServ[4])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round(((($totalMOServ[5] + $arrayRepServ[5]) > 0) ? (($arrayRepServ[5] * 100) / ($totalMOServ[5] + $arrayRepServ[5])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round(((($totalMOServ[6] + $arrayRepServ[6]) > 0) ? (($arrayRepServ[6] * 100) / ($totalMOServ[6] + $arrayRepServ[6])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round(((($totalMOServ[7] + $arrayRepServ[7]) > 0) ? (($arrayRepServ[7] * 100) / ($totalMOServ[7] + $arrayRepServ[7])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round(((($totalMOServ[8] + $arrayRepServ[8]) > 0) ? (($arrayRepServ[8] * 100) / ($totalMOServ[8] + $arrayRepServ[8])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round(((($totalMOServ[9] + $arrayRepServ[9]) > 0) ? (($arrayRepServ[9] * 100) / ($totalMOServ[9] + $arrayRepServ[9])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round(((($totalMOServ[10] + $arrayRepServ[10]) > 0) ? (($arrayRepServ[10] * 100) / ($totalMOServ[10] + $arrayRepServ[10])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round(((($totalMOServ[11] + $arrayRepServ[11]) > 0) ? (($arrayRepServ[11] * 100) / ($totalMOServ[11] + $arrayRepServ[11])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round(((($totalMOServ[12] + $arrayRepServ[12]) > 0) ? (($arrayRepServ[12] * 100) / ($totalMOServ[12] + $arrayRepServ[12])) : 0),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((((array_sum($totalMOServ) + array_sum($arrayRepServ)) > 0) ? ((array_sum($arrayRepServ) * 100) / (array_sum($totalMOServ) + array_sum($arrayRepServ))) : 0),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("MOSTRADOR (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 24, " ", STR_PAD_BOTH),$textColor);
$posX = 120;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($mes[$mesCierre]),0,13)), 13, " ", STR_PAD_BOTH),$textColor);
	$posX += 65;
}
imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode("TOTALES AÑO"),0,18)), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// VENTA DE REPUESTOS
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Total Repuestos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayRepMost[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayRepMost[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayRepMost[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayRepMost[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayRepMost[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayRepMost[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayRepMost[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayRepMost[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayRepMost[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayRepMost[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayRepMost[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayRepMost[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayRepMost),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
// COSTO DE VENTAS REPUESTOS
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Costo de Ventas Repuestos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayCostoRepMost[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayCostoRepMost),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// UTILIDAD DEPARTAMENTO
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Dep."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round(($arrayRepMost[1] - $arrayCostoRepMost[1]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round(($arrayRepMost[2] - $arrayCostoRepMost[2]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round(($arrayRepMost[3] - $arrayCostoRepMost[3]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round(($arrayRepMost[4] - $arrayCostoRepMost[4]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round(($arrayRepMost[5] - $arrayCostoRepMost[5]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round(($arrayRepMost[6] - $arrayCostoRepMost[6]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round(($arrayRepMost[7] - $arrayCostoRepMost[7]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round(($arrayRepMost[8] - $arrayCostoRepMost[8]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round(($arrayRepMost[9] - $arrayCostoRepMost[9]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round(($arrayRepMost[10] - $arrayCostoRepMost[10]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round(($arrayRepMost[11] - $arrayCostoRepMost[11]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round(($arrayRepMost[12] - $arrayCostoRepMost[12]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((array_sum($arrayRepMost) - array_sum($arrayCostoRepMost)),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// LATONERIA Y PINTURA
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("LATONERIA Y PINTURA (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 24, " ", STR_PAD_BOTH),$textColor);
$posX = 120;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($mes[$mesCierre]),0,13)), 13, " ", STR_PAD_BOTH),$textColor);
	$posX += 65;
}
imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode("TOTALES AÑO"),0,18)), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// ENTRADA DE VEHICULOS
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Entrada de Vehículos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
$posX = 120;
$totalValeRecepcion = 0;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(formatoNumero(round($arrayValeRecepcion[$mesCierre][2][1] + $arrayValeRecepcion[$mesCierre][3][1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
	$posX += 65;
	$totalValeRecepcion += $arrayValeRecepcion[$mesCierre][2][1] + $arrayValeRecepcion[$mesCierre][3][1];
}
imagestring($img,1,900,$posY,str_pad(formatoNumero(round($totalValeRecepcion,2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
// MANO DE OBRA LATONERÍA Y PINTURA
if (isset($arrayMOLatPint)) {
	foreach ($arrayMOLatPint as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Mano de Obra ".$arrayMOLatPint[$indice][0]),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayMOLatPint[$indice][12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayMOLatPint[$indice]),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
	}
}

// VENTA DE REPUESTOS POR LATONERÍA Y PINTURA
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Repuestos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayRepLatPint[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayRepLatPint[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayRepLatPint[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayRepLatPint[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayRepLatPint[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayRepLatPint[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayRepLatPint[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayRepLatPint[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayRepLatPint[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayRepLatPint[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayRepLatPint[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayRepLatPint[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayRepLatPint),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// TOT POR LATONERÍA Y PINTURA
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Trabajos Otros Talleres"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayTotLatPint[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayTotLatPint[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayTotLatPint[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayTotLatPint[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayTotLatPint[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayTotLatPint[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayTotLatPint[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotLatPint[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayTotLatPint[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayTotLatPint[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayTotLatPint[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayTotLatPint[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayTotLatPint),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// TOTAL LATONERIA Y PINTURA
$totalMOLatPint = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOLatPint)) {
		foreach ($arrayMOLatPint as $indice => $valor) {
			$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
		}
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Total Latonería y Pintura"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round(($totalMOLatPint[1] + $arrayRepLatPint[1] + $arrayTotLatPint[1]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round(($totalMOLatPint[2] + $arrayRepLatPint[2] + $arrayTotLatPint[2]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round(($totalMOLatPint[3] + $arrayRepLatPint[3] + $arrayTotLatPint[3]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round(($totalMOLatPint[4] + $arrayRepLatPint[4] + $arrayTotLatPint[4]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round(($totalMOLatPint[5] + $arrayRepLatPint[5] + $arrayTotLatPint[5]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round(($totalMOLatPint[6] + $arrayRepLatPint[6] + $arrayTotLatPint[6]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round(($totalMOLatPint[7] + $arrayRepLatPint[7] + $arrayTotLatPint[7]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round(($totalMOLatPint[8] + $arrayRepLatPint[8] + $arrayTotLatPint[8]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round(($totalMOLatPint[9] + $arrayRepLatPint[9] + $arrayTotLatPint[9]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round(($totalMOLatPint[10] + $arrayRepLatPint[10] + $arrayTotLatPint[10]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round(($totalMOLatPint[11] + $arrayRepLatPint[11] + $arrayTotLatPint[11]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round(($totalMOLatPint[12] + $arrayRepLatPint[12] + $arrayTotLatPint[12]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) + array_sum($arrayTotLatPint)),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// COSTO MANO DE OBRA
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Costo Mano de Obra (Incluye Materiales"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayCostoMOLatPint[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayCostoMOLatPint),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// UTILIDAD MANO DE OBRA
$totalMOLatPint = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOLatPint)) {
		foreach ($arrayMOLatPint as $indice => $valor) {
			$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
		}
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Mano de Obra"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round(($totalMOLatPint[1] - $arrayCostoMOLatPint[1]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round(($totalMOLatPint[2] - $arrayCostoMOLatPint[2]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round(($totalMOLatPint[3] - $arrayCostoMOLatPint[3]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round(($totalMOLatPint[4] - $arrayCostoMOLatPint[4]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round(($totalMOLatPint[5] - $arrayCostoMOLatPint[5]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round(($totalMOLatPint[6] - $arrayCostoMOLatPint[6]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round(($totalMOLatPint[7] - $arrayCostoMOLatPint[7]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round(($totalMOLatPint[8] - $arrayCostoMOLatPint[8]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round(($totalMOLatPint[9] - $arrayCostoMOLatPint[9]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round(($totalMOLatPint[10] - $arrayCostoMOLatPint[10]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round(($totalMOLatPint[11] - $arrayCostoMOLatPint[11]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round(($totalMOLatPint[12] - $arrayCostoMOLatPint[12]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((array_sum($totalMOLatPint) - array_sum($arrayCostoMOLatPint)),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// COSTO DE VENTAS REPUESTOS POR LATONERIA Y PINTURA
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Costo de Ventas Repuestos"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayCostoRepLatPint[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayCostoRepLatPint),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

/// UTILIDAD REPUESTOS POR LATONERIA Y PINTURA
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Rep."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[1] - $arrayCostoRepLatPint[1]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[2] - $arrayCostoRepLatPint[2]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[3] - $arrayCostoRepLatPint[3]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[4] - $arrayCostoRepLatPint[4]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[5] - $arrayCostoRepLatPint[5]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[6] - $arrayCostoRepLatPint[6]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[7] - $arrayCostoRepLatPint[7]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[8] - $arrayCostoRepLatPint[8]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[9] - $arrayCostoRepLatPint[9]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[10] - $arrayCostoRepLatPint[10]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[11] - $arrayCostoRepLatPint[11]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round(($arrayRepLatPint[12] - $arrayCostoRepLatPint[12]),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint)),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// UTILIDAD DEPARTAMENTO LATONERIA Y PINTURA
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Dep."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round(($totalMOLatPint[1] + ($arrayRepLatPint[1] - $arrayCostoRepLatPint[1])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round(($totalMOLatPint[2] + ($arrayRepLatPint[2] - $arrayCostoRepLatPint[2])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round(($totalMOLatPint[3] + ($arrayRepLatPint[3] - $arrayCostoRepLatPint[3])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round(($totalMOLatPint[4] + ($arrayRepLatPint[4] - $arrayCostoRepLatPint[4])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round(($totalMOLatPint[5] + ($arrayRepLatPint[5] - $arrayCostoRepLatPint[5])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round(($totalMOLatPint[6] + ($arrayRepLatPint[6] - $arrayCostoRepLatPint[6])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round(($totalMOLatPint[7] + ($arrayRepLatPint[7] - $arrayCostoRepLatPint[7])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round(($totalMOLatPint[8] + ($arrayRepLatPint[8] - $arrayCostoRepLatPint[8])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round(($totalMOLatPint[9] + ($arrayRepLatPint[9] - $arrayCostoRepLatPint[9])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round(($totalMOLatPint[10] + ($arrayRepLatPint[10] - $arrayCostoRepLatPint[10])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round(($totalMOLatPint[11] + ($arrayRepLatPint[11] - $arrayCostoRepLatPint[11])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round(($totalMOLatPint[12] + ($arrayRepLatPint[12] - $arrayCostoRepLatPint[12])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint)),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."reporte_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(978, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE POST-VENTA (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// TOTAL FACTURACIÓN
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURACIÓN (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 24, " ", STR_PAD_BOTH),$textColor);
$posX = 120;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($mes[$mesCierre]),0,13)), 13, " ", STR_PAD_BOTH),$textColor);
	$posX += 65;
}
imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode("TOTALES AÑO"),0,18)), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// TOTAL FACTURACIÓN
$totalMOServ = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
		}
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Total Facturación"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
//imagestring($img,1,120,$posY,str_pad(formatoNumero(round((($totalMOServ[1] + $arrayRepServ[1] + $arrayTotServ[1] + $arrayNotasServ[1]) + $arrayRepMost[1] + ($totalMOLatPint[1] + $arrayRepLatPint[1])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor); // ESTE TIENE INCLUIDAS LAS NOTAS
imagestring($img,1,120,$posY,str_pad(formatoNumero(round((($totalMOServ[1] + $arrayRepServ[1] + $arrayTotServ[1]) + $arrayRepMost[1] + ($totalMOLatPint[1] + $arrayRepLatPint[1] + $arrayTotLatPint[1])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round((($totalMOServ[2] + $arrayRepServ[2] + $arrayTotServ[2]) + $arrayRepMost[2] + ($totalMOLatPint[2] + $arrayRepLatPint[2] + $arrayTotLatPint[2])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round((($totalMOServ[3] + $arrayRepServ[3] + $arrayTotServ[3]) + $arrayRepMost[3] + ($totalMOLatPint[3] + $arrayRepLatPint[3] + $arrayTotLatPint[3])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round((($totalMOServ[4] + $arrayRepServ[4] + $arrayTotServ[4]) + $arrayRepMost[4] + ($totalMOLatPint[4] + $arrayRepLatPint[4] + $arrayTotLatPint[4])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round((($totalMOServ[5] + $arrayRepServ[5] + $arrayTotServ[5]) + $arrayRepMost[5] + ($totalMOLatPint[5] + $arrayRepLatPint[5] + $arrayTotLatPint[5])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round((($totalMOServ[6] + $arrayRepServ[6] + $arrayTotServ[6]) + $arrayRepMost[6] + ($totalMOLatPint[6] + $arrayRepLatPint[6] + $arrayTotLatPint[6])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round((($totalMOServ[7] + $arrayRepServ[7] + $arrayTotServ[7]) + $arrayRepMost[7] + ($totalMOLatPint[7] + $arrayRepLatPint[7] + $arrayTotLatPint[7])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round((($totalMOServ[8] + $arrayRepServ[8] + $arrayTotServ[8]) + $arrayRepMost[8] + ($totalMOLatPint[8] + $arrayRepLatPint[8] + $arrayTotLatPint[8])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round((($totalMOServ[9] + $arrayRepServ[9] + $arrayTotServ[9]) + $arrayRepMost[9] + ($totalMOLatPint[9] + $arrayRepLatPint[9] + $arrayTotLatPint[9])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round((($totalMOServ[10] + $arrayRepServ[10] + $arrayTotServ[10]) + $arrayRepMost[10] + ($totalMOLatPint[10] + $arrayRepLatPint[10] + $arrayTotLatPint[10])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round((($totalMOServ[11] + $arrayRepServ[11] + $arrayTotServ[11]) + $arrayRepMost[11] + ($totalMOLatPint[11] + $arrayRepLatPint[11] + $arrayTotLatPint[11])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round((($totalMOServ[12] + $arrayRepServ[12] + $arrayTotServ[12]) + $arrayRepMost[12] + ($totalMOLatPint[12] + $arrayRepLatPint[12] + $arrayTotLatPint[12])),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
//imagestring($img,1,900,$posY,str_pad(formatoNumero(round(((array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ) + array_sum($arrayNotasServ)) + array_sum($arrayRepMost) + (array_sum($totalMOLatPint) + array_sum($arrayRepLatPint))),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor); // ESTE TIENE INCLUIDAS LAS NOTAS
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(((array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ)) + array_sum($arrayRepMost) + (array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) + array_sum($arrayTotLatPint))),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// UTILIDAD POST VENTA
$totalMOServ = NULL;
$totalMOLatPint = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
		}
	}
	
	if (isset($arrayMOLatPint)) {
		foreach ($arrayMOLatPint as $indice => $valor) {
			$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
		}
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Utl. Bruta Post-Venta"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round((($totalMOServ[1] + ($arrayRepServ[1] - $arrayCostoRepServ[1])) + ($arrayRepMost[1] - $arrayCostoRepMost[1]) + ($totalMOLatPint[1] + ($arrayRepLatPint[1] - $arrayCostoRepLatPint[1]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round((($totalMOServ[2] + ($arrayRepServ[2] - $arrayCostoRepServ[2])) + ($arrayRepMost[2] - $arrayCostoRepMost[2]) + ($totalMOLatPint[2] + ($arrayRepLatPint[2] - $arrayCostoRepLatPint[2]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round((($totalMOServ[3] + ($arrayRepServ[3] - $arrayCostoRepServ[3])) + ($arrayRepMost[3] - $arrayCostoRepMost[3]) + ($totalMOLatPint[3] + ($arrayRepLatPint[3] - $arrayCostoRepLatPint[3]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round((($totalMOServ[4] + ($arrayRepServ[4] - $arrayCostoRepServ[4])) + ($arrayRepMost[4] - $arrayCostoRepMost[4]) + ($totalMOLatPint[4] + ($arrayRepLatPint[4] - $arrayCostoRepLatPint[4]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round((($totalMOServ[5] + ($arrayRepServ[5] - $arrayCostoRepServ[5])) + ($arrayRepMost[5] - $arrayCostoRepMost[5]) + ($totalMOLatPint[5] + ($arrayRepLatPint[5] - $arrayCostoRepLatPint[5]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round((($totalMOServ[6] + ($arrayRepServ[6] - $arrayCostoRepServ[6])) + ($arrayRepMost[6] - $arrayCostoRepMost[6]) + ($totalMOLatPint[6] + ($arrayRepLatPint[6] - $arrayCostoRepLatPint[6]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round((($totalMOServ[7] + ($arrayRepServ[7] - $arrayCostoRepServ[7])) + ($arrayRepMost[7] - $arrayCostoRepMost[7]) + ($totalMOLatPint[7] + ($arrayRepLatPint[7] - $arrayCostoRepLatPint[7]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round((($totalMOServ[8] + ($arrayRepServ[8] - $arrayCostoRepServ[8])) + ($arrayRepMost[8] - $arrayCostoRepMost[8]) + ($totalMOLatPint[8] + ($arrayRepLatPint[8] - $arrayCostoRepLatPint[8]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round((($totalMOServ[9] + ($arrayRepServ[9] - $arrayCostoRepServ[9])) + ($arrayRepMost[9] - $arrayCostoRepMost[9]) + ($totalMOLatPint[9] + ($arrayRepLatPint[9] - $arrayCostoRepLatPint[9]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round((($totalMOServ[10] + ($arrayRepServ[10] - $arrayCostoRepServ[10])) + ($arrayRepMost[10] - $arrayCostoRepMost[10]) + ($totalMOLatPint[10] + ($arrayRepLatPint[10] - $arrayCostoRepLatPint[10]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round((($totalMOServ[11] + ($arrayRepServ[11] - $arrayCostoRepServ[11])) + ($arrayRepMost[11] - $arrayCostoRepMost[11]) + ($totalMOLatPint[11] + ($arrayRepLatPint[11] - $arrayCostoRepLatPint[11]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round((($totalMOServ[12] + ($arrayRepServ[12] - $arrayCostoRepServ[12])) + ($arrayRepMost[12] - $arrayCostoRepMost[12]) + ($totalMOLatPint[12] + ($arrayRepLatPint[12] - $arrayCostoRepLatPint[12]))),2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(((array_sum($totalMOServ) + (array_sum($arrayRepServ) - array_sum($arrayCostoRepServ))) + (array_sum($arrayRepMost) - array_sum($arrayCostoRepMost)) + (array_sum($totalMOLatPint) + (array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint)))),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// INVENTARIO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("INVENTARIO (".$anoCierre.")"), 195, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 24, " ", STR_PAD_BOTH),$textColor);
$posX = 120;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($mes[$mesCierre]),0,13)), 13, " ", STR_PAD_BOTH),$textColor);
	$posX += 65;
}
imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode("TOTALES AÑO"),0,20)), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
		
// CLASIFICACION INVENTARIO
if (isset($arrayClasifInv)) {
	foreach ($arrayClasifInv as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayClasifInv[$indice][0],0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,120,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,185,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,250,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,380,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,445,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,510,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,640,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,705,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,770,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,835,$posY,str_pad(formatoNumero(round($arrayClasifInv[$indice][12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($arrayClasifInv[$indice]),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
		
// TOTAL INVENTARIO
$totalClasifInv = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayClasifInv)) {
		foreach ($arrayClasifInv as $indice => $valor) {
			$totalClasifInv[$mesCierre] += $arrayClasifInv[$indice][$mesCierre];
		}
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Total Inventario"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($totalClasifInv[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($totalClasifInv[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($totalClasifInv[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($totalClasifInv[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($totalClasifInv[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($totalClasifInv[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($totalClasifInv[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($totalClasifInv[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($totalClasifInv[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($totalClasifInv[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($totalClasifInv[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($totalClasifInv[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round(array_sum($totalClasifInv),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// SUMA CLASIFICACION SELECCIONADA
$totalClasifInvSelec = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[3][$mesCierre] + $arrayClasifInv[4][$mesCierre] + $arrayClasifInv[5][$mesCierre]) > 0) {
		$totalClasifInvSelec[$mesCierre] = ($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[5][$mesCierre]) * 100 / ($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[3][$mesCierre] + $arrayClasifInv[4][$mesCierre] + $arrayClasifInv[5][$mesCierre]);
		
		$cantClasifInvSelec++;
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("A + B + C + F"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($totalClasifInvSelec[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((($cantClasifInvSelec > 0) ? (array_sum($totalClasifInvSelec) / $cantClasifInvSelec) : 0),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// MESES COBERTURA
$totalClasifInv = NULL;
$totalMesesCobertura = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if (isset($arrayClasifInv)) {
		foreach ($arrayClasifInv as $indice => $valor) {
			$totalClasifInv[$mesCierre] += $arrayClasifInv[$indice][$mesCierre];
		}
	}
	
	if ($totalClasifInv[$mesCierre] > 0 && ($arrayCostoRepLatPint[$mesCierre] + $arrayCostoRepMost[$mesCierre] + $arrayCostoRepServ[$mesCierre]) > 0) {
		$totalMesesCobertura[$mesCierre] += $totalClasifInv[$mesCierre] / ($arrayCostoRepLatPint[$mesCierre] + $arrayCostoRepMost[$mesCierre] + $arrayCostoRepServ[$mesCierre]);
		
		$cantMesesCoberturaSelec++;
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Meses Cobertura"),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($totalMesesCobertura[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($totalMesesCobertura[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($totalMesesCobertura[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($totalMesesCobertura[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($totalMesesCobertura[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($totalMesesCobertura[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($totalMesesCobertura[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($totalMesesCobertura[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($totalMesesCobertura[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($totalMesesCobertura[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($totalMesesCobertura[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($totalMesesCobertura[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((($cantMesesCoberturaSelec > 0) ? (array_sum($totalMesesCobertura) / $cantMesesCoberturaSelec) : 0),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MARGENES
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 24, " ", STR_PAD_BOTH),$textColor);
$posX = 120;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($mes[$mesCierre]),0,13)), 13, " ", STR_PAD_BOTH),$textColor);
	$posX += 65;
}
imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode("TOTALES AÑO"),0,20)), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 195, "-", STR_PAD_BOTH),$textColor);

// MARGEN REPUESTOS / SERVICIOS
$totalMargenRepServ = NULL;
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if ($arrayRepServ[$mesCierre] > 0) {
		$totalMargenRepServ[$mesCierre] += ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]) * 100 / $arrayRepServ[$mesCierre];
		
		$cantMargenRepServ++;
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Margen Rptos/Serv."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($totalMargenRepServ[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($totalMargenRepServ[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($totalMargenRepServ[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($totalMargenRepServ[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($totalMargenRepServ[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($totalMargenRepServ[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($totalMargenRepServ[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($totalMargenRepServ[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($totalMargenRepServ[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($totalMargenRepServ[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($totalMargenRepServ[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($totalMargenRepServ[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((($cantMargenRepServ > 0) ? (array_sum($totalMargenRepServ) / $cantMargenRepServ) : 0),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

// MARGEN REPUESTOS / MOSTRADOR
for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
	if ($arrayRepMost[$mesCierre] > 0) {
		$totalMargenRepMost[$mesCierre] = ($arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre]) * 100 / $arrayRepMost[$mesCierre];
		
		$cantMargenRepMost++;
	}
}
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Margen Rptos/Most."),0,24)), 24, " ", STR_PAD_RIGHT),$textColor);
imagestring($img,1,120,$posY,str_pad(formatoNumero(round($totalMargenRepMost[1],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,185,$posY,str_pad(formatoNumero(round($totalMargenRepMost[2],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,250,$posY,str_pad(formatoNumero(round($totalMargenRepMost[3],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,315,$posY,str_pad(formatoNumero(round($totalMargenRepMost[4],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,380,$posY,str_pad(formatoNumero(round($totalMargenRepMost[5],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,445,$posY,str_pad(formatoNumero(round($totalMargenRepMost[6],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,510,$posY,str_pad(formatoNumero(round($totalMargenRepMost[7],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,575,$posY,str_pad(formatoNumero(round($totalMargenRepMost[8],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,640,$posY,str_pad(formatoNumero(round($totalMargenRepMost[9],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,705,$posY,str_pad(formatoNumero(round($totalMargenRepMost[10],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,770,$posY,str_pad(formatoNumero(round($totalMargenRepMost[11],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,835,$posY,str_pad(formatoNumero(round($totalMargenRepMost[12],2), 1, $nroDecimales), 13, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,900,$posY,str_pad(formatoNumero(round((($cantMargenRepMost > 0) ? (array_sum($totalMargenRepMost) / $cantMargenRepMost) : 0),2), 1, $nroDecimales), 15, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."reporte_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 60, 978, 520);
	}
}

$pdf->SetDisplayMode(70);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}

function formatoNumero($monto, $idFormatoNumero = 1, $nroDecimales = 2){
	switch($idFormatoNumero) {
		case 1 : return number_format($monto, $nroDecimales, ".", ","); break;
		case 2 : return number_format($monto, $nroDecimales, ",", "."); break;
		case 3 : return number_format($monto, $nroDecimales, ".", ""); break;
		case 4 : return number_format($monto, $nroDecimales, ",", ""); break;
	}
}
?>