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
$maxRows = 16;
$maxRowsSinTotal = 16; // NRO DE LINEAS PERMITIDAS QUITANDO LAS LINEAS DE TOTAL
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT 
	cxp_fact.*,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre,
	prov.direccion,
	prov.telefono,
	prov.fax,
	prov.contacto,
	prov.correococtacto,
	cxp_fact_imp.total_advalorem,
	cxp_fact_imp.total_advalorem_diferencia,
	cxp_fact_imp.tasa_cambio,
	cxp_fact_imp.tasa_cambio_diferencia,
	moneda_origen.abreviacion AS abreviacion_moneda_origen,
	moneda_local.abreviacion AS abreviacion_moneda_local,
	B.id_fecha_reconversion as reconversion,
	
	(SELECT SUM(cxp_fact_det.cantidad * cxp_fact_det_imp.costo_unitario)
	FROM cp_factura_detalle cxp_fact_det
		INNER JOIN cp_factura_detalle_importacion cxp_fact_det_imp ON (cxp_fact_det.id_factura_detalle = cxp_fact_det_imp.id_factura_detalle)
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS total_fob,
	
	(SELECT SUM(cxp_fact_det.cantidad * cxp_fact_det_imp.gasto_unitario)
	FROM cp_factura_detalle cxp_fact_det
		INNER JOIN cp_factura_detalle_importacion cxp_fact_det_imp ON (cxp_fact_det.id_factura_detalle = cxp_fact_det_imp.id_factura_detalle)
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS total_gasto_fob,
	
	(SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
	WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
		AND cxp_fact_gasto.id_modo_gasto IN (3)) AS total_gastos_importacion,
	
	(SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
	WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
		AND cxp_fact_gasto.id_modo_gasto IN (2)) AS total_otros_cargos,
	
	vw_pg_empleado.nombre_empleado
FROM cp_factura cxp_fact
	left join cp_reconversion B on (cxp_fact.id_factura = B.id_factura)
	INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
	LEFT JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact.id_factura = cxp_fact_imp.id_factura)
	LEFT JOIN pg_monedas moneda_origen ON (cxp_fact_imp.id_moneda_tasa_cambio = moneda_origen.idmoneda)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxp_fact.id_empleado_creador = vw_pg_empleado.id_empleado)
	INNER JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
