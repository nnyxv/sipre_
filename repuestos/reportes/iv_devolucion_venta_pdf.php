<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

// INSERTA LOS DETALLES DE LAS NOTAS DE CREDITO QUE NO TIENEN
$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_motivo (id_nota_credito, id_motivo, precio_unitario)
SELECT 
	cxc_nc.idNotaCredito,
	cxc_nc.id_motivo,
	cxc_nc.subtotalNotaCredito
FROM cj_cc_notacredito cxc_nc
WHERE cxc_nc.idNotaCredito NOT IN (SELECT cxc_nc_det.id_nota_credito
								FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det)
	AND cxc_nc.id_motivo IS NOT NULL;");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 0;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 32;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];
$verCargosEnDetalle = true;

// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
$queryEncabezado = sprintf("SELECT cxc_nc.*,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	vw_pg_empleado.nombre_empleado,
                A.fecha_reconversion as reconversion,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_notacredito cxc_nc ON (cliente.id = cxc_nc.idCliente)
                LEFT JOIN cj_cc_notacredito_reconversion A ON (A.id_notacredito=cxc_nc.idNotaCredito)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxc_nc.id_empleado_vendedor = vw_pg_empleado.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
WHERE cxc_nc.idNotaCredito = %s
	AND cxc_nc.idDepartamentoNotaCredito IN (0);",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsEncabezado = mysql_num_rows($rsEncabezado);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];
$idFactura = $rowEncabezado['idDocumento'];

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

// BUSCA LOS DATOS DE LA FACTURA
$queryFact = sprintf("SELECT cxc_fact.* FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.idFactura = %s;",
	valTpDato($idFactura, "int"));
$rsFact = mysql_query($queryFact, $conex);
if (!$rsFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFact = mysql_num_rows($rsFact);
$rowFact = mysql_fetch_assoc($rsFact);

// DETALLES DE LOS MOTIVOS
$queryDetalle = sprintf("SELECT
	cxc_nc_det_motivo.id_nota_credito_detalle_motivo,
	cxc_nc_det_motivo.id_nota_credito,
	cxc_nc_det_motivo.id_motivo,
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
	
	cxc_nc_det_motivo.cantidad,
	cxc_nc_det_motivo.precio_unitario
FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
	INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
WHERE cxc_nc_det_motivo.id_nota_credito = %s;",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
if ($totalRowsDetalle > 0) {
	$pdf->mostrarFooter = 1;
	$tieneMotivo = true;
} else {
	// DETALLES DE LOS REPUESTOS
	$queryDetalle = sprintf("SELECT
		art.id_articulo,
		art.id_modo_compra,
		art.codigo_articulo,
		tipo_art.descripcion AS descripcion_tipo,
		art.descripcion AS descripcion_articulo,
		subseccion.id_subseccion,
		seccion.descripcion AS descripcion_seccion,
		cxc_nc_det.id_articulo_almacen_costo,
		cxc_nc_det.id_articulo_costo,
		cxc_nc_det.cantidad,
		cxc_nc_det.precio_unitario,
		cxc_nc_det.id_iva,
		cxc_nc_det.iva,
		cxc_nc_det.id_articulo,
		cxc_nc_det.id_nota_credito_detalle
	FROM iv_articulos art
		INNER JOIN iv_subsecciones subseccion ON (art.id_subseccion = subseccion.id_subseccion)
		INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
		INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
		INNER JOIN cj_cc_nota_credito_detalle cxc_nc_det ON (art.id_articulo = cxc_nc_det.id_articulo)
	WHERE cxc_nc_det.id_nota_credito = %s",
		valTpDato($idDocumento, "int"));
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	
	$queryGasto = sprintf("SELECT
		cxc_nc_gasto.id_nota_credito_gasto,
		cxc_nc_gasto.id_nota_credito,
		cxc_nc_gasto.tipo,
		cxc_nc_gasto.porcentaje_monto,
		cxc_nc_gasto.monto,
		cxc_nc_gasto.estatus_iva,
		cxc_nc_gasto.id_iva,
		cxc_nc_gasto.iva,
		gasto.*
	FROM pg_gastos gasto
		INNER JOIN cj_cc_nota_credito_gasto cxc_nc_gasto ON (gasto.id_gasto = cxc_nc_gasto.id_gasto)
	WHERE id_nota_credito = %s;",
		valTpDato($idDocumento, "int"));
	$rsGasto = mysql_query($queryGasto, $conex);
	if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsGasto = mysql_num_rows($rsGasto);
}
if (strlen($queryDetalle) > 0) {
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

if (($totalRowsDetalle + $totalRowsGasto) == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_nota_credito_detalle_motivo,
		NULL AS id_nota_credito,
		NULL AS id_motivo,
		NULL AS descripcion,
		NULL AS descripcion_modulo_transaccion,
		NULL AS descripcion_tipo_transaccion,
		NULL AS cantidad,
		NULL AS precio_unitario");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	if ($tieneMotivo == true) {
		$arrayDetalle[] = array(
			"codigo_articulo" => $rowDetalle['id_motivo'],
			"descripcion_articulo" => $rowDetalle['descripcion'],
			"cantidad" => $rowDetalle['cantidad'],
			"precio_unitario" => $rowDetalle['precio_unitario']);
	} else {
		$arrayDetalle[] = array(
			"codigo_articulo" => elimCaracter($rowDetalle['codigo_articulo'],";"),
			"descripcion_articulo" => $rowDetalle['descripcion_articulo'],
			"cantidad" => $rowDetalle['cantidad'],
			"precio_unitario" => $rowDetalle['precio_unitario'],
			"id_modo_compra" => $rowDetalle['id_modo_compra'],
			"id_articulo_costo" => $rowDetalle['id_articulo_costo']);
	}
}

if ($verCargosEnDetalle == true) {
	while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
		$arrayDetalle[] = array(
			"codigo_articulo" => " ",
			"descripcion_articulo" => $rowGasto['nombre'],
			"cantidad" => 1,
			"precio_unitario" => $rowGasto['monto'],
			"id_modo_compra" => " ",
			"id_articulo_costo" => " ");
		
		$totalGastoDetalle += $rowGasto['monto'];
	}
	
	$totalRowsDetalle += $totalRowsGasto;
}

foreach ($arrayDetalle as $indice => $valor) {
	$contFila++;
	$contFilaY++;
	
	if (fmod($contFilaY, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		$posY = 9;
		imagestring($img,1,300,$posY,str_pad("NOTA DE CRÉDITO SERIE - R", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		if (ceil($totalRowsDetalle / $maxRows) > 1) {
			imagestring($img,1,300,$posY,str_pad(("PÁGINA ".($pageNum + 1)."/".ceil($totalRowsDetalle / $maxRows)), 34, " ", STR_PAD_BOTH),$textColor);
		}
		
		$posY += 9;
		imagestring($img,1,300,$posY,("NOTA CRÉD. NRO."),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeracion_nota_credito'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaNotaCredito'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,("NRO. PEDIDO"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowFact['numeroPedido'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,("VENDEDOR"),$textColor);
		imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);
		
		$posY = 28;
		imagestring($img,1,190,$posY,("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,60)),$textColor);
		
		if (in_array($rowConfig409['valor'],array("","1"))) {
			$posY += 9;
			imagestring($img,1,0,$posY,($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
		}
		
		$direccionCliente = strtoupper(elimCaracter($rowEncabezado['direccion_cliente'],";"));
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
		imagestring($img,1,155,$posY,("TELEFONO"),$textColor);
		imagestring($img,1,195,$posY,": ".$rowEncabezado['telf'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
		imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);
		
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad(("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,260,$posY,str_pad(("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,395,$posY,str_pad(("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	if (isset($tieneDetalle)) {
		$verObservacion = true;
		
		$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacionesNotaCredito']), 50);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
			}
		}
		$posY -= (9 * count($arrayObservacionDcto));
		$posY += 9;
		imagestring($img,1,260,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	} else {
		$posY += 9;
		imagestring($img,1,0,$posY,$arrayDetalle[$indice]['codigo_articulo'],$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($arrayDetalle[$indice]['descripcion_articulo'],0,28)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayDetalle[$indice]['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($arrayDetalle[$indice]['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($arrayDetalle[$indice]['cantidad'] * $arrayDetalle[$indice]['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		
		$lineaAdicional = false;
		/*if (!in_array($_SESSION['idMetodoCosto'], array(1,2))) {
			$contFilaY++;
			$posY += 9;
			imagestring($img,1,115,$posY,strtoupper(substr("LOTE: ".$arrayDetalle[$indice]['id_articulo_costo'],0,14)),$textColor);
			$lineaAdicional = true;
		}*/
		
		if ($arrayDetalle[$indice]['id_modo_compra'] == 2) { // 1 = Nacional, 2 = Importacion
			$queryArtPrecio = sprintf("SELECT
				precio.descripcion_precio,
				art_precio.precio,
				moneda.descripcion AS descripcion_moneda
			FROM iv_articulos_precios art_precio
				INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
				INNER JOIN pg_precios precio ON (art_precio.id_precio = precio.id_precio)
			WHERE art_precio.id_articulo_costo = %s
				AND art_precio.id_precio = 13;",
				valTpDato($arrayDetalle[$indice]['id_articulo_costo'], "int"));
			$rsArtPrecio = mysql_query($queryArtPrecio, $conex);
			if (!$rsArtPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
			$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
			
			if ($totalRowsArtPrecio > 0) {
				if ($lineaAdicional == false) {
					$contFilaY++;
					$posY += 9;
				}
				imagestring($img,1,115,$posY,str_pad(strtoupper($rowArtPrecio['descripcion_precio']).":", 10, " ", STR_PAD_RIGHT),$textColor);
				imagestring($img,1,180,$posY,str_pad(number_format($rowArtPrecio['precio'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
		}
	}
	
	if (fmod($contFilaY, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 410;
			if ($totalRowsConfig4 > 0) {
				$arrayValor = str_split(str_replace("\n"," ",$rowConfig4['valor']), 94);
				if (isset($arrayValor)) {
					foreach ($arrayValor as $indice => $valor) {
						$posY += 8;
						imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
					}
				}
			} else if ($maxRows > $contFilaY + 1 && !$verObservacion) {
				$verObservacion = true;
				
				$arrayObservacionDcto = str_split(str_replace("\n"," ",$rowEncabezado['observacionesNotaCredito']), 94);
				if (isset($arrayObservacionDcto)) {
					foreach ($arrayObservacionDcto as $indice => $valor) {
						$posY += 8;
						imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
					}
				}
			}
			
			$posY = 450;
			if (!($verCargosEnDetalle == true)) {
				while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
					$posY += 9;
					imagestring($img,1,0,$posY,strtoupper($rowGasto['nombre']),$textColor);
					imagestring($img,1,90,$posY,":",$textColor);
					imagestring($img,1,100,$posY,str_pad(number_format($rowGasto['porcentaje_monto'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,145,$posY,str_pad(number_format($rowGasto['monto'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					
					if ($rowGasto['estatus_iva'] == 0) {
						$totalGastosSinIva += $rowGasto['monto'];
					} else if ($rowGasto['estatus_iva'] == 1) {
						$totalGastosConIva += $rowGasto['monto'];
					}
					
					$totalGasto += $rowGasto['monto'];
				}
			}
			
			
			$arrayObservacionDcto = (isset($verObservacion)) ? "" : str_split(strtoupper($rowEncabezado['observacionesNotaCredito']), 50);
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
			
			if ($totalRowsFact > 0) {
				$posY += 9;
				imagestring($img,1,0,$posY,"NOTA DE CREDITO QUE HACE REFERENCIA A",$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,"FACT. NRO ".$rowFact['numeroFactura']." NRO CONTROL ".$rowFact['numeroControl'],$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,"DE FECHA ".date(spanDateFormat, strtotime($rowFact['fechaRegistroFactura'])),$textColor);
			}
			
			$posY = 450;
			
			$posY += 9;
			$subtotalNotaCredito = $rowEncabezado['subtotalNotaCredito'] + $totalGastoDetalle;
			imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,380,$posY,str_pad(number_format($subtotalNotaCredito, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
			
			if ($rowEncabezado['porcentaje_descuento'] == "") {
				$porcDescuento = ($rowEncabezado['subtotal_descuento'] * 100) / $subtotalNotaCredito;
				$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
			} else {
				$porcDescuento = $rowEncabezado['porcentaje_descuento'];
				$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
			}
			
			if ($subtotalDescuento > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,350,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($subtotalDescuento, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($totalGastosConIva != 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("CARGOS C/IMPTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaNotaCred = sprintf("SELECT
				iva.observacion,
				cxc_nc_iva.base_imponible,
				cxc_nc_iva.iva,
				cxc_nc_iva.subtotal_iva
			FROM cj_cc_nota_credito_iva cxc_nc_iva
				INNER JOIN pg_iva iva ON (cxc_nc_iva.id_iva = iva.idIva)
			WHERE id_nota_credito = %s;",
				valTpDato($idDocumento, "int"));
			$rsIvaNotaCred = mysql_query($queryIvaNotaCred, $conex);
			if (!$rsIvaNotaCred) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaNotaCred = mysql_fetch_assoc($rsIvaNotaCred)) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaNotaCred['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad(substr($rowIvaNotaCred['observacion'],0,16), 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,350,$posY,str_pad(number_format($rowIvaNotaCred['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaNotaCred['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaNotaCred['subtotal_iva'];
			}
			
			if ($totalGastosSinIva != 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("CARGOS S/IMPTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor); 
			}
			
			if ($rowEncabezado['montoExentoCredito'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("EXENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['montoExentoCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($rowEncabezado['montoExoneradoCredito'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("EXONERADO", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['montoExoneradoCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 8;
			imagestring($img,1,255,$posY,"-------------------------------------------",$textColor);
			
                                               $fechaNotaCredito = date_create_from_format('Y-m-d',$rowEncabezado['fechaNotaCredito']);
                                               $fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
                                               $fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');

                                               if ($rowEncabezado['reconversion']==null) {
                                                               if ($fechaNotaCredito>=$fechaReconversion and $fechaNotaCredito<$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($rowEncabezado['montoNetoNotaCredito'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($rowEncabezado['montoNetoNotaCredito']/100000), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }else if ($fechaNotaCredito>=$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($rowEncabezado['montoNetoNotaCredito']), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($rowEncabezado['montoNetoNotaCredito']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }else{
                                                                               $posY += 8;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($rowEncabezado['montoNetoNotaCredito'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }
                                               }else{

                                                               if ($fechaNotaCredito>=$fechaReconversion and $fechaNotaCredito<$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($rowEncabezado['montoNetoNotaCredito']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                              $posY += 10;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($rowEncabezado['montoNetoNotaCredito']), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }else if ($fechaNotaCredito>=$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($rowEncabezado['montoNetoNotaCredito']), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($rowEncabezado['montoNetoNotaCredito']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);

                                                               }else{
                                                                               $posY += 8;
                                                                               imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($rowEncabezado['montoNetoNotaCredito']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }
                                               }

                                               
                               }
		$contFilaY = 0;
		
		$pageNum++;
		$arrayImg[] = "tmp/"."devolucion_venta_repuestos".$pageNum.".png";
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


$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = (in_array(idArrayPais,array(3))) ? 1 : 0;
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
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