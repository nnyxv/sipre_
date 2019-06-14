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
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 22;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$query = sprintf("SELECT
	ped_comp.id_pedido_compra,
	ped_comp.id_pedido_compra_propio,
	ped_comp.id_pedido_compra_referencia,
	ped_comp.id_empresa,
	ped_comp.fecha,
	ped_comp.id_moneda_tasa_cambio,
	ped_comp.monto_tasa_cambio,
	ped_comp.estatus_pedido_compra,
	ped_comp.monto_exento,
	ped_comp.subtotal,
	ped_comp.porcentaje_descuento,
	ped_comp.subtotal_descuento,
	prov.id_proveedor,
	prov.nombre,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	tipo_ped_comp.id_tipo_pedido_compra,
	tipo_ped_comp.tipo_pedido_compra,
	moneda_local.idmoneda AS id_moneda_local,
	moneda_extranjera.idmoneda AS id_moneda_extranjera,
	
	IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
	
	IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
	
	vw_pg_empleado.id_empleado,
	vw_pg_empleado.nombre_empleado
FROM iv_pedido_compra ped_comp
	INNER JOIN cp_proveedor prov ON (ped_comp.id_proveedor = prov.id_proveedor)
	INNER JOIN iv_tipo_pedido_compra tipo_ped_comp ON (ped_comp.id_tipo_pedido_compra = tipo_ped_comp.id_tipo_pedido_compra)
	INNER JOIN pg_monedas moneda_local ON (ped_comp.id_moneda = moneda_local.idmoneda)
	LEFT JOIN pg_monedas moneda_extranjera ON (ped_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (ped_comp.id_empleado_preparador = vw_pg_empleado.id_empleado)
WHERE id_pedido_compra = %s",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rs);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DEL PROVEEDOR
$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = %s",
	valTpDato($rowEncabezado['id_proveedor'], "int"));
$rsProveedor = mysql_query($queryProveedor);
if (!$rsProveedor) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowProveedor = mysql_fetch_assoc($rsProveedor);

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT 
	ped_comp_det.*,
	vw_iv_art_datos_bas.codigo_articulo,
	vw_iv_art_datos_bas.descripcion,
	vw_iv_art_datos_bas.codigo_articulo_prov,
	
	(SELECT SUM(ped_comp_det_impsto.impuesto) FROM iv_pedido_compra_detalle_impuesto ped_comp_det_impsto
	WHERE ped_comp_det_impsto.id_pedido_compra_detalle = ped_comp_det.id_pedido_compra_detalle) AS porc_iva
FROM iv_pedido_compra_detalle ped_comp_det
	INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (ped_comp_det.id_articulo = vw_iv_art_datos_bas.id_articulo)
WHERE id_pedido_compra = %s
ORDER BY id_pedido_compra_detalle",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(530, 630) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$backgroundGris = imagecolorallocate($img, 230, 230, 230);
		$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
		
		$posY = 0;
		imagestring($img,1,350,$posY,str_pad("PEDIDO DE COMPRA", 36, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,350,$posY,"ID PED. COMPRA",$textColor);
		imagestring($img,1,430,$posY,": ".$rowEncabezado['id_pedido_compra'],$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,"FECHA",$textColor);
		imagestring($img,1,430,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"EMPLEADO",$textColor);
		imagestring($img,1,45,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);
		imagestring($img,1,350,$posY,"TIPO PEDIDO",$textColor);
		imagestring($img,1,430,$posY,": ".strtoupper($rowEncabezado['tipo_pedido_compra']),$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,"Nro. PED. PROPIO",$textColor);
		imagestring($img,1,430,$posY,": ".$rowEncabezado['id_pedido_compra_propio'],$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,"MONEDA",$textColor);
		imagestring($img,1,430,$posY,": ".strtoupper($rowEncabezado['descripcion_moneda']." (".$rowEncabezado['abreviacion_moneda'].")"),$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,"Nro. REFERENCIA",$textColor);////////////////////////////////////////////////////
		imagestring($img,1,430,$posY,": ".$rowEncabezado['id_pedido_compra_referencia'],$textColor);
		
		$posY += 9;
		imagefilledrectangle($img, 0, $posY, 529, $posY+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad("DATOS DEL PROVEEDOR", 106, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"RAZON SOCIAL",$textColor);
		imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre']),$textColor);
		imagestring($img,1,370,$posY,$spanProvCxP,$textColor);
		imagestring($img,1,410,$posY,": ".$rowEncabezado['rif_proveedor'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"CONTACTO",$textColor);
		imagestring($img,1,45,$posY,": ".strtoupper($rowProveedor['contacto']),$textColor);
		imagestring($img,1,210,$posY,"CARGO",$textColor);
		imagestring($img,1,235,$posY,": ".$rowProveedor[''],$textColor);
		imagestring($img,1,370,$posY,"EMAIL",$textColor);
		imagestring($img,1,410,$posY,": ".$rowProveedor['correococtacto'],$textColor);
		
		$direccionProveedor = strtoupper(str_replace(",", " ", $rowProveedor['direccion']));
		$posY += 9;
		imagestring($img,1,0,$posY,"DIRECCIÓN",$textColor);
		imagestring($img,1,45,$posY,": ".trim(substr($direccionProveedor,0,60)),$textColor);
		imagestring($img,1,370,$posY,"TELÉFONO",$textColor);
		imagestring($img,1,410,$posY,": ".$rowProveedor['telefono'],$textColor);
		
		$posY += 9;
		imagestring($img,1,60,$posY,trim(substr($direccionProveedor,60,60)),$textColor);
		imagestring($img,1,370,$posY,"FAX",$textColor);
		imagestring($img,1,410,$posY,": ".$rowProveedor['fax'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 106, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 529, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad("CODIGO", 18, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,95,$posY,str_pad("DESCRIPCIÓN", 29, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,245,$posY,str_pad("PED.", 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,280,$posY,str_pad("RECIB.", 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,str_pad("PEND.", 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,350,$posY,strtoupper(str_pad($spanPrecioUnitario, 13, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,420,$posY,str_pad("%IMPTO", 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,455,$posY,str_pad("TOTAL", 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 106, "-", STR_PAD_BOTH),$textColor);
	}
	
	$cantPedida = $rowDetalle['cantidad'];
	$cantRecibida = $rowDetalle['cantidad'] - $rowDetalle['pendiente'];
	$cantPendiente = $rowDetalle['pendiente'];
	$precioUnitario = $rowDetalle['precio_unitario'];
	$porcIva = ($rowDetalle['porc_iva'] > 0 && $rowDetalle['estatus_iva'] == 1) ? $rowDetalle['porc_iva']."%" : "-";
	$total = $cantPedida * $precioUnitario;
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 528, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
	imagestring($img,1,95,$posY,strtoupper(str_pad(substr($rowDetalle['descripcion'],0,29), 29, " ", STR_PAD_RIGHT)),$textColor);
	imagestring($img,1,245,$posY,str_pad(number_format($cantPedida, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,280,$posY,str_pad(number_format($cantRecibida, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,315,$posY,str_pad(number_format($cantPendiente, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,350,$posY,str_pad(number_format($precioUnitario, 2, ".", ","), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,420,$posY,str_pad($porcIva, 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,455,$posY,str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 528, $posY+9, $backgroundAzul);
	imagestring($img,1,95,$posY,strtoupper(str_pad(substr($rowDetalle['codigo_articulo_prov'],0,29), 29, " ", STR_PAD_RIGHT)),$textColor);
	
	$totalArticulos += $total;
	
	$posY += 1;
	if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$queryGasto = sprintf("SELECT
				ped_comp_gasto.id_pedido_compra_gasto,
				ped_comp_gasto.id_pedido_compra,
				ped_comp_gasto.tipo,
				ped_comp_gasto.porcentaje_monto,
				ped_comp_gasto.monto,
				ped_comp_gasto.estatus_iva,
				ped_comp_gasto.id_iva,
				ped_comp_gasto.iva,
				gasto.*
			FROM pg_gastos gasto
				INNER JOIN iv_pedido_compra_gasto ped_comp_gasto ON (gasto.id_gasto = ped_comp_gasto.id_gasto)
			WHERE id_pedido_compra = %s;",
				valTpDato($idDocumento, "text"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 540;
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcGasto = $rowGasto['porcentaje_monto'];
				$montoGasto = $rowGasto['monto'];
				
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad(strtoupper(substr($rowGasto['nombre'],0,24)), 24, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,130,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,175,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
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
			
			$posY = 540;
			
			$subTotal = $totalArticulos;
			$posY += 9;
			imagestring($img,1,315,$posY,str_pad("SUBTOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,455,$posY,str_pad(number_format($subTotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = $rowEncabezado['porcentaje_descuento'];
			$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
			if ($subtotalDescuento > 0) {
				$posY += 9;
				imagestring($img,1,315,$posY,str_pad("DESCUENTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,410,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($subtotalDescuento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$totalGastosConIva = $totalGastosConIvaOrigen + $totalGastosConIvaLocal;
			if ($totalGastosConIva != 0) {
				$posY += 9;
				imagestring($img,1,315,$posY,str_pad("GASTOS C/IMPTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaFact = sprintf("SELECT
				iva.observacion,
				ped_comp_iva.base_imponible,
				ped_comp_iva.iva,
				ped_comp_iva.subtotal_iva
			FROM iv_pedido_compra_iva ped_comp_iva
				INNER JOIN pg_iva iva ON (ped_comp_iva.id_iva = iva.idIva)
			WHERE id_pedido_compra = %s;",
				valTpDato($rowEncabezado['id_pedido_compra'], "int"));
			$rsIvaFact = mysql_query($queryIvaFact);
			if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
				$posY += 9;
				imagestring($img,1,315,$posY,str_pad("BASE IMPONIBLE", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,315,$posY,str_pad(substr($rowIvaFact['observacion'],0,17), 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,410,$posY,str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaFact['subtotal_iva'];
			}
			
			$totalGastosSinIva = $totalGastosSinIvaOrigen + $totalGastosSinIvaLocal;
			if ($totalGastosSinIva != 0) {
				$posY += 9;
				imagestring($img,1,315,$posY,str_pad("GASTOS S/IMPTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$totalExento = $rowEncabezado['monto_exento'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
			if ($totalExento > 0) {
				$posY += 9;
				imagestring($img,1,315,$posY,str_pad("MONTO EXENTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,455,$posY,str_pad(number_format($totalExento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,315,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $totalArticulos - $subtotalDescuento + $totalIva + $totalGastosConIvaOrigen + $totalGastosConIvaLocal + $totalGastosSinIvaOrigen + $totalGastosSinIvaLocal;
			
//$rowEncabezado['fecha']
			$fechaPedido = date_create_from_format('Y-m-d',$rowEncabezado['fecha']);
			$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
			$fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');
			// echo number_format(($totalFactura/1000), 2, ".", ",");
			
			if ($fechaPedido>=$fechaReconversion and $fechaPedido<$fechaAjusten) {
				$posY += 7;
				imagestring($img,1,315,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,2,440,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				$posY += 10;
				imagestring($img,1,315,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,2,420,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
			}else if ($fechaPedido>=$fechaAjusten) {
				$posY += 7;
				imagestring($img,1,315,$posY,str_pad("TOTAL Bs.S", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,2,420,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				$posY += 10;
				imagestring($img,1,315,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,2,440,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);

			}else{
				$posY += 7;
				imagestring($img,1,315,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,2,440,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."pedido_compra".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
}

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
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
$pdf->mostrarHeader = 1;
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