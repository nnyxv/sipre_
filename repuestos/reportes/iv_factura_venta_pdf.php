<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
//$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 32;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];
$verCargosEnDetalle = true;

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT
	cxc_fact.*,
	ped_vent.id_pedido_venta_propio,
	ped_vent.id_moneda AS id_moneda,
	moneda.descripcion AS descripcion,
	moneda.abreviacion AS abreviacion,
	presup_vent.numero_siniestro,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	B.fecha_reconversion as reconversion,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cliente.id = cxc_fact.idCliente)
        left JOIN cj_cc_factura_reconversion B on (cxc_fact.idFactura=B.id_factura)	
        INNER JOIN pg_empleado empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
	LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta)
	LEFT JOIN pg_monedas moneda ON (ped_vent.id_moneda = moneda.idmoneda)
	LEFT JOIN iv_presupuesto_venta presup_vent ON (ped_vent.id_presupuesto_venta = presup_vent.id_presupuesto_venta)
WHERE cxc_fact.idFactura = %s",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// VERIFICA VALORES DE CONFIGURACION (Mostrar Numero Control en Impresión de Factura)
$queryConfig11 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 11 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig11 = mysql_query($queryConfig11, $conex);
if (!$rsConfig11) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig11 = mysql_num_rows($rsConfig11);
$rowConfig11 = mysql_fetch_assoc($rsConfig11);

// VERIFICA VALORES DE CONFIGURACION (Pie Página de Factura de Repuesto)
$queryConfig4 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 4 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig4 = mysql_query($queryConfig4, $conex);
if (!$rsConfig4) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig4 = mysql_num_rows($rsConfig4);
$rowConfig4 = mysql_fetch_assoc($rsConfig4);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT
	art.id_articulo,
	art.id_modo_compra,
	art.codigo_articulo,
	tipo_art.descripcion AS descripcion_tipo,
	art.descripcion AS descripcion_articulo,
	subseccion.id_subseccion,
	seccion.descripcion AS descripcion_seccion,
	cxc_fact_det.id_articulo_almacen_costo,
	cxc_fact_det.id_articulo_costo,
	cxc_fact_det.cantidad,
	cxc_fact_det.precio_unitario,
	cxc_fact_det.precio_sugerido,
	cxc_fact_det.id_iva,
	cxc_fact_det.iva,
	cxc_fact_det.id_articulo,
	cxc_fact_det.id_factura_detalle
FROM iv_articulos art
	INNER JOIN iv_subsecciones subseccion ON (art.id_subseccion = subseccion.id_subseccion)
	INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
	INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
	INNER JOIN cj_cc_factura_detalle cxc_fact_det ON (art.id_articulo = cxc_fact_det.id_articulo)
WHERE cxc_fact_det.id_factura = %s",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);

$queryGasto = sprintf("SELECT
	cxc_fact_gasto.id_factura_gasto,
	cxc_fact_gasto.id_factura,
	cxc_fact_gasto.tipo,
	cxc_fact_gasto.porcentaje_monto,
	cxc_fact_gasto.monto,
	cxc_fact_gasto.estatus_iva,
	cxc_fact_gasto.id_iva,
	cxc_fact_gasto.iva,
	gasto.*
FROM pg_gastos gasto
	INNER JOIN cj_cc_factura_gasto cxc_fact_gasto ON (gasto.id_gasto = cxc_fact_gasto.id_gasto)
WHERE id_factura = %s;",
	valTpDato($idDocumento, "int"));
$rsGasto = mysql_query($queryGasto, $conex);
if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsGasto = mysql_num_rows($rsGasto);

if (($totalRowsDetalle + $totalRowsGasto) == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_articulo,
		NULL AS id_modo_compra,
		NULL AS codigo_articulo,
		NULL AS descripcion_articulo,
		NULL AS descripcion_tipo,
		NULL AS id_subseccion,
		NULL AS descripcion_seccion,
		NULL AS id_articulo_almacen_costo,
		NULL AS id_articulo_costo,
		NULL AS cantidad,
		NULL AS precio_unitario,
		NULL AS precio_sugerido,
		NULL AS id_iva,
		NULL AS iva,
		NULL AS id_articulo,
		NULL AS id_factura_detalle");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$arrayDetalle[] = array(
		"codigo_articulo" => elimCaracter($rowDetalle['codigo_articulo'],";"),
		"descripcion_articulo" => $rowDetalle['descripcion_articulo'],
		"cantidad" => $rowDetalle['cantidad'],
		"precio_unitario" => $rowDetalle['precio_unitario'],
		"precio_sugerido" => $rowDetalle['precio_sugerido'],
		"id_modo_compra" => $rowDetalle['id_modo_compra'],
		"id_articulo_costo" => $rowDetalle['id_articulo_costo']);
}

