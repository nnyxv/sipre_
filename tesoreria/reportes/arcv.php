<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"20");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$idEmpresa =$_GET["empresa"];
$idProveedor =$_GET["proveedor"];
$fecha= explode("-", $_GET["fecha"]);
$ano = $fecha[1];

$idEmpresa = ($idEmpresa > 0) ? $idEmpresa : 100 ;

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$fecha= time();

$query = sprintf("SELECT * FROM vw_te_retencion_cheque WHERE id_proveedor = %s AND anulado IS NULL",$idProveedor);
$rs = mysql_query($query);
if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
$row = mysql_fetch_assoc($rs);
$totalRows =1;

if ($totalRows > 0) {
	
	/* DATA */
	$contFila = 0;
	$fill = false;
	while ($contFila<1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			/* CABECERA DEL DOCUMENTO */
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',5);
				$pdf->SetX(88);
				$pdf->Cell(200,9,htmlentities($rowEmp['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmp['rif']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(200,9,htmlentities("RIF: ".$rowEmp['rif']),0,2,'L');
				}
				if (strlen($rowEmp['direccion']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
				}
				if (strlen($rowEmp['web']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(200,9,htmlentities($rowEmp['web']),0,0,'L');
					$pdf->Ln();
				}
			}
	
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell(562,5,"AR-CV ",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			
			////////////////////DATOS EMPRESA///////////////////////
			$pdf->SetTextColor(0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(200,20,"DATOS DEL AGENTE DE RETENCION       ",'B',0,'R');
			$pdf->Ln();
			$pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"NOMBRE O RAZON SOCIAL:                 ".$row['nombre_empresa']."",0,0,'L');
			$pdf->Ln();
					
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"Nro. DE RIF:                                             ".$row['rif_empresa']."",0,0,'L');
			$pdf->Ln();

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"TIPO DE AGENTE DE RETENCION:      ".tipo_agente($row['rif_empresa'])."",0,0,'L');
			$pdf->Ln();
	
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"TELEFONO:                                             ".$rowEmp['telefono1']."",0,0,'L');
			$pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"PERIODO FISCAL:                                  ".$ano."",0,0,'L');
			$pdf->Ln();
			
			////////////////////DATOS PROVEEDOR///////////////////////
			$pdf->SetTextColor(0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(150,20,"DATOS DEL BENEFICIARIO       ",'B',0,'R');
			$pdf->Ln();
			$pdf->Ln();			

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"NOMBRE O RAZON SOCIAL:                 ".$row['nombre']."",0,0,'L');
			$pdf->Ln();

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"Nro. DE RIF:                                             ".$row['rif_proveedor']."",0,0,'L');
			$pdf->Ln();

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"TIPO DE AGENTE DE RETENCION:      ".tipo_agente($row['rif_proveedor'])."",0,0,'L');
			$pdf->Ln();

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"DIRECCION:                                            ".$row['direccion_proveedor']."",0,0,'L');
			$pdf->Ln();

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"TELEFONO:                                             ".$rowEmp['telefono']."",0,0,'L');
			$pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(10,12,"PERIODO FISCAL:                                  ".$ano."",0,0,'L');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
				
			$arrayTamCol = array("100","100","62","100","100","100");
			$arrayCol = array("MES\n\n","TOTAL CANT. SUJETA A RETENCION","PORCENTAJE RETENCION","IMPUESTO RETENIDO\n\n","TOTAL CANT. SUJETA A RETENCION ACUMULADA","IMPUESTO RETENIDO ACUMULADO");	
	
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			$meses = array(	1 => "ENERO", 
								2 => "FEBRERO",
                              	3 => "MARZO",
                              	4 => "ABRIL",
                              	5 => "MAYO",
                              	6 => "JUNIO",
                              	7 => "JULIO",
                              	8 => "AGOSTO",
                              	9 => "SEPTIEMBRE",
                              	10 => "OCTUBRE",
                              	11 => "NOVIEMBRE",
                              	12 => "DICIEMBRE");
								
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
								
				$pdf->MultiCell($arrayTamCol[$indice],8,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}

		//RESTAURACION DE COLORES Y FUENTES
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');		
		
		for ($mes = 1; $mes <= 12; $mes++) {
			
			if(fmod($mes,2)== 0){
				$pdf->SetFillColor(234,244,255);
			}else{ 
				$pdf->SetFillColor(255,255,255);
			}			
			
			$queryRetencion = sprintf("SELECT 
										SUM(subtotal_factura) AS total_facturas,
										porcentaje_retencion,
										SUM(monto_retenido) AS monto_retenido
									FROM vw_te_retencion_cheque
									WHERE (fecha_registro >= '".$ano."-".$mes."-01' 
									AND fecha_registro <= '".$ano."-".$mes."-31' )
									AND id_proveedor = %s AND anulado IS NULL
									GROUP BY porcentaje_retencion", 
									$idProveedor);
			$rsRetencion = mysql_query($queryRetencion);
			if (!$rsRetencion) return die(mysql_error()."\n\nLine: ".__LINE__);				
				
			while ($rowRetencion = mysql_fetch_assoc($rsRetencion)){
				$total_fact= $total_fact+$rowRetencion['total_facturas'];
				$total_acumu= $total_acumu+$rowRetencion['monto_retenido'];
				
				$pdf->Cell($arrayTamCol[0],12,$meses[$mes],'LR',0,'L',true);
				$pdf->Cell($arrayTamCol[1],12,number_format($rowRetencion['total_facturas'],2,".",","),'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[2],12,$rowRetencion['porcentaje_retencion'],'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[3],12,number_format($rowRetencion['monto_retenido'],2,".",","),'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[4],12,number_format($total_fact,2,".",","),'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[5],12,number_format($total_acumu,2,".",","),'LR',0,'C',true);
				$pdf->Ln();
			}
			
		}		
		
		$pdf->Ln();		
		$fill = !$fill;		
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {			
			
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			if ($contFila == $totalRows) {
				$pdf->Ln();
				$pdf->SetFillColor(255);
				$pdf->Cell(562,5,"",'T',0,'L',true);
				$pdf->Ln();
				
				/*$pdf->Cell($arrayTamCol[0],12,"",'LR',0,'L',true);
				$pdf->Cell($arrayTamCol[1],12,$total_fact,'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[2],12,"",'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[3],12,$total_acumu,'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[4],12,$total_fact,'LR',0,'C',true);
				$pdf->Cell($arrayTamCol[5],12,$total_acumu,'LR',0,'C',true);*/
					
				$pdf->SetFillColor(204,204,204,204);
				$pdf->Cell(100,14,"TOTAL Bs:  ",1,0,'L',true);
				$pdf->Cell(100,14,number_format($total_fact,2,".",","),1,0,'C',true);
				$pdf->Cell(62,14,"",1,0,'L',true);
				$pdf->Cell(100,14,number_format($total_acumu,2,".",","),1,0,'C',true);
				$pdf->Cell(100,14,number_format($total_fact,2,".",","),1,0,'C',true);
				$pdf->Cell(100,14,number_format($total_acumu,2,".",","),1,0,'C',true);					
			}

			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}

$pdf->SetDisplayMode("real");
//$pdf->AutoPrint(true);
$pdf->Output();


function tipo_agente($id_empresa){
	
	$tipoAgente = explode("-", $id_empresa);
	
	if ($tipoAgente[0]=='J'){
		$respuesta='PERSONA JURIDICA';
	}else if ($tipoAgente[0]=='G'){
		$respuesta='PERSONA JURIDICA';
	}else if ($tipoAgente[0]=='V'){
		$respuesta='PERSONA NATURAL';
	}else if ($tipoAgente[0]=='E'){
		$respuesta='PERSONA NATURAL';
	}	
	
	return $respuesta;
}

?>
