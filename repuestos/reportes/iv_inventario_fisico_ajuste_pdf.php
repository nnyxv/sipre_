<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("20","20","20");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"20");
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idDocumento = $valCadBusq[0];

$queryInvFis = sprintf("SELECT * FROM vw_iv_inventario_fisico WHERE id_inventario_fisico = %s",
	valTpDato($idDocumento, "int"));
$rsInvFis = mysql_query($queryInvFis);
$rowInvFis = mysql_fetch_assoc($rsInvFis);

$idEmpresa = ($rowInvFis['id_empresa'] > 0) ? $rowInvFis['id_empresa'] : 100 ;

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


$maxRows = 22;
$campOrd = "numero";
$tpOrd = "ASC";

foreach ($valCadBusq as $indice => $valor) {
	if ($indice >= 1 && in_array($valor,array("K","C"))) {
		if ($valor == "K")
			$verKardex = true;
		if ($valor == "C")
			$verConteo = true;
	} else if ($indice >= 1 && in_array($valor,array("I","F","S"))) {
		if ($valor == "I")
			$verIguales = true;
		if ($valor == "F")
			$verFaltantes = true;
		if ($valor == "S")
			$verSobrantes = true;
	} else if ($indice >= 1 && in_array($valor,array(1,2,3))) {
		$comparaConteo = $valor;
	}
}

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_inventario_fisico = %s",
	valTpDato($idDocumento, "int"));

if ($comparaConteo == 1) {
	$conteoComparar = "conteo_1";
} else if ($comparaConteo == 2) {
	$conteoComparar = "conteo_2";
} else if ($comparaConteo == 3) {
	$conteoComparar = "conteo_3";
}

if ($verIguales == true && $verFaltantes == true && $verSobrantes == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((".$conteoComparar." - existencia_kardex = 0
		OR ".$conteoComparar." - existencia_kardex < 0
		OR ".$conteoComparar." - existencia_kardex > 0))");
} else if ($verIguales == true && $verFaltantes == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((".$conteoComparar." - existencia_kardex = 0
		OR ".$conteoComparar." - existencia_kardex < 0))");
} else if ($verIguales == true && $verSobrantes == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((".$conteoComparar." - existencia_kardex = 0
		OR ".$conteoComparar." - existencia_kardex > 0))");
} else if ($verFaltantes == true && $verSobrantes == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((".$conteoComparar." - existencia_kardex < 0
		OR ".$conteoComparar." - existencia_kardex > 0))");
} else if ($verIguales == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(".$conteoComparar." - existencia_kardex = 0)");
} else if ($verFaltantes == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(".$conteoComparar." - existencia_kardex < 0)");
} else if ($verSobrantes == true) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(".$conteoComparar." - existencia_kardex > 0)");
}

$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle %s", $sqlBusq);

$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
$rsLimit = mysql_query($queryLimit);
if (!$rsLimit) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
if ($totalRows == NULL) {
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
}
$totalPages = ceil($totalRows/$maxRows)-1;

