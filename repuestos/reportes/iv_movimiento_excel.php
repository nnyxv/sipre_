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
	$lstTipoMovimiento = array(1 => "Compra", 2 => "Entrada", 3 => "Venta", 4 => "Salida");
	foreach ($lstTipoMovimiento as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayTipoMovimiento[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Tipo Mov.: ".((isset($arrayTipoMovimiento)) ? implode(", ", $arrayTipoMovimiento) : "");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	foreach (explode(",", $valCadBusq[4]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayModulo[] = utf8_encode($row['descripcionModulo']);
		}
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	foreach (explode(",", $valCadBusq[5]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayClaveMovimiento[] = utf8_encode($row['clave'].") ".$row['descripcion']);
		}
	}
	$arrayCriterioBusqueda[] = "Clave Mov.: ".((isset($arrayClaveMovimiento)) ? implode(", ", $arrayClaveMovimiento) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	foreach (explode(",", $valCadBusq[6]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM vw_pg_empleados empleado WHERE id_empleado = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayEmpleado[] = utf8_encode($row['nombre_empleado']);
		}
	}
	$arrayCriterioBusqueda[] = "Empleado: ".((isset($arrayEmpleado)) ? implode(", ", $arrayEmpleado) : "");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	foreach (explode(",", $valCadBusq[7]) as $indice => $valor) {
		$rs = mysql_query(sprintf("SELECT * FROM iv_tipos_articulos WHERE id_tipo_articulo = %s;", valTpDato($valor, "int")));
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayTipoArticulo[] = utf8_encode($row['descripcion']);
		}
	}
	$arrayCriterioBusqueda[] = "Tipo de Artículo: ".((isset($arrayTipoArticulo)) ? implode(", ", $arrayTipoArticulo) : "");
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[8];
}

////////// CRITERIO DE BUSQUEDA //////////
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_mov.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_mov.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(fecha_movimiento) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_movimiento IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_clave_movimiento IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado_vendedor = %s",
		valTpDato($valCadBusq[6], "int"));
}
	
