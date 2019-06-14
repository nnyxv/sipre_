<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");

include("clase_txt.php");

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];

$tituloDcto = "ERP KARDEX";
$ar = fopen("tmp/".$tituloDcto.".txt","w") or die("Problemas en la creacion");
	
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

//iteramos para los resultados
$query = sprintf("SELECT
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion
FROM iv_kardex kardex
	INNER JOIN iv_articulos art ON (kardex.id_articulo = art.id_articulo) %s
GROUP BY 1,2,3
ORDER BY art.codigo_articulo ASC;", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
$nroHoja = 0;
while ($row = mysql_fetch_assoc($rs)) {
	$contFila = 0;
	
	$idArticulo = $row['id_articulo'];
	
	cabeceraTxt($ar, $idEmpresa);
	
	fputs($ar,chr(13).chr(10));
	fputs($ar,str_pad("Kardex ".elimCaracter(utf8_encode($row['codigo_articulo']),";")." (".$valCadBusq[1]." al ".$valCadBusq[2].")", 280, " ", STR_PAD_BOTH));
	fputs($ar,chr(13).chr(10));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar, str_pad("Código:", 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad(elimCaracter(utf8_encode($row['codigo_articulo']),";"), 91, " ", STR_PAD_RIGHT)); fputs($ar, " ");
	fputs($ar, str_pad("C: Compra", 14, " ", STR_PAD_RIGHT));
	fputs($ar, str_pad("E: Entrada", 14, " ", STR_PAD_RIGHT));
	fputs($ar, str_pad("E-NC: Entrada por Nota de Crédito", 40, " ", STR_PAD_RIGHT));
	fputs($ar, str_pad("E-TRNS.ALM: Entrada por Transferencia de Almacen", 50, " ", STR_PAD_RIGHT));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar, str_pad("Descripción:", 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad(utf8_encode($row['descripcion']), 91, " ", STR_PAD_RIGHT)); fputs($ar, " ");
	fputs($ar, str_pad("V: Venta", 14, " ", STR_PAD_RIGHT));
	fputs($ar, str_pad("S: Salida", 14, " ", STR_PAD_RIGHT));
	fputs($ar, str_pad("S-XXX: Salida por Garantía", 40, " ", STR_PAD_RIGHT));
	fputs($ar, str_pad("S-TRNS.ALM: Salida por Transferencia de Almacen", 50, " ", STR_PAD_RIGHT));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar,str_pad("", 280, "-", STR_PAD_LEFT));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar, str_pad("", 6, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("", 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Fecha", 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Empresa", 40, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Ubicación", 40, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("T", 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Nro. Documento", 20, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("C/P/N", 60, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("E/S", 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Lote", 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Saldo", 18, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad($spanPrecioUnitario, 18, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("Costo Unit.", 18, " ", STR_PAD_BOTH)); fputs($ar, " ");
	
	fputs($ar,chr(13).chr(10));
	fputs($ar,str_pad("", 280, "-", STR_PAD_LEFT));
	
	$sqlBusq3 = " ";
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
	}
	
	$queryArticuloSaldoAnt = sprintf("SELECT
		SUM(IF(k.tipo_movimiento IN (1,2), 1, (-1)) * IFNULL(k.cantidad, 0)) AS saldo_anterior
	FROM iv_kardex k
	WHERE k.id_articulo = %s
		AND DATE(k.fecha_movimiento) < %s %s",
		valTpDato($idArticulo, "int"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"), $sqlBusq3);
	$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
	if (!$rsArticuloSaldoAnt) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsArticuloSaldoAnt = mysql_num_rows($rsArticuloSaldoAnt);
	$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
	
	$queryArticuloSaldoIni = sprintf("SELECT
		SUM(IFNULL(art_emp.cantidad_inicio, 0)) AS saldo_inicio
	FROM iv_articulos_empresa art_emp
	WHERE art_emp.id_articulo = %s %s",
		valTpDato($idArticulo, "int"), $sqlBusq4);
	$rsArticuloSaldoIni = mysql_query($queryArticuloSaldoIni);
	if (!$rsArticuloSaldoIni) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArticuloSaldoIni = mysql_num_rows($rsArticuloSaldoAnt);
	$rowArticuloSaldoIni = mysql_fetch_assoc($rsArticuloSaldoIni);
	
	$cantSaldoAnterior = $rowArticuloSaldoIni['saldo_inicio'] + $rowArticuloSaldoAnt['saldo_anterior'];
	
	$totalEntrada = 0;
	$totalValorEntradaPrecio = 0;
	$totalSalida = 0;
	$totalValorSalidaPrecio = 0;
	$entradaSalida = 0;
	$contFila2 = 0;
	if ($cantSaldoAnterior != 0) {
		$contFila++;
		$contFila2++;
		
		$totalEntrada = $cantSaldoAnterior;
		$entradaSalida = $cantSaldoAnterior;
		
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad("", 6, " ", STR_PAD_BOTH)); fputs($ar, " ");
		fputs($ar, str_pad("", 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
		fputs($ar, str_pad("Saldo Anterior al Intervalo de Fecha Seleccionado:", 205, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar, str_pad(number_format($cantSaldoAnterior, 2, ".", ","), 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	}
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("kardex.id_articulo = %s",
		valTpDato($idArticulo,"int"));
	
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
		DATE(kardex.fecha_movimiento) AS fecha_movimiento,
		
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
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$contFila2 = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)){
		$contFila++;
		$contFila2++;
			
		$idEmpresa = $rowDetalle['id_empresa'];
		$idModulo = $rowDetalle['id_modulo'];
			
		switch ($rowDetalle['tipo_movimiento']) {
			case 1 : 
				$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] + $rowDetalle['costo_diferencia'] - $rowDetalle['subtotal_descuento']; 
				$precioUnitario = $costoUnitario; break;
			case 2 : 
				switch($rowDetalle['tipo_documento_movimiento']) {
					case 1 : 
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
						break;
					case 2 : 
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
				}
				break;
			case 3 :
				$costoUnitario = $rowDetalle['costo'];
				$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
			case 4 : 
				switch($rowDetalle['tipo_documento_movimiento']) {
					case 1 : 
						$costoUnitario = $rowDetalle['costo'];
						$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
						break;
					case 2 : 
						$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] + $rowDetalle['costo_diferencia'] - $rowDetalle['subtotal_descuento']; 
						$precioUnitario = $costoUnitario;
						break;
				}
				break;
		}
		
		if ($rowDetalle['estado'] == 0) {
			$totalEntrada += $rowDetalle['cantidad'];
			$totalValorEntradaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
			$entradaSalida += $rowDetalle['cantidad'];
		} else if ($rowDetalle['estado'] == 1) {
			$totalSalida += $rowDetalle['cantidad'];
			$totalValorSalidaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
			$entradaSalida -= $rowDetalle['cantidad'];
		}
		
		$imgInterAlmacen = ($rowDetalle['nombre_tipo_movimiento'] == "E-TRNS.ALN" || $rowDetalle['nombre_tipo_movimiento'] == "S-TRNS.ALN") ? "TRNS.ALN" : " ";
		
		fputs($ar,chr(13).chr(10));
		fputs($ar, str_pad($contFila2, 6, " ", STR_PAD_BOTH)); fputs($ar, " ");
		fputs($ar, str_pad($imgInterAlmacen, 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
		fputs($ar, str_pad(date(spanDateFormat,strtotime($rowDetalle['fecha_movimiento'])), 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
		fputs($ar, str_pad(utf8_encode($rowDetalle['nombre_empresa']), 40, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar, str_pad(utf8_encode($rowDetalle['descripcion_almacen']), 22, " ", STR_PAD_RIGHT)." ".str_pad(utf8_encode(str_replace("-[]", "", $rowDetalle['ubicacion'])), 16, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar, str_pad(utf8_encode($rowDetalle['nombre_tipo_movimiento']), 10, " ", STR_PAD_BOTH)); fputs($ar, " ");
		fputs($ar, str_pad(utf8_encode($rowDetalle['nombre_modulo']), 1, " ", STR_PAD_BOTH)." ".str_pad(utf8_encode($rowDetalle['numero_documento']), 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
		fputs($ar, str_pad(utf8_encode($rowDetalle['ciPCE']), 14, " ", STR_PAD_LEFT)." ".str_pad(utf8_encode($rowDetalle['nombrePCE']), 44, " ", STR_PAD_RIGHT)); fputs($ar, " ");
		fputs($ar, str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT)); fputs($ar, " ");
		fputs($ar, str_pad($rowDetalle['id_articulo_costo'], 10, " ", STR_PAD_LEFT)); fputs($ar, " ");
		fputs($ar, str_pad(number_format($entradaSalida, 2, ".", ","), 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
		fputs($ar, str_pad(number_format($precioUnitario, 2, ".", ","), 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
		fputs($ar, str_pad(number_format($costoUnitario, 2, ".", ","), 18, " ", STR_PAD_LEFT));
	}
	
	fputs($ar,chr(13).chr(10));
	fputs($ar,str_pad("", 280, "-", STR_PAD_LEFT));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar, str_pad("Totales:", 151, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad("", 60, " ", STR_PAD_BOTH)); fputs($ar, " ");
	fputs($ar, str_pad("E #:", 10, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad(number_format($totalEntrada, 2, ".", ","), 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad("E:", 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad(number_format($totalValorEntradaPrecio, 2, ".", ","), 18, " ", STR_PAD_LEFT));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar, str_pad("", 212, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad("S #:", 10, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad(number_format($totalSalida, 2, ".", ","), 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad("S:", 18, " ", STR_PAD_LEFT)); fputs($ar, " ");
	fputs($ar, str_pad(number_format($totalValorSalidaPrecio, 2, ".", ","), 18, " ", STR_PAD_LEFT));
	
	fputs($ar,chr(13).chr(10));
	fputs($ar,chr(13).chr(10));
	fputs($ar,str_pad("", 280, "=", STR_PAD_LEFT));
	fputs($ar,chr(13).chr(10));
}
fclose($ar);

$basefichero = basename("tmp/".$tituloDcto.".txt");

header( "Content-Type: text/plain");
header( "Content-Length: ".filesize("tmp/".$tituloDcto.".txt"));
header( "Content-Disposition: attachment; filename=".str_replace(" ", "_", $basefichero)."");
readfile("tmp/".$tituloDcto.".txt");

if(file_exists("tmp/".$tituloDcto.".txt")) unlink("tmp/".$tituloDcto.".txt");
?>