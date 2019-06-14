<?php
require_once("../../connections/conex.php");
session_start();
set_time_limit(0);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

// INSERTA LOS DETALLES DE LAS NOTAS DE DEBITO QUE NO TIENEN
$insertSQL = sprintf("INSERT INTO cp_notacargo_detalle_motivo (id_notacargo, id_motivo, precio_unitario)
SELECT 
	cxp_nd.id_notacargo,
	cxp_nd.id_motivo,
	cxp_nd.subtotal_notacargo
FROM cp_notadecargo cxp_nd
WHERE cxp_nd.id_notacargo NOT IN (SELECT cxp_nd_det.id_notacargo
								FROM cp_notacargo_detalle_motivo cxp_nd_det)
	AND cxp_nd.id_motivo IS NOT NULL;");
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
$maxRows = 34;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT 
	cxp_nd.id_notacargo,
	cxp_nd.id_empresa,
	cxp_nd.numero_notacargo,
	cxp_nd.numero_control_notacargo,
	cxp_nd.fecha_notacargo,
	cxp_nd.fecha_vencimiento_notacargo,
	cxp_nd.fecha_origen_notacargo,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	prov.direccion AS direccion_proveedor,
	prov.telefono,
	prov.otrotelf,
	prov_cred.diascredito,
	cxp_nd.tipo_pago_notacargo AS condicionDePago,
	cxp_nd.id_modulo,
	cxp_nd.subtotal_notacargo,
	cxp_nd.subtotal_descuento_notacargo,
	cxp_nd.monto_exento_notacargo,
	cxp_nd.monto_exonerado_notacargo,
	cxp_nd.observacion_notacargo,
	motivo.id_motivo,
	motivo.descripcion AS descripcion_motivo,
	vw_pg_empleado.nombre_empleado
FROM cp_notadecargo cxp_nd
	INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
	LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	LEFT JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxp_nd.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE cxp_nd.id_notacargo = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// DETALLES DE LOS MOTIVOS
$queryDetalle = sprintf("SELECT
	cxp_nd_det_motivo.id_notacargo_detalle_motivo,
	cxp_nd_det_motivo.id_notacargo,
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
FROM cp_notacargo_detalle_motivo cxp_nd_det_motivo
	INNER JOIN pg_motivo motivo ON (cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
WHERE cxp_nd_det_motivo.id_notacargo = %s;",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);

if ($totalRowsDetalle == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_notacargo_detalle_motivo,
		NULL AS id_notacargo,
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
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		$posY = 9;
		imagestring($img,1,300,$posY,str_pad("CXP - NOTA DE CARGO", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. NOTA CARGO"),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numero_notacargo'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. CONTROL"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowEncabezado['numero_control_notacargo'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_notacargo'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_vencimiento_notacargo'])),$textColor);
		
		$posY += 9;
		if ($rowEncabezado['condicionDePago'] == 0 && $rowEncabezado['diascredito'] != 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($rowEncabezado['diascredito'])." DIAS",$textColor);
		}
		
		$posY = 28;
		imagestring($img,1,0,$posY,strtoupper($rowEncabezado['nombre_proveedor']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanProvCxP).": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id_proveedor'], 8, " ", STR_PAD_LEFT),$textColor);
		
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
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 28, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,255,$posY,str_pad(utf8_decode("CANTIDAD"), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,strtoupper(str_pad(utf8_decode($spanPrecioUnitario), 15, " ", STR_PAD_BOTH)),$textColor);
		imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	if (isset($tieneDetalle)) {
		$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacion_notacargo']), 51);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
				if ($indice == 0) {
					imagestring($img,1,255,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotal_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotal_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}
			}
		}
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['id_motivo'].".- ".$rowEncabezado['descripcion_motivo'],0,51)),$textColor);
	} else {
		$posY += 9;
		imagestring($img,1,0,$posY,elimCaracter($rowDetalle['id_motivo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,28)),$textColor);
		imagestring($img,1,255,$posY,str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,315,$posY,str_pad(number_format($rowDetalle['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(number_format($rowDetalle['cantidad'] * $rowDetalle['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
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
			
			$posY = 460;
			
			$queryGasto = sprintf("SELECT
				cxp_nd_gasto.id_notacargo_gastos,
				cxp_nd_gasto.id_notacargo,
				cxp_nd_gasto.tipo,
				cxp_nd_gasto.porcentaje_monto,
				cxp_nd_gasto.monto,
				cxp_nd_gasto.estatus_iva,
				cxp_nd_gasto.id_iva,
				cxp_nd_gasto.iva,
				gasto.*
			FROM pg_gastos gasto
				INNER JOIN cp_notacargo_gastos cxp_nd_gasto ON (gasto.id_gasto = cxp_nd_gasto.id_gastos)
			WHERE cxp_nd_gasto.id_notacargo = %s;",
				valTpDato($idDocumento, "text"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				$porcGasto = $rowGasto['porcentaje_monto'];
				$montoGasto = $rowGasto['monto'];
				
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper($rowGasto['nombre']),$textColor);
				imagestring($img,1,90,$posY,":",$textColor);
				imagestring($img,1,100,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,145,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				if ($rowGasto['estatus_iva'] == 0) {
					$totalGastosSinIva += $rowGasto['monto'];
				} else if ($rowGasto['estatus_iva'] == 1) {
					$totalGastosConIva += $rowGasto['monto'];
				}
				
				$totalGasto += $rowGasto['monto'];
			}
			
			
			$arrayObservacionDcto = (isset($tieneDetalle)) ? "" : str_split(strtoupper($rowEncabezado['observacion_notacargo']), 50);
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
			imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotal_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotal_notacargo'] > 0) ? ($rowEncabezado['subtotal_descuento_notacargo'] * 100) / $rowEncabezado['subtotal_notacargo'] : 0;
			if ($rowEncabezado['subtotal_descuento_notacargo'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,345,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotal_descuento_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($totalGastosConIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaDcto = sprintf("SELECT
				iva.observacion,
				cxp_nd_iva.baseimponible AS base_imponible,
				cxp_nd_iva.iva,
				cxp_nd_iva.subtotal_iva
			FROM cp_notacargo_iva cxp_nd_iva
				INNER JOIN pg_iva iva ON (cxp_nd_iva.id_iva = iva.idIva)
			WHERE cxp_nd_iva.id_notacargo = %s;",
				valTpDato($idDocumento, "text"));
			$rsIvaDcto = mysql_query($queryIvaDcto);
			if (!$rsIvaDcto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowIvaDcto = mysql_fetch_assoc($rsIvaDcto)) {
				$posY += 9;
				imagestring($img,1,255,$posY,"BASE IMPONIBLE",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaDcto['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$posY += 9;
				imagestring($img,1,255,$posY,substr($rowIvaDcto['observacion'],0,14),$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,360,$posY,str_pad(number_format($rowIvaDcto['iva'], 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowIvaDcto['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				$totalIva += $rowIvaDcto['subtotal_iva'];
			}
			
			if ($totalGastosSinIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS S/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor); // <---
			}
			
			if ($rowEncabezado['monto_exento_notacargo'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"EXENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['monto_exento_notacargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotal_notacargo'] - $rowEncabezado['subtotal_descuento_notacargo'] + $totalIva + $totalGastosSinIva + $totalGastosConIva;
			$posY += 7;
			imagestring($img,1,255,$posY,"TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,2,380,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."cxp_nd".$pageNum.".png";
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