if ($verCargosEnDetalle == true) {
	while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
		$arrayDetalle[] = array(
			"codigo_articulo" => " ",
			"descripcion_articulo" => ""/*$rowGasto['nombre']*/,
			"cantidad" => ""/*1*/,
			"precio_unitario" => ""/*$rowGasto['monto']*/,
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
		imagestring($img,1,300,$posY,str_pad("FACTURA SERIE - R", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		if (ceil($totalRowsDetalle / $maxRows) > 1) {
			imagestring($img,1,300,$posY,str_pad(utf8_decode("PÁGINA ".($pageNum + 1)."/".ceil($totalRowsDetalle / $maxRows)), 34, " ", STR_PAD_BOTH),$textColor);
		}
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. FACTURA"),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeroFactura'],$textColor);
		
		if ($rowConfig11['valor'] == 1) {
			$posY += 9;
			imagestring($img,1,300,$posY,utf8_decode("NRO. CONTROL"),$textColor);
			imagestring($img,1,375,$posY,": ".$rowEncabezado['numeroControl'],$textColor);
		}
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaRegistroFactura'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaVencimientoFactura'])),$textColor);
		
		$posY += 9;
		if ($rowEncabezado['condicionDePago'] == 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($rowEncabezado['diasDeCredito'])." DIAS",$textColor);
		}
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. PEDIDO"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowEncabezado['id_pedido_venta_propio'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("VENDEDOR"),$textColor);
		imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);
		
		$posY = 28;
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,60)),$textColor);
		
		if (in_array($rowConfig409['valor'],array("","1"))) {
			$posY += 9;
			imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
		}
		
		$direccionCliente = strtoupper(elimCaracter($rowEncabezado['direccion_cliente'],";"));
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
		imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
		imagestring($img,1,195,$posY,": ".$rowEncabezado['telf'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
		imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);
		
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,260,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	if (isset($tieneDetalle)) {
		$verObservacion = true;
		
		$arrayObservacionDcto = str_split($rowEncabezado['observacionFactura'], 50);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
			}
		}
		$posY -= (9 * count($arrayObservacionDcto));
		$posY += 9;
		imagestring($img,1,260,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotalFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotalFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	} else {
		if ($arrayDetalle[$indice]['cantidad']!=0 && $arrayDetalle[$indice]['precio_unitario']!=0) {
			$posY += 9;
		imagestring($img,1,0,$posY,$arrayDetalle[$indice]['codigo_articulo'],$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($arrayDetalle[$indice]['descripcion_articulo'],0,28)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($arrayDetalle[$indice]['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($arrayDetalle[$indice]['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($arrayDetalle[$indice]['cantidad'] * $arrayDetalle[$indice]['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		}
		
		
		$lineaAdicional = false;
		/*if (!in_array($_SESSION['idMetodoCosto'], array(1,2))) {
			$contFilaY++;
			$posY += 9;
			imagestring($img,1,115,$posY,strtoupper(substr("LOTE: ".$arrayDetalle[$indice]['id_articulo_costo'],0,14)),$textColor);
			$lineaAdicional = true;
		}*/
		
		if ($arrayDetalle[$indice]['id_modo_compra'] == 2 && in_array(idArrayPais,array(1))) { // 1 = Nacional, 2 = Importacion
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
			$rsArtPrecio = mysql_query($queryArtPrecio, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
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
		} else if (in_array(idArrayPais,array(3)) && $arrayDetalle[$indice]['precio_sugerido'] > 0) { // PUERTO RICO
			$contFilaY++;
			$posY += 9;
			(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
			imagestring($img,1,10,$posY,strtoupper(substr("PRECIO LISTA: ",0,22)),$textColor);
			imagestring($img,1,115,$posY,str_pad($rowEncabezado['abreviacion'].number_format($arrayDetalle[$indice]['precio_sugerido'], 2, ".", ","), 28, " ", STR_PAD_LEFT),$textColor);
			$lineaAdicional = true;
		}
	}
	
	if (fmod($contFilaY, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 410;
			if ($totalRowsConfig4 > 0) {
				$arrayColetilla = wordwrap(str_replace("\n","<br>",$rowConfig4['valor']), 94, "<br>");
				$arrayValor = explode("<br>",$arrayColetilla);
				if (isset($arrayValor)) {
					foreach ($arrayValor as $indice => $valor) {
						$posY += 7;
						imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
					}
				}
			} else if ($maxRows > $contFilaY + 1 && !$verObservacion) {
				$verObservacion = true;
				
				$arrayObservacionDcto = wordwrap(str_replace("\n","<br>",str_replace(";", "", $rowEncabezado['observacionFactura'])), 94, "<br>");
				$arrayValor = explode("<br>",$arrayObservacionDcto);
				if (isset($arrayValor)) {
					foreach ($arrayValor as $indice => $valor) {
						$posY += 8;
						imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
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
			
			
			$arrayObservacionDcto = wordwrap(str_replace("\n","<br>",str_replace(";", "", ((isset($verObservacion)) ? "" : $rowEncabezado['observacionFactura']))), 47, "<br>");
			$arrayValor = explode("<br>",$arrayObservacionDcto);
			if (isset($arrayValor)) {
				foreach ($arrayValor as $indice => $valor) {
					$posY += 8;
					imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
				}
			}
			
			if (strlen($rowEncabezado['numero_siniestro']) > 0) {
				$posY += 9;
				imagestring($img,1,0,$posY,utf8_decode("NRO. SINIESTRO"),$textColor);
				imagestring($img,1,70,$posY,": ".$rowEncabezado['numero_siniestro'],$textColor);
			}
			
			$posY = 450;

			$posY += 9;
			$subtotalFactura = $rowEncabezado['subtotalFactura']/* + $totalGastoDetalle*/;
			imagestring($img,1,240,$posY,
				str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":".
				str_pad("", 6, " ", STR_PAD_LEFT).
				str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
				str_pad(number_format($subtotalFactura, 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);

/*----------------------------------------------------------------------------------------------------*/
$queryGasto = sprintf("SELECT
	cxc_fact_gasto.id_factura_gasto,
	cxc_fact_gasto.id_factura,
	cxc_fact_gasto.tipo,
	cxc_fact_gasto.porcentaje_monto,
	cxc_fact_gasto.monto,
	cxc_fact_gasto.estatus_iva,
	cxc_fact_gasto.id_iva,
	cxc_fact_gasto.iva,
	gasto.*
FROM pg_gastos gasto
	INNER JOIN cj_cc_factura_gasto cxc_fact_gasto ON (gasto.id_gasto = cxc_fact_gasto.id_gasto)
WHERE id_factura = %s;",
	valTpDato($idDocumento, "int"));
$rsGasto = mysql_query($queryGasto, $conex);
if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
	$posY += 9;
	imagestring($img,1,240,$posY,
	str_pad($rowGasto['nombre'], 16, " ", STR_PAD_RIGHT).":".
	str_pad(number_format($rowGasto['porcentaje_monto'], 2, ".", ","). "%", 8, " ", STR_PAD_LEFT).
	str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
	str_pad(number_format($rowGasto['monto'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}
/*----------------------------------------------------------------------------------------------------*/
			
			$porcDescuento = ($subtotalFactura > 0) ? ($rowEncabezado['descuentoFactura'] * 100) / $subtotalFactura : 0;
			$subtotalDescuento = $rowEncabezado['descuentoFactura'];
			if ($subtotalDescuento > 0) {
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":".
					str_pad(number_format($porcDescuento, 2, ".", ",")."%", 6, " ", STR_PAD_LEFT).
					str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($subtotalDescuento, 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($totalGastosConIva != 0) {
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("CARGOS C/IMPTO", 16, " ", STR_PAD_RIGHT).":".
					str_pad("", 8, " ", STR_PAD_LEFT).
					str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($totalGastosConIva, 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaFact = sprintf("SELECT
				iva.observacion,
				cxc_fact_iva.base_imponible,
				cxc_fact_iva.iva,
				cxc_fact_iva.subtotal_iva
			FROM cj_cc_factura_iva cxc_fact_iva
				INNER JOIN pg_iva iva ON (cxc_fact_iva.id_iva = iva.idIva)
			WHERE id_factura = %s;",
				valTpDato($idDocumento, "int"));
			$rsIvaFact = mysql_query($queryIvaFact, $conex);
			if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":".
					str_pad("", 6, " ", STR_PAD_LEFT).
					str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad(substr(strtoupper(utf8_decode($rowIvaFact['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
					str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT).
					str_pad($rowIvaFact['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
					str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaFact['subtotal_iva'];
			}
			
			if ($totalGastosSinIva != 0) {
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("CARGOS S/IMPTO", 16, " ", STR_PAD_RIGHT).":".
					str_pad("", 6, " ", STR_PAD_LEFT).
					str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($totalGastosSinIva, 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($rowEncabezado['montoExento'] > 0) {
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("EXENTO", 16, " ", STR_PAD_RIGHT).":".
					str_pad("", 6, " ", STR_PAD_LEFT).
					str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($rowEncabezado['montoExento'], 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($rowEncabezado['montoExonerado'] > 0) {
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("EXONERADO", 16, " ", STR_PAD_RIGHT).":".
					str_pad("", 6, " ", STR_PAD_LEFT).
					str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($rowEncabezado['montoExonerado'], 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 8;
			imagestring($img,1,240,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
			
			
			$fechaRegistroFactura = date_create_from_format('Y-m-d',$rowEncabezado['fechaRegistroFactura']);
			$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
			$fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');

			if ($rowEncabezado['reconversion']==null) {
				if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
					$posY += 8;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
					$posY += 11;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,

						str_pad(number_format(($rowEncabezado['montoTotalFactura']/100000), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaRegistroFactura>=$fechaAjusten) {

					$posY += 8;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
					$posY += 11;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}else{
					$posY += 8;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}
			}else{

				if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
					$posY += 8;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
					$posY += 11;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,

						str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaRegistroFactura>=$fechaAjusten) {

					$posY += 8;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
					$posY += 11;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}else{
					$posY += 8;
					imagestring($img,1,240,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,350,$posY,
					str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				}
			}
		}
		
		$contFilaY = 0;
		
		$pageNum++;
		$arrayImg[] = "tmp/"."factura_venta_repuestos".$pageNum.".png";
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

