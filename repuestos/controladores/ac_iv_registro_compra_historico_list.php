<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['lstModoCompra'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRegistroCompra(0, "id_factura", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarArticuloExpediente($frmImportacion) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	if (isset($frmImportacion['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmImportacion['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmImportacion['txtCodigoArticulo'.$cont].";";
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = codArticuloExpReg($codArticulo);
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$frmImportacion['hddIdRegistroCompra'],
		$codArticulo,
		$frmImportacion['txtCriterioImportacion']);
	
	$objResponse->loadCommands(listaExpediente(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function exportarExpediente($frmImportacion) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmImportacion['hddIdRegistroCompra']);
	
	$objResponse->script("window.open('reportes/iv_expediente_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function exportarRegistroCompra($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['lstModoCompra'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_registro_compra_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formImportacion($idFactura) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DEL REGISTRO DE COMPRA
	$query = sprintf("SELECT * FROM cp_factura WHERE id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(objetoCodigoDinamico('tdCodigoArt', $row['id_empresa']));
	
	$numeroFacturaProveedor = $row['numero_factura_proveedor'];
	
	// BUSCA LOS DATOS DE LA IMPORTACION DEL REGISTRO DE COMPRA
	$query = sprintf("SELECT
		cxp_fact_import.*,
		act_import.item AS actividad_importador,
		clase_import.item AS clase_importador,
		clase_sol.item AS clase_solicitud,
		prov_export.nombre AS nombre_proveedor_exportador,
		prov_consig.nombre AS nombre_proveedor_consignatario,
		pais_aduana.nom_origen AS nom_pais_aduana,
		pais_origen.nom_origen AS nom_pais_origen,
		pais_compra.nom_origen AS nom_pais_compra,
		via_envio.item AS via_envio,
		moneda_extranjera.descripcion AS descripcion_moneda_extranjera
	FROM cp_factura_importacion cxp_fact_import
		LEFT JOIN grupositems act_import ON (cxp_fact_import.id_actividad_importador = act_import.idItem)
		LEFT JOIN grupositems clase_import ON (cxp_fact_import.id_clase_importador = clase_import.idItem)
		LEFT JOIN grupositems clase_sol ON (cxp_fact_import.id_clase_solicitud = clase_sol.idItem)
		LEFT JOIN an_origen pais_aduana ON (cxp_fact_import.id_aduana = pais_aduana.id_origen)
		LEFT JOIN an_origen pais_origen ON (cxp_fact_import.id_pais_origen = pais_origen.id_origen)
		LEFT JOIN an_origen pais_compra ON (cxp_fact_import.id_pais_compra = pais_compra.id_origen)
		LEFT JOIN cp_proveedor prov_export ON (cxp_fact_import.id_proveedor_exportador = prov_export.id_proveedor)
		LEFT JOIN cp_proveedor prov_consig ON (cxp_fact_import.id_proveedor_consignatario = prov_consig.id_proveedor)
		INNER JOIN grupositems via_envio ON (cxp_fact_import.id_via_envio = via_envio.idItem)
		INNER JOIN pg_monedas moneda_extranjera ON (cxp_fact_import.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE cxp_fact_import.id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdRegistroCompra","value",utf8_encode($idFactura));
	$objResponse->assign("aTabsExpediente","innerHTML",utf8_encode($row['numero_expediente']));
	$objResponse->assign("spnActividadImportador","innerHTML",utf8_encode($row['actividad_importador']));
	$objResponse->assign("spnClaseImportador","innerHTML",utf8_encode($row['clase_importador']));
	$objResponse->assign("spnClaseSolicitud","innerHTML",utf8_encode($row['clase_solicitud']));
	$objResponse->assign("spnPuertoLlegada","innerHTML",utf8_encode($row['puerto_llegada']));
	$objResponse->assign("spnDestinoFinal","innerHTML",utf8_encode($row['destino_final']));
	$objResponse->assign("spnCompaniaTransporte","innerHTML",utf8_encode($row['compania_transportadora']));
	
	$objResponse->assign("spnNombreProvExportador","innerHTML",utf8_encode($row['nombre_proveedor_exportador']));
	$objResponse->assign("spnNombreProvConsignatario","innerHTML",utf8_encode($row['nombre_proveedor_consignatario']));
	$objResponse->assign("spnNombrePaisAduana","innerHTML",utf8_encode($row['nom_pais_aduana']));
	$objResponse->assign("spnNombrePaisOrigen","innerHTML",utf8_encode($row['nom_pais_origen']));
	$objResponse->assign("spnNombrePaisCompra","innerHTML",utf8_encode($row['nom_pais_compra']));
	$objResponse->assign("spnPuertoEmbarque","innerHTML",utf8_encode($row['puerto_embarque']));
	$objResponse->assign("spnViaEnvio","innerHTML",utf8_encode($row['via_envio']));
	$objResponse->assign("spnMonedaNegociacion","innerHTML",utf8_encode($row['descripcion_moneda_extranjera']));
	$objResponse->assign("spnDiferenciaCambiaria","innerHTML",number_format($row['tasa_cambio_diferencia'], 2, ".", ","));
	$objResponse->assign("spnNumeroEmbarque","innerHTML",utf8_encode($row['numero_embarque'])); //
	$objResponse->assign("spnPorcSeguro","innerHTML",number_format($row['porcentaje_seguro'], 2, ".", ","));
	$objResponse->assign("spnDctoTransporte","innerHTML",utf8_encode($row['numero_dcto_transporte'])); //
	$objResponse->assign("spnFechaDctoTransporte","innerHTML",date(spanDateFormat,strtotime($row['fecha_dcto_transporte'])));
	$objResponse->assign("spnFechaVencDctoTransporte","innerHTML",date(spanDateFormat,strtotime($row['fecha_vencimiento_dcto_transporte'])));
	$objResponse->assign("spnFechaEstimadaLlegada","innerHTML",date(spanDateFormat,strtotime($row['fecha_estimada_llegada'])));
	$objResponse->assign("spnExpediente","innerHTML",utf8_encode($row['numero_expediente']));
	$objResponse->assign("spnPlanillaImportacion","innerHTML",utf8_encode($row['numero_planilla_importacion']));
	
	$objResponse->loadCommands(listaGastosImportacionFactura(0, "", "", $idFactura));
	$objResponse->loadCommands(listaOtrosCargosFactura(0, "gasto.nombre", "ASC", $idFactura));
	$objResponse->loadCommands(listaExpediente(0, "", "", $idFactura));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Detalles de la Importación (Nro. Factura: ".$numeroFacturaProveedor.", Nro. Expediente: ".$row['numero_expediente'].")");
	
	return $objResponse;
}

function listaExpediente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact_det.id_factura = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT 
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
		INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "16%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "4%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "4%", $pageNum, "porcentaje_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "% ADV");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "6%", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit. FOB");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "6%", $pageNum, "gasto_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Gasto Unit.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "6%", $pageNum, "costo_cif", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit. CIF",
			"Costo Unit. FOB + Gasto Unit.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "6%", $pageNum, "tasa_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Tasa Cambio");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "6%", $pageNum, "costo_cif_nacional", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit. CIF",
			"Costo Unit. CIF * Tasa Cambio");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "6%", $pageNum, "tarifa_adv", $campOrd, $tpOrd, $valBusq, $maxRows, "Tarifa Unit. ADV");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "7%", $pageNum, "gastos_import_nac_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Gastos Unit. Importación");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "7%", $pageNum, "gastos_import_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Otros Cargos Unit.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "7%", $pageNum, "costo_unitario_final", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit. Final",
			"Costo Unit. CIF + Tarifa Unit. ADV + Gastos Unit. Importación + Otros Cargos Unit.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "4%", $pageNum, "peso_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Peso Unit. (g)");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "5%", $pageNum, "tasa_cambio_diferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Tasa Cambio Dif.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "5%", $pageNum, "costo_cif_diferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit. CIF Dif.",
			"Costo Unit. CIF * Tasa Cambio Dif.");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "5%", $pageNum, "costo_unitario_final_kardex", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit. Final Kardex",
			"Costo Unit. Final + Costo Unit. CIF Dif.");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		$abrevMonedaOrigen = $row['abreviacion_moneda_origen'];
		$abrevMonedaLocal = $row['abreviacion_moneda_local'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td nowrap=\"nowrap\" title=\"".utf8_encode($row['descripcion'])."\">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['porcentaje_grupo'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaOrigen.number_format($row['costo_unitario'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaOrigen.number_format($row['gasto_unitario'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaOrigen.number_format($row['costo_cif'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['tasa_cambio'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['costo_cif_nacional'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['tarifa_adv'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['gastos_import_nac_unitario'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['gastos_import_unitario'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['costo_unitario_final'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['peso_unitario'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['tasa_cambio_diferencia'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['costo_cif_diferencia'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$abrevMonedaLocal.number_format($row['costo_unitario_final_kardex'], 3, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['cantidad'] += $row['cantidad'];
		$arrayTotal['costo_unitario'] += $row['cantidad'] * $row['costo_unitario'];
		$arrayTotal['gasto_unitario'] += $row['cantidad'] * $row['gasto_unitario'];
		$arrayTotal['costo_cif'] += $row['cantidad'] * $row['costo_cif'];
		$arrayTotal['costo_cif_nacional'] += $row['cantidad'] * $row['costo_cif_nacional'];
		$arrayTotal['tarifa_adv'] += $row['cantidad'] * $row['tarifa_adv'];
		$arrayTotal['gastos_import_nac_unitario'] += $row['cantidad'] * $row['gastos_import_nac_unitario'];
		$arrayTotal['gastos_import_unitario'] += $row['cantidad'] * $row['gastos_import_unitario'];
		$arrayTotal['costo_unitario_final'] += $row['cantidad'] * $row['costo_unitario_final'];
		$arrayTotal['peso_unitario'] += $row['cantidad'] * $row['peso_unitario'];
		$arrayTotal['costo_cif_diferencia'] += $row['cantidad'] * $row['costo_cif_diferencia'];
		$arrayTotal['costo_unitario_final_kardex'] += $row['cantidad'] * $row['costo_unitario_final_kardex'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cantidad'], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td>".$abrevMonedaOrigen.number_format($arrayTotal['costo_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaOrigen.number_format($arrayTotal['gasto_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaOrigen.number_format($arrayTotal['costo_cif'], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['costo_cif_nacional'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['tarifa_adv'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['gastos_import_nac_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['gastos_import_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['costo_unitario_final'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['peso_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['costo_cif_diferencia'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotal['costo_unitario_final_kardex'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal['cantidad'] += $row['cantidad'];
				$arrayTotalFinal['costo_unitario'] += $row['cantidad'] * $row['costo_unitario'];
				$arrayTotalFinal['gasto_unitario'] += $row['cantidad'] * $row['gasto_unitario'];
				$arrayTotalFinal['costo_cif'] += $row['cantidad'] * $row['costo_cif'];
				$arrayTotalFinal['costo_cif_nacional'] += $row['cantidad'] * $row['costo_cif_nacional'];
				$arrayTotalFinal['tarifa_adv'] += $row['cantidad'] * $row['tarifa_adv'];
				$arrayTotalFinal['gastos_import_nac_unitario'] += $row['cantidad'] * $row['gastos_import_nac_unitario'];
				$arrayTotalFinal['gastos_import_unitario'] += $row['cantidad'] * $row['gastos_import_unitario'];
				$arrayTotalFinal['costo_unitario_final'] += $row['cantidad'] * $row['costo_unitario_final'];
				$arrayTotalFinal['peso_unitario'] += $row['cantidad'] * $row['peso_unitario'];
				$arrayTotalFinal['costo_cif_diferencia'] += $row['cantidad'] * $row['costo_cif_diferencia'];
				$arrayTotalFinal['costo_unitario_final_kardex'] += $row['cantidad'] * $row['costo_unitario_final_kardex'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cantidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td>".$abrevMonedaOrigen.number_format($arrayTotalFinal['costo_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaOrigen.number_format($arrayTotalFinal['gasto_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaOrigen.number_format($arrayTotalFinal['costo_cif'], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['costo_cif_nacional'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['tarifa_adv'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['gastos_import_nac_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['gastos_import_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['costo_unitario_final'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['peso_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['costo_cif_diferencia'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".$abrevMonedaLocal.number_format($arrayTotalFinal['costo_unitario_final_kardex'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaExpediente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaExpediente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaGastosImportacionFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact_gasto.id_modo_gasto IN (3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact_gasto.id_factura = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT
		gasto.nombre,
		cxp_fact_gasto.id_factura_compra_cargo,
		cxp_fact_gasto.monto,
		cxp_fact_gasto.id_condicion_gasto,
		cxp_fact_gasto.iva,
		cxp_fact_gasto.estatus_iva
	FROM cp_factura_gasto cxp_fact_gasto
		INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaGastosImportacionFactura", "80%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Gasto");
		$htmlTh .= ordenarCampo("xajax_listaGastosImportacionFactura", "10%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaGastosImportacionFactura", "10%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= ($row['iva'] > 0) ? number_format($row['iva'], 2, ".", ",")."%" : "-";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[3] += $row['monto'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[3], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[3] += $row['monto'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[3], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"3\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastosImportacionFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastosImportacionFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGastosImportacionFactura(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastosImportacionFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastosImportacionFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaGastosImportacionFactura","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaOtrosCargosFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact_gasto.id_modo_gasto IN (2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact_gasto.id_factura = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT
		cxp_fact_cargo.id_factura,
		gasto.id_gasto,
		gasto.nombre,
		cxp_fact_cargo.fecha_origen,
		cxp_fact_cargo.numero_factura_proveedor,
		cxp_fact_cargo.numero_control_factura,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		cxp_fact_cargo.subtotal_factura,
		cxp_fact_gasto.monto,
		cxp_fact_gasto.id_condicion_gasto,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact_cargo.id_factura
			AND cxp_fact_cargo.id_modulo = 3
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
		WHERE reten_cheque.id_factura = cxp_fact_cargo.id_factura
			AND reten_cheque.tipo IN (0)
			AND reten_cheque.anulado IS NULL) AS id_retencion_cheque
		
	FROM cp_factura_gasto cxp_fact_gasto
		INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
		LEFT JOIN cp_factura cxp_fact_cargo ON (cxp_fact_gasto.id_factura_compra_cargo = cxp_fact_cargo.id_factura)
		LEFT JOIN cp_proveedor prov ON (cxp_fact_cargo.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargosFactura", "30%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Gasto");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargosFactura", "10%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Reg. Compra");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargosFactura", "10%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargosFactura", "10%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargosFactura", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargosFactura", "14%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Subtotal");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		switch ($row['id_condicion_gasto']) {
			case 1 : $imgEstatus = ($row['id_factura'] > 0) ? "<img src=\"../img/iconos/ico_verde.gif\" title=\"Factura Registrada\"/>" : ""; break;
			case 2 : $imgEstatus = ($row['id_factura'] == "") ? "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Factura Sin Registrar con Cargo Estimado\"/>" : ""; break;
			case 3 : $imgEstatus = ($row['id_factura'] > 0) ? "<img src=\"../img/iconos/ico_azul.gif\" title=\"Factura Registrada con Cargo Estimado\"/>" : ""; break;
			default : $imgEstatus = $row['id_condicion_gasto'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($row['monto'], 2, ".", ",");
				$htmlTb .= ($row['monto'] != $row['subtotal_factura'] && $row['id_factura'] > 0) ? "<br><br><span class=\"textoNegrita_10px\">Subtotal Factura:</span><br><span class=\"texto_10px\">".number_format($row['subtotal_factura'], 2, ".", ",")."</span>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_factura'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/>",
					$row['id_factura']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[7] += $row['monto'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"2\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[7] += $row['monto'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"2\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargosFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargosFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaOtrosCargosFactura(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargosFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargosFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaOtrosCargosFactura","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
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
	
	$query = sprintf("SELECT
		cxp_fact.id_factura,
		cxp_fact.id_modo_compra,
		cxp_fact.fecha_origen,
		cxp_fact.fecha_factura_proveedor,
		cxp_fact.numero_factura_proveedor,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		cxp_fact.id_modulo,
		
		(SELECT COUNT(cxp_fact_det.id_factura)
		FROM cp_factura_detalle cxp_fact_det
		WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_items,
		
		(SELECT SUM(cxp_fact_det.cantidad)
		FROM cp_factura_detalle cxp_fact_det
		WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_piezas,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
		WHERE reten_cheque.id_factura = cxp_fact.id_factura
			AND reten_cheque.tipo IN (0)
			AND reten_cheque.anulado IS NULL) AS id_retencion_cheque,
		
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
		
		cxp_fact.activa,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= "<td width=\"16%\">"."Tipo de Pedido"."</td>";
		$htmlTh .= "<td width=\"8%\">"."Nro. Pedido"."</td>";
		$htmlTh .= "<td width=\"8%\">"."Nro. Referencia"."</td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "16%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "4%", $pageNum, "cant_piezas", $campOrd, $tpOrd, $valBusq, $maxRows, "Piezas");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"6\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch ($row['activa']) {
			case "" : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devolución)\"/>"; break;
			case 1 : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			default : $imgEstatusRegistroCompra = "";
		}
		
		$queryFactDet = sprintf("SELECT id_pedido_compra FROM cp_factura_detalle WHERE id_factura = %s
		GROUP BY id_pedido_compra;",
			valTpDato($row['id_factura'], "int"));
		$rsFactDet = mysql_query($queryFactDet);
		if (!$rsFactDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayFactDet = NULL;
		while ($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
			$queryPedComp = sprintf("SELECT * FROM vw_iv_pedidos_compra WHERE id_pedido_compra = %s;",
				valTpDato($rowFactDet['id_pedido_compra'], "int"));
			$rsPedComp = mysql_query($queryPedComp);
			if (!$rsPedComp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedComp = mysql_fetch_assoc($rsPedComp);
			
			$arrayFactDet[] = array(
				$rowPedComp['estatus_pedido_compra'],
				$rowPedComp['tipo_pedido_compra'],
				$rowPedComp['id_pedido_compra_propio'],
				$rowPedComp['id_pedido_compra_referencia']);
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusRegistroCompra."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						switch($arrayFactDet[$indice][0]) {
							case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Pendiente por Terminar\"/>"; break;
							case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido Cerrado\"/>"; break;
							case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Orden Aprobada\"/>"; break;
							case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
							case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devolución)\"/>"; break;
							case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Pedido Anulado\"/>"; break;
							default : $imgEstatusPedido = "";
						}
						
						$htmlTb .= "<tr align=\"left\">";
							$htmlTb .= "<td>".$imgEstatusPedido."</td>";
							$htmlTb .= "<td>".utf8_encode($arrayFactDet[$indice][1])."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>".$arrayFactDet[$indice][2]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>".$arrayFactDet[$indice][3]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_piezas'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<a class=\"modalImg\" id=\"aEtiqueta".$contFila."\" rel=\"#divFlotante1\" onclick=\"verVentana('reportes/iv_articulo_etiqueta_pdf.php?valBusq2=".$row['id_factura']."', 400, 300);\"><img class=\"puntero\" src=\"../img/iconos/tag_blue.png\" title=\"Etiqueta\"/></a>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_modo_compra'] == 2) { // 1 = Nacional, 2 = Importacion
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblImportacion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
					$contFila,
					$row['id_factura']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/iv_registro_compra_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/></a>",
					$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/ico_print.png\" title=\"Comprobante de Retención\"/></a>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_retencion_cheque'] > 0) {
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../tesoreria/reportes/te_imprimir_constancia_retencion_pdf.php?id=%s&documento=3', 960, 550);\"><img src=\"../img/iconos/page_red.png\" title=\"Comprobante de Retención ISLR\"/></a>",
					$row['id_retencion_cheque']);
			}
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$row['id_factura'];
			$sPar .= "&ct=01";
			$sPar .= "&dt=01";
			$sPar .= "&cc=04";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\"><img src=\"../img/iconos/new_window.png\" title=\"Movimiento Contable\"/></a>");
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
		$htmlTb .= "</tr>";
		
		$arrayTotal[12] += $row['cant_items'];
		$arrayTotal[13] += $row['cant_piezas'];
		$arrayTotal[14] += $row['total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"6\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[12] += $row['cant_items'];
				$arrayTotalFinal[13] += $row['cant_piezas'];
				$arrayTotalFinal[14] += $row['total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"6\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarArticuloExpediente");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"exportarExpediente");
$xajax->register(XAJAX_FUNCTION,"exportarRegistroCompra");
$xajax->register(XAJAX_FUNCTION,"formImportacion");
$xajax->register(XAJAX_FUNCTION,"listaExpediente");
$xajax->register(XAJAX_FUNCTION,"listaGastosImportacionFactura");
$xajax->register(XAJAX_FUNCTION,"listaOtrosCargosFactura");
$xajax->register(XAJAX_FUNCTION,"listaRegistroCompra");
?>