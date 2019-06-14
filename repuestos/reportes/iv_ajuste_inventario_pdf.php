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
$tipoMovimiento = $valCadBusq[1];

// BUSCA LOS DATOS DEL DOCUMENTO
$query = sprintf("SELECT
	vw_iv_vale.*,
	
	(CASE
		WHEN (vw_iv_vale.tipo_vale_entrada = 1 OR vw_iv_vale.tipo_vale_entrada = 2 OR vw_iv_vale.tipo_vale_entrada = 3) THEN
			(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS nombre_cliente
			FROM cj_cc_cliente cliente
			WHERE cliente.id = vw_iv_vale.id_cliente)
		WHEN (vw_iv_vale.tipo_vale_entrada = 4 OR vw_iv_vale.tipo_vale_entrada = 5) THEN
			(SELECT cedula
			FROM pg_empleado empleado
			WHERE empleado.id_empleado = vw_iv_vale.id_cliente)
	END) AS ci_cliente,
	
	(CASE
		WHEN (vw_iv_vale.tipo_vale_entrada = 1 OR vw_iv_vale.tipo_vale_entrada = 2 OR vw_iv_vale.tipo_vale_entrada = 3) THEN
			(SELECT cliente.telf
			FROM cj_cc_cliente cliente
			WHERE cliente.id = vw_iv_vale.id_cliente)
		WHEN (vw_iv_vale.tipo_vale_entrada = 4 OR vw_iv_vale.tipo_vale_entrada = 5) THEN
			(SELECT empleado.telefono
			FROM pg_empleado empleado
			WHERE empleado.id_empleado = vw_iv_vale.id_cliente)
	END) AS telf,
	
	(CASE
		WHEN (vw_iv_vale.tipo_vale_entrada = 1 OR vw_iv_vale.tipo_vale_entrada = 2 OR vw_iv_vale.tipo_vale_entrada = 3) THEN
			(SELECT cliente.direccion
			FROM cj_cc_cliente cliente
			WHERE cliente.id = vw_iv_vale.id_cliente)
		WHEN (vw_iv_vale.tipo_vale_entrada = 4 OR vw_iv_vale.tipo_vale_entrada = 5) THEN
			(SELECT empleado.direccion
			FROM pg_empleado empleado
			WHERE empleado.id_empleado = vw_iv_vale.id_cliente)
	END) AS direccion_cliente,
	
	(SELECT nombre_empleado FROM vw_pg_empleados
	WHERE id_empleado = vw_iv_vale.id_empleado_creador) AS nombre_empleado
FROM vw_iv_vales vw_iv_vale
WHERE id_vale = %s
	AND tipo_movimiento = %s;",
	valTpDato($idDocumento,"int"),
	valTpDato($tipoMovimiento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_assoc($rs);

$idEmpresa = $row['id_empresa'];

$queryClaveMov = sprintf("SELECT
	clave_mov.id_clave_movimiento,
	clave_mov.descripcion,
	(CASE tipo
		WHEN 1 THEN 'COMPRA'
		WHEN 2 THEN 'ENTRADA'
		WHEN 3 THEN 'VENTA'
		WHEN 4 THEN 'SALIDA'
	END) AS tipo_movimiento
FROM pg_clave_movimiento clave_mov
  INNER JOIN iv_movimiento mov ON (clave_mov.id_clave_movimiento = mov.id_clave_movimiento)
WHERE mov.id_documento = %s
	AND clave_mov.tipo = %s
	AND mov.tipo_documento_movimiento = 1
    AND clave_mov.id_modulo = 0;",
	valTpDato($idDocumento,"int"),
	valTpDato($tipoMovimiento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

// DETALLES DE LOS REPUESTOS
switch ($tipoMovimiento) {
	case 2 : // ENTRADA
		$queryDetalle = sprintf("SELECT
			vale_entrada_det.id_vale_entrada_detalle,
			vale_entrada_det.id_vale_entrada,
			vw_iv_art.id_articulo,
			vw_iv_art.codigo_articulo,
			vw_iv_art.descripcion,
			vw_iv_art.id_tipo_articulo,
			vw_iv_art.tipo_articulo,
			vale_entrada_det.cantidad,
			vale_entrada_det.precio_venta,
			
			(SELECT vw_iv_casillas.descripcion_almacen FROM vw_iv_casillas
			WHERE vw_iv_casillas.id_casilla = vale_entrada_det.id_casilla) AS descripcion_almacen,
			
			(SELECT vw_iv_casillas.ubicacion FROM vw_iv_casillas
			WHERE vw_iv_casillas.id_casilla = vale_entrada_det.id_casilla) AS ubicacion
			
		FROM vw_iv_articulos_datos_basicos vw_iv_art
			INNER JOIN iv_vale_entrada_detalle vale_entrada_det ON (vw_iv_art.id_articulo = vale_entrada_det.id_articulo)
		WHERE vale_entrada_det.id_vale_entrada = %s
		ORDER BY vale_entrada_det.id_vale_entrada_detalle;",
			valTpDato($idDocumento,"int"));
		break;
	case 4 : // SALIDA
		$queryDetalle = sprintf("SELECT
			vale_salida_det.id_vale_salida_detalle,
			vale_salida_det.id_vale_salida,
			vw_iv_art.id_articulo,
			vw_iv_art.codigo_articulo,
			vw_iv_art.descripcion,
			vw_iv_art.id_tipo_articulo,
			vw_iv_art.tipo_articulo,
			vale_salida_det.cantidad,
			vale_salida_det.costo_compra,
			
			(SELECT vw_iv_casillas.descripcion_almacen FROM vw_iv_casillas
			WHERE vw_iv_casillas.id_casilla = vale_salida_det.id_casilla) AS descripcion_almacen,
			
			(SELECT vw_iv_casillas.ubicacion FROM vw_iv_casillas
			WHERE vw_iv_casillas.id_casilla = vale_salida_det.id_casilla) AS ubicacion
			
		FROM vw_iv_articulos_datos_basicos vw_iv_art
			INNER JOIN iv_vale_salida_detalle vale_salida_det ON (vw_iv_art.id_articulo = vale_salida_det.id_articulo)
		WHERE vale_salida_det.id_vale_salida = %s
		ORDER BY vale_salida_det.id_vale_salida_detalle;",
			valTpDato($idDocumento,"int"));
		break;
}
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
		imagestring($img,1,300,$posY,str_pad(utf8_decode("VALE DE ".(($tipoMovimiento == 2) ? "ENTRADA" : "SALIDA")), 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("VALE ".(($tipoMovimiento == 2) ? "ENT." : "SAL.")." NRO."),$textColor);
		imagestring($img,2,375,$posY-3,": ".$row['numeracion_vale'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($row['fecha'])),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("TIPO"),$textColor);////////////////////////////////////////////////////
		imagestring($img,1,375,$posY,": ".strtoupper($rowClaveMov['tipo_movimiento']),$textColor);
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("CLAVE"),$textColor);////////////////////////////////////////////////////
		imagestring($img,1,375,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);
		
		$posY = 28;
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($row['id_cliente'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper($row['nombre_cliente']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($row['ci_cliente']),$textColor);
		
		$direccionCliente = strtoupper(elimCaracter($row['direccion_cliente'],";"));
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
		imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
		imagestring($img,1,195,$posY,": ".$row['telf'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
		imagestring($img,1,205,$posY,$row['otrotelf'],$textColor);
		
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,260,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,str_pad(utf8_decode("COSTO UNIT."), 15, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	switch ($tipoMovimiento) {
		case 2 : // ENTRADA
			$precioCosto = $rowDetalle['precio_venta'];
			$total = ($rowDetalle['cantidad'] * $rowDetalle['precio_venta']);
			break;
		case 4 : // SALIDA
			$precioCosto = $rowDetalle['costo_compra'];
			$total = ($rowDetalle['cantidad'] * $rowDetalle['costo_compra']);
			break;
	}
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
	imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,28)),$textColor);
	imagestring($img,1,260,$posY,strtoupper(str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($precioCosto, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
	imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion_almacen'],0,28)),$textColor);
	imagestring($img,1,260,$posY,strtoupper(substr($rowDetalle['ubicacion'],0,28)),$textColor);
		
	if (fmod($contFila, $maxRows + (($nroHoja < $nroHojaSinTotal && $contFila <= $totalRowsDetalle && $totalRowsDetalle > $maxRowsSinTotal) ? ($maxRowsSinTotal - $maxRows) : 0)) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 460;
			
			$arrayObservacionDcto = str_split(preg_replace("/[\"?]/"," ",preg_replace("/[\r?|\n?]/"," ",utf8_encode($row['observacion']))), 50);
			if (isset($arrayObservacionDcto)) {
				foreach ($arrayObservacionDcto as $indice => $valor) {
					$posY += 9;
					imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
				}
			}
			
			$posY = 460;
			
			$posY += 9;
			imagestring($img,1,260,$posY,"SUB-TOTAL",$textColor);
			imagestring($img,1,340,$posY,":",$textColor);
			imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($row['subtotal_documento'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
			
			if ($row['descuentoFactura'] > 0) {
				$porcDescuento = (($row['descuentoFactura'] * 100) / $row['subtotal_documento']);
				$posY += 9;
				imagestring($img,1,260,$posY,"DESCUENTO",$textColor);
				imagestring($img,1,340,$posY,":",$textColor);
				imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
				imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($row['descuentoFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
			}
			
			if ($row['baseImponible'] > 0) {
				$posY += 9;
				imagestring($img,1,260,$posY,"BASE IMPONIBLE",$textColor);
				imagestring($img,1,340,$posY,":",$textColor);
				imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($row['baseImponible'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
			}
			
			if ($row['calculoIvaFactura'] > 0) {
				$posY += 9;
				imagestring($img,1,260,$posY,"IMPUESTO",$textColor);
				imagestring($img,1,340,$posY,":",$textColor);
				imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($row['porcentajeIvaFactura'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
				imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($row['calculoIvaFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
			}
			
			if ($row['calculoIvaDeLujoFactura'] > 0) {
				$posY += 9;
				imagestring($img,1,260,$posY,"IMPUESTO LUJO",$textColor);
				imagestring($img,1,340,$posY,":",$textColor);
				imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($row['porcentajeIvaDeLujoFactura'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
				imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($row['calculoIvaDeLujoFactura'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
			}
			
			if ($row['montoNoGravado'] > 0) {
				$posY += 9;
				imagestring($img,1,260,$posY,"MONTO EXENTO",$textColor);
				imagestring($img,1,340,$posY,":",$textColor);
				imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($row['montoNoGravado'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
			}
			
			$posY += 8;
			imagestring($img,1,260,$posY,str_pad("", 42, "-", STR_PAD_BOTH),$textColor);
			
			$posY += 8;
			imagestring($img,1,260,$posY,"TOTAL",$textColor);
			imagestring($img,1,340,$posY,":",$textColor);
			imagestring($img,2,360,$posY,strtoupper(str_pad(number_format($row['subtotal_documento'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."vale_entrada_salida_repuestos".$pageNum.".png";
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

$pdf->nombreRegistrado = $row['nombre_empleado'];
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