$contFila = 0;
for ($pageNum = 0; $pageNum <= $totalPages; $pageNum++) {
	$startRow = $pageNum * $maxRows;
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit, $conex);
	if (!$rsLimit) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query, $conex);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$fill = false;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		if (fmod($contFila, $maxRows) == 1) {
			$pdf->AddPage();
			
			/* CABECERA DEL DOCUMENTO */
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',5);
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_decode($rowEmp['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmp['rif']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(200,9,utf8_decode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
				}
				if (strlen($rowEmp['direccion']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
				}
				if (strlen($rowEmp['web']) > 1) {
					$pdf->SetX(88);
					$pdf->Cell(200,9,utf8_decode($rowEmp['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			
			$pdf->Cell('',2,'',0,2);
		
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell(582,14,"Inventario Fisico Comparativo",0,0,'C');
			$pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(582,14,"(".$rowInvFis['filtro_conteo_descripcion'].")",0,0,'C');
			$pdf->Ln();
			
			
			$pdf->Cell(60,14,"Ordenado por: ",0,0,'R');
			$pdf->Cell(100,14,$rowInvFis['orden_conteo_descripcion'],0,0,'L');
			$pdf->SetX(485);
			$pdf->Cell(60,14,"Fecha: ",0,0,'R');
			$pdf->Cell(45,14,date(spanDateFormat,strtotime($rowInvFis['fecha'])),0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',2,'',0,2);
			$pdf->Ln();
			
			// COLUMNAS
			// COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			
			$arrayCol = array(
				array("tamano" => "16", "campo" => "NRO.\n\n"),
				array("tamano" => "78", "campo" => "CODIGO\n\n"),
				array("tamano" => "483", "campo" => "DESCRIPCION\n\n"));
			
			if (!($verConteo == true)) {
				$arrayCol[2]['tamano'] -= 36;
				$arrayCol[] = array("tamano" => "36", "campo" => "UNIDAD\n\n");
			}
			
			$arrayCol[2]['tamano'] -= 56;
			$arrayCol[] = array("tamano" => "56", "campo" => "LOCALIZACIÓN\n\n");
			
			$arrayCol[2]['tamano'] -= 50;
			$arrayCol[] = array("tamano" => "50", "campo" => "LOTE / COSTO UNIT.");
			
			$arrayCol[2]['tamano'] -= 53;
			$arrayCol[] = array("tamano" => "53", "campo" => "KARDEX UNID./VALOR");
			
			$arrayCol[2]['tamano'] -= 53;
			$arrayCol[] = array("tamano" => "53", "campo" => "FISICO UNID./VALOR");
			
			$arrayCol[2]['tamano'] -= 53;
			$arrayCol[] = array("tamano" => "53", "campo" => "DIFERENCIAS UNID./VALOR");
			
			if ($verConteo == true) {
				$arrayCol[2]['tamano'] -= 36;
				$arrayCol[] = array("tamano" => "36", "campo" => "CONTEO");
			}
			
			$largoTabla = 0;
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			foreach ($arrayCol as $indice => $valor) {
				$largoTabla += $valor['tamano'];
				
				$pdf->SetY($posY); $pdf->SetX($posX); 
				$pdf->MultiCell($valor['tamano'],8,utf8_decode($valor['campo']),1,'C',true);
				
				$posX += $valor['tamano'];
			}
		}
			
		// RESTAURACION DE COLOR Y FUENTE
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
		
		$pdf->SetFont('Arial','',5.6);
		$pdf->SetTextColor(0,0,0);
		
		$cantConteo = $row[$conteoComparar];
		$cantDiferencia = $cantConteo - $row['existencia_kardex'];
		
		$pdf->Cell($arrayCol[0]['tamano'],14,$row['numero'],1,0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,elimCaracter($row['codigo_articulo'],";"),1,0,'L',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,$row['descripcion'],1,0,'L',true);
		$pos = 2;
		if (!($verConteo == true)) {
			$pos++;
			$pdf->Cell($arrayCol[$pos]['tamano'],14,$row['unidad'],1,0,'C',true);
		}
		$pos++;
		$posY = $pdf->GetY();
		$posX = $pdf->GetX();
		$pdf->MultiCell($arrayCol[$pos]['tamano'],7,$row['descripcion_almacen']."\n".str_replace("-[]", "", $row['ubicacion']),'LTR','C',true);
		$posX += $arrayCol[$pos]['tamano'];
		
		$pos++;
		$pdf->SetFont('Arial','B',5.6);
		$pdf->SetY($posY); $pdf->SetX($posX);
		$pdf->Cell($arrayCol[$pos]['tamano'],14,$row['id_articulo_costo'],1,0,'R',true);
		
		$pdf->SetFont('Arial','',5.6);
		$pos++;
		$pdf->Cell($arrayCol[$pos]['tamano'],14,(($verKardex == true) ? number_format($row['existencia_kardex'], 2, ".", ",") : "-"),1,0,'R',true);
		
		$pos++;
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($cantConteo, 2, ".", ","),1,0,'R',true);
		
		$pos++;
		($cantDiferencia >= 0) ? $pdf->SetFont('Arial','',5.6) : $pdf->SetFont('Arial','B',5.6);
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($cantDiferencia, 2, ".", ","),1,0,'R',true);
		if ($verConteo == true) {
			$pos++;
			$pdf->Cell($arrayCol[$pos]['tamano'],14,"","LTR",0,'R',true);
		}
		$pdf->SetFont('Arial','',5.6);
		
		$pdf->Ln();
		
		if (!($verConteo == true)) {
			$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'];
			$pos = 4;
		} else {
			$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'];
			$pos = 3;
		}
		$pdf->Cell($tamano,14,"",1,0,'L',true);
		$posX = $pdf->GetX() + $arrayCol[$pos]['tamano']; $pdf->SetX($posX);
		$pos++;
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($row['costo_proveedor'], 2, ".", ","),1,0,'R',true);
		
		$pos++;
		$pdf->Cell($arrayCol[$pos]['tamano'],14,(($verKardex == true) ? number_format(($row['existencia_kardex'] * $row['costo_proveedor']), 2, ".", ",") : "-"),1,0,'R',true);
		
		$pos++;
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format(($cantConteo * $row['costo_proveedor']), 2, ".", ","),1,0,'R',true);
		
		$pos++;
		($cantDiferencia >= 0) ? $pdf->SetFont('Arial','',5.6) : $pdf->SetFont('Arial','B',5.6);
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format(($cantDiferencia * $row['costo_proveedor']), 2, ".", ","),1,0,'R',true);
		if ($verConteo == true) {
			$pos++;
			$pdf->Cell($arrayCol[$pos]['tamano'],14,"","LBR",0,'R',true);
		}
		$pdf->SetFont('Arial','',5.6);
		
		$pdf->Ln();
				
		$fill = !$fill;
	
		$arrayTotales[0] += $row['existencia_kardex'] * $row['costo_proveedor'];
		$arrayTotales[1] += $cantConteo * $row['costo_proveedor'];
		$arrayTotales[2] += $cantDiferencia * $row['costo_proveedor'];
		
		
		if ($cantDiferencia == 0) {
			$nroArtIguales++;
			$cantArtIguales += $row['existencia_kardex'];
			$montoArtIguales += $row['existencia_kardex'] * $row['costo_proveedor'];
		} else if ($cantDiferencia < 0) {
			$nroArtFaltantes++;
			$cantArtFaltantes += $cantDiferencia;
			$montoArtFaltantes += $cantDiferencia * $row['costo_proveedor'];
		} else if ($cantDiferencia > 0) {
			$nroArtSobrantes++;
			$cantArtSobrantes += $cantDiferencia;
			$montoArtSobrantes += $cantDiferencia * $row['costo_proveedor'];
		}
	}
		
	// TOTALES POR HOJA
	$pdf->SetFillColor(255,255,255);
	if (!($verConteo == true)) {
		$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'];
	} else {
		$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'];
	}
	$pdf->Cell($tamano,14,"",'T',0,'L',true);
	if (!($verConteo == true)) {
		$tamano = $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'];
		$pos = 6;
	} else {
		$tamano = $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'];
		$pos = 5;
	}
	$pdf->SetFillColor(204,204,204);
	$pdf->Cell($tamano,14,"Total de Hoja:",1,0,'R',true);
	$pdf->SetFillColor(255,238,213);
	$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($arrayTotales[0], 2, ".", ","),1,0,'R',true);
	
	$pos++;
	$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($arrayTotales[1], 2, ".", ","),1,0,'R',true);
	
	$pos++;
	($arrayTotales[2] >= 0) ? $pdf->SetFont('Arial','',5.6) : $pdf->SetFont('Arial','B',5.6);
	$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($arrayTotales[2], 2, ".", ","),1,0,'R',true);
	$pdf->SetFont('Arial','',5.6);
	
	$pdf->Ln();
	
	
	$arrayTotalesTotal[0] += $arrayTotales[0];
	$arrayTotalesTotal[1] += $arrayTotales[1];
	$arrayTotalesTotal[2] += $arrayTotales[2];
	
	$arrayTotales = NULL;
	
	if ($pageNum >= $totalPages) {
		$pdf->SetFillColor(255,255,255);
		if (!($verConteo == true)) {
			$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'];
		} else {
			$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'];
		}
		$pdf->Cell($tamano,14,"",0,0,'L',true);
		if (!($verConteo == true)) {
			$tamano = $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'];
			$pos = 6;
		} else {
			$tamano = $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'];
			$pos = 5;
		}
		$pdf->SetFillColor(204,204,204);
		$pdf->Cell($tamano,14,"Total de Totales:",1,0,'R',true);
		$pdf->SetFillColor(223,255,223);
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($arrayTotalesTotal[0], 2, ".", ","),1,0,'R',true);
		
		$pos++;
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($arrayTotalesTotal[1], 2, ".", ","),1,0,'R',true);
		
		$pos++;
		($arrayTotalesTotal[2] >= 0) ? $pdf->SetFont('Arial','',5.6) : $pdf->SetFont('Arial','B',5.6);
		$pdf->Cell($arrayCol[$pos]['tamano'],14,number_format($arrayTotalesTotal[2], 2, ".", ","),1,0,'R',true);
		$pdf->SetFont('Arial','',5.6);
	}
	
	
	$pdf->SetY(-35);
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','I',6);
	$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
	$pdf->SetY(-35);
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','I',8);
	$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
}


