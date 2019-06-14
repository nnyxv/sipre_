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
$pdf->mostrarFooter = 0;
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
	cxc_fact.numeroSiniestro AS numero_siniestro,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	B.fecha_reconversion as reconversion,
	vw_pg_empleado.nombre_empleado
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cliente.id = cxc_fact.idCliente)
	left JOIN cj_cc_factura_reconversion B on (cxc_fact.idFactura=B.id_factura)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
WHERE cxc_fact.idFactura = %s",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsEncabezado = mysql_num_rows($rsEncabezado);
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

// VERIFICA VALORES DE CONFIGURACION (Formato de Impresión Factura Administrativa)
$queryConfig406 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 406 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig406 = mysql_query($queryConfig406, $conex);
if (!$rsConfig406) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig406 = mysql_num_rows($rsConfig406);
$rowConfig406 = mysql_fetch_assoc($rsConfig406);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

$maxRows = ($rowConfig406['valor'] == 2) ? 16 : 32;

// DETALLES DE LOS CONCEPTOS
$queryDetalle = sprintf("SELECT
	concep.codigo_concepto AS codigo_articulo,
	cxc_fact_det.descripcion AS descripcion_articulo,
	cxc_fact_det.cantidad,
	cxc_fact_det.precio_unitario,
	cxc_fact_det.id_iva,
	cxc_fact_det.iva,
	cxc_fact_det.id_factura_detalle_adm
FROM cj_cc_factura_detalle_adm cxc_fact_det
	INNER JOIN cj_cc_concepto concep ON (cxc_fact_det.id_concepto = concep.id_concepto)
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
		NULL AS codigo_articulo,
		NULL AS descripcion_articulo,
		NULL AS cantidad,
		NULL AS precio_unitario,
		NULL AS id_iva,
		NULL AS iva,
		NULL AS id_factura_detalle_adm");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$arrayDetalle[] = array(
		"codigo_articulo" => elimCaracter($rowDetalle['codigo_articulo'],";"),
		"descripcion_articulo" => $rowDetalle['descripcion_articulo'],
		"cantidad" => $rowDetalle['cantidad'],
		"precio_unitario" => $rowDetalle['precio_unitario']);
}

