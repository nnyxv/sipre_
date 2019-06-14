<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
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
$valFecha[0] = date("m", strtotime("01-".$valCadBusq[1]));
$valFecha[1] = date("Y", strtotime("01-".$valCadBusq[1]));
//$nroDecimales = ($_GET['lstDecimalPDF'] == 1) ? 2 : 0;

//MODIFICACION
if(($_GET['lstDecimalPDF'] == 1)||($_GET['lstDecimalPDF'] == 3)){
	$nroDecimales = 2;
}else{
	$nroDecimales = 0;	
}
//FIN MODIFICACION

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// RESUMEN MENSUAL
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// RESUMEN MENSUAL
$queryVentaMensual = sprintf("SELECT 
	uni_fis.id_condicion_unidad, 
	cond_unidad.descripcion AS condicion_unidad, 
	COUNT(
		(
			CASE MONTH(cxc_fact.fechaRegistroFactura) WHEN %s THEN cxc_fact.idFactura END
		)
	) AS nro_unidades_vendidas, 
	COUNT(cxc_fact.idFactura) AS nro_unidades_vendidas_acumulado, 
	SUM(
		(
			CASE MONTH(cxc_fact.fechaRegistroFactura) WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			) END
		)
	) AS monto_facturado_vehiculo, 
	SUM(
		if (
			cxc_fact.fechaRegistroFactura <= '2018-08-20', 
			cxc_fact_det_vehic.precio_unitario / 100000, 
			cxc_fact_det_vehic.precio_unitario
		)
	) AS monto_facturado_vehiculo_acumulado 
FROM 
	cj_cc_encabezadofactura cxc_fact 
	INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (
		cxc_fact.idFactura = cxc_fact_det_vehic.id_factura
	) 
	INNER JOIN an_unidad_fisica uni_fis ON (
		cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica
	) 
	INNER JOIN an_condicion_unidad cond_unidad ON (
		uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad
	) 
	INNER JOIN an_pedido ped_vent ON (
		cxc_fact.numeroPedido = ped_vent.id_pedido
	) 
	LEFT JOIN an_solicitud_factura ped_comp_det ON (
		uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud
	) %s
	GROUP BY uni_fis.id_condicion_unidad

UNION
SELECT 
	uni_fis.id_condicion_unidad, 
	cond_unidad.descripcion AS condicion_unidad, 
	(
		(-1) * COUNT(
			(
				CASE MONTH(cxc_nc.fechaNotaCredito) WHEN %s THEN cxc_nc.idNotaCredito END
			)
		)
	) AS nro_unidades_vendidas, 
	(
		(-1) * COUNT(cxc_nc.idNotaCredito)
	) AS nro_unidades_vendidas_acumulado, 
	(
		(-1) * SUM(
			(
				CASE MONTH(cxc_nc.fechaNotaCredito) WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			) END
			)
		)
	) AS monto_facturado_vehiculo, 
	(
		(-1) * SUM(
            if (
			cxc_nc.fechaNotaCredito <= '2018-08-20', 
			cxc_nc_det_vehic.precio_unitario / 100000, 
			cxc_nc_det_vehic.precio_unitario
		)
		)
	) AS monto_facturado_vehiculo_acumulado
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA') %s
GROUP BY uni_fis.id_condicion_unidad;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsVentaMensual = mysql_query($queryVentaMensual);
if (!$rsVentaMensual) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaMensual = mysql_fetch_assoc($rsVentaMensual)) {
	$existe = false;
	$arrayDetalleMensual = NULL;
	if (isset($arrayVentaMensual)) {
		foreach ($arrayVentaMensual as $indice => $valor) {
			if ($arrayVentaMensual[$indice]['id_condicion_unidad'] == $rowVentaMensual['id_condicion_unidad']) {
				$existe = true;
				
				$arrayVentaMensual[$indice]['nro_unidades_vendidas'] += round($rowVentaMensual['nro_unidades_vendidas'],2);
				$arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2);
				$arrayVentaMensual[$indice]['monto_facturado_vehiculo'] += round($rowVentaMensual['monto_facturado_vehiculo'],2);
				$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2);
			}
		}
	}
	
	if ($existe == false) {
		$arrayVentaMensual[] = array(
			"id_condicion_unidad" => $rowVentaMensual['id_condicion_unidad'],
			"condicion_unidad" => $rowVentaMensual['condicion_unidad'],
			"nro_unidades_vendidas" => round($rowVentaMensual['nro_unidades_vendidas'],2),
			"nro_unidades_vendidas_acumulado" => round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2),
			"monto_facturado_vehiculo" => round($rowVentaMensual['monto_facturado_vehiculo'],2),
			"monto_facturado_vehiculo_acumulado" => round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2));
	}
	
	$arrayTotalVentaMensual['nro_unidades_vendidas'] += round($rowVentaMensual['nro_unidades_vendidas'],2);
	$arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'] += round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2);
	$arrayTotalVentaMensual['monto_facturado_vehiculo'] += round($rowVentaMensual['monto_facturado_vehiculo'],2);
	$arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'] += round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ACCESORIOS INSTALADOS POR ASESOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND ped_vent.vexacc1 > 0");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND ped_vent.vexacc1 > 0
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// ACCESORIOS INSTALADOS POR ASESOR
$queryVentaAccesorio = sprintf("SELECT
	vw_pg_empleado.id_empleado,
	vw_pg_empleado.nombre_empleado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN if (
			cxc_fact.fechaRegistroFactura <= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)
			
	END)) AS monto_facturado_accesorio,
	
	SUM(if (
			cxc_fact.fechaRegistroFactura <= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)) AS monto_facturado_accesorio_acumulado
FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
	INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
GROUP BY vw_pg_empleado.id_empleado

UNION

SELECT 
	vw_pg_empleado.id_empleado,
	vw_pg_empleado.nombre_empleado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN if (
			cxc_nc.fechaNotaCredito<= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)
	END))) AS monto_facturado_accesorio,
	
	((-1) * SUM(if (
			cxc_nc.fechaNotaCredito<= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)
			)) AS monto_facturado_accesorio_acumulado
FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
	RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
GROUP BY vw_pg_empleado.id_empleado