if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(mov_det.id_movimiento) FROM iv_movimiento_detalle mov_det
		INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo)
	WHERE mov_det.id_movimiento = vw_iv_mov.id_movimiento
		AND art.id_tipo_articulo IN (%s)) > 0",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(numero_documento LIKE %s
	OR folio LIKE %s
	OR (CASE (vw_iv_mov.tipo_proveedor_cliente_empleado)
			WHEN (1) THEN
				(SELECT nombre FROM cp_proveedor
				WHERE id_proveedor = vw_iv_mov.id_proveedor_cliente_empleado)
				
			WHEN (2) THEN
				(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
				WHERE id = vw_iv_mov.id_proveedor_cliente_empleado)
				
			WHEN (3) THEN
				(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
				WHERE id_empleado = vw_iv_mov.id_proveedor_cliente_empleado)
		END) LIKE %s)",
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT vw_iv_mov.* FROM vw_iv_movimiento vw_iv_mov %s
ORDER BY id_tipo_movimiento, clave, id_movimiento ASC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$contFila = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idModulo = $row['id_modulo'];
		
	switch ($idModulo) {
		case 0 : $imgModuloDcto = "Repuestos"; break;
		case 1 : $imgModuloDcto = "Servicios"; break;
		case 2 : $imgModuloDcto = "Vehículos"; break;
		case 3 : $imgModuloDcto = "Administración"; break;
		default : $imgModuloDcto = "";
	}
	
	if ($row['tipo_proveedor_cliente_empleado'] == 1) { // PROVEEDOR
		$queryProvClienteEmpleado = sprintf("SELECT
			CONCAT_WS('-', lrif, rif) AS rif_proveedor,
			nombre
		FROM cp_proveedor
		WHERE id_proveedor = %s;",
			$row['id_proveedor_cliente_empleado']);
		$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
		if (!$rsProvClienteEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
		$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre'];
		$rifProvClienteEmpleado = $rowProvClienteEmpleado['rif_proveedor'];
	} else if ($row['tipo_proveedor_cliente_empleado'] == 2) { // CLIENTE
		$queryProvClienteEmpleado = sprintf("SELECT
			CONCAT_WS('-', lci, ci) AS ci_cliente,
			CONCAT_WS(' ', nombre, apellido) AS nombre_cliente
		FROM cj_cc_cliente
		WHERE id = %s ",
			$row['id_proveedor_cliente_empleado']);
		$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
		if (!$rsProvClienteEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
		$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_cliente'];
		$rifProvClienteEmpleado = $rowProvClienteEmpleado['ci_cliente'];
	} else if ($row['tipo_proveedor_cliente_empleado'] == 3) { // EMPLEADO
		$queryProvClienteEmpleado = sprintf("SELECT
			cedula,
			CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado
		FROM pg_empleado
		WHERE id_empleado = %s",
			$row['id_proveedor_cliente_empleado']);
		$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
		if (!$rsProvClienteEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
		$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_empleado'];
		$rifProvClienteEmpleado = $rowProvClienteEmpleado['cedula'];
	}
	
	if ($auxActual != $row['id_clave_movimiento']) {
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($row['descripcion_tipo_movimiento'])." - ".$row['clave'].") ".utf8_encode($row['descripcion_clave_movimiento']));
	
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":O".$contFila)->applyFromArray($styleArrayTitulo);
	
		$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":O".$contFila);
		
		$auxActual = $row['id_clave_movimiento'];
		
		$contFila++;
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro. Dcto:");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $imgModuloDcto);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['numero_documento']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Control / Folio:");
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("G".$contFila, $row['folio'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Fecha Dcto.:");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, date(spanDateFormat,strtotime($row['fecha_documento'])));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Fecha Registro / Captura:");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, date(spanDateFormat,strtotime($row['fecha_captura'])));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Prov./Clnte./Emp.:");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rifProvClienteEmpleado);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $nombreProvClienteEmpleado);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Orden::");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $row['numero_orden']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Remis:");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $row['numero_documento']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);
	$objPHPExcel->getActiveSheet()->mergeCells("E".$contFila.":H".$contFila);
	
	$contFila++;
	$primero = $contFila;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Código");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Descripción");
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Lote");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Cant.");
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Descripción Precio");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanPrecioUnitario);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "PMU Unit.");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Costo Unit.");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Importe Precio");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Importe PMU");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Dscto.");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Neto");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Importe Costo");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Utl.");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "%Utl. S/V");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "%Utl. S/C");
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "%Dscto.");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($styleArrayColumna);
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("mov_det.id_movimiento = %s",
		valTpDato($row['id_movimiento'], "int"));
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	$queryDetalle = sprintf("SELECT 
		mov_det.id_movimiento_detalle,
		art.codigo_articulo,
		art.descripcion,
		mov_det.id_kardex,
		mov_det.id_articulo_costo,
		mov_det.cantidad,
		(CASE mov.id_tipo_movimiento
			WHEN 1 THEN -- COMPRA
				(IFNULL(mov_det.precio,0)
					+ IFNULL(mov_det.costo_cargo,0)
					+ IFNULL(mov_det.costo_diferencia,0))
			ELSE
				(IFNULL(mov_det.precio,0))
		END) AS precio,
		mov_det.pmu_unitario,
		(IFNULL(mov_det.costo,0)
			+ IFNULL(mov_det.costo_cargo,0)
			+ IFNULL(mov_det.costo_diferencia,0)) AS costo,
		mov_det.porcentaje_descuento,
		
		(SELECT 
			precio.descripcion_precio
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_factura_detalle cxc_fact_det ON (cxc_fact.idFactura = cxc_fact_det.id_factura)
			LEFT JOIN iv_pedido_venta_detalle ped_vent_det ON (cxc_fact.numeroPedido = ped_vent_det.id_pedido_venta
				AND cxc_fact_det.id_articulo = ped_vent_det.id_articulo
				AND cxc_fact_det.cantidad = ped_vent_det.cantidad
				AND cxc_fact.idDepartamentoOrigenFactura IN (0))
			LEFT JOIN sa_det_orden_articulo det_orden_art ON (cxc_fact.numeroPedido = det_orden_art.id_orden
				AND cxc_fact_det.id_articulo = det_orden_art.id_articulo
				AND cxc_fact_det.cantidad = det_orden_art.cantidad
				AND det_orden_art.estado_articulo IN ('FACTURADO','DEVUELTO')
				AND cxc_fact.idDepartamentoOrigenFactura IN (1))
			LEFT JOIN pg_precios precio ON ((ped_vent_det.id_precio = precio.id_precio AND cxc_fact.idDepartamentoOrigenFactura IN (0))
				OR (det_orden_art.id_precio = precio.id_precio AND cxc_fact.idDepartamentoOrigenFactura IN (1)))
		WHERE cxc_fact.idFactura = mov.id_documento
			AND cxc_fact_det.id_articulo = mov_det.id_articulo
			AND cxc_fact_det.id_articulo_costo = mov_det.id_articulo_costo
			AND cxc_fact_det.cantidad = mov_det.cantidad
		LIMIT 1) AS descripcion_precio
	FROM iv_movimiento mov
		INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
		INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
	ORDER BY id_movimiento_detalle;", $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contFila2 = 0;
	$arrayTotal = NULL;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
		$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
		$importePMU = $rowDetalle['cantidad'] * $rowDetalle['pmu_unitario'];
		$descuento = $rowDetalle['porcentaje_descuento'] * ($importePrecio + $importePMU) / 100;
		$neto = ($importePrecio + $importePMU) - $descuento;
		
		$importeCosto = ($row['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
		
		$porcUtilidadCosto = 0;
		$porcUtilidadVenta = 0;
		if (($importePrecio + $importePMU) > 0) {
			$utilidad = $neto - $importeCosto;
			
			$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
			$porcUtilidadVenta = $utilidad * 100 / ($importePrecio + $importePMU);
		}
		
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("A".$contFila, elimCaracter($rowDetalle['codigo_articulo'],";"), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, $rowDetalle['descripcion'], PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $rowDetalle['id_articulo_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowDetalle['cantidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $rowDetalle['descripcion_precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $rowDetalle['precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $rowDetalle['pmu_unitario']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $rowDetalle['costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $importePrecio);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $importePMU);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $descuento);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $neto);
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $importeCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $utilidad);
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $porcUtilidadVenta);
		$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $porcUtilidadCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $rowDetalle['porcentaje_descuento']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
		$arrayTotal['cantidad'] += $rowDetalle['cantidad'];
		$arrayTotal['importe_precio'] += $importePrecio;
		$arrayTotal['importe_pmu'] += $importePMU;
		$arrayTotal['descuento'] += $descuento;
		$arrayTotal['importe_neto'] += $neto;
		$arrayTotal['importe_costo'] += $importeCosto;
		$arrayTotal['utilidad'] += $utilidad;
	}
	$ultimo = $contFila;
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total Dcto. ".$row['documento'].":");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotal['cantidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal['importe_precio']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $arrayTotal['importe_pmu']);
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $arrayTotal['descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $arrayTotal['importe_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTotal['importe_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotal['utilidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, (($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, (($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo'] : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, ((($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) > 0) ? ($arrayTotal['descuento'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) : 0));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila.":"."Q".$contFila)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
	
	//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "Q", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Movimientos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:Q7");
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:Q9");
}
 
//Trabajamos con la hoja activa secundaria
$objPHPExcel->createSheet(NULL, 1);
$objPHPExcel->setActiveSheetIndex(1);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Clave de Movimiento");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Cant. Dctos.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Importe Precio");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Importe PMU");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Dscto.");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Importe Neto");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Importe Costo");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Utl.");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "%Utl. S/V");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "%Utl. S/C");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "%Dscto.");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayColumna);

for ($idTipoMovimiento = 1; $idTipoMovimiento <= 4; $idTipoMovimiento++) {
	$contFila++;
	
	$arrayTipoMovimiento = array(1 => "Compra", 2 => "Entrada", 3 => "Venta", 4 => "Salida");
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $arrayTipoMovimiento[$idTipoMovimiento]);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("vw_iv_mov.id_tipo_movimiento IN (%s)",
		valTpDato($idTipoMovimiento, "int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(vw_iv_mov.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_mov.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	$query = sprintf("SELECT
		vw_iv_mov.id_clave_movimiento,
		vw_iv_mov.clave,
		vw_iv_mov.descripcion_clave_movimiento,
		vw_iv_mov.id_tipo_movimiento,
		vw_iv_mov.descripcion_tipo_movimiento,
		vw_iv_mov.id_modulo
	FROM vw_iv_movimiento vw_iv_mov %s %s
	GROUP BY id_clave_movimiento, descripcion_clave_movimiento, id_tipo_movimiento
	ORDER BY clave ASC;", $sqlBusq, $sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contFila2 = 0;
	$arrayDet = NULL;
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("mov.id_clave_movimiento = %s",
			valTpDato($row['id_clave_movimiento'], "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
				(CASE
					WHEN (mov.id_tipo_movimiento = 1) THEN
						(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
						WHERE (cxp_fact.id_factura = mov.id_documento))
					WHEN (mov.id_tipo_movimiento = 2) THEN
						(CASE
							WHEN (mov.tipo_documento_movimiento = 1) THEN
								(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
								WHERE (iv_ve.id_vale_entrada = mov.id_documento))
							WHEN (mov.tipo_documento_movimiento = 2) THEN
								(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
								WHERE (cxc_nc.idNotaCredito = mov.id_documento))
						END)
					WHEN (mov.id_tipo_movimiento = 3) THEN
						(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
						WHERE (cxc_fact.idFactura = mov.id_documento))
					WHEN (mov.id_tipo_movimiento = 4) THEN
						(CASE
							WHEN (mov.tipo_documento_movimiento = 1) THEN
								(CASE
									WHEN (clave_mov.id_modulo = 0) THEN
										(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
										WHERE (iv_vs.id_vale_salida = mov.id_documento))
									WHEN (clave_mov.id_modulo = 1) THEN
										(SELECT orden.id_empresa AS id_empresa
										FROM (sa_vale_salida sa_vs
											INNER JOIN sa_orden orden on ((sa_vs.id_orden = orden.id_orden)))
										WHERE (sa_vs.id_vale_salida = mov.id_documento))
								END)
							WHEN (mov.tipo_documento_movimiento = 2) THEN
								(SELECT cxp_nc.id_empresa AS id_empresa FROM cp_notacredito cxp_nc
								WHERE (cxp_nc.id_notacredito = mov.id_documento))
						END)
				END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE
												WHEN (mov.id_tipo_movimiento = 1) THEN
													(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
													WHERE (cxp_fact.id_factura = mov.id_documento))
												WHEN (mov.id_tipo_movimiento = 2) THEN
													(CASE
														WHEN (mov.tipo_documento_movimiento = 1) THEN
															(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
															WHERE (iv_ve.id_vale_entrada = mov.id_documento))
														WHEN (mov.tipo_documento_movimiento = 2) THEN
															(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
															WHERE (cxc_nc.idNotaCredito = mov.id_documento))
													END)
												WHEN (mov.id_tipo_movimiento = 3) THEN
													(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
													WHERE (cxc_fact.idFactura = mov.id_documento))
												WHEN (mov.id_tipo_movimiento = 4) THEN
													(CASE
														WHEN (mov.tipo_documento_movimiento = 1) THEN
															(CASE
																WHEN (clave_mov.id_modulo = 0) THEN
																	(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
																	WHERE (iv_vs.id_vale_salida = mov.id_documento))
																WHEN (clave_mov.id_modulo = 1) THEN
																	(SELECT orden.id_empresa AS id_empresa
																	FROM (sa_vale_salida sa_vs
																		INNER JOIN sa_orden orden on ((sa_vs.id_orden = orden.id_orden)))
																	WHERE (sa_vs.id_vale_salida = mov.id_documento))
															END)
														WHEN (mov.tipo_documento_movimiento = 2) THEN
															(SELECT cxp_nc.id_empresa AS id_empresa FROM cp_notacredito cxp_nc
															WHERE (cxp_nc.id_notacredito = mov.id_documento))
													END)
											END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE(mov.fecha_movimiento) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (mov.id_tipo_movimiento = 2) THEN
					(CASE
						WHEN (mov.tipo_documento_movimiento = 2) THEN
							(SELECT cxc_nc.id_empleado_vendedor FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = mov.id_documento)
					END)
				WHEN (mov.id_tipo_movimiento = 3) THEN
					(SELECT cxc_fact.idVendedor FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = mov.id_documento)
			END) = %s",
				valTpDato($valCadBusq[6], "int"));
		}
			
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(SELECT COUNT(mov_det.id_movimiento) FROM iv_movimiento_detalle mov_det
				INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo)
			WHERE mov_det.id_movimiento = mov.id_movimiento
				AND art.id_tipo_articulo IN (%s)) > 0",
				valTpDato($valCadBusq[7], "campo"));
		}
		
		if ($valCadBusq[8] != "" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
			(CASE
				WHEN (clave_mov.tipo = 1 OR mov.id_tipo_movimiento = 1) THEN -- COMPRA
					(SELECT cxp_fact.numero_factura_proveedor AS numero_factura_proveedor FROM cp_factura cxp_fact
					WHERE cxp_fact.id_factura = mov.id_documento)
				WHEN (clave_mov.tipo = 2 OR mov.id_tipo_movimiento = 2) THEN -- ENTRADA
					(CASE mov.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT iv_ve.numeracion_vale_entrada AS numeracion_vale_entrada FROM iv_vale_entrada iv_ve
							WHERE iv_ve.id_vale_entrada = mov.id_documento)
						WHEN 2 THEN
							(SELECT nota_cred.numeracion_nota_credito AS numeracion_nota_credito FROM cj_cc_notacredito nota_cred
							WHERE nota_cred.idNotaCredito = mov.id_documento)
					END)
				WHEN (clave_mov.tipo = 3 OR mov.id_tipo_movimiento = 3) THEN -- VENTA
					(SELECT cxc_fact.numeroFactura AS numeroFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = mov.id_documento)
				WHEN (clave_mov.tipo = 4 OR mov.id_tipo_movimiento = 4) THEN -- SALIDA
					(CASE mov.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE clave_mov.id_modulo
								WHEN 0 THEN
									(SELECT iv_vs.numeracion_vale_salida AS numeracion_vale_salida FROM iv_vale_salida iv_vs
									WHERE iv_vs.id_vale_salida = mov.id_documento)
								WHEN 1 THEN
									(SELECT iv_vs.numero_vale AS numero_vale
									FROM sa_vale_salida iv_vs
										INNER JOIN sa_orden orden ON (iv_vs.id_orden = orden.id_orden)
									WHERE iv_vs.id_vale_salida = mov.id_documento)
							END)
						WHEN 2 THEN
							(SELECT cxp_nc.numero_nota_credito AS numero_nota_credito FROM cp_notacredito cxp_nc
							WHERE cxp_nc.id_notacredito = mov.id_documento)
					END)
			END) LIKE %s
			OR (CASE (CASE
						WHEN (clave_mov.tipo = 1 OR mov.id_tipo_movimiento = 1) THEN 1
						WHEN (clave_mov.tipo = 2 OR mov.id_tipo_movimiento = 2) THEN
							(CASE mov.tipo_documento_movimiento
								WHEN 1 THEN
									(CASE (SELECT iv_ve.tipo_vale_entrada AS tipo_vale_entrada FROM iv_vale_entrada iv_ve
											WHERE iv_ve.id_vale_entrada = mov.id_documento)
										WHEN 1 THEN 2
										WHEN 2 THEN 2
										WHEN 3 THEN 2
										WHEN 4 THEN 3
										WHEN 5 THEN 3
									END)
								WHEN 2 THEN 2
							END)
						WHEN (clave_mov.tipo = 3 OR mov.id_tipo_movimiento = 3) THEN 2
						WHEN (clave_mov.tipo = 4 OR mov.id_tipo_movimiento = 4) THEN
							(CASE mov.tipo_documento_movimiento
								WHEN 1 THEN
									(CASE (SELECT iv_vs.tipo_vale_salida AS tipo_vale_salida FROM iv_vale_salida iv_vs
											WHERE iv_vs.id_vale_salida = mov.id_documento)
										WHEN 1 THEN 2
										WHEN 2 THEN 2
										WHEN 3 THEN 2
										WHEN 4 THEN 3
										WHEN 5 THEN 3
									END)
								WHEN 2 THEN 1
							END)
					END)
				WHEN 1 THEN
					(SELECT nombre FROM cp_proveedor
					WHERE id_proveedor = mov.id_cliente_proveedor)
					
				WHEN 2 THEN
					(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
					WHERE id = mov.id_cliente_proveedor)
					
				WHEN 3 THEN
					(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
					WHERE id_empleado = mov.id_cliente_proveedor)
			END) LIKE %s)",
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"));
		}
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("mov_det.id_movimiento IN (SELECT mov.id_movimiento
															FROM iv_movimiento mov
																INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
				valTpDato($valCadBusq[7], "campo"));
		}
		
		$queryDetalle = sprintf("SELECT 
			art.codigo_articulo,
			mov_det.cantidad,
			(CASE mov.id_tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(IFNULL(mov_det.precio,0)
						+ IFNULL(mov_det.costo_cargo,0)
						+ IFNULL(mov_det.costo_diferencia,0))
				ELSE
					(IFNULL(mov_det.precio,0))
			END) AS precio,
			mov_det.pmu_unitario,
			(IFNULL(mov_det.costo,0)
				+ IFNULL(mov_det.costo_cargo,0)
				+ IFNULL(mov_det.costo_diferencia,0)) AS costo,
			mov_det.porcentaje_descuento,
			
			(SELECT COUNT(mov.id_movimiento)
			FROM iv_movimiento mov
				INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s) AS cant_dctos
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
		ORDER BY id_movimiento_detalle;", $sqlBusq2, $sqlBusq3);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$arrayTotal = NULL;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
			$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
			$importePMU = $rowDetalle['cantidad'] * $rowDetalle['pmu_unitario'];
			$descuento = $rowDetalle['porcentaje_descuento'] * ($importePrecio + $importePMU) / 100;
			$neto = ($importePrecio + $importePMU) - $descuento;
			
			$importeCosto = ($row['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
			
			$porcUtilidadCosto = 0;
			$porcUtilidadVenta = 0;
			if (($importePrecio + $importePMU) > 0) {
				$utilidad = $neto - $importeCosto;
				
				$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
				$porcUtilidadVenta = $utilidad * 100 / ($importePrecio + $importePMU);
			}
			
			$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
			$arrayTotal['importe_precio'] += $importePrecio;
			$arrayTotal['importe_pmu'] += $importePMU;
			$arrayTotal['descuento'] += $descuento;
			$arrayTotal['importe_neto'] += $neto;
			$arrayTotal['importe_costo'] += $importeCosto;
			$arrayTotal['utilidad'] += $utilidad;
		}
		
		if (($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) > 0) {
			$porcUtilidadCosto = ($arrayTotal['utilidad'] > 0) ? (($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo']) : 0;
			$porcUtilidadVenta = ($arrayTotal['utilidad'] > 0) ? (($arrayTotal['utilidad'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu'])) : 0;
			
			$porcDescuento = (($arrayTotal['descuento'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']));
		} else {
			$porcUtilidadCosto = 0;
			$porcUtilidadVenta = 0;
			
			$porcDescuento = 0;
		}
		
		switch($row['id_modulo']) {
			case 0 : $imgModulo = "R"; break;
			case 1 : $imgModulo = "S"; break;
		}
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($imgModulo));
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['clave'].") ".$row['descripcion_clave_movimiento']));
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotal['cant_dctos']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotal['importe_precio']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotal['importe_pmu']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotal['descuento']);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal['importe_neto']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal['importe_costo']);
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotal['utilidad']);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $porcUtilidadVenta);
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $porcUtilidadCosto);
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $porcDescuento);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":L".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayDet['cant_dctos'] += $arrayTotal['cant_dctos'];
		$arrayDet['importe_precio'] += $arrayTotal['importe_precio'];
		$arrayDet['importe_pmu'] += $arrayTotal['importe_pmu'];
		$arrayDet['descuento'] += $arrayTotal['descuento'];
		$arrayDet['importe_neto'] += $arrayTotal['importe_neto'];
		$arrayDet['importe_costo'] += $arrayTotal['importe_costo'];
		$arrayDet['utilidad'] += $arrayTotal['utilidad'];
		
		$arrayTotalMovimiento[$idTipoMovimiento] = $arrayDet;
	}
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode("Total ".$arrayTipoMovimiento[$idTipoMovimiento].":"));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['cant_dctos']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['importe_precio']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['importe_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['importe_costo']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $arrayTotalMovimiento[$idTipoMovimiento]['utilidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, (($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] * 100) / ($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'] + $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu'])) : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] * 100) / $arrayTotalMovimiento[$idTipoMovimiento]['importe_costo']) : 0));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, ((($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'] + $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu']) > 0) ? ($arrayTotalMovimiento[$idTipoMovimiento]['descuento'] * 100) / ($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'] + $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu']) : 0));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":B".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."L".$contFila)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "L", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto2 = "Resumen Movimientos";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto2);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:L7");
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto2,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:L9");
}

//Titulo del libro y seguridad
$objPHPExcel->setActiveSheetIndex(0);
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