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

// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO')");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
		valTpDato($valCadBusq[5], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[8])."'", "defined", "'".str_replace(",","','",$valCadBusq[8])."'"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
		valTpDato($valCadBusq[9], "campo"));
}
	
if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
		valTpDato($valCadBusq[10], "campo"));
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_chasis LIKE %s
	OR uni_fis.placa LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"),
		valTpDato("%".$valCadBusq[11]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	vw_iv_modelo.id_uni_bas,
	vw_iv_modelo.nom_uni_bas,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	(CASE vw_iv_modelo.catalogo
		WHEN 0 THEN ''
		WHEN 1 THEN 'En Catálogo'
	END) AS mostrar_catalogo
FROM an_unidad_fisica uni_fis
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
	INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
	LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
		LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version) ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."\nSQL: ".$query);
$contFila = 0;
$primero = $contFila;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $row['vehiculo']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."S".$contFila)->applyFromArray($styleArrayCampo);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."S".$contFila);
	
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Id Unidad Física");
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $spanSerialCarroceria);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Condición");
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $spanSerialMotor);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Color");
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanPlaca);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $spanKilometraje);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Fecha Ingreso");
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Días en Inv.");
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Estado Venta");
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Estado Compra");
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Empresa");
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Almacén");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Nro. Fact. Compra");
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Costo");
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Fecha Pedido");
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Nro. Pedido");
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Cliente");
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Vendedor");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($styleArrayColumna);
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE "; // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$sqlBusq2 .= $cond.sprintf("(ped_vent.estado_pedido IN (0,1,3)
	OR ped_vent.estado_pedido IS NULL)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
		valTpDato($row['id_uni_bas'], "int"));
	
	$queryDetalle = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.id_activo_fijo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.titulo_vehiculo,
		uni_fis.placa,
		uni_fis.tipo_placa,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		uni_fis.kilometraje,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		(CASE
			WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
				IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
			WHEN (an_ve.fecha IS NOT NULL) THEN
				an_ve.fecha
		END) AS fecha_origen,
		IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
				WHEN (an_ve.fecha IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
			END),
		0) AS dias_inventario,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		asig.idAsignacion,
		alm.nom_almacen,
		cxp_fact.id_factura,
		cxp_fact.numero_factura_proveedor,
		cxp_fact.id_modulo AS id_modulo_cxp,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in,
		
		(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
		WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
		
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		ped_vent.fecha AS fecha_pedido,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		vw_pg_empleado.nombre_empleado,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND an_ve.fecha IS NOT NULL
			AND an_ve.tipo_vale_entrada = 1
			AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
		LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
			LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s
	ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version) ASC;", $sqlBusq, $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$arrayTotal = NULL;
	$contFila2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
		$contFila++;
		$contFila2++;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $rowDetalle['id_unidad_fisica']);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("B".$contFila, utf8_encode($rowDetalle['serial_carroceria']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($rowDetalle['condicion_unidad']));
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, utf8_encode($rowDetalle['serial_motor']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($rowDetalle['color_externo1']));
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("F".$contFila, utf8_encode($rowDetalle['placa']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $rowDetalle['kilometraje']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, (($rowDetalle['fecha_origen'] != "") ? date(spanDateFormat, strtotime($rowDetalle['fecha_origen'])) : ""));
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $rowDetalle['dias_inventario']);
		$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($rowDetalle['estado_venta']));
		$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($rowDetalle['estado_venta'] == "RESERVADO" && $rowDetalle['estado_compra'] != "REGISTRADO") ? "(".utf8_encode($rowDetalle['estado_compra']).")" : ""));
		$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($rowDetalle['nombre_empresa']));
		$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($rowDetalle['nom_almacen']));
		$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $rowDetalle['numero_factura_proveedor']);
		$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, ($rowDetalle['precio_compra'] + $rowDetalle['costo_agregado'] - $rowDetalle['costo_depreciado'] - $rowDetalle['costo_trade_in']));
		$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, (($rowDetalle['fecha_pedido'] != "") ? date(spanDateFormat, strtotime($rowDetalle['fecha_pedido'])) : ""));
		$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $rowDetalle['numeracion_pedido']);
		$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, utf8_encode($rowDetalle['nombre_cliente']));
		$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, utf8_encode($rowDetalle['nombre_empleado']));
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":S".$contFila)->applyFromArray($clase);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$arrayTotal['cant_unidades'] += 1;
		$arrayTotal['precio_compra'] += ($rowDetalle['precio_compra'] + $rowDetalle['costo_agregado'] - $rowDetalle['costo_depreciado'] - $rowDetalle['costo_trade_in']);
		$arrayTotal['precio_unitario'] += $rowDetalle['precio_unitario'];
	}
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Subtotal:");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotal['cant_unidades']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $arrayTotal['precio_compra']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."M".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila.":"."S".$contFila)->applyFromArray($styleArrayResaltarTotal);
	
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."M".$contFila);
	
	$contFila++;
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
	
	$arrayTotalFinal['cant_unidades'] += $arrayTotal['cant_unidades'];
	$arrayTotalFinal['precio_compra'] += $arrayTotal['precio_compra'];
	$arrayTotalFinal['precio_unitario'] += $arrayTotal['precio_unitario'];
}

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total de Totales:");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotalFinal['cant_unidades']);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $arrayTotalFinal['precio_compra']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."M".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("N".$contFila.":"."S".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."M".$contFila);

$ultimo = $contFila;
//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":S".$ultimo);

cabeceraExcel($objPHPExcel, $idEmpresa, "S");

$tituloDcto = "Disponibilidad de Vehículos con Asignación";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:S7");

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