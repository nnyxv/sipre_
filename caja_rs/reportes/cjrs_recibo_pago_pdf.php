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

$idTpDcto = $_GET['idTpDcto']; // 1 = FA, 2 = ND
$idDocumento = $_GET['id'];
$idRecibo = $_GET["idRecibo"];

if ($idRecibo > 0) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recibo.idComprobante IN (%s)",
		valTpDato($idRecibo, "campo"));
} else if ($idTpDcto > 0 && $idDocumento > 0) {
	if (!in_array($idTpDcto,array(1,2))) {
		echo "<script>alert('Tipo de documento invalido para el tipo de recibo.'); window.close(); </script>";
	}
	
	// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recibo.idTipoDeDocumento = %s AND recibo.numero_tipo_documento = %s",
		valTpDato($idTpDcto, "int"),
		valTpDato($idDocumento, "int"));
} else {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("recibo.idComprobante = -1");
}

// DATOS DEL RECIBO
$queryRecibo = sprintf("SELECT recibo.*,
	(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = recibo.idTipoDeDocumento) AS tipo_documento_pagado,
	vw_pg_empleado.nombre_empleado
FROM cj_encabezadorecibopago recibo
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (recibo.id_empleado_creador = vw_pg_empleado.id_empleado) %s;", $sqlBusq);
$rsRecibo = mysql_query($queryRecibo);
if (!$rsRecibo) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsRecibo = mysql_num_rows($rsRecibo);
while ($rowRecibo = mysql_fetch_assoc($rsRecibo)) {
	$idDocumento = $rowRecibo['numero_tipo_documento'];
	$idRecibo = $rowRecibo['idComprobante'];
	
	if (!in_array($rowRecibo['idTipoDeDocumento'],array(1,2))) {
		echo "<script>alert('Tipo de documento invalido para el tipo de recibo.'); window.close(); </script>";
	}
	
	// ENCABEZADO DE LA TABLA
	$arrayCol = array(
		array("tamano" => "60", "descripcion" => "FECHA PAGO\n"),
		array("tamano" => "100", "descripcion" => "FORMA DE PAGO\n\n"),
		array("tamano" => "60", "descripcion" => "NRO. REF.\n\n"),
		array("tamano" => "125", "descripcion" => "BANCO / CUENTA CLIENTE\n\n"),
		array("tamano" => "135", "descripcion" => "BANCO / CUENTA COMPAÑIA\n\n"),
		array("tamano" => "95", "descripcion" => "MONTO\n\n"));
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'];
	
	switch ($rowRecibo['idTipoDeDocumento']) {
		case 1 : // 1 = Factura
			$queryFactura = sprintf("SELECT cxc_fact.*,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.direccion,
				cliente.estado,
				cliente.telf
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			WHERE cxc_fact.idFactura = %s;",
				valTpDato($idDocumento, "int"));
			
			$queryPago = sprintf("SELECT q.*,
				
				(CASE q.formaPago
					WHEN 7 THEN
						(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = q.numeroDocumento)
					WHEN 8 THEN
						(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = q.numeroDocumento)
					ELSE
						q.numeroDocumento
				END) AS numero_documento,
				
				(CASE q.formaPago
					WHEN 8 THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nc_det_motivo.id_nota_credito = q.numeroDocumento)
				END) AS descripcion_motivo,
				
				(CASE q.formaPago
					WHEN 7 THEN
						(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = q.numeroDocumento)
					WHEN 8 THEN
						(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = q.numeroDocumento)
				END) AS observacion_documento,
				
				forma_pago.idFormaPago,
				forma_pago.nombreFormaPago,
				
				(CASE q.formaPago
					WHEN 7 THEN
						(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
						FROM cj_cc_detalleanticipo det_anticipo
							INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
							INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
							INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
						WHERE cxc_ant.idAnticipo = q.numeroDocumento
							AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
				END) AS descripcion_concepto_forma_pago,
				
				q.bancoOrigen,
				banco_cliente.nombreBanco AS nombre_banco_cliente,
				q.numero_cuenta_cliente,
				q.bancoDestino,
				banco_emp.nombreBanco AS nombre_banco_empresa,
				q.cuentaEmpresa,
				
				(SELECT tipo_tarjeta.descripcionTipoTarjetaCredito
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
					INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
				WHERE ret_punto_pago.id_pago = q.idPago
					AND ret_punto_pago.id_caja = q.idCaja
					AND ret_punto_pago.id_tipo_documento = 1) AS nombre_tarjeta,
				
				caja.descripcion AS nombre_caja,
				vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
			FROM (SELECT * FROM an_pagos cxc_pago_an
					
					UNION
					
					SELECT * FROM sa_iv_pagos cxc_pago) AS q
				INNER JOIN formapagos forma_pago ON (q.formaPago = forma_pago.idFormaPago)
				INNER JOIN caja ON (q.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (q.bancoOrigen = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (q.bancoDestino = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (q.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN cj_detallerecibopago recibo_det ON (q.idPago = recibo_det.idPago)
			WHERE recibo_det.idComprobantePagoFactura = %s
				AND q.id_factura = %s;",
				valTpDato($idRecibo, "int"),
				valTpDato($idDocumento, "int"));
			
			break;
		case 2 : // 2 = Nota de Débito
			$queryFactura = sprintf("SELECT cxc_nd.*,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.direccion,
				cliente.estado,
				cliente.telf
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
			WHERE cxc_nd.idNotaCargo = %s;",
				valTpDato($idDocumento, "int"));
			
			$queryPago = sprintf("SELECT cxc_pago.*,
				
				(CASE cxc_pago.idFormaPago
					WHEN 7 THEN
						(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
					WHEN 8 THEN
						(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
					ELSE
						cxc_pago.numeroDocumento
				END) AS numero_documento,
				
				(CASE cxc_pago.idFormaPago
					WHEN 8 THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
				END) AS descripcion_motivo,
				
				(CASE cxc_pago.idFormaPago
					WHEN 7 THEN
						(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
					WHEN 8 THEN
						(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				END) AS observacion_documento,
				
				forma_pago.idFormaPago,
				forma_pago.nombreFormaPago,
				
				(CASE cxc_pago.idFormaPago
					WHEN 7 THEN
						(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
						FROM cj_cc_detalleanticipo det_anticipo
							INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
							INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
							INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
						WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
							AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
				END) AS descripcion_concepto_forma_pago,
				
				cxc_pago.bancoOrigen,
				banco_cliente.nombreBanco AS nombre_banco_cliente,
				cxc_pago.numero_cuenta_cliente,
				cxc_pago.bancoDestino,
				banco_emp.nombreBanco AS nombre_banco_empresa,
				cxc_pago.cuentaEmpresa,
				
				(SELECT tipo_tarjeta.descripcionTipoTarjetaCredito
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
					INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
				WHERE ret_punto_pago.id_pago = cxc_pago.id_det_nota_cargo
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 2) AS nombre_tarjeta,
				
				caja.descripcion AS nombre_caja,
				vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
			FROM cj_det_nota_cargo cxc_pago
				INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
				INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			WHERE recibo_det.idComprobantePagoFactura = %s
				AND cxc_pago.idNotaCargo = %s;",
				valTpDato($idRecibo, "int"),
				valTpDato($idDocumento, "int"));
			
			break;
	}
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	switch ($rowRecibo['idTipoDeDocumento']) {
		case 1 : 
			$idEmpresa = $rowFactura['id_empresa'];
			$nroDocumento = $rowFactura['numeroFactura'];
			$observacionDcto = $rowFactura['observacionFactura'];
			break;
		case 2 :
			$idEmpresa = $rowFactura['id_empresa'];
			$nroDocumento = $rowFactura['numeroNotaCargo'];
			$observacionDcto = $rowFactura['observacionNotaCargo'];
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
	if ($totalRowsPago == 0) {
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
	$pdf->Cell(90,16,$rowRecibo['numeroComprobante'],0,0,'C');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',10);
	
	$pdf->Cell(500,14,utf8_decode("FECHA EMISIÓN: "),0,0,'R');
	$pdf->Cell(90,14,date(spanDateFormat, strtotime($rowRecibo['fechaComprobante'])),0,0,'C');
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
			case 1 : 
				$idPago = $rowPago['idPago'];
				$montoPagado = $rowPago['montoPagado'];
				break;
			case 2 : 
				$idPago = $rowPago['id_det_nota_cargo'];
				$montoPagado = $rowPago['monto_pago'];
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
			date(spanDateFormat, strtotime($rowPago['fechaPago'])),
			strtoupper($rowPago['nombreFormaPago'].
				((strlen($rowPago['nombre_tarjeta']) > 0) ? " (".$rowPago['nombre_tarjeta'].")" : "").
				((strlen($rowPago['descripcion_concepto_forma_pago']) > 0) ? "\n(".$rowPago['descripcion_concepto_forma_pago'].")" : "").
				$estatusPago.
				((strlen($rowPago['nombre_empleado_anulado']) > 0) ? "\nAnulado por: ".$rowPago['nombre_empleado_anulado']."\n(".date(spanDateFormat, strtotime($rowPago['fecha_anulado'])).")" : "")),
			strtoupper($rowPago['numero_documento']),
			strtoupper($rowPago['nombre_banco_cliente'].
				((strlen($rowPago['numero_cuenta_cliente']) > 1) ? "\n".$rowPago['numero_cuenta_cliente'] : "")),
			strtoupper($rowPago['nombre_banco_empresa']),
			$rowMoneda['abreviacion'].number_format($montoPagado, 2, ".", ",")), $fill);
		
		/*$pdf->Cell($arrayCol[0]['tamano'],14,date(spanDateFormat, strtotime($rowPago['fechaPago'])),'LR',0,'C',true);
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
				case 1 : 
					$queryDeposito = sprintf("SELECT
						cxc_pago_deposito_det.numero_cheque,
						forma_pago.idFormaPago,
						forma_pago.nombreFormaPago,
						cxc_pago_deposito_det.idBanco,
						banco_cliente.nombreBanco AS nombre_banco_cliente,
						cxc_pago_deposito_det.numero_cuenta AS numero_cuenta_cliente,
						cxc_pago_deposito_det.monto
					FROM an_det_pagos_deposito_factura cxc_pago_deposito_det
						INNER JOIN formapagos forma_pago ON (cxc_pago_deposito_det.idFormaPago = forma_pago.idFormaPago)
						LEFT JOIN bancos banco_cliente ON (cxc_pago_deposito_det.idBanco = banco_cliente.idBanco)
					WHERE cxc_pago_deposito_det.idPago = %s
						AND cxc_pago_deposito_det.idCaja = %s;",
						valTpDato($idPago, "int"),
						valTpDato($rowPago['idCaja'], "int"));
					break;
				case 2 : 
					$queryDeposito = sprintf("SELECT
						cxc_pago_deposito_det.numero_cheque,
						forma_pago.idFormaPago,
						forma_pago.nombreFormaPago,
						cxc_pago_deposito_det.idBanco,
						banco_cliente.nombreBanco AS nombre_banco_cliente,
						cxc_pago_deposito_det.numero_cuenta AS numero_cuenta_cliente,
						cxc_pago_deposito_det.monto
					FROM cj_det_pagos_deposito_nota_cargo cxc_pago_deposito_det
						INNER JOIN formapagos forma_pago ON (cxc_pago_deposito_det.idFormaPago = forma_pago.idFormaPago)
						LEFT JOIN bancos banco_cliente ON (cxc_pago_deposito_det.idBanco = banco_cliente.idBanco)
					WHERE cxc_pago_deposito_det.id_det_nota_cargo = %s
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
