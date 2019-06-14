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
$maxRows = 30;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$query = sprintf("SELECT
	vw_iv_orden_comp.*,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.direccion,
	prov.telefono,
	prov.fax,
	prov.contacto,
	prov.correococtacto
FROM vw_iv_ordenes_compra vw_iv_orden_comp
	INNER JOIN cp_proveedor prov ON (vw_iv_orden_comp.id_proveedor = prov.id_proveedor)
WHERE id_orden_compra = %s;",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rs);

$idEmpresa = $rowEncabezado['id_empresa'];

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT 
	ped_comp_det.*,
	vw_iv_art_datos_bas.codigo_articulo,
	vw_iv_art_datos_bas.descripcion,
	vw_iv_art_datos_bas.codigo_articulo_prov,
	vw_iv_art_datos_bas.unidad,
	
	(SELECT SUM(ped_comp_det_impsto.impuesto) FROM iv_pedido_compra_detalle_impuesto ped_comp_det_impsto
	WHERE ped_comp_det_impsto.id_pedido_compra_detalle = ped_comp_det.id_pedido_compra_detalle) AS porc_iva
FROM iv_pedido_compra_detalle ped_comp_det
	INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (ped_comp_det.id_articulo = vw_iv_art_datos_bas.id_articulo)
WHERE id_pedido_compra = %s
ORDER BY id_pedido_compra_detalle",
	valTpDato($rowEncabezado['id_pedido_compra'],"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$backgroundGris = imagecolorallocate($img, 230, 230, 230);
		$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
		
		$posY = 0;
		imagestring($img,1,310,$posY,str_pad("ORDEN DE COMPRA", 32, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,310,$posY,"ID ORDEN COMPRA",$textColor);
		imagestring($img,1,390,$posY,": ".$rowEncabezado['id_orden_compra'],$textColor);
		
		$posY += 9;
		imagestring($img,1,310,$posY,"FECHA",$textColor);
		imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_orden'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,310,$posY,"ID PEDIDO",$textColor);
		imagestring($img,1,390,$posY,": ".strtoupper($rowEncabezado['id_pedido_compra']),$textColor);
		
		$posY += 9;
		imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad("DATOS DEL PROVEEDOR", 94, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"RAZON SOCIAL",$textColor);
		imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre']),$textColor);
		imagestring($img,1,310,$posY,$spanProvCxP,$textColor);
		imagestring($img,1,350,$posY,": ".$rowEncabezado['rif_proveedor'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"CONTACTO",$textColor);/////////////////////////////////////////
		imagestring($img,1,60,$posY,": ".$rowEncabezado['contacto'],$textColor);
		imagestring($img,1,310,$posY,"EMAIL",$textColor);///////////////////////////////////////////////////
		imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['correococtacto']),$textColor);
		
		$direccionProveedor = strtoupper(str_replace(",", " ", $rowEncabezado['direccion']));
		$posY += 9;
		imagestring($img,1,0,$posY,"DIRECCIÓN",$textColor);/////////////////////////////////////////
		imagestring($img,1,60,$posY,": ".trim(substr($direccionProveedor,0,44)),$textColor);
		imagestring($img,1,310,$posY,"TELÉFONO",$textColor);///////////////////////////////////////////////////
		imagestring($img,1,350,$posY,": ".$rowEncabezado['telefono'],$textColor);
		
		$posY += 9;
		imagestring($img,1,70,$posY,trim(substr($direccionProveedor,44,44)),$textColor);
		imagestring($img,1,310,$posY,"FAX",$textColor);///////////////////////////////////////////////////
		imagestring($img,1,350,$posY,": ".$rowEncabezado['fax'],$textColor);
		
		$posY += 9;
		$posY += 9;
		imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad("DATOS DE LA COMPRA", 94, " ", STR_PAD_BOTH),$textColor);
		
		$queryContacto = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s",
			valTpDato($rowEncabezado['id_empleado_contacto'],"int"));
		$rsContacto = mysql_query($queryContacto);
		if (!$rsContacto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowContacto = mysql_fetch_assoc($rsContacto);
		
		$queryRecepcion = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s",
			valTpDato($rowEncabezado['id_empleado_recepcion'],"int"));
		$rsRecepcion = mysql_query($queryRecepcion);
		if (!$rsRecepcion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowRecepcion = mysql_fetch_assoc($rsRecepcion);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"CONTACTO",$textColor);
		imagestring($img,1,60,$posY,": ".strtoupper($rowContacto['nombre_empleado']." ".$rowContacto['apellido']),$textColor);
		imagestring($img,1,200,$posY,"CARGO",$textColor);/////////////////////////////////////////
		imagestring($img,1,225,$posY,": ".strtoupper($rowContacto['nombre_cargo']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,"EMAIL",$textColor);
		imagestring($img,1,60,$posY,": ".strtoupper($rowContacto['email']),$textColor);
		
		switch($rowEncabezado['tipo_transporte']) {
			case 1 : $tipoTransporte = "PROPIO"; break;
			case 2 : $tipoTransporte = "TERCEROS"; break;
		}
		
		$posY += 9;
		imagestring($img,1,0,$posY,"RESP. RECEPCIÓN",$textColor);/////////////////////////////////////////
		imagestring($img,1,75,$posY,": ".strtoupper($rowRecepcion['nombre_empleado']." ".$rowRecepcion['apellido']),$textColor);
		imagestring($img,1,200,$posY,"FECHA ENTREGA",$textColor);/////////////////////////////////////////
		imagestring($img,1,265,$posY,": ".date(spanDateFormat,strtotime($rowEncabezado['fecha_entrega'])),$textColor);
		imagestring($img,1,350,$posY,"TRANSPORTE",$textColor);
		imagestring($img,1,400,$posY,": ".$tipoTransporte,$textColor);
		
		// DETALLE DE LA ORDEN
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad("NRO.", 4, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,25,$posY,str_pad("CÓDIGO", 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,140,$posY,str_pad("DESCRIPCIÓN", 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,255,$posY,str_pad("CANT.", 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,290,$posY,strtoupper(str_pad($spanPrecioUnitario, 13, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,360,$posY,str_pad("%IMPTO", 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,395,$posY,str_pad("TOTAL", 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	$cantPedida = $rowDetalle['cantidad'];
	$precioUnitario = $rowDetalle['precio_unitario'];
	$porcIva = ($rowDetalle['porc_iva'] > 0 && $rowDetalle['estatus_iva'] == 1) ? $rowDetalle['porc_iva']."%" : "-";
	$total = $cantPedida * $precioUnitario;
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,str_pad($contFila, 4, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,25,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
	imagestring($img,1,140,$posY,strtoupper(substr($rowDetalle['descripcion'],0,22)),$textColor);
	imagestring($img,1,255,$posY,str_pad(number_format($cantPedida, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,290,$posY,str_pad(number_format($precioUnitario, 2, ".", ","), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,360,$posY,str_pad($porcIva, 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
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
			WHERE id_pedido_compra = %s",
				valTpDato($rowEncabezado['id_pedido_compra'], "text"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 440;
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
			
			$posY = 440;
			
			$subTotal = $totalArticulos;
			$posY += 9;
			imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($subTotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = $rowEncabezado['porcentaje_descuento'];
			$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
			if ($subtotalDescuento > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("DESCUENTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,350,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($subtotalDescuento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$totalGastosConIva = $totalGastosConIvaOrigen + $totalGastosConIvaLocal;
			if ($totalGastosConIva != 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("GASTOS C/IMPTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
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
				imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad(substr($rowIvaFact['observacion'],0,17), 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,350,$posY,str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaFact['subtotal_iva'];
			}
			
			$totalGastosSinIva = $totalGastosSinIvaOrigen + $totalGastosSinIvaLocal;
			if ($totalGastosSinIva != 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("GASTOS S/IMPTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$totalExento = $rowEncabezado['monto_exento'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
			if ($totalExento > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("MONTO EXENTO", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalExento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $totalArticulos - $subtotalDescuento + $totalIva + $totalGastosConIvaOrigen + $totalGastosConIvaLocal + $totalGastosSinIvaOrigen + $totalGastosSinIvaLocal;
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,2,380,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 116, "-", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"SEGUN COTIZACIÓN Nro.",$textColor);
			imagestring($img,1,100,$posY,": ".$rowEncabezado['segun_cotizacion'],$textColor);
			imagestring($img,1,230,$posY,"DE FECHA",$textColor);
			imagestring($img,1,275,$posY,": ".(($rowEncabezado['fecha_cotizacion'] != "") ? date(spanDateFormat,strtotime($rowEncabezado['fecha_cotizacion'])) : "xx-xx-xxxx"),$textColor);
			imagestring($img,1,360,$posY,"TIPO DE PAGO",$textColor);
			imagestring($img,1,420,$posY,": ".(($rowEncabezado['tipo_pago'] == 0) ? "CRÉDITO" : "CONTADO"),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"CONDICIONES DE PAGO",$textColor);
			imagestring($img,1,100,$posY,": ".$rowEncabezado['condiciones_pago'],$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"SON",$textColor);
			imagestring($img,1,100,$posY,": ".$rowEncabezado['monto_letras'],$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"OBSERVACIONES",$textColor);
			imagestring($img,1,100,$posY,":".$rowEncabezado['observaciones'],$textColor);
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."orden_compra".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
}

//////////////////////////////////////// SEGUNDA PAGINA ////////////////////////////////////////

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$backgroundAzul = imagecolorallocate($img, 226, 239, 254);

$posY = 0;
imagestring($img,1,310,$posY,str_pad("ORDEN DE COMPRA", 32, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,310,$posY,"ID ORDEN COMPRA",$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['id_orden_compra'],$textColor);

$posY += 9;
imagestring($img,1,310,$posY,"FECHA",$textColor);
imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_orden'])),$textColor);

$posY += 9;
imagestring($img,1,310,$posY,"ID PEDIDO",$textColor);
imagestring($img,1,390,$posY,": ".strtoupper($rowEncabezado['id_pedido_compra']),$textColor);


// BUSCA LOS DATOS DEL USUARIO PREPARADOR PARA SABER SUS DATOS PERSONALES
$queryPreparador = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_empleado = %s",
	valTpDato($rowEncabezado['id_empleado_preparador'],"int"));
$rsPreparador = mysql_query($queryPreparador);
if (!$rsPreparador) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPreparador = mysql_fetch_assoc($rsPreparador);

// BUSCA LOS DATOS DEL USUARIO APROBADOR PARA SABER SUS DATOS PERSONALES
$queryAprobador = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_empleado = %s",
	valTpDato($rowEncabezado['id_empleado_aprobador'],"int"));
$rsAprobador = mysql_query($queryAprobador);
if (!$rsAprobador) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowAprobador = mysql_fetch_assoc($rsAprobador);

$posY = 60;

imagestring($img,1,0,$posY,str_pad("", 116, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
imagestring($img,1,0,$posY,str_pad("APROBACIÓN", 94, " ", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 116, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,"PREPARADO POR",$textColor);
imagestring($img,1,70,$posY,": ".$rowPreparador['nombre_empleado'],$textColor);
imagestring($img,1,255,$posY,"APROBADO POR",$textColor);
imagestring($img,1,325,$posY,": ".$rowAprobador['nombre_empleado'],$textColor);
$posY += 9;
imagestring($img,1,0,$posY,"NOMBRE Y FIRMA",$textColor);
imagestring($img,1,70,$posY,":",$textColor);
imagestring($img,1,255,$posY,"NOMBRE Y FIRMA",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
$posY += 80;
imagestring($img,1,0,$posY,"FECHA",$textColor);
imagestring($img,1,70,$posY,": ".date(spanDateFormat,strtotime($rowEncabezado['fecha'])),$textColor);
imagestring($img,1,255,$posY,"FECHA",$textColor);
imagestring($img,1,325,$posY,": ".date(spanDateFormat,strtotime($rowEncabezado['fecha_orden'])),$textColor);

$posY += 20;

imagestring($img,1,0,$posY,str_pad("", 116, "-", STR_PAD_BOTH),$textColor);
$posY += 20;

imagestring($img,1,0,$posY,"CONDICIONES DE COMPRA, PRECIO, CALIDAD Y OPORTUNIDADES DE ENTREGA",$textColor);
$posY += 9;
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("1.- Despachar el pedido con nota de entrega y enviar la factura a la dirección que aparece al"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("pie de página delpresente documento."),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper("2.- Las condiciones de compra que aparecen en el presente documento, tales como precio,"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("especificaciones de calidad, plazos de entrega, lugar de despacho, etc., no son modificables"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("por el proveedor, en caso de prever algún incumplimiento, o de requerirse alguna modificación,"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("el proveedor notificará a la empresa de manera oportuna cualquier ajuste necesario antes de la"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("fecha de entrega prevista a fin de autorizar su cambio."),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper("3.- Los bienes sujetos a la presente deden ser de la calidad reconocida y regida por aquellas"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("normas que por antelación se hayan aceptado por las partes."),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper("4.- Los bienes para ser cancelado deben ser verificados y aprobados por el responsable de la"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("recepción según los requisitos de calidad acordada."),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper("5.- Las devoluciones que surjan por modificaciones realizadas por el proveedor de la presente"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("Orden de Compra, sin previa autorización del cliente, ya sea por el incumplimiento de los"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("precios acordados, por rechazos de calidad o por materiales que no cumplan a las condiciones"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("acordadas, serán buscadas por el proveedor en las instalaciones a donde fueron despachadas,"),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("asumiendo el proveedor el costo total del transporte."),$textColor);

$pageNum++;
$arrayImg[] = "tmp/"."orden_compra".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

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

//$pdf->nombreRegistrado = $row['nombre_empleado'];
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