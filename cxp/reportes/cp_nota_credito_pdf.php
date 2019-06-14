<?php
require_once("../../connections/conex.php");
session_start();
set_time_limit(0);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

// INSERTA LOS DETALLES DE LAS NOTAS DE CREDITO QUE NO TIENEN
$insertSQL = sprintf("INSERT INTO cp_notacredito_detalle_motivo (id_notacredito, id_motivo, precio_unitario)
SELECT 
	cxp_nc.id_notacredito,
	cxp_nc.id_motivo,
	cxp_nc.subtotal_notacredito
FROM cp_notacredito cxp_nc
WHERE cxp_nc.id_notacredito NOT IN (SELECT cxp_nc_det.id_notacredito
								FROM cp_notacredito_detalle_motivo cxp_nc_det)
	AND cxp_nc.id_motivo IS NOT NULL;");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 18;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT 
	cxp_nc.id_notacredito,
	cxp_nc.id_empresa,
	cxp_nc.numero_nota_credito,
	cxp_nc.numero_control_notacredito,
	cxp_nc.fecha_notacredito,
	cxp_nc.fecha_notacredito AS fecha_vencimiento_notacredito,
	cxp_nc.fecha_registro_notacredito,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	prov.direccion AS direccion_proveedor,
	prov.telefono,
	prov.otrotelf,
	prov_cred.diascredito,
	1 AS condicionDePago,
	cxp_nc.id_departamento_notacredito,
	cxp_nc.id_documento,
	cxp_nc.tipo_documento,
	cxp_nc.subtotal_notacredito,
	cxp_nc.subtotal_descuento,
	cxp_nc.monto_exento_notacredito,
	cxp_nc.monto_exonerado_notacredito,
	cxp_nc.observacion_notacredito,
	motivo.id_motivo,
	rec.id_notacredito as reconvercion,
	motivo.descripcion AS descripcion_motivo,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador
FROM cp_notacredito cxp_nc
	INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
	LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	LEFT JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
	LEFT JOIN cp_reconversion rec on (cxp_nc.id_notacredito = rec.id_notacredito)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxp_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
WHERE cxp_nc.id_notacredito = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

$queryClaveMov = sprintf("SELECT
	clave_mov.id_clave_movimiento,
	clave_mov.descripcion,
	(CASE mov.id_tipo_movimiento
		WHEN 1 THEN 'COMPRA'
		WHEN 2 THEN 'ENTRADA'
		WHEN 3 THEN 'VENTA'
		WHEN 4 THEN 'SALIDA'
	END) AS tipo_movimiento
FROM pg_clave_movimiento clave_mov
  INNER JOIN iv_movimiento mov ON (clave_mov.id_clave_movimiento = mov.id_clave_movimiento)
WHERE mov.id_documento = %s
	AND mov.id_tipo_movimiento IN (4)
	AND mov.tipo_documento_movimiento IN (2);",
	valTpDato($idDocumento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

// DETALLES DE LOS MOTIVOS
$queryDetalle = sprintf("SELECT
	cxp_nd_det_motivo.id_notacredito_detalle_motivo,
	cxp_nd_det_motivo.id_notacredito,
	cxp_nd_det_motivo.id_motivo,
	motivo.descripcion,
	
	(CASE motivo.modulo
		WHEN 'CC' THEN	'Cuentas por Cobrar'
		WHEN 'CP' THEN	'Cuentas por Pagar'
		WHEN 'CJ' THEN	'Caja'
		WHEN 'TE' THEN	'Tesorería'
	END) AS descripcion_modulo_transaccion,
	
	(CASE motivo.ingreso_egreso
		WHEN 'I' THEN	'Ingreso'
		WHEN 'E' THEN	'Egreso'
	END) AS descripcion_tipo_transaccion,
	
	cxp_nd_det_motivo.cantidad,
	cxp_nd_det_motivo.precio_unitario
FROM cp_notacredito_detalle_motivo cxp_nd_det_motivo
	INNER JOIN pg_motivo motivo ON (cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
WHERE cxp_nd_det_motivo.id_notacredito = %s;",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
if ($totalRowsDetalle > 0) {
	$tieneMotivo = true;
} else {
	// DETALLES DE LOS REPUESTOS
	if ($rowEncabezado['id_departamento_notacredito'] == 0) {
		$queryDetalle = sprintf("SELECT 
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			cxp_fact_det.cantidad,
			cxp_fact_det.pendiente,
			cxp_fact_det.precio_unitario,
			cxp_fact_det.id_iva,
			cxp_fact_det.iva,
			cxp_fact_det.id_casilla
		FROM cp_factura_detalle cxp_fact_det
			INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
			INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det.id_factura = cxp_nc.id_documento)
		WHERE cxp_nc.id_notacredito = %s
			AND cxp_nc.tipo_documento = 'FA'
		ORDER BY id_factura_detalle ASC",
			valTpDato($idDocumento,"int"));
	} else if ($rowEncabezado['id_departamento_notacredito'] == 3) {
		$queryDetalle = sprintf("SELECT 
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			cxp_fact_det.cantidad,
			cxp_fact_det.pendiente,
			cxp_fact_det.precio_unitario,
			cxp_fact_det.id_iva,
			cxp_fact_det.iva,
			cxp_fact_det.id_casilla
		FROM cp_factura_detalle cxp_fact_det
			INNER JOIN ga_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
			INNER JOIN cp_notacredito cxp_nc ON (cxp_fact_det.id_factura = cxp_nc.id_documento)
		WHERE cxp_nc.id_notacredito = %s
			AND cxp_nc.tipo_documento = 'FA'
		ORDER BY id_factura_detalle ASC",
			valTpDato($idDocumento,"int"));
	}
}
if (strlen($queryDetalle) > 0) {
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

if ($totalRowsDetalle == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_articulo,
		NULL AS codigo_articulo,
		NULL AS descripcion,
		NULL AS cantidad,
		NULL AS pendiente,
		NULL AS precio_unitario,
		NULL AS id_iva,
		NULL AS iva,
		NULL AS id_casilla");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		$posY = 9;
		imagestring($img,1,300,$posY,str_pad(utf8_decode("NOTA DE CRÉDITO"), 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA REGISTRO"),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_registro_notacredito'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. NOTA CRÉD."),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numero_nota_credito'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. CONTROL"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowEncabezado['numero_control_notacredito'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA NC PROV."),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_notacredito'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_vencimiento_notacredito'])),$textColor);
		
		$posY += 9;
		if ($rowEncabezado['condicionDePago'] == 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($rowEncabezado['diascredito'])." DIAS",$textColor);
		}
		
		if (strlen($rowClaveMov['tipo_movimiento']) > 0) {
			$posY += 9;
			imagestring($img,1,300,$posY,utf8_decode("TIPO MOV."),$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($rowClaveMov['tipo_movimiento']),$textColor);
		}
		
		if (strlen($rowClaveMov['descripcion']) > 0) {
			$posY += 9;
			imagestring($img,1,300,$posY,utf8_decode("CLAVE MOV."),$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);
		}
		
		$posY = 28;
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id_proveedor'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper($rowEncabezado['nombre_proveedor']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanProvCxP).": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);
		
		$direccionCliente = strtoupper(str_replace(",", "", $rowEncabezado['direccion_proveedor']));
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
		imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
		imagestring($img,1,195,$posY,": ".$rowEncabezado['telefono'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
		imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);
		
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 1, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,55,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,220,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,290,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 20, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,370,$posY,str_pad(utf8_decode("TOTAL"), 20, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	if (isset($tieneDetalle)) {
		$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacion_notacredito']), 51);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
				if ($indice == 0) {
					imagestring($img,1,255,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotal_notacredito'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,370,$posY,str_pad(number_format($rowEncabezado['subtotal_notacredito'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}
			}
		}
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['id_motivo'].".- ".$rowEncabezado['descripcion_motivo'],0,51)),$textColor);
	} else {
		if ($tieneMotivo == true) {
			$posY += 9;
			imagestring($img,1,0,$posY,elimCaracter($rowDetalle['id_motivo'],";"),$textColor);
			imagestring($img,1,55,$posY,strtoupper(substr($rowDetalle['descripcion'],0,28)),$textColor);
			imagestring($img,1,220,$posY,str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 1, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,290,$posY,str_pad(number_format($rowDetalle['precio_unitario'], 2, ".", ","), 11, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,370,$posY,str_pad(number_format($rowDetalle['cantidad'] * $rowDetalle['precio_unitario'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
		} else {
			if ($rowEncabezado['id_departamento_notacredito'] == 0) {
				$queryArtAlm = sprintf("SELECT
					vw_iv_art_alm.descripcion_almacen,
					vw_iv_art_alm.ubicacion
				FROM vw_iv_articulos_almacen vw_iv_art_alm
					INNER JOIN iv_kardex kardex ON (vw_iv_art_alm.id_casilla = kardex.id_casilla)
				WHERE kardex.id_documento = %s
					AND kardex.id_articulo = %s
					AND kardex.tipo_movimiento IN (4)
					AND kardex.tipo_documento_movimiento IN (2);",
					valTpDato($idDocumento, "int"),
					valTpDato($rowDetalle['id_articulo'], "int"));
			} else if ($rowEncabezado['id_departamento_notacredito'] == 3) {
				$queryArtAlm = sprintf("SELECT
					almacen.descripcion AS descripcion_almacen,
					CONCAT_WS('-', calle.descripcion_calle, estante.descripcion_estante, tramo.descripcion_tramo, casilla.descripcion_casilla) AS ubicacion
				FROM ga_almacenes almacen
					INNER JOIN ga_calles calle ON (almacen.id_almacen = calle.id_almacen)
					INNER JOIN ga_estantes estante ON (calle.id_calle = estante.id_calle)
					INNER JOIN ga_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN ga_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					INNER JOIN ga_kardex kardex ON (casilla.id_casilla = kardex.id_casilla)
				WHERE kardex.id_documento = %s
					AND kardex.id_articulo = %s
					AND kardex.tipo_movimiento IN (4);",
					valTpDato($idDocumento, "int"),
					valTpDato($rowDetalle['id_articulo'], "int"));
			}
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			
			$posY += 8;
			imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
			imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,28)),$textColor);
			imagestring($img,1,255,$posY,str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,315,$posY,str_pad(number_format($rowDetalle['precio_unitario'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($rowDetalle['cantidad'] * $rowDetalle['precio_unitario'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			
			$posY += 8;
			imagestring($img,1,115,$posY,strtoupper(substr($rowArtAlm['descripcion_almacen']." ".str_replace("-[]", "", $rowArtAlm['ubicacion']),0,34)),$textColor);
		}
	}
		
	if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 425;
			if ($totalRowsConfig4 > 0) {
				$valor = $rowConfig4['valor'];
				
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,0,94))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,94,188))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,188,282))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,282,376))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,376,470))),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim(substr($valor,470,564))),$textColor);
			}
			
			$queryGasto = sprintf("SELECT
				cxp_nc_gasto.id_notacredito_gastos,
				cxp_nc_gasto.id_gastos_notacredito AS id_notacredito,
				cxp_nc_gasto.tipo_gasto_notacredito AS tipo,
				cxp_nc_gasto.porcentaje_monto,
				cxp_nc_gasto.monto_gasto_notacredito AS monto,
				cxp_nc_gasto.estatus_iva_notacredito AS estatus_iva,
				cxp_nc_gasto.id_iva_notacredito AS id_iva,
				cxp_nc_gasto.iva_notacredito AS iva,
				gasto.id_gasto,
				IF ((cxp_nc_gasto.id_iva_notacredito > 0), gasto.nombre, CONCAT_WS(' ', gasto.nombre, '(E)')) AS nombre,
				gasto.id_modo_gasto
			FROM pg_gastos gasto
				INNER JOIN cp_notacredito_gastos cxp_nc_gasto ON (gasto.id_gasto = cxp_nc_gasto.id_gastos_notacredito)
			WHERE cxp_nc_gasto.id_notacredito = %s
				AND cxp_nc_gasto.id_modo_gasto IN (1,3);",
				valTpDato($idDocumento, "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 460;
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcGasto = $rowGasto['porcentaje_monto'];
				$montoGasto = $rowGasto['monto'];
				
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(substr($rowGasto['nombre'],0,25)),$textColor);
				imagestring($img,1,130,$posY,":",$textColor);
				imagestring($img,1,140,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,175,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				
				if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
					if ($rowGasto['id_iva'] > 0) {
						$totalGastosConIvaOrigen += $montoGasto;
					} else if ($rowGasto['id_iva'] == 0) {
						$totalGastosSinIvaOrigen += $montoGasto;
					}
				} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
					if ($rowGasto['id_iva'] > 0) {
						$totalGastosConIvaLocal += $montoGasto;
					} else if ($rowGasto['id_iva'] == 0) {
						$totalGastosSinIvaLocal += $montoGasto;
					}
				}
			}
			
			
			$arrayObservacionDcto = (isset($tieneDetalle)) ? "" : str_split(strtoupper($rowEncabezado['observacion_notacredito']), 50);
			if (count($arrayObservacionDcto) > 0 || strlen($rowEncabezado['numero_siniestro']) > 0) {
				if (count($arrayObservacionDcto) > 0) {
					if (isset($arrayObservacionDcto)) {
						foreach ($arrayObservacionDcto as $indice => $valor) {
							$posY += 9;
							imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
						}
					}
				}
				if (strlen($rowEncabezado['numero_siniestro']) > 0) {
					$posY += 9;
					imagestring($img,1,0,$posY,utf8_decode("NRO. SINIESTRO"),$textColor);
					imagestring($img,1,70,$posY,": ".$rowEncabezado['numero_siniestro'],$textColor);
				}
			}
			
			$posY = 460;
			
			$posY += 9;
			imagestring($img,1,255,$posY,"SUB TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,1,370,$posY,str_pad(number_format($rowEncabezado['subtotal_notacredito'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotal_notacredito'] > 0) ? ($rowEncabezado['subtotal_descuento'] * 100) / $rowEncabezado['subtotal_notacredito'] : 0;
			if ($rowEncabezado['subtotal_descuento'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,345,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($rowEncabezado['subtotal_descuento'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}
			
			$totalGastosConIva = $totalGastosConIvaOrigen + $totalGastosConIvaLocal;
			if ($totalGastosConIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaDcto = sprintf("SELECT
				iva.observacion,
				cxp_nc_iva.baseimponible_notacredito AS base_imponible,
				cxp_nc_iva.iva_notacredito AS iva,
				cxp_nc_iva.subtotal_iva_notacredito AS subtotal_iva
			FROM cp_notacredito_iva cxp_nc_iva
				INNER JOIN pg_iva iva ON (cxp_nc_iva.id_iva_notacredito = iva.idIva)
			WHERE cxp_nc_iva.id_notacredito = %s;",
				valTpDato($idDocumento, "text"));
			$rsIvaDcto = mysql_query($queryIvaDcto);
			if (!$rsIvaDcto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaDcto = mysql_fetch_assoc($rsIvaDcto)) {
				$posY += 9;
				imagestring($img,1,255,$posY,"BASE IMPONIBLE",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($rowIvaDcto['base_imponible'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,255,$posY,substr($rowIvaDcto['observacion'],0,14),$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,360,$posY,str_pad(number_format($rowIvaDcto['iva'], 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($rowIvaDcto['subtotal_iva'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaDcto['subtotal_iva'];
			}
			
			$totalGastosSinIva = $totalGastosSinIvaOrigen + $totalGastosSinIvaLocal;
			if ($totalGastosSinIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS S/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor); // <---
			}
			
			$montoExento = $rowEncabezado['monto_exento_notacredito'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
			if ($montoExento > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"EXENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($montoExento, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotal_notacredito'] - $rowEncabezado['subtotal_descuento'] + $totalIva + $totalGastosSinIvaOrigen + $totalGastosConIvaOrigen + $totalGastosSinIvaLocal + $totalGastosConIvaLocal;
			if ($rowEncabezado['reconvercion'] == NULL) {
				if ($rowEncabezado['fecha_registro_notacredito']>= '2018-08-01' and $rowEncabezado['fecha_registro_notacredito'] < '2018-08-20') {
				$posY += 7;
				imagestring($img,1,255,$posY,"TOTAL Bs",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				$posY += 9;
				imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}else if ($rowEncabezado['fecha_registro_notacredito']>= '2018-08-20') {
				$posY += 9;
				imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				$posY += 7;
				imagestring($img,1,255,$posY,"TOTAL Bs",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}else{
				$posY += 7;
				imagestring($img,1,255,$posY,"TOTAL Bs",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}
				# code...
			}else{
				if ($rowEncabezado['fecha_registro_notacredito']>= '2018-08-01' and $rowEncabezado['fecha_registro_notacredito'] < '2018-08-20') {
				$posY += 7;
				imagestring($img,1,255,$posY,"TOTAL Bs",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				$posY += 9;
				imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}else if ($rowEncabezado['fecha_registro_notacredito']>= '2018-08-20') {
				$posY += 9;
				imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,380,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				$posY += 7;
				imagestring($img,1,255,$posY,"TOTAL Bs",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}else{
				$posY += 7;
				imagestring($img,1,255,$posY,"TOTAL bson_decode(bson)",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			}
			}
			

			
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."cxp_nc".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
}

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$pdf->nombreRegistrado = $rowEncabezado['nombre_empleado_creador'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 690);
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}
?>
