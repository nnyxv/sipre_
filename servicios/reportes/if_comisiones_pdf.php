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
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
//var_dump($valCadBusq);

$idEmpresa = $valCadBusq[0];

$maxRows = 1;
$campOrd = "";
$tpOrd = "";

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((CASE
									WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
										(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
										WHERE cxc_fact.idFactura = comision_emp.id_factura)
									WHEN (id_nota_credito IS NOT NULL) THEN
										(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
										WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
									WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
										(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
										WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
									WHEN (id_vale_entrada IS NOT NULL) THEN
										(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
										WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
								END) = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = (CASE
										WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
											(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
											WHERE cxc_fact.idFactura = comision_emp.id_factura)
										WHEN (id_nota_credito IS NOT NULL) THEN
											(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
											WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
										WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
											(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
											WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
										WHEN (id_vale_entrada IS NOT NULL) THEN
											(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
											WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
									END)))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE(comision_emp.fecha_comision) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[5])), "date"));
		}


if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT cargo_dep.id_cargo
	FROM pg_cargo_departamento cargo_dep
		INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
	WHERE cargo_dep.id_cargo_departamento = empleado.id_cargo_departamento) = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision_emp.id_empleado = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = comision_emp.id_factura)
		WHEN (id_nota_credito IS NOT NULL) THEN
			(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
			WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			(SELECT 1 FROM sa_vale_salida sa_vs
			WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
		WHEN (id_vale_entrada IS NOT NULL) THEN
			(SELECT 1 FROM sa_vale_entrada sa_ve
			WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
	END) = %s",
		valTpDato($valCadBusq[4], "int"));
}

