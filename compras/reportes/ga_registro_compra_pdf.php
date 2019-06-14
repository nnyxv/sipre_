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
$pdf->SetAutoPageBreak(1,"0");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 32;
$maxRowsSinTotal = 32; // NRO DE LINEAS PERMITIDAS QUITANDO LAS LINEAS DE TOTAL
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
	prov.correo,
	prov.fax,
	prov.contacto,
	B.id_fecha_reconversion as reconversion,
	vw_pg_empleado.nombre_empleado
FROM cp_factura cxp_fact
	left JOIN cp_reconversion B ON  (cxp_fact.id_factura=B.id_factura )
	INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxp_fact.id_empleado_creador = vw_pg_empleado.id_empleado)
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
	(CASE mov.id_tipo_clave_movimiento
		WHEN 1 THEN 'COMPRA'
		WHEN 2 THEN 'ENTRADA'
		WHEN 3 THEN 'VENTA'
		WHEN 4 THEN 'SALIDA'
	END) AS tipo_movimiento
FROM pg_clave_movimiento clave_mov
  INNER JOIN ga_movimiento mov ON (clave_mov.id_clave_movimiento = mov.id_clave_movimiento)
WHERE mov.id_documento = %s
	AND mov.id_tipo_clave_movimiento IN (1);",
	valTpDato($idDocumento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

//DETALLES DE LOS ARTICULOS
$queryDetalle = sprintf("SELECT 
	cxp_fact_det.id_factura_detalle,
	cxp_fact_det.id_factura,
	cxp_fact_det.id_pedido_compra,
	cxp_fact_det.cantidad,
	cxp_fact_det.pendiente,
	cxp_fact_det.precio_unitario,
	art.codigo_articulo,
	art.descripcion,
	
	(SELECT SUM(iva) AS iva
		FROM ga_factura_detalle_iva 
	WHERE ga_factura_detalle_iva.id_factura_detalle = cxp_fact_det.id_factura_detalle) AS iva	
		
FROM cp_factura_detalle cxp_fact_det
	INNER JOIN ga_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
WHERE cxp_fact_det.id_factura = %s
ORDER BY id_factura_detalle ASC",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);

if($totalRowsDetalle == 0){//sin detalle, factura por CxP
	$rsDetalle = mysql_query('SELECT 1', $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	$sinDetalle = 1;
}

while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	$nroHojaSinTotal = floor($totalRowsDetalle / $maxRowsSinTotal); // NRO. DE HOJAS DEPENDIENDO DE LAS LINEAS PERMITIDAS QUITANDO LAS LINEAS DE TOTAL
	$nroHoja = floor($contFila / $maxRowsSinTotal); // NRO. DE HOJA ACTUAL
	
	if (fmod($contFila, $maxRows + (($nroHoja < $nroHojaSinTotal && $contFila <= $totalRowsDetalle && $totalRowsDetalle > $maxRowsSinTotal) ? ($maxRowsSinTotal - $maxRows) : 0)) == 1) {
		/* ENCABEZADO */
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
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
		imagestring($img,1,200,$posY,": "."COMPRA",$textColor);
		imagestring($img,1,270,$posY,("CLAVE MOV."),$textColor);////////////////////////////////////////////////////
		imagestring($img,1,320,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);
		
		$posY += 9;
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
		imagestring($img,1,320,$posY,": ".strtoupper($rowEncabezado['correo']),$textColor);
		
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
		imagestring($img,1,0,$posY,str_pad(("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(("DESCRIPCIÓN"), 27, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,255,$posY,str_pad(("RECIB."), 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,290,$posY,str_pad(("PRECIO UNIT."), 13, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,360,$posY,str_pad(("%IMPTO"), 6, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,395,$posY,str_pad(("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}

	/* DETALLE ARTICULOS */
	if(!$sinDetalle){
		$cantPedida = $rowDetalle['cantidad'];
		$cantRecibida = $rowDetalle['cantidad'] /*- $rowDetalle['pendiente']*/;
		$cantPendiente = $rowDetalle['pendiente'];
		$precioUnitario = $rowDetalle['precio_unitario'];
		$porcIva = ($rowDetalle['iva'] > 0) ? number_format($rowDetalle['iva'], 2, ".", ",")."%" : "-";
		$total = $cantRecibida * $precioUnitario;
		
		$posY += 9;
		imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,27)),$textColor);
		imagestring($img,1,255,$posY,str_pad(number_format($cantRecibida, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,290,$posY,str_pad(number_format($precioUnitario, 2, ".", ","), 13, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,360,$posY,str_pad($porcIva, 6, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	}
	
	if (fmod($contFila, $maxRows + (($nroHoja < $nroHojaSinTotal && $contFila <= $totalRowsDetalle && $totalRowsDetalle > $maxRowsSinTotal) ? ($maxRowsSinTotal - $maxRows) : 0)) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 415;
			
			$arrayObservacionDcto = str_split(preg_replace("/[\"?]/"," ",preg_replace("/[\r?|\n?]/"," ",($rowEncabezado['observacion_factura']))), 94);
			if (isset($arrayObservacionDcto)) {
				foreach ($arrayObservacionDcto as $indice => $valor) {
					$posY += 9;
					imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
				}
			}
			
			//CONSUTLA LOS GASTO DE LA FACTURA
			$queryGasto = sprintf("SELECT
				cxp_fact_gasto.id_factura_gasto,
				cxp_fact_gasto.id_factura,
				cxp_fact_gasto.id_gasto,	
				cxp_fact_gasto.estatus_iva,
				cxp_fact_gasto.id_modo_gasto,
				cxp_fact_gasto.afecta_documento,
				cxp_fact_det_iva_gasto.id_iva, 
				cxp_fact_det_iva_gasto.iva,
				IF(cxp_fact_det_iva_gasto.porcentaje_monto IS NULL, cxp_fact_gasto.porcentaje_monto, cxp_fact_det_iva_gasto.porcentaje_monto) AS porcentaje_monto_gasto,
				IF(cxp_fact_det_iva_gasto.monto IS NULL, cxp_fact_gasto.monto, cxp_fact_det_iva_gasto.monto) AS monto_gasto,
				gasto.nombre
			FROM cp_factura_gasto cxp_fact_gasto
				LEFT JOIN ga_factura_detalle_iva_gasto cxp_fact_det_iva_gasto ON (cxp_fact_gasto.id_factura_gasto = cxp_fact_det_iva_gasto.id_factura_gasto)
				INNER JOIN pg_gastos gasto ON (gasto.id_gasto = cxp_fact_gasto.id_gasto)
			WHERE cxp_fact_gasto.id_factura = %s
			GROUP BY cxp_fact_gasto.id_gasto;",
				valTpDato($idDocumento, "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$posY = 460;
			
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcGasto = $rowGasto['porcentaje_monto_gasto'];
				$montoGasto = $rowGasto['monto_gasto'];
				
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(substr($rowGasto['nombre'],0,25)),$textColor);
				imagestring($img,1,130,$posY,":",$textColor);
				imagestring($img,1,140,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,175,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				if ($rowGasto['id_iva'] > 0) {
					$totalGastosConIva += $rowGasto['monto_gasto'];
				} else if ($rowGasto['id_iva'] == 0 || $rowGasto['id_iva'] == "" || $rowGasto['id_iva'] == NULL) {
					$totalGastosSinIva += $rowGasto['monto_gasto'];
				}
				
				$totalGasto += $rowGasto['monto_gasto'];
			}
		
			/* TOTALES */
			$posY = 460;
			
			$subTotal = $rowEncabezado['subtotal_factura'];
			$posY += 8;
			imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 14, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($subTotal, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotal_factura'] > 0) ? ($rowEncabezado['subtotal_descuento'] * 100) / $rowEncabezado['subtotal_factura'] : 0;
			$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
			$posY += 9;
			imagestring($img,1,255,$posY,str_pad("DESCUENTO", 14, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($subtotalDescuento,2,".",","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$totalGastosConIva = $totalGastosConIva;
			$posY += 9;
			imagestring($img,1,255,$posY,str_pad("GASTOS C/IMPTO", 14, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$queryIvaFact = sprintf("SELECT
					iva.observacion,
					cxp_fact_iva.base_imponible,
					cxp_fact_iva.iva,
					cxp_fact_iva.subtotal_iva
				FROM cp_factura_iva cxp_fact_iva
					INNER JOIN pg_iva iva ON (cxp_fact_iva.id_iva = iva.idIva)
				WHERE cxp_fact_iva.id_factura = %s;",
				valTpDato($idDocumento, "int"));
			$rsIvaFact = mysql_query($queryIvaFact);
			
			if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalIva = 0;
			
			while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {								
				/*$posY += 9;
				imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 14, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);*/
				
				$posY += 9;
				//se agrega la variable $iva_porcentaje para mejorar la visualización de la información del campo IVA
				$iva_porcentaje = $rowIvaFact['observacion']." (".number_format($rowIvaFact['iva'],0, ".", ",")."%)";
				
				//Estas dos líneas se comentan porque la información del IVA sale distorsionada, con los montos uno sobre el otro. Se sustituye con la variable $iva_porcentaje
				/*imagestring($img,1,255,$posY,str_pad(substr($rowIvaFact['observacion'],0,17), 14, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,380,$posY,str_pad(number_format($rowIvaFact['iva'], 0, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);*/
				
				imagestring($img,1,255,$posY,str_pad(substr($iva_porcentaje,0,17), 14, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,1,340,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaFact['subtotal_iva'];
			}
			
			$totalGastosSinIva = $totalGastosSinIva;
			$posY += 9;
			imagestring($img,1,255,$posY,str_pad("GASTOS S/IMPTO", 14, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
						
			$posY += 9;
			$totalExento = $rowEncabezado['monto_exento'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
			imagestring($img,1,255,$posY,str_pad("MONTO EXENTO", 14, " ", STR_PAD_RIGHT).":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($totalExento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$posY += 5;
			imagestring($img,1,255,$posY,str_pad("", 54, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $totalIva + $totalGasto;
			
			//echo $rowEncabezado['fecha_origen'];
			
			//Se agrega línea para mostrar el total en bolívares Soberanos, quitar cuando sea requerido///////////////////////////////// 
			if ($rowEncabezado['reconversion']==null) {
				if($rowEncabezado['fecha_origen']>="2018-08-01" and $rowEncabezado['fecha_origen']<"2018-08-20"){
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format($totalFactura, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				 	$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}else if($rowEncabezado['fecha_origen']>="2018-08-20"){
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format(($totalFactura), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format($totalFactura/100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}else{
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format($totalFactura, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}
			}else{
				if($rowEncabezado['fecha_origen']>="2018-08-01" and $rowEncabezado['fecha_origen']<"2018-08-20"){
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format($totalFactura*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				 	$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format(($totalFactura), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}else if($rowEncabezado['fecha_origen']>="2018-08-20"){
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format(($totalFactura), 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format($totalFactura*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}else{
					$posY += 7;
					imagestring($img,1,255,$posY,str_pad("TOTAL Bs.S", 14, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,1,380,$posY-2,str_pad(number_format($totalFactura, 2, ".", ","), 18, " ", STR_PAD_LEFT),$textColor);
				}	
			}
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."registro_compra".$pageNum.".png";
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
