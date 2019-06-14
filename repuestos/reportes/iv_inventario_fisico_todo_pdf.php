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
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idDocumento = $valCadBusq[0];
$idEmpresa = $valCadBusq[1];

$idEmpresa = ($idEmpresa > 0) ? $idEmpresa : 100 ;

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$queryInvFisico = sprintf("SELECT * FROM vw_iv_inventario_fisico
WHERE id_inventario_fisico = %s",
	valTpDato($idDocumento, "int"));
$rsInvFisico = mysql_query($queryInvFisico, $conex);
if (!$rsInvFisico) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowInvFisico = mysql_fetch_assoc($rsInvFisico);

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////// CONTEO /////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
foreach ($valCadBusq as $indice => $valor) {
	if ($valor == "SI")
		$verData = true;
	else if ($valor == "NO")
		$verData = false;
		
	if ($indice >= 2 && in_array($valor,array("K",1,2,3))) {
		$verConteo = true;
		
		if ($valor == "K")
			$verKardex = true;
		if ($valor == 1)
			$verConteo1 = true;
		if ($valor == 2)
			$verConteo2 = true;
		if ($valor == 3)
			$verConteo3 = true;
	}
}

if ($verConteo == true) {
	if ($rowInvFisico['id_inventario_fisico'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_inventario_fisico = %s",
			valTpDato($idDocumento, "int"));
		
		if ($rowInvFisico['cantidad_conteo'] == 2) {
			if ($rowInvFisico['estatus'] == 0
			&& $rowInvFisico['numero_conteo'] == 2
			&& $verConteo2 == true) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("conteo_1 <> existencia_kardex");	
			}
		} else if ($rowInvFisico['cantidad_conteo'] == 3) {
			if ($rowInvFisico['estatus'] == 0
			&& $rowInvFisico['numero_conteo'] == 3
			&& $verConteo3 == true) {
				$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
				$sqlBusq .= $cond.sprintf("((conteo_1 IS NOT NULL AND conteo_2 IS NOT NULL) AND (conteo_1 <> conteo_2))
					OR (conteo_1 IS NULL AND conteo_2 IS NOT NULL)
					OR (conteo_1 IS NOT NULL AND conteo_2 IS NULL))");
			}
		}
		
		$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle %s
		ORDER BY numero ASC", $sqlBusq);
		$rs = mysql_query($query, $conex);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		// DATA
		$contFila = 0;
		$fill = false;
		while ($row = mysql_fetch_assoc($rs)) {
			$contReg++;
			$contFila++;
			
			$ubicacionItem = explode("-", $row['ubicacion']);
			
			if ($ubicacionItem[0] != $ubicacionAnterior[0]
			&& $ubicacionAnterior[0] != "") {
				$pdf->Cell($largoTabla,0,'','T');
				
				$pdf->SetY(-35);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','I',7);
				$pdf->Cell(0,8,((strlen($row['nombre_empleado']) > 0) ? "Registrado por: ".$row['nombre_empleado'] : ""),0,0,'L');
				$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
				$pdf->SetY(-35);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','I',8);
				$pdf->Cell(0,10,("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
				
				$contFila = 1;
			}
			
			if ($contFila % 22 == 1
			|| ($ubicacionItem[0] != $ubicacionAnterior[0] || $ubicacionAnterior[0] == "")) {//echo $ubicacionItem[0]." != ".$ubicacionAnterior[0]; exit;
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
				
				$pdf->Cell('',8,'',0,2);
			
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',12);
				$pdf->Cell(562,16,"Inventario Fisico",0,0,'C');
				$pdf->Ln();
				
				$pdf->Cell('',8,'',0,2);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7);
				$pdf->Cell(60,12,"Tipo Conteo: ",0,0,'R');
				$pdf->Cell(100,12,$rowInvFisico['orden_conteo_descripcion'],0,0,'L');
				$pdf->SetX(475);
				$pdf->Cell(60,12,"Fecha Inventario: ",0,0,'R');
				$pdf->Cell(40,12,date(spanDateFormat,strtotime($rowInvFisico['fecha'])),0,0,'C');
				$pdf->Ln();
				
				$pdf->Cell(60,12,"Articulos: ",0,0,'R');
				$pdf->Cell(100,12,$rowInvFisico['filtro_conteo_descripcion'],0,0,'L');
				$pdf->Ln();
				
				$pdf->Cell('',10);
				$pdf->Ln();
				
				// COLUMNAS
				//Colores, ancho de línea y fuente en negrita
				$pdf->SetFillColor(204,204,204);
				$pdf->SetDrawColor(153,153,153);
				$pdf->SetLineWidth(1);	
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7.5);
				
				$arrayCol = array(
					array("tamano" => "24", "campo" => "Nro."),
					array("tamano" => "80", "campo" => "Ubicación"),
					array("tamano" => "96", "campo" => "Código"),
					array("tamano" => "311", "campo" => "Descripción"),
					array("tamano" => "51", "campo" => "Lote"));
				
				if ($verKardex == true) {
					$arrayCol[3]['tamano'] -= 42;
					$arrayCol[] = array("tamano" => "42", "campo" => "Kardex");
				}
				
				if ($verConteo1 == true) {
					$arrayCol[3]['tamano'] -= 42;
					$arrayCol[] = array("tamano" => "42", "campo" => "Conteo 1");
				}
				
				if ($verConteo2 == true) {
					$arrayCol[3]['tamano'] -= 42;
					$arrayCol[] = array("tamano" => "42", "campo" => "Conteo 2");
				}
				
				if ($verConteo3 == true) {
					$arrayCol[3]['tamano'] -= 42;
					$arrayCol[] = array("tamano" => "42", "campo" => "Conteo 3");
				}
				
				$largoTabla = 0;
				foreach ($arrayCol as $indice => $valor) {
					$largoTabla += $valor['tamano'];
					$pdf->Cell($valor['tamano'],16,$valor['campo'],1,0,'C',true);
				}
				$pdf->Ln();
			}
			
			$pdf->SetFont('Arial','',7);
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
			$pdf->SetTextColor(0,0,0);
			
			$posY = $pdf->GetY();
			
			$pdf->Cell($arrayCol[0]['tamano'],27,$row['numero'],1,0,'R',true);
			$pdf->MultiCell($arrayCol[1]['tamano'],9,$row['descripcion_almacen']."\n".str_replace("-[]", "", $row['ubicacion']),'LT','C',true);
			$pdf->SetY($posY);
			$pdf->SetX($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + 24);
			$pdf->Cell($arrayCol[2]['tamano'],27,elimCaracter(utf8_decode($row['codigo_articulo']),";"),'TB',0,'L',true);
			$pdf->Cell($arrayCol[3]['tamano'],27,strtoupper(substr(utf8_decode($row['descripcion']),0,84)),'TB',0,'L',true);
			$pdf->Cell($arrayCol[4]['tamano'],27,$row['id_articulo_costo'],'TB',0,'R',true);
			$pos = 4;
			if ($verKardex == true) {
				$pos++;
				$pdf->Cell($arrayCol[$pos]['tamano'],27,valTpDato($row['existencia_kardex'], "cero_por_vacio"),1,0,'C',true);
			}
			
			if ($verConteo1 == true) {
				$pos++;
				if (($rowInvFisico['numero_conteo'] == 1 && $verData == true)
				|| ($rowInvFisico['numero_conteo'] == 2 && ($verConteo2 == true || $verConteo3 == true))
				|| ($verData == true && ($verConteo2 != true && $verConteo3 != true))
				|| ($rowInvFisico['estatus'] == 1)) {
					$pdf->Cell($arrayCol[$pos]['tamano'],27,valTpDato($row['conteo_1'], "cero_por_vacio"),1,0,'C',true);
				} else {
					$pdf->Cell($arrayCol[$pos]['tamano'],27,"",1,0,'C',true);
				}
			}
			
			if ($verConteo2 == true) {
				$pos++;
				if (($rowInvFisico['numero_conteo'] == 2 && $verData == true)
				|| ($rowInvFisico['numero_conteo'] == 3 && $verConteo3 == true)
				|| ($verData == true && ($verConteo1 != true && $verConteo3 != true))
				|| ($rowInvFisico['estatus'] == 1)) {
					$pdf->Cell($arrayCol[$pos]['tamano'],27,valTpDato($row['conteo_2'], "cero_por_vacio"),1,0,'C',true);
				} else {
					$pdf->Cell($arrayCol[$pos]['tamano'],27,"",1,0,'C',true);
				}
			}
			
			if ($verConteo3 == true) {
				$pos++;
				if (($rowInvFisico['numero_conteo'] == 3 && $verData == true)
				|| ($rowInvFisico['estatus'] == 1))
					$pdf->Cell($arrayCol[$pos]['tamano'],27,valTpDato($row['conteo_3'], "cero_por_vacio"),1,0,'C',true);
				else
					$pdf->Cell($arrayCol[$pos]['tamano'],27,"",1,0,'C',true);
			}
			$pdf->Ln();
			
			$fill = !$fill;
			
			if (($contFila % 22 == 0)
			|| ($contReg == $totalRows)) {
				$pdf->Cell($largoTabla,0,'','T');
				
				$pdf->SetY(-35);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','I',7);
				$pdf->Cell(0,8,((strlen($row['nombre_empleado']) > 0) ? "Registrado por: ".$row['nombre_empleado'] : ""),0,0,'L');
				$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
				$pdf->SetY(-35);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','I',8);
				$pdf->Cell(0,10,("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
				
				$contFila = 0;
			}
			$ubicacionAnterior = explode("-", $row['ubicacion']);
		}
	}
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////// COMPARATIVO //////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
foreach ($valCadBusq as $indice => $valor) {
	if ($indice > 1 && $valor == 4) {
		$queryInvFis = sprintf("SELECT * FROM vw_iv_inventario_fisico WHERE id_inventario_fisico = %s",
			valTpDato($idDocumento,"int"));
		$rsInvFis = mysql_query($queryInvFis);
		$rowInvFis = mysql_fetch_assoc($rsInvFis);
		
		if ($rowInvFis) {
			$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle WHERE id_inventario_fisico = %s
			ORDER BY numero ASC;",
				valTpDato($idDocumento,"int"));
			$rs = mysql_query($query);
			$totalRows = mysql_num_rows($rs);
			
			// DATA
			$contFila = 0;
			$fill = false;
			while ($row = mysql_fetch_assoc($rs)) {
				$contFila++;
				
				if ($contFila % 24 == 1) {
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
					$pdf->Cell(562,14,"Ajuste del Inventario Fisico",0,0,'C');
					$pdf->Ln();
					
					$pdf->SetTextColor(0,0,0);
					$pdf->SetFont('Arial','',7);
					$pdf->Cell(562,14,"(".$rowInvFis['filtro_conteo_descripcion'].")",0,0,'C');
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
					//Colores, ancho de línea y fuente en negrita
					$pdf->SetFillColor(204,204,204);
					$pdf->SetDrawColor(153,153,153);
					$pdf->SetLineWidth(1);	
					$pdf->SetTextColor(0,0,0);
					$pdf->SetFont('Arial','',6.8);
					
					$arrayCol = array(
						array("tamano" => "16", "campo" => "\n\n"),
						array("tamano" => "78", "campo" => "CÓDIGO\n\n"),
						array("tamano" => "182", "campo" => "DESCRIPCIÓN\n\n"),
						array("tamano" => "36", "campo" => "UNIDAD\n\n"),
						array("tamano" => "56", "campo" => "LOCALIZACIÓN\n\n"),
						array("tamano" => "50", "campo" => "LOTE / COSTO UNIT."),
						array("tamano" => "53", "campo" => "KARDEX UNID./VALOR"),
						array("tamano" => "53", "campo" => "FISICO UNID./VALOR"),
						array("tamano" => "53", "campo" => "DIFERENCIAS UNID./VALOR"));
					$largoTabla = 0;
					$posY = $pdf->GetY();
					$posX = $pdf->GetX();
					foreach ($arrayCol as $indice => $valor) {
						$largoTabla += $valor['tamano'];
						
						$pdf->SetY($posY); $pdf->SetX($posX); 
						$pdf->MultiCell($valor['tamano'],8,$valor['campo'],1,'C',true);
						
						$posX += $valor['tamano'];
					}
				}
				
				// RESTAURACION DE COLOR Y FUENTE
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				
				$pdf->SetFont('Arial','',5.6);
				$pdf->SetTextColor(0,0,0);
				
				$cantConteo = $row['conteo_'.$rowInvFis['numero_conteo']];
				$cantDiferencia = $cantConteo - $row['existencia_kardex'];
				
				$pdf->Cell($arrayCol[0]['tamano'],14,$row['numero'],1,0,'C',true);
				$pdf->Cell($arrayCol[1]['tamano'],14,elimCaracter($row['codigo_articulo'],";"),1,0,'L',true);
				$pdf->Cell($arrayCol[2]['tamano'],14,strtoupper(substr($row['descripcion'],0,45)),1,0,'L',true);
				$pdf->Cell($arrayCol[3]['tamano'],14,$row['unidad'],1,0,'C',true);
				$posY = $pdf->GetY();
				$posX = $pdf->GetX();
				$pdf->MultiCell($arrayCol[4]['tamano'],7,$row['descripcion_almacen']."\n".str_replace("-[]", "", $row['ubicacion']),1,'C',true);
				$posX += $arrayCol[4]['tamano'];
				
				$pdf->SetFont('Arial','B',5.6);
				$pdf->SetY($posY); $pdf->SetX($posX);
				$pdf->Cell($arrayCol[5]['tamano'],14,$row['id_articulo_costo'],1,0,'R',true);
				$pdf->SetFont('Arial','',5.6);
				$pdf->Cell($arrayCol[6]['tamano'],14,number_format($row['existencia_kardex'], 2, ".", ","),1,0,'R',true);
				$pdf->Cell($arrayCol[7]['tamano'],14,number_format($cantConteo, 2, ".", ","),1,0,'R',true);
				$pdf->Cell($arrayCol[8]['tamano'],14,number_format($cantDiferencia, 2, ".", ","),1,0,'R',true);
				
				$pdf->Ln();
				
				$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'];
				$pdf->Cell($tamano,16,"",1,0,'L',true);
				$pdf->Cell($arrayCol[5]['tamano'],14,number_format($row['costo_proveedor'], 2, ".", ","),1,0,'R',true);
				$pdf->Cell($arrayCol[6]['tamano'],14,number_format(($row['existencia_kardex'] * $row['costo_proveedor']), 2, ".", ","),1,0,'R',true);
				$pdf->Cell($arrayCol[7]['tamano'],14,number_format(($cantConteo * $row['costo_proveedor']), 2, ".", ","),1,0,'R',true);
				$pdf->Cell($arrayCol[8]['tamano'],14,number_format(($cantDiferencia * $row['costo_proveedor']), 2, ".", ","),1,0,'R',true);
				
				$pdf->Ln();
				
				$fill = !$fill;
				
				$arrayTotales[0] += $row['existencia_kardex'] * $row['costo_proveedor'];
				$arrayTotales[1] += $cantConteo * $row['costo_proveedor'];
				$arrayTotales[2] += $cantDiferencia * $row['costo_proveedor'];
				
				
				if (($contFila % 24 == 0) || $contFila == $totalRows) {
					$arrayTotalesTotal[0] += $arrayTotales[0];
					$arrayTotalesTotal[1] += $arrayTotales[1];
					$arrayTotalesTotal[2] += $arrayTotales[2];
					
					$pdf->SetFillColor(255,255,255);
					
					$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'];
					$pdf->Cell($tamano,14,"",'T',0,'L',true);
					$tamano = $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'];
					$pdf->SetFillColor(204,204,204);
					$pdf->Cell($tamano,14,"Total de Hoja:",1,0,'R',true);
					$pdf->SetFillColor(255,238,213);
					$pdf->Cell($arrayCol[6]['tamano'],14,number_format($arrayTotales[0], 2, ".", ","),1,0,'R',true);
					$pdf->Cell($arrayCol[7]['tamano'],14,number_format($arrayTotales[1], 2, ".", ","),1,0,'R',true);
					$pdf->Cell($arrayCol[8]['tamano'],14,number_format($arrayTotales[2], 2, ".", ","),1,0,'R',true);
					
					$pdf->Ln();
					
					$arrayTotales = NULL;
					
					if ($contFila == $totalRows) {
						$pdf->SetFillColor(255,255,255);
						
						$tamano = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'];
						$pdf->Cell($tamano,14,"",0,0,'L',true);
						$tamano = $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'];
						$pdf->SetFillColor(204,204,204);
						$pdf->Cell($tamano,14,"Total de Totales:",1,0,'R',true);
						$pdf->SetFillColor(223,255,223);
						$pdf->Cell($arrayCol[6]['tamano'],14,number_format($arrayTotalesTotal[0], 2, ".", ","),1,0,'R',true);
						$pdf->Cell($arrayCol[7]['tamano'],14,number_format($arrayTotalesTotal[1], 2, ".", ","),1,0,'R',true);
						$pdf->Cell($arrayCol[8]['tamano'],14,number_format($arrayTotalesTotal[2], 2, ".", ","),1,0,'R',true);
					}
	
					$pdf->SetY(-35);
					$pdf->SetTextColor(0,0,0);
					$pdf->SetFont('Arial','I',6);
					$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
					$pdf->SetY(-35);
					$pdf->SetTextColor(0,0,0);
					$pdf->SetFont('Arial','I',8);
					$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
				}
			}
		}
	}
}

 
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// FALTANTES ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
foreach ($valCadBusq as $indice => $valor) {
	if ($indice > 1 && $valor == 5) {
		$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle
		WHERE id_inventario_fisico = %s
			AND (conteo_%s - existencia_kardex) < 0
		ORDER BY numero ASC;",
			valTpDato($idDocumento,"int"),
			valTpDato($rowInvFisico['cantidad_conteo'],"campo"));
		$rs = mysql_query($query);
		$totalRows = mysql_num_rows($rs);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		if ($totalRows > 0) {
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
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell(562,16,"Listado de Códigos Descuadrados",0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(60,14,"Tipo Conteo: ",0,0,'R');
			$pdf->Cell(100,14,$rowInvFisico['orden_conteo_descripcion'],0,0,'L');
			$pdf->SetX(485);
			$pdf->Cell(60,14,"Fecha: ",0,0,'R');
			$pdf->Cell(45,14,date(spanDateFormat,strtotime($rowInvFisico['fecha'])),0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell(60,14,"Articulos: ",0,0,'R');
			$pdf->Cell(100,14,$rowInvFisico['filtro_conteo_descripcion'],0,0,'L');
			
			$pdf->Cell('',10);
			$pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(562,10,"SOLO LOS FALTANTES",'B',0,'R');
			$pdf->Ln();
			$pdf->Cell('',3);
			$pdf->Ln();
			
			/* COLUMNAS */
			//Colores, ancho de línea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			
			$arrayCol = array(
				array("tamano" => "28", "campo" => "Nro."),
				array("tamano" => "86", "campo" => "CÓDIGO"),
				array("tamano" => "232", "campo" => "DESCRIPCIÓN"),
				array("tamano" => "48", "campo" => "TIPO"),
				array("tamano" => "84", "campo" => "UBICACIÓN"),
				array("tamano" => "38", "campo" => "LOTE"),
				array("tamano" => "46", "campo" => "CANTIDAD"));
			$largoTabla = 0;
			foreach ($arrayCol as $indice => $valor) {
				$largoTabla += $valor['tamano'];
				$pdf->Cell($valor['tamano'],16,$valor['campo'],1,0,'C',true);
			}
			$pdf->Ln();
			
			/* DATA */
			while ($row = mysql_fetch_assoc($rs)) {
				// RESTAURACION DE COLOR Y FUENTE
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('');
				
				$pdf->Cell($arrayCol[0]['tamano'],14,$row['numero'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[1]['tamano'],14,elimCaracter($row['codigo_articulo'],";"),'LR',0,'L',true);
				$pdf->Cell($arrayCol[2]['tamano'],14,utf8_decode(strtoupper(substr($row['descripcion'],0,60))),'LR',0,'L',true);
				$pdf->Cell($arrayCol[3]['tamano'],14,$row['tipo_articulo'],'LR',0,'C',true);
				$posY = $pdf->GetY();
				$posX = $pdf->GetX();
				$pdf->MultiCell($arrayCol[4]['tamano'],7,$row['descripcion_almacen']."\n".str_replace("-[]", "", $row['ubicacion']),'LR','C',true);
				$posX += $arrayCol[4]['tamano'];
				$pdf->SetY($posY); $pdf->SetX($posX);
				$pdf->Cell($arrayCol[5]['tamano'],14,$row['id_articulo_costo'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[6]['tamano'],14,((-1) * ($row['conteo_'.$rowInvFisico['cantidad_conteo']] - $row['existencia_kardex'])),'LR',0,'R',true);
				$pdf->Ln();
				
				$fill = !$fill;
			}
			$pdf->Cell($largoTabla,0,'','T');
	
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',6);
			$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// SOBRANTES ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
foreach ($valCadBusq as $indice => $valor) {
	if ($indice > 1 && $valor == 6) {
		$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle
		WHERE id_inventario_fisico = %s
			AND (conteo_%s - existencia_kardex) > 0
		ORDER BY numero ASC;",
			valTpDato($idDocumento,"int"),
			valTpDato($rowInvFisico['cantidad_conteo'],"campo"));
		$rs = mysql_query($query);
		$totalRows = mysql_num_rows($rs);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		if ($totalRows > 0) {
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
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell(562,16,"Listado de Códigos Descuadrados",0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(60,14,"Tipo Conteo: ",0,0,'R');
			$pdf->Cell(100,14,$rowInvFisico['orden_conteo_descripcion'],0,0,'L');
			$pdf->SetX(485);
			$pdf->Cell(60,14,"Fecha: ",0,0,'R');
			$pdf->Cell(45,14,date(spanDateFormat,strtotime($rowInvFisico['fecha'])),0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell(60,14,"Articulos: ",0,0,'R');
			$pdf->Cell(100,14,$rowInvFisico['filtro_conteo_descripcion'],0,0,'L');
			
			$pdf->Cell('',10);
			$pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(562,10,"SOLO LOS SOBRANTES",'B',0,'R');
			$pdf->Ln();
			$pdf->Cell('',3);
			$pdf->Ln();
			
			/* COLUMNAS */
			//Colores, ancho de línea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			
			$arrayCol = array(
				array("tamano" => "28", "campo" => "Nro."),
				array("tamano" => "86", "campo" => "CÓDIGO"),
				array("tamano" => "232", "campo" => "DESCRIPCIÓN"),
				array("tamano" => "48", "campo" => "TIPO"),
				array("tamano" => "84", "campo" => "UBICACIÓN"),
				array("tamano" => "38", "campo" => "LOTE"),
				array("tamano" => "46", "campo" => "CANTIDAD"));
			$largoTabla = 0;
			foreach ($arrayCol as $indice => $valor) {
				$largoTabla += $valor['tamano'];
				$pdf->Cell($valor['tamano'],16,$valor['campo'],1,0,'C',true);
			}
			$pdf->Ln();
			
			/* DATA */
			while ($row = mysql_fetch_assoc($rs)) {
				// RESTAURACION DE COLOR Y FUENTE
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('');
				
				$pdf->Cell($arrayCol[0]['tamano'],14,$row['numero'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[1]['tamano'],14,elimCaracter($row['codigo_articulo'],";"),'LR',0,'L',true);
				$pdf->Cell($arrayCol[2]['tamano'],14,utf8_decode(strtoupper(substr($row['descripcion'],0,60))),'LR',0,'L',true);
				$pdf->Cell($arrayCol[3]['tamano'],14,$row['tipo_articulo'],'LR',0,'C',true);
				$posY = $pdf->GetY();
				$posX = $pdf->GetX();
				$pdf->MultiCell($arrayCol[4]['tamano'],7,$row['descripcion_almacen']."\n".str_replace("-[]", "", $row['ubicacion']),'LR','C',true);
				$posX += $arrayCol[4]['tamano'];
				$pdf->SetY($posY); $pdf->SetX($posX);
				$pdf->Cell($arrayCol[5]['tamano'],14,$row['id_articulo_costo'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[6]['tamano'],14,($row['conteo_'.$rowInvFisico['cantidad_conteo']] - $row['existencia_kardex']),'LR',0,'R',true);
				$pdf->Ln();
				
				$fill = !$fill;
			}
			$pdf->Cell($largoTabla,0,'','T');
	
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',6);
			$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////// SALIDA /////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
foreach ($valCadBusq as $indice => $valor) {
	if ($indice > 1 && $valor == 7) {
		$query = sprintf("SELECT * FROM iv_vale_salida
		WHERE id_documento = %s
			AND tipo_vale_salida = 5",
			valTpDato($idDocumento,"int"));
		$rs = mysql_query($query);
		$totalRows = mysql_num_rows($rs);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($totalRows > 0) {
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
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell(562,16,utf8_decode("AJUSTE DE SALIDA"),0,0,'C');
			$pdf->Ln(5);
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->SetX(485);
			$pdf->Cell(60,14,utf8_decode("Nro. Vale Salida: "),0,0,'R');
			$pdf->Cell(100,14,utf8_decode($row['numeracion_vale_salida']),0,0,'L');
			$pdf->Ln();
			$pdf->SetX(485);
			$pdf->Cell(60,14,"Fecha: ",0,0,'R');
			$pdf->Cell(45,14,date(spanDateFormat,strtotime($row['fecha'])),0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',10);
			$pdf->Ln();
			
			/* COLUMNAS */
			//Colores, ancho de línea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			
			$arrayCol = array(
				array("tamano" => "94", "campo" => "Código"),
				array("tamano" => "270", "campo" => "Descripción"),
				array("tamano" => "50", "campo" => "Lote"),
				array("tamano" => "48", "campo" => "Cantidad"),
				array("tamano" => "50", "campo" => "Costo"),
				array("tamano" => "50", "campo" => "Total"));
			$largoTabla = 0;
			foreach ($arrayCol as $indice => $valor) {
				$largoTabla += $valor['tamano'];
				$pdf->Cell($valor['tamano'],16,$valor['campo'],1,0,'C',true);
			}
			$pdf->Ln();
			
			/* DATA */
			$queryDet = sprintf("SELECT vale_salida_det.*,
				vw_iv_art.id_articulo,
				vw_iv_art.codigo_articulo,
				vw_iv_art.descripcion,
				vw_iv_art.id_tipo_articulo,
				vw_iv_art.tipo_articulo
			FROM vw_iv_articulos_datos_basicos vw_iv_art
				INNER JOIN iv_vale_salida_detalle vale_salida_det ON (vw_iv_art.id_articulo = vale_salida_det.id_articulo)
			WHERE vale_salida_det.id_vale_salida = %s
			ORDER BY vale_salida_det.id_vale_salida_detalle ASC;",
				valTpDato($row['id_vale_salida'],"int"));
			$rsDet = mysql_query($queryDet);
			if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($rowDet = mysql_fetch_assoc($rsDet)) {
				// RESTAURACION DE COLOR Y FUENTE
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('');
				
				$pdf->Cell($arrayCol[0]['tamano'],16,elimCaracter($rowDet['codigo_articulo'],";"),'LR',0,'L',true);
				$pdf->Cell($arrayCol[1]['tamano'],16,strtoupper(substr($rowDet['descripcion'],0,60)),'LR',0,'L',true);
				$pdf->Cell($arrayCol[2]['tamano'],16,$rowDet['id_articulo_costo'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[3]['tamano'],16,$rowDet['cantidad'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[4]['tamano'],16,number_format($rowDet['costo_compra'], 2, ".", ","),'LR',0,'R',true);
				$pdf->Cell($arrayCol[5]['tamano'],16,number_format(($rowDet['cantidad'] * $rowDet['costo_compra']), 2, ".", ","),'LR',0,'R',true);
				$pdf->Ln();
				
				$fill = !$fill;
			}
			$pdf->Cell($largoTabla,0,'','T');
			
			$pdf->SetY(650);
			$pdf->SetX(430);
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			$pdf->Cell(57,14,"Total: ",1,0,'R',true);
			$pdf->Cell(98,14,number_format($row['subtotal_documento'], 2, ".", ","),1,0,'R');
			$pdf->Ln();
			$pdf->Ln();
			
			$pdf->Cell($largoTabla,0,'','T');
			$pdf->Ln();
			$pdf->Cell('',2,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(60,14,"Referencia: ",0,0,'R');
			$pdf->Ln();
			$pdf->Cell(60,14,"Observación: ",0,0,'R');
			$pdf->Cell(502,14,$row['observacion'],0,0,'L');
	
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',6);
			$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////// ENTRADA ////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
foreach ($valCadBusq as $indice => $valor) {
	if ($indice > 1 && $valor == 8) {
		$query = sprintf("SELECT * FROM iv_vale_entrada
		WHERE id_documento = %s
			AND tipo_vale_entrada = 5",
			valTpDato($idDocumento,"int"));
		$rs = mysql_query($query);
		$totalRows = mysql_num_rows($rs);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($totalRows > 0) {
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
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell(562,16,"AJUSTE DE ENTRADA",0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->SetX(485);
			$pdf->Cell(60,14,"Nro. Vale Entrada: ",0,0,'R');
			$pdf->Cell(100,14,$row['numeracion_vale_entrada'],0,0,'L');
			$pdf->Ln();
			$pdf->SetX(485);
			$pdf->Cell(60,14,"Fecha: ",0,0,'R');
			$pdf->Cell(45,14,date(spanDateFormat,strtotime($row['fecha'])),0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',10);
			$pdf->Ln();
			
			/* COLUMNAS */
			//Colores, ancho de línea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			
			$arrayCol = array(
				array("tamano" => "94", "campo" => "Código"),
				array("tamano" => "270", "campo" => "Descripción"),
				array("tamano" => "50", "campo" => "Lote"),
				array("tamano" => "48", "campo" => "Cantidad"),
				array("tamano" => "50", "campo" => "Costo"),
				array("tamano" => "50", "campo" => "Total"));
			$largoTabla = 0;
			foreach ($arrayCol as $indice => $valor) {
				$largoTabla += $valor['tamano'];
				$pdf->Cell($valor['tamano'],16,$valor['campo'],1,0,'C',true);
			}
			$pdf->Ln();
			
			/* DATA */
			$queryDet = sprintf("SELECT vale_entrada_det.*,
				vw_iv_art.id_articulo,
				vw_iv_art.codigo_articulo,
				vw_iv_art.descripcion,
				vw_iv_art.id_tipo_articulo,
				vw_iv_art.tipo_articulo
			FROM vw_iv_articulos_datos_basicos vw_iv_art
				INNER JOIN iv_vale_entrada_detalle vale_entrada_det ON (vw_iv_art.id_articulo = vale_entrada_det.id_articulo)
			WHERE vale_entrada_det.id_vale_entrada = %s
			ORDER BY vale_entrada_det.id_vale_entrada_detalle ASC;",
				valTpDato($row['id_vale_entrada'],"int"));
			$rsDet = mysql_query($queryDet);
			if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			while ($rowDet = mysql_fetch_assoc($rsDet)) {
				// RESTAURACION DE COLOR Y FUENTE
				($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('');
				
				$pdf->Cell($arrayCol[0]['tamano'],16,elimCaracter($rowDet['codigo_articulo'],";"),'LR',0,'L',true);
				$pdf->Cell($arrayCol[1]['tamano'],16,strtoupper(substr($rowDet['descripcion'],0,60)),'LR',0,'L',true);
				$pdf->Cell($arrayCol[2]['tamano'],16,$rowDet['id_articulo_costo'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[3]['tamano'],16,$rowDet['cantidad'],'LR',0,'R',true);
				$pdf->Cell($arrayCol[4]['tamano'],16,number_format($rowDet['precio_venta'], 2, ".", ","),'LR',0,'R',true);
				$pdf->Cell($arrayCol[5]['tamano'],16,number_format(($rowDet['cantidad'] * $rowDet['precio_venta']), 2, ".", ","),'LR',0,'R',true);
				$pdf->Ln();
				
				$fill = !$fill;
			}
			$pdf->Cell($largoTabla,0,'','T');

			$pdf->SetY(650);
			$pdf->SetX(430);
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
			$pdf->Cell(57,14,"Total: ",1,0,'R',true);
			$pdf->Cell(98,14,number_format($row['subtotal_documento'], 2, ".", ","),1,0,'R');
			$pdf->Ln();
			$pdf->Ln();
			
			$pdf->Cell($largoTabla,0,'','T');
			$pdf->Ln();
			$pdf->Cell('',2,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(60,14,"Referencia: ",0,0,'R');
			$pdf->Ln();
			$pdf->Cell(60,14,"Observación: ",0,0,'R');
			$pdf->Cell(502,14,$row['observacion'],0,0,'L');
	
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',6);
			$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
			$pdf->SetY(-35);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}


$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>
