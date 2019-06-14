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
$idPedido = $_GET["valBusq"];

// BUSCA LOS DATOS DEL DOCUMENTO
	$query = sprintf("SELECT 
						pedido.id_pedido_financiamiento,
						pedido.estatus_pedido,
						pedido.id_cliente,
						pedido.id_notadecargo_cxc,
						pedido.id_empresa,
						pedido.fecha_pedido,
						pedido.numero_pagos,
						pedido.fecha_financiamiento,
						pedido.fecha_fin_financiamiento,
						cliente.nombre,
						cliente.ci,
						cliente.telf,
						pedido.numeracion_pedido AS numeracion,
						empresa.nombre_empresa AS empresa,
						empresa.rif AS rif,
						CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
						pedido.fecha_financiamiento AS fecha_inicial,
						pedido.fecha_fin_financiamiento AS fecha_final,
						pedido.tipo_interes,
						CONCAT_WS(' ',pedido.interes_financiamiento,'%s') AS interes,
						CONCAT_WS(' ',pedido.cuotas_duracion, 
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_duracion_plazo)) AS duracion,
						CONCAT_WS(' ',pedido.numero_pagos,'pagos en',
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_frecuencia_plazo)) AS frecuencia,
						pedido.monto_financiamiento_documentos AS total_inicial,
						pedido.total_intereses AS total_intereses,
						pedido.total_monto_financiar AS total_cuotas,
						CONCAT_WS(' ',empleado.nombre_empleado,empleado.apellido) AS nombre_empleado,
						empleado.email AS correo_empleado
					FROM fi_pedido pedido
						INNER JOIN cj_cc_cliente cliente ON (pedido.id_cliente = cliente.id)
						INNER JOIN pg_empresa empresa ON (pedido.id_empresa = empresa.id_empresa)
						INNER JOIN fi_plazos plazo ON (pedido.id_duracion_plazo = plazo.id_plazo)
						INNER JOIN pg_empleado empleado ON (pedido.id_empleado = empleado.id_empleado)
					WHERE pedido.id_pedido_financiamiento = %s", 
			'%', valTpDato($idPedido, "int"));

$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPedido = mysql_fetch_assoc($rs);


$idEmpresa = $rowPedido['id_empresa'];
$tipoPago =  "CREDITO";

// BUSCA LOS DATOS DEL CLIENTE
$queryCliente = sprintf("SELECT
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
	cliente.telf,
	cliente_cred.diascredito
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	LEFT JOIN cj_cc_credito cliente_cred ON (cliente_emp.id_cliente_empresa = cliente_cred.id_cliente_empresa)
WHERE cliente.id = %s
	AND cliente_emp.id_empresa = %s;",
	valTpDato($rowPedido['id_cliente'], "int"),
	valTpDato($idEmpresa, "int"));
$rsCliente = mysql_query($queryCliente);
if (!$rsCliente) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsCliente = mysql_num_rows($rsCliente);
$rowCliente = mysql_fetch_assoc($rsCliente);

	//CREANDO IMAGEN DE PDF		
	$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$backgroundGris = imagecolorallocate($img, 230, 230, 230);
			$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
			
			$posY = 0;
			
			imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
			imagestring($img,1,0,$posY,str_pad("FINANCIAMIENTO", 94, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"EMPRESA",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($rowPedido['empresa']),$textColor);
			imagestring($img,1,275,$posY,str_pad("PEDIDO DE FINANCIAMIENTO", 34, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,$spanRIF.": ".strtoupper($rowPedido['rif']),$textColor);
			imagestring($img,1,275,$posY,"NRO. PEDIDO",$textColor);
			imagestring($img,1,375,$posY,": ".$rowPedido['numeracion'],$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"VENDEDOR",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($rowPedido['nombre_empleado']),$textColor);
			imagestring($img,1,275,$posY,"FECHA PEDIDO",$textColor);
			imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowPedido['fecha_pedido'])),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"CORREO",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($rowPedido['correo_empleado']),$textColor);
			imagestring($img,1,275,$posY,"FECHA PRIMER PAGO",$textColor);
			imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowPedido['fecha_financiamiento'])),$textColor);
			
			$posY += 9;
			imagestring($img,1,275,$posY,"FECHA UTLIMO PAGO",$textColor);
			imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowPedido['fecha_fin_financiamiento'])),$textColor);

			$posY += 9;
			imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad("DATOS DEL CLIENTE", 94, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,310,$posY,utf8_decode("CÓDIGO"),$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($rowCliente['id']),$textColor);