WHERE cxp_fact.id_factura = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];
$idModoCompra = $rowEncabezado['id_modo_compra'];

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
	AND mov.id_tipo_movimiento IN (1);",
	valTpDato($idDocumento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT 
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	cxp_fact_det.cantidad,
	cxp_fact_det.pendiente,
	cxp_fact_det.precio_unitario,
	cxp_fact_det.id_casilla,
	
	(SELECT SUM(cxp_fact_det_impsto.impuesto) FROM cp_factura_detalle_impuesto cxp_fact_det_impsto
	WHERE cxp_fact_det_impsto.id_factura_detalle = cxp_fact_det.id_factura_detalle) AS porc_iva
FROM cp_factura_detalle cxp_fact_det
	INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
WHERE cxp_fact_det.id_factura = %s
ORDER BY id_factura_detalle ASC",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	$nroHojaSinTotal = floor($totalRowsDetalle / $maxRowsSinTotal); // NRO. DE HOJAS DEPENDIENDO DE LAS LINEAS PERMITIDAS QUITANDO LAS LINEAS DE TOTAL
	$nroHoja = floor($contFila / $maxRowsSinTotal); // NRO. DE HOJA ACTUAL
	
	if (fmod($contFila, $maxRows + (($nroHoja < $nroHojaSinTotal && $contFila <= $totalRowsDetalle && $totalRowsDetalle > $maxRowsSinTotal) ? ($maxRowsSinTotal - $maxRows) : 0)) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$backgroundGris = imagecolorallocate($img, 230, 230, 230);
		$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
		
		$posY = 0;
		imagestring($img,1,160,$posY,str_pad("REGISTRO DE COMPRA", 62, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,320,$posY,("ID REG. COMPRA"),$textColor);
		imagestring($img,1,390,$posY,": ".$rowEncabezado['id_factura'],$textColor);
		
		$posY += 9;
		imagestring($img,1,320,$posY,("FECHA REGISTRO"),$textColor);
		imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_origen'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,160,$posY,("FACT. PROV. NRO."),$textColor);
		imagestring($img,1,245,$posY,": ".$rowEncabezado['numero_factura_proveedor'],$textColor);
		imagestring($img,1,320,$posY,("NRO. CONTROL"),$textColor);
		imagestring($img,1,390,$posY,": ".$rowEncabezado['numero_control_factura'],$textColor);
		
		$posY += 9;
		imagestring($img,1,160,$posY,("FECHA FACT. PROV."),$textColor);
		imagestring($img,1,245,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_factura_proveedor'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,160,$posY,("TIPO MOV."),$textColor);////////////////////////////////////////////////////
		imagestring($img,1,245,$posY,": ".strtoupper($rowClaveMov['tipo_movimiento']),$textColor);
		imagestring($img,1,320,$posY,("CLAVE MOV."),$textColor);////////////////////////////////////////////////////
		imagestring($img,1,390,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);
		
		$posY += 9;
		imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(("DATOS DEL PROVEEDOR"), 94, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,("RAZÓN SOCIAL"),$textColor);
		imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre']),$textColor);
		imagestring($img,1,280,$posY,($spanProvCxP),$textColor);
		imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,("CONTACTO"),$textColor);/////////////////////////////////////////
		imagestring($img,1,45,$posY,": ".strtoupper(substr($rowEncabezado['contacto'],0,30)),$textColor);
		imagestring($img,1,280,$posY,("EMAIL"),$textColor);///////////////////////////////////////////////////
		imagestring($img,1,320,$posY,": ".strtoupper($rowEncabezado['correococtacto']),$textColor);
		
		$direccionProveedor = strtoupper(str_replace(",", " ", $rowEncabezado['direccion']));
		$posY += 9;
		imagestring($img,1,0,$posY,("DIRECCIÓN"),$textColor);/////////////////////////////////////////
		imagestring($img,1,45,$posY,": ".trim(substr($direccionProveedor,0,44)),$textColor);
		imagestring($img,1,280,$posY,("TELÉFONO"),$textColor);///////////////////////////////////////////////////
		imagestring($img,1,320,$posY,": ".$rowEncabezado['telefono'],$textColor);
		
		$posY += 9;
		imagestring($img,1,55,$posY,trim(substr($direccionProveedor,44,44)),$textColor);
		imagestring($img,1,280,$posY,("FAX"),$textColor);///////////////////////////////////////////////////
		imagestring($img,1,320,$posY,": ".$rowEncabezado['fax'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,100,$posY,str_pad(("DESCRIPCIÓN"), 20, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,230,$posY,str_pad(("RECIB."), 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,260,$posY,strtoupper(str_pad(($spanPrecioUnitario), 13, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,340,$posY,str_pad(("%IMPTO"), 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,380,$posY,str_pad(("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	$queryArtAlm = sprintf("SELECT
		vw_iv_art_alm.descripcion_almacen,
		vw_iv_art_alm.ubicacion
	FROM vw_iv_articulos_almacen vw_iv_art_alm
		INNER JOIN iv_kardex kardex ON (vw_iv_art_alm.id_casilla = kardex.id_casilla)
	WHERE kardex.id_documento = %s
		AND kardex.id_articulo = %s
		AND kardex.tipo_movimiento IN (1);",
		valTpDato($rowEncabezado['id_factura'], "int"),
		valTpDato($rowDetalle['id_articulo'], "int"));
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
	
	$cantPedida = $rowDetalle['cantidad'];
	$cantRecibida = $rowDetalle['cantidad'] - $rowDetalle['pendiente'];
	$cantPendiente = $rowDetalle['pendiente'];
	$precioUnitario = $rowDetalle['precio_unitario'];
	$porcIva = ($rowDetalle['porc_iva'] > 0) ? $rowDetalle['porc_iva']."%" : "-";
	$total = $cantRecibida * $precioUnitario;
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
	imagestring($img,1,100,$posY,strtoupper(substr($rowDetalle['descripcion'],0,27)),$textColor);
	imagestring($img,1,225,$posY,str_pad(number_format($cantRecibida, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,260,$posY,str_pad(number_format($precioUnitario, 2, ".", ","), 13, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,340,$posY,str_pad($porcIva, 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,380,$posY,str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
	imagestring($img,1,100,$posY,strtoupper(substr($rowArtAlm['descripcion_almacen']." ".str_replace("-[]", "", $rowArtAlm['ubicacion']),0,34)),$textColor);
	
	$posY += 1;
	if (fmod($contFila, $maxRows + (($nroHoja < $nroHojaSinTotal && $contFila <= $totalRowsDetalle && $totalRowsDetalle > $maxRowsSinTotal) ? ($maxRowsSinTotal - $maxRows) : 0)) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 415;
			
			if ($rowEncabezado['total_advalorem'] > 0) {
				imagestring($img,1,0,$posY,str_pad("AD-VALOREM (INCLUIDO EN SUBTOTAL)", 33, " ", STR_PAD_RIGHT).":".str_pad(number_format($rowEncabezado['total_advalorem'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
			}
			if ($rowEncabezado['tasa_cambio'] > 0) {
				imagestring($img,1,255,$posY,str_pad("TASA CAMBIO (INFORMATIVO)", 26, " ", STR_PAD_RIGHT).":".str_pad(number_format($rowEncabezado['tasa_cambio'], 3, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($rowEncabezado['total_otros_cargos'] > 0) {
				if ($rowEncabezado['total_advalorem'] > 0) {
					$posY += 9;
				}
				imagestring($img,1,0,$posY,str_pad("OTROS CARGOS (INFORMATIVO)", 33, " ", STR_PAD_RIGHT).":".str_pad(number_format($rowEncabezado['total_otros_cargos'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
			}
			
			$arrayObservacionDcto = str_split(preg_replace("/[\"?]/"," ",preg_replace("/[\r?|\n?]/"," ",utf8_encode($rowEncabezado['observacion_factura']))), 94);
			if (isset($arrayObservacionDcto)) {
				foreach ($arrayObservacionDcto as $indice => $valor) {
					$posY += 9;
					imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
				}
			}
			
			$queryGasto = sprintf("SELECT
				cxp_fact_gasto.id_factura_gasto,
				cxp_fact_gasto.id_factura,
				cxp_fact_gasto.tipo,
				cxp_fact_gasto.porcentaje_monto,
				cxp_fact_gasto.monto,
				cxp_fact_gasto.estatus_iva,
				cxp_fact_gasto.id_iva,
				cxp_fact_gasto.iva,
				gasto.id_gasto,
				IF (((SELECT SUM(cxp_fact_gasto_impsto.impuesto) FROM cp_factura_gasto_impuesto cxp_fact_gasto_impsto
						WHERE cxp_fact_gasto_impsto.id_factura_gasto = cxp_fact_gasto.id_factura_gasto) > 0), gasto.nombre, CONCAT_WS(' ', gasto.nombre, '(E)')) AS nombre,
				gasto.id_modo_gasto
			FROM pg_gastos gasto
				INNER JOIN cp_factura_gasto cxp_fact_gasto ON (gasto.id_gasto = cxp_fact_gasto.id_gasto)
			WHERE cxp_fact_gasto.id_factura = %s
				AND cxp_fact_gasto.id_modo_gasto IN (1,3)
			ORDER BY cxp_fact_gasto.id_modo_gasto ASC;",
				valTpDato($rowEncabezado['id_factura'], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 460;
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcGasto = $rowGasto['porcentaje_monto'];
				$montoGasto = $rowGasto['monto'];
				
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad(strtoupper(substr((($rowGasto['id_modo_gasto'] == 1) ? "" : " ").$rowGasto['nombre'],0,24)), 24, " ", STR_PAD_RIGHT).":",$textColor);
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
			
			
			$posY = 460;
			
			$subTotal = $rowEncabezado['subtotal_factura'];
			$posY += 9;
			imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,370,$posY,str_pad(number_format($subTotal, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotal_factura'] > 0) ? ($rowEncabezado['subtotal_descuento'] * 100) / $rowEncabezado['subtotal_factura'] : 0;
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
			$advDiferencia=$rowEncabezado['total_advalorem_diferencia'];

			if ($advDiferencia > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("DIF. BASE IMPO.", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($advDiferencia, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			$queryIvaFact = sprintf("SELECT
				iva.observacion,
				cxp_fact_iva.base_imponible,
				cxp_fact_iva.iva,
				cxp_fact_iva.subtotal_iva
			FROM cp_factura_iva cxp_fact_iva
				INNER JOIN pg_iva iva ON (cxp_fact_iva.id_iva = iva.idIva)
			WHERE id_factura = %s;",
				valTpDato($rowEncabezado['id_factura'], "int"));
			$rsIvaFact = mysql_query($queryIvaFact);
			if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalIva = 0;
			while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
				$posY += 9;
				imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 17, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,370,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
				
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
			imagestring($img,1,255,$posY,str_pad("", 54, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $totalIva + $totalGastosSinIvaOrigen + $totalGastosConIvaOrigen + $totalGastosSinIvaLocal + $totalGastosConIvaLocal + $advDiferencia;

			$fechaOrigen = date_create_from_format('Y-m-d',$rowEncabezado['fecha_origen']);
			$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
			$fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');
			// $totalFactura=$rowIvaFact['base_imponible']+$rowIvaFact['subtotal_iva'];
			if ($rowEncabezado['reconversion']==null) {
				if ($fechaOrigen>=$fechaAjusten) {
					$aa = $totalFactura*100000;
				}else{
					$aa = $totalFactura;
				}
			
			}else{

				$aa = $totalFactura*100000;
			}

			$a = number_format($aa, 2, ".", ",");
			$cantidad_string= strlen($a);

			//var_dump($cantidad_string);
			//exit();
			if ($cantidad_string == 16) {
				$total = 375;
				$total2 = 380;
			}elseif ($cantidad_string == 17) {
				$total = 368;
				$total2 = 385;
			}elseif ($cantidad_string >= 18) {
				$total = 362;
				$total2 = 380;
			}else {
				$total=380;
				$total2 = 380;
			}

//$rowEncabezado['fecha_origen']
		
			if ($rowEncabezado['reconversion']==null) {
				if ($fechaOrigen>=$fechaReconversion and $fechaOrigen<$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
					$posY += 10;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaOrigen>=$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					imagestring($img,1,255,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	

				}else{
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
				}
			}else{
				if ($fechaOrigen>=$fechaReconversion and $fechaOrigen<$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
					$posY += 10;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaOrigen>=$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					imagestring($img,1,255,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	

				}else{
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
				}

			}
			
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."registro_compra".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
}

//////////////////////////////////////// PAGINA DETALLES DE LA IMPORTACION ////////////////////////////////////////
if ($rowEncabezado['id_modo_compra'] == 2) { // 1 = Nacional, 2 = Importación
	$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	$backgroundGris = imagecolorallocate($img, 230, 230, 230);
	$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
	
	$posY = 0;
	imagestring($img,1,160,$posY,str_pad("REGISTRO DE COMPRA", 62, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 9;
	$posY += 9;
	imagestring($img,1,320,$posY,("ID REG. COMPRA"),$textColor);
	imagestring($img,1,390,$posY,": ".$rowEncabezado['id_factura'],$textColor);
	
	$posY += 9;
	imagestring($img,1,320,$posY,("FECHA"),$textColor);
	imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_origen'])),$textColor);
	
	$posY += 9;
	imagestring($img,1,160,$posY,("FACT. PROV. NRO."),$textColor);
	imagestring($img,1,245,$posY,": ".$rowEncabezado['numero_factura_proveedor'],$textColor);
	imagestring($img,1,320,$posY,("NRO. CONTROL"),$textColor);
	imagestring($img,1,390,$posY,": ".$rowEncabezado['numero_control_factura'],$textColor);
	
	$posY += 9;
	imagestring($img,1,160,$posY,("FECHA FACT. PROV."),$textColor);
	imagestring($img,1,245,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_factura_proveedor'])),$textColor);
	
	$posY += 9;
	imagestring($img,1,160,$posY,("TIPO MOV."),$textColor);////////////////////////////////////////////////////
	imagestring($img,1,245,$posY,": ".strtoupper($rowClaveMov['tipo_movimiento']),$textColor);
	imagestring($img,1,320,$posY,("CLAVE MOV."),$textColor);////////////////////////////////////////////////////
	imagestring($img,1,390,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);
	
	$posY += 9;
	imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
	imagestring($img,1,0,$posY,str_pad(("DATOS DEL PROVEEDOR"), 94, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,("RAZÓN SOCIAL"),$textColor);
	imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre']),$textColor);
	imagestring($img,1,280,$posY,($spanProvCxP),$textColor);
	imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,("CONTACTO"),$textColor);/////////////////////////////////////////
	imagestring($img,1,45,$posY,": ".strtoupper(substr($rowEncabezado['contacto'],0,30)),$textColor);
	imagestring($img,1,280,$posY,("EMAIL"),$textColor);///////////////////////////////////////////////////
	imagestring($img,1,320,$posY,": ".strtoupper($rowEncabezado['correococtacto']),$textColor);
	
	$direccionProveedor = strtoupper(str_replace(",", " ", $rowEncabezado['direccion']));
	$posY += 9;
	imagestring($img,1,0,$posY,("DIRECCIÓN"),$textColor);/////////////////////////////////////////
	imagestring($img,1,45,$posY,": ".trim(substr($direccionProveedor,0,44)),$textColor);
	imagestring($img,1,280,$posY,("TELÉFONO"),$textColor);///////////////////////////////////////////////////
	imagestring($img,1,320,$posY,": ".$rowEncabezado['telefono'],$textColor);
	
	$posY += 9;
	imagestring($img,1,55,$posY,trim(substr($direccionProveedor,44,44)),$textColor);
	imagestring($img,1,280,$posY,("FAX"),$textColor);///////////////////////////////////////////////////
	imagestring($img,1,320,$posY,": ".$rowEncabezado['fax'],$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
	imagestring($img,1,0,$posY,str_pad(("DETALLES DE LA IMPORTACIÓN"), 94, " ", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	
	if ($rowEncabezado['total_fob'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("F.O.B.", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_origen'], 5, " ", STR_PAD_LEFT).str_pad(number_format($rowEncabezado['total_fob'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['total_gasto_fob'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("GASTOS", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_origen'], 5, " ", STR_PAD_LEFT).str_pad(number_format($rowEncabezado['total_gasto_fob'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	$totalCif = $rowEncabezado['total_fob'] + $rowEncabezado['total_gasto_fob'];
	if ($totalCif > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("C.I.F.", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_origen'], 5, " ", STR_PAD_LEFT).str_pad(number_format($totalCif, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['tasa_cambio'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("C.I.F. CAMBIO", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_local'], 5, " ", STR_PAD_LEFT).str_pad(number_format($totalCif * $rowEncabezado['tasa_cambio'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,255,$posY,str_pad("TASA CAMBIO", 26, " ", STR_PAD_RIGHT).":".str_pad(number_format($rowEncabezado['tasa_cambio'], 3, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['total_advalorem'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("AD-VALOREM", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_local'], 5, " ", STR_PAD_LEFT).str_pad(number_format($rowEncabezado['total_advalorem'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['total_gastos_importacion'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("GASTOS POR IMPORTACIÓN", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_local'], 5, " ", STR_PAD_LEFT).str_pad(number_format($rowEncabezado['total_gastos_importacion'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['total_otros_cargos'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("OTROS CARGOS", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_local'], 5, " ", STR_PAD_LEFT).str_pad(number_format($rowEncabezado['total_otros_cargos'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['tasa_cambio_diferencia'] > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("DIF. CAMBIARIA", 28, " ", STR_PAD_RIGHT).":".str_pad($rowEncabezado['abreviacion_moneda_local'], 5, " ", STR_PAD_LEFT).str_pad(number_format($totalCif * $rowEncabezado['tasa_cambio_diferencia'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,255,$posY,str_pad("TASA DIFERENCIA", 26, " ", STR_PAD_RIGHT).":".str_pad(number_format($rowEncabezado['tasa_cambio_diferencia'], 3, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
	}
	
	$posY += 9;
	$posY += 9;
	$subtotal = $totalCif * ($rowEncabezado['tasa_cambio'] + $rowEncabezado['tasa_cambio_diferencia']);
	imagestring($img,1,230,$posY,str_pad("SUBTOTAL", 22, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($subtotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$posY += 9;
	$subtotalImportacion = $rowEncabezado['total_advalorem'] + $rowEncabezado['total_gastos_importacion'];
	imagestring($img,1,230,$posY,str_pad("SUBTOTAL IMPORTACIÓN", 22, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($subtotalImportacion, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$rsIvaFact = mysql_query($queryIvaFact);
	if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalIva = 0;
	while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
		$posY += 9;
		imagestring($img,1,230,$posY,str_pad("BASE IMPONIBLE", 22, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,230,$posY,str_pad(substr($rowIvaFact['observacion'],0,22), 22, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,350,$posY,str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		
		$totalIva += $rowIvaFact['subtotal_iva'];
	}
	
	$posY += 9;
	imagestring($img,1,230,$posY,str_pad("OTROS CARGOS", 22, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['total_otros_cargos'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
	$posY += 7;
	imagestring($img,1,230,$posY,str_pad("", 59, "-", STR_PAD_LEFT),$textColor);
	
	$totalFactura = $subtotal + $subtotalImportacion + $totalIva + $rowEncabezado['total_otros_cargos'];

//$rowEncabezado['fecha_origen']
	$fechaOrigen = date_create_from_format('Y-m-d',$rowEncabezado['fecha_origen']);
	$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
	$fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');

			if ($rowEncabezado['reconversion']==null) {
				if ($fechaOrigen>=$fechaAjusten) {
					$aa = $totalFactura*100000;
				}else{
					$aa = $totalFactura;
				}
			
			}else{

				$aa = $totalFactura*100000;
			}

			$a = number_format($aa, 2, ".", ",");
			$cantidad_string= strlen($a);

			//var_dump($cantidad_string);
			//exit();
			if ($cantidad_string == 16) {
				$total = 375;
				$total2 = 380;
			}elseif ($cantidad_string == 17) {
				$total = 368;
				$total2 = 385;
			}elseif ($cantidad_string >= 18) {
				$total = 362;
				$total2 = 380;
			}else {
				$total=380;
				$total2 = 380;
			}
			
			// echo number_format(($totalFactura/1000), 2, ".", ",");
		if ($rowEncabezado['reconversion']==null) {
				if ($fechaOrigen>=$fechaReconversion and $fechaOrigen<$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,230,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
					$posY += 10;
					imagestring($img,1,315,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaOrigen>=$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,315,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					imagestring($img,1,230,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	

				}else{
					$posY += 7;
					imagestring($img,1,230,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
				}
		}else{
				if ($fechaOrigen>=$fechaReconversion and $fechaOrigen<$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,230,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
					$posY += 10;
					imagestring($img,1,315,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaOrigen>=$fechaAjusten) {
					$posY += 7;
					imagestring($img,1,315,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total2,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					imagestring($img,1,230,$posY,str_pad("TOTAL", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	

				}else{
					$posY += 7;
					imagestring($img,1,230,$posY,str_pad("TOTAL Bs.S", 17, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,$total,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);	
				}

		}	
			
			
	$pageNum++;
	$arrayImg[] = "tmp/"."registro_compra".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
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