$pdf->AddPage();

/* CABECERA DEL DOCUMENTO */
if ($idEmpresa != "") {
	$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',5);
	$pdf->SetX(88);
	$pdf->Cell(200,9,utf8_decode($rowEmp['nombre_empresa']),0,2,'L');
	
	if (strlen($rowEmp['rif']) > 1) {
		$pdf->SetX(88);
		$pdf->Cell(200,9,utf8_decode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
	}
	if (strlen($rowEmp['direccion']) > 1) {
		$pdf->SetX(88);
		$pdf->Cell(100,9,$rowEmp['direccion'],0,2,'L');
	}
	if (strlen($rowEmp['web']) > 1) {
		$pdf->SetX(88);
		$pdf->Cell(200,9,utf8_decode($rowEmp['web']),0,0,'L');
		$pdf->Ln();
	}
}

$pdf->Cell('',2,'',0,2);

$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(582,14,"Inventario Fisico Comparativo",0,0,'C');
$pdf->Ln();

$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','',7);
$pdf->Cell(582,14,"(".$rowInvFis['filtro_conteo_descripcion'].")",0,0,'C');
$pdf->Ln();


$pdf->Cell(60,14,"Ordenado por: ",0,0,'R');
$pdf->Cell(100,14,$rowInvFis['orden_conteo_descripcion'],0,0,'L');
$pdf->SetX(485);
$pdf->Cell(60,14,"Fecha: ",0,0,'R');
$pdf->Cell(45,14,date(spanDateFormat,strtotime($rowInvFis['fecha'])),0,0,'C');
$pdf->Ln();

$pdf->Cell('',2,'',0,2);
$pdf->Ln();

// COLUMNAS
// COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
$pdf->SetFillColor(204,204,204);
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);	
$pdf->SetFont('Arial','',6.8);

