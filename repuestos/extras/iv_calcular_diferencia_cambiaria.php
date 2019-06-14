<?php
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// BUSCA LOS ARTICULOS DE LA EMPRESA QUE ESTEN REGISTRADO EN EL KARDEX
$queryArt = sprintf("SELECT 
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
	
	(cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio AS costo_cif_nacional,
	
	(((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100 AS tarifa_adv,
	
	((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) + 
	((((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100) +
	cxp_fact_det_imp.gastos_import_nac_unitario +
	cxp_fact_det_imp.gastos_import_unitario AS costo_unitario_final
FROM cp_factura_detalle_importacion cxp_fact_det_imp
	INNER JOIN cp_factura_detalle cxp_fact_det ON (cxp_fact_det_imp.id_factura_detalle = cxp_fact_det.id_factura_detalle)
	INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
	INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact_det.id_factura = cxp_fact_imp.id_factura)
	INNER JOIN pg_monedas moneda_origen ON (cxp_fact_imp.id_moneda_tasa_cambio = moneda_origen.idmoneda)
	INNER JOIN cp_factura cxp_fact ON (cxp_fact_imp.id_factura = cxp_fact.id_factura)
	INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
WHERE cxp_fact_imp.tasa_cambio_diferencia <> 0;");
$rsArt = mysql_query($queryArt);
if (!$rsArt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$updateSQL = sprintf("UPDATE iv_kardex kardex SET
		costo_diferencia = %s
	WHERE kardex.id_articulo = %s
		AND kardex.cantidad = %s
		AND kardex.id_documento = %s
		AND kardex.tipo_movimiento IN (1)
		AND kardex.costo_diferencia = 0;",
		valTpDato(($rowArt['costo_cif'] * $rowArt['tasa_cambio_diferencia']), "real_inglesa"),
		valTpDato($rowArt['id_articulo'], "int"),
		valTpDato($rowArt['cantidad'], "real_inglesa"),
		valTpDato($rowArt['id_factura'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}

mysql_query("COMMIT;");

echo "<h1>DIFERENCIAS CAMBIARIAS GENERADAS CON EXITO</h1>";
?>