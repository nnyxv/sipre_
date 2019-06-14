<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");
session_start();
set_time_limit(0);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

($_GET["lstOrientacionPDF"] == "V") ? $pdf = new PDF_AutoPrint('P','pt','Letter') : $pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("10","10","10");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
//$pdf->nombreRegistrado = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// LISTADO DE COBRANZAS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)
AND cxc_pago.fechapago != cxc_fact.fechaRegistroFactura
AND cxc_pago.formaPago NOT IN (8)
AND cxc_pago.estatus IN (1)",
	valTpDato($idModuloPpal, "campo"));

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
	OR cxc_fact.numeroControl LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s)",
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

$query = sprintf("SELECT
	cxc_fact.idFactura,
	cxc_fact.id_empresa,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	cxc_fact.idCliente,
	cxc_fact.idDepartamentoOrigenFactura,
	cxc_fact.condicionDePago,
	cxc_fact.numeroPedido,
	cxc_fact.saldoFactura,
	cxc_fact.montoTotalFactura,
	cxc_pago.montopagado,
	recibo.idComprobante AS id_recibo_pago,
	forma_pago.nombreFormaPago,
	CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_pago.fechapago,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
	INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
	INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
	INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
	INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
	INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY cxc_pago.idPago DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if ($totalRows > 0) {
	$pdf->AddPage();
	
	if ($_GET["lstOrientacionPDF"] == "V") {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => "\n\n"),
			array("tamano" => "25", "descripcion" => "MODULO\n\n"),
			array("tamano" => "90", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "40", "descripcion" => "FECHA REGISTRO\n\n"),
			array("tamano" => "40", "descripcion" => "NRO. FACTURA\n\n"),
			array("tamano" => "40", "descripcion" => "NRO. CONTROL\n\n"),
			array("tamano" => "85", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "30", "descripcion" => "TIPO PAGO\n\n"),
			array("tamano" => "35", "descripcion" => "FECHA PAGO\n"),
			array("tamano" => "50", "descripcion" => "FORMA DE PAGO\n\n"),
			array("tamano" => "45", "descripcion" => "MONTO PAGADO\n"),
			array("tamano" => "45", "descripcion" => "SALDO FACTURA\n"),
			array("tamano" => "45", "descripcion" => "TOTAL FACTURA\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => "\n\n"),
			array("tamano" => "35", "descripcion" => "MODULO\n\n"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "60", "descripcion" => "FECHA REGISTRO\n\n"),
			array("tamano" => "60", "descripcion" => "NRO. FACTURA\n\n"),
			array("tamano" => "60", "descripcion" => "NRO. CONTROL\n\n"),
			array("tamano" => "125", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "40", "descripcion" => "TIPO PAGO\n\n"),
			array("tamano" => "45", "descripcion" => "FECHA PAGO\n"),
			array("tamano" => "60", "descripcion" => "FORMA DE PAGO\n\n"),
			array("tamano" => "55", "descripcion" => "MONTO PAGADO\n"),
			array("tamano" => "55", "descripcion" => "SALDO FACTURA\n"),
			array("tamano" => "55", "descripcion" => "TOTAL FACTURA\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'] + $arrayCol[12]['tamano'] + $arrayCol[13]['tamano'];
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,"COBRANZAS",0,0,'C');
	
	$pdf->Ln();
	
	if (strlen($nombreCajaPpal) > 0) {
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$pdf->Cell($totalAncho,10,"(".$nombreCajaPpal.")",0,0,'C');
		
		$pdf->Ln();
	}
	
	$pdf->Ln();
	
	$posY = $pdf->GetY();
	$posX = $pdf->GetX();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',6);
	
	foreach ($arrayCol as $indice => $valor) {
		$pdf->SetY($posY);
		$pdf->SetX($posX);
		
		$pdf->MultiCell($arrayCol[$indice]['tamano'],14,$arrayCol[$indice]['descripcion'],1,'C',true);
		
		$posX += $arrayCol[$indice]['tamano'];
	}
	
	while ($row = mysql_fetch_assoc($rs)) {
		// RESTAURACION DE COLOR Y FUENTE
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
		$contFila++;
		
		switch($row['idDepartamentoOrigenFactura']) {
			case 0 : $imgDctoModulo = "R"; break;
			case 1 : $imgDctoModulo = "S"; break;
			case 2 : $imgDctoModulo = "V"; break;
			case 3 : $imgDctoModulo = "A"; break;
			case 4 : $imgDctoModulo = "AL"; break;
			default : $imgDctoModulo = $row['idDepartamentoOrigenFactura'];
		}
		
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Factura";
		
		$pdf->Cell($arrayCol[0]['tamano'],14,$contFila,'LR',0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,$imgDctoModulo,'LR',0,'C',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,($row['nombre_empresa']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[3]['tamano'],14,date(spanDateFormat, strtotime($row['fechaRegistroFactura'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[4]['tamano'],14,($row['numeroFactura']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['numeroControl']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,utf8_decode(($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"),'LR',0,'C',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,date(spanDateFormat, strtotime($row['fechapago'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,($row['nombreFormaPago']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[10]['tamano'],14,number_format($row['montopagado'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[11]['tamano'],14,number_format($row['saldoFactura'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[12]['tamano'],14,number_format($row['montoTotalFactura'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
		
		$arrayTotal[10] += $row['montopagado'];
		$arrayTotal[11] += $row['saldoFactura'];
		$arrayTotal[12] += $row['montoTotalFactura'];
	}
	
	$pdf->MultiCell($totalAncho,0,'',1,'C',true); // cierra linea de tabla
	
	$pdf->Ln();
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'],14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell($arrayCol[9]['tamano'],14,"TOTALES: ",1,0,'R',true);
	$pdf->Cell($arrayCol[10]['tamano'],14,number_format($arrayTotal[10],2,".",","),1,0,'R',true);
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>