//DATOS DEL CLIENTE
			
			$posY += 9;
			imagestring($img,1,0,$posY,"CLIENTE",$textColor);
			imagestring($img,1,45,$posY,": ".strtoupper($rowCliente['nombre_cliente']),$textColor);
			imagestring($img,1,310,$posY,$spanClienteCxC,$textColor);
			imagestring($img,1,375,$posY,": ".$rowCliente['ci_cliente'],$textColor);
			
			$direccionCliente = strtoupper(elimCaracter($rowCliente['direccion_cliente'],";"));
			$posY += 9;
			imagestring($img,1,0,$posY,utf8_decode("DIRECCIÓN"),$textColor);/////////////////////////////////////////
			imagestring($img,1,45,$posY,": ".trim(substr($direccionCliente,0,50)),$textColor);
			imagestring($img,1,310,$posY,utf8_decode("TELÉFONO"),$textColor);///////////////////////////////////////////////////
			imagestring($img,1,375,$posY,": ".$rowCliente['telf'],$textColor);
			
			$posY += 9;
			imagestring($img,1,55,$posY,trim(substr($direccionCliente,50,50)),$textColor);
			if ($rowCliente['diascredito'] > 0) {
				imagestring($img,1,310,$posY,utf8_decode("DIAS CRÉDITO"),$textColor);///////////////////////////////////////////////////
				imagestring($img,1,375,$posY,": ".number_format($rowCliente['diascredito']).utf8_decode(" DÍAS"),$textColor);
			}
			
			$posY += 9;
			imagestring($img,1,55,$posY,trim(substr($direccionCliente,100,50)),$textColor);
			
			$posY += 9;
			