ORDER BY 2;",
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsVentaAccesorio = mysql_query($queryVentaAccesorio);
if (!$rsVentaAccesorio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaAccesorio = mysql_fetch_assoc($rsVentaAccesorio)) {
	$existe = false;
	if (isset($arrayVentaAccesorio)) {
		foreach ($arrayVentaAccesorio as $indice => $valor) {
			if ($arrayVentaAccesorio[$indice][0] == $rowVentaAccesorio['nombre_empleado']) {
				$existe = true;
				
				$arrayVentaAccesorio[$indice][1] += round($rowVentaAccesorio['monto_facturado_accesorio'],2);
				$arrayVentaAccesorio[$indice][2] += round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayVentaAccesorio[] = array(
			$rowVentaAccesorio['nombre_empleado'],
			round($rowVentaAccesorio['monto_facturado_accesorio'],2),
			round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2));
	}
	
	$arrayTotalVentaAccesorio[1] += round($rowVentaAccesorio['monto_facturado_accesorio'],2);
	$arrayTotalVentaAccesorio[2] += round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ADICIONALES INSTALADOS POR ASESOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$htmlTblIni = "";
$htmlTh = "";
$htmlTb = "";
$htmlTblFin = "";
$arrayDet = NULL;
$arrayClave = NULL;

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_fact_det_acc.id_tipo_accesorio IN (1,3)");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_nc_det_acc.id_tipo_accesorio IN (1,3)
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// ADICIONALES INSTALADOS POR ASESOR
$queryVentaAdicional = sprintf("SELECT
	query.id_empleado,
	query.nombre_empleado,
	query.id_accesorio,
	query.id_tipo_accesorio,
	query.nom_accesorio,
	query.fecha,
	SUM(query.cant_facturado_accesorio) AS cant_facturado_accesorio,
	SUM(query.cant_facturado_accesorio_acumulado) AS cant_facturado_accesorio_acumulado,
	SUM(query.monto_facturado_accesorio) AS monto_facturado_accesorio,
	SUM(query.monto_costo_facturado_accesorio) AS monto_costo_facturado_accesorio,
	SUM(query.monto_facturado_accesorio_acumulado) AS monto_facturado_accesorio_acumulado,
	SUM(query.monto_facturado_costo_accesorio_acumulado) AS monto_facturado_costo_accesorio_acumulado
FROM (
	SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		acc.id_accesorio,
		acc.id_tipo_accesorio,
		acc.nom_accesorio,
		cxc_fact.fechaRegistroFactura as fecha,
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_acc.cantidad
		END)) AS cant_facturado_accesorio,
		
		COUNT(cxc_fact_det_acc.cantidad) AS cant_facturado_accesorio_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_acc.precio_unitario
		END)) AS monto_facturado_accesorio,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_acc.costo_compra
		END)) AS monto_costo_facturado_accesorio,
		
		SUM(cxc_fact_det_acc.precio_unitario) AS monto_facturado_accesorio_acumulado,
		SUM(cxc_fact_det_acc.costo_compra) AS monto_facturado_costo_accesorio_acumulado
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
	GROUP BY vw_pg_empleado.id_empleado, cxc_fact_det_acc.id_accesorio

	UNION
	
	SELECT 
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		acc.id_accesorio,
		acc.id_tipo_accesorio,
		acc.nom_accesorio,
		cxc_nc.fechaNotaCredito as fecha,
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_acc.cantidad
		END))) AS cant_facturado_accesorio,
		
		((-1) * COUNT(cxc_nc_det_acc.cantidad)) AS cant_facturado_accesorio_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_acc.precio_unitario
		END))) AS monto_facturado_accesorio,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_acc.costo_compra
		END))) AS monto_costo_facturado_accesorio,
		
		((-1) * SUM(cxc_nc_det_acc.precio_unitario)) AS monto_facturado_accesorio_acumulado,
		((-1) * SUM(cxc_nc_det_acc.costo_compra)) AS monto_facturado_costo_accesorio_acumulado
	FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
		INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
	GROUP BY vw_pg_empleado.id_empleado, cxc_nc_det_acc.id_accesorio
	
	ORDER BY 2) AS query
