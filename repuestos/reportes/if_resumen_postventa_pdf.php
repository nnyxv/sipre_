<?php
require_once("../../connections/conex.php");
set_time_limit(0);

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];
$valFecha = explode("-", $valCadBusq[1]);

// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig12 = mysql_query($queryConfig12);
if (!$rsConfig12) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig12 = mysql_num_rows($rsConfig12);
$rowConfig12 = mysql_fetch_assoc($rsConfig12);

// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 300 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig300 = mysql_query($queryConfig300);
if (!$rsConfig300) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig300 = mysql_num_rows($rsConfig300);
$rowConfig300 = mysql_fetch_assoc($rsConfig300);

// DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// TIPOS DE ORDENES
$sqlTiposOrden = "SELECT * FROM sa_tipo_orden
WHERE orden_generica = 0
ORDER BY id_tipo_orden";
$rsTiposOrden = mysql_query($sqlTiposOrden);
if (!$rsTiposOrden) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTipoOrden = mysql_fetch_assoc($rsTiposOrden)) {
	$tipoOrden[$rowTipoOrden['id_tipo_orden']] = array('nombre' => $rowTipoOrden['nombre_tipo_orden']);
}

// TIPOS DE MANO DE OBRA
$sqlOperadores = "SELECT * FROM sa_operadores
ORDER BY id_operador";
$rsOperadores = mysql_query($sqlOperadores);
if (!$rsOperadores) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowOperadores = mysql_fetch_assoc($rsOperadores)) {
	$operadores[$rowOperadores['id_operador']] = $rowOperadores['descripcion_operador'];
}
$itot = count($operadores) + 1;
$operadores[$itot] = "Trabajos Otros Talleres";

// TIPOS DE ARTICULOS
$sqlTiposArticulos = "SELECT * FROM iv_tipos_articulos
ORDER BY id_tipo_articulo";
$rsTiposArticulos = mysql_query($sqlTiposArticulos);
if (!$rsTiposArticulos) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTipoArticulo = mysql_fetch_assoc($rsTiposArticulos)) {
	$tipoArticulos[$rowTipoArticulo['id_tipo_articulo']] = $rowTipoArticulo['descripcion'];
}


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.aprobado = 1
AND sa_v_inf_final_temp.estado_tempario IN ('FACTURADO', 'TERMINADO')");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(sa_v_inf_final_temp.fecha_filtro) = %s
	AND YEAR(sa_v_inf_final_temp.fecha_filtro) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// BUSCA LAS MANO DE OBRA DE FACTURAS
