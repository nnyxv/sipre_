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

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(cxp_fact.id_modulo IN (2)
OR cxp_fact.id_modulo IS NULL)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
		reg_comp_uni_fis.fechaActualizado
	ELSE
		cxp_fact.fecha_origen
	END) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT kardex.claveKardex FROM an_kardex kardex
		INNER JOIN vw_pg_clave_movimiento ON (kardex.claveKardex = vw_pg_clave_movimiento.id_clave_movimiento)
	WHERE kardex.tipoMovimiento IN (1)
		AND kardex.id_documento = cxp_fact.id_factura
	LIMIT 1) = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modo_compra = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	((CASE WHEN(cxp_fact.id_factura IS NULL) THEN
		CONCAT_WS('*','',(SELECT reg_comp_uni_fis.numeroFactura
		FROM an_registro_compras_unidades_fisicas reg_comp_uni_fis
		WHERE reg_comp_uni_fis.idUnidadFisica = uni_fis.id_unidad_fisica
		LIMIT 1))
	ELSE
		cxp_fact.numero_factura_proveedor
	END) LIKE %s
	OR (CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			reg_comp_uni_fis.referenciaPedido
		ELSE
			cxp_fact.id_pedido_compra
		END) LIKE %s
	OR (SELECT prov.nombre FROM cp_proveedor prov
		WHERE prov.id_proveedor = cxp_fact.id_proveedor
			OR prov.id_proveedor = reg_comp_uni_fis.proveedor
		LIMIT 1) LIKE %s
	OR serial_carroceria LIKE %s
	OR placa LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cxp_fact.id_factura,
	cxp_fact.id_modo_compra,
	
	(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
		CONCAT_WS('*','',(SELECT reg_comp_uni_fis.numeroFactura
		FROM an_registro_compras_unidades_fisicas reg_comp_uni_fis
		WHERE reg_comp_uni_fis.idUnidadFisica = uni_fis.id_unidad_fisica
		LIMIT 1))
	ELSE
		cxp_fact.numero_factura_proveedor
	END) AS numero_factura_proveedor,
	
	IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.numeroControl, cxp_fact.numero_control_factura) AS numero_control_factura,
	IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.referenciaPedido, ped_comp.idPedidoCompra) AS id_pedido_compra,
	IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaCompra, cxp_fact.fecha_factura_proveedor) AS fecha_factura_proveedor,
	IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaActualizado, cxp_fact.fecha_origen) AS fecha_origen,
	IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaVencimiento, cxp_fact.fecha_vencimiento) AS fecha_vencimiento,
	
	(CASE cxp_fact.estatus_factura
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS estado_factura,
	
	(SELECT CONCAT_WS('-', prov.lrif, prov.rif) FROM cp_proveedor prov
	WHERE prov.id_proveedor IN (cxp_fact.id_proveedor, reg_comp_uni_fis.proveedor)
	LIMIT 1) AS rif_proveedor,
	
	(SELECT prov.nombre FROM cp_proveedor prov
	WHERE prov.id_proveedor IN (cxp_fact.id_proveedor, reg_comp_uni_fis.proveedor)
	LIMIT 1) AS nombre_proveedor,
	
	cxp_fact.id_modulo,
	origen.nom_origen,
	uni_fis.placa,
	uni_fis.serial_carroceria,
	
	(CASE id_modulo
		WHEN 1 THEN
			(SELECT COUNT(orden_tot.id_factura)
			FROM sa_orden_tot orden_tot
				INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
			WHERE orden_tot.id_factura = cxp_fact.id_factura)
		WHEN 2 THEN
			(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
			WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			+
			(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
			WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
		ELSE
			(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
			WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
	END) AS cant_items,
	
	moneda_local.abreviacion AS abreviacion_moneda_local,
	
	(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
		(reg_comp_uni_fis.importeVehiculo + reg_comp_uni_fis.totalPaquete)
	ELSE
		cxp_fact.subtotal_factura
	END) AS subtotal_factura,
	
	(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
		reg_comp_uni_fis.descuentoVehiculo
	ELSE
		cxp_fact.subtotal_descuento
	END) AS subtotal_descuento,
	
	reg_comp_uni_fis.porcentajeIvaVehiculo AS porcentaje_iva,
	reg_comp_uni_fis.ivaVehiculo AS subtotal_iva,
	reg_comp_uni_fis.porcentajeImpuestoLujoVehiculo AS porcentaje_iva_lujo,
	reg_comp_uni_fis.impuestoLujoVehiculo AS subtotal_iva_lujo,
	reg_comp_uni_fis.montoExento AS monto_exento,
	reg_comp_uni_fis.montoExonerado AS monto_exonerado,
	cxp_fact.saldo_factura,
	uni_fis.id_unidad_fisica,
	uni_fis.placa,
	uni_fis.serial_carroceria,
	cond_unidad.descripcion AS condicion_unidad,
	ped_comp_det.flotilla,
	
	(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
		reg_comp_uni_fis.montoTotal
	ELSE
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0))
	END) AS total,
	
	cxp_fact.activa,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = cxp_fact.id_factura
		AND cxp_fact.id_modulo IN (2)
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
	WHERE reten_cheque.id_factura = cxp_fact.id_factura
		AND reten_cheque.tipo IN (0)
		AND reten_cheque.anulado IS NULL) AS id_retencion_cheque,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	(IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
			FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total_iva,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva cxp_fact_iva
				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM an_unidad_fisica uni_fis
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	LEFT JOIN an_registro_compras_unidades_fisicas reg_comp_uni_fis ON (uni_fis.id_unidad_fisica = reg_comp_uni_fis.idUnidadFisica)
	LEFT JOIN an_origen origen ON (uni_fis.id_origen = origen.id_origen)
	INNER JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
	INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
	INNER JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
	LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY id_factura DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, "Detalles de la Importación");

$objPHPExcel->getActiveSheet()->getStyle("X".$contFila.":AI".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->mergeCells("X".$contFila.":AI".$contFila);

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Factura Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Condición");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, "Saldo Factura");
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, "Subtotal Factura");
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, "Descuento Factura");
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, "Total Neto");
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, "Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, "Total Factura");
$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, "Cant.");
$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, "% ADV");
$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, "Costo FOB");
$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, "Gasto");
$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, "Costo CIF");
$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, "Tasa Cambio");
$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, "Costo CIF");
$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, "Tarifa ADV");
$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, "Gastos Importación");
$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, "Otros Cargos");
$objPHPExcel->getActiveSheet()->setCellValue("AH".$contFila, "Costo Final");
$objPHPExcel->getActiveSheet()->setCellValue("AI".$contFila, "Peso (g)");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":AI".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['id_modulo']) {
		case 0 : $imgPedidoModulo = ("Repuestos"); break;
		case 1 : $imgPedidoModulo = ("Servicios"); break;
		case 2 : $imgPedidoModulo = ("Vehículos"); break;
		case 3 : $imgPedidoModulo = ("Administración"); break;
		default : $imgPedidoModulo = $row['id_modulo'];
	}
	
	$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "Creada por CxP";
	
	switch($row['activa']) {
		case "" : $imgEstatusRegistroCompra = "Compra Registrada (Con Devolución)"; break;
		case 1 : $imgEstatusRegistroCompra = "Compra Registrada"; break;
		default : $imgEstatusRegistroCompra = "";
	}
	
	if ($row['id_modo_compra']) { // 1 = Nacional, 2 = Importacion
		$queryFacturaImportacionDet = sprintf("SELECT
			SUM(q.cantidad) AS cantidad,
			AVG(q.porcentaje_grupo) AS porcentaje_grupo,
			SUM(q.cantidad * q.costo_unitario) AS costo_unitario,
			SUM(q.cantidad * q.gasto_unitario) AS gasto_unitario,
			SUM(q.cantidad * q.costo_cif) AS costo_cif,
			AVG(q.tasa_cambio) AS tasa_cambio,
			SUM(q.cantidad * q.costo_cif_nacional) AS costo_cif_nacional,
			SUM(q.cantidad * q.tarifa_adv) AS tarifa_adv,
			SUM(q.cantidad * q.gastos_import_nac_unitario) AS gastos_import_nac_unitario,
			SUM(q.cantidad * q.gastos_import_unitario) AS gastos_import_unitario,
			SUM(q.cantidad * q.costo_unitario_final) AS costo_unitario_final,
			SUM(q.cantidad * q.peso_unitario) AS peso_unitario,
			SUM(q.cantidad * q.costo_cif_diferencia) AS costo_cif_diferencia,
			SUM(q.cantidad * q.costo_unitario_final_kardex) AS costo_unitario_final_kardex,
			q.abreviacion_moneda_origen,
			q.abreviacion_moneda_local
		FROM (
			SELECT
				cxp_fact_det.id_factura,
				cxp_fact.id_empresa,
				cxp_fact_imp.numero_expediente,
				art.id_articulo,
				art.codigo_articulo,
				art.descripcion,
				cxp_fact_det.cantidad,
				cxp_fact_det_imp.costo_unitario,
				cxp_fact_det_imp.gasto_unitario,
				cxp_fact_det.peso_unitario,
				cxp_fact_imp.tasa_cambio,
				cxp_fact_imp.tasa_cambio_diferencia,
				cxp_fact_det_imp.porcentaje_grupo,
				cxp_fact_det_imp.gastos_import_nac_unitario,
				cxp_fact_det_imp.gastos_import_unitario,
				moneda_origen.abreviacion AS abreviacion_moneda_origen,
				moneda_local.abreviacion AS abreviacion_moneda_local,
				
				(cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) AS costo_cif,
				
				((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) AS costo_cif_nacional,
				
				(((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100 AS tarifa_adv,
				
				(((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
					+ ((((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100)
					+ cxp_fact_det_imp.gastos_import_nac_unitario
					+ cxp_fact_det_imp.gastos_import_unitario) AS costo_unitario_final,
					
				((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia) AS costo_cif_diferencia,
				
				((((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
						+ ((((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100)
						+ cxp_fact_det_imp.gastos_import_nac_unitario
						+ cxp_fact_det_imp.gastos_import_unitario)
					+ ((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia)) AS costo_unitario_final_kardex
			FROM cp_factura_detalle_importacion cxp_fact_det_imp
				INNER JOIN cp_factura_detalle cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle = cxp_fact_det.id_factura_detalle)
				INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
				INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact_det.id_factura = cxp_fact_imp.id_factura)
				INNER JOIN pg_monedas moneda_origen ON (cxp_fact_imp.id_moneda_tasa_cambio = moneda_origen.idmoneda)
				INNER JOIN cp_factura cxp_fact ON (cxp_fact_imp.id_factura = cxp_fact.id_factura)
				INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
			WHERE cxp_fact.id_factura = %s
		
			UNION
		
			SELECT 
				cxp_fact.id_factura,
				cxp_fact.id_empresa,
				cxp_fact_imp.numero_expediente,
				uni_bas.id_uni_bas,
				uni_bas.nom_uni_bas,
				uni_bas.des_uni_bas,
				1 AS cantidad,
				cxp_fact_det_unidad_imp.costo_unitario,
				cxp_fact_det_unidad_imp.gasto_unitario,
				0 AS peso_unitario,
				cxp_fact_imp.tasa_cambio,
				cxp_fact_imp.tasa_cambio_diferencia,
				cxp_fact_det_unidad_imp.porcentaje_grupo,
				cxp_fact_det_unidad_imp.gastos_import_nac_unitario,
				cxp_fact_det_unidad_imp.gastos_import_unitario,
				moneda_origen.abreviacion AS abreviacion_moneda_origen,
				moneda_local.abreviacion AS abreviacion_moneda_local,
				
				(cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) AS costo_cif,
				
				(cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio AS costo_cif_nacional,
				
				(((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_unidad_imp.porcentaje_grupo) / 100 AS tarifa_adv,
				
				(((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
					+ ((((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_unidad_imp.porcentaje_grupo) / 100)
					+ cxp_fact_det_unidad_imp.gastos_import_nac_unitario
					+ cxp_fact_det_unidad_imp.gastos_import_unitario) AS costo_unitario_final,
					
				((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia) AS costo_cif_diferencia,
				
				((((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
						+ ((((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_unidad_imp.porcentaje_grupo) / 100)
						+ cxp_fact_det_unidad_imp.gastos_import_nac_unitario
						+ cxp_fact_det_unidad_imp.gastos_import_unitario)
					+ ((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia)) AS costo_unitario_final_kardex
			FROM cp_factura cxp_fact
				INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
				INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact.id_factura = cxp_fact_imp.id_factura)
				INNER JOIN pg_monedas moneda_origen ON (cxp_fact_imp.id_moneda_tasa_cambio = moneda_origen.idmoneda)
				INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (cxp_fact.id_factura = cxp_fact_det_unidad.id_factura)
				INNER JOIN cp_factura_detalle_unidad_importacion cxp_fact_det_unidad_imp ON (cxp_fact_det_unidad.id_factura_detalle_unidad = cxp_fact_det_unidad_imp.id_factura_detalle_unidad)
				INNER JOIN an_uni_bas uni_bas ON (cxp_fact_det_unidad.id_unidad_basica = uni_bas.id_uni_bas)
			WHERE cxp_fact.id_factura = %s
			
			UNION
			
			SELECT 
				cxp_fact.id_factura,
				cxp_fact.id_empresa,
				cxp_fact_imp.numero_expediente,
				acc.id_accesorio,
				acc.nom_accesorio,
				acc.des_accesorio,
				cxp_fact_det_acc.cantidad,
				cxp_fact_det_acc_imp.costo_unitario,
				cxp_fact_det_acc_imp.gasto_unitario,
				0 AS peso_unitario,
				cxp_fact_imp.tasa_cambio,
				cxp_fact_imp.tasa_cambio_diferencia,
				cxp_fact_det_acc_imp.porcentaje_grupo,
				cxp_fact_det_acc_imp.gastos_import_nac_unitario,
				cxp_fact_det_acc_imp.gastos_import_unitario,
				moneda_origen.abreviacion AS abreviacion_moneda_origen,
				moneda_local.abreviacion AS abreviacion_moneda_local,
				
				(cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) AS costo_cif,
				
				(cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio AS costo_cif_nacional,
				
				(((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_acc_imp.porcentaje_grupo) / 100 AS tarifa_adv,
				
				(((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
					+ ((((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_acc_imp.porcentaje_grupo) / 100)
					+ cxp_fact_det_acc_imp.gastos_import_nac_unitario
					+ cxp_fact_det_acc_imp.gastos_import_unitario) AS costo_unitario_final,
					
				((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia) AS costo_cif_diferencia,
				
				((((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio)
						+ ((((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_acc_imp.porcentaje_grupo) / 100)
						+ cxp_fact_det_acc_imp.gastos_import_nac_unitario
						+ cxp_fact_det_acc_imp.gastos_import_unitario)
					+ ((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio_diferencia)) AS costo_unitario_final_kardex
			FROM cp_factura cxp_fact
				INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
				INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact.id_factura = cxp_fact_imp.id_factura)
				INNER JOIN pg_monedas moneda_origen ON (cxp_fact_imp.id_moneda_tasa_cambio = moneda_origen.idmoneda)
				INNER JOIN cp_factura_detalle_accesorio cxp_fact_det_acc ON (cxp_fact.id_factura = cxp_fact_det_acc.id_factura)
				INNER JOIN cp_factura_detalle_accesorio_importacion cxp_fact_det_acc_imp ON (cxp_fact_det_acc.id_factura_detalle_accesorio = cxp_fact_det_acc_imp.id_factura_detalle_accesorio)
				INNER JOIN an_accesorio acc ON (cxp_fact_det_acc.id_accesorio = acc.id_accesorio)
			WHERE cxp_fact.id_factura = %s) AS q
			GROUP BY q.id_factura",
			valTpDato($row['id_factura'], "int"),
			valTpDato($row['id_factura'], "int"),
			valTpDato($row['id_factura'], "int"));
		$rsFacturaImportacionDet = mysql_query($queryFacturaImportacionDet);
		if (!$rsFacturaImportacionDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowFacturaImportacionDet = mysql_fetch_assoc($rsFacturaImportacionDet);
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgPedidoModulo);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgPedidoModuloCondicion);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $imgEstatusRegistroCompra);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_origen'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, date(spanDateFormat, strtotime($row['fecha_vencimiento'])));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['numero_factura_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, (($row['id_nota_cargo_planmayor'] > 0) ? "Factura por Plan Mayor" : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['rif_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['nit_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['condicion_unidad']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, utf8_encode($row['serial_carroceria']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, utf8_encode($row['placa']));
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, ((strlen($row['observacion_factura']) > 0) ? utf8_encode($row['observacion_factura']) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['estado_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $row['saldo_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $row['subtotal_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $row['subtotal_descuento']);
	$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $row['total_neto']);
	$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $row['total_iva']);
	$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $row['total']);
	$objPHPExcel->getActiveSheet()->setCellValue("X".$contFila, $rowFacturaImportacionDet['cantidad']);
	$objPHPExcel->getActiveSheet()->setCellValue("Y".$contFila, $rowFacturaImportacionDet['porcentaje_grupo']);
	$objPHPExcel->getActiveSheet()->setCellValue("Z".$contFila, $rowFacturaImportacionDet['costo_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("AA".$contFila, $rowFacturaImportacionDet['gasto_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("AB".$contFila, $rowFacturaImportacionDet['costo_cif']);
	$objPHPExcel->getActiveSheet()->setCellValue("AC".$contFila, $rowFacturaImportacionDet['tasa_cambio']);
	$objPHPExcel->getActiveSheet()->setCellValue("AD".$contFila, $rowFacturaImportacionDet['costo_cif_nacional']);
	$objPHPExcel->getActiveSheet()->setCellValue("AE".$contFila, $rowFacturaImportacionDet['tarifa_adv']);
	$objPHPExcel->getActiveSheet()->setCellValue("AF".$contFila, $rowFacturaImportacionDet['gastos_import_nac_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("AG".$contFila, $rowFacturaImportacionDet['gastos_import_unitario']);
	$objPHPExcel->getActiveSheet()->setCellValue("AH".$contFila, $rowFacturaImportacionDet['costo_unitario_final']);
	$objPHPExcel->getActiveSheet()->setCellValue("AI".$contFila, $rowFacturaImportacionDet['peso_unitario']);
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":AI".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("X".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Y".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Z".$contFila)->getNumberFormat()->setFormatCode('"'.$rowFacturaImportacionDet['abreviacion_moneda_origen'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AA".$contFila)->getNumberFormat()->setFormatCode('"'.$rowFacturaImportacionDet['abreviacion_moneda_origen'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AB".$contFila)->getNumberFormat()->setFormatCode('"'.$rowFacturaImportacionDet['abreviacion_moneda_origen'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AC".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AD".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AE".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AF".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AG".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AH".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.000');
	$objPHPExcel->getActiveSheet()->getStyle("AI".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$arrayTotal['cant_documentos'] += 1;
	$arrayTotal['saldo_factura'] += $row['saldo_factura'];
	$arrayTotal['subtotal_factura'] += $row['subtotal_factura'];
	$arrayTotal['subtotal_descuento'] += $row['subtotal_descuento'];
	$arrayTotal['total_neto'] += $row['total_neto'];
	$arrayTotal['total_iva'] += $row['total_iva'];
	$arrayTotal['total'] += $row['total'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":AI".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $arrayTotal['cant_documentos']);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal['saldo_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal['subtotal_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal['subtotal_descuento']);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal['total_neto']);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal['total_iva']);
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $arrayTotal['total']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."G".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("H".$contFila.":"."AI".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."G".$contFila);

cabeceraExcel($objPHPExcel, $idEmpresa, "AI");

$tituloDcto = "Histórico de Registro de Compra";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:AI7");

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