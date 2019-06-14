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
$sqlBusq .= $cond.sprintf("id_modulo IN (0)");

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
	$sqlBusq .= $cond.sprintf("fecha_origen BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
	WHERE mov.id_tipo_movimiento IN (1)
		AND mov.id_documento = cxp_fact.id_factura
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
	$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR cxp_fact.numero_control_factura LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT
	cxp_fact.id_factura,
	cxp_fact.id_modo_compra,
	cxp_fact.fecha_origen,
	cxp_fact.fecha_factura_proveedor,
	cxp_fact.numero_factura_proveedor,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre AS nombre_proveedor,
	
	(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_items,
	
	(SELECT SUM(cxp_fact_det.cantidad) FROM cp_factura_detalle cxp_fact_det
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_piezas,
	
	moneda_local.abreviacion AS abreviacion_moneda_local,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = cxp_fact.id_factura
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT DISTINCT ped_comp.estatus_pedido_compra
	FROM cp_factura_detalle cxp_fact_det
		INNER JOIN iv_pedido_compra ped_comp ON (cxp_fact_det.id_pedido_compra = ped_comp.id_pedido_compra)
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura
	LIMIT 1) AS estatus_pedido_compra,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
	
	(IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total_iva,
	
	(IFNULL(cxp_fact.subtotal_factura, 0)
		- IFNULL(cxp_fact.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
				FROM cp_factura_gasto cxp_fact_gasto
				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
					AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva FROM cp_factura_iva cxp_fact_iva
				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
	
	cxp_fact.activa,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cp_factura cxp_fact
	INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
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
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha Factura Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Fecha Venc. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Nro. Factura");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Tipo Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Nro. Referencia");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanProvCxP);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanNIT);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Proveedor");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Observación");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "Estado Factura");
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Items");
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Piezas");
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
	
	$queryFactDet = sprintf("SELECT id_pedido_compra FROM cp_factura_detalle
	WHERE id_factura = %s
	GROUP BY id_pedido_compra;",
		valTpDato($row['id_factura'], "int"));
	$rsFactDet = mysql_query($queryFactDet);
	if (!$rsFactDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$arrayEstatusPedidoCompra = NULL;
	$arrayTipoPedidoCompra = NULL;
	$arrayIdPedidoCompraPropio = NULL;
	$arrayIdPedidoCompraReferencia = NULL;
	while ($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
		$queryPedComp = sprintf("SELECT * FROM vw_iv_pedidos_compra WHERE id_pedido_compra = %s;",
			valTpDato($rowFactDet['id_pedido_compra'], "int"));
		$rsPedComp = mysql_query($queryPedComp);
		if (!$rsPedComp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowPedComp = mysql_fetch_assoc($rsPedComp);
		
		$arrayEstatusPedidoCompra[] = $rowPedComp['estatus_pedido_compra'];
		$arrayTipoPedidoCompra[] = $rowPedComp['tipo_pedido_compra'];
		$arrayIdPedidoCompraPropio[] = $rowPedComp['id_pedido_compra_propio'];
		$arrayIdPedidoCompraReferencia[] = $rowPedComp['id_pedido_compra_referencia'];
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
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, date(spanDateFormat, strtotime($row['fecha_origen'])));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, date(spanDateFormat, strtotime($row['fecha_vencimiento'])));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['numero_factura_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode(implode(", ",$arrayTipoPedidoCompra)));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode(implode(", ",$arrayIdPedidoCompraPropio)));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode(implode(", ",$arrayIdPedidoCompraReferencia)));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, utf8_encode($row['rif_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, utf8_encode($row['nit_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['nombre_proveedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, ((strlen($row['observacion_factura']) > 0) ? utf8_encode($row['observacion_factura']) : ""));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['estado_factura']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['cant_items']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['cant_piezas']);
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
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
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
	
	$arrayTotal['cantidad_documentos'] += 1;
	$arrayTotal['cant_items'] += $row['cant_items'];
	$arrayTotal['cant_piezas'] += $row['cant_piezas'];
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
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $arrayTotal['cantidad_documentos']);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $arrayTotal['cant_items']);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal['cant_piezas']);
$objPHPExcel->getActiveSheet()->setCellValue("R".$contFila, $arrayTotal['saldo_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("S".$contFila, $arrayTotal['subtotal_factura']);
$objPHPExcel->getActiveSheet()->setCellValue("T".$contFila, $arrayTotal['subtotal_descuento']);
$objPHPExcel->getActiveSheet()->setCellValue("U".$contFila, $arrayTotal['total_neto']);
$objPHPExcel->getActiveSheet()->setCellValue("V".$contFila, $arrayTotal['total_iva']);
$objPHPExcel->getActiveSheet()->setCellValue("W".$contFila, $arrayTotal['total']);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":"."F".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":"."AI".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$objPHPExcel->getActiveSheet()->getStyle("R".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("S".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("T".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("U".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("V".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("W".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":"."F".$contFila);

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