$sqlTempario = sprintf("SELECT
	sa_v_inf_final_temp.id_tipo_orden,
	sa_v_inf_final_temp.operador,
	
	(CASE sa_v_inf_final_temp.id_modo
		WHEN 1 THEN -- UT
			(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
		WHEN 2 THEN -- PRECIO
			sa_v_inf_final_temp.precio
	END) AS total_tempario_orden
	
FROM sa_v_informe_final_tempario sa_v_inf_final_temp %s;", $sqlBusq);
$rsTempario = mysql_query($sqlTempario);
if (!$rsTempario) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$arrayTotalMOTipoOrden[0] = 0;
while ($rowTempario = mysql_fetch_assoc($rsTempario)) {
	$valor = $rowTempario['total_tempario_orden'];
	
	$dataMo[$rowTempario['operador']][$rowTempario['id_tipo_orden']] += $valor;

	$arrayTotalMOTipoOrden[$rowTempario['id_tipo_orden']] += $valor;
	$arrayTotalMOOperador[$rowTempario['operador']] += $valor;
}

// BUSCA LAS MANO DE OBRA DE FACTURAS DEVUELTAS
$sqlTemparioDev = sprintf("SELECT
	sa_v_inf_final_temp.id_tipo_orden,
	sa_v_inf_final_temp.operador,
	
	(CASE sa_v_inf_final_temp.id_modo
		WHEN 1 THEN -- UT
			(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
		WHEN 2 THEN -- PRECIO
			sa_v_inf_final_temp.precio
	END) AS total_tempario_dev_orden
	
FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp %s;", $sqlBusq);
$rsTemparioDev = mysql_query($sqlTemparioDev);
if (!$rsTemparioDev) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTemparioDev = mysql_fetch_assoc($rsTemparioDev)) {
	$valor = $rowTemparioDev['total_tempario_dev_orden'];
	
	$dataMo[$rowTemparioDev['operador']][$rowTemparioDev['id_tipo_orden']] -= $valor;

	$arrayTotalMOTipoOrden[$rowTemparioDev['id_tipo_orden']] -= $valor;
	$arrayTotalMOOperador[$rowTemparioDev['operador']] -= $valor;
}

// BUSCA LAS MANO DE OBRA POR VALES DE SALIDA
$sqlTemparioVale = sprintf("SELECT
	sa_v_inf_final_temp.id_tipo_orden,
	sa_v_inf_final_temp.operador,
	
	(CASE sa_v_inf_final_temp.id_modo
		WHEN 1 THEN -- UT
			(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
		WHEN 2 THEN -- PRECIO
			sa_v_inf_final_temp.precio
	END) AS total_tempario_vale
	
FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp %s;", $sqlBusq);
$rsTemparioVale = mysql_query($sqlTemparioVale);
if (!$rsTemparioVale) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTemparioVale = mysql_fetch_assoc($rsTemparioVale)) {
	$valor = $rowTemparioVale['total_tempario_vale'];
	
	$dataMo[$rowTemparioVale['operador']][$rowTemparioVale['id_tipo_orden']] += $valor;

	$arrayTotalMOTipoOrden[$rowTemparioVale['id_tipo_orden']] += $valor;
	$arrayTotalMOOperador[$rowTemparioVale['operador']] += $valor;
}


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("aprobado = 1");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_filtro) = %s
	AND YEAR(fecha_filtro) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// TRABAJOS OTROS TALLERES DE FACTURAS
$sqlTot = sprintf("SELECT * FROM sa_v_informe_final_tot %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsTot = mysql_query($sqlTot);
if (!$rsTot) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTot = mysql_fetch_assoc($rsTot)) {
	$valor = $rowTot['monto_total'] + (($rowTot['porcentaje_tot'] * $rowTot['monto_total']) / 100);

	$dataMo[$itot][$rowTot['id_tipo_orden']] += $valor;

	$arrayTotalMOTipoOrden[$rowTot['id_tipo_orden']] += $valor;
	$arrayTotalMOOperador[$itot] += $valor;
}

// TRABAJOS OTROS TALLERES DE FACTURAS DEVUELTAS
$sqlTotDev = sprintf("SELECT * FROM sa_v_informe_final_tot_dev %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotDev = mysql_query($sqlTotDev);
if (!$rsTotDev) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTotDev = mysql_fetch_assoc($rsTotDev)) {
	$valor = $rowTotDev['monto_total'] + (($rowTotDev['porcentaje_tot'] * $rowTotDev['monto_total']) / 100);

	$dataMo[$itot][$rowTotDev['id_tipo_orden']] -= $valor;

	$arrayTotalMOTipoOrden[$rowTotDev['id_tipo_orden']] -= $valor;
	$arrayTotalMOOperador[$itot] -= $valor;
}

// TRABAJOS OTROS TALLERES DE VALES DE SALIDA
$sqlTotVale = sprintf("SELECT * FROM sa_v_vale_informe_final_tot %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsTotVale = mysql_query($sqlTotVale);
if (!$rsTotVale) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTotVale = mysql_fetch_assoc($rsTotVale)) {
	$valor = $rowTotVale['monto_total']+(($rowTotVale['porcentaje_tot'] * $rowTotVale['monto_total']) / 100);

	$dataMo[$itot][$rowTotVale['id_tipo_orden']] += $valor;
	
	$arrayTotalMOTipoOrden[$rowTotVale['id_tipo_orden']] += $valor;
	$arrayTotalMOOperador[$itot] += $valor;
}

// REPUESTOS DE FACTURAS
$sqlRepuestos = sprintf("SELECT * FROM sa_v_informe_final_repuesto %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepuestos = mysql_query($sqlRepuestos);
if (!$rsRepuestos) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$arrayTotalRepuestoTipoOrden[0] = 0;
$arrayTotalRepuestoDescuentoTipoOrden[0] = 0;
while ($rowRepuestos = mysql_fetch_assoc($rsRepuestos)) {
	$valor = $rowRepuestos['precio_unitario'] * $rowRepuestos['cantidad'];

	$desc = round((($valor * $rowRepuestos['porcentaje_descuento_orden']) / 100),2);

	$dataRepuesto[$rowRepuestos['id_tipo_articulo']][$rowRepuestos['id_tipo_orden']] += $valor;

	$arrayTotalRepuestoTipoOrden[$rowRepuestos['id_tipo_orden']] += $valor;
	$totalRepuestoTipoRepuesto[$rowRepuestos['id_tipo_articulo']] += $valor;
	$arrayTotalRepuestoDescuentoTipoOrden[$rowRepuestos['id_tipo_orden']] += $desc;
	$totalDescuentoRepServ += $desc;
}

// REPUESTOS DE FACTURAS DEVUELTAS
$sqlRepuestosDev = sprintf("SELECT * FROM sa_v_informe_final_repuesto_dev %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepuestosDev = mysql_query($sqlRepuestosDev);
if (!$rsRepuestosDev) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowRepuestosDev = mysql_fetch_assoc($rsRepuestosDev)) {
	$valor = $rowRepuestosDev['precio_unitario'] * $rowRepuestosDev['cantidad'];

	$desc = round((($valor * $rowRepuestosDev['porcentaje_descuento_orden']) / 100),2);

	$dataRepuesto[$rowRepuestosDev['id_tipo_articulo']][$rowRepuestosDev['id_tipo_orden']] -= $valor;

	$arrayTotalRepuestoTipoOrden[$rowRepuestosDev['id_tipo_orden']] -= $valor;
	$totalRepuestoTipoRepuesto[$rowRepuestosDev['id_tipo_articulo']] -= $valor;
	$arrayTotalRepuestoDescuentoTipoOrden[$rowRepuestosDev['id_tipo_orden']] -= $desc;
	$totalDescuentoRepServ -= $desc;
}

// REPUESTOS DE VALES DE SALIDA
$sqlRepuestosVale = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsRepuestosVale = mysql_query($sqlRepuestosVale);
if (!$rsRepuestosVale) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowRepuestosVale = mysql_fetch_assoc($rsRepuestosVale)) {
	$valor = $rowRepuestosVale['precio_unitario'] * $rowRepuestosVale['cantidad'];

	$desc = round((($valor * $rowRepuestosVale['porcentaje_descuento_orden']) / 100),2);

	$dataRepuesto[$rowRepuestosVale['id_tipo_articulo']][$rowRepuestosVale['id_tipo_orden']] += $valor;

	$arrayTotalRepuestoTipoOrden[$rowRepuestosVale['id_tipo_orden']] += $valor;
	$totalRepuestoTipoRepuesto[$rowRepuestosVale['id_tipo_articulo']] += $valor;
	$arrayTotalRepuestoDescuentoTipoOrden[$rowRepuestosVale['id_tipo_orden']] += $desc;
	$totalDescuentoRepServ += $desc;
}

// NOTAS DE FACTURAS
$sqlNotas = sprintf("SELECT * FROM sa_v_informe_final_notas %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotas = mysql_query($sqlNotas);
if (!$rsNotas) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$arrayTotalNotaTipoOrden[0] = 0;
while ($rowNotas = mysql_fetch_assoc($rsNotas)) {
	$valor = $rowNotas['precio'];
	
	$arrayTotalNotaTipoOrden[$rowNotas['id_tipo_orden']] += $valor;
	$totalNota += $valor;
}

// NOTAS DE FACTURAS DEVUELTAS
$sqlNotasDev = sprintf("SELECT * FROM sa_v_informe_final_notas_dev %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotasDev = mysql_query($sqlNotasDev);
if (!$rsNotasDev) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowNotasDev = mysql_fetch_assoc($rsNotasDev)) {
	$valor = $rowNotasDev['precio'];

	$arrayTotalNotaTipoOrden[$rowNotasDev['id_tipo_orden']] -= $valor;
	$totalNota -= $valor;
}

// NOTAS DE VALES DE SALIDA
$sqlNotasVale = sprintf("SELECT * FROM sa_v_vale_informe_final_notas %s
ORDER BY id_tipo_orden;", $sqlBusq);
$rsNotasVale = mysql_query($sqlNotasVale);
if (!$rsNotasVale) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowNotasVale = mysql_fetch_assoc($rsNotasVale)) {
	$valor = $rowNotasVale['precio'];

	$arrayTotalNotaTipoOrden[$rowNotasVale['id_tipo_orden']] += $valor;
	$totalNota += $valor;
}
		
// CALCULO DEL TOTAL
$totalProdTaller = array_sum($arrayTotalMOTipoOrden) + array_sum($arrayTotalRepuestoTipoOrden) - array_sum($arrayTotalRepuestoDescuentoTipoOrden);
$totalProdTaller += ($rowConfig300['valor'] == 1) ? array_sum($arrayTotalNotaTipoOrden) : 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION REPUESTOS MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura = 0
AND fact_vent.aplicaLibros = 1");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito = 0
AND nota_cred.tipoDocumento LIKE 'FA'
AND nota_cred.aplicaLibros = 1
AND nota_cred.estatus_nota_credito = 2");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
	AND YEAR(fact_vent.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
	AND YEAR(nota_cred.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// PRODUCCIÓN REPUESTOS MOSTRADOR
$query = sprintf("SELECT
	fact_vent.condicionDePago,
	(fact_vent.subtotalFactura - IFNULL(fact_vent.descuentoFactura,0)) AS neto
FROM cj_cc_encabezadofactura fact_vent %s
	
UNION ALL

SELECT
	fact_vent2.condicionDePago,
	((-1)*(nota_cred.subtotalNotaCredito - IFNULL(nota_cred.subtotal_descuento,0))) AS neto
FROM cj_cc_notacredito nota_cred
	INNER JOIN cj_cc_encabezadofactura fact_vent2 ON (nota_cred.idDocumento = fact_vent2.idFactura) %s;",
	$sqlBusq,
	$sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$arrayVentaMost = array();
while ($row = mysql_fetch_assoc($rs)) {
	switch($row['condicionDePago']) {
		case 0 : $arrayVentaMost[1] += round($row['neto'],2); break;
		case 1 : $arrayVentaMost[0] += round($row['neto'],2); break;
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_tipo_orden IN (1,2,3,4,7,8)
AND aprobado = 1");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_filtro) = %s
	AND YEAR(fecha_filtro) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// COSTO DE VENTAS REPUESTOS POR SERVICIOS Y LATONERIA Y PINTURA
$query2 = sprintf("SELECT
	SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
FROM (
	SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto %s
	
	UNION ALL
	
	SELECT (-1)*(costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto_dev %s
	
	UNION ALL
	
	SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_vale_informe_final_repuesto %s) AS query",
	$sqlBusq,
	$sqlBusq,
	$sqlBusq);
$rs2 = mysql_query($query2);
if (!$rs2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row2 = mysql_fetch_assoc($rs2);
$arrayCostoRepServ[0] = round($row2['total_costo_repuesto_orden'],2);


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura = 0
AND fact_vent.aplicaLibros = 1");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito = 0
AND nota_cred.tipoDocumento LIKE 'FA'
AND nota_cred.aplicaLibros = 1
AND nota_cred.estatus_nota_credito = 2");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
	AND YEAR(fact_vent.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
	AND YEAR(nota_cred.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// COSTO DE VENTAS REPUESTOS
$query = sprintf("SELECT
	(SELECT SUM((fact_vent_det.cantidad * fact_vent_det.costo_compra)) AS costo_total
	FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
FROM cj_cc_encabezadofactura fact_vent %s
	
UNION ALL

SELECT
	((-1)*(SELECT SUM((nota_cred_det.cantidad * nota_cred_det.costo_compra)) AS costo_total
	FROM cj_cc_nota_credito_detalle nota_cred_det
	WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito)) AS neto
FROM cj_cc_notacredito nota_cred %s;",
	$sqlBusq,
	$sqlBusq2);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalCostoRepMost = 0;
while ($rowDetalle = mysql_fetch_assoc($rs)) {
	$totalCostoRepMost += round($rowDetalle['neto'],2);
}
$arrayCostoRepMost[0] = $totalCostoRepMost;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ANÁLISIS DE INVENTARIO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";	
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mens.mes = %s
	AND cierre_mens.ano = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
$queryTipoMov = sprintf("SELECT
	analisis_inv.id_analisis_inventario,
	analisis_inv_det.clasificacion
FROM iv_analisis_inventario_detalle analisis_inv_det
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
	INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
	INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
GROUP BY analisis_inv_det.clasificacion", $sqlBusq);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while($rowMovDet = mysql_fetch_array($rsTipoMov)){
	$idAnalisisInv = $rowMovDet['id_analisis_inventario'];

	$queryDetalle = sprintf("SELECT
		analisis_inv_det.id_analisis_inventario,
		analisis_inv_det.cantidad_disponible_logica,
		analisis_inv_det.cantidad_disponible_fisica,
		analisis_inv_det.costo,
		(analisis_inv_det.costo * analisis_inv_det.cantidad_disponible_logica) AS costo_total,
		(analisis_inv_det.cantidad_disponible_logica / analisis_inv_det.promedio_mensual) AS meses_existencia,
		analisis_inv_det.promedio_diario,
		analisis_inv_det.promedio_mensual,
		(analisis_inv_det.promedio_mensual * 2) AS inventario_recomendado,
		(analisis_inv_det.cantidad_disponible_logica - (analisis_inv_det.promedio_mensual * 2)) AS sobre_stock,
		((analisis_inv_det.promedio_mensual * 2) - analisis_inv_det.cantidad_disponible_logica) AS sugerido,
		analisis_inv_det.clasificacion
	FROM iv_analisis_inventario_detalle analisis_inv_det
		INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
		INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
		INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual)
	WHERE analisis_inv.id_analisis_inventario = %s
		AND ((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
			OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
		valTpDato($idAnalisisInv, "int"),
		valTpDato($rowMovDet['clasificacion'], "text"), valTpDato($rowMovDet['clasificacion'], "text"),
		valTpDato($rowMovDet['clasificacion'], "text"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$cantArt = 0;
	$exist = 0;
	$costoInv = 0;
	$promVenta = 0;
	$mesesExist = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$cantArt++;
		$exist += round($rowDetalle['cantidad_disponible_logica'],2);
		$costoInv += round($rowDetalle['costo_total'],2);
		$promVenta += round($rowDetalle['promedio_mensual'] * $rowDetalle['costo'],2);
	}


	$arrayDet[0] = $rowMovDet['clasificacion'];
	$arrayDet[1] = $cantArt;
	$arrayDet[2] = $exist;
	$arrayDet[3] = $costoInv;
	$arrayDet[4] = $promVenta;
	$arrayDet[5] = ($promVenta > 0) ? ($costoInv / $promVenta) : 0;
	
	$arrayAnalisisInv[] = $arrayDet;

	$totalCantArt += $cantArt;
	$totalExistArt += $exist;
	$totalCostoInv += $costoInv;
	$totalPromVentas += $promVenta;
	$totalExistNroArt += $exist / $cantArt;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";	
if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
	$sqlBusq .= $cond.sprintf("cierre_mens.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mens.mes = %s
	AND cierre_mens.ano = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
$queryTipoMov = sprintf("SELECT
	analisis_inv.id_analisis_inventario,
	cierre_mens.id_empresa,
	cierre_mens.ano,
	analisis_inv_det.clasificacion_anterior
FROM iv_analisis_inventario_detalle analisis_inv_det
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
	INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
	INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
GROUP BY clasificacion_anterior", $sqlBusq);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowTipoMov = mysql_fetch_assoc($rsTipoMov)) {
	$sqlBusq2 = "";	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
		$sqlBusq2 .= $cond.sprintf("cierre_anual.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$queryNroVend = sprintf("SELECT
		cierre_anual.%s AS numero_vendido
	FROM iv_cierre_anual cierre_anual
	WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
			WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
		AND cierre_anual.ano = %s
		AND cierre_anual.%s IS NOT NULL
		AND cierre_anual.%s > 0 %s",
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
		valTpDato($rowTipoMov['ano'], "int"),
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		$sqlBusq2);
	$rsNroVend = mysql_query($queryNroVend);
	if (!$rsNroVend) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsNroVend = mysql_num_rows($rsNroVend);

	$queryCantVend = sprintf("SELECT SUM(IFNULL(cierre_anual.%s, 0)) AS cantidad_vendida
	FROM iv_cierre_anual cierre_anual
	WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
			WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
		AND cierre_anual.ano = %s %s",
		valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
		valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
		valTpDato($rowTipoMov['ano'], "int"),
		$sqlBusq2);
	$rsCantVend = mysql_query($queryCantVend);
	if (!$rsCantVend) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCantVend = mysql_fetch_assoc($rsCantVend);

	$arrayDet[0] = $rowTipoMov['clasificacion_anterior'];
	$arrayDet[1] = $totalRowsNroVend;
	$arrayDet[2] = $rowCantVend['cantidad_vendida'];

	$arrayCantArtVend[] = $arrayDet;

	$totalNroArt += $totalRowsNroVend;
	$totalCantArtVend += $rowCantVend['cantidad_vendida'];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// INDICADORES DE TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recepcion.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(recepcion.fecha_entrada) = %s
	AND YEAR(recepcion.fecha_entrada) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// ENTRADA DE VEHICULOS
$queryValeRecepcion = sprintf("SELECT recepcion.id_recepcion FROM sa_recepcion recepcion %s", $sqlBusq);
$rsValeRecepcion = mysql_query($queryValeRecepcion);
if (!$rsValeRecepcion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalTipoOrdenAbierta = 0;
while ($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
	$totalValeRecepcion += 1;
}


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura = 1
AND fact_vent.aplicaLibros = 1
AND fact_vent.numeroPedido IS NOT NULL");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("vale_salida.id_orden IS NOT NULL");

$sqlBusq3 = "";
$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
$sqlBusq3 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito = 1
AND fact_vent.aplicaLibros = 1
AND nota_cred.tipoDocumento LIKE 'FA'
AND fact_vent.numeroPedido IS NOT NULL");

$sqlBusq4 = "";

$sqlBusq5 = "";
$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
$sqlBusq5 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
AND fact_vent.idDepartamentoOrigenFactura = 1
AND fact_vent.aplicaLibros = 1");

$sqlBusq6 = "";
$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
$sqlBusq6 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden");

$sqlBusq7 = "";
$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
$sqlBusq7 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
AND nota_cred.idDepartamentoNotaCredito = 1
AND fact_vent.aplicaLibros = 1
AND nota_cred.tipoDocumento LIKE 'FA'");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("vale_salida.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("nota_cred.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("vale_salida.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
		
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("nota_cred.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) <= %s
	AND YEAR(fact_vent.fechaRegistroFactura) <= %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(vale_salida.fecha_vale) <= %s
	AND YEAR(vale_salida.fecha_vale) <= %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) <= %s
	AND YEAR(nota_cred.fechaNotaCredito) <= %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " AND ";
	$sqlBusq4 .= $cond.sprintf("MONTH(orden.tiempo_orden) <= %s
	AND YEAR(orden.tiempo_orden) <= %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
	AND YEAR(fact_vent.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("MONTH(vale_salida.fecha_vale) = %s
	AND YEAR(vale_salida.fecha_vale) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
	AND YEAR(nota_cred.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
$queryOrdenServ = sprintf("SELECT
	tipo_orden.id_tipo_orden,
	tipo_orden.nombre_tipo_orden,
	
	(IFNULL((SELECT COUNT(orden.id_orden) FROM sa_orden orden
	WHERE orden.id_tipo_orden = tipo_orden.id_tipo_orden
		AND orden.id_orden NOT IN (SELECT fact_vent.numeroPedido
										FROM cj_cc_encabezadofactura fact_vent %s
								
								UNION
								
								SELECT vale_salida.id_orden
								FROM sa_vale_salida vale_salida %s
								
								UNION
								
								SELECT fact_vent.numeroPedido
								FROM cj_cc_encabezadofactura fact_vent
									INNER JOIN cj_cc_notacredito nota_cred ON (fact_vent.idFactura = nota_cred.idDocumento) %s
									
								ORDER BY 1) %s),0)) AS cantidad_ordenes_abiertas,
	
	(IFNULL((SELECT COUNT(orden.id_orden)
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden) %s),0)
	+
	IFNULL((SELECT COUNT(orden.id_orden)
	FROM sa_orden orden
		INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden) %s),0)
	-	
	IFNULL((SELECT COUNT(orden.id_orden)
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
		INNER JOIN cj_cc_notacredito nota_cred ON (fact_vent.idFactura = nota_cred.idDocumento) %s),0)) AS cantidad_ordenes_cerradas
FROM sa_tipo_orden tipo_orden
ORDER BY tipo_orden.nombre_tipo_orden ASC", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7);
$rsOrdenServ = mysql_query($queryOrdenServ);
if (!$rsOrdenServ) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowOrdenServ = mysql_fetch_assoc($rsOrdenServ)) {
	$arrayDet[0] = $rowOrdenServ['nombre_tipo_orden'];
	$arrayDet[1] = $rowOrdenServ['cantidad_ordenes_abiertas'];
	$arrayDet[2] = $rowOrdenServ['cantidad_ordenes_cerradas'];
	
	$arrayTipoOrden[$rowOrdenServ['id_tipo_orden']] = $arrayDet;
	
	$totalTipoOrdenAbierta += $rowOrdenServ['cantidad_ordenes_abiertas'];
	$totalTipoOrdenCerrada += $rowOrdenServ['cantidad_ordenes_cerradas'];
}

$sqlBusq = "";
if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_baja) = %s
	AND (YEAR(fecha_baja) = %s OR YEAR(fecha_baja) = '0000')",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// BUSCA LOS DIAS FERIADOS
$query = sprintf("SELECT *
FROM pg_fecha_baja %s;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDiasFeriados = mysql_num_rows($rs);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN ASESORES DE SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sql1 = "SELECT
	id_empleado,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
FROM pg_empleado empleado
ORDER BY id_empleado";
$rs1 = mysql_query($sql1);
if (!$rs1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row1 = mysql_fetch_assoc($rs1)) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s
	AND aprobado = 1",
		valTpDato($row1['id_empleado'], "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}

	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_filtro) = %s
		AND YEAR(fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// SOLO APLICA PARA LAS MANO DE OBRA
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp.estado_tempario IN ('FACTURADO','TERMINADO')");
	
	// MANO DE OBRAS FACTURAS DE SERVICIOS
	$sql2 = sprintf("SELECT
		sa_v_inf_final_temp.id_empleado,
		
		SUM((CASE sa_v_inf_final_temp.id_modo
			WHEN 1 THEN -- UT
				(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
			WHEN 2 THEN -- PRECIO
				sa_v_inf_final_temp.precio
		END)) AS total_tempario_orden
		
	FROM sa_v_informe_final_tempario sa_v_inf_final_temp %s %s
	GROUP BY sa_v_inf_final_temp.id_empleado", $sqlBusq, $sqlBusq2);
	$rs2 = mysql_query($sql2);
	if (!$rs2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row2 = mysql_fetch_assoc($rs2);
	
	// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
	$sql3 = sprintf("SELECT
		sa_v_inf_final_temp.id_empleado,
		
		SUM((CASE sa_v_inf_final_temp.id_modo
			WHEN 1 THEN -- UT
				(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
			WHEN 2 THEN -- PRECIO
				sa_v_inf_final_temp.precio
		END)) AS total_tempario_dev_orden
		
	FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp %s %s
	GROUP BY sa_v_inf_final_temp.id_empleado", $sqlBusq, $sqlBusq2);
	$rs3 = mysql_query($sql3);
	if (!$rs3) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row3 = mysql_fetch_assoc($rs3);
	
	// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
	$sql4 = sprintf("SELECT
		sa_v_inf_final_temp.id_empleado,
		
		SUM((CASE sa_v_inf_final_temp.id_modo
			WHEN 1 THEN -- UT
				(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
			WHEN 2 THEN -- PRECIO
				sa_v_inf_final_temp.precio
		END)) AS total_tempario_vale
		
	FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp %s %s
	GROUP BY sa_v_inf_final_temp.id_empleado", $sqlBusq, $sqlBusq2);
	$rs4 = mysql_query($sql4);
	if (!$rs4) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row4 = mysql_fetch_assoc($rs4);
	
	
	// TOT FACTURAS DE SERVICIOS
	$sql5 = sprintf("SELECT
		sa_v_inf_final_tot.id_empleado,
		SUM(sa_v_inf_final_tot.monto_total + ((sa_v_inf_final_tot.porcentaje_tot * sa_v_inf_final_tot.monto_total) / 100)) AS total_tot_orden
	FROM sa_v_informe_final_tot sa_v_inf_final_tot %s
	GROUP BY sa_v_inf_final_tot.id_empleado", $sqlBusq);
	$rs5 = mysql_query($sql5);
	if (!$rs5) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowFacturaTot = mysql_fetch_assoc($rs5);
	
	// TOT NOTAS DE CREDITO DE SERVICIOS
	$queryNotaCreditoTot = sprintf("SELECT
		sa_v_inf_final_tot.id_empleado,
		SUM(sa_v_inf_final_tot.monto_total + ((sa_v_inf_final_tot.porcentaje_tot * sa_v_inf_final_tot.monto_total) / 100)) AS total_tot_dev_orden
	FROM sa_v_informe_final_tot_dev sa_v_inf_final_tot %s
	GROUP BY sa_v_inf_final_tot.id_empleado", $sqlBusq);
	$rsNotaCreditoTot = mysql_query($queryNotaCreditoTot);
	if (!$rsNotaCreditoTot) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNotaCreditoTot = mysql_fetch_assoc($rsNotaCreditoTot);
	
	// TOT VALE DE SALIDA DE SERVICIOS
	$sql6 = sprintf("SELECT
		sa_v_inf_final_tot.id_empleado,
		SUM(sa_v_inf_final_tot.monto_total + ((sa_v_inf_final_tot.porcentaje_tot * sa_v_inf_final_tot.monto_total) / 100)) AS total_tot_vale
	FROM sa_v_vale_informe_final_tot sa_v_inf_final_tot %s
	GROUP BY sa_v_inf_final_tot.id_empleado", $sqlBusq);
	$rs6 = mysql_query($sql6);
	if (!$rs6) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowValeSalidaTot = mysql_fetch_assoc($rs6);
	
	
	// REPUESTOS FACTURAS DE SERVICIOS
	$sql7 = sprintf("SELECT
		sa_v_inf_final_repuesto.id_empleado,
		SUM((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad)) AS total_repuesto_orden,
		SUM(((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad) * sa_v_inf_final_repuesto.porcentaje_descuento_orden) / 100) AS total_descuento_orden
	FROM sa_v_informe_final_repuesto  sa_v_inf_final_repuesto %s
	GROUP BY sa_v_inf_final_repuesto.id_empleado", $sqlBusq);
	$rs7 = mysql_query($sql7);
	if (!$rs7) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row7 = mysql_fetch_assoc($rs7);
	
	// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
	$sql8 = sprintf("SELECT
		sa_v_inf_final_repuesto.id_empleado,
		SUM((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad)) AS total_repuesto_dev_orden,
		SUM(((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad) * sa_v_inf_final_repuesto.porcentaje_descuento_orden) / 100) AS total_descuento_dev_orden
	FROM sa_v_informe_final_repuesto_dev sa_v_inf_final_repuesto %s
	GROUP BY sa_v_inf_final_repuesto.id_empleado", $sqlBusq);
	$rs8 = mysql_query($sql8);
	if (!$rs8) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row8 = mysql_fetch_assoc($rs8);
	
	// REPUESTOS VALE DE SALIDA DE SERVICIOS
	$sql9 = sprintf("SELECT
		sa_v_inf_final_repuesto.id_empleado,
		SUM((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad)) AS total_repuesto_vale,
		SUM(((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad) * sa_v_inf_final_repuesto.porcentaje_descuento_orden) / 100) AS total_descuento_vale
	FROM sa_v_vale_informe_final_repuesto sa_v_inf_final_repuesto %s
	GROUP BY sa_v_inf_final_repuesto.id_empleado", $sqlBusq);
	$rs9 = mysql_query($sql9);
	if (!$rs9) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$row9 = mysql_fetch_assoc($rs9);
	
	if ($row2 || $row3 || $row4
	|| $rowFacturaTot || $rowNotaCreditoTot || $rowValeSalidaTot
	|| $row7 || $row8 || $row9) {
		$totalMoAsesor = ($row2['total_tempario_orden']) + (-1 * $row3['total_tempario_dev_orden']) + $row4['total_tempario_vale'];
		
		$totalRepuetosAsesor = ($row7['total_repuesto_orden'] - $row7['total_descuento_orden']) + (-1 * ($row8['total_repuesto_dev_orden'] - $row8['total_descuento_dev_orden'])) + ($row9['total_repuesto_vale'] - $row9['total_descuento_vale']);
		
		$totalTotAsesor = $rowFacturaTot['total_tot_orden'] + (-1 * $rowNotaCreditoTot['total_tot_dev_orden']) + $rowValeSalidaTot['total_tot_vale'];

		$total1 += $totalMoAsesor;
		$total2 += $totalRepuetosAsesor;
		$total3 += $totalTotAsesor;
		$total4 += $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor;

		$arrayVentaAsesor[] = array('nombre_asesor'=> $row1['nombre_empleado'],
			'total_mo'=> $totalMoAsesor,
			'total_repuestos'=> $totalRepuetosAsesor,
			'total_tot'=> $totalTotAsesor,
			'total_asesor'=> $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor);
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN VENDEDORES DE REPUESTOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura = 0
AND fact_vent.aplicaLibros = 1");

$sqlBusq2 = "";
$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito = 0
AND nota_cred.tipoDocumento LIKE 'FA'
AND nota_cred.aplicaLibros = 1
AND nota_cred.estatus_nota_credito = 2");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
	AND YEAR(fact_vent.fechaRegistroFactura) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
	AND YEAR(nota_cred.fechaNotaCredito) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

$queryTipoMov = sprintf("SELECT
	empleado.id_empleado,
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado
FROM pg_empleado empleado
WHERE empleado.id_empleado IN (
	SELECT DISTINCT
		fact_vent.idVendedor
	FROM cj_cc_encabezadofactura fact_vent %s
	
	UNION ALL
	
	SELECT DISTINCT
		fact_vent2.idVendedor
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_encabezadofactura fact_vent2 ON (nota_cred.idDocumento = fact_vent2.idFactura) %s)
ORDER BY nombre_empleado",
	$sqlBusq,
	$sqlBusq2);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while($rowMovDet = mysql_fetch_array($rsTipoMov)) {
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idVendedor = %s
	AND fact_vent.idDepartamentoOrigenFactura = 0
	AND fact_vent.aplicaLibros = 1",
		valTpDato($rowMovDet['id_empleado'], "int"));
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("fact_vent.idVendedor = %s
	AND nota_cred.idDepartamentoNotaCredito = 0
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND nota_cred.aplicaLibros = 1
	AND nota_cred.estatus_nota_credito = 2",
		valTpDato($rowMovDet['id_empleado'], "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// FACTURA DE VENTA
	$query = sprintf("SELECT 
		condicionDePago AS condicion_pago,
		(fact_vent.subtotalFactura - IFNULL(fact_vent.descuentoFactura,0)) AS neto
	FROM cj_cc_encabezadofactura fact_vent %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalContado = 0;
	$totalCredito = 0;
	while($row = mysql_fetch_array($rs)) {
		switch ($row['condicion_pago']) {
			case 0 : $totalCredito += round($row['neto'],2); break;
			case 1 : $totalContado += round($row['neto'],2); break;
		}
	}

	// NOTA DE CREDITO
	$query = sprintf("SELECT
		fact_vent.condicionDePago AS condicion_pago,
		(nota_cred.subtotalNotaCredito - IFNULL(nota_cred.subtotal_descuento,0)) AS neto
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura) %s;", $sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($row = mysql_fetch_array($rs)) {
		switch ($row['condicion_pago']) {
			case 0 : $totalCredito -= round($row['neto'],2); break;
			case 1 : $totalContado -= round($row['neto'],2); break;
		}
	}

	$arrayDet[0] = $rowMovDet['nombre_empleado'];
	$arrayDet[1] = $totalContado;
	$arrayDet[2] = $totalCredito;
	$arrayDet[3] = $totalContado + $totalCredito;
	$arrayVentaVendedor[] = $arrayDet;

	$totalVentaVendedores += $arrayDet[3];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN TÉCNICOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sql0 = "SELECT
	id_equipo_mecanico,
	nombre_equipo
FROM sa_equipos_mecanicos
ORDER BY nombre_equipo";
$rs0 = mysql_query($sql0);
if (!$rs0) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row0 = mysql_fetch_assoc($rs0)) {
	$totalMecanicoBs = 0;
	$totalMecanicoUts = 0;
	$i = 0;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.aprobado = 1
	AND sa_v_inf_final_temp.estado_tempario IN ('FACTURADO')");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.aprobado = 1
	AND sa_v_inf_final_temp_dev.estado_tempario IN ('FACTURADO')");
	
	$sqlBusq3 = "";
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("sa_v_vale_inf_final_temp.aprobado = 1
	AND sa_v_vale_inf_final_temp.estado_tempario IN ('TERMINADO')");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("sa_v_vale_inf_final_temp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(sa_v_inf_final_temp.fecha_filtro) = %s
		AND YEAR(sa_v_inf_final_temp.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(sa_v_inf_final_temp_dev.fecha_filtro) = %s
		AND YEAR(sa_v_inf_final_temp_dev.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("MONTH(sa_v_vale_inf_final_temp.fecha_filtro) = %s
		AND YEAR(sa_v_vale_inf_final_temp.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}

	$sql1 = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado AS nombre_completo,
		mec.id_mecanico,
		mec.nivel
	FROM sa_mecanicos mec
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (mec.id_empleado = vw_pg_empleado.id_empleado)
	WHERE id_equipo_mecanico = %s
		AND (mec.id_mecanico IN (SELECT id_mecanico
								FROM sa_det_orden_tempario det_orden_temp
									INNER JOIN sa_v_informe_final_tempario sa_v_inf_final_temp
										ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp.id_det_orden_tempario) %s)
			OR mec.id_mecanico IN (SELECT id_mecanico
								FROM sa_det_orden_tempario det_orden_temp
									INNER JOIN sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev
										ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp_dev.id_det_orden_tempario) %s)
			OR mec.id_mecanico IN (SELECT id_mecanico
								FROM sa_det_vale_salida_tempario det_vale_temp
									INNER JOIN sa_v_vale_informe_final_tempario sa_v_vale_inf_final_temp
										ON (det_vale_temp.id_det_vale_salida_tempario = sa_v_vale_inf_final_temp.id_det_vale_salida_tempario) %s))
	ORDER BY nombre_completo",
		valTpDato($row0['id_equipo_mecanico'], "int"),
		$sqlBusq,
		$sqlBusq2,
		$sqlBusq3);
	$rsMercanicos = mysql_query($sql1);
	if (!$rsMercanicos) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsMercanicos = mysql_num_rows($rsMercanicos);
		
	$arrayTecnico = NULL;
	while ($row1 = mysql_fetch_assoc($rsMercanicos)) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("det_orden_temp.id_mecanico = %s
		AND sa_v_inf_final_temp.aprobado = 1
		AND sa_v_inf_final_temp.estado_tempario IN ('FACTURADO')",
			valTpDato($row1['id_mecanico'], "int"));
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("det_orden_temp.id_mecanico = %s
		AND sa_v_inf_final_temp_dev.aprobado = 1
		AND sa_v_inf_final_temp_dev.estado_tempario IN ('FACTURADO')",
			valTpDato($row1['id_mecanico'], "int"));
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("det_vale_temp.id_mecanico = %s
		AND sa_v_vale_inf_final_temp.aprobado = 1
		AND sa_v_vale_inf_final_temp.estado_tempario IN ('TERMINADO')",
			valTpDato($row1['id_mecanico'], "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("sa_v_vale_inf_final_temp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
		
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(sa_v_inf_final_temp.fecha_filtro) = %s
			AND YEAR(sa_v_inf_final_temp.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(sa_v_inf_final_temp_dev.fecha_filtro) = %s
			AND YEAR(sa_v_inf_final_temp_dev.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
			
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("MONTH(sa_v_vale_inf_final_temp.fecha_filtro) = %s
			AND YEAR(sa_v_vale_inf_final_temp.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		// FACTURAS
		$sql2 = sprintf("SELECT
			SUM((CASE sa_v_inf_final_temp.id_modo
				WHEN 1 THEN
					(sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
				WHEN 2 THEN
					0
			END)) AS uts,
			
			SUM((CASE sa_v_inf_final_temp.id_modo
				WHEN 1 THEN
					(sa_v_inf_final_temp.ut * sa_v_inf_final_temp.precio_tempario_tipo_orden) / sa_v_inf_final_temp.base_ut_precio
				WHEN 2 THEN
					sa_v_inf_final_temp.precio
			END)) AS valor_uts
			
		FROM sa_det_orden_tempario det_orden_temp
			INNER JOIN sa_v_informe_final_tempario sa_v_inf_final_temp ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp.id_det_orden_tempario) %s;", $sqlBusq);
		$rs2 = mysql_query($sql2);
		if (!$rs2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row2 = mysql_fetch_assoc($rs2);

		// NOTAS DE CREDITO
		$sql3 = sprintf("SELECT
			SUM((CASE sa_v_inf_final_temp_dev.id_modo
				WHEN 1 THEN
					(sa_v_inf_final_temp_dev.ut) / sa_v_inf_final_temp_dev.base_ut_precio
				WHEN 2 THEN
					0
			END)) AS uts_dev,
			
			SUM((CASE sa_v_inf_final_temp_dev.id_modo
				WHEN 1 THEN
					(sa_v_inf_final_temp_dev.ut * sa_v_inf_final_temp_dev.precio_tempario_tipo_orden) / sa_v_inf_final_temp_dev.base_ut_precio
				WHEN 2 THEN
					sa_v_inf_final_temp_dev.precio
			END)) AS valor_uts_dev
			
		FROM sa_det_orden_tempario det_orden_temp
			INNER JOIN sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp_dev.id_det_orden_tempario) %s;", $sqlBusq2);
		$rs3 = mysql_query($sql3);
		if (!$rs3) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row3 = mysql_fetch_assoc($rs3);

		// VALES DE SALIDA
		$sql4 = sprintf("SELECT
			SUM((CASE sa_v_vale_inf_final_temp.id_modo
				WHEN 1 THEN
					(sa_v_vale_inf_final_temp.ut) / sa_v_vale_inf_final_temp.base_ut_precio
				WHEN 2 THEN
					0
			END)) AS uts_vale,
			
			SUM((CASE sa_v_vale_inf_final_temp.id_modo
				WHEN 1 THEN
					(sa_v_vale_inf_final_temp.ut * sa_v_vale_inf_final_temp.precio_tempario_tipo_orden) / sa_v_vale_inf_final_temp.base_ut_precio
				WHEN 2 THEN
					sa_v_vale_inf_final_temp.precio
			END)) AS valor_uts_vale
			
		FROM sa_det_vale_salida_tempario det_vale_temp
			INNER JOIN sa_v_vale_informe_final_tempario sa_v_vale_inf_final_temp ON (det_vale_temp.id_det_vale_salida_tempario = sa_v_vale_inf_final_temp.id_det_vale_salida_tempario) %s;", $sqlBusq3);
		$rs4 = mysql_query($sql4);
		if (!$rs4) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row4 = mysql_fetch_assoc($rs4);
		
		if ($row1 || $row2 || $row3 || $row4) {
			$totalMecanicoUts = $row2['uts'] - $row3['uts_dev'] + $row4['uts_vale'];
			$totalMecanicoBs = $row2['valor_uts'] - $row3['valor_uts_dev'] + $row4['valor_uts_vale'];
			
			$arrayTecnico[] = array(
				'nombre_mecanico'=> $row1['nombre_completo'],
				'total_uts'=> $totalMecanicoUts,
				'total_bs'=> $totalMecanicoBs);
		}
		
		switch($row1['nivel']) {
			case 'AYUDANTE' : $arrayMecanico[0] += 1; break;
			case 'PRINCIPIANTE' : $arrayMecanico[1] += 1; break;
			case 'NORMAL' : $arrayMecanico[1] += 1; break;
			case 'EXPERTO' : $arrayMecanico[1] += 1; break;
		}
	}
	
	$totalUtsEquipo = 0;
	$totalBsEquipo = 0;
	if (isset($arrayTecnico)) {
		foreach ($arrayTecnico as $indice => $valor) {
			$totalUtsEquipo += $arrayTecnico[$indice]['total_uts'];
			$totalBsEquipo += $arrayTecnico[$indice]['total_bs'];
		}
	}
	
	$totalTotalUtsEquipos += $totalUtsEquipo;
	
	$arrayDet[0] = $row0['nombre_equipo'];
	$arrayDet[1] = $arrayTecnico;
	$arrayDet[2] = array($totalUtsEquipo, $totalBsEquipo);
	$arrayVentaTecnico[] = $arrayDet;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMPRAS DE REPUESTOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_modulo IN (0)
AND (id_tipo_movimiento IN (1)
	OR (id_tipo_movimiento IN (4) AND (tipo_vale IS NULL OR tipo_vale IN (3))))");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_movimiento) = %s
	AND YEAR(fecha_movimiento) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
$queryTipoMov = sprintf("SELECT
	id_clave_movimiento,
		
	(SELECT clave_mov.clave
	FROM pg_clave_movimiento clave_mov
	WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
	
	descripcion,
	id_tipo_movimiento,
	(CASE id_tipo_movimiento
		WHEN 1 THEN 'Compra'
		WHEN 2 THEN 'Entrada'
		WHEN 3 THEN 'Venta'
		WHEN 4 THEN 'Salida'
	END) AS tipo_movimiento,
	id_modulo
FROM vw_iv_movimiento %s
GROUP BY id_clave_movimiento, descripcion, id_tipo_movimiento
ORDER BY clave ASC;", $sqlBusq);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$arrayDet = NULL;
$array = NULL;
while ($rowMovDet = mysql_fetch_array($rsTipoMov)) {
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 = $cond.sprintf("mov.id_clave_movimiento = %s",
		valTpDato($rowMovDet['id_clave_movimiento'], "int"));

	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(mov.fecha_movimiento) = %s
		AND YEAR(mov.fecha_movimiento) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	$sqlBusq3 = "";
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 = $cond.sprintf("mov_det.id_movimiento IN (SELECT
			mov.id_movimiento
		FROM iv_movimiento mov
			INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
	
	$queryDetalle = sprintf("SELECT
		mov_det.cantidad,
		mov_det.precio,
		mov_det.porcentaje_descuento,
		mov_det.costo,
		(IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) AS importePv,
		((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100) AS descuento,
		((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) - ((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100)) AS neto,
		(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
	FROM iv_movimiento_detalle mov_det
		INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
	ORDER BY id_movimiento_detalle;", $sqlBusq3);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalImportePv = 0;
	$totalDescuento = 0;
	$totalUtilidad = 0;
	$totalNeto = 0;
	$totalImporteC = 0;
	$porcentajeUtilidad = 0;
	$porcentajeDescuento = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$importePv = $rowDetalle['importePv'];
		$descuento = $rowDetalle['descuento'];
		$neto = $rowDetalle['neto'];
		$importeCosto = $rowDetalle['importeCosto'];

		$importeC = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;

		if ($importePv > 0) {
			$utilidad = $neto - $importeC;
			$utilidadPorcentaje = $utilidad * 100 / $importePv;
		}
		
		$totalImportePv += $importePv;
		$totalDescuento += $descuento;
		$totalUtilidad += $utilidad;
		$totalNeto += $neto;
		$totalImporteC += $importeC;
	}

	if ($totalImportePv > 0) {
		$porcentajeUtilidad = ($totalUtilidad > 0) ? (($totalUtilidad * 100) / $totalImportePv) : 0;
		$porcentajeDescuento = (($totalDescuento * 100) / $totalImportePv);
	} else {
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
	}
	
	if ($rowMovDet['id_tipo_movimiento'] == 1 || $rowMovDet['id_tipo_movimiento'] == 3) {
		$totalNetoClaveMovCompras += $totalNeto;
	} else if ($rowMovDet['id_tipo_movimiento'] == 2 || $rowMovDet['id_tipo_movimiento'] == 4) {
		$totalNetoClaveMovCompras -= $totalNeto;
	}
	
	$arrayMovDet[0] = $rowMovDet['id_tipo_movimiento'];
	$arrayMovDet[1] = $rowMovDet['descripcion'];
	$arrayMovDet[2] = $totalNeto;
	$arrayMovCompras[] = $arrayMovDet;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_modulo IN (0)
AND (id_tipo_movimiento IN (3)
	OR (id_tipo_movimiento IN (2) AND (tipo_vale IS NULL OR tipo_vale IN (3))))");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_movimiento) = %s
	AND YEAR(fecha_movimiento) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
$queryTipoMov = sprintf("SELECT
	id_clave_movimiento,
		
	(SELECT clave_mov.clave
	FROM pg_clave_movimiento clave_mov
	WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
	
	descripcion,
	id_tipo_movimiento,
	(CASE id_tipo_movimiento
		WHEN 1 THEN 'Compra'
		WHEN 2 THEN 'Entrada'
		WHEN 3 THEN 'Venta'
		WHEN 4 THEN 'Salida'
	END) AS tipo_movimiento,
	id_modulo
FROM vw_iv_movimiento %s
GROUP BY id_clave_movimiento, descripcion, id_tipo_movimiento
ORDER BY clave ASC;", $sqlBusq);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$arrayDet = NULL;
$array = NULL;
while($rowMovDet = mysql_fetch_array($rsTipoMov)){
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 = $cond.sprintf("mov.id_clave_movimiento = %s",
		valTpDato($rowMovDet['id_clave_movimiento'], "int"));
	
	/*if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT vw_iv_mov.id_empresa FROM vw_iv_movimiento vw_iv_mov
		WHERE vw_iv_mov.id_movimiento = mov_det.id_movimiento) = %s",
			valTpDato($idEmpresa, "date"));
	}*/

	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(mov.fecha_movimiento) = %s
		AND YEAR(mov.fecha_movimiento) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	$sqlBusq3 = "";
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 = $cond.sprintf("mov_det.id_movimiento IN (SELECT
			mov.id_movimiento
		FROM iv_movimiento mov
			INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);

	$queryDetalle = sprintf("SELECT
		mov_det.cantidad,
		mov_det.precio,
		mov_det.porcentaje_descuento,
		mov_det.costo,
		(IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) AS importePv,
		((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100) AS descuento,
		((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) - ((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100)) AS neto,
		(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
	FROM iv_movimiento_detalle mov_det
		INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
	ORDER BY id_movimiento_detalle;", $sqlBusq3);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalImportePv = 0;
	$totalDescuento = 0;
	$totalUtilidad = 0;
	$totalNeto = 0;
	$totalImporteC = 0;
	$porcentajeUtilidad = 0;
	$porcentajeDescuento = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$importePv = $rowDetalle['importePv'];
		$descuento = $rowDetalle['descuento'];
		$neto = $rowDetalle['neto'];
		$importeCosto = $rowDetalle['importeCosto'];

		$importeC = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;

		if ($importePv > 0) {
			$utilidad = $neto - $importeC;
			$utilidadPorcentaje = $utilidad * 100 / $importePv;
		}

		$totalImportePv += $importePv;
		$totalDescuento += $descuento;
		$totalUtilidad += $utilidad;
		$totalNeto += $neto;
		$totalImporteC += $importeC;
	}

	if ($totalImportePv > 0) {
		$porcentajeUtilidad = ($totalUtilidad > 0) ? (($totalUtilidad * 100) / $totalImportePv) : 0;
		$porcentajeDescuento = (($totalDescuento * 100) / $totalImportePv);
	} else {
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
	}

	if ($rowMovDet['id_tipo_movimiento'] == 1 || $rowMovDet['id_tipo_movimiento'] == 3) {
		$totalNetoClaveMovVentas += $totalNeto;
	} else if ($rowMovDet['id_tipo_movimiento'] == 2 || $rowMovDet['id_tipo_movimiento'] == 4) {
		$totalNetoClaveMovVentas -= $totalNeto;
	}

	$arrayMovDet[0] = $rowMovDet['id_tipo_movimiento'];
	$arrayMovDet[1] = $rowMovDet['descripcion'];
	$arrayMovDet[2] = $totalNeto;
	$arrayMovVentas[] = $arrayMovDet;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_modulo IN (1)
AND (id_tipo_movimiento IN (3,4)
	OR (id_tipo_movimiento IN (2) AND tipo_vale IS NULL))");

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
}

if ($valFecha[0] != "-1" && $valFecha[0] != ""
&& $valFecha[1] != "-1" && $valFecha[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_movimiento) = %s
	AND YEAR(fecha_movimiento) = %s",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[1], "date"));
}

// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
$queryTipoMov = sprintf("SELECT
	id_clave_movimiento,
		
	(SELECT clave_mov.clave
	FROM pg_clave_movimiento clave_mov
	WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
	
	descripcion,
	id_tipo_movimiento,
	(CASE id_tipo_movimiento
		WHEN 1 THEN 'Compra'
		WHEN 2 THEN 'Entrada'
		WHEN 3 THEN 'Venta'
		WHEN 4 THEN 'Salida'
	END) AS tipo_movimiento,
	id_modulo
FROM vw_iv_movimiento %s
GROUP BY id_clave_movimiento, descripcion, id_tipo_movimiento
ORDER BY descripcion", $sqlBusq);
$rsTipoMov = mysql_query($queryTipoMov);
if (!$rsTipoMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$arrayDet = NULL;
$array = NULL;
while($rowMovDet = mysql_fetch_array($rsTipoMov)){
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 = $cond.sprintf("mov.id_clave_movimiento = %s",
		valTpDato($rowMovDet['id_clave_movimiento'], "int"));

	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(fecha_movimiento) = %s
		AND YEAR(fecha_movimiento) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	$sqlBusq3 = "";
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 = $cond.sprintf("id_movimiento IN (SELECT
			mov.id_movimiento
		FROM iv_movimiento mov
			INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);

	$queryDetalle = sprintf("SELECT
		mov_det.cantidad,
		mov_det.precio,
		mov_det.porcentaje_descuento,
		mov_det.costo,
		(IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) AS importePv,
		((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100) AS descuento,
		((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) - ((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100)) AS neto,
		(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
	FROM iv_movimiento_detalle mov_det
		INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
	ORDER BY id_movimiento_detalle;", $sqlBusq3);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalImportePv = 0;
	$totalDescuento = 0;
	$totalUtilidad = 0;
	$totalNeto = 0;
	$totalImporteC = 0;
	$porcentajeUtilidad = 0;
	$porcentajeDescuento = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$importePv = $rowDetalle['importePv'];
		$descuento = $rowDetalle['descuento'];
		$neto = $rowDetalle['neto'];
		$importeCosto = $rowDetalle['importeCosto'];

		$importeC = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;

		if ($importePv > 0) {
			$utilidad = $neto - $importeC;
			$utilidadPorcentaje = $utilidad * 100 / $importePv;
		}

		$totalImportePv += $importePv;
		$totalDescuento += $descuento;
		$totalUtilidad += $utilidad;
		$totalNeto += $neto;
		$totalImporteC += $importeC;
	}

	if ($totalImportePv > 0) {
		$porcentajeUtilidad = ($totalUtilidad > 0) ? (($totalUtilidad * 100) / $totalImportePv) : 0;
		$porcentajeDescuento = (($totalDescuento * 100) / $totalImportePv);
	} else {
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
	}

	if ($rowMovDet['id_tipo_movimiento'] == 1 || $rowMovDet['id_tipo_movimiento'] == 3) {
		$totalNetoClaveMovVentasServ += $totalNeto;
	} else if ($rowMovDet['id_tipo_movimiento'] == 2) {
		$totalNetoClaveMovVentasServ -= $totalNeto;
	} else if ($rowMovDet['id_tipo_movimiento'] == 4) {
		$totalNetoClaveMovVentasServ += $totalNeto;
	}

	$arrayMovDet[0] = $rowMovDet['id_tipo_movimiento'];
	$arrayMovDet[1] = $rowMovDet['descripcion'];
	$arrayMovDet[2] = $totalNeto;
	$arrayMovVentasServ[] = $arrayMovDet;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// TOTAL FACTURACION
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("TOTAL FACTURACIÓN"), 60, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 60, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Facturado")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 60, "-", STR_PAD_BOTH),$textColor);
		
// TOTAL SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Servicios"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalProdTaller, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(((($totalProdTaller + array_sum($arrayVentaMost)) > 0) ? $totalProdTaller * 100 / ($totalProdTaller + array_sum($arrayVentaMost)) : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL REPUESTOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Repuestos"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(array_sum($arrayVentaMost), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(((($totalProdTaller + array_sum($arrayVentaMost)) > 0) ? array_sum($arrayVentaMost) * 100 / ($totalProdTaller + array_sum($arrayVentaMost)) : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 60, "-", STR_PAD_BOTH),$textColor);

// TOTAL
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(($totalProdTaller + array_sum($arrayVentaMost)), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("PRODUCCIÓN TALLER"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 22, " ", STR_PAD_BOTH),$textColor);
$posX += 110;
foreach ($tipoOrden as $tipo) {
	imagestring($img,1,$posX,$posY,str_pad(strtoupper(substr(utf8_decode($tipo['nombre']),0,14)), 15, " ", STR_PAD_BOTH),$textColor);
	$posX += 75;
}
imagestring($img,1,640,$posY,str_pad(strtoupper(utf8_decode("Total")), 16, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,720,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// MANOS DE OBRA
if (isset($operadores)) {
	foreach ($operadores as $idOperador => $operador) {
		$porcOperador = ($totalProdTaller > 0) ? ($arrayTotalMOOperador[$idOperador] * 100) / $totalProdTaller : 0;
		$porcMO += $porcOperador;
		
		$posX = 0; $posY += 10;
		imagestring($img,1,$posX,$posY,strtoupper(substr($operador,0,22)),$textColor);
		$posX += 110;
		if (isset($tipoOrden)) {
			foreach ($tipoOrden as $idTipo => $tipo) {
				imagestring($img,1,$posX,$posY,str_pad(((isset($dataMo[$idOperador][$idTipo])) ? number_format(round($dataMo[$idOperador][$idTipo],2), 2, ".", ",") : number_format(round(0,2), 2, ".", ",")), 15, " ", STR_PAD_LEFT),$textColor);
				$posX += 75;
			}
		}
		imagestring($img,1,640,$posY,str_pad(number_format(round($arrayTotalMOOperador[$idOperador], 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,720,$posY,str_pad(number_format(round($porcOperador, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE LA MANO DE OBRA
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Sub Total Mano de Obra:"),0,22)),$textColor);
$posX += 110;
if (isset($tipoOrden)) {
	foreach ($tipoOrden as $idTipo => $tipo) {
		$subTotalMO += $arrayTotalMOTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayTotalMOTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalMO, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcMO, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS
if (isset($tipoArticulos)) {
	foreach ($tipoArticulos as $idTipoArticulo => $tipoArticulo) {
		$porcTipoRepuestos = ($totalProdTaller > 0) ? ($totalRepuestoTipoRepuesto[$idTipoArticulo] * 100) / $totalProdTaller : 0;
		$porcRepuestos += $porcTipoRepuestos;
		
		$posX = 0; $posY += 10;
		imagestring($img,1,$posX,$posY,strtoupper(substr($tipoArticulo,0,22)),$textColor);
		$posX += 110;
		if (isset($tipoOrden)) {
			foreach ($tipoOrden as $idTipo => $tipo) {
				imagestring($img,1,$posX,$posY,str_pad(number_format(round($dataRepuesto[$idTipoArticulo][$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				$posX += 75;
			}
		}
		imagestring($img,1,640,$posY,str_pad(number_format(round($totalRepuestoTipoRepuesto[$idTipoArticulo], 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,720,$posY,str_pad(number_format(round($porcTipoRepuestos, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Sub Total Repuestos:"),0,22)),$textColor);
$posX += 110;
if (isset($tipoOrden)) {
	foreach ($tipoOrden as $idTipo => $tipo) {
		$subTotalRepServ += $arrayTotalRepuestoTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($arrayTotalRepuestoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
imagestring($img,1,640,$posY,str_pad(number_format(round($subTotalRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcRepuestos, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL DE DESCUENTO DE REPUESTOS
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Sub Total Descuento Repuestos:"),0,22)),$textColor);
$posX += 110;
if (isset($tipoOrden)) {
	foreach ($tipoOrden as $idTipo => $tipo) {
		imagestring($img,1,$posX,$posY,str_pad(number_format((-1)*round($arrayTotalRepuestoDescuentoTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcDescuentoRepServ = ($totalProdTaller > 0) ? ($totalDescuentoRepServ * 100) / $totalProdTaller : 0;
imagestring($img,1,640,$posY,str_pad(number_format((-1)*round($totalDescuentoRepServ, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format((-1)*round($porcDescuentoRepServ, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// TOTAL DE NOTAS
if ($rowConfig300['valor'] == 1) {
	$posX = 0; $posY += 10;
	imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Sub Total Notas:"),0,22)),$textColor);
	$posX += 110;
	if (isset($tipoOrden)) {
		foreach ($tipoOrden as $idTipo => $tipo) {
			imagestring($img,1,$posX,$posY,str_pad(round($arrayTotalNotaTipoOrden[$idTipo], 2), 15, " ", STR_PAD_LEFT),$textColor);
			$posX += 75;
		}
	}
	$porcNota = ($totalProdTaller > 0) ? ($totalNota * 100) / $totalProdTaller : 0;
	imagestring($img,1,640,$posY,str_pad(round($totalNota, 2), 16, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,720,$posY,str_pad(round($porcNota, 2), 8, " ", STR_PAD_LEFT),$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// TOTAL
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("Total Producción Taller:"),0,22)),$textColor);
$posX += 110;
if (isset($tipoOrden)) {
	foreach ($tipoOrden as $idTipo => $tipo) {
		$totalTipoOrden = $arrayTotalMOTipoOrden[$idTipo] + $arrayTotalRepuestoTipoOrden[$idTipo] - $arrayTotalRepuestoDescuentoTipoOrden[$idTipo];
		$totalTipoOrden += ($rowConfig300['valor'] == 1) ? $arrayTotalNotaTipoOrden[$idTipo] : 0;
		$porcTotalTipoOrden[$idTipo] = ($totalProdTaller > 0) ? ($totalTipoOrden * 100) / $totalProdTaller : 0;
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($totalTipoOrden, 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcTotalProdTaller = $porcMO + $porcRepuestos - $porcDescuentoRepServ;
$porcTotalProdTaller += ($rowConfig300['valor'] == 1) ? $porcNota : 0;
imagestring($img,1,640,$posY,str_pad(number_format(round($totalProdTaller, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,720,$posY,str_pad(number_format(round($porcTotalProdTaller, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// PARTICIPACION
$posX = 0; $posY += 10;
imagestring($img,1,$posX,$posY,strtoupper(substr(utf8_decode("% Participación"),0,22)),$textColor);
$posX += 110;
$porcentajeTotal = 0;
if (isset($tipoOrden)) {
	foreach ($tipoOrden as $idTipo => $tipo) {
		$porcentajeTotal += $porcTotalTipoOrden[$idTipo];
		
		imagestring($img,1,$posX,$posY,str_pad(number_format(round($porcTotalTipoOrden[$idTipo], 2), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		$posX += 75;
	}
}
$porcTotalProdTaller = $porcMO + $porcRepuestos + $porcNota - $porcDescuentoRepServ;
imagestring($img,1,640,$posY,str_pad(number_format(round($porcentajeTotal, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PRODUCCION REPUESTOS MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("PRODUCCIÓN REPUESTOS MOSTRADOR"), 100, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Contado")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Crédito")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("Total")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,460,$posY,str_pad(utf8_decode("%"), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

// VENTAS ITINERANTES
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Ventas Itinerantes"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round(0, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

// MOSTRADOR PUBLICO
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Mostrador Público"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayVentaMost[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($arrayVentaMost[1], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(array_sum($arrayVentaMost), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? array_sum($arrayVentaMost) * 100 / (array_sum($arrayVentaMost)) : 0), 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

// TOTAL REPUESTOS MOSTRADOR
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Producción Repuestos Mostrador:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayVentaMost[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($arrayVentaMost[1], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(array_sum($arrayVentaMost), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

// PARTICIPACION
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("% Participación"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[0] * 100 / array_sum($arrayVentaMost) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[1] * 100 / array_sum($arrayVentaMost) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR"), 92, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Conceptos")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Costo")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Utl. Bruta")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%Utl. Bruta")), 20, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS POR SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Repuestos por Servicios"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayCostoRepServ[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(((($subTotalRepServ - $totalDescuentoRepServ) > 0) ? ((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]) * 100) / ($subTotalRepServ - $totalDescuentoRepServ) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

// REPUESTOS POR MOSTRADOR
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Repuestos por Mostrador"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($arrayCostoRepMost[0], 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round(((array_sum($arrayVentaMost) > 0) ? ((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]) * 100) / array_sum($arrayVentaMost) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ANÁLISIS DE INVENTARIO
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("ANÁLISIS DE INVENTARIO"), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clasif.")), 12, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,60,$posY,str_pad(strtoupper(utf8_decode("Nro. Items")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,130,$posY,str_pad(strtoupper(utf8_decode("% Items")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(strtoupper(utf8_decode("Existencia")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,270,$posY,str_pad(strtoupper(utf8_decode("% Existencia")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,340,$posY,str_pad(strtoupper(utf8_decode("Importe Bs.")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,410,$posY,str_pad(strtoupper(utf8_decode("% Importe Bs.")), 14, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,480,$posY,str_pad(strtoupper(utf8_decode("Prom. Ventas Bs.")), 16, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,560,$posY,str_pad(strtoupper(utf8_decode("Meses Exist.")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,660,$posY,str_pad(strtoupper(utf8_decode("Exist. / Nro. Items")), 20, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayAnalisisInv)) {
	foreach ($arrayAnalisisInv as $indice => $valor) {
		$arrayFila = NULL;
		$arrayFila[] = $arrayAnalisisInv[$indice][0];
		$arrayFila[] = round($arrayAnalisisInv[$indice][1],2);
		$arrayFila[] = round((($totalCantArt > 0) ? ($arrayAnalisisInv[$indice][1] * 100 / $totalCantArt) : 0),2);
		$arrayFila[] = round($arrayAnalisisInv[$indice][2],2);
		$arrayFila[] = round((($totalExistArt > 0) ? ($arrayAnalisisInv[$indice][2] * 100 / $totalExistArt) : 0),2);
		$arrayFila[] = round($arrayAnalisisInv[$indice][3],2);
		$arrayFila[] = round((($totalCostoInv > 0) ? ($arrayAnalisisInv[$indice][3] * 100 / $totalCostoInv) : 0),2);
		$arrayFila[] = round($arrayAnalisisInv[$indice][4],2);
		$arrayFila[] = round($arrayAnalisisInv[$indice][5],2);
		$arrayFila[] = round(($arrayAnalisisInv[$indice][2] / $arrayAnalisisInv[$indice][1]),2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,12)), 12, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,60,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,130,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,200,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,270,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,340,$posY,str_pad(number_format($arrayFila[5], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,410,$posY,str_pad(number_format($arrayFila[6], 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,480,$posY,str_pad(number_format($arrayFila[7], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,560,$posY,str_pad(number_format($arrayFila[8], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,660,$posY,str_pad(number_format($arrayFila[9], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Totales:"),0,12)),$textColor);
imagestring($img,1,60,$posY,str_pad(number_format(round($totalCantArt, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,130,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,200,$posY,str_pad(number_format(round($totalExistArt, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,270,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,340,$posY,str_pad(number_format(round($totalCostoInv, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,410,$posY,str_pad(number_format(round(100, 2), 2, ".", ","), 14, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,480,$posY,str_pad(number_format(round($totalPromVentas, 2), 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,560,$posY,str_pad(number_format(round((($totalPromVentas > 0) ? ($totalCostoInv / $totalPromVentas) : 0), 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,660,$posY,str_pad(number_format(round($totalExistNroArt, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS"), 92, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clasif.")), 12, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,60,$posY,str_pad(strtoupper(utf8_decode("Nro. Items Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("% Items Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Cant. Art. Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("% Art. Vendidos")), 20, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayCantArtVend)) {
	foreach ($arrayCantArtVend as $indice => $valor) {
		$arrayFila = NULL;
		$arrayFila[] = $arrayCantArtVend[$indice][0];
		$arrayFila[] = round($arrayCantArtVend[$indice][1],2);
		$arrayFila[] = round((($totalNroArt > 0) ? ($arrayCantArtVend[$indice][1] * 100 / $totalNroArt) : 0),2);
		$arrayFila[] = round($arrayCantArtVend[$indice][2],2);
		$arrayFila[] = round((($totalCantArtVend > 0) ? ($arrayCantArtVend[$indice][2] * 100 / $totalCantArtVend) : 0),2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(substr($arrayFila[0],0,12)), 12, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,60,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,160,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Totales:"),0,12)),$textColor);
imagestring($img,1,60,$posY,str_pad(number_format($totalNroArt, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(100, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalCantArtVend, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// INDICADORES DE TALLER
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("INDICADORES DE TALLER"), 92, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Indicador")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Unidad")), 40, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// MANO DE OBRA
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Mano de Obra"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($subTotalMO, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// DIAS HABILES
$diaHabiles = evaluaFecha(diasHabiles('01-'.$valFecha[0].'-'.$valFecha[1], ultimoDia($valFecha[0],$valFecha[1]).'-'.$valFecha[0].'-'.$valFecha[1]));
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Días Hábiles Mes"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($diaHabiles - $totalRowsDiasFeriados, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// NUMERO DE TECNICOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Nro. Técnicos"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[1], 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// HORAS DISPONIBLE VENTA TECNICOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Disp. Venta Técnicos"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[1] * 7.1 * ($diaHabiles - $totalRowsDiasFeriados), 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// HORAS PROMEDIO TECNICOS
$posY += 10;
$HrsPromTec = ($arrayMecanico[1] > 0) ? ($arrayMecanico[1] * 7.1 * ($diaHabiles - $totalRowsDiasFeriados)) / $arrayMecanico[1] : 0;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Prom / Técnicos"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($HrsPromTec, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// NUMERO DE TECNICOS EN FORMACION
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Nro. Técnicos en Formación"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[0], 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// HORAS DISPONIBLE VENTA TECNICOS EN FORMACION
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Disp. Venta Técnicos en Formación"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($arrayMecanico[0] * 3.57 * ($diaHabiles - $totalRowsDiasFeriados), 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

// HORAS PROMEDIO TECNICOS EN FORMACION
$posY += 10;
$HrsPromTecFormacion = ($arrayMecanico[0] > 0) ? ($arrayMecanico[0] * 3.57 * ($diaHabiles - $totalRowsDiasFeriados)) / $arrayMecanico[0] : 0;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. Prom / Técnicos en Formación"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($HrsPromTecFormacion, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// ENTRADA DE VEHICULOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Entrada de Vehículos"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalValeRecepcion, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);
	
// ORDENES DE SERVICIOS ABIERTAS
if (isset($arrayTipoOrden)) {
	foreach ($arrayTipoOrden as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("O/R Abiertas ".$arrayTipoOrden[$indice][0]),0,32)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayTipoOrden[$indice][1], 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE ORDENES ABIERTAS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total O/R Abiertas"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalTipoOrdenAbierta, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// ORDENES DE SERVICIOS CERRADAS
if (isset($arrayTipoOrden)) {
	foreach ($arrayTipoOrden as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("O/R Cerradas ".$arrayTipoOrden[$indice][0]),0,32)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayTipoOrden[$indice][2], 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// TOTAL DE ORDENES CERRADAS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total O/R Cerradas"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalTipoOrdenCerrada, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// REPUESTOS POR SERVICIOS
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Rptos. Servicios"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($subTotalRepServ - $totalDescuentoRepServ, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// BS REPUESTOS ENTRE ORDENES
$posY += 10;
$totalTipoOrdenCerrada = ($totalTipoOrdenCerrada > 0) ? $totalTipoOrdenCerrada : 1;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Bs. Rptos / OR"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(($subTotalRepServ - $totalDescuentoRepServ) / $totalTipoOrdenCerrada, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 92, "-", STR_PAD_BOTH),$textColor);

// HORAS ENTRE ORDENES
$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Hrs. / OR"),0,32)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalTotalUtsEquipos / $totalTipoOrdenCerrada, 2, ".", ","), 40, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN ASESORES DE SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("FACTURACIÓN ASESORES DE SERVICIOS"), 100, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 120, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Asesor")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("M/Obra")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Rptos.")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("T.O.T.")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,460,$posY,str_pad(strtoupper(utf8_decode("Total")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,560,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 120, "-", STR_PAD_BOTH),$textColor);

for ($i = 0; $i < count($arrayVentaAsesor); $i++) {
	$porcAsesor = ($arrayVentaAsesor[$i]['total_asesor'] * 100) / $total4;
	
	$arrayFila = NULL;
	$arrayFila[] = utf8_encode($arrayVentaAsesor[$i]['nombre_asesor']);
	$arrayFila[] = round($arrayVentaAsesor[$i]['total_mo'],2);
	$arrayFila[] = round($arrayVentaAsesor[$i]['total_repuestos'],2);
	$arrayFila[] = round($arrayVentaAsesor[$i]['total_tot'],2);
	$arrayFila[] = round($arrayVentaAsesor[$i]['total_asesor'],2);
	$arrayFila[] = round($porcAsesor,2);
	
	$posY += 10;
	imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,32)),$textColor);
	imagestring($img,1,160,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,460,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,560,$posY,str_pad(number_format($arrayFila[5], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	
	$porcTotalAsesor += $porcAsesor;
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 120, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación Asesores:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($total1, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($total2, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round($total3, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round($total4, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,560,$posY,str_pad(number_format(round($porcTotalAsesor, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN VENDEDORES DE REPUESTOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("FACTURACIÓN VENDEDORES DE REPUESTOS"), 100, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Vendedor")), 32, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,160,$posY,str_pad(strtoupper(utf8_decode("Contado")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Crédito")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("Total")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,460,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayVentaVendedor)) {
	foreach ($arrayVentaVendedor as $indice => $valor) {
		$porcVendedor = ($arrayVentaVendedor[$indice][3] * 100) / $totalVentaVendedores;
		
		$arrayFila = NULL;
		$arrayFila[] = $arrayVentaVendedor[$indice][0];
		$arrayFila[] = round($arrayVentaVendedor[$indice][1],2);
		$arrayFila[] = round($arrayVentaVendedor[$indice][2],2);
		$arrayFila[] = round($arrayVentaVendedor[$indice][3],2);
		$arrayFila[] = round($porcVendedor,2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,32)),$textColor);
		imagestring($img,1,160,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad(number_format($arrayFila[4], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		
		$totalVentaContado += $arrayVentaVendedor[$indice][1];
		$totalVentaCredito += $arrayVentaVendedor[$indice][2];
		$porcTotalVendedor += $porcVendedor;
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación Vendedores:"),0,32)),$textColor);
imagestring($img,1,160,$posY,str_pad(number_format(round($totalVentaContado, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format(round($totalVentaCredito, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(round($totalVentaVendedores, 2), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,460,$posY,str_pad(number_format(round($porcTotalVendedor, 2), 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FACTURACIÓN TÉCNICOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("FACTURACIÓN TÉCNICOS"), 100, " ", STR_PAD_BOTH),$textColor);

if (isset($arrayVentaTecnico)) {
	foreach ($arrayVentaTecnico as $indice => $valor) {
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad($arrayVentaTecnico[$indice][0], 100, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Técnicos ".$arrayVentaTecnico[$indice][0])), 52, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("UT'S")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("Bs.")), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,460,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
		
		$totalUtsEquipo = 0;
		$totalBsEquipo = 0;
		if (isset($arrayVentaTecnico[$indice][1])) {
			foreach ($arrayVentaTecnico[$indice][1] as $indice2 => $valor2) {
				$totalUtsEquipo += $valor2['total_uts'];
				$totalBsEquipo += $valor2['total_bs'];
			}
		}
		
		$porcTotalEquipo = 0;
		if (isset($arrayVentaTecnico[$indice][1])) {
			foreach ($arrayVentaTecnico[$indice][1] as $indice2 => $valor2) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$porcMecanico = ($totalBsEquipo > 0) ? ($valor2['total_bs'] * 100) / $totalBsEquipo : 0;
		
				$arrayFila = NULL;
				$arrayFila[] = $valor2['nombre_mecanico'];
				$arrayFila[] = round($valor2['total_uts'],2);
				$arrayFila[] = round($valor2['total_bs'],2);
				$arrayFila[] = round($porcMecanico,2);
				
				$posY += 10;
				imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,52)),$textColor);
				imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,460,$posY,str_pad(number_format($arrayFila[3], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
				
				$porcTotalEquipo += $porcMecanico;
			}
		}
		
		$posY += 10;
		imagestring($img,1,0,$posY,str_pad("", 100, "-", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Facturación Técnicos:"),0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($totalUtsEquipo, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($totalBsEquipo, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad(number_format($porcTotalEquipo, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMPRAS DE REPUESTOS Y ACCESORIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("COMPRAS DE REPUESTOS Y ACCESORIOS"), 80, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clave de Movimiento")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Importe Bs")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayMovCompras)) {
	foreach ($arrayMovCompras as $indice => $valor) {
		$arrayFila = NULL;
		$arrayFila[] = $arrayMovCompras[$indice][1];
		if ($arrayMovCompras[$indice][0] == 1 || $arrayMovCompras[$indice][0] == 3) {
			$arrayFila[] = round($arrayMovCompras[$indice][2],2);
		} else if ($arrayMovCompras[$indice][0] == 2 || $arrayMovCompras[$indice][0] == 4) {
			$arrayFila[] = round((-1) * $arrayMovCompras[$indice][2],2);
		}
		$arrayFila[] = round((($arrayMovCompras[$indice][2] * 100) / $totalNetoClaveMovCompras),2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Compras Repuestos y Accesorios:"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalNetoClaveMovCompras, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("RESÚMEN DE POST-VENTA ".$valFecha[0]."-".$valFecha[1]), 152, " ", STR_PAD_BOTH),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR MOSTRADOR
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS DE REPUESTOS POR MOSTRADOR"), 80, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clave de Movimiento")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Importe Bs")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayMovVentas)) {
	foreach ($arrayMovVentas as $indice => $valor) {
		$arrayFila = NULL;
		$arrayFila[] = $arrayMovVentas[$indice][1];
		if ($arrayMovVentas[$indice][0] == 1 || $arrayMovVentas[$indice][0] == 3) {
			$arrayFila[] = round($arrayMovVentas[$indice][2],2);
		} else if ($arrayMovVentas[$indice][0] == 2 || $arrayMovVentas[$indice][0] == 4) {
			$arrayFila[] = round((-1)*$arrayMovVentas[$indice][2],2);
		}
		$arrayFila[] = round((($arrayMovVentas[$indice][2] * 100) / $totalNetoClaveMovVentas),2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Ventas Repuestos y Accesorios:"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalNetoClaveMovVentas, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS DE REPUESTOS POR SERVICIOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS DE REPUESTOSVENTAS DE REPUESTOS POR MOSTRADOR POR SERVICIOS"), 80, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Clave de Movimiento")), 52, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,260,$posY,str_pad(strtoupper(utf8_decode("Importe Bs")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,360,$posY,str_pad(strtoupper(utf8_decode("%")), 8, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

if (isset($arrayMovVentasServ)) {
	foreach ($arrayMovVentasServ as $indice => $valor) {
		$arrayFila = NULL;
		$arrayFila[] = $arrayMovVentasServ[$indice][1];
		if ($arrayMovVentasServ[$indice][0] == 1 || $arrayMovVentasServ[$indice][0] == 3) {
			$arrayFila[] = round($arrayMovVentasServ[$indice][2],2);
		} else if ($arrayMovVentasServ[$indice][0] == 2 || $arrayMovVentasServ[$indice][0] == 4) {
			$arrayFila[] = round((-1)*$arrayMovVentasServ[$indice][2],2);
		}
		$arrayFila[] = round((($arrayMovVentasServ[$indice][2] * 100) / $totalNetoClaveMovVentasServ),2);
		
		$posY += 10;
		imagestring($img,1,0,$posY,strtoupper(substr($arrayFila[0],0,52)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayFila[1], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad(number_format($arrayFila[2], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
	}
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 80, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,strtoupper(substr(utf8_decode("Total Ventas Repuestos y Accesorios por Servicios:"),0,52)),$textColor);
imagestring($img,1,260,$posY,str_pad(number_format($totalNetoClaveMovVentasServ, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,360,$posY,str_pad(number_format(100, 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."resumen_postventa".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO
		if ($rowEmp['id_empresa'] != "") {
			$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			
			$pdf->SetY(15);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',5);
			$pdf->SetX(88);
			$pdf->Cell(200,9,utf8_decode($rowEmp['nombre_empresa']),0,2,'L');
			
			if (strlen($rowEmp['rif']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_decode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
			}
			if (strlen($rowEmp['direccion']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_decode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, 60, 758, 520);
		
		$pdf->SetY(-35);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',6);
		$pdf->Cell(780,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
		$pdf->SetY(-35);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
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