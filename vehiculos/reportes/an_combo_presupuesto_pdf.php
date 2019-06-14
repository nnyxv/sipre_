<?php
require_once("../../connections/conex.php");

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
$idPresupuesto = $_GET['idPresupuesto'];

$totalRows = 1;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// PRESUPUESTO DE ACCESORIOS ///////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	// ENCABEZADO PRESUPUESTO
	$queryEncabezado = sprintf("SELECT * FROM an_presupuesto_accesorio
								WHERE id_presupuesto_accesorio = %s",
									$idPresupuesto);
	$rsEncabezado = mysql_query($queryEncabezado);
	if (!$rsEncabezado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEncabezado = mysql_fetch_assoc($rsEncabezado);
	
	// ENCABEZADO EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM pg_empresa
								WHERE id_empresa = %s",
									1);
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
//	$idEmpresa = $rowEmpresa['id_empresa'];
if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila<1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			// CABECERA DEL DOCUMENTO 
		/*	//if ($idEmpresa != "") {*/
				$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
				
				/*
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',6);
				$pdf->SetX(100);
				$pdf->Cell(200,9,htmlentities($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,htmlentities($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,htmlentities($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,htmlentities($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}*/
			//}
						$pdf->Cell('',8,'',0,2);

			//	 FECHA
				$fechaHoy = date(spanDateFormat);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7);
				$pdf->Cell(560,20,"FECHA: ".$fechaHoy."",0,0,'R');
				$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(562,5,"PRESUPUESTO DE ACCESORIOS",0,0,'C');
/*			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"".'Asociado a Presupuesto Nro.: '.$rowEncabezado['id_presupuesto']."",0,0,'C');*/
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","224","80","80","80");
			$arrayCol = array("CÓDIGO\n\n","DESCRIPCIÓN\n\n","PRECIO SIN I.V.A\n\n","I.V.A 12%\n\n","PRECIO CON I.V.A\n\n");
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
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
		
		//$pdf->SetFillColor(234,244,255); // blanco
		$pdf->SetFillColor(255,255,255); // azul
		
		// IVA  PREDETERMINADO 12%
			/*$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);		
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$iva = $rowIva['iva'];*/
			
		// DETALLE DEL PRESUPUESTO
			$queryDetalle = sprintf("SELECT *
										FROM
											an_presupuesto_accesorio_detalle
											INNER JOIN an_accesorio ON (an_accesorio.id_accesorio = an_presupuesto_accesorio_detalle.id_accesorio)											
										WHERE
											id_presupuesto_accesorio = %s", $idPresupuesto);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$codigoAccesorio = $rowDetalle['nom_accesorio'];
				$descripcion = $rowDetalle['des_accesorio'];
				$cantidadSolicitada = $rowDetalle['precio_unitario'];
				$precioUnitario = $rowDetalle['iva_unitario'];
				$preciocantidad = $rowDetalle['iva_unitario'] + $cantidadSolicitada;
				
						$pdf->Cell($arrayTamCol[0],12,$codigoAccesorio,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,utf8_encode($descripcion),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[2],12,number_format($cantidadSolicitada,2,".",","),'LR',0,'R',true);
						$pdf->Cell($arrayTamCol[3],12,number_format($precioUnitario,2,".",","),'LR',0,'R',true);
						$pdf->Cell($arrayTamCol[4],12,number_format($preciocantidad,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
			
			// SUB-TOTAL PRESUPUESTO
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(432,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL SIN I.V.A Bs: ",1,0,'L',true);
			$pdf->Cell(60,14,number_format($rowEncabezado['subtotal'],2,".",","),1,0,'R',true);
			$pdf->Ln();
			
			// TOTAL PRESUPUESTO
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(432,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"I.V.A 12% Bs: ",1,0,'L',true);
			$pdf->Cell(60,14,number_format($rowEncabezado['subtotal_iva'],2,".",","),1,0,'R',true);
			$pdf->Ln();
			
			$precioFinal = $rowEncabezado['subtotal'] + $rowEncabezado['subtotal_iva'];

			// TOTAL PRESUPUESTO
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(432,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL CON I.V.A Bs: ",1,0,'L',true);
			$pdf->Cell(60,14,number_format($precioFinal,2,".",","),1,0,'R',true);
			
			
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
		
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}
$pdf->SetDisplayMode("real");
//$pdf->AutoPrint(true);
$pdf->Output();
?>