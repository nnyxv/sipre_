<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
$idPlanilla = $valCadBusq[1];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// PLANILLA DE DEPOSITO ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

	//CONSULTA FECHA DEL DEPOSITO
	$queryFechaDeposito = sprintf("SELECT fechaPlanilla FROM an_encabezadodeposito WHERE idPlanilla = %s",
		valTpDato($idPlanilla, "int"));
	$rsFechaDeposito = mysql_query($queryFechaDeposito) or die(mysql_error());
	$rowFechaDeposito = mysql_fetch_assoc($rsFechaDeposito);
	
	//CONSULTA DATOS DEL DEPOSITO
	$queryDeposito = sprintf("
	SELECT DISTINCT
		(an_detalledeposito.numeroDeposito) AS numeroDeposito,
		an_detalledeposito.idBancoAdepositar,
		(an_detalledeposito.numeroCuentaBancoAdepositar) AS numeroCuentaBancoAdepositar
	FROM
		an_encabezadodeposito
		INNER JOIN an_detalledeposito ON (an_encabezadodeposito.idPlanilla = an_detalledeposito.idPlanilla)
	WHERE
		an_encabezadodeposito.idPlanilla = %s
		AND an_detalledeposito.anulada = %s
		AND an_encabezadodeposito.id_empresa = %s
	ORDER BY
		an_detalledeposito.numeroDeposito DESC",
			valTpDato($idPlanilla, "int"),
			valTpDato('NO', "text"),
			valTpDato($idEmpresa, "int"));
	$rsDeposito = mysql_query($queryDeposito) or die(mysql_error());
	
	$pdf->AddPage();
	
	//CABECERA DEL DOCUMENTO
	if ($rowEmp['id_empresa'] != "") {
		$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			
		$pdf->SetY(15);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',5);
		$pdf->SetX(88);
		$pdf->Cell(200,9,$rowEmp['nombre_empresa'],0,2,'L');
		
		if (strlen($rowEmp['rif']) > 1) {
			$pdf->SetX(88);
			$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
		}
		if (strlen($rowEmp['direccion']) > 1) {
			$pdf->SetX(88);
			$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
		}
		if (strlen($rowEmp['web']) > 1) {
			$pdf->SetX(88);
			$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
			$pdf->Ln();
		}
	}
	
	$pdf->Cell('',8,'',0,2);
			
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',11);
	$pdf->Ln();
	$pdf->Cell(562,5,$nombreCajaPpal,0,0,'C');
	$pdf->Ln();$pdf->Ln();$pdf->Ln();
	$pdf->Cell(562,5,"PLANILLA DE DEPOSITO",0,0,'C');
	$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
			
	//CABEZERA DEL DEPOSITO
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(455);
	$pdf->Cell(60,12,utf8_decode("Fecha: "),0,0,'R');
	$pdf->Cell(100,12,utf8_decode(date(spanDateFormat, strtotime($rowFechaDeposito['fechaPlanilla']))),0,0,'L');
	
	while ($rowDeposito = mysql_fetch_assoc($rsDeposito)) {
	
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',7);
		//CONSULTA DETALLE DEL DEPOSITO		
		$queryDetalleDeposito = sprintf("SELECT *
		FROM an_detalledeposito
			INNER JOIN bancos ON (bancos.idBanco = an_detalledeposito.banco)
		WHERE
			idPlanilla = %s
			AND anulada = %s",
				valTpDato($idPlanilla, "int"),
				valTpDato('NO', "text"));
		$rsDetalleDeposito = mysql_query($queryDetalleDeposito) or die(mysql_error());
		//$rowDetalleDeposito = mysql_fetch_assoc($rsDetalleDeposito);
		$pdf->Cell('',10);
		$pdf->Ln();
		$pdf->Ln();
		
		$pdf->Ln();
		//$pdf->SetX(455);
		$pdf->Cell(60,12,utf8_decode("Nro. Depósito: "),0,0,'L');
		$pdf->Cell(45,12,$rowDeposito['numeroDeposito'],0,0,'L');
		$pdf->Ln();
		
		//$pdf->SetX(455);
		$pdf->Cell(60,12,utf8_decode("Banco a Depositar: "),0,0,'L');
		$pdf->Cell(45,12,nombreBanco($rowDeposito['idBancoAdepositar']),0,0,'L');
		$pdf->Ln();
		
		//$pdf->SetX(455);
		$pdf->Cell(60,12,utf8_decode("Nro. Cuenta: "),0,0,'L');
		$pdf->Cell(45,12,$rowDeposito['numeroCuentaBancoAdepositar'],0,0,'L');
		$pdf->Ln();
		
		$pdf->Cell('',10);
		$pdf->Ln();
		
		//COLUMNAS
		//Colores, ancho de línea y fuente en negrita
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',6.8);
		
		$arrayTamCol = array("210","100","169","83");
		$arrayCol = array("Banco","Nro. Cheque","Cuenta Cliente","Monto");
		
		$totalNroPlanillas += 1;
		foreach ($arrayCol as $indice => $valor) {
			$pdf->Cell($arrayTamCol[$indice],16,utf8_decode($valor),1,0,'C',true);
		}
		
		$pdf->Ln();
		while ($rowDetalleDeposito = mysql_fetch_assoc($rsDetalleDeposito)){	
		//DATA		
			if ($rowDetalleDeposito['formaPago'] == 2) { // 2 = CHEQUES
			
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				
				$pdf->Cell($arrayTamCol[0],16,$rowDetalleDeposito['nombreBanco'],'LR',0,'L',true);
				$pdf->Cell($arrayTamCol[1],16,$rowDetalleDeposito['numeroCheque'],'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[2],16,$rowDetalleDeposito['numeroCuenta'],'LR',0,'L',true);
				$pdf->Cell($arrayTamCol[3],16,number_format($rowDetalleDeposito['monto'],2,".",","),'LR',0,'R',true);
				$pdf->Ln();
				
				$fill = !$fill;
				
				$totalCheque += $rowDetalleDeposito['monto'];
			} else if ($rowDetalleDeposito['formaPago'] == 1) { // 1 = EFECTIVO
				$totalEfectivo += $rowDetalleDeposito['monto'];
			}
		}
		
	$totalDeposito = $totalCheque + $totalEfectivo;
		
	$pdf->SetFillColor(255);
	$pdf->Cell(562,0,"",'T',0,'L',true);
	$pdf->Ln();

	//TOTAL CHEQUES
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"","T",0,'L',true);
	$pdf->SetFillColor(255,255,255,255);
	$pdf->Cell(77,14,"TOTAL CHEQUES: ",1,0,'L',true);
	$pdf->Cell(83,14,number_format($totalCheque,2,".",","),1,0,'R',true);
	$pdf->Ln();
	
	//TOTAL EFECTIVO
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"",0,0,'L',true);
	$pdf->SetFillColor(255,255,255,255);
	$pdf->Cell(77,14,"TOTAL EFECTIVO: ",1,0,'L',true);
	$pdf->Cell(83,14,number_format($totalEfectivo,2,".",","),1,0,'R',true);
	$pdf->Ln();
	
	//TOTAL DEPOSITO
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"",0,0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell(77,14,"TOTAL DEPOSITO: ",1,0,'L',true);
	$pdf->Cell(83,14,number_format($totalDeposito,2,".",","),1,0,'R',true);
	
	$totalDepositoEfectivo += $totalEfectivo;
	$totalDepositoCheque += $totalCheque;
		
	$totalPlanillas = $totalDepositoEfectivo + $totalDepositoCheque;
	
	$totalCheque = 0;
	$totalEfectivo = 0;
	$totalDeposito = 0;
}

	$pdf->Ln();
	$pdf->Ln();
	$pdf->Ln();
	
	//TOTAL DEPOSITO CHEQUES
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"",0,0,'L',true);
	$pdf->SetFillColor(255,255,255,255);
	$pdf->Cell(77,14,"TOTAL CHEQUES: ",1,0,'L',true);
	$pdf->Cell(83,14,number_format($totalDepositoCheque,2,".",","),1,0,'R',true);
	$pdf->Ln();
	
	//TOTAL DEPOSITO EFECTIVO
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"",0,0,'L',true);
	$pdf->SetFillColor(255,255,255,255);
	$pdf->Cell(77,14,"TOTAL EFECTIVO: ",1,0,'L',true);
	$pdf->Cell(83,14,number_format($totalDepositoEfectivo,2,".",","),1,0,'R',true);
	$pdf->Ln();
	
	//TOTAL TODAS LAS PLANILLAS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"",0,0,'L',true);
	$pdf->SetFillColor(255,255,255,255);
	$pdf->Cell(77,14,"TOTAL PLANILLAS: ",1,0,'L',true);
	$pdf->Cell(83,14,number_format($totalPlanillas,2,".",","),1,0,'R',true);
	$pdf->Ln();
	
	//TOTAL NRO. PLANILLAS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell(402,14,"",0,0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell(77,14,"Nro. Planillas: ",1,0,'L',true);
	$pdf->Cell(83,14,$totalNroPlanillas,1,0,'R',true);
	
function nombreBanco($idBanco) {
	$query = sprintf("SELECT *
		FROM bancos
		WHERE
			idBanco = %s",
				valTpDato($idBanco, "int"));
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($rs);
		
		return $row['nombreBanco'];
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>