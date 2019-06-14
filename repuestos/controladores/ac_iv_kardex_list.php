<?php


function buscarArticulo($frmBuscar){
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstTipoMovimiento']),
		$frmBuscar['lstVerExcel'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaKardex(0, "codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function exportarKardex($frmBuscar, $tipoExportar = "EXCEL"){
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstTipoMovimiento']),
		$frmBuscar['lstVerExcel'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
		
	switch ($tipoExportar) {
		case "EXCEL" : $objResponse->script("window.open('reportes/iv_kardex_excel.php?valBusq=".$valBusq."','_self');"); break;
		case "TXT" : $objResponse->script("window.open('reportes/iv_kardex_txt.php?valBusq=".$valBusq."','_self');"); break;
	}
	
	return $objResponse;
}

function listaKardex($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		((CASE kardex.tipo_movimiento
			WHEN 1 THEN -- COMPRA
				(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
			WHEN 2 THEN -- ENTRADA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- ENTRADA CON VALE
						(CASE kardex.id_modulo
							WHEN 0 THEN
								(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							WHEN 1 THEN
								(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
						END)
					WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
						(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
				END)
			WHEN 3 THEN -- VENTA
				(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
			WHEN 4 THEN -- SALIDA
				(CASE kardex.tipo_documento_movimiento
					WHEN 1 THEN -- SALIDA CON VALE
						(CASE kardex.id_modulo
							WHEN 0 THEN -- REPUESTOS
								(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
							WHEN 1 THEN -- SERVICIOS
								(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
						END)
					WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
						(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
				END)
		END) = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = (CASE kardex.tipo_movimiento
											WHEN 1 THEN -- COMPRA
												(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- ENTRADA CON VALE
														(CASE kardex.id_modulo
															WHEN 0 THEN
																(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
															WHEN 1 THEN
																(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
														END)
													WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
														(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
												END)
											WHEN 3 THEN -- VENTA
												(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
											WHEN 4 THEN -- SALIDA
												(CASE kardex.tipo_documento_movimiento
													WHEN 1 THEN -- SALIDA CON VALE
														(CASE kardex.id_modulo
															WHEN 0 THEN -- REPUESTOS
																(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
															WHEN 1 THEN -- SERVICIOS
																(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
														END)
													WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
														(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
												END)
										END)))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_movimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("kardex.tipo_movimiento IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$queryArticulo = sprintf("SELECT
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion
	FROM iv_kardex kardex
		INNER JOIN iv_articulos art ON (kardex.id_articulo = art.id_articulo) %s 
	GROUP BY 1,2,3", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitArticulo = sprintf("%s %s LIMIT %d OFFSET %d", $queryArticulo, $sqlOrd, $maxRows, $startRow);
	$rsLimitArticulo = mysql_query($queryLimitArticulo);
	if (!$rsLimitArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsArticulo);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	while ($rowArticulo = mysql_fetch_assoc($rsLimitArticulo)) {
		$idArticulo = $rowArticulo['id_articulo'];
		
		$sqlBusq3 = " ";
		$sqlBusq4 = " ";
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("
			((CASE k.tipo_movimiento
					WHEN 1 THEN -- COMPRA
						(SELECT id_empresa FROM cp_factura WHERE id_factura = k.id_documento)
					WHEN 2 THEN -- ENTRADA
						(CASE k.tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								(CASE k.id_modulo
									WHEN 0 THEN
										(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = k.id_documento)
									WHEN 1 THEN
										(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = k.id_documento)
								END)
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = k.id_documento)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = k.id_documento)
					WHEN 4 THEN -- SALIDA
						(CASE k.tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								(CASE k.id_modulo
									WHEN 0 THEN -- REPUESTOS
										(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = k.id_documento)
									WHEN 1 THEN -- SERVICIOS
										(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = k.id_documento)
								END)
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = k.id_documento)
						END)
				END) = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = (CASE k.tipo_movimiento
													WHEN 1 THEN -- COMPRA
														(SELECT id_empresa FROM cp_factura WHERE id_factura = k.id_documento)
													WHEN 2 THEN -- ENTRADA
														(CASE k.tipo_documento_movimiento
															WHEN 1 THEN -- ENTRADA CON VALE
																(CASE k.id_modulo
																	WHEN 0 THEN
																		(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = k.id_documento)
																	WHEN 1 THEN
																		(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = k.id_documento)
																END)
															WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
																(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = k.id_documento)
														END)
													WHEN 3 THEN -- VENTA
														(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = k.id_documento)
													WHEN 4 THEN -- SALIDA
														(CASE k.tipo_documento_movimiento
															WHEN 1 THEN -- SALIDA CON VALE
																(CASE k.id_modulo
																	WHEN 0 THEN -- REPUESTOS
																		(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = k.id_documento)
																	WHEN 1 THEN -- SERVICIOS
																		(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = k.id_documento)
																END)
															WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
																(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = k.id_documento)
														END)
												END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
			
			$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
			$sqlBusq4 .= $cond.sprintf("
			(art_emp.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = art_emp.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		$queryArticuloSaldoAnt = sprintf("SELECT
			SUM(IF(k.tipo_movimiento IN (1,2), 1, (-1)) * IFNULL(k.cantidad, 0)) AS saldo_anterior
		FROM iv_kardex k
		WHERE k.id_articulo = %s
			AND DATE(k.fecha_movimiento) < %s %s",
			valTpDato($idArticulo, "int"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"), $sqlBusq3);
		$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
		if (!$rsArticuloSaldoAnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArticuloSaldoAnt = mysql_num_rows($rsArticuloSaldoAnt);
		$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
		
		$queryArticuloSaldoIni = sprintf("SELECT
			SUM(IFNULL(art_emp.cantidad_inicio, 0)) AS saldo_inicio
		FROM iv_articulos_empresa art_emp
		WHERE art_emp.id_articulo = %s %s",
			valTpDato($idArticulo, "int"), $sqlBusq4);
		$rsArticuloSaldoIni = mysql_query($queryArticuloSaldoIni);
		if (!$rsArticuloSaldoIni) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArticuloSaldoIni = mysql_num_rows($rsArticuloSaldoAnt);
		$rowArticuloSaldoIni = mysql_fetch_assoc($rsArticuloSaldoIni);
		
		$cantSaldoAnterior = $rowArticuloSaldoIni['saldo_inicio'] + $rowArticuloSaldoAnt['saldo_anterior'];
		
		$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
		$htmlTh = "<tr align=\"left\" height=\"22\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"3\" title=\"".$idArticulo."\">".utf8_encode("Código:")."</a></td>";
			$htmlTh .= "<td colspan=\"9\">".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"left\" height=\"22\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"3\">".utf8_encode("Descripción:")."</td>";
			$htmlTh .= "<td colspan=\"9\">".utf8_encode($rowArticulo['descripcion'])."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"2\"></td>";
			$htmlTh .= "<td width=\"6%\">".utf8_encode("Fecha")."</td>";
			$htmlTh .= "<td width=\"14%\">".utf8_encode("Empresa")."</td>";
			$htmlTh .= "<td width=\"12%\">".utf8_encode("Ubicación")."</td>";
			$htmlTh .= "<td width=\"4%\">".utf8_encode("T")."</td>";
			$htmlTh .= "<td width=\"8%\">".utf8_encode("Nro. Documento")."</td>";
			$htmlTh .= "<td colspan=\"2\" width=\"26%\">".utf8_encode("C/P/M")."</td>";
			$htmlTh .= "<td width=\"8%\">".utf8_encode("E/S")."</td>";
			$htmlTh .= "<td width=\"6%\">".utf8_encode("Saldo")."</td>";
			$htmlTh .= "<td width=\"8%\">".utf8_encode($spanPrecioUnitario)."</td>";
			$htmlTh .= "<td width=\"8%\">".utf8_encode("Costo Unit.")."</td>";
		$htmlTh .= "</tr>";
		
		if ($cantSaldoAnterior != 0) {
			$clase = "trResaltar5";
			
			$totalEntrada = $cantSaldoAnterior;
			$entradaSalida = $cantSaldoAnterior;
			
			$htmlTh .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTh .= "<td colspan=\"2\"></td>";
				$htmlTh .= "<td class=\"divMsjInfo\" colspan=\"5\">Saldo Anterior al Intervalo de Fecha Seleccionado:</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td align=\"right\">".number_format($cantSaldoAnterior, 2, ".", ",")."</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
			$htmlTh .= "</tr>";
		}
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("id_articulo = %s",
			valTpDato($idArticulo, "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			((CASE kardex.tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN -- REPUESTOS
									(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN -- SERVICIOS
									(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE kardex.tipo_movimiento
													WHEN 1 THEN -- COMPRA
														(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- ENTRADA CON VALE
																(CASE kardex.id_modulo
																	WHEN 0 THEN
																		(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
																	WHEN 1 THEN
																		(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
																END)
															WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
																(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
														END)
													WHEN 3 THEN -- VENTA
														(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
													WHEN 4 THEN -- SALIDA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- SALIDA CON VALE
																(CASE kardex.id_modulo
																	WHEN 0 THEN -- REPUESTOS
																		(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
																	WHEN 1 THEN -- SERVICIOS
																		(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
																END)
															WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
																(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
														END)
												END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE(fecha_movimiento) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("kardex.tipo_movimiento IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		}
		
		$queryDetalle = sprintf("SELECT
			kardex.id_kardex,
			
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN -- REPUESTOS
									(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN -- SERVICIOS
									(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) AS id_empresa,
			
			(SELECT
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			WHERE vw_iv_emp_suc.id_empresa_reg = (CASE kardex.tipo_movimiento
													WHEN 1 THEN -- COMPRA
														(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- ENTRADA CON VALE
																(CASE kardex.id_modulo
																	WHEN 0 THEN
																		(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
																	WHEN 1 THEN
																		(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
																END)
															WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
																(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
														END)
													WHEN 3 THEN -- VENTA
														(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
													WHEN 4 THEN -- SALIDA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- SALIDA CON VALE
																(CASE kardex.id_modulo
																	WHEN 0 THEN -- REPUESTOS
																		(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
																	WHEN 1 THEN -- SERVICIOS
																		(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
																END)
															WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
																(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
														END)
												END)) AS nombre_empresa,
			
			kardex.id_documento,
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN
					(SELECT numero_factura_proveedor FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT numeracion_vale_entrada FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT numero_vale_entrada FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN
							(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN
					(SELECT numeroFactura FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT numeracion_vale_salida FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN
									(SELECT numero_vale FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN
							(SELECT numero_nota_credito FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
					END)
			END) AS numero_documento,
			
			kardex.id_modulo,
			(CASE kardex.id_modulo
				WHEN 0 THEN		'R'
				WHEN 1 THEN		'S'
			END) AS nombre_modulo,
			
			kardex.tipo_movimiento,
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN	'C'
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(CASE (SELECT tipo_vale_entrada FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
										WHEN 4 THEN
											'E-TRNS.ALM'
										ELSE
											'E'
									END)
								WHEN 1 THEN
									'E'
							END)
						WHEN 2 THEN
							'E-NC'
					END)
				WHEN 3 THEN 'V'
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(CASE (SELECT tipo_vale_salida FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
										WHEN 4 THEN
											'S-TRNS.ALM'
										ELSE
											'S'
									END)
								WHEN 1 THEN
									CONCAT_WS('-','S',(SELECT tipo_orden.nombre_tipo_orden
													FROM sa_vale_salida sa_vs
														INNER JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden)
														INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
													WHERE sa_vs.id_vale_salida = kardex.id_documento))
							END)
						WHEN 2 THEN
							'S-NC'
					END)
			END) AS nombre_tipo_movimiento,
			
			kardex.id_clave_movimiento,
			kardex.tipo_documento_movimiento,
			kardex.estado,
			kardex.fecha_movimiento,
			
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN
					(SELECT CONCAT_WS('-', lrif, rif) FROM cp_proveedor
					WHERE id_proveedor = (SELECT id_proveedor FROM cp_factura WHERE id_factura = kardex.id_documento))
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(CASE (SELECT tipo_vale_entrada FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
										WHEN 1 THEN
											(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 2 THEN
											(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 3 THEN
											(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 4 THEN
											(SELECT cedula FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 5 THEN
											(SELECT cedula FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
									END)
								WHEN 1 THEN
									(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
									WHERE id = (SELECT
													IFNULL(
														(SELECT sa_recepcion.id_cliente_pago FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
															(SELECT sa_orden.id_orden FROM sa_orden WHERE sa_orden.id_orden = sa_vale_entrada.id_orden)
														), 
														(SELECT id_cliente_contacto FROM sa_cita WHERE sa_cita.id_cita= 
															(SELECT sa_recepcion.id_cita FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
																(SELECT sa_orden.id_recepcion FROM sa_orden WHERE sa_orden.id_orden = sa_vale_entrada.id_orden)
															)
														)
													) AS id_cliente
												FROM sa_vale_entrada
												WHERE id_vale_entrada = kardex.id_documento))
							END)
						WHEN 2 THEN
							(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
							WHERE id = (SELECT idCliente FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento))
					END)
				WHEN 3 THEN
					(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
					WHERE id = (SELECT idCliente FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento))
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(CASE (SELECT tipo_vale_salida FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
										WHEN 1 THEN
											(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 2 THEN
											(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 3 THEN
											(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 4 THEN
											(SELECT cedula FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 5 THEN
											(SELECT cedula FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
									END)
								WHEN 1 THEN
									(SELECT CONCAT_WS('-', lci, ci) AS ci_cliente FROM cj_cc_cliente
									WHERE id = (SELECT
													IFNULL(
														(SELECT sa_recepcion.id_cliente_pago FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
															(SELECT sa_orden.id_orden FROM sa_orden WHERE sa_orden.id_orden = sa_vale_salida.id_orden)
														), 
														(SELECT id_cliente_contacto FROM sa_cita WHERE sa_cita.id_cita= 
															(SELECT sa_recepcion.id_cita FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
																(SELECT sa_orden.id_recepcion FROM sa_orden WHERE sa_orden.id_orden = sa_vale_salida.id_orden)
															)
														)
													) AS id_cliente
												FROM sa_vale_salida
												WHERE id_vale_salida = kardex.id_documento))
							END)
						WHEN 2 THEN
							(SELECT CONCAT_WS('-', lrif, rif) FROM cp_proveedor
							WHERE id_proveedor = (SELECT id_proveedor FROM cp_notacredito WHERE id_notacredito = kardex.id_documento))
					END)
			END) AS ciPCE,
			
			(CASE kardex.tipo_movimiento
				WHEN 1 THEN
					(SELECT nombre FROM cp_proveedor
					WHERE id_proveedor = (SELECT id_proveedor FROM cp_factura WHERE id_factura = kardex.id_documento))
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(CASE (SELECT tipo_vale_entrada FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
										WHEN 1 THEN
											(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 2 THEN
											(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 3 THEN
											(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 4 THEN
											(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
										WHEN 5 THEN
											(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento))
									END)
								WHEN 1 THEN
									(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
									WHERE id = (SELECT
													IFNULL(
														(SELECT sa_recepcion.id_cliente_pago FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
															(SELECT sa_orden.id_orden FROM sa_orden WHERE sa_orden.id_orden = sa_vale_entrada.id_orden)
														), 
														(SELECT id_cliente_contacto FROM sa_cita WHERE sa_cita.id_cita= 
															(SELECT sa_recepcion.id_cita FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
																(SELECT sa_orden.id_recepcion FROM sa_orden WHERE sa_orden.id_orden = sa_vale_entrada.id_orden)
															)
														)
													) AS id_cliente
												FROM sa_vale_entrada
												WHERE id_vale_entrada = kardex.id_documento))
							END)
						WHEN 2 THEN
							(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
							WHERE id = (SELECT idCliente FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento))
					END)
				WHEN 3 THEN
					(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
					WHERE id = (SELECT idCliente FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento))
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(CASE (SELECT tipo_vale_salida FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
										WHEN 1 THEN
											(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 2 THEN
											(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 3 THEN
											(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
											WHERE id = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 4 THEN
											(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
										WHEN 5 THEN
											(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado
											WHERE id_empleado = (SELECT id_cliente FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento))
									END)
								WHEN 1 THEN
									(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
									WHERE id = (SELECT
													IFNULL(
														(SELECT sa_recepcion.id_cliente_pago FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
															(SELECT sa_orden.id_orden FROM sa_orden WHERE sa_orden.id_orden = sa_vale_salida.id_orden)
														), 
														(SELECT id_cliente_contacto FROM sa_cita WHERE sa_cita.id_cita= 
															(SELECT sa_recepcion.id_cita FROM sa_recepcion WHERE sa_recepcion.id_recepcion= 
																(SELECT sa_orden.id_recepcion FROM sa_orden WHERE sa_orden.id_orden = sa_vale_salida.id_orden)
															)
														)
													) AS id_cliente
												FROM sa_vale_salida
												WHERE id_vale_salida = kardex.id_documento))
							END)
						WHEN 2 THEN
							(SELECT nombre FROM cp_proveedor
							WHERE id_proveedor = (SELECT id_proveedor FROM cp_notacredito WHERE id_notacredito = kardex.id_documento))
					END)
			END) AS nombrePCE,
			
			kardex.id_casilla,
			
			(SELECT almacen.descripcion
			FROM iv_casillas
				INNER JOIN iv_tramos ON (iv_casillas.id_tramo = iv_tramos.id_tramo)
				INNER JOIN iv_estantes ON (iv_tramos.id_estante = iv_estantes.id_estante)
				INNER JOIN iv_calles ON (iv_estantes.id_calle = iv_calles.id_calle)
				INNER JOIN iv_almacenes almacen ON (iv_calles.id_almacen = almacen.id_almacen)
			WHERE id_casilla = kardex.id_casilla) AS descripcion_almacen,
			
			(SELECT CONCAT_WS('-', descripcion_calle, descripcion_estante, descripcion_tramo, descripcion_casilla) AS ubicacion
			FROM iv_casillas
				INNER JOIN iv_tramos ON (iv_casillas.id_tramo = iv_tramos.id_tramo)
				INNER JOIN iv_estantes ON (iv_tramos.id_estante = iv_estantes.id_estante)
				INNER JOIN iv_calles ON (iv_estantes.id_calle = iv_calles.id_calle)
				INNER JOIN iv_almacenes almacen ON (iv_calles.id_almacen = almacen.id_almacen)
			WHERE id_casilla = kardex.id_casilla) AS ubicacion,
			
			kardex.id_articulo_costo,
			kardex.cantidad,
			(IFNULL(kardex.precio,0) + IFNULL(kardex.pmu_unitario,0)) AS precio,
			kardex.costo,
			kardex.costo_cargo,
			kardex.costo_diferencia,
			kardex.porcentaje_descuento,
			kardex.subtotal_descuento
		FROM iv_kardex kardex %s
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) ASC, kardex.id_kardex ASC", $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$idEmpresa = $rowDetalle['id_empresa'];
			$idModulo = $rowDetalle['id_modulo'];
			
			$titleCosto = "";
			switch ($rowDetalle['tipo_movimiento']) {
				case 1 : // COMPRA
					$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] + $rowDetalle['costo_diferencia'] - $rowDetalle['subtotal_descuento']; 
					$precioUnitario = $costoUnitario;
					$titleCosto = ($rowDetalle['costo_diferencia'] != 0) ? sprintf("title=\"Costo: %s + Diferencia Cambiaria: %s\"",
						number_format($rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento'], 2, ".", ","),
						number_format($rowDetalle['costo_diferencia'], 2, ".", ",")) : "";
					break;
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
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] + $rowDetalle['costo_diferencia'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = $costoUnitario;
							$titleCosto = ($rowDetalle['costo_diferencia'] != 0) ? sprintf("title=\"Costo: %s + Diferencia Cambiaria: %s\"",
								number_format($rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento'], 2, ".", ","),
								number_format($rowDetalle['costo_diferencia'], 2, ".", ",")) : "";
							break;
					}
					break;
			}
		
			if ($rowDetalle['estado'] == 0) {
				$totalEntrada += $rowDetalle['cantidad'];
				$totalValorEntradaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
				$totalValorEntradaCosto += $rowDetalle['cantidad'] * $costoUnitario;
				$entradaSalida += $rowDetalle['cantidad'];
			} else if ($rowDetalle['estado'] == 1) {
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
				default : $imgModuloDcto = "";
			}
			
			switch ($rowDetalle['tipo_movimiento']) {
				case 1 : // COMPRA
					$aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('reportes/iv_registro_compra_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Registro Compra PDF\"/><a>";
					break;
				case 2 : // ENTRADA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE ENTRADA
							switch ($rowDetalle['id_modulo']) {
								case 0 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowDetalle['id_documento']."|2', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Entrada PDF\"/><a>"; break;
								case 1 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Entrada PDF\"/><a>"; break;
							}
							break;
						case 2 : // NOTA DE CREDITO
							switch ($rowDetalle['id_modulo']) {
								case 0 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('reportes/iv_devolucion_venta_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Ver Nota Crédito PDF")."\"/><a>"; break;
								case 1 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Ver Nota Crédito PDF")."\"/><a>"; break;
							}
							break;
					}
					break;
				case 3 : // VENTA
					switch ($rowDetalle['id_modulo']) {
						case 0: $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('reportes/iv_factura_venta_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Factura Venta PDF\"/><a>"; break;
						case 1 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=".$rowDetalle['id_documento']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Factura Venta PDF\"/><a>"; break;
					}
					break;
				case 4 : // SALIDA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE SALIDA
							switch ($rowDetalle['id_modulo']) {
								case 0 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowDetalle['id_documento']."|4', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Salida PDF\"/><a>"; break;
								case 1 : $aVerDcto = "<a id=\"aVerDcto\" href=\"javascript:verVentana('../servicios/sa_imprimir_historico_vale.php?valBusq=".$rowDetalle['id_documento']."|2|3', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Vale Salida PDF\"/><a>"; break;
							}
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = ""; break;
					}
					break;
				default :
					$aVerDcto = "";
			}
			
			// DATOS DE LA RELACION ARTICULO Y EMPRESA
			$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
			$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
			
			$class = ($rowDetalle['fecha_movimiento'] >= $rowArtEmp['fecha_kardex_corte']
			&& (($rowArtEmp['id_tipo_corte'] == 1 && $rowDetalle['id_kardex'] != $rowArtEmp['id_kardex_corte'] && $rowDetalle['id_kardex'] >= $rowArtEmp['id_kardex_corte'])
				|| ($rowArtEmp['id_tipo_corte'] == 2))) ? "class=\"divMsjInfo textoNegrita_9px\"" : "class=\"textoNegrita_9px\"";
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" ".$class." title=\"".$rowDetalle['id_kardex']."\">".$contFila."</td>";
				$htmlTb .= "<td>".$imgInterAlmacen."</td>";
				$htmlTb .= "<td align=\"center\" title=\"".date("H:i:s",strtotime($rowDetalle['fecha_movimiento']))."\">".date(spanDateFormat,strtotime($rowDetalle['fecha_movimiento']))."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empresa'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\" title=\"".$rowDetalle['id_casilla']."\">";
					$htmlTb .= "<span class=\"textoNegrita_10px\">".utf8_encode(strtoupper($rowDetalle['descripcion_almacen']))."</span>";
					$htmlTb .= "<br>";
					$htmlTb .= utf8_encode(str_replace("-[]", "", $rowDetalle['ubicacion']));
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\">".$rowDetalle['nombre_tipo_movimiento']."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td>".$aVerDcto."</td>";
						$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($rowDetalle['numero_documento'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".$rowDetalle['ciPCE']."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombrePCE'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
					$htmlTb .= "<tr>";
						$htmlTb .= ($rowDetalle['id_articulo_costo'] > 0) ? "<tr><td><span id=\"spnLote".$contFila2."\" class=\"textoNegrita_9px\">LOTE: ".$rowDetalle['id_articulo_costo']."</span></td><tr>" : "";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".number_format($entradaSalida, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\" ".$titleCosto.">".cAbrevMoneda.number_format($precioUnitario, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\" ".$titleCosto.">".cAbrevMoneda.number_format($costoUnitario, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$htmlTb .= "<tr class=\"trResaltarTotal3\" height=\"22\">";
			$htmlTb .= "<td align=\"right\" colspan=\"8\" class=\"tituloCampo\">Totales:<br>".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloColumna\" colspan=\"2\">E #:<br>S #:</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntrada, 2, ".", ",")."<br>".number_format($totalSalida, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($totalValorEntradaPrecio, 2, ".", ",")."<br>".cAbrevMoneda.number_format($totalValorSalidaPrecio, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($totalValorEntradaCosto, 2, ".", ",")."<br>".cAbrevMoneda.number_format($totalValorSalidaCosto, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">&nbsp;";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
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

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarKardex");
$xajax->register(XAJAX_FUNCTION,"listaKardex");
?>