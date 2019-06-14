<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

// INSERTA LOS DETALLES DE LAS NOTAS DE DEBITO QUE NO TIENEN
$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_detalle_motivo (id_nota_cargo, id_motivo, precio_unitario)
SELECT 
	cxc_nd.idNotaCargo,
	cxc_nd.id_motivo,
	cxc_nd.subtotalNotaCargo
FROM cj_cc_notadecargo cxc_nd
WHERE cxc_nd.idNotaCargo NOT IN (SELECT cxc_nd_det.id_nota_cargo
								FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det)
	AND cxc_nd.id_motivo IS NOT NULL;");
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
	cxc_nd.idNotaCargo,
	cxc_nd.id_empresa,
	cxc_nd.numeroNotaCargo,
	cxc_nd.numeroControlNotaCargo,
	cxc_nd.fechaRegistroNotaCargo,
	cxc_nd.fechaVencimientoNotaCargo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	cxc_nd.tipoNotaCargo AS condicionDePago,
	cxc_nd.idDepartamentoOrigenNotaCargo,
	cxc_nd.subtotalNotaCargo,
	cxc_nd.descuentoNotaCargo,
	cxc_nd.montoExentoNotaCargo,
	cxc_nd.montoExoneradoNotaCargo,
	cxc_nd.observacionNotaCargo,
                A.fecha_reconversion as reconvercion,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador
FROM cj_cc_notadecargo cxc_nd
	INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nd.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
                LEFT JOIN cj_cc_notacargo_reconversion A ON (cxc_nd.idNotaCargo = A.id_notacargo)
WHERE cxc_nd.idNotaCargo = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// DETALLES DE LOS MOTIVOS
$queryDetalle = sprintf("SELECT
	cxc_nd_det_motivo.id_nota_cargo_detalle_motivo,
	cxc_nd_det_motivo.id_nota_cargo,
	motivo.id_motivo,
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
	
	cxc_nd_det_motivo.cantidad,
	cxc_nd_det_motivo.precio_unitario
FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
	INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
WHERE cxc_nd_det_motivo.id_nota_cargo = %s;",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);

if ($totalRowsDetalle == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_nota_cargo_detalle_motivo,
		NULL AS id_nota_cargo,
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
		imagestring($img,1,300,$posY,str_pad("CXC - NOTA DE CARGO", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. NOTA CARGO"),$textColor);
		imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeroNotaCargo'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("NRO. CONTROL"),$textColor);
		imagestring($img,1,375,$posY,": ".$rowEncabezado['numeroControlNotaCargo'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaRegistroNotaCargo'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
		imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaVencimientoNotaCargo'])),$textColor);
		
		$posY += 9;
		if ($rowEncabezado['condicionDePago'] == 0 && $rowEncabezado['diascredito'] != 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($rowEncabezado['diascredito'])." DIAS",$textColor);
		}
		
		$posY = 28;
		imagestring($img,1,0,$posY,strtoupper($rowEncabezado['nombre_cliente']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
		imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id_cliente'], 8, " ", STR_PAD_LEFT),$textColor);
		
		$direccionCliente = strtoupper(str_replace(",", "", elimCaracter(utf8_encode($rowEncabezado['direccion_cliente']),";")));
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
		$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacionNotaCargo']), 51);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice => $valor) {
				$posY += 9;
				imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
				if ($indice == 0) {
					imagestring($img,1,260,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}
			}
		}
	} else if ($totalRowsDetalle > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,elimCaracter($rowDetalle['id_motivo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($rowDetalle['descripcion'],0,28)),$textColor);
		imagestring($img,1,260,$posY,str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
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
				cxc_nd_gasto.id_nota_cargo_gasto,
				cxc_nd_gasto.id_nota_cargo,
				cxc_nd_gasto.tipo,
				cxc_nd_gasto.porcentaje_monto,
				cxc_nd_gasto.monto,
				cxc_nd_gasto.estatus_iva,
				cxc_nd_gasto.id_iva,
				cxc_nd_gasto.iva,
				gasto.*
			FROM pg_gastos gasto
				INNER JOIN cj_cc_nota_cargo_gasto cxc_nd_gasto ON (gasto.id_gasto = cxc_nd_gasto.id_gasto)
			WHERE cxc_nd_gasto.id_nota_cargo = %s;",
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
			
			
			$arrayObservacionDcto = (isset($tieneDetalle)) ? "" : wordwrap(str_replace("\n","<br>",str_replace(";", "", ((isset($verObservacion)) ? "" : $rowEncabezado['observacionNotaCargo']))), 47, "<br>");
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
			
			$posY = 460;
			
			$posY += 9;
			imagestring($img,1,255,$posY,"SUB TOTAL",$textColor);
			imagestring($img,1,325,$posY,":",$textColor);
			imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			
			$porcDescuento = ($rowEncabezado['subtotalNotaCargo'] > 0) ? ($rowEncabezado['descuentoNotaCargo'] * 100) / $rowEncabezado['subtotalNotaCargo'] : 0;
			if ($rowEncabezado['descuentoNotaCargo'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,345,$posY,str_pad(number_format($porcDescuento, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['descuentoNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			if ($totalGastosConIva > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$queryIvaDcto = sprintf("SELECT
				iva.observacion,
				cxc_nd_iva.base_imponible,
				cxc_nd_iva.iva,
				cxc_nd_iva.subtotal_iva
			FROM cj_cc_nota_cargo_iva cxc_nd_iva
				INNER JOIN pg_iva iva ON (cxc_nd_iva.id_iva = iva.idIva)
			WHERE cxc_nd_iva.id_nota_cargo = %s;",
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
			
			if ($rowEncabezado['montoExentoNotaCargo'] > 0) {
				$posY += 9;
				imagestring($img,1,255,$posY,"EXENTO",$textColor);
				imagestring($img,1,325,$posY,":",$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['montoExentoNotaCargo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 7;
			imagestring($img,1,255,$posY,str_pad("", 43, "-", STR_PAD_LEFT),$textColor);
			
			$totalFactura = $rowEncabezado['subtotalNotaCargo'] - $rowEncabezado['descuentoNotaCargo'] + $totalIva + $totalGastosSinIva + $totalGastosConIva;
                                               if ($rowEncabezado['reconvercion']==null) {
                                                               if ($rowEncabezado['fechaRegistroNotaCargo']>="2018-08-01" and $rowEncabezado['fechaRegistroNotaCargo']< "2018-08-20") {
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                             $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }else if ($rowEncabezado['fechaRegistroNotaCargo']>="2018-08-20") {
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000,2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                              }else{
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }
                                               }else{

                                                               if ($rowEncabezado['fechaRegistroNotaCargo']>="2018-08-01" and $rowEncabezado['fechaRegistroNotaCargo']< "2018-08-20") {
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                             $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }else if ($rowEncabezado['fechaRegistroNotaCargo']>="2018-08-20") {
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                              }else{
                                                                               $posY += 9;
                                                                               imagestring($img,1,255,$posY,"TOTAL",$textColor);
                                                                               imagestring($img,1,325,$posY,":",$textColor);
                                                                               imagestring($img,2,350,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT),$textColor);
                                                               }

                                               }
                                
                                               
                               }
		$pageNum++;
		$arrayImg[] = "tmp/"."cxc_nd".$pageNum.".png";
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