$arrayCol = array(
	array("tamano" => "60", "campo" => ""),
	array("tamano" => "100", "campo" => "NRO. TOTAL DE ARTICULOS"),
	array("tamano" => "100", "campo" => "CANT. DE ARTICULOS"),
	array("tamano" => "100", "campo" => "MONTO"));
$largoTabla = 0;
foreach ($arrayCol as $indice => $valor) {
	$largoTabla += $valor['tamano'];
	$pdf->Cell($valor['tamano'],16,$valor['campo'],1,0,'C',true);
}
$pdf->Ln();

if ($verIguales == true) {
	$pdf->SetFillColor(204,204,204);
	$pdf->Cell($arrayCol[0]['tamano'],14,"Iguales:",1,0,'R',true);;
	
	// RESTAURACION DE COLOR Y FUENTE
	($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[1]['tamano'],14,number_format($nroArtIguales, 2, ".", ","),1,0,'R',true);
	$pdf->Cell($arrayCol[2]['tamano'],14,number_format($cantArtIguales, 2, ".", ","),1,0,'R',true);
	$pdf->Cell($arrayCol[3]['tamano'],14,number_format($montoArtIguales, 2, ".", ","),1,0,'R',true);
	$pdf->Ln();
		
	$fill = !$fill;
}
if ($verFaltantes == true) {
	$pdf->SetFillColor(204,204,204);
	$pdf->Cell($arrayCol[0]['tamano'],14,"Faltantes:",1,0,'R',true);;
	
	// RESTAURACION DE COLOR Y FUENTE
	($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[1]['tamano'],14,number_format($nroArtFaltantes, 2, ".", ","),1,0,'R',true);
	$pdf->Cell($arrayCol[2]['tamano'],14,number_format((-1) * $cantArtFaltantes, 2, ".", ","),1,0,'R',true);
	$pdf->Cell($arrayCol[3]['tamano'],14,number_format((-1) * $montoArtFaltantes, 2, ".", ","),1,0,'R',true);
	$pdf->Ln();
		
	$fill = !$fill;
}
if ($verSobrantes == true) {
	$pdf->SetFillColor(204,204,204);
	$pdf->Cell($arrayCol[0]['tamano'],14,"Sobrantes:",1,0,'R',true);
	
	// RESTAURACION DE COLOR Y FUENTE
	($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[1]['tamano'],14,number_format($nroArtSobrantes, 2, ".", ","),1,0,'R',true);
	$pdf->Cell($arrayCol[2]['tamano'],14,number_format($cantArtSobrantes, 2, ".", ","),1,0,'R',true);
	$pdf->Cell($arrayCol[3]['tamano'],14,number_format($montoArtSobrantes, 2, ".", ","),1,0,'R',true);
	$pdf->Ln();
		
	$fill = !$fill;
}


$pdf->SetY(-35);
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','I',6);
$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
$pdf->SetY(-35);
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');


$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

function iniciales($texto) {
    $ini = '';

    foreach (explode(' ', $texto) as $palabras){
        $ini .= strtoupper($palabras[0]);
    }
    
    return $ini;
}
?>