GROUP BY query.id_empleado, query.id_accesorio, query.id_tipo_accesorio;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsVentaAdicional = mysql_query($queryVentaAdicional);
if (!$rsVentaAdicional) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaAdicional = mysql_fetch_assoc($rsVentaAdicional)) {
	$existe = false;
	$arrayDetalleAdicional = NULL;
	if (isset($arrayVentaAdicional)) {
		foreach ($arrayVentaAdicional as $indice => $valor) {
			if ($arrayVentaAdicional[$indice]['id_empleado'] == $rowVentaAdicional['id_empleado']) {
				$existe = true;
				
				$existeAdicional = false;
				$arrayDetalleAdicional = NULL;
				if (isset($arrayVentaAdicional[$indice]['array_adicional'])) {
					foreach ($arrayVentaAdicional[$indice]['array_adicional'] as $indice2 => $valor2) {
						$arrayDetalleAdicional = $valor2;
						if ($arrayDetalleAdicional['id_accesorio'] == $rowVentaAdicional['id_accesorio']) {
							$existeAdicional = true;
							
							$arrayDetalleAdicional['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
							$arrayDetalleAdicional['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
							$arrayDetalleAdicional['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
							$arrayDetalleAdicional['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
						}
						
						$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['cant_facturado_accesorio'] = $arrayDetalleAdicional['cant_facturado_accesorio'];
						$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['cant_facturado_accesorio_acumulado'] = $arrayDetalleAdicional['cant_facturado_accesorio_acumulado'];
						$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['monto_facturado_accesorio'] = $arrayDetalleAdicional['monto_facturado_accesorio'];
						$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['monto_facturado_accesorio_acumulado'] = $arrayDetalleAdicional['monto_facturado_accesorio_acumulado'];
					}
				}
				
				if ($existeAdicional == false) {
					$arrayDetalleAdicional = array(
						"id_accesorio" => $rowVentaAdicional['id_accesorio'],
						"id_tipo_accesorio" => $rowVentaAdicional['id_tipo_accesorio'],
						"fecha" => $rowVentaAdicional['fecha'],
						"nom_accesorio" => $rowVentaAdicional['nom_accesorio'],
						"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
						"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
						"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
						"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
					
					$arrayVentaAdicional[$indice]['array_adicional'][] = $arrayDetalleAdicional;
				}
				
				$arrayVentaAdicional[$indice]['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
				$arrayVentaAdicional[$indice]['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
				$arrayVentaAdicional[$indice]['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
				$arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayDetalleAdicional[] = array(
			"id_accesorio" => $rowVentaAdicional['id_accesorio'],
			"fecha" => $rowVentaAdicional['fecha'],
			"id_tipo_accesorio" => $rowVentaAdicional['id_tipo_accesorio'],
			"nom_accesorio" => $rowVentaAdicional['nom_accesorio'],
			"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
			"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
			"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
			"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
			
		$arrayVentaAdicional[] = array(
			"id_empleado" => $rowVentaAdicional['id_empleado'],
			"nombre_empleado" => $rowVentaAdicional['nombre_empleado'],
			"array_adicional" => $arrayDetalleAdicional,
			"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
			"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
			"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
			"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
	}
	
	$arrayTotalVentaAdicional['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
	$arrayTotalVentaAdicional['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
	$arrayTotalVentaAdicional['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
	$arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS POR ASESOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// VENTAS POR ASESOR
$queryVentaAsesor = sprintf("SELECT
	vw_pg_empleado.id_empleado,
	vw_pg_empleado.nombre_empleado,
	uni_fis.id_condicion_unidad,
	cxc_fact.fechaRegistroFactura as fecha,

	cond_unidad.descripcion AS condicion_unidad,
	
	COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END)) AS nro_unidades_vendidas,
	
	COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
			cxc_fact_det_vehic.precio_unitario
	END)) AS monto_facturado_vehiculo,
	
	SUM(cxc_fact_det_vehic.precio_unitario) AS monto_facturado_vehiculo_acumulado
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
GROUP BY vw_pg_empleado.id_empleado, uni_fis.id_condicion_unidad

UNION

SELECT 
	vw_pg_empleado.id_empleado,
	vw_pg_empleado.nombre_empleado,
	uni_fis.id_condicion_unidad,
	cxc_nc.fechaNotaCredito as fecha,
	cond_unidad.descripcion AS condicion_unidad,
	
	((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END))) AS nro_unidades_vendidas,
	
	((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			cxc_nc_det_vehic.precio_unitario
	END))) AS monto_facturado_vehiculo,
	
	((-1) * SUM(cxc_nc_det_vehic.precio_unitario)) AS monto_facturado_vehiculo_acumulado
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
GROUP BY vw_pg_empleado.id_empleado, uni_fis.id_condicion_unidad

ORDER BY 4,2;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsVentaAsesor = mysql_query($queryVentaAsesor);
if (!$rsVentaAsesor) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaAsesor = mysql_fetch_assoc($rsVentaAsesor)) {
	$existe = false;
	$arrayDetalleAsesor = NULL;
	if ($arrayVentaAsesor) {
		foreach ($arrayVentaAsesor as $indice => $valor) {
			if ($arrayVentaAsesor[$indice]['id_condicion_unidad'] == $rowVentaAsesor['id_condicion_unidad']) {
				$existe = true;
				
				$existeAsesor = false;
				$arrayDetalleAsesor = NULL;
				if (isset($arrayVentaAsesor[$indice]['array_asesor'])) {
					foreach ($arrayVentaAsesor[$indice]['array_asesor'] as $indice2 => $valor2) {
						$arrayDetalleAsesor = $valor2;
						if ($arrayDetalleAsesor['id_empleado'] == $rowVentaAsesor['id_empleado']) {
							$existeAsesor = true;
							
							$arrayDetalleAsesor['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
							$arrayDetalleAsesor['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
							$arrayDetalleAsesor['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
							$arrayDetalleAsesor['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
						}
						
						$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['nro_unidades_vendidas'] = round($arrayDetalleAsesor['nro_unidades_vendidas'],2);
						$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['nro_unidades_vendidas_acumulado'] = round($arrayDetalleAsesor['nro_unidades_vendidas_acumulado'],2);
						$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['monto_facturado_vehiculo'] = round($arrayDetalleAsesor['monto_facturado_vehiculo'],2);
						$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['monto_facturado_vehiculo_acumulado'] = round($arrayDetalleAsesor['monto_facturado_vehiculo_acumulado'],2);
					}
				}
				
				if ($existeAsesor == false) {
					$arrayDetalleAsesor = array(
						"fecha" => $rowVentaAsesor['fecha'],
						"id_empleado" => $rowVentaAsesor['id_empleado'],
						"nombre_empleado" => $rowVentaAsesor['nombre_empleado'],
						"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
						"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
						"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
						"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
					
					$arrayVentaAsesor[$indice]['array_asesor'][] = $arrayDetalleAsesor;
				}
				
				$arrayVentaAsesor[$indice]['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
				$arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
				$arrayVentaAsesor[$indice]['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
				$arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayDetalleAsesor[] = array(
			"fecha" => $rowVentaAsesor['fecha'],
			"id_empleado" => $rowVentaAsesor['id_empleado'],
			"nombre_empleado" => $rowVentaAsesor['nombre_empleado'],
			"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
			"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
			"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
			"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
		
		$arrayVentaAsesor[] = array(
			"id_condicion_unidad" => $rowVentaAsesor['id_condicion_unidad'],
			"fecha" => $rowVentaAsesor['fecha'],
			"condicion_unidad" => $rowVentaAsesor['condicion_unidad'],
			"array_asesor" => $arrayDetalleAsesor,
			"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
			"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
			"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
			"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
	}
	
	$arrayTotalVentaAsesor['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
	$arrayTotalVentaAsesor['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
	$arrayTotalVentaAsesor['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
	$arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS POR MODELO DE VEHÍCULO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$htmlTblIni = "";
$htmlTh = "";
$htmlTb = "";
$htmlTblFin = "";
$arrayDet = NULL;
$arrayMov = NULL;
$arrayClave = NULL;

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// VENTAS POR MODELO DE VEHÍCULO
$queryVentaModelo = sprintf("SELECT
	uni_bas.id_uni_bas,
	CONCAT(uni_bas.nom_uni_bas, ': ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
	uni_fis.id_condicion_unidad,
	cond_unidad.descripcion AS condicion_unidad,
	cxc_fact.fechaRegistroFactura as fecha,
	
	COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END)) AS cant_unidades_vendidas,
	
	COUNT(uni_fis.id_unidad_fisica) AS cant_unidades_vendidas_acumulado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
		 IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)
			
	END)) AS monto_unidades_vendidas,
	
	SUM( IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)) AS monto_unidades_vendidas_acumulado
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
	INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
	INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano) %s
GROUP BY uni_bas.id_uni_bas, uni_fis.id_condicion_unidad

UNION

SELECT
	uni_bas.id_uni_bas,
	CONCAT(uni_bas.nom_uni_bas, ': ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
	uni_fis.id_condicion_unidad,
	cond_unidad.descripcion AS condicion_unidad,
	cxc_nc.fechaNotaCredito as fecha,
	
	((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END))) AS cant_unidades_vendidas,
	
	((-1) * COUNT(uni_fis.id_unidad_fisica)) AS cant_unidades_vendidas_acumulado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario /100000, 
				cxc_nc_det_vehic.precio_unitario
			)
			
	END))) AS monto_unidades_vendidas,
	
	((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			))) AS monto_unidades_vendidas_acumulado
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
	INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
	INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano) %s
GROUP BY uni_bas.id_uni_bas, uni_fis.id_condicion_unidad

ORDER BY 4,2;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
//var_dump($queryVentaModelo);
$rsVentaModelo = mysql_query($queryVentaModelo);
if (!$rsVentaModelo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaModelo = mysql_fetch_assoc($rsVentaModelo)) {
	$existe = false;
	$arrayDetalleModelo = NULL;
	if (isset($arrayVentaModelo)) {
		foreach ($arrayVentaModelo as $indice => $valor) {
			if ($arrayVentaModelo[$indice]['id_condicion_unidad'] == $rowVentaModelo['id_condicion_unidad']) {
				$existe = true;
				$existeModelo = false;
				$arrayDetalleModelo = NULL;
				if (isset($arrayVentaModelo[$indice]['array_modelo'])) {
					foreach ($arrayVentaModelo[$indice]['array_modelo'] as $indice2 => $valor2) {
						$arrayDetalleModelo = $valor2;
						if ($arrayDetalleModelo['id_uni_bas'] == $rowVentaModelo['id_uni_bas']) {
							$existeModelo = true;
							
							$arrayDetalleModelo['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
							$arrayDetalleModelo['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
							$arrayDetalleModelo['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
							$arrayDetalleModelo['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
						}
						
						$arrayVentaModelo[$indice]['array_modelo'][$indice2]['cant_unidades_vendidas'] = round($arrayDetalleModelo['cant_unidades_vendidas'],2);
						$arrayVentaModelo[$indice]['array_modelo'][$indice2]['cant_unidades_vendidas_acumulado'] = round($arrayDetalleModelo['cant_unidades_vendidas_acumulado'],2);
						$arrayVentaModelo[$indice]['array_modelo'][$indice2]['monto_unidades_vendidas'] = round($arrayDetalleModelo['monto_unidades_vendidas'],2);
						$arrayVentaModelo[$indice]['array_modelo'][$indice2]['monto_unidades_vendidas_acumulado'] = round($arrayDetalleModelo['monto_unidades_vendidas_acumulado'],2);
					}
				}
				//var_dump($rowVentaModelo['fecha']);	
				
				if ($existeModelo == false) {
					$arrayDetalleModelo = array(
						"id_uni_bas" => $rowVentaModelo['id_uni_bas'],
						"fecha" => $rowVentaModelo['fecha'],
						"vehiculo" => $rowVentaModelo['vehiculo'],
						"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
						"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
						"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
						"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
					
					$arrayVentaModelo[$indice]['array_modelo'][] = $arrayDetalleModelo;
				}
				
				$arrayVentaModelo[$indice]['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
				$arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
				$arrayVentaModelo[$indice]['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
				$arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayDetalleModelo[] = array(
			"id_uni_bas" => $rowVentaModelo['id_uni_bas'],
			"fecha" => $rowVentaModelo['fecha'],
			"vehiculo" => $rowVentaModelo['vehiculo'],
			"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
			"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
			"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
			"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
		
		$arrayVentaModelo[] = array(
			"id_condicion_unidad" => $rowVentaModelo['id_condicion_unidad'],
			"condicion_unidad" => $rowVentaModelo['condicion_unidad'],
			"array_modelo" => $arrayDetalleModelo,
			"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
			"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
			"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
			"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
	}
	
	$arrayTotalVentaModelo['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
	$arrayTotalVentaModelo['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
	$arrayTotalVentaModelo['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
	$arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// OPERACIONES A CRÉDITO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND ped_vent.porcentaje_inicial < 100");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND ped_vent.porcentaje_inicial < 100
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// OPERACIONES A CRÉDITO
$queryVentaCredito = sprintf("SELECT 
	banco.idBanco AS id_banco,
	banco.nombreBanco AS nombre_banco,
	
	COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END)) AS nro_unidades_vendidas,
	
	COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			)
	END)) AS monto_financiado,
	
	SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			)) AS monto_financiado_acumulado
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
GROUP BY banco.idBanco

UNION

SELECT 
	banco.idBanco AS id_banco,
	banco.nombreBanco AS nombre_banco,
	
	((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END))) AS nro_unidades_vendidas,
	
	((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			)
	END))) AS monto_financiado,
	
	((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			))) AS monto_financiado_acumulado
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
GROUP BY banco.idBanco

ORDER BY 2;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsVentaCredito = mysql_query($queryVentaCredito);
if (!$rsVentaCredito) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaCredito = mysql_fetch_assoc($rsVentaCredito)) {
	$existe = false;
	if (isset($arrayVentaCredito)) {
		foreach ($arrayVentaCredito as $indice => $valor) {
			if ($arrayVentaCredito[$indice][0] == $rowVentaCredito['nombre_banco']) {
				$existe = true;
				
				$arrayVentaCredito[$indice][1] += round($rowVentaCredito['nro_unidades_vendidas'],2);
				$arrayVentaCredito[$indice][2] += round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2);
				$arrayVentaCredito[$indice][3] += round($rowVentaCredito['monto_financiado'],2);
				$arrayVentaCredito[$indice][4] += round($rowVentaCredito['monto_financiado_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayVentaCredito[] = array(
			$rowVentaCredito['nombre_banco'],
			round($rowVentaCredito['nro_unidades_vendidas'],2),
			round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2),
			round($rowVentaCredito['monto_financiado'],2),
			round($rowVentaCredito['monto_financiado_acumulado'],2));
	}
	
	$arrayTotalVentaCredito[1] += round($rowVentaCredito['nro_unidades_vendidas'],2);
	$arrayTotalVentaCredito[2] += round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2);
	$arrayTotalVentaCredito[3] += round($rowVentaCredito['monto_financiado'],2);
	$arrayTotalVentaCredito[4] += round($rowVentaCredito['monto_financiado_acumulado'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// OPERACIONES A CONTADO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND ped_vent.porcentaje_inicial = 100");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND ped_vent.porcentaje_inicial = 100
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// OPERACIONES A CONTADO
$queryVentaContado = sprintf("SELECT 
	'Contado' AS nombre_banco,
	
	COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END)) AS nro_unidades_vendidas,
	
	COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
		IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.inicial / 100000, 
				ped_vent.inicial
			)
	END)) AS monto_contado,
	
	SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.inicial / 100000, 
				ped_vent.inicial
			)) AS monto_contado_acumulado
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s

UNION

SELECT 
	'Contado' AS nombre_banco,
	
	((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			uni_fis.id_unidad_fisica
	END))) AS nro_unidades_vendidas,
	
	((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.inicial / 100000, 
				ped_vent.inicial
			)
			
	END))) AS monto_contado,
	
	((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.inicial /100000, 
				ped_vent.inicial
			))) AS monto_contado_acumulado
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
GROUP BY 1

ORDER BY 2;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsVentaContado = mysql_query($queryVentaContado);
if (!$rsVentaContado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowVentaContado = mysql_fetch_assoc($rsVentaContado)) {
	$existe = false;
	if (isset($arrayVentaContado)) {
		foreach ($arrayVentaContado as $indice => $valor) {
			if ($arrayVentaContado[$indice][0] == $rowVentaContado['nombre_banco']) {
				$existe = true;
				
				$arrayVentaContado[$indice][1] += round($rowVentaContado['nro_unidades_vendidas'],2);
				$arrayVentaContado[$indice][2] += round($rowVentaContado['nro_unidades_vendidas_acumulado'],2);
				$arrayVentaContado[$indice][3] += round($rowVentaContado['monto_contado'],2);
				$arrayVentaContado[$indice][4] += round($rowVentaContado['monto_contado_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayVentaContado[] = array(
			$rowVentaContado['nombre_banco'],
			round($rowVentaContado['nro_unidades_vendidas'],2),
			round($rowVentaContado['nro_unidades_vendidas_acumulado'],2),
			round($rowVentaContado['monto_contado'],2),
			round($rowVentaContado['monto_contado_acumulado'],2));
	}
	
	$arrayTotalVentaContado[1] += round($rowVentaContado['nro_unidades_vendidas'],2);
	$arrayTotalVentaContado[2] += round($rowVentaContado['nro_unidades_vendidas_acumulado'],2);
	$arrayTotalVentaContado[3] += round($rowVentaContado['monto_contado'],2);
	$arrayTotalVentaContado[4] += round($rowVentaContado['monto_contado_acumulado'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMPAÑIA DE SEGUROS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// COMPAÑIA DE SEGUROS
$querySeguros = sprintf("SELECT 
	poliza.id_poliza,
	poliza.nombre_poliza,
	
	COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
			poliza.id_poliza
	END)) AS nro_unidades_vendidas,
	
	COUNT(poliza.id_poliza) AS nro_unidades_vendidas_acumulado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			)
			
	END)) AS monto_asegurado,
	
	SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			)) AS monto_asegurado_acumulado
FROM cj_cc_encabezadofactura cxc_fact
	LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza) %s
GROUP BY poliza.id_poliza

UNION

SELECT 
	poliza.id_poliza,
	poliza.nombre_poliza,
	
	((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			poliza.id_poliza
	END))) AS nro_unidades_vendidas,
	
	((-1) * COUNT(poliza.id_poliza)) AS nro_unidades_vendidas_acumulado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			)
	END))) AS monto_asegurado,
	
	((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			))) AS monto_asegurado_acumulado
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	INNER JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza) %s
GROUP BY poliza.id_poliza

ORDER BY 2;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
$rsSeguros = mysql_query($querySeguros);
if (!$rsSeguros) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowSeguros = mysql_fetch_assoc($rsSeguros)) {
	$existe = false;
	if (isset($arraySeguros)) {
		foreach ($arraySeguros as $indice => $valor) {
			if ($arraySeguros[$indice][0] == $rowSeguros['nombre_poliza']) {
				$existe = true;
				
				$arraySeguros[$indice][1] += round($rowSeguros['nro_unidades_vendidas'],2);
				$arraySeguros[$indice][2] += round($rowSeguros['nro_unidades_vendidas_acumulado'],2);
				$arraySeguros[$indice][3] += round($rowSeguros['monto_asegurado'],2);
				$arraySeguros[$indice][4] += round($rowSeguros['monto_asegurado_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arraySeguros[] = array(
			$rowSeguros['nombre_poliza'],
			round($rowSeguros['nro_unidades_vendidas'],2),
			round($rowSeguros['nro_unidades_vendidas_acumulado'],2),
			round($rowSeguros['monto_asegurado'],2),
			round($rowSeguros['monto_asegurado_acumulado'],2));
	}
	
	$arrayTotalSeguros[1] += round($rowSeguros['nro_unidades_vendidas'],2);
	$arrayTotalSeguros[2] += round($rowSeguros['nro_unidades_vendidas_acumulado'],2);
	$arrayTotalSeguros[3] += round($rowSeguros['monto_asegurado'],2);
	$arrayTotalSeguros[4] += round($rowSeguros['monto_asegurado_acumulado'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTA POR EMPRESA
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
AND cxc_nc.idDepartamentoNotaCredito IN (2)
AND cxc_nc.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
	AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
		
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
	AND YEAR(cxc_nc.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// VENTA POR EMPRESA
$queryVentaEmpresa = sprintf("SELECT
	cxc_fact.id_empresa,
	uni_fis.id_condicion_unidad,
	cond_unidad.descripcion AS condicion_unidad,
	
	COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN 
			cxc_fact.idFactura
	END)) AS nro_unidades_vendidas,
	
	COUNT(cxc_fact.idFactura) AS nro_unidades_vendidas_acumulado,
	
	SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
		WHEN %s THEN
		IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)
	END)) AS monto_facturado_vehiculo,
	
	SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)) AS monto_facturado_vehiculo_acumulado,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
	INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
GROUP BY cxc_fact.id_empresa, uni_fis.id_condicion_unidad

UNION

SELECT 
	cxc_nc.id_empresa,
	uni_fis.id_condicion_unidad,
	cond_unidad.descripcion AS condicion_unidad,
	
	((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
			cxc_nc.idNotaCredito
	END))) AS nro_unidades_vendidas,
	
	((-1) * COUNT(cxc_nc.idNotaCredito)) AS nro_unidades_vendidas_acumulado,
	
	((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
		WHEN %s THEN
		IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			)
	END))) AS monto_facturado_vehiculo,
	
	((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			))) AS monto_facturado_vehiculo_acumulado,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
	INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
	INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