if ($verCargosEnDetalle == true) {
	while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
		$arrayDetalle[] = array(
			"codigo_articulo" => " ",
			"descripcion_articulo" => $rowGasto['nombre'],
			"cantidad" => 1,
			"precio_unitario" => $rowGasto['monto']);
		
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
		imagestring($img,1,300,$posY,str_pad("FACTURA SERIE - A", 34, " ", STR_PAD_BOTH),$textColor);
		
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
		imagestring($img,1,300,$posY,utf8_decode("VENDEDOR"),$textColor);
		imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);
		
		$posY = 18;
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
		if ($rowConfig406['valor'] == 2) {
			$posY += 9;
			$posY += 9;
		} else {
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 6, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,35,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 44, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,260,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,315,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
			imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		}
	}
	
	if (isset($tieneDetalle)) {
		$verObservacion = true;
		
		$arrayObservacionDcto = str_split($rowEncabezado['observacionFactura'], 50);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice2 => $valor2) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor2)),$textColor);
			}
		}
		$posY -= (9 * count($arrayObservacionDcto));
		$posY += 9;
		imagestring($img,1,260,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotalFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotalFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	} else if (count($arrayDetalle) > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,$arrayDetalle[$indice]['codigo_articulo'],$textColor);
		if ($rowConfig406['valor'] == 2) {
			$arrayDescripcionArticulo = str_split($arrayDetalle[$indice]['descripcion_articulo'], 38);
			if (isset($arrayDescripcionArticulo)) {
				foreach ($arrayDescripcionArticulo as $indice2 => $valor2) {
					if ($indice2 > 0) {
						$contFilaY++;
						$posY += 9;
					}
					imagestring($img,1,65,$posY,strtoupper(trim($valor2)),$textColor);
				}
			}
		} else {
			$arrayDescripcionArticulo = str_split($arrayDetalle[$indice]['descripcion_articulo'], 44);
			if (isset($arrayDescripcionArticulo)) {
				foreach ($arrayDescripcionArticulo as $indice2 => $valor2) {
					if ($indice2 > 0) {
						$contFilaY++;
						$posY += 9;
					}
					imagestring($img,1,35,$posY,strtoupper(trim($valor2)),$textColor);
				}
			}
		}
		imagestring($img,1,260,$posY,str_pad(number_format($arrayDetalle[$indice]['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($arrayDetalle[$indice]['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($arrayDetalle[$indice]['cantidad'] * $arrayDetalle[$indice]['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	}
	
	if (fmod($contFilaY, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = ($rowConfig406['valor'] == 2) ? 310 : 420;
			if ($totalRowsConfig4 > 0) {
				$arrayValor = str_split(str_replace("\n"," ",$rowConfig4['valor']), (($rowConfig406['valor'] == 2) ? 64 : 94));
				if (isset($arrayValor)) {
					foreach ($arrayValor as $indice2 => $valor2) {
						$posY += 8;
						imagestring($img,1,0,$posY,strtoupper(trim($valor2)),$textColor);
					}
				}
			} else if ($maxRows > $contFilaY + 1 && !$verObservacion) {
				$verObservacion = true;
				
				$arrayObservacionDcto = str_split(str_replace("\n"," ",$rowEncabezado['observacionFactura']), (($rowConfig406['valor'] == 2) ? 64 : 94));
				if (isset($arrayObservacionDcto)) {
					foreach ($arrayObservacionDcto as $indice2 => $valor2) {
						$posY += 8;
						imagestring($img,1,0,$posY,strtoupper(trim($valor2)),$textColor);
					}
				}
			}
			
			$posY = ($rowConfig406['valor'] == 2) ? 350 : 460;
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
			
			
			$arrayObservacionDcto = (isset($verObservacion)) ? "" : str_split(strtoupper($rowEncabezado['observacionFactura']), 50);
			if (count($arrayObservacionDcto) > 0 || strlen($rowEncabezado['numero_siniestro']) > 0) {
				if (count($arrayObservacionDcto) > 0) {
					if (isset($arrayObservacionDcto)) {
						foreach ($arrayObservacionDcto as $indice2 => $valor2) {
							$posY += 9;
							imagestring($img,1,0,$posY,strtoupper(trim($valor2)),$textColor);
						}
					}
				}
				if (strlen($rowEncabezado['numero_siniestro']) > 0) {
					$posY += 9;
					imagestring($img,1,0,$posY,utf8_decode("NRO. SINIESTRO"),$textColor);
					imagestring($img,1,70,$posY,": ".$rowEncabezado['numero_siniestro'],$textColor);
				}
			}
			
			if ($rowConfig406['valor'] == 2) {
				$posY = 310;
				
				$posY += 9;
				$subtotalFactura = $rowEncabezado['subtotalFactura'] + $totalGastoDetalle;
				imagestring($img,1,330,$posY,str_pad("SUBTOTAL", 12, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,400,$posY,str_pad(number_format($subtotalFactura, 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
				
				$porcDescuento = ($subtotalFactura > 0) ? ($rowEncabezado['descuentoFactura'] * 100) / $subtotalFactura : 0;
				$subtotalDescuento = $rowEncabezado['descuentoFactura'];
				
				if ($subtotalDescuento > 0) {
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad("DESCUENTO", 12, " ", STR_PAD_RIGHT).":",$textColor);
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 12, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,400,$posY,str_pad(number_format($subtotalDescuento, 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
				}
				
				if ($totalGastosConIva != 0) {
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad("CARGOS C/IMPTO", 12, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,400,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
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
					imagestring($img,1,330,$posY,str_pad(substr("BASE IMPONIBLE",0,12), 12, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,400,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
					
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad(substr($rowIvaFact['observacion'],0,12), 12, " ", STR_PAD_RIGHT),$textColor);
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 12, " ", STR_PAD_LEFT).":",$textColor);
					imagestring($img,1,405,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
					
					$totalIva += $rowIvaFact['subtotal_iva'];
				}
				
				if ($totalGastosSinIva != 0) {
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad("CARGOS S/IMPTO", 12, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,406,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
				}
				
				if ($rowEncabezado['montoExento'] > 0) {
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad("EXENTO", 12, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,406,$posY,str_pad(number_format($rowEncabezado['montoExento'], 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
				}
				
				if ($rowEncabezado['montoExonerado'] > 0) {
					$posY += 9;
					imagestring($img,1,330,$posY,str_pad("EXONERADO", 12, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,406,$posY,str_pad(number_format($rowEncabezado['montoExonerado'], 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
				}
				
				/*$posY += 8;
				imagestring($img,1,330,$posY,str_pad("", 28, "-", STR_PAD_RIGHT),$textColor);*/
				
				$posY = 404;
				if ($rowEncabezado['reconversion']==null) {

					if ($rowEncabezado['fechaRegistroFactura']>="2018-08-01" and $rowEncabezado['fechaRegistroFactura']<"2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']/100000), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else if ($rowEncabezado['fechaRegistroFactura']>="2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else{
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);

					}
					
				}else{

					if ($rowEncabezado['fechaRegistroFactura']>="2018-08-01" and $rowEncabezado['fechaRegistroFactura']<"2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else if ($rowEncabezado['fechaRegistroFactura']>="2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else{
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);

					}

				}
			} else {
				$posY = 460;
				
				$posY += 9;
				$subtotalFactura = $rowEncabezado['subtotalFactura'] + $totalGastoDetalle;
				imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($subtotalFactura, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				
				$porcDescuento = ($subtotalFactura > 0) ? ($rowEncabezado['descuentoFactura'] * 100) / $subtotalFactura : 0;
				$subtotalDescuento = $rowEncabezado['descuentoFactura'];
				
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
					imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					
					$posY += 9;
					imagestring($img,1,255,$posY,str_pad(substr($rowIvaFact['observacion'],0,16), 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,350,$posY,str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					
					$totalIva += $rowIvaFact['subtotal_iva'];
				}
				
				if ($totalGastosSinIva != 0) {
					$posY += 9;
					imagestring($img,1,255,$posY,str_pad("CARGOS S/IMPTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}
				
				if ($rowEncabezado['montoExento'] > 0) {
					$posY += 9;
					imagestring($img,1,255,$posY,str_pad("EXENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['montoExento'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}
				
				if ($rowEncabezado['montoExonerado'] > 0) {
					$posY += 9;
					imagestring($img,1,255,$posY,str_pad("EXONERADO", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY,str_pad(number_format($rowEncabezado['montoExonerado'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}
				
				$posY += 8;
				imagestring($img,1,255,$posY,"-------------------------------------------",$textColor);
				
				if ($rowEncabezado['reconversion']==null) {

					if ($rowEncabezado['fechaRegistroFactura']>="2018-08-01" and $rowEncabezado['fechaRegistroFactura']<"2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']/100000), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else if ($rowEncabezado['fechaRegistroFactura']>="2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else{
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);

					}
					
				}else{

					if ($rowEncabezado['fechaRegistroFactura']>="2018-08-01" and $rowEncabezado['fechaRegistroFactura']<"2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else if ($rowEncabezado['fechaRegistroFactura']>="2018-08-20") {
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format(($rowEncabezado['montoTotalFactura']), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
						$posY += 10;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					}else{
						$posY += 8;
						imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
						imagestring($img,2,360,$posY,str_pad(number_format($rowEncabezado['montoTotalFactura']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);

					}

				}
			}
		}
		
		$contFilaY = 0;
		
		$pageNum++;
		$arrayImg[] = "tmp/"."factura_venta_adm".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
}

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Administracion)
$queryConfig407 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 407 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig407 = mysql_query($queryConfig407, $conex);
if (!$rsConfig407) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig407 = mysql_num_rows($rsConfig407);
$rowConfig407 = mysql_fetch_assoc($rsConfig407);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


$pdf->nombreRegistrado = $rowEncabezado['nombre_empleado'];
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
		
		$pdf->Image($valor, 15, $rowConfig407['valor'], 580, 688);
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
