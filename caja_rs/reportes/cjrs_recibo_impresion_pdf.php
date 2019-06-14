<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");
session_start();
set_time_limit(0);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

include('../../clases/num2letras.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("10","10","10");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idTpDcto = $_GET['idTpDcto']; // 4 = AN, 5 = CH, 6 = TB
$idDocumento = $_GET['id'];
$idRecibo = $_GET["idRecibo"];

if ($idRecibo > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recibo.idReporteImpresion IN (%s)",
		valTpDato($idRecibo, "campo"));
} else if ($idTpDcto > 0 && $idDocumento > 0) {
	if (!in_array($idTpDcto,array(4,5,6))) {
		echo "<script>alert('Tipo de documento invalido para el tipo de recibo.'); window.close(); </script>";
	}
	
	// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recibo.tipoDocumento = (CASE %s
															WHEN 4 THEN 'AN'
															WHEN 5 THEN 'CH'
															WHEN 6 THEN 'TB'
														END)
	AND recibo.idDocumento = %s",
		valTpDato($idTpDcto, "int"),
		valTpDato($idDocumento, "int"));
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recibo.idReporteImpresion = -1");
}

// DATOS DEL RECIBO
$queryRecibo = sprintf("SELECT recibo.*,
	(CASE recibo.tipoDocumento
		WHEN 'AN' THEN 4
		WHEN 'CH' THEN 5
		WHEN 'TB' THEN 6
	END) AS idTipoDeDocumento,
	(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto
	WHERE tipo_dcto.idTipoDeDocumento = (CASE recibo.tipoDocumento
											WHEN 'AN' THEN 4
											WHEN 'CH' THEN 5
											WHEN 'TB' THEN 6
										END)) AS tipo_documento_pagado,
	vw_pg_empleado.nombre_empleado
FROM pg_reportesimpresion recibo
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (recibo.id_empleado_creador = vw_pg_empleado.id_empleado) %s;", $sqlBusq);
$rsRecibo = mysql_query($queryRecibo);
if (!$rsRecibo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsRecibo = mysql_num_rows($rsRecibo);
while ($rowRecibo = mysql_fetch_assoc($rsRecibo)) {
	$idDocumento = $rowRecibo['idDocumento'];
	$idRecibo = $rowRecibo['idReporteImpresion'];
	
	if (!in_array($rowRecibo['idTipoDeDocumento'],array(4,5,6))) {
		echo "<script>alert('Tipo de documento invalido para el tipo de recibo.'); window.close(); </script>";
	}
	
	// ENCABEZADO DE LA TABLA
	$arrayCol = array(
		array("tamano" => "60", "descripcion" => "FECHA PAGO\n"),
		array("tamano" => "100", "descripcion" => "FORMA DE PAGO\n\n"),
		array("tamano" => "70", "descripcion" => "NRO. REF.\n\n"),
		array("tamano" => "140", "descripcion" => "BANCO / CUENTA CLIENTE\n\n"),
		array("tamano" => "140", "descripcion" => "BANCO / CUENTA COMPAÑIA\n\n"),
		array("tamano" => "80", "descripcion" => "MONTO\n\n"));
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'];
	
	switch ($rowRecibo['idTipoDeDocumento']) {
		case 4 : // 4 = ANTICIPO
			$queryFactura = sprintf("SELECT cxc_ant.*,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.direccion,
				cliente.estado,
				cliente.telf
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			WHERE cxc_ant.idAnticipo = %s;",
				valTpDato($idDocumento, "int"));
			
			$queryPago = sprintf("SELECT cxc_pago.*,
				
				(CASE cxc_pago.id_forma_pago
					WHEN 7 THEN
						(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
					WHEN 8 THEN
						(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
					ELSE
						cxc_pago.numeroControlDetalleAnticipo
				END) AS numero_documento,
				
				(CASE cxc_pago.id_forma_pago
					WHEN 8 THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroControlDetalleAnticipo)
				END) AS descripcion_motivo,
				
				(CASE cxc_pago.id_forma_pago
					WHEN 7 THEN
						(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
					WHEN 8 THEN
						(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
				END) AS observacion_documento,
				
				forma_pago.idFormaPago,
				forma_pago.nombreFormaPago,
				concepto_forma_pago.descripcion AS descripcion_concepto_forma_pago,
				cxc_pago.bancoClienteDetalleAnticipo,
				banco_cliente.nombreBanco AS nombre_banco_cliente,
				cxc_pago.numeroCuentaCliente AS numero_cuenta_cliente,
				cxc_pago.bancoCompaniaDetalleAnticipo,
				banco_emp.nombreBanco AS nombre_banco_empresa,
				cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
				
				(SELECT tipo_tarjeta.descripcionTipoTarjetaCredito
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
					INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
				WHERE ret_punto_pago.id_pago = cxc_pago.idDetalleAnticipo
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 4) AS nombre_tarjeta,
				
				caja.descripcion AS nombre_caja,
				vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion AND recibo.tipoDocumento LIKE 'AN')
			WHERE recibo.idReporteImpresion = %s
				AND cxc_pago.idAnticipo = %s;",
				valTpDato($idRecibo, "int"),
				valTpDato($idDocumento, "int"));
			
			break;
		case 5 : // 5 = CHEQUE
			$queryFactura = sprintf("SELECT cxc_ch.*,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.direccion,
				cliente.estado,
				cliente.telf
			FROM cj_cc_cheque cxc_ch
				INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
			WHERE cxc_ch.id_cheque = %s;",
				valTpDato($idDocumento, "int"));
			
			$queryPago = sprintf("SELECT cxc_ch.*,
				cxc_ch.numero_cheque AS numero_documento,
				NULL AS descripcion_motivo,
				NULL AS observacion_documento,
				forma_pago.idFormaPago,
				forma_pago.nombreFormaPago,
				NULL AS descripcion_concepto_forma_pago,
				cxc_ch.id_banco_cliente,
				banco_cliente.nombreBanco AS nombre_banco_cliente,
				cxc_ch.cuenta_cliente AS numero_cuenta_cliente,
				NULL AS id_banco_empresa,
				NULL AS nombre_banco_empresa,
				NULL AS cuentaEmpresa,
				NULL AS nombre_tarjeta,
				caja.descripcion AS nombre_caja,
				vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
			FROM cj_cc_cheque cxc_ch
				INNER JOIN formapagos forma_pago ON (2 = forma_pago.idFormaPago)
				INNER JOIN caja ON (cxc_ch.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_ch.id_banco_cliente = banco_cliente.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_ch.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH')
			WHERE recibo.idReporteImpresion = %s
				AND cxc_ch.id_cheque = %s;",
				valTpDato($idRecibo, "int"),
				valTpDato($idDocumento, "int"));
			
			break;
		case 6 : // 6 = TRANSFERENCIA
			$queryFactura = sprintf("SELECT cxc_tb.*,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.direccion,
				cliente.estado,
				cliente.telf
			FROM cj_cc_transferencia cxc_tb
				INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
			WHERE cxc_tb.id_transferencia = %s;",
				valTpDato($idDocumento, "int"));
			
			$queryPago = sprintf("SELECT cxc_tb.*,
				cxc_tb.numero_transferencia AS numero_documento,
				NULL AS descripcion_motivo,
				NULL AS observacion_documento,
				forma_pago.idFormaPago,
				forma_pago.nombreFormaPago,
				NULL AS descripcion_concepto_forma_pago,
				cxc_tb.id_banco_cliente,
				banco_cliente.nombreBanco AS nombre_banco_cliente,
				cxc_tb.cuenta_cliente AS numero_cuenta_cliente,
				cxc_tb.id_banco_compania,
				banco_emp.nombreBanco AS nombre_banco_empresa,
				cxc_tb.cuenta_compania,
				NULL AS nombre_tarjeta,
				caja.descripcion AS nombre_caja,
				vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
			FROM cj_cc_transferencia cxc_tb
				INNER JOIN formapagos forma_pago ON (4 = forma_pago.idFormaPago)
				INNER JOIN caja ON (cxc_tb.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_tb.id_banco_cliente = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (cxc_tb.id_banco_compania = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_tb.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN pg_reportesimpresion recibo ON (cxc_tb.id_transferencia = recibo.idDocumento AND cxc_tb.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'TB')
			WHERE recibo.idReporteImpresion = %s
				AND cxc_tb.id_transferencia = %s;",
				valTpDato($idRecibo, "int"),
				valTpDato($idDocumento, "int"));
			
			break;
	}
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	switch ($rowRecibo['idTipoDeDocumento']) {
		case 4 : // 4 = ANTICIPO
			$idEmpresa = $rowFactura['id_empresa'];
			$nroDocumento = $rowFactura['numeroAnticipo'];
			$observacionDcto = $rowFactura['observacionesAnticipo'];
			break;
		case 5 : // 5 = CHEQUE
			$idEmpresa = $rowFactura['id_empresa'];
			$nroDocumento = $rowFactura['numero_cheque'];
			$observacionDcto = $rowFactura['observacion_cheque'];
			break;
		case 6 : // 6 = TRANSFERENCIA
			$idEmpresa = $rowFactura['id_empresa'];
			$nroDocumento = $rowFactura['numero_transferencia'];
			$observacionDcto = $rowFactura['observacion_transferencia'];
			break;
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig403 = mysql_num_rows($rsConfig403);
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	
	$centimos = (in_array($rowConfig403['valor'], array(1))) ? 1 : 2; // 1 = Centimo, 2 = Centavo
	
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
	
	$pdf->AddPage();
	
	$rsPago = mysql_query($queryPago);
	if (!$rsPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsPago = mysql_num_rows($rsPago);
	if ($totalRowsPago == 0 || !($rowFactura['estatus'] == 1)) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		
		// MARCA DE AGUA
		$src = imagecreatefrompng("../../img/dcto_anulado.png");
		//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
		if(!imagecopyresampled($img, $src, 0, 10, 0, 0, 470, 500, 470, 500)){ die ("Error marca de agua"); }
		$arrayImg[] = "tmp/"."dcto_anulado".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		
		if (isset($arrayImg)) {
			foreach ($arrayImg as $indice => $valor) {
				$pdf->Image($valor, 15, 60, 580, 688);
			}
		}
	}
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,"RECIBO DE PAGO",0,0,'C');
	
	$pdf->Ln();
	
	if (strlen($nombreCajaPpal) > 0) {
		$pdf->SetFont('Arial','',8);
		$pdf->Cell($totalAncho,10,"(".$nombreCajaPpal.")",0,0,'C');
		
		$pdf->Ln();
	}
	
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',10);
	
	$pdf->Cell(500,16,utf8_decode("NRO. RECIBO: "),0,0,'R');
	$pdf->SetFont('Arial','B',16);
	$pdf->Cell(90,16,$rowRecibo['numeroReporteImpresion'],0,0,'C');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',10);
	
	$pdf->Cell(500,14,utf8_decode("FECHA EMISIÓN: "),0,0,'R');
	$pdf->Cell(90,14,date(spanDateFormat, strtotime($rowRecibo['fechaDocumento'])),0,0,'C');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','B',10);
	
	$pdf->Cell($totalAncho,14,utf8_decode(strtoupper("NRO. ".$rowRecibo['tipo_documento_pagado'].": ".$nroDocumento)),0,0,'C');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',10);
	
	$pdf->Cell(370,14,utf8_decode("ID: ".$rowFactura['id_cliente']),0,0,'L');
	$pdf->Cell(220,14,utf8_decode($spanClienteCxC.": ".$rowFactura['ci_cliente']),0,0,'L');
	$pdf->Ln();
	
	$pdf->Cell(370,14,utf8_decode("CLIENTE: ".$rowFactura['nombre_cliente']),0,0,'L');
	$pdf->Cell(220,14,utf8_decode("TELÉFONO: ".$rowFactura['telf']),0,0,'L');
	$pdf->Ln();
	
	$pdf->Ln();
	
	$posY = $pdf->GetY();
	$posX = $pdf->GetX();
	
	$pdf->SetFont('Arial','',9);
	
	foreach ($arrayCol as $indice => $valor) {
		$pdf->SetY($posY);
		$pdf->SetX($posX);
		
		$pdf->MultiCell($arrayCol[$indice]['tamano'],14,utf8_decode($arrayCol[$indice]['descripcion']),1,'C',true);
		
		$posX += $arrayCol[$indice]['tamano'];
	}
	
	$arrayTotal[9] = NULL;
	while ($rowPago = mysql_fetch_assoc($rsPago)) {
		// RESTAURACION DE COLOR Y FUENTE
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
		$contFila++;
		
		switch ($rowRecibo['idTipoDeDocumento']) {
			case 4 : // 4 = ANTICIPO
				$idPago = $rowPago['idDetalleAnticipo'];
				$fechaPago = $rowPago['fechaPagoAnticipo'];
				$montoPagado = $rowPago['montoDetalleAnticipo'];
				break;
			case 5 : // 5 = CHEQUE
				$idPago = $rowPago['id_cheque'];
				$fechaPago = $rowPago['fecha_cheque'];
				$montoPagado = $rowPago['total_pagado_cheque'];
				break;
			case 6 : // 6 = TRANSFERENCIA
				$idPago = $rowPago['id_transferencia'];
				$fechaPago = $rowPago['fecha_transferencia'];
				$montoPagado = $rowPago['total_pagado_transferencia'];
				break;
		}
		
		switch($rowPago['estatus']) {
			case 1 : $estatusPago = ""; break;
			case 2 : $estatusPago = "\nPAGO PENDIENTE"; break;
			default : $estatusPago = "\nPAGO ANULADO"; break;
		}
		
		// Table with 20 rows and 4 columns
		$pdf->SetWidths(array(
			$arrayCol[0]['tamano'],
			$arrayCol[1]['tamano'],
			$arrayCol[2]['tamano'],
			$arrayCol[3]['tamano'],
			$arrayCol[4]['tamano'],
			$arrayCol[5]['tamano']));
		$pdf->SetAligns(array(
			"C",
			"C",
			"R",
			"L",
			"L",
			"R"));
		$pdf->Row(array(
			date(spanDateFormat, strtotime($fechaPago)),
			strtoupper($rowPago['nombreFormaPago'].
				((strlen($rowPago['nombre_tarjeta']) > 0) ? " (".$rowPago['nombre_tarjeta'].")" : "").
				((strlen($rowPago['descripcion_concepto_forma_pago']) > 0) ? "\n(".$rowPago['descripcion_concepto_forma_pago'].")" : "").
				$estatusPago.
				((strlen($rowPago['nombre_empleado_anulado']) > 0) ? "\nAnulado por: ".$rowPago['nombre_empleado_anulado']."\n(".date(spanDateFormat, strtotime($rowPago['fecha_anulado'])).")" : "")),
			($rowPago['numero_documento']),
			strtoupper($rowPago['nombre_banco_cliente'].
				((strlen($rowPago['numero_cuenta_cliente']) > 1) ? "\n".$rowPago['numero_cuenta_cliente'] : "")),
			strtoupper($rowPago['nombre_banco_empresa']),
			$rowMoneda['abreviacion'].number_format($montoPagado, 2, ".", ",")), $fill);
		
		/*$pdf->Cell($arrayCol[0]['tamano'],14,date(spanDateFormat, strtotime($fechaPago)),'LR',0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,($rowPago['nombreFormaPago']." ".$rowPago['nombre_tarjeta'].$estatusPago),'LR',0,'L',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,($rowPago['numero_documento']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[3]['tamano'],14,($rowPago['nombre_banco_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[4]['tamano'],14,($rowPago['nombre_banco_empresa']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,$rowMoneda['abreviacion'].number_format($montoPagado, 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();*/
		
		if ($rowPago['idFormaPago'] == 3) { // 3 = Deposito
			$pdf->SetFont('Arial','',8);
			
			$arrayCol2 = array(
				array("tamano" => "60", "descripcion" => "."),
				array("tamano" => "100", "descripcion" => "FORMA DE PAGO\n"),
				array("tamano" => "70", "descripcion" => "NRO. REF.\n"),
				array("tamano" => "140", "descripcion" => "BANCO CLIENTE\n"),
				array("tamano" => "140", "descripcion" => "CUENTA CLIENTE\n"),
				array("tamano" => "77", "descripcion" => "MONTO\n"),
				array("tamano" => "3", "descripcion" => "."));
			
			$pdf->MultiCell($totalAncho,0,"",1,'C',true); // cierra linea de tabla
			
			$pdf->Ln(3);
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			$pdf->SetFillColor(204,204,204);
			foreach ($arrayCol2 as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
				
				$pdf->MultiCell($arrayCol2[$indice]['tamano'],10,(($arrayCol2[$indice]['descripcion'] != ".") ? utf8_decode($arrayCol2[$indice]['descripcion']) : ""),(($arrayCol2[$indice]['descripcion'] != ".") ? 1 : 0),'C',(($arrayCol2[$indice]['descripcion'] != ".") ? true : false));
				
				$posX += $arrayCol2[$indice]['tamano'];
			}
			
			switch ($rowRecibo['idTipoDeDocumento']) {
				case 4 : // 4 = ANTICIPO
					$queryDeposito = sprintf("SELECT
						cxc_pago_deposito_det.numero_cheque,
						forma_pago.idFormaPago,
						forma_pago.nombreFormaPago,
						cxc_pago_deposito_det.idBanco,
						banco_cliente.nombreBanco AS nombre_banco_cliente,
						cxc_pago_deposito_det.numero_cuenta AS numero_cuenta_cliente,
						cxc_pago_deposito_det.monto
					FROM cj_cc_det_pagos_deposito_anticipos cxc_pago_deposito_det
						INNER JOIN formapagos forma_pago ON (cxc_pago_deposito_det.idFormaPago = forma_pago.idFormaPago)
						LEFT JOIN bancos banco_cliente ON (cxc_pago_deposito_det.idBanco = banco_cliente.idBanco)
					WHERE cxc_pago_deposito_det.idDetalleAnticipo = %s
						AND cxc_pago_deposito_det.idCaja = %s;",
						valTpDato($idPago, "int"),
						valTpDato($rowPago['idCaja'], "int"));
					break;
			}
			$rsDeposito = mysql_query($queryDeposito);
			if (!$rsDeposito) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsDeposito = mysql_num_rows($rsDeposito);
			$contFila2 = 0;
			$arrayTotalDeposito[5] = NULL;
			while ($rowDeposito = mysql_fetch_assoc($rsDeposito)) {
				// RESTAURACION DE COLOR Y FUENTE
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				$contFila2++;
				
				$pdf->Line($pdf->GetX(), $pdf->GetY()-16, $pdf->GetX(), $pdf->GetY()+26);
				$pdf->Cell($arrayCol2[0]['tamano'],12,"","",0,'C',false);
				$pdf->Cell($arrayCol2[1]['tamano'],12,($rowDeposito['nombreFormaPago']),'LRB',0,'C',true);
				$pdf->Cell($arrayCol2[2]['tamano'],12,($rowDeposito['numero_cheque']),'LRB',0,'R',true);
				$pdf->Cell($arrayCol2[3]['tamano'],12,($rowDeposito['nombre_banco_cliente']),'LRB',0,'L',true);
				$pdf->Cell($arrayCol2[4]['tamano'],12,($rowDeposito['numero_cuenta_cliente']),'LRB',0,'C',true);
				$pdf->Cell($arrayCol2[5]['tamano'],12,$rowMoneda['abreviacion'].number_format($rowDeposito['monto'], 2, ".", ","),'LRB',0,'R',true);
				$pdf->Cell($arrayCol2[6]['tamano'],12,"",'L',0,'C',false);
				$pdf->Line($pdf->GetX(), $pdf->GetY()-16, $pdf->GetX(), $pdf->GetY()+26);
				$pdf->Ln();
				
				//$fill = !$fill;
				
				$arrayTotalDeposito[5] += $rowDeposito['monto'];
			}
			
			// TOTAL DEPOSITO
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell($arrayCol2[0]['tamano'],10,"","",0,'L',false);
			$pdf->Cell($arrayCol2[1]['tamano'] + $arrayCol2[2]['tamano'] + $arrayCol2[3]['tamano'],10,"",'T',0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell($arrayCol2[4]['tamano'],10,"TOTAL DEPOSITO ".$rowPago['numero_documento'].": ",1,0,'R',true);
			$pdf->Cell($arrayCol2[5]['tamano'],10,$rowMoneda['abreviacion'].number_format($arrayTotalDeposito[5], 2, ".", ","),1,0,'R',true);
			$pdf->Ln();
			
			$pdf->Ln(4);
			
			$pdf->MultiCell($totalAncho,0,"",1,'C',true); // cierra linea de tabla
			
			$pdf->SetFont('Arial','',8);
		}
		
		//$fill = !$fill;
		
		$arrayTotal[9] += $montoPagado;
	}
	
	$pdf->MultiCell($totalAncho,0,"",1,'C',true); // cierra linea de tabla
	$pdf->Ln();
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'],14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell($arrayCol[4]['tamano'],14,"TOTAL: ",1,0,'R',true);
	$pdf->Cell($arrayCol[5]['tamano'],14,$rowMoneda['abreviacion'].number_format($arrayTotal[9], 2, ".", ","),1,0,'R',true);
	$pdf->Ln();

if ($rowRecibo['fechaDocumento']<="01-05-2018") {
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'],14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell($arrayCol[4]['tamano'],14,"TOTAL: ",1,0,'R',true);
	$pdf->Cell($arrayCol[5]['tamano'],14,"Bs.S ".number_format($arrayTotal[9]/100000, 2, ".", ","),1,0,'R',true);
	$pdf->Ln();
}
	
	
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',10);
	
	$pdf->Cell($totalAncho,10,"OBSERVACIONES:",0,0,'L');
	$pdf->Ln();
	$pdf->MultiCell($totalAncho, 14, $observacionDcto, 0, 'L');
	$pdf->Ln();
	
	$pdf->Cell(20,5,"",0,0,'L');
	$pdf->MultiCell(570,15,"HEMOS RECIBIDO DE: ".$rowFactura['nombre_cliente'], 0, 'L');
	
	$pdf->Cell(20,5,"",0,0,'L');
	$pdf->MultiCell(570, 15, "LA CANTIDAD DE: ".utf8_decode(strtoupper(num2letras(number_format($arrayTotal[9], 2, ".", ""),false,true,$rowMoneda['descripcion'],$centimos))), 0, 'L');
	$pdf->Ln();
	
	$pdf->Ln(); $pdf->Ln();
	
	$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado
	WHERE vw_pg_empleado.id_empleado = %s",
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowEmpleado = mysql_fetch_array($rsEmpleado);
	
	$pdf->Cell($totalAncho,14,"EMITIDO POR: ".utf8_decode((strlen($rowRecibo['nombre_empleado']) > 0) ? $rowRecibo['nombre_empleado'] : $rowEmpleado['nombre_empleado']),0,0,'C');
	
	$pdf->nombreRegistrado = $rowRecibo['nombre_empleado'];
}

if ($totalRowsRecibo > 0) {
	$pdf->SetDisplayMode(80);
	//$pdf->AutoPrint(true);
	$pdf->Output();
}
?>