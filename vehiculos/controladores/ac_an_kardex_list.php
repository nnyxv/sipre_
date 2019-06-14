<?php


function buscarKardex($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaKardex(0, "nom_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstCondicionBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstCondicionBuscar\" name=\"lstCondicionBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicionBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstOrientacionExcel($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("1" => "Hoja por Cada Código", "2" => "Hoja Unica");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_exportarKardex(xajax.getFormValues('frmBuscar'), 'EXCEL');\"";
	
	$html = "<select id=\"lstOrientacionExcel\" name=\"lstOrientacionExcel\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstOrientacionExcel","innerHTML",$html);
	
	return $objResponse;
}

function exportarKardex($frmBuscar, $tipoExportar = "EXCEL"){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		$frmBuscar['txtCriterio']);
	
	switch ($tipoExportar) {
		case "EXCEL" : $objResponse->script("window.open('reportes/an_kardex_excel.php?valBusq=".$valBusq."&lstOrientacionExcel=".$frmBuscar['lstOrientacionExcel']."','_self');"); break;
		case "TXT" : $objResponse->script("window.open('reportes/an_kardex_txt.php?valBusq=".$valBusq."','_self');"); break;
	}
	
	$objResponse->assign("tdlstOrientacionExcel","innerHTML","");
	
	return $objResponse;
}

function listaKardex($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	global $spanSerialCarroceria;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		((CASE kardex.tipoMovimiento
			WHEN 1 THEN -- COMPRA
				(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
					WHEN 3 THEN -- ENTRADA CON CONTRATO
						(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
					WHEN 3 THEN -- SALIDA CON CONTRATO
						(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
				END)
		END) = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
											WHEN 1 THEN -- COMPRA
												(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- ENTRADA CON VALE
														(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
														(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
													WHEN 3 THEN -- ENTRADA CON CONTRATO
														(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
												END)
											WHEN 3 THEN -- VENTA
												(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
											WHEN 4 THEN -- SALIDA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- SALIDA CON VALE
														(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
													WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
														(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
													WHEN 3 THEN -- SALIDA CON CONTRATO
														(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
												END)
										END)))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(DATE(fechaMovimiento) BETWEEN %s AND %s
		OR (SELECT
				SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * k.cantidad) AS saldo_anterior
			FROM an_kardex k
				INNER JOIN an_unidad_fisica uni_fis ON (k.idUnidadFisica = uni_fis.id_unidad_fisica)
			WHERE k.idUnidadBasica = vw_iv_modelo.id_uni_bas
				AND DATE(k.fechaMovimiento) < %s) > 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_marca LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato($valCadBusq[4], "int"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM an_kardex kardex
		INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas) %s
	GROUP BY 1,2,3", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$idUnidadBasica = $row['id_uni_bas'];
		
		$htmlTh = "<tr align=\"left\" height=\"24\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"3\" title=\"".$idUnidadBasica."\">"."Unidad Básica:"."</a></td>";
			$htmlTh .= "<td colspan=\"9\">".htmlentities($row['nom_uni_bas'])."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"left\" height=\"24\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"3\">"."Descripción:"."</td>";
			$htmlTh .= "<td colspan=\"9\">".htmlentities($row['vehiculo'])."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"2\"></td>";
			$htmlTh .= "<td width=\"8%\">Fecha</td>";
			$htmlTh .= "<td width=\"14%\">Empresa</td>";
			$htmlTh .= "<td width=\"12%\">".$spanSerialCarroceria."</td>";
			$htmlTh .= "<td width=\"4%\">T</td>";
			$htmlTh .= "<td width=\"8%\">Nro. Documento</td>";
			$htmlTh .= "<td colspan=\"3\" width=\"26%\">C/P/M</td>";
			$htmlTh .= "<td width=\"6%\">E/S</td>";
			$htmlTh .= "<td width=\"6%\">Saldo</td>";
			$htmlTh .= "<td width=\"8%\">".$spanPrecioUnitario."</td>";
			$htmlTh .= "<td width=\"8%\">Costo Unit.</td>";
		$htmlTh .= "</tr>";
		
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("k.idUnidadBasica = %s
		AND DATE(k.fechaMovimiento) < %s",
			valTpDato($idUnidadBasica, "int"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));
			
		if ($valCadBusq[3] != "" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
				valTpDato($valCadBusq[3], "int"));
		}
		
		$queryArticuloSaldoAnt = sprintf("SELECT
			SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * k.cantidad) AS saldo_anterior,
			SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * 
				(CASE
					WHEN (k.tipoMovimiento = 1) THEN
						k.costo + k.costo_cargo - k.subtotal_descuento
					WHEN (k.tipoMovimiento = 2) THEN
						(CASE
							WHEN (k.tipo_documento_movimiento = 1) THEN
								k.costo + k.costo_cargo - k.subtotal_descuento
							WHEN (k.tipo_documento_movimiento = 2) THEN
								k.precio - k.subtotal_descuento
							WHEN (k.tipo_documento_movimiento = 3) THEN
								k.costo + k.costo_cargo - k.subtotal_descuento
						END)
					WHEN (k.tipoMovimiento = 3) THEN
						k.precio - k.subtotal_descuento
					WHEN (k.tipoMovimiento = 4) THEN
						(CASE
							WHEN (k.tipo_documento_movimiento = 1) THEN
								k.precio - k.subtotal_descuento
							WHEN (k.tipo_documento_movimiento = 2) THEN
								k.costo + k.costo_cargo - k.subtotal_descuento
							WHEN (k.tipo_documento_movimiento = 3) THEN
								k.precio - k.subtotal_descuento
						END)
				END)
			) AS total_precio_saldo_anterior,
			SUM(IF(k.tipoMovimiento IN (1,2), 1, (-1)) * 
				(CASE
					WHEN (k.tipoMovimiento = 1) THEN
						k.costo + k.costo_cargo - k.subtotal_descuento
					WHEN (k.tipoMovimiento = 2) THEN
						(CASE
							WHEN (k.tipo_documento_movimiento = 1) THEN
								k.costo + k.costo_cargo - k.subtotal_descuento
							WHEN (k.tipo_documento_movimiento = 2) THEN
								k.costo
							WHEN (k.tipo_documento_movimiento = 3) THEN
								k.costo + k.costo_cargo - k.subtotal_descuento
						END)
					WHEN (k.tipoMovimiento = 3) THEN
						k.costo
					WHEN (k.tipoMovimiento = 4) THEN
						(CASE
							WHEN (k.tipo_documento_movimiento = 1) THEN
								k.costo
							WHEN (k.tipo_documento_movimiento = 2) THEN
								k.costo + k.costo_cargo - k.subtotal_descuento
							WHEN (k.tipo_documento_movimiento = 3) THEN
								k.costo
						END)
				END)
			) AS total_costo_saldo_anterior
		FROM an_kardex k
			INNER JOIN an_unidad_fisica uni_fis ON (k.idUnidadFisica = uni_fis.id_unidad_fisica) %s", $sqlBusq2);
		$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
		if (!$rsArticuloSaldoAnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
		$totalEntrada = 0;
		$totalValorEntradaPrecio = 0;
		$totalValorEntradaCosto = 0;
		$totalSalida = 0;
		$totalValorSalidaPrecio = 0;
		$totalValorSalidaCosto = 0;
		$entradaSalida = 0;
		if ($rowArticuloSaldoAnt['saldo_anterior'] != 0) {
			$clase = "trResaltar5";
			
			$totalEntrada = $rowArticuloSaldoAnt['saldo_anterior'];
			$totalValorEntradaPrecio = $rowArticuloSaldoAnt['total_precio_saldo_anterior'];
			$totalValorEntradaCosto = $rowArticuloSaldoAnt['total_costo_saldo_anterior'];
		
			$entradaSalida = $rowArticuloSaldoAnt['saldo_anterior'];
			
			$cantSaldoAnterior = $rowArticuloSaldoAnt['saldo_anterior'];
			$totalValorSaldoAnteriorPrecio = $rowArticuloSaldoAnt['total_precio_saldo_anterior'];
			$totalValorSaldoAnteriorCosto = $rowArticuloSaldoAnt['total_costo_saldo_anterior'];
			
			$htmlTh .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTh .= "<td colspan=\"2\"></td>";
				$htmlTh .= "<td class=\"divMsjInfo\" colspan=\"5\">Saldo Anterior al Intervalo de Fecha Seleccionado:</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td align=\"right\">".number_format($cantSaldoAnterior, 2, ".", ",")."</td>";
				$htmlTh .= "<td align=\"right\">".cAbrevMoneda.number_format($totalValorSaldoAnteriorPrecio, 2, ".", ",")."</td>";
				$htmlTh .= "<td align=\"right\">".cAbrevMoneda.number_format($totalValorSaldoAnteriorCosto, 2, ".", ",")."</td>";
			$htmlTh .= "</tr>";
		}
		
		$sqlBusq4 = "";
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("uni_bas.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
			$sqlBusq4 .= $cond.sprintf("
			((CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
												WHEN 1 THEN -- COMPRA
													(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
												WHEN 2 THEN -- ENTRADA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- ENTRADA CON VALE
															(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
														WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
															(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
														WHEN 3 THEN -- ENTRADA CON CONTRATO
															(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
													END)
												WHEN 3 THEN -- VENTA
													(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
												WHEN 4 THEN -- SALIDA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- SALIDA CON VALE
															(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
														WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
															(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
														WHEN 3 THEN -- SALIDA CON CONTRATO
															(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
													END)
											END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
			$sqlBusq4 .= $cond.sprintf("DATE(fechaMovimiento) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
	
		if ($valCadBusq[3] != "" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
			$sqlBusq4 .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
				valTpDato($valCadBusq[3], "int"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
			$sqlBusq4 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
			OR vw_iv_modelo.nom_uni_bas LIKE %s
			OR vw_iv_modelo.nom_marca LIKE %s
			OR vw_iv_modelo.nom_modelo LIKE %s
			OR vw_iv_modelo.nom_version LIKE %s
			OR uni_fis.serial_carroceria LIKE %s
			OR uni_fis.serial_motor LIKE %s
			OR uni_fis.serial_chasis LIKE %s
			OR uni_fis.placa LIKE %s)",
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"));
		}
		
		$queryDetalle = sprintf("SELECT
			kardex.idKardex,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS id_empresa,
			
			(SELECT
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			WHERE vw_iv_emp_suc.id_empresa_reg = (CASE kardex.tipoMovimiento
													WHEN 1 THEN -- COMPRA
														(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- ENTRADA CON VALE
																(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
															WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
																(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
															WHEN 3 THEN -- ENTRADA CON CONTRATO
																(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
														END)
													WHEN 3 THEN -- VENTA
														(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
													WHEN 4 THEN -- SALIDA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- SALIDA CON VALE
																(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
															WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
																(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
															WHEN 3 THEN -- SALIDA CON CONTRATO
																(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
														END)
												END)) AS nombre_empresa,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			kardex.id_documento,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT an_ve.numeracion_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							(SELECT al_contrato.numero_contrato_venta FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT an_vs.numeracion_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT al_contrato.numero_contrato_venta FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS numero_documento,
			
			2 AS id_modulo,
			(CASE 2
				WHEN 0 THEN		'R'
				WHEN 1 THEN		'S'
				WHEN 2 THEN		'V'
				WHEN 3 THEN		'C'
				WHEN 4 THEN		'AL'
			END) AS nombre_modulo,
			
			kardex.tipoMovimiento,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN	'C'
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE (SELECT an_ve.tipo_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
								WHEN 4 THEN		'E-TRNS.ALM'
								ELSE			'E'
							END)
						WHEN 2 THEN
							'E-NC'
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							'E-ALQUILER'
					END)
				WHEN 3 THEN 'V'
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE (SELECT an_vs.tipo_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
								WHEN 4 THEN		'S-TRNS.ALM'
								ELSE			'S'
							END)
						WHEN 2 THEN
							'S-NC'
						WHEN 3 THEN -- SALIDA CON CONTRATO
							'S-ALQUILER'
					END)
			END) AS nombre_tipo_movimiento,
			
			kardex.claveKardex,
			kardex.tipo_documento_movimiento,
			kardex.estadoKardex,
			kardex.fechaMovimiento,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT prov.id_proveedor AS idPCE
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = kardex.id_documento
						AND cxp_fact.id_modulo IN (2))
				WHEN 2 THEN -- ENTRADA
					(SELECT cliente.id AS idPCE FROM cj_cc_cliente cliente
					WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
											WHEN 1 THEN -- ENTRADA CON VALE
												(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
												(SELECT cxc_nc.idCliente
												FROM cj_cc_notacredito cxc_nc
													INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
												WHERE cxc_nc.idNotaCredito = kardex.id_documento
													AND cxc_nc.idDepartamentoNotaCredito IN (2)
													AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
											WHEN 3 THEN -- ENTRADA CON CONTRATO
												(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
										END))
				WHEN 3 THEN -- VENTA
					(SELECT cliente.id AS idPCE
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT cliente.id AS idPCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT cliente.id AS idPCE
							FROM al_contrato_venta al_contrato
								INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
							WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS idPCE,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT CONCAT_WS('-', prov.lrif, prov.rif) AS ciPCE
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = kardex.id_documento
						AND cxp_fact.id_modulo IN (2))
				WHEN 2 THEN -- ENTRADA
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE FROM cj_cc_cliente cliente
					WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
											WHEN 1 THEN -- ENTRADA CON VALE
												(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
												(SELECT cxc_nc.idCliente
												FROM cj_cc_notacredito cxc_nc
													INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
												WHERE cxc_nc.idNotaCredito = kardex.id_documento
													AND cxc_nc.idDepartamentoNotaCredito IN (2)
													AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
											WHEN 3 THEN -- ENTRADA CON CONTRATO
												(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
										END))
				WHEN 3 THEN -- VENTA
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
							FROM al_contrato_venta al_contrato
								INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
							WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS ciPCE,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN
					(SELECT prov.nombre
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = kardex.id_documento
						AND cxp_fact.id_modulo IN (2))
				WHEN 2 THEN
					(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE FROM cj_cc_cliente cliente
					WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
											WHEN 1 THEN -- ENTRADA CON VALE
												(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
												(SELECT cxc_nc.idCliente
												FROM cj_cc_notacredito cxc_nc
													INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
												WHERE cxc_nc.idNotaCredito = kardex.id_documento
													AND cxc_nc.idDepartamentoNotaCredito IN (2)
													AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
											WHEN 3 THEN -- ENTRADA CON CONTRATO
												(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
										END))
				WHEN 3 THEN
					(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM al_contrato_venta al_contrato
								INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
							WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS nombrePCE,
			
			(CASE kardex.tipoMovimiento
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT an_ve.tipo_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
					END)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT an_vs.tipo_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
					END)
			END) AS tipo_vale,
			
			kardex.cantidad,
			kardex.precio,
			kardex.costo,
			kardex.costo_cargo,
			kardex.porcentaje_descuento,
			kardex.subtotal_descuento,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN
					uni_fis.precio_compra
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							(SELECT cxc_fact_det_vehic.precio_unitario
							FROM cj_cc_notacredito cxc_nc
								INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
							WHERE cxc_nc.idNotaCredito = kardex.id_documento
								AND cxc_nc.idDepartamentoNotaCredito IN (2)
								AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							uni_fis.precio_compra
					END)
				WHEN 3 THEN
					(SELECT cxc_fact_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					WHERE cxc_fact_det_vehic.id_factura = kardex.id_documento
						AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							uni_fis.precio_compra
						WHEN 3 THEN -- SALIDA CON CONTRATO
							uni_fis.precio_compra
					END)
			END) AS precio_unidad_dcto,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN
					uni_fis.precio_compra
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							(SELECT cxc_fact_det_vehic.costo_compra
							FROM cj_cc_notacredito cxc_nc
								INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
							WHERE cxc_nc.idNotaCredito =kardex.id_documento
								AND cxc_nc.idDepartamentoNotaCredito IN (2)
								AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							uni_fis.precio_compra
					END)
				WHEN 3 THEN
					(SELECT cxc_fact_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					WHERE cxc_fact_det_vehic.id_factura = kardex.id_documento
						AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							uni_fis.precio_compra
						WHEN 3 THEN -- SALIDA CON CONTRATO
							uni_fis.precio_compra
					END)
			END) AS costo_unidad_dcto
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_kardex kardex ON (uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas) %s
		ORDER BY kardex.fechaMovimiento ASC, kardex.idKardex ASC", $sqlBusq4);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$updateSQL = sprintf("UPDATE an_kardex SET
				precio = %s
			WHERE idKardex = %s
				AND precio = 0;",
				valTpDato($rowDetalle['precio_unidad_dcto'], "real_inglesa"),
				valTpDato($rowDetalle['idKardex'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$updateSQL = sprintf("UPDATE an_kardex SET
				costo = %s
			WHERE idKardex = %s
				AND costo = 0;",
				valTpDato($rowDetalle['costo_unidad_dcto'], "real_inglesa"),
				valTpDato($rowDetalle['idKardex'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$idModulo = $rowDetalle['id_modulo'];
			
			switch ($rowDetalle['tipoMovimiento']) {
				case 1 : // COMPRA
					$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
					$precioUnitario = $costoUnitario; break;
				case 2 : // ENTRADA
					switch($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
							break;
						case 2 : // NOTA CREDITO
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
						case 3 : // CONTRATO
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
							break;
					}
					break;
				case 3 : // VENTA
					$costoUnitario = $rowDetalle['costo'];
					$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
				case 4 : // SALIDA
					switch($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
						case 2 : // NOTA CREDITO
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = $costoUnitario;
							break;
						case 3 : // CONTRATO
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
					}
					break;
			}
			
			if ($rowDetalle['estadoKardex'] == 0) {
				$totalEntrada += $rowDetalle['cantidad'];
				$totalValorEntradaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
				$totalValorEntradaCosto += $rowDetalle['cantidad'] * $costoUnitario;
				$entradaSalida += $rowDetalle['cantidad'];
			} else if ($rowDetalle['estadoKardex'] == 1) {
				$totalSalida += $rowDetalle['cantidad'];
				$totalValorSalidaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
				$totalValorSalidaCosto += $rowDetalle['cantidad'] * $costoUnitario;
				$entradaSalida -= $rowDetalle['cantidad'];
			}
			
			$imgInterAlmacen = ($rowDetalle['nombre_tipo_movimiento'] == "E-TRNS.ALM" || $rowDetalle['nombre_tipo_movimiento'] == "S-TRNS.ALM") ? "<img src=\"../img/iconos/ico_cambio.png\"/>" : "";
			
			switch ($idModulo) {
				case 0 : $imgModuloDcto = "<img src=\"../img/iconos/ico_repuestos.gif\"/ title=\"Repuestos\">"; break;
				case 1 : $imgModuloDcto = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgModuloDcto = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
				case 3 : $imgModuloDcto = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
				case 4 : $imgModuloDcto = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				default : $imgModuloDcto = "";
			}
			
			switch ($rowDetalle['tipoMovimiento']) {
				case 1 : // COMPRA
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
						$rowDetalle['id_documento']);
					switch ($idModulo) {
						case 0: $aVerDctoAux = sprintf("../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 2: $aVerDctoAux = sprintf("../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
					break;
				case 2 : // ENTRADA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							switch ($idModulo) {
								case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowDetalle['id_documento']."|2"; break;
								case 1 : $aVerDctoAux = "../servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|".$rowDetalle['id_documento']; break;
								case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=".$rowDetalle['id_documento']; break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Entrada PDF")."\"/></a>" : "";
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
								$rowDetalle['id_documento']);
							switch ($idModulo) {
								case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>" : "";
							break;
						case 3 : // CONTRATO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../alquiler/reportes/al_contrato_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Contrato PDF")."\"/></a>",
								$rowDetalle['id_documento']);
							break;
					}
					break;
				case 3 : // VENTA
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
						$rowDetalle['id_documento']);
					switch ($idModulo) {
						case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						default : $aVerDctoAux = "";
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>" : "";
					break;
				case 4 : // SALIDA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							switch ($idModulo) {
								case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowDetalle['id_documento']."|4"; break;
								case 1 : $aVerDctoAux = "../servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=".$rowDetalle['id_documento']."|2|3"; break;
								case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=".$rowDetalle['id_documento']; break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Salida PDF")."\"/></a>" : "";
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_nota_credito_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
								$rowDetalle['id_documento']);
							$aVerDcto .= sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>",
								$rowDetalle['id_documento']);
							break;
						case 3 : // CONTRATO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../alquiler/reportes/al_contrato_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Contrato PDF")."\"/></a>",
								$rowDetalle['id_documento']);
							break;
					}
					break;
				default : $aVerDcto = "";
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" title=\"Id. Kardex: ".$rowDetalle['idKardex']."\">".$contFila2."</td>";
				$htmlTb .= "<td>".$imgInterAlmacen."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\" title=\"".date("h:i:s a", strtotime($rowDetalle['fechaMovimiento']))."\">".date(spanDateFormat, strtotime($rowDetalle['fechaMovimiento']))."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empresa'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".utf8_encode($rowDetalle['serial_carroceria'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"textoNegrita_10px\">";
						$htmlTb .= "<td>".utf8_encode($rowDetalle['condicion_unidad'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowDetalle['nombre_tipo_movimiento'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['numero_documento'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowDetalle['idPCE'])."</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowDetalle['ciPCE'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombrePCE'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($entradaSalida, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($precioUnitario, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($costoUnitario, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\">";
			$htmlTb .= "<td colspan=\"9\" class=\"tituloCampo\">Totales:<br>".htmlentities($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td class=\"tituloColumna\" colspan=\"2\">E #:<br>S #:</td>";
			$htmlTb .= "<td>".number_format($totalEntrada, 2, ".", ",")."<br>".number_format($totalSalida, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($totalValorEntradaPrecio, 2, ".", ",")."<br>".cAbrevMoneda.number_format($totalValorSalidaPrecio, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($totalValorEntradaCosto, 2, ".", ",")."<br>".cAbrevMoneda.number_format($totalValorSalidaCosto, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaKardex(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaKardex","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarKardex");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionExcel");
$xajax->register(XAJAX_FUNCTION,"exportarKardex");
$xajax->register(XAJAX_FUNCTION,"listaKardex");
?>