GROUP BY cxc_nc.id_empresa, uni_fis.id_condicion_unidad

ORDER BY 3,8;",
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq,
	valTpDato($valFecha[0], "date"),
	valTpDato($valFecha[0], "date"),
	$sqlBusq2);
//var_dump($queryVentaEmpresa);
$rsVentaEmpresa = mysql_query($queryVentaEmpresa);
if (!$rsVentaEmpresa) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$arrayVentaEmpresa = array();
while ($rowVentaEmpresa = mysql_fetch_assoc($rsVentaEmpresa)) {
	$existe = false;
	$arrayDetalleEmpresa = NULL;
	if (isset($arrayVentaEmpresa)) {
		foreach ($arrayVentaEmpresa as $indice => $valor) {
			if ($arrayVentaEmpresa[$indice]['id_condicion_unidad'] == $rowVentaEmpresa['id_condicion_unidad']) {
				$existe = true;
				
				$existeEmpresa = false;
				$arrayDetalleEmpresa = NULL;
				if (isset($arrayVentaEmpresa[$indice]['array_empresa'])) {
					foreach ($arrayVentaEmpresa[$indice]['array_empresa'] as $indice2 => $valor2) {
						$arrayDetalleEmpresa = $valor2;
						if ($arrayDetalleEmpresa['id_empresa'] == $rowVentaEmpresa['id_empresa']) {
							$existeEmpresa = true;
							
							$arrayDetalleEmpresa['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
							$arrayDetalleEmpresa['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
							$arrayDetalleEmpresa['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
							$arrayDetalleEmpresa['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
						}
						
						$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['nro_unidades_vendidas'] = round($arrayDetalleEmpresa['nro_unidades_vendidas'],2);
						$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['nro_unidades_vendidas_acumulado'] = round($arrayDetalleEmpresa['nro_unidades_vendidas_acumulado'],2);
						$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['monto_facturado_vehiculo'] = round($arrayDetalleEmpresa['monto_facturado_vehiculo'],2);
						$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['monto_facturado_vehiculo_acumulado'] = round($arrayDetalleEmpresa['monto_facturado_vehiculo_acumulado'],2);
					}
				}
				
				if ($existeEmpresa == false) {
					$arrayDetalleEmpresa = array(
						"id_empresa" => $rowVentaEmpresa['id_empresa'],
						"nombre_empresa" => $rowVentaEmpresa['nombre_empresa'],
						"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
						"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
						"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
						"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
					
					$arrayVentaEmpresa[$indice]['array_empresa'][] = $arrayDetalleEmpresa;
				}
				
				$arrayVentaEmpresa[$indice]['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
				$arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
				$arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
				$arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
			}
		}
	}
		
	if ($existe == false) {
		$arrayDetalleEmpresa[] = array(
			"id_empresa" => $rowVentaEmpresa['id_empresa'],
			"nombre_empresa" => $rowVentaEmpresa['nombre_empresa'],
			"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
			"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
			"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
			"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
	
		$arrayVentaEmpresa[] = array(
			"id_condicion_unidad" => $rowVentaEmpresa['id_condicion_unidad'],
			"condicion_unidad" => $rowVentaEmpresa['condicion_unidad'],
			"array_empresa" => $arrayDetalleEmpresa,
			"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
			"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
			"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
			"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
	}
	
	$arrayTotalVentaEmpresa['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
	$arrayTotalVentaEmpresa['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
	$arrayTotalVentaEmpresa['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
	$arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE VENTA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// RESUMEN MENSUAL
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("RESUMEN MENSUAL (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("")), 33, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Cant. de")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto de")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// RESUMEN MENSUAL
if (isset($arrayVentaMensual)) {
	foreach ($arrayVentaMensual as $indice => $valor) {
		$porcParticipacion = ($arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'] > 0) ? (($arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado']) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
			$arrayFila = array(
				($arrayVentaMensual[$indice]['condicion_unidad']),
				round($arrayVentaMensual[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'],2),						
				round($arrayVentaMensual[$indice]['monto_facturado_vehiculo'],2),
				round($arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'],2),
				round($porcParticipacion,2));
		}else{
			$arrayFila = array(
				($arrayVentaMensual[$indice]['condicion_unidad']),
				round($arrayVentaMensual[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'],2),						
				round($arrayVentaMensual[$indice]['monto_facturado_vehiculo'],2),
				round($arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'],2),
				round($porcParticipacion,2));
		}
		//////////////////////////////////////////////////////////////////////////////////////////		
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper((substr($arrayFila[0],0,33))), 33, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaMensual['porcentaje_participacion'] += $porcParticipacion;
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL RESUMEN MENSUAL")), 33, " ", STR_PAD_RIGHT).":",$textColor);
imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['nro_unidades_vendidas'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);

//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES//////////////////////////////////////////////////////////////
if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){			
	imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['monto_facturado_vehiculo'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['porcentaje_participacion'],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
}else{			
	imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['monto_facturado_vehiculo'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaMensual['porcentaje_participacion'],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS POR ASESOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($arrayVentaAsesor)) {
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	foreach ($arrayVentaAsesor as $indice => $valor) {
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagefilledrectangle($img, 0, $posY-4, 760, $posY+4+10, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']), 152, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Asesor")), 33, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Cant. Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Venta Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Venta Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		if (isset($arrayVentaAsesor[$indice]['array_asesor'])) {
			foreach ($arrayVentaAsesor[$indice]['array_asesor'] as $indice2 => $valor2) {
				$porcParticipacion = ($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] > 0) ? (($valor2['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado']) : 0;
				
				
				//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
				if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
					if ($valor2['fecha'] <= '2018-08-20') {

					$arrayFila = array(
						utf8_encode($valor2['nombre_empleado']),
						round($valor2['nro_unidades_vendidas'],2),
						round($valor2['nro_unidades_vendidas_acumulado'],2),
						round($valor2['monto_facturado_vehiculo']/100000,2),
						round($valor2['monto_facturado_vehiculo_acumulado']/100000,2),
						round($porcParticipacion,2));

						}else{
								$arrayFila = array(
						utf8_encode($valor2['nombre_empleado']),
						round($valor2['nro_unidades_vendidas'],2),
						round($valor2['nro_unidades_vendidas_acumulado'],2),
						round($valor2['monto_facturado_vehiculo'],2),
						round($valor2['monto_facturado_vehiculo_acumulado'],2),
						round($porcParticipacion,2));

						}
				}else{
					$arrayFila = array(
						utf8_encode($valor2['nombre_empleado']),
						round($valor2['nro_unidades_vendidas'],2),
						round($valor2['nro_unidades_vendidas_acumulado'],2),
						round($valor2['monto_facturado_vehiculo'],2),
						round($valor2['monto_facturado_vehiculo_acumulado'],2),
						round($porcParticipacion,2));
				}
				//////////////////////////////////////////////////////////////////////////////////////////
				
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,33)), 33, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
			}
		}
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$porcParticipacion = ($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] > 0) ? (($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado']) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
			if ($arrayVentaAsesor[$indice]['fecha'] <= '2018-08-20') {

			$arrayFila = array(
				utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']),
				round($arrayVentaAsesor[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'],2),
				round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo']/100000,2),
				round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado']/100000,2),
				round($porcParticipacion,2));
			}else{
				$arrayFila = array(
				utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']),
				round($arrayVentaAsesor[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'],2),
				round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo'],2),
				round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'],2),
				round($porcParticipacion,2));
			}
		}else{
			$arrayFila = array(
				utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']),
				round($arrayVentaAsesor[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'],2),
				round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo'],2),
				round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'],2),
				round($porcParticipacion,2));
		}		
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Total Facturación ".substr($arrayFila[0],0,33))), 33, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaAsesor['porcentaje_participacion'] += $porcParticipacion;
		$totalventa= $arrayFila[3];
		$totalventaacumulada= $arrayFila[4];
	}

	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL VENTAS POR ASESOR")), 33, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaAsesor['nro_unidades_vendidas'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaAsesor['nro_unidades_vendidas_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	
	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){	
			imagestring($img,1,440,$posY,str_pad(formatoNumero(round($totalventa,2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,575,$posY,str_pad(formatoNumero(round($totalventaacumulada,2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaAsesor['porcentaje_participacion'],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
	}else{					
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaAsesor['monto_facturado_vehiculo'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaAsesor['porcentaje_participacion'],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ACCESORIOS INSTALADOS POR ASESOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($arrayVentaAccesorio)) {
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("ACCESORIOS INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 98, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 98, "-", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Asesor")), 33, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Monto Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Monto Venta Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 98, "-", STR_PAD_BOTH),$textColor);

	foreach ($arrayVentaAccesorio as $indice => $valor) {
		$porcParticipacion = ($arrayTotalVentaAccesorio[2] > 0) ? (($arrayVentaAccesorio[$indice][2] * 100) / $arrayTotalVentaAccesorio[2]) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){

			$arrayFila = array(
			($arrayVentaAccesorio[$indice][0]),
			round($arrayVentaAccesorio[$indice][1],2),
			round($arrayVentaAccesorio[$indice][2],2));
		}else{		
			$arrayFila = array(
				($arrayVentaAccesorio[$indice][0]),
				round($arrayVentaAccesorio[$indice][1],2),
				round($arrayVentaAccesorio[$indice][2],2));
		}
		/////////////////////////////////////////////////////////////////////////////////////////
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,33)), 33, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($porcParticipacion, 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaAccesorio[3] += $porcParticipacion;
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 98, "=", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode(substr("TOTAL ACCESORIOS INSTALADOS POR ASESOR",0,33))), 33, " ", STR_PAD_RIGHT).":",$textColor);
	
	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES//////////////////////////////////////////////////////////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
		imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaAccesorio[1],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaAccesorio[2],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}else{
		imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaAccesorio[1],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaAccesorio[2],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaAccesorio[3],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
}

$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ADICIONALES INSTALADOS POR ASESOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($arrayVentaAdicional)) {
	$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	$backgroundGris = imagecolorallocate($img, 230, 230, 230);
	$posY = 0;
	
	imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE VENTA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("ADICIONALES INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	foreach ($arrayVentaAdicional as $indice => $valor) {
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagefilledrectangle($img, 0, $posY-4, 760, $posY+4+10, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(strtoupper($arrayVentaAdicional[$indice]['nombre_empleado']), 152, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Adicional")), 60, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Venta Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		if (isset($arrayVentaAdicional[$indice]['array_adicional'])) {
			foreach ($arrayVentaAdicional[$indice]['array_adicional'] as $indice2 => $valor2) {
				$porcParticipacion = ($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? (($valor2['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado']) : 0;
				
				//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES//
				if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){	
					if ($valor2['fecha'] <= '2018-08-20') {
					
					$arrayFila = array(
						utf8_encode($valor2['nom_accesorio']),
						round($valor2['cant_facturado_accesorio'],2),
						round($valor2['monto_facturado_accesorio']/100000,2),
						round($valor2['monto_facturado_accesorio_acumulado']/100000,2),
						round($porcParticipacion,2));
				}else{
					$arrayFila = array(
						utf8_encode($valor2['nom_accesorio']),
						round($valor2['cant_facturado_accesorio'],2),
						round($valor2['monto_facturado_accesorio'],2),
						round($valor2['monto_facturado_accesorio_acumulado'],2),
						round($porcParticipacion,2));
				}

				}else{
					$arrayFila = array(
						utf8_encode($valor2['nom_accesorio']),
						round($valor2['cant_facturado_accesorio'],2),
						round($valor2['monto_facturado_accesorio'],2),
						round($valor2['monto_facturado_accesorio_acumulado'],2),
						round($porcParticipacion,2));
				}
				////////////////////////////////////////////////////////////////////////////////////////
				
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,60)), 60, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
			}
		}
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$porcParticipacion = ($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? (($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado']) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES///////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
					if ($arrayVentaAdicional[$indice]['fecha'] <= '2018-08-20') {

			$arrayFila = array(
				($arrayVentaAdicional[$indice]['nombre_empleado']),
				round($arrayVentaAdicional[$indice]['cant_facturado_accesorio'],2),
				round($arrayVentaAdicional[$indice]['monto_facturado_accesorio']/100000,2),
				round($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado']/100000,2),
				round($porcParticipacion,2));
				}else{
					$arrayFila = array(
						($arrayVentaAdicional[$indice]['nombre_empleado']),
						round($arrayVentaAdicional[$indice]['cant_facturado_accesorio'],2),
						round($arrayVentaAdicional[$indice]['monto_facturado_accesorio'],2),
						round($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'],2),
						round($porcParticipacion,2));
				}
		}else{
			$arrayFila = array(
				($arrayVentaAdicional[$indice]['nombre_empleado']),
				round($arrayVentaAdicional[$indice]['cant_facturado_accesorio'],2),
				round($arrayVentaAdicional[$indice]['monto_facturado_accesorio'],2),
				round($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'],2),
				round($porcParticipacion,2));
		}
		/////////////////////////////////////////////////////////////////////////////////////////////
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr(utf8_decode("Total Facturación ").$arrayFila[0],0,60)), 60, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaAdicional['porcentaje_participacion'] += $porcParticipacion;
		$monto_facturado_accesorio += $arrayFila[2];
		$monto_facturado_accesorio_acumulado += $arrayFila[3];
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL ADICIONALES INSTALADOS POR ASESOR")), 60, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaAdicional['cant_facturado_accesorio'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	
	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES//////////////////////////////////////////////////////////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($monto_facturado_accesorio,2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($monto_facturado_accesorio_acumulado,2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}else{
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaAdicional['monto_facturado_accesorio'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaAdicional['porcentaje_participacion'],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
	
	$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE VENTA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS POR MODELO DE VEHÍCULO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS POR MODELO DE VEHÍCULO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);

// VENTAS POR MODELO DE VEHÍCULO
if (isset($arrayVentaModelo)) {
	foreach ($arrayVentaModelo as $indice => $valor) {
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagefilledrectangle($img, 0, $posY-4, 760, $posY+4+10, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']), 152, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Modelo")), 64, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,320,$posY,str_pad(strtoupper(utf8_decode("Cant. Mes")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,420,$posY,str_pad(strtoupper(utf8_decode("Cant. Acumulada")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,520,$posY,str_pad(strtoupper(utf8_decode("Monto Mes")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,620,$posY,str_pad(strtoupper(utf8_decode("Monto Acumulado")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,720,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		if (isset($arrayVentaModelo[$indice]['array_modelo'])) {
			foreach ($arrayVentaModelo[$indice]['array_modelo'] as $indice2 => $valor2) {
				$porcParticipacion = ($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? (($valor2['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado']) : 0;
				
				
				//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES///////
				if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
					$arrayFila = array(
						utf8_encode($valor2['vehiculo']),
						round($valor2['cant_unidades_vendidas'],2),
						round($valor2['cant_unidades_vendidas_acumulado'],2),
						round($valor2['monto_unidades_vendidas'],2),
						round($valor2['monto_unidades_vendidas_acumulado'],2),
						round($porcParticipacion,2));

				}else{
					$arrayFila = array(
						utf8_encode($valor2['vehiculo']),
						round($valor2['cant_unidades_vendidas'],2),
						round($valor2['cant_unidades_vendidas_acumulado'],2),
						round($valor2['monto_unidades_vendidas'],2),
						round($valor2['monto_unidades_vendidas_acumulado'],2),
						round($porcParticipacion,2));
				}
				/////////////////////////////////////////////////////////////////////////////////////////////
				
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,64)), 64, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,320,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,420,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,520,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,620,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,720,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 8, " ", STR_PAD_LEFT),$textColor);
			}
		}
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$porcParticipacion = ($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado']) : 0;
		$a[] = $arrayVentaModelo[$indice]['array_modelo'];
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES///////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){		
			//auqi iva ante de la bromaaaaaaaaaaaaaaaaa de reconvercion reconvertido var_dump(expression)
			//var_dump('asd');
			$arrayFila = array(
				utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']),
				round($arrayVentaModelo[$indice]['cant_unidades_vendidas'],2),
				round($arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'],2),
				round($arrayVentaModelo[$indice]['monto_unidades_vendidas'],2),
				round($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'],2),
				round($porcParticipacion,2));
		}else{
			$arrayFila = array(
				utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']),
				round($arrayVentaModelo[$indice]['cant_unidades_vendidas'],2),
				round($arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'],2),
				round($arrayVentaModelo[$indice]['monto_unidades_vendidas'],2),
				round($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'],2),
				round($porcParticipacion,2));
		}
		/////////////////////////////////////////////////////////////////////////////////////////////
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Total Facturación ".substr($arrayFila[0],0,64))), 64, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,320,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,420,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,520,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,620,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,720,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 8, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaModelo['porcentaje_participacion'] += $porcParticipacion;
		$monto_unidades_vendidas += $arrayFila[3]; 
		$monto_unidades_vendidas_acumulado += $arrayFila[4]; 
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("TOTAL VENTAS POR MODELO DE VEHÍCULO:"),0,64)),$textColor);
imagestring($img,1,320,$posY,str_pad(formatoNumero(round($arrayTotalVentaModelo['cant_unidades_vendidas'],2), 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,420,$posY,str_pad(formatoNumero(round($arrayTotalVentaModelo['cant_unidades_vendidas_acumulado'],2), 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);

//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES//////////////////////////////////////////////////////////////
if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){		
	imagestring($img,1,520,$posY,str_pad(formatoNumero(round($monto_unidades_vendidas,2), 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,620,$posY,str_pad(formatoNumero(round($monto_unidades_vendidas_acumulado,2), 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
}else{
	imagestring($img,1,520,$posY,str_pad(formatoNumero(round($arrayTotalVentaModelo['monto_unidades_vendidas'],2), 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,620,$posY,str_pad(formatoNumero(round($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'],2), 1, $nroDecimales), 20, " ", STR_PAD_LEFT),$textColor);
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

imagestring($img,1,720,$posY,str_pad(formatoNumero(round($arrayTotalVentaModelo['porcentaje_participacion'],2), 1, $nroDecimales), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$posY = 0;
/////////////////////////////////////////////////////7sdasd
imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE VENTA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTA POR EMPRESA
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// VENTAS POR EMPRESA
if (isset($arrayVentaEmpresa)) {
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS POR EMPRESA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	foreach ($arrayVentaEmpresa as $indice => $valor) {
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagefilledrectangle($img, 0, $posY-4, 760, $posY+4+10, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']), 152, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Empresa")), 33, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Cant. Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Venta Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto Venta de")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Venta Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		if (isset($arrayVentaEmpresa[$indice]['array_empresa'])) {
			foreach ($arrayVentaEmpresa[$indice]['array_empresa'] as $indice2 => $valor2) {
				$porcParticipacion = ($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? (($valor2['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado']) : 0;				
				
				//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES///
				if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
					$arrayFila = array(
						utf8_encode($valor2['nombre_empresa']),
						round($valor2['nro_unidades_vendidas'],2),
						round($valor2['nro_unidades_vendidas_acumulado'],2),
						round($valor2['monto_facturado_vehiculo'],2),
						round($valor2['monto_facturado_vehiculo_acumulado'],2),
						round($porcParticipacion,2));
				}else{
					$arrayFila = array(
						utf8_encode($valor2['nombre_empresa']),
						round($valor2['nro_unidades_vendidas'],2),
						round($valor2['nro_unidades_vendidas_acumulado'],2),
						round($valor2['monto_facturado_vehiculo'],2),
						round($valor2['monto_facturado_vehiculo_acumulado'],2),
						round($porcParticipacion,2));
				}
				///////////////////////////////////////////////////////////////////////////////////////////					
				
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,33)), 33, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
			}
		}
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		
		$porcParticipacion = ($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? (($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado']) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
			$arrayFila = array(
				utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']),
				round($arrayVentaEmpresa[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'],2),
				round($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'],2),
				round($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'],2),
				round($porcParticipacion,2));
		}else{
			$arrayFila = array(
				utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']),
				round($arrayVentaEmpresa[$indice]['nro_unidades_vendidas'],2),
				round($arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'],2),
				round($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'],2),
				round($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'],2),
				round($porcParticipacion,2));
		}
		/////////////////////////////////////////////////////////////////////////////////////////
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Total Facturación ".substr($arrayFila[0],0,33))), 33, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($arrayFila[5], 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaEmpresa['porcentaje_participacion'] += $porcParticipacion;
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL VENTAS POR EMPRESA")), 33, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['nro_unidades_vendidas'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['nro_unidades_vendidas_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	
	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES/////////////////////////////////////////////////////////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){	
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['monto_facturado_vehiculo'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}else{
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['monto_facturado_vehiculo'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaEmpresa['porcentaje_participacion'],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// OPERACIONES A CONTADO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($arrayVentaContado)) {
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("OPERACIONES A CONTADO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
		
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("")), 33, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Cant. Contado de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Contado Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto Contado de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Contado Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	
	foreach ($arrayVentaContado as $indice => $valor) {
		$porcParticipacion = ($arrayTotalVentaContado[4] > 0) ? (($arrayVentaContado[$indice][4] * 100) / $arrayTotalVentaContado[4]) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
			$arrayFila = array(
				utf8_encode($arrayVentaContado[$indice][0]),
				round($arrayVentaContado[$indice][1],2),
				round($arrayVentaContado[$indice][2],2),
				round($arrayVentaContado[$indice][3],2),
				round($arrayVentaContado[$indice][4],2));
		}else{
			$arrayFila = array(
				utf8_encode($arrayVentaContado[$indice][0]),
				round($arrayVentaContado[$indice][1],2),
				round($arrayVentaContado[$indice][2],2),
				round($arrayVentaContado[$indice][3],2),
				round($arrayVentaContado[$indice][4],2));
		}
		///////////////////////////////////////////////////////////////////////////////////////////
				
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,32)), 33, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($porcParticipacion, 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaContado[5] += $porcParticipacion;
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL OPERACIONES A CONTADO")), 33, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[1],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[2],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	
	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[3],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[4],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}else{
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[3],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[4],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);		
	}	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaContado[5],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
}

$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// OPERACIONES A CRÉDITO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($arrayVentaCredito)) {
	$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	$backgroundGris = imagecolorallocate($img, 230, 230, 230);
	$posY = 0;
	
	imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE VENTA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("OPERACIONES A CRÉDITO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Banco")), 33, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Cant. Financiado de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Financiado Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto Financiado de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Financiado Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	
	foreach ($arrayVentaCredito as $indice => $valor) {
		$porcParticipacion = ($arrayTotalVentaCredito[4] > 0) ? (($arrayVentaCredito[$indice][4] * 100) / $arrayTotalVentaCredito[4]) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
			$arrayFila = array(
				utf8_encode($arrayVentaCredito[$indice][0]),
				round($arrayVentaCredito[$indice][1],2),
				round($arrayVentaCredito[$indice][2],2),
				round($arrayVentaCredito[$indice][3],2),
				round($arrayVentaCredito[$indice][4],2));
		}else{
			$arrayFila = array(
				utf8_encode($arrayVentaCredito[$indice][0]),
				round($arrayVentaCredito[$indice][1],2),
				round($arrayVentaCredito[$indice][2],2),
				round($arrayVentaCredito[$indice][3],2),
				round($arrayVentaCredito[$indice][4],2));
		}
		//////////////////////////////////////////////////////////////////////////////////////////
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,33)), 33, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($porcParticipacion, 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalVentaCredito[5] += $porcParticipacion;
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL OPERACIONES A CRÉDITO")), 33, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[1],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[2],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	
	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[3],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[4],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}else{
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[3],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[4],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalVentaCredito[5],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
	
	$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMPAÑIA DE SEGUROS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($arraySeguros)) {
	$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	$backgroundGris = imagecolorallocate($img, 230, 230, 230);
	$posY = 0;
	
	imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE VENTA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 20;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("COMPAÑIA DE SEGUROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Aseguradora")), 33, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode("Cant. de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode("Cant. Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode("Monto de")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode("Monto Acum. hasta")), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,710,$posY,str_pad(utf8_decode("%"), 10, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,170,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,305,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,440,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,575,$posY,str_pad(strtoupper(utf8_decode($mes[intval($valFecha[0])]." ".$valFecha[1])), 26, " ", STR_PAD_BOTH),$textColor);
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
	
	foreach ($arraySeguros as $indice => $valor) {
		$porcParticipacion = ($arrayTotalSeguros[4] > 0) ? (($arraySeguros[$indice][4] * 100) / $arrayTotalSeguros[4]) : 0;
		
		//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
		if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){
			$arrayFila = array(
				utf8_encode($arraySeguros[$indice][0]),
				round($arraySeguros[$indice][1],2),
				round($arraySeguros[$indice][2],2),
				round($arraySeguros[$indice][3],2),
				round($arraySeguros[$indice][4],2));
		}else{
			$arrayFila = array(
				utf8_encode($arraySeguros[$indice][0]),
				round($arraySeguros[$indice][1],2),
				round($arraySeguros[$indice][2],2),
				round($arraySeguros[$indice][3],2),
				round($arraySeguros[$indice][4],2));
		}
		///////////////////////////////////////////////////////////////////////////////////////////
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,33)), 33, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,170,$posY,str_pad(formatoNumero($arrayFila[1], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,305,$posY,str_pad(formatoNumero($arrayFila[2], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,440,$posY,str_pad(formatoNumero($arrayFila[3], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero($arrayFila[4], 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,710,$posY,str_pad(formatoNumero($porcParticipacion, 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
		
		$arrayTotalSeguros[5] += $porcParticipacion;
	}
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL COMPAÑIA DE SEGUROS")), 33, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,170,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[1],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,305,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[2],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);

	//Se agrega el filtro del reporte sin decimales o con ellos, pero expresados en MILES////
	if(($_GET['lstDecimalPDF'] == 2)||($_GET['lstDecimalPDF'] == 3)){	
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[3],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[4],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}else{
		imagestring($img,1,440,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[3],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[4],2), 1, $nroDecimales), 26, " ", STR_PAD_LEFT),$textColor);
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	imagestring($img,1,710,$posY,str_pad(formatoNumero(round($arrayTotalSeguros[5],2), 1, $nroDecimales), 10, " ", STR_PAD_LEFT),$textColor);
	
	$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
}

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
		
		$pdf->Image($valor, 15, 60, 760, 520);
	}
}

$pdf->SetDisplayMode(80);
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