$query = sprintf("SELECT
	empleado.id_empleado,
	empleado.cedula,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	
	(CASE
		WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
			(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = comision_emp.id_factura)
		WHEN (id_nota_credito IS NOT NULL) THEN
			(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
			WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
		WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
			(SELECT 1 FROM sa_vale_salida sa_vs
			WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
		WHEN (id_vale_entrada IS NOT NULL) THEN
			(SELECT 1 FROM sa_vale_entrada sa_ve
			WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
	END) AS id_modulo,
	
	MONTH(comision_emp.fecha_comision) AS mes_comision,
	YEAR(comision_emp.fecha_comision) AS ano_comision,
	
	(SELECT GROUP_CONCAT(vw_pg_cargos.nombre_cargo SEPARATOR ', ') FROM vw_pg_cargos
	WHERE vw_pg_cargos.id_cargo_departamento = comision_emp.id_cargo_departamento) AS nombre_cargo,
	
	(SELECT (SUM(total_ut / total_ut_fisica) * 100) AS porcentaje_productividad
	FROM iv_cierre_mensual cierre_mensual
		INNER JOIN iv_cierre_mensual_facturacion cierre_mensual_fact ON (cierre_mensual.id_cierre_mensual = cierre_mensual_fact.id_cierre_mensual)
	WHERE cierre_mensual_fact.id_empleado = comision_emp.id_empleado
		AND cierre_mensual.id_empresa = (CASE
											WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
												(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
												WHERE cxc_fact.idFactura = comision_emp.id_factura)
											WHEN (id_nota_credito IS NOT NULL) THEN
												(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
												WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
											WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
												(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
												WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
											WHEN (id_vale_entrada IS NOT NULL) THEN
												(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
												WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
										END)
		AND cierre_mensual.mes = (CASE
										WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
											MONTH((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idFactura = comision_emp.id_factura))
										WHEN (id_nota_credito IS NOT NULL) THEN
											MONTH((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
										WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
											MONTH((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
													WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
										WHEN (id_vale_entrada IS NOT NULL) THEN
											MONTH((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
													WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
									END)
		AND cierre_mensual.ano = (CASE
										WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
											YEAR((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idFactura = comision_emp.id_factura))
										WHEN (id_nota_credito IS NOT NULL) THEN
											YEAR((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
										WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
											YEAR((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
													WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
										WHEN (id_vale_entrada IS NOT NULL) THEN
											YEAR((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
													WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
									END)) AS porcentaje_productividad
FROM pg_comision_empleado comision_emp
	INNER JOIN pg_empleado empleado ON (comision_emp.id_empleado = empleado.id_empleado) %s
GROUP BY 1,2,3", $sqlBusq);
$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
$rsLimit = mysql_query($queryLimit);
if (!$rsLimit) die(mysql_error()."<br><br>Line: ".__LINE__);
if ($totalRows == NULL) {
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
}
$totalPages = ceil($totalRows/$maxRows)-1;

$arrayImg = NULL;
for ($pageNum = 0; $pageNum <= $totalPages; $pageNum++) {
	$startRow = $pageNum * $maxRows;
	
	$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	
	$queryComision = $query;
	
	$queryLimitComision = sprintf("%s %s LIMIT %d OFFSET %d", $queryComision, $sqlOrd, $maxRows, $startRow);
	$rsLimitComision = mysql_query($queryLimitComision, $conex);
	if (!$rsLimitComision) die(mysql_error()."<br><br>Line: ".__LINE__);
	if ($totalRowsComision == NULL) {
		$rsComision = mysql_query($queryComision, $conex);
		if (!$rsComision) die(mysql_error()."<br><br>Line: ".__LINE__);
		$totalRowsComision = mysql_num_rows($rsComision);
	}
	$totalPages = ceil($totalRowsComision/$maxRows)-1;
	
	while ($rowComision = mysql_fetch_assoc($rsLimitComision)) {
		$maxRowsDetalle = 36;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("id_empleado = %s",
			valTpDato($rowComision['id_empleado'], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(comision_emp.venta_bruta > 0
		OR comision_emp.monto_comision > 0
		OR (SELECT AVG(comision_emp_det.porcentaje_comision) FROM pg_comision_empleado_detalle comision_emp_det
			WHERE comision_emp_det.id_comision_empleado = comision_emp.id_comision_empleado) > 0
		OR comision_emp.porcentaje_comision > 0)");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE(comision_emp.fecha_comision) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[5])), "date"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
											WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
												(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
												WHERE cxc_fact.idFactura = comision_emp.id_factura)
											WHEN (id_nota_credito IS NOT NULL) THEN
												(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
												WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
											WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
												(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
												WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
											WHEN (id_vale_entrada IS NOT NULL) THEN
												(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
												WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
										END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE
												WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
													(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idFactura = comision_emp.id_factura)
												WHEN (id_nota_credito IS NOT NULL) THEN
													(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
												WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
													(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
													WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
												WHEN (id_vale_entrada IS NOT NULL) THEN
													(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
													WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
											END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT 1 FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT 1 FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) = %s",
				valTpDato($valCadBusq[4], "int"));
		}
		
		$queryDetalle = sprintf("SELECT
			comision_emp.id_comision_empleado,
		
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT 1 FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT 1 FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS id_modulo,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					'FA'
				WHEN (id_nota_credito IS NOT NULL) THEN
					'NC'
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					'VS'
				WHEN (id_vale_entrada IS NOT NULL) THEN
					'VE'
			END) AS tipo_documento,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT sa_vs.numero_vale FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT sa_ve.numero_vale_entrada FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS numero_documento,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.condicionDePago FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_facta.condicionDePago AS condicionDePago
					FROM cj_cc_encabezadofactura cxc_facta
						INNER JOIN cj_cc_notacredito cxc_nc on (cxc_facta.idFactura = cxc_nc.idDocumento)
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT tp_ord.nombre_tipo_orden
					FROM sa_orden ord
						INNER JOIN sa_tipo_orden tp_ord ON (ord.id_tipo_orden = tp_ord.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (ord.id_orden = sa_vs.id_orden)
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT tp_ord.nombre_tipo_orden
					FROM sa_orden ord
						INNER JOIN sa_tipo_orden tp_ord ON (ord.id_tipo_orden = tp_ord.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (ord.id_orden = sa_vs.id_orden)
						INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_vs.id_vale_salida)
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS tipo_pago,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN cj_cc_encabezadofactura cxc_fact ON (orden_serv.id_orden = cxc_fact.numeroPedido)
					WHERE cxc_fact.idFactura = comision_emp.id_factura
						AND cxc_fact.idDepartamentoOrigenFactura = 1)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN cj_cc_encabezadofactura cxc_fact ON (orden_serv.id_orden = cxc_fact.numeroPedido)
						INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito
						AND cxc_fact.idDepartamentoOrigenFactura = 1
						AND cxc_nc.idDepartamentoNotaCredito = cxc_fact.idDepartamentoOrigenFactura
						AND cxc_nc.tipoDocumento LIKE 'FA')
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (orden_serv.id_orden = sa_vs.id_orden)
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (orden_serv.id_orden = sa_vs.id_orden)
						INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS nombre_tipo_orden,

			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_facta.idCliente AS idCliente
					FROM cj_cc_encabezadofactura cxc_facta
					WHERE (cxc_facta.idFactura = comision_emp.id_factura))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_facta.idCliente AS idCliente
					FROM (cj_cc_notacredito cxc_nc
						JOIN cj_cc_encabezadofactura cxc_facta on ((cxc_nc.idDocumento = cxc_facta.idFactura)))
					WHERE (cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
									FROM sa_recepcion r
									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
									FROM sa_recepcion r
									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
									INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
								WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS id_cliente,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente FROM cj_cc_encabezadofactura cxc_facta
										WHERE (cxc_facta.idFactura = comision_emp.id_factura))))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente
										FROM (cj_cc_notacredito cxc_nc
											JOIN cj_cc_encabezadofactura cxc_facta on ((cxc_nc.idDocumento = cxc_facta.idFactura)))
										WHERE (cxc_nc.idNotaCredito = comision_emp.id_nota_credito))))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
									INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
								WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
			END) AS ci_cliente,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente FROM cj_cc_encabezadofactura cxc_facta
										WHERE (cxc_facta.idFactura = comision_emp.id_factura))))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente FROM (cj_cc_notacredito cxc_nc
											JOIN cj_cc_encabezadofactura cxc_facta on ((cxc_nc.idDocumento = cxc_facta.idFactura)))
										WHERE (cxc_nc.idNotaCredito = comision_emp.id_nota_credito))))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
									INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
								WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
			END) AS nombre_cliente,
			
			comision_emp.venta_bruta,
			comision_emp.monto_descuento,
			(comision_emp.venta_bruta - comision_emp.monto_descuento) AS venta_neta,
			comision_emp.costo_compra,
			
			(comision_emp.venta_bruta
				- comision_emp.monto_descuento
				- comision_emp.costo_compra) AS utilidad_bruta,
			
			(((comision_emp.venta_bruta
				- comision_emp.monto_descuento
				- comision_emp.costo_compra) * 100) / (comision_emp.venta_bruta - comision_emp.monto_descuento)) AS porcentaje_utilidad_venta,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS fecha_documento,
			
			comision_emp.monto_comision,
			
			(SELECT AVG(comision_emp_det.porcentaje_comision) FROM pg_comision_empleado_detalle comision_emp_det
			WHERE comision_emp_det.id_comision_empleado = comision_emp.id_comision_empleado) AS promedio_porcentaje_comision,
			
			MONTH(comision_emp.fecha_comision) AS mes_comision,
			YEAR(comision_emp.fecha_comision) AS ano_comision,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(to_days((SELECT cxc_fact.fechaRegistroFactura
							FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = comision_emp.id_factura)) - to_days((SELECT uni_fis.fecha_ingreso
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_fact_det_vehic.id_factura = comision_emp.id_factura)))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(to_days((SELECT cxc_nc.fechaNotaCredito
							FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)) - to_days((SELECT uni_fis.fecha_ingreso
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_fact_det_vehic.id_factura = comision_emp.id_factura)))
			END) AS dias_inventario
		
		FROM pg_comision_empleado comision_emp %s
		ORDER BY 2,3,4", $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) die(mysql_error()."<br><br>Line: ".__LINE__);
		$totalRowsDetalle = mysql_num_rows($rsDetalle);
		$totalPagesDetalle = ceil($totalRowsDetalle/$maxRowsDetalle)-1;
		$posY += 20;
		$contFila = 0;
		$subPage = 0;
		$arrayTotalFact = NULL;
		$arrayTotalNC = NULL;
		$arrayTotalVS = NULL;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$contFila++;
			
			if (fmod($contFila, $maxRowsDetalle) == 1) {
				$subPage++;
				
				$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
				
				// ESTABLECIENDO LOS COLORES DE LA PALETA
				$backgroundColor = imagecolorallocate($img, 255, 255, 255);
				$textColor = imagecolorallocate($img, 0, 0, 0);
				
				
				$posY = 10;
				imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS DE REPUESTOS, SERVICIOS Y VEHICULOS"), 152, " ", STR_PAD_BOTH),$textColor);
				
				$posY += 10;
				imagestring($img,1,0,$posY+10,
					str_pad(utf8_decode("VENDEDOR"), 10, " ", STR_PAD_LEFT).": ".
					str_pad($rowComision['cedula'], 11, " ", STR_PAD_LEFT)." ".
					utf8_decode($rowComision['nombre_empleado']),$textColor);
				imagestring($img,1,315,$posY+10,
					str_pad(utf8_decode("% PRODUCTIVIDAD / EFICIENCIA"), 10, " ", STR_PAD_LEFT).": ".
					str_pad(number_format($rowComision['porcentaje_productividad'], 2, ".", ","), 11, " ", STR_PAD_LEFT),$textColor);
				
				imagestring($img,1,600,$posY+10,
					str_pad(utf8_decode("MES / AÑO"), 10, " ", STR_PAD_LEFT).": ".
					str_pad(utf8_decode(strtoupper($mes[$rowComision['mes_comision']]." ".$rowComision['ano_comision'])), 20, " ", STR_PAD_BOTH),$textColor);
				
				
				$posY += 20;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
				$posY += 10;
				imagestring($img,1,0,$posY,
					str_pad(utf8_decode("FOLIO FACT."), 14, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("COD. CLTE"), 13, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("CLIENTE"), 20, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("VENTA BRUTA"), 13, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("DESCUENTO"), 12, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("VENTA NETA"), 13, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("COSTO"), 13, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("UTL.BRUTA"), 13, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("%UTL.BRUTA"), 10, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("FEC. DCTO."), 10, " ", STR_PAD_BOTH)." ".
					str_pad(utf8_decode("COMISION"), 11, " ", STR_PAD_BOTH),$textColor);
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
				
				$arrayTotalRep = NULL;
				$arrayTotalServ = NULL;
				$arrayTotalVehic = NULL;
			}
			
			if ($rowDetalle['tipo_documento'] == "FA") {
				$indice = 0;
				$signo = 1;
			} else if ($rowDetalle['tipo_documento'] == "NC") {
				$indice = 1;
				$signo = (-1);
			} else if ($rowDetalle['tipo_documento'] == "VS") {
				$indice = 2;
				$signo = 1;
			}
			
			if ($rowDetalle['id_modulo'] == 0) {
				$imgModulo = "R";
				
				$arrayTotalRep[$indice][7] ++;
				$arrayTotalRep[$indice][0] += $signo * $rowDetalle['venta_bruta'];
				$arrayTotalRep[$indice][1] += $signo * $rowDetalle['monto_descuento'];
				$arrayTotalRep[$indice][2] += $signo * $rowDetalle['venta_neta'];
				$arrayTotalRep[$indice][3] += $signo * $rowDetalle['costo_compra'];
				$arrayTotalRep[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
				$arrayTotalRep[$indice][5] = ($arrayTotalRep[$indice][7] > 0) ? ($arrayTotalRep[$indice][4] * 100) / $arrayTotalRep[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalRep[$indice][6] += $signo * $rowDetalle['monto_comision'];
			} else if ($rowDetalle['id_modulo'] == 1) {
				$imgModulo = "S";
				
				$arrayTotalServ[$indice][7] ++;
				$arrayTotalServ[$indice][0] += $signo * $rowDetalle['venta_bruta'];
				$arrayTotalServ[$indice][1] += $signo * $rowDetalle['monto_descuento'];
				$arrayTotalServ[$indice][2] += $signo * $rowDetalle['venta_neta'];
				$arrayTotalServ[$indice][3] += $signo * $rowDetalle['costo_compra'];
				$arrayTotalServ[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
				$arrayTotalServ[$indice][5] = ($arrayTotalServ[$indice][7] > 0) ? ($arrayTotalServ[$indice][4] * 100) / $arrayTotalServ[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalServ[$indice][6] += $signo * $rowDetalle['monto_comision'];
			} else if ($rowDetalle['id_modulo'] == 2) {
				$imgModulo = "V";
				
				$arrayTotalVehic[$indice][7] ++;
				$arrayTotalVehic[$indice][0] += $signo * $rowDetalle['venta_bruta'];
				$arrayTotalVehic[$indice][1] += $signo * $rowDetalle['monto_descuento'];
				$arrayTotalVehic[$indice][2] += $signo * $rowDetalle['venta_neta'];
				$arrayTotalVehic[$indice][3] += $signo * $rowDetalle['costo_compra'];
				$arrayTotalVehic[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
				$arrayTotalVehic[$indice][5] = ($arrayTotalVehic[$indice][7] > 0) ? ($arrayTotalVehic[$indice][4] * 100) / $arrayTotalVehic[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalVehic[$indice][6] += $signo * $rowDetalle['monto_comision'];
			}
			
			
			$ventaBruta = number_format($signo * $rowDetalle['venta_bruta'],2,".",",");
			$descuento = number_format($signo * $rowDetalle['monto_descuento'],2,".",",");
			$ventaNeta = number_format($signo * $rowDetalle['venta_neta'],2,".",",");
			$costoBruto = number_format($signo * $rowDetalle['costo_compra'],2,".",",");
			$utilidadBruta = number_format($signo * $rowDetalle['utilidad_bruta'],2,".",",");
			$porcUtilidadBruta = number_format($signo * $rowDetalle['porcentaje_utilidad_venta'],2,".",",");
			$montoComision = number_format($signo * $rowDetalle['monto_comision'],2,".",",");
			
			$posY += 10;
			imagestring($img,1,0,$posY,$imgModulo,$textColor);
			imagestring($img,1,10,$posY,$rowDetalle['tipo_documento'],$textColor);
			imagestring($img,1,25,$posY,$rowDetalle['numero_documento'],$textColor);
			imagestring($img,1,75,$posY,str_pad($rowDetalle['ci_cliente'], 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,145,$posY,strtoupper(substr($rowDetalle['nombre_cliente'],0,20)),$textColor);
			imagestring($img,1,250,$posY,str_pad($ventaBruta, 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,320,$posY,str_pad($descuento, 12, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,385,$posY,str_pad($ventaNeta, 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,455,$posY,str_pad($costoBruto, 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,525,$posY,str_pad($utilidadBruta, 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,595,$posY,str_pad($porcUtilidadBruta, 10, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,650,$posY,date(spanDateFormat, strtotime($rowDetalle['fecha_documento'])),$textColor);
			imagestring($img,1,705,$posY,str_pad($montoComision, 11, " ", STR_PAD_LEFT),$textColor);
			
			
			
			// ULTIMA LINEA DE TOTALES POR HOJA
			if (fmod($contFila, $maxRowsDetalle) == 0 || $contFila == $totalRowsDetalle) {
				$posY += 10;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
				
				// FACTURA
				if ($arrayTotalRep[0][7] > 0 || $arrayTotalServ[0][7] > 0 || $arrayTotalVehic[0][7] > 0) {
					if ($arrayTotalRep[0][7] > 0) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURAS:"), 28, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,145,$posY,utf8_decode("R"),$textColor);
						imagestring($img,1,155,$posY,str_pad($arrayTotalRep[0][7], 18, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalRep[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalRep[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalRep[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalRep[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalRep[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalRep[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalRep[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
					}
					
					if ($arrayTotalServ[0][7] > 0) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURAS:"), 28, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,145,$posY,utf8_decode("S"),$textColor);
						imagestring($img,1,155,$posY,str_pad($arrayTotalServ[0][7], 18, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalServ[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalServ[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalServ[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalServ[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalServ[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalServ[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalServ[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
					}
					
					if ($arrayTotalVehic[0][7] > 0) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURAS:"), 28, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,145,$posY,utf8_decode("V"),$textColor);
						imagestring($img,1,155,$posY,str_pad($arrayTotalVehic[0][7], 18, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalVehic[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalVehic[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalVehic[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalVehic[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalVehic[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalVehic[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalVehic[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
					}
				}
				
				// NOTA CREDITO
				if ($arrayTotalRep[1][7] > 0 || $arrayTotalServ[1][7] || $arrayTotalVehic[1][7] > 0) {
					if (($arrayTotalRep[0][7] > 0 || $arrayTotalServ[0][7] > 0 || $arrayTotalVehic[0][7] > 0)) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
					}
					
					if ($arrayTotalRep[1][7] > 0) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL NOTAS CRÉD.:"), 28, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,145,$posY,utf8_decode("R"),$textColor);
						imagestring($img,1,155,$posY,str_pad($arrayTotalRep[1][7], 18, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalRep[1][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalRep[1][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalRep[1][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalRep[1][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalRep[1][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalRep[1][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalRep[1][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
					}
					
					if ($arrayTotalServ[1][7] > 0) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL NOTAS CRÉD.:"), 28, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,145,$posY,utf8_decode("S"),$textColor);
						imagestring($img,1,155,$posY,str_pad($arrayTotalServ[1][7], 18, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalServ[1][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalServ[1][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalServ[1][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalServ[1][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalServ[1][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalServ[1][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalServ[1][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
					}
					
					if ($arrayTotalVehic[1][7] > 0) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL NOTAS CRÉD.:"), 28, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,145,$posY,utf8_decode("V"),$textColor);
						imagestring($img,1,155,$posY,str_pad($arrayTotalVehic[1][7], 18, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalVehic[1][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalVehic[1][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalVehic[1][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalVehic[1][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalVehic[1][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalVehic[1][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
						imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalVehic[1][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
					}
				}
				
				// VALE SALIDA
				if ($arrayTotalServ[2][7] > 0) {
					if (($arrayTotalRep[0][7] > 0 || $arrayTotalServ[0][7] > 0 || $arrayTotalVehic[0][7] > 0 || $arrayTotalRep[1][7] > 0 || $arrayTotalServ[1][7] || $arrayTotalVehic[1][7])) {
						$posY += 10;
						imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
					}
					
					$posY += 10;
					imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL VALES SALIDA:"), 28, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,145,$posY,utf8_decode("S"),$textColor);
					imagestring($img,1,155,$posY,str_pad($arrayTotalServ[2][7], 18, " ", STR_PAD_BOTH),$textColor);
					imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalServ[2][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalServ[2][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalServ[2][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalServ[2][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalServ[2][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalServ[2][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalServ[2][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
				}
				
				// FACTURAS
				// REPUESTOS
				$arrayTotalFact[0][7] += $arrayTotalRep[0][7];
				$arrayTotalFact[0][0] += $arrayTotalRep[0][0];
				$arrayTotalFact[0][1] += $arrayTotalRep[0][1];
				$arrayTotalFact[0][2] += $arrayTotalRep[0][2];
				$arrayTotalFact[0][3] += $arrayTotalRep[0][3];
				$arrayTotalFact[0][4] += $arrayTotalRep[0][4];
				$arrayTotalFact[0][5] = ($arrayTotalFact[0][7] > 0) ? ($arrayTotalFact[0][4] * 100) / $arrayTotalFact[0][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalFact[0][6] += $arrayTotalRep[0][6];
				
				// SERVICIOS
				$arrayTotalFact[1][7] += $arrayTotalServ[0][7];
				$arrayTotalFact[1][0] += $arrayTotalServ[0][0];
				$arrayTotalFact[1][1] += $arrayTotalServ[0][1];
				$arrayTotalFact[1][2] += $arrayTotalServ[0][2];
				$arrayTotalFact[1][3] += $arrayTotalServ[0][3];
				$arrayTotalFact[1][4] += $arrayTotalServ[0][4];
				$arrayTotalFact[1][5] = ($arrayTotalFact[1][7] > 0) ? ($arrayTotalFact[1][4] * 100) / $arrayTotalFact[1][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalFact[1][6] += $arrayTotalServ[0][6];
				
				// VEHICULOS
				$arrayTotalFact[2][7] += $arrayTotalVehic[0][7];
				$arrayTotalFact[2][0] += $arrayTotalVehic[0][0];
				$arrayTotalFact[2][1] += $arrayTotalVehic[0][1];
				$arrayTotalFact[2][2] += $arrayTotalVehic[0][2];
				$arrayTotalFact[2][3] += $arrayTotalVehic[0][3];
				$arrayTotalFact[2][4] += $arrayTotalVehic[0][4];
				$arrayTotalFact[2][5] = ($arrayTotalFact[2][7]) ? ($arrayTotalFact[2][4] * 100) / $arrayTotalFact[2][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalFact[2][6] += $arrayTotalVehic[0][6];
				
				// TOTAL FACTURAS
				$arrayTotalFact[3][7] = $arrayTotalFact[0][7] + $arrayTotalFact[1][7] + $arrayTotalFact[2][7];
				$arrayTotalFact[3][0] = $arrayTotalFact[0][0] + $arrayTotalFact[1][0] + $arrayTotalFact[2][0];
				$arrayTotalFact[3][1] = $arrayTotalFact[0][1] + $arrayTotalFact[1][1] + $arrayTotalFact[2][1];
				$arrayTotalFact[3][2] = $arrayTotalFact[0][2] + $arrayTotalFact[1][2] + $arrayTotalFact[2][2];
				$arrayTotalFact[3][3] = $arrayTotalFact[0][3] + $arrayTotalFact[1][3] + $arrayTotalFact[2][3];
				$arrayTotalFact[3][4] = $arrayTotalFact[0][4] + $arrayTotalFact[1][4] + $arrayTotalFact[2][4];
				$arrayTotalFact[3][5] = ($arrayTotalFact[3][7] > 0) ? ($arrayTotalFact[3][4] * 100) / $arrayTotalFact[3][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalFact[3][6] = $arrayTotalFact[0][6] + $arrayTotalFact[1][6] + $arrayTotalFact[2][6];
				
				
				
				// NOTAS DE CREDITO
				// REPUESTOS
				$arrayTotalNC[0][7] += $arrayTotalRep[1][7];
				$arrayTotalNC[0][0] += $arrayTotalRep[1][0];
				$arrayTotalNC[0][1] += $arrayTotalRep[1][1];
				$arrayTotalNC[0][2] += $arrayTotalRep[1][2];
				$arrayTotalNC[0][3] += $arrayTotalRep[1][3];
				$arrayTotalNC[0][4] += $arrayTotalRep[1][4];
				$arrayTotalNC[0][5] = ($arrayTotalNC[0][7] > 0) ? ($arrayTotalNC[0][4] * 100) / $arrayTotalNC[0][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalNC[0][6] += $arrayTotalRep[1][6];
				
				// SERVICIOS
				$arrayTotalNC[1][7] += $arrayTotalServ[1][7];
				$arrayTotalNC[1][0] += $arrayTotalServ[1][0];
				$arrayTotalNC[1][1] += $arrayTotalServ[1][1];
				$arrayTotalNC[1][2] += $arrayTotalServ[1][2];
				$arrayTotalNC[1][3] += $arrayTotalServ[1][3];
				$arrayTotalNC[1][4] += $arrayTotalServ[1][4];
				$arrayTotalNC[1][5] = ($arrayTotalNC[1][7] > 0) ? ($arrayTotalNC[1][4] * 100) / $arrayTotalNC[1][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalNC[1][6] += $arrayTotalServ[1][6];
				
				// VEHICULOS
				$arrayTotalNC[2][7] += $arrayTotalVehic[1][7];
				$arrayTotalNC[2][0] += $arrayTotalVehic[1][0];
				$arrayTotalNC[2][1] += $arrayTotalVehic[1][1];
				$arrayTotalNC[2][2] += $arrayTotalVehic[1][2];
				$arrayTotalNC[2][3] += $arrayTotalVehic[1][3];
				$arrayTotalNC[2][4] += $arrayTotalVehic[1][4];
				$arrayTotalNC[2][5] = ($arrayTotalNC[2][7] > 0) ? ($arrayTotalNC[2][4] * 100) / $arrayTotalNC[2][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalNC[2][6] += $arrayTotalVehic[1][6];
				
				// TOTAL NOTAS DE CREDITO
				$arrayTotalNC[3][7] = $arrayTotalNC[0][7] + $arrayTotalNC[1][7] + $arrayTotalNC[2][7];
				$arrayTotalNC[3][0] = $arrayTotalNC[0][0] + $arrayTotalNC[1][0] + $arrayTotalNC[2][0];
				$arrayTotalNC[3][1] = $arrayTotalNC[0][1] + $arrayTotalNC[1][1] + $arrayTotalNC[2][1];
				$arrayTotalNC[3][2] = $arrayTotalNC[0][2] + $arrayTotalNC[1][2] + $arrayTotalNC[2][2];
				$arrayTotalNC[3][3] = $arrayTotalNC[0][3] + $arrayTotalNC[1][3] + $arrayTotalNC[2][3];
				$arrayTotalNC[3][4] = $arrayTotalNC[0][4] + $arrayTotalNC[1][4] + $arrayTotalNC[2][4];
				$arrayTotalNC[3][5] = ($arrayTotalNC[3][7] > 0) ? ($arrayTotalNC[3][4] * 100) / $arrayTotalNC[3][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalNC[3][6] = $arrayTotalNC[0][6] + $arrayTotalNC[1][6] + $arrayTotalNC[2][6];
				
				
				
				// VALES DE SALIDA
				// SERVICIOS
				$arrayTotalVS[0][7] += $arrayTotalServ[2][7];
				$arrayTotalVS[0][0] += $arrayTotalServ[2][0];
				$arrayTotalVS[0][1] += $arrayTotalServ[2][1];
				$arrayTotalVS[0][2] += $arrayTotalServ[2][2];
				$arrayTotalVS[0][3] += $arrayTotalServ[2][3];
				$arrayTotalVS[0][4] += $arrayTotalServ[2][4];
				$arrayTotalVS[0][5] = ($arrayTotalVS[0][7] > 0) ? ($arrayTotalVS[0][4] * 100) / $arrayTotalVS[0][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalVS[0][6] += $arrayTotalServ[2][6];
				
				
				$arrayImg[] = "tmp/"."comisiones".$pageNum."-".$subPage.".png";
				$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
			}
		}
		
		////////////////////////////////////////////////////////////////////////////////
		//////////////////////// PAGINA DE RESUMEN POR EMPLEADO ////////////////////////
		////////////////////////////////////////////////////////////////////////////////
		$subPage++;
		
		$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		
		$posY = 10;
		imagestring($img,1,0,$posY,str_pad("VENTAS DE REPUESTOS, SERVICIOS Y VEHICULOS", 152, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY+10,
			str_pad(utf8_decode("VENDEDOR"), 10, " ", STR_PAD_LEFT).": ".
			str_pad($rowComision['cedula'], 11, " ", STR_PAD_LEFT)." ".
			utf8_decode($rowComision['nombre_empleado']),$textColor);
		imagestring($img,1,315,$posY+10,
			str_pad(utf8_decode("% PRODUCTIVIDAD / EFICIENCIA"), 10, " ", STR_PAD_LEFT).": ".
			str_pad(number_format($rowComision['porcentaje_productividad'], 2, ".", ","), 11, " ", STR_PAD_LEFT),$textColor);
		
		imagestring($img,1,600,$posY+10,
			str_pad(utf8_decode("MES / AÑO"), 10, " ", STR_PAD_LEFT).": ".
			str_pad(utf8_decode(strtoupper($mes[$rowComision['mes_comision']]." ".$rowComision['ano_comision'])), 20, " ", STR_PAD_BOTH),$textColor);
		
		
		$posY += 20;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
		$posY += 10;
		imagestring($img,1,250,$posY,str_pad(utf8_decode("VENTA BRUTA"), 13, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,320,$posY,str_pad(utf8_decode("DESCUENTO"), 12, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,385,$posY,str_pad(utf8_decode("VENTA NETA"), 13, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,455,$posY,str_pad(utf8_decode("COSTO"), 13, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,525,$posY,str_pad(utf8_decode("UTL.BRUTA"), 13, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,595,$posY,str_pad(utf8_decode("%UTL.BRUTA"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,705,$posY,str_pad(utf8_decode("COMISION"), 11, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
		
		$posY += 20;
		
		if ($arrayTotalFact[0][7] > 0 || $arrayTotalFact[1][7] > 0 || $arrayTotalFact[2][7] > 0) {
			if ($arrayTotalFact[0][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("R"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalFact[0][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalFact[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalFact[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalFact[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalFact[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalFact[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalFact[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalFact[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($arrayTotalFact[1][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("S"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalFact[1][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalFact[1][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalFact[1][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalFact[1][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalFact[1][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalFact[1][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalFact[1][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalFact[1][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($arrayTotalFact[2][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("V"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalFact[2][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalFact[2][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalFact[2][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalFact[2][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalFact[2][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalFact[2][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalFact[2][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalFact[2][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 10;
			imagestring($img,1,140,$posY,str_pad("", 124, "-", STR_PAD_LEFT),$textColor);
			$posY += 10;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURAS:"), 28, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,155,$posY,str_pad($arrayTotalFact[3][7], 18, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalFact[3][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalFact[3][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalFact[3][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalFact[3][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalFact[3][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalFact[3][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalFact[3][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
		}
		
		if ($arrayTotalNC[0][7] > 0 || $arrayTotalNC[1][7] > 0 || $arrayTotalNC[2][7] > 0) {
			$posY += 10;
			imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
			
			if ($arrayTotalNC[0][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("R"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalNC[0][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalNC[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalNC[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalNC[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalNC[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalNC[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalNC[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalNC[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($arrayTotalNC[1][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("S"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalNC[1][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalNC[1][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalNC[1][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalNC[1][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalNC[1][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalNC[1][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalNC[1][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalNC[1][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($arrayTotalNC[2][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("V"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalNC[2][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalNC[2][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalNC[2][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalNC[2][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalNC[2][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalNC[2][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalNC[2][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalNC[2][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 10;
			imagestring($img,1,140,$posY,str_pad("", 124, "-", STR_PAD_LEFT),$textColor);
			$posY += 10;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL NOTAS CRÉD.:"), 28, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,155,$posY,str_pad($arrayTotalNC[3][7], 18, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalNC[3][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalNC[3][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalNC[3][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalNC[3][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalNC[3][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalNC[3][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalNC[3][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
		}
		
		if ($arrayTotalVS[0][7] > 0) {
			$posY += 10;
			imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_LEFT),$textColor);
			
			if ($arrayTotalVS[0][7] > 0) {
				$posY += 10;
				imagestring($img,1,145,$posY,utf8_decode("S"),$textColor);
				imagestring($img,1,155,$posY,str_pad($arrayTotalVS[0][7], 18, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalVS[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalVS[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalVS[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalVS[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalVS[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalVS[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalVS[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 10;
			imagestring($img,1,140,$posY,str_pad("", 124, "-", STR_PAD_LEFT),$textColor);
			$posY += 10;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL VALES SALIDA:"), 28, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,155,$posY,str_pad($arrayTotalVS[0][7], 18, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalVS[0][0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalVS[0][1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalVS[0][2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalVS[0][3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalVS[0][4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalVS[0][5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalVS[0][6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
		}
		
		
		$arrayTotalComision[7] = $arrayTotalFact[3][7] + $arrayTotalNC[3][7] + $arrayTotalVS[0][7];
		$arrayTotalComision[0] = $arrayTotalFact[3][0] + $arrayTotalNC[3][0] + $arrayTotalVS[0][0];
		$arrayTotalComision[1] = $arrayTotalFact[3][1] + $arrayTotalNC[3][1] + $arrayTotalVS[0][1];
		$arrayTotalComision[2] = $arrayTotalFact[3][2] + $arrayTotalNC[3][2] + $arrayTotalVS[0][2];
		$arrayTotalComision[3] = $arrayTotalFact[3][3] + $arrayTotalNC[3][3] + $arrayTotalVS[0][3];
		$arrayTotalComision[4] = $arrayTotalFact[3][4] + $arrayTotalNC[3][4] + $arrayTotalVS[0][4];
		$arrayTotalComision[5] = ($arrayTotalComision[7] > 0) ? ($arrayTotalComision[4] * 100) / $arrayTotalComision[2] : 0; // PORCENTAJE UTILIDAD VENTA
		$arrayTotalComision[6] = $arrayTotalFact[3][6] + $arrayTotalNC[3][6] + $arrayTotalVS[0][6];
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 152, "=", STR_PAD_LEFT),$textColor);
		$posY += 10;
		imagestring($img,1,250,$posY,str_pad(number_format($arrayTotalComision[0],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,320,$posY,str_pad(number_format($arrayTotalComision[1],2,'.',','), 12, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,385,$posY,str_pad(number_format($arrayTotalComision[2],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,455,$posY,str_pad(number_format($arrayTotalComision[3],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,525,$posY,str_pad(number_format($arrayTotalComision[4],2,'.',','), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,595,$posY,str_pad(number_format($arrayTotalComision[5],2,'.',','), 10, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,705,$posY,str_pad(number_format($arrayTotalComision[6],2,'.',','), 11, " ", STR_PAD_LEFT),$textColor);
		
		$arrayImg[] = "tmp/"."comisiones".$pageNum."-".$subPage.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
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
?>