//DATOS DEL FINANCIAMIENTO		

			imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad("DATOS DEL FINANCIAMIENTO", 94, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 18;
			imagestring($img,1,0,$posY,"TIPO DE INTERES",$textColor);
			
			switch ($rowPedido['tipo_interes']){
				case 1: $tipoInteres = 'SIMPLE'; break;
				case 2: $tipoInteres = 'COMPUESTO'; break;
			}
			imagestring($img,1,80,$posY,": ".strtoupper($tipoInteres),$textColor);
			
			imagestring($img,1,170,$posY,"DURACION DE PAGO",$textColor);
			imagestring($img,1,255,$posY,": ".strtoupper($rowPedido['duracion']),$textColor);
				
			
			$posY += 9;
			imagestring($img,1,0,$posY,"INTERES",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($rowPedido['interes']),$textColor);
			
			imagestring($img,1,170,$posY,"FRECUENCIA DE PAGOS",$textColor);
			imagestring($img,1,270,$posY,": ".strtoupper($rowPedido['frecuencia']),$textColor);

//CABECERA DE LOS DOCUMENTOS ASOCIADOS
			$posY += 18;
				
			imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad("DOCUMENTOS ASOCIADOS", 94, " ", STR_PAD_BOTH),$textColor);

			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundColor);
			imagestring($img,1,0,$posY,str_pad("CODIGO", 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,115,$posY,utf8_decode(str_pad("DESCRIPCIÓN", 21, " ", STR_PAD_BOTH)),$textColor);
			imagestring($img,1,295,$posY,strtoupper(str_pad("FECHA", 12, " ", STR_PAD_BOTH)),$textColor);
			imagestring($img,1,395,$posY,str_pad("TOTAL", 15, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			

// DOCUMENTOS ASOCIADOS AL PEDIDO DE FINANCIAMIENTO
		$queryAdicionales = sprintf("SELECT 
									*
								FROM fi_documento doc
								WHERE doc.id_pedido_financiamiento = %s;",
						valTpDato($idPedido,"int"));
			$rsDocumento = mysql_query($queryAdicionales);
			if (!$rsDocumento) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsDocumento = mysql_num_rows($rsDocumento);

			$posY += 9;
			
			while ($rowDocumento = mysql_fetch_assoc($rsDocumento)) {
				
				if($rowDocumento['tipo_documento'] == 'FACTURA'){
					imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
					imagestring($img,1,0,$posY,str_pad('FA-'.$rowDocumento['id_documento_tabla'], 22, " ", STR_PAD_BOTH),$textColor);
				}else{
					imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
					imagestring($img,1,0,$posY,str_pad('FI-'.$rowDocumento['id_documento'], 22, " ", STR_PAD_BOTH),$textColor);
				}
				if(isset($rowDocumento['observacion_documento'])){
					imagestring($img,1,95,$posY,utf8_decode(str_pad(strtoupper($rowDocumento['descripcion_documento']), 21, " ", STR_PAD_BOTH)),$textColor);
					imagestring($img,1,295,$posY,strtoupper(str_pad(date(spanDateFormat,strtotime($rowDocumento['fecha_documento'])), 12, " ", STR_PAD_BOTH)),$textColor);
					imagestring($img,2,395,$posY-2,str_pad(formatoNumero($rowDocumento['saldo_documento']), 15, " ", STR_PAD_BOTH),$textColor);
					$posY += 9;
					imagestring($img,1,95,$posY,utf8_decode(str_pad(strtoupper(substr($rowDocumento['observacion_documento'],"0","27")), 21, " ", STR_PAD_BOTH)),$textColor);
					$posY += 9;
					imagestring($img,1,95,$posY,utf8_decode(str_pad(strtoupper(substr($rowDocumento['observacion_documento'],"27","100")), 21, " ", STR_PAD_BOTH)),$textColor);
				}else{
					imagestring($img,1,95,$posY,utf8_decode(str_pad(strtoupper($rowDocumento['descripcion_documento']), 21, " ", STR_PAD_BOTH)),$textColor);
					imagestring($img,1,295,$posY,strtoupper(str_pad(date(spanDateFormat,strtotime($rowDocumento['fecha_documento'])), 12, " ", STR_PAD_BOTH)),$textColor);
					imagestring($img,2,395,$posY-2,str_pad(formatoNumero($rowDocumento['saldo_documento']), 15, " ", STR_PAD_BOTH),$textColor);
				}
				$posY += 9;
			}
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 260, $posY, 469, $posY+12, $backgroundGris);
			imagestring($img,2,260,$posY,"SUBTOTAL",$textColor);
			imagestring($img,2,340,$posY,":",$textColor);
			imagestring($img,2,395,$posY-2,str_pad(formatoNumero($rowPedido['total_inicial']), 15, " ", STR_PAD_BOTH),$textColor);

			//DETERMINANDO ADICIONALES
			
			$queryAdicional = sprintf("SELECT
											adi.id_adicional,
											adi.nombre_adicional,
											fin_adi.tipo_adicional,
											fin_adi.monto_adicional
										FROM fi_financiamiento_adicionales fin_adi
										INNER JOIN  fi_adicionales adi ON (adi.id_adicional = fin_adi.id_adicional)
										WHERE fin_adi.id_pedido_financiamiento = %s",
					valTpDato($idPedido, "int"));
			$rsAdicional = mysql_query($queryAdicional);
			if (!$rsAdicional) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsAdicional = mysql_num_rows($rsAdicional);
			
			if($totalRowsAdicional >0){
				
				$posY += 27;
				
			//CABECERA DE LOS ADICIONALES ASOCIADOS
			
				imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
				imagestring($img,1,0,$posY,str_pad("ADICIONALES ASOCIADOS", 94, " ", STR_PAD_BOTH),$textColor);
				
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
				$posY += 9;
				imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundColor);
				imagestring($img,1,0,$posY,str_pad("ID", 22, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,115,$posY,utf8_decode(str_pad("NOMBRE", 21, " ", STR_PAD_BOTH)),$textColor);
				imagestring($img,1,295,$posY,strtoupper(str_pad("TIPO", 12, " ", STR_PAD_BOTH)),$textColor);
				imagestring($img,1,395,$posY,str_pad("TOTAL ADICIONAL", 15, " ", STR_PAD_BOTH),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);			
				
			//ADICIONALES ASOCIADOS AL PEDIDO DE FINANCIAMIENTO
	
			
			$posY += 9;
			
			while($rowAdicional = mysql_fetch_assoc($rsAdicional)){
	
				imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
				imagestring($img,1,0,$posY,str_pad($rowAdicional['id_adicional'], 22, " ", STR_PAD_BOTH),$textColor);			
				imagestring($img,1,95,$posY,utf8_decode(str_pad(strtoupper($rowAdicional['nombre_adicional']), 21, " ", STR_PAD_BOTH)),$textColor);
				imagestring($img,1,295,$posY,utf8_decode(str_pad(strtoupper($rowAdicional['tipo_adicional']), 12, " ", STR_PAD_BOTH)),$textColor);
				
				////////// CALCULOS PARA EL TOTAL
				
				$contNumeroPagos = $rowPedido['numero_pagos']; 
				
				if($rowAdicional['tipo_adicional'] == 'Cuota'){
					while($contNumeroPagos){
						$totalAdicional += $rowAdicional['monto_adicional'];	 
						$contNumeroPagos--;
					}
				}else{
					$totalAdicional = $rowAdicional['monto_adicional'];
				}
				
				imagestring($img,2,395,$posY-2,str_pad(formatoNumero($totalAdicional), 15, " ", STR_PAD_BOTH),$textColor);
				
				$totalAdicionales += $totalAdicional;
				
				$posY += 9;
			}
				
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 260, $posY, 469, $posY+12, $backgroundGris);
			imagestring($img,2,260,$posY,"SUBTOTAL",$textColor);
			imagestring($img,2,340,$posY,":",$textColor);
			imagestring($img,2,395,$posY-2,str_pad(formatoNumero($totalAdicionales), 15, " ", STR_PAD_BOTH),$textColor);	
		}
		
//FOOTER DE RESUMEN DE MONTOS DE DOCUMENTOS, INTERESES Y MONTO TOTAL.

			$posY = 460;
				
			$posY += 9;
			imagestring($img,2,260,$posY,"CAPITAL",$textColor);
			imagestring($img,2,340,$posY,":",$textColor);
			imagestring($img,2,360,$posY,str_pad(formatoNumero($rowPedido['total_inicial']), 18, " ", STR_PAD_LEFT),$textColor);
			$posY += 10;
			imagestring($img,2,260,$posY,"INTERESES",$textColor);
			imagestring($img,2,340,$posY,":",$textColor);
			imagestring($img,2,360,$posY,str_pad(formatoNumero($rowPedido['total_intereses']), 18, " ", STR_PAD_LEFT),$textColor);
			if($totalRowsAdicional >0){
				$posY += 10;
				imagestring($img,2,260,$posY,"ADICIONALES",$textColor);
				imagestring($img,2,340,$posY,":",$textColor);
				imagestring($img,2,360,$posY,str_pad(formatoNumero($totalAdicionales), 18, " ", STR_PAD_LEFT),$textColor);
			}
			$posY += 12;
			imagestring($img,1,260,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			$posY += 8;
			imagefilledrectangle($img, 260, $posY, 469, $posY+12, $backgroundAzul);
			imagestring($img,2,260,$posY,"SUBTOTAL",$textColor);
			imagestring($img,2,340,$posY,":",$textColor);
			imagestring($img,2,360,$posY,str_pad(formatoNumero($rowPedido['total_cuotas']), 18, " ", STR_PAD_LEFT),$textColor);

			
			$arrayImg[] = "tmp/"."pedido_venta1.png";
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

$pdf->nombreRegistrado = $rowPedido['nombre_empleado'];
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

// FORMATO DE NUMEROS

function formatoNumero($monto){
	return number_format($monto, 2, ".", ",");
}

?>