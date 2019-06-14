<?php
function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerExcel'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "nom_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function exportarKardex($frmBuscar, $tipoExportar = "EXCEL"){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerExcel'],
		$frmBuscar['txtCriterio']);
		
	switch ($tipoExportar) {
		case "EXCEL" : $objResponse->script("window.open('../vehiculos/reportes/an_kardex_excel.php?valBusq=".$valBusq."','_self');"); break;
		case "TXT" : $objResponse->script("window.open('../vehiculos/reportes/an_kardex_txt.php?valBusq=".$valBusq."','_self');"); break;
	}
	
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1, $totalRows = NULL) {
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
				(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
				END)
		END) = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
											WHEN 1 THEN -- COMPRA
												(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- ENTRADA CON VALE
														(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
														(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
												END)
											WHEN 3 THEN -- VENTA
												(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
											WHEN 4 THEN -- SALIDA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- SALIDA CON VALE
														(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
													WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
														(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
												END)
										END)))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fechaMovimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"date"));
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
		CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
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
		
		$queryArticuloSaldoAnt = sprintf("SELECT
			(IFNULL((SELECT SUM(k.cantidad) FROM an_kardex k
			WHERE k.idUnidadBasica = %s
				AND DATE(k.fechaMovimiento) < %s
				AND k.tipoMovimiento IN (1,2,3)),0)
			-
			IFNULL((SELECT SUM(k.cantidad) FROM an_kardex k
			WHERE k.idUnidadBasica = %s
				AND DATE(k.fechaMovimiento) < %s
				AND k.tipoMovimiento IN (3,4)),0)) AS saldo_anterior",
			valTpDato($idUnidadBasica, "int"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
			valTpDato($idUnidadBasica, "int"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"));
		$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
		if (!$rsArticuloSaldoAnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
		
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
			$htmlTh .= "<td colspan=\"2\" width=\"26%\">C/P/M</td>";
			$htmlTh .= "<td width=\"6%\">E/S</td>";
			$htmlTh .= "<td width=\"6%\">Saldo</td>";
			$htmlTh .= "<td width=\"8%\">".$spanPrecioUnitario."</td>";
			$htmlTh .= "<td width=\"8%\">Costo Unit.</td>";
		$htmlTh .= "</tr>";
		
		if ($rowArticuloSaldoAnt['saldo_anterior'] != 0) {
			$clase = "trResaltar5";
			
			$totalEntrada = $rowArticuloSaldoAnt['saldo_anterior'];
			$entradaSalida = $rowArticuloSaldoAnt['saldo_anterior'];
			
			$saldoAnterior = $rowArticuloSaldoAnt['saldo_anterior'];
			
			$htmlTh .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTh .= "<td colspan=\"2\"></td>";
				$htmlTh .= "<td class=\"divMsjInfo\" colspan=\"5\">Saldo Anterior al Intervalo de Fecha Seleccionado:</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td align=\"right\">".number_format($saldoAnterior, 2, ".", ",")."</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
			$htmlTh .= "</tr>";
		}
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_bas.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'],"int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			((CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
					END)
			END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE kardex.tipoMovimiento
												WHEN 1 THEN -- COMPRA
													(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
												WHEN 2 THEN -- ENTRADA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- ENTRADA CON VALE
															(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
														WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
															(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
													END)
												WHEN 3 THEN -- VENTA
													(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
												WHEN 4 THEN -- SALIDA
													(CASE kardex.tipo_documento_movimiento
														WHEN 1 THEN -- SALIDA CON VALE
															(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
														WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
															(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
													END)
											END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE(fechaMovimiento) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
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
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
					END)
			END) AS id_empresa,
			
			kardex.fechaMovimiento,
			(SELECT
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			WHERE vw_iv_emp_suc.id_empresa_reg = (CASE kardex.tipoMovimiento
													WHEN 1 THEN -- COMPRA
														(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- ENTRADA CON VALE
																(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
															WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
																(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
														END)
													WHEN 3 THEN -- VENTA
														(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
													WHEN 4 THEN -- SALIDA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- SALIDA CON VALE
																(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
															WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
																(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
														END)
												END)) AS nombre_empresa,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			kardex.id_documento,
			(CASE tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT fact_comp.numero_factura_proveedor FROM cp_factura fact_comp WHERE fact_comp.id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT vale_ent.numeracion_vale_entrada FROM an_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT nota_cred.numeracion_nota_credito FROM cj_cc_notacredito nota_cred WHERE nota_cred.idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT fact_vent.numeroFactura FROM cj_cc_encabezadofactura fact_vent WHERE fact_vent.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT vale_sal.numeracion_vale_salida FROM an_vale_salida vale_sal WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.numero_nota_credito FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) AS numero_documento,
			
			2 AS id_modulo,
			(CASE 2
				WHEN 0 THEN		'R'
				WHEN 1 THEN		'S'
				WHEN 2 THEN		'V'
			END) AS nombre_modulo,
			
			kardex.tipoMovimiento,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN	'C'
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE (SELECT tipo_vale_entrada FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 4 THEN
									'E-TRNS.ALM'
								ELSE
									'E'
							END)
						WHEN 2 THEN
							'E-NC'
					END)
				WHEN 3 THEN 'V'
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE (SELECT tipo_vale_salida FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 4 THEN
									'S-TRNS.ALM'
								ELSE
									'S'
							END)
						WHEN 2 THEN
							'S-NC'
					END)
			END) AS nombre_tipo_movimiento,
			
			kardex.claveKardex,
			kardex.tipo_documento_movimiento,
			kardex.estadoKardex,
			DATE(kardex.fechaMovimiento) AS fechaMovimiento,
			
			(CASE tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT CONCAT_WS('-', prov.lrif, prov.rif) AS ciPCE
					FROM cp_factura fact_comp
						INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
					WHERE fact_comp.id_factura = kardex.id_documento
						AND fact_comp.id_modulo IN (2))
				WHEN 2 THEN -- ENTRADA
					(CASE tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
							FROM an_vale_entrada vale_ent
								INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id)
							WHERE vale_ent.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
							FROM cj_cc_notacredito nota_cred
								INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
								INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
							WHERE nota_cred.idNotaCredito = kardex.id_documento
								AND nota_cred.idDepartamentoNotaCredito IN (2)
								AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
					FROM cj_cc_encabezadofactura fact_vent
						INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
					WHERE fact_vent.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT CONCAT_WS('-',cliente.lci,cliente.ci) AS ciPCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
					END)
			END) AS ciPCE,
			
			(CASE tipoMovimiento
				WHEN 1 THEN
					(SELECT prov.nombre
					FROM cp_factura fact_comp
						INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
					WHERE fact_comp.id_factura = kardex.id_documento
						AND fact_comp.id_modulo IN (2))
				WHEN 2 THEN
					(CASE tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM an_vale_entrada vale_ent
								INNER JOIN cj_cc_cliente cliente ON (vale_ent.id_cliente = cliente.id)
							WHERE vale_ent.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM cj_cc_notacredito nota_cred
								INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
								INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
							WHERE nota_cred.idNotaCredito = kardex.id_documento
								AND nota_cred.idDepartamentoNotaCredito IN (2)
								AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					END)
				WHEN 3 THEN
					(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
					FROM cj_cc_encabezadofactura fact_vent
						INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
					WHERE fact_vent.idFactura = kardex.id_documento)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
					END)
			END) AS nombrePCE,
			
			(CASE tipoMovimiento
				WHEN 2 THEN
					(CASE tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT vale_ent.tipo_vale_entrada FROM an_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = kardex.id_documento)
					END)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT vale_sal.tipo_vale_salida FROM an_vale_salida vale_sal WHERE vale_sal.id_vale_salida = kardex.id_documento)
					END)
			END) AS tipo_vale,
			
			kardex.cantidad,
			kardex.precio,
			kardex.costo,
			kardex.costo_cargo,
			kardex.porcentaje_descuento,
			kardex.subtotal_descuento,
			
			(CASE tipoMovimiento
				WHEN 1 THEN
					uni_fis.precio_compra
				WHEN 2 THEN
					(CASE tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							(SELECT fact_vent_det_vehic.precio_unitario
							FROM cj_cc_notacredito nota_cred
								INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
							WHERE nota_cred.idNotaCredito = kardex.id_documento
								AND nota_cred.idDepartamentoNotaCredito IN (2)
								AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					END)
				WHEN 3 THEN
					(SELECT fact_vent_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
					WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
						AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN
					(CASE tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							uni_fis.precio_compra
					END)
			END) AS precio_unidad_dcto,
			
			(CASE tipoMovimiento
				WHEN 1 THEN
					uni_fis.precio_compra
				WHEN 2 THEN
					(CASE tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							(SELECT fact_vent_det_vehic.costo_compra
							FROM cj_cc_notacredito nota_cred
								INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
							WHERE nota_cred.idNotaCredito =kardex.id_documento
								AND nota_cred.idDepartamentoNotaCredito IN (2)
								AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					END)
				WHEN 3 THEN
					(SELECT fact_vent_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
					WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
						AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN
					(CASE tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							uni_fis.precio_compra
					END)
			END) AS costo_unidad_dcto
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_kardex kardex ON (uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas) %s
		ORDER BY kardex.fechaMovimiento ASC, kardex.idKardex ASC", $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$updateSQL = sprintf("UPDATE an_kardex SET
				cantidad = 1
			WHERE idKardex = %s
				AND cantidad = 0;",
				valTpDato($rowDetalle['idKardex'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
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
					}
					break;
				case 3 : // VENTA
					$costoUnitario = $rowDetalle['costo'];
					$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
				case 4 : // SALIDA
					switch($rowDetalle['tipo_documento_movimiento']) {
						case 1 : 
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
						case 2 : 
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = $costoUnitario;
							break;
					}
					break;
			}
			
			if ($rowDetalle['estadoKardex'] == 0) {
				$totalEntrada += $rowDetalle['cantidad'];
				$totalValorEntrada += $rowDetalle['cantidad'] * $precioUnitario;
				$entradaSalida += $rowDetalle['cantidad'];
			} else if ($rowDetalle['estadoKardex'] == 1) {
				$totalSalida += $rowDetalle['cantidad'];
				$totalValorSalida += $rowDetalle['cantidad'] * $precioUnitario;
				$entradaSalida -= $rowDetalle['cantidad'];
			}
			
			$imgInterAlmacen = ($rowDetalle['nombre_tipo_movimiento'] == "E-TRNS.ALM" || $rowDetalle['nombre_tipo_movimiento'] == "S-TRNS.ALM") ? "<img src=\"../img/iconos/ico_cambio.png\"/>" : "";
			
			switch ($idModulo) {
				case 0 : $imgModuloDcto = "<img src=\"../img/iconos/ico_repuestos.gif\"/ title=\"Repuestos\">"; break;
				case 1 : $imgModuloDcto = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgModuloDcto = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
				default : $imgModuloDcto = "";
			}
			
			switch ($rowDetalle['tipoMovimiento']) {
				case 1 : // COMPRA
					$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Registro Compra PDF\"/><a>";
					break;
				case 2 : // ENTRADA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE ENTRADA
							$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Entrada PDF\"/><a>";
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Ver Nota Crédito PDF")."\"/><a>";
							break;
						default : $aVerDcto = "";
					}
					break;
				case 3 : // VENTA
					$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Factura Venta PDF\"/><a>";
					break;
				case 4 : // SALIDA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE SALIDA
							$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Salida PDF\"/><a>";
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Ver Nota Crédito PDF")."\"/><a>";
							break;
						default : $aVerDcto = "";
					}
					break;
				default : $aVerDcto = "";
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" title=\"".$rowDetalle['idKardex']."\">".$contFila2."</td>";
				$htmlTb .= "<td>".$imgInterAlmacen."</td>";
				$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($rowDetalle['fechaMovimiento']))."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empresa'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowDetalle['serial_carroceria'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowDetalle['nombre_tipo_movimiento'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td>".$aVerDcto."</td>";
						$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($rowDetalle['numero_documento'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowDetalle['ciPCE'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombrePCE'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($entradaSalida, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($precioUnitario, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($costoUnitario, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\">";
			$htmlTb .= "<td colspan=\"8\" class=\"tituloCampo\">Totales:<br>".htmlentities($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td class=\"tituloColumna\">E #:<br>S #:</td>";
			$htmlTb .= "<td>".number_format($totalEntrada, 2, ".", ",")."<br>".number_format($totalSalida, 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"2\" class=\"tituloColumna\">E:<br>S:</td>";
			$htmlTb .= "<td>".number_format($totalValorEntrada, 2, ".", ",")."<br>".number_format($totalValorSalida, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"exportarKardex");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
?>