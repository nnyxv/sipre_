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
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idEmpresa = $valCadBusq[0];
$idCuenta = $valCadBusq[1];
$idConciliacion = $valCadBusq[2];

$idEmpresa = ($idEmpresa > 0) ? $idEmpresa : 100 ;

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$fecha= time();



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// RESUMEN CONCILIACION ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$query = sprintf("SELECT 
  bancos.nombreBanco,
  te_estado_cuenta.fecha_registro,
  te_estado_cuenta.tipo_documento,
  te_estado_cuenta.numero_documento,
  te_estado_cuenta.monto,
  cuentas.numeroCuentaCompania
FROM
  te_estado_cuenta
  INNER JOIN cuentas ON (te_estado_cuenta.id_cuenta = cuentas.idCuentas)
  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
WHERE
  te_estado_cuenta.id_conciliacion=".$idConciliacion."");
  

$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$row = mysql_fetch_assoc($rs);


$queryDP = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'DP'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsDP = mysql_query($queryDP);
if (!$rsDP) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowDP = mysql_fetch_assoc($rsDP);


$queryNC = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'NC'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsNC = mysql_query($queryNC);
if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowNC = mysql_fetch_assoc($rsNC);


$queryND = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'ND'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsND = mysql_query($queryND);
if (!$rsND) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowND = mysql_fetch_assoc($rsND);


$queryTR = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'TR'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsTR = mysql_query($queryTR);
if (!$rsTR) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowTR = mysql_fetch_assoc($rsTR);


$queryCH = sprintf("SELECT COUNT(*) as totalRegistros, sum(monto) as monto
FROM te_estado_cuenta
WHERE te_estado_cuenta.tipo_documento LIKE 'CH'
AND te_estado_cuenta.id_conciliacion =".$idConciliacion."");
$rsCH = mysql_query($queryCH);
if (!$rsCH) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowCH = mysql_fetch_assoc($rsCH);





$queryConciliacion = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$idConciliacion);
$rsConciliacion = mysql_query($queryConciliacion);
if (!$rsConciliacion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowConciliacion = mysql_fetch_array($rsConciliacion);

$queryConAnt = sprintf("SELECT * FROM te_conciliacion WHERE id_cuenta = ".$idCuenta." AND id_conciliacion <> ".$idConciliacion." ORDER BY id_conciliacion DESC LIMIT 0 , 1");
$rsConAnt = mysql_query($queryConAnt);
if (!$rsConAnt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowConAnt = mysql_fetch_array($rsConAnt);




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
		$pdf->Cell(562,16,"RESUMEN CONCILIACION  ".date(spanDateFormat,strtotime($rowConciliacion['fecha']))."",0,0,'C');
		$pdf->Ln();
		
		$pdf->Cell('',8,'',0,2);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',7);
		$pdf->SetX(485);
		$pdf->Cell(60,12,"Fecha: ",0,0,'R');
		$pdf->Cell(45,12,date(spanDateFormat,$fecha),0,0,'C');
		$pdf->Ln();
		$pdf->Cell('',3);
		$pdf->Ln();
			
		
		
		/* COLUMNAS */
		//Colores, ancho de linea y fuente en negrita
		$pdf->SetFillColor(204,204,204);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetDrawColor(153,153,153);
		$pdf->SetLineWidth(1);	
		$pdf->SetFont('Arial','',6.8);

	
		$pdf->Cell(281,14,"BANCO: ".$row['nombreBanco']."",'1','L',false);
		$pdf->Cell(281,14,"NUMERO DE CUENTA: ".$row['numeroCuentaCompania']."",'1','L',false);
		$pdf->Ln();
		$fill = !$fill;
		
/*		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(50,14,folio($row['id_documento'],$row['tipo_documento']),'LR',0,'C',true);
		$pdf->Cell(50,14,date(spanDateFormat,strtotime($row['fecha_registro'])),'LR',0,'C',true);
		$pdf->Cell(50,14,$row['tipo_documento'],'LR',0,'C',true);
		$pdf->Cell(50,14,$rowEmp['nombre_empresa'],'LR',0,'L',true);
		$pdf->Cell(50,14,$row['id_estado_cuenta'],'LR',0,'C',true);
		$pdf->Cell(50,14,number_format(($row['monto']),2,".",","),'LR',0,'R',true);
		$pdf->Cell(50,14,$row['estados_principales'],'LR',0,'C',true);

		$pdf->Ln();
		*/
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"DEPOSITOS EFECTUADOS:      ".$rowDP['totalRegistros']."",'1','L',false);
		$pdf->Cell(281,14,"".$rowDP['monto']."",'1','L',true);
		$pdf->Ln();
		$fill = !$fill;
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(281,14,"NOTAS DE CREDITO:                 ".$rowNC['totalRegistros']."",'1','L',false);
		$pdf->Cell(281,14,"".number_format(($rowNC['monto']),2,".",",")."",'1','R',true);
		$pdf->Ln();
		$fill = !$fill;
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"NOTAS DE DEBITO:                    ".$rowND['totalRegistros']."",'1','L',false);
		$pdf->Cell(281,14,"".number_format(($rowND['monto']),2,".",",")."",'1','L',true);
		$pdf->Ln();
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"CHEQUES:                                   ".$rowCH['totalRegistros']."",'1','L',false);
		$pdf->Cell(281,14,"".number_format(($rowCH['monto']),2,".",",")."",'1','L',true);
		$pdf->Ln();
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"TRANSFERENCIAS:                    ".$rowTR['totalRegistros']."",'1','L',false);
		$pdf->Cell(281,14,"".number_format(($rowTR['monto']),2,".",",")."",'1','L',true);
		$pdf->Ln();
		
		
		$pdf->Ln();
		$pdf->Ln();
		$pdf->Ln();
		
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"SALDO CONCILIADO:   ",'1','L',false);
		$pdf->Cell(281,14,"".number_format(($rowConciliacion['monto_conciliado']),2,".",",")."",'1','L',true);
		$pdf->Ln();
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"DIFERENCIA CONCILIACION:   ",'1','L',false);
		$Diferencia= $rowConciliacion['monto_conciliado']-$rowConciliacion['saldo_banco'];
		$pdf->Cell(281,14,"".number_format($Diferencia,2,".",",")."",'1','L',true);
		$pdf->Ln();
		
		$pdf->SetFillColor(234,244,255);
		$pdf->Cell(281,14,"SALDO EN LIBROS:   ",'1','L',false);
		$pdf->Cell(281,14,"".number_format(($rowConciliacion['monto_libro']),2,".",",")."",'1','L',true);
		$pdf->Ln();
		
		
		

			
			

			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		




/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// CONCILIADO ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$query = sprintf("SELECT 
  bancos.nombreBanco,
  te_estado_cuenta.id_estado_cuenta,
  te_estado_cuenta.fecha_registro,
  te_estado_cuenta.tipo_documento,
  te_estado_cuenta.id_documento,
  te_estado_cuenta.numero_documento,
  te_estado_cuenta.monto,
  te_estado_cuenta.estados_principales
FROM
  te_estado_cuenta
  INNER JOIN cuentas ON (te_estado_cuenta.id_cuenta = cuentas.idCuentas)
  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
WHERE
  te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.estados_principales = 3 AND te_estado_cuenta.id_cuenta =".$idCuenta." AND te_estado_cuenta.id_conciliacion=".$idConciliacion."");
$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);

if ($totalRows > 0) {
	
	/* DATA */
	$contFila = 0;
	$fill = false;
	while ($row = mysql_fetch_assoc($rs)) {
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
			$pdf->Cell(562,16,"MOVIMIENTOS DE TESORERIA",0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
	
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->SetX(485);
			$pdf->Cell(60,12,"Fecha: ",0,0,'R');
			$pdf->Cell(45,12,date(spanDateFormat,$fecha),0,0,'C');
			$pdf->Ln();
					
			
			$pdf->SetTextColor(0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(562,10,"Documentos Conciliados",'B',0,'R');
			$pdf->Ln();
			$pdf->Cell('',3);
			$pdf->Ln();
			
			/* COLUMNAS */
			//Colores, ancho de linea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
				
			$arrayTamCol = array("130","52","50","30","190","50","50","10");
			$arrayCol = array("BANCO\n\n",utf8_decode("FOLIO APLICACIÓN"),"FECHA\n\n","TIPO\n\n","BENEFICIARIO\n\n","REF MOV\n\n","IMPORTE\n\n" ,"E");
	
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
								
				$pdf->MultiCell($arrayTamCol[$indice],8,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//Restauracion de colores y fuentes
		if ($fill == true)
			$pdf->SetFillColor(234,244,255);
		else
			$pdf->SetFillColor(255,255,255);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		
		$pdf->Cell($arrayTamCol[0],14,$row['nombreBanco'],'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[1],14,folio($row['id_documento'],$row['tipo_documento']),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[2],14,date(spanDateFormat,strtotime($row['fecha_registro'])),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[3],14,$row['tipo_documento'],'LR',0,'C',true);
		
		if ($row['tipo_documento']=='NC' OR $row['tipo_documento']=='ND' OR $row['tipo_documento']=='DP'){
			$pdf->Cell($arrayTamCol[4],14,$rowEmp['nombre_empresa'],'LR',0,'L',true);
		}else{
			$pdf->Cell($arrayTamCol[4],14,beneficiario($row['id_documento'],$row['tipo_documento']),'LR',0,'L',true);
		}
		
		$pdf->Cell($arrayTamCol[5],14,$row['numero_documento'],'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[6],14,number_format(($row['monto']),2,".",","),'LR',0,'R',true);
		$pdf->Cell($arrayTamCol[7],14,$row['estados_principales'],'LR',0,'C',true);

		$pdf->Ln();
		
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
			
			
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			if ($contFila == $totalRows) {
					$pdf->Ln();
					$pdf->SetFillColor(255);
					$pdf->Cell(562,5,"",'T',0,'L',true);
					$pdf->Ln();
					
				$queryConciliacion = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$idConciliacion);
				$rsConciliacion = mysql_query($queryConciliacion) or die(mysql_error());
				$rowConciliacion = mysql_fetch_array($rsConciliacion);
			
					
						$pdf->SetFillColor(204,204,204);
						$pdf->Cell(187,14,"MONTO CONCILIADO:  ".number_format(($rowConciliacion['monto_conciliado']),2,".",","),1,0,'L',true);
						$pdf->Cell(187,14,"TOTAL CREDITOS:  ".number_format(($rowConciliacion['total_credito']),2,".",","),1,0,'L',true);
						$pdf->Cell(187,14,"TOTAL DEBITOS:  ".number_format(($rowConciliacion['total_debito']),2,".",","),1,0,'L',true);
						
					}

			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// APLICADOS ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$query = sprintf("SELECT 
  bancos.nombreBanco,
  te_estado_cuenta.id_estado_cuenta,
  te_estado_cuenta.fecha_registro,
  te_estado_cuenta.tipo_documento,
  te_estado_cuenta.id_documento,
  te_estado_cuenta.numero_documento,
  te_estado_cuenta.monto,
  te_estado_cuenta.estados_principales
FROM
  te_estado_cuenta
  INNER JOIN cuentas ON (te_estado_cuenta.id_cuenta = cuentas.idCuentas)
  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
WHERE
  te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.estados_principales = 2 AND te_estado_cuenta.id_cuenta =".$idCuenta."");
$rs = mysql_query($query);
$totalRows = mysql_num_rows($rs);
if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);


$queryDebito = sprintf("SELECT sum( `monto` ) AS monto
FROM `te_estado_cuenta`
WHERE te_estado_cuenta.desincorporado <>0
AND te_estado_cuenta.estados_principales >=1
AND te_estado_cuenta.estados_principales <=2
AND te_estado_cuenta.id_cuenta =".$idCuenta."
AND `suma_resta` =0");
$rsDebito = mysql_query($queryDebito);
if (!$rsDebito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowDebito = mysql_fetch_array($rsDebito);

$queryCredito = sprintf("SELECT sum( `monto` ) AS monto
FROM `te_estado_cuenta`
WHERE te_estado_cuenta.desincorporado <>0
AND te_estado_cuenta.estados_principales >=1
AND te_estado_cuenta.estados_principales <=2
AND te_estado_cuenta.id_cuenta =".$idCuenta."
AND `suma_resta` =1");
$rsCredito = mysql_query($queryCredito);
if (!$rsCredito) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowCredito = mysql_fetch_array($rsCredito);


if ($totalRows > 0) {
	
	/* DATA */
	$contFila = 0;
	$fill = false;
	while ($row = mysql_fetch_assoc($rs)) {
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
			$pdf->Cell(562,16,"MOVIMIENTOS DE TESORERIA",0,0,'C');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
	
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->SetX(485);
			$pdf->Cell(60,12,"Fecha: ",0,0,'R');
			$pdf->Cell(45,12,date(spanDateFormat,$fecha),0,0,'C');
			$pdf->Ln();
					
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',9);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->Cell(562,10,"Documentos Por Conciliar",'B',0,'R');
			$pdf->Ln();
			$pdf->Cell('',3);
			$pdf->Ln();
			
			
			/* COLUMNAS */
			//Colores, ancho de linea y fuente en negrita
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);	
			$pdf->SetFont('Arial','',6.8);
				
			$arrayTamCol = array("130","52","50","30","190","50","50","10");
			$arrayCol = array("BANCO\n\n",utf8_decode("FOLIO APLICACIÓN"),"FECHA\n\n","TIPO\n\n","BENEFICIARIO\n\n","REF MOV\n\n","IMPORTE\n\n" ,"E");
	
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
								
				$pdf->MultiCell($arrayTamCol[$indice],8,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//Restauracion de colores y fuentes
		if ($fill == true)
			$pdf->SetFillColor(234,244,255);
		else
			$pdf->SetFillColor(255,255,255);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		
		$pdf->Cell($arrayTamCol[0],14,$row['nombreBanco'],'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[1],14,folio($row['id_documento'],$row['tipo_documento']),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[2],14,date(spanDateFormat,strtotime($row['fecha_registro'])),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[3],14,$row['tipo_documento'],'LR',0,'C',true);
		if ($row['tipo_documento']=='NC' OR $row['tipo_documento']=='ND' OR $row['tipo_documento']=='DP'){
			$pdf->Cell($arrayTamCol[4],14,$rowEmp['nombre_empresa'],'LR',0,'L',true);}
		else {$pdf->Cell($arrayTamCol[4],14,beneficiario($row['id_documento'],$row['tipo_documento']),'LR',0,'L',true);}
		
		$pdf->Cell($arrayTamCol[5],14,$row['numero_documento'],'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[6],14,number_format(($row['monto']),2,".",","),'LR',0,'R',true);
		$pdf->Cell($arrayTamCol[7],14,$row['estados_principales'],'LR',0,'C',true);

		$pdf->Ln();
		
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');

				if ($contFila == $totalRows) {
					$pdf->Ln();
					$pdf->SetFillColor(255);
					$pdf->Cell(562,5,"",'T',0,'L',true);
					$pdf->Ln();
					
						$pdf->SetFillColor(204,204,204,204);
						$pdf->Cell(141,14,"SALDO ANTERIOR:  ".number_format(($rowConciliacion['saldo_ant']),2,".",","),1,0,'L',true);
						$pdf->Cell(140,14,"TOTAL CREDITOS:  ".number_format(($rowCredito['monto']),2,".",","),1,0,'L',true);
						$pdf->Cell(140,14,"TOTAL DEBITOS:  ".number_format(($rowDebito['monto']),2,".",","),1,0,'L',true);
						$pdf->Cell(141,14,"SALDO ACTUAL:  ".number_format((($rowConciliacion['saldo_ant']+$rowCredito['monto'])-$rowDebito['monto']),2,".",","),1,0,'L',true);
					}



			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,  utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}






$pdf->SetDisplayMode("real");
//$pdf->AutoPrint(true);
$pdf->Output();


function folio($id,$tipo_doc){


	if($tipo_doc == 'NC'){
		$queryNC = sprintf("SELECT folio_tesoreria FROM te_nota_credito WHERE id_nota_credito = '%s'", $id);
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_array($rsNC);
	
		$respuesta = $rowNC['folio_tesoreria'];
	}
	
	else if($tipo_doc == 'ND'){
		$queryND = sprintf("SELECT folio_tesoreria FROM te_nota_debito WHERE id_nota_debito = '%s'", $id);
		$rsND = mysql_query($queryND);
		if (!$rsND) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowND = mysql_fetch_array($rsND);
	
		$respuesta = $rowND['folio_tesoreria'];
		}
		
	else if($tipo_doc == 'TR'){
		$queryTR = sprintf("SELECT folio_tesoreria FROM te_transferencia WHERE id_transferencia = '%s'", $id);
		$rsTR = mysql_query($queryTR);
		if (!$rsTR) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTR = mysql_fetch_array($rsTR);
	
		$respuesta = $rowTR['folio_tesoreria'];
		}
	
	else if($tipo_doc == 'CH'){
		$queryCH = sprintf("SELECT folio_tesoreria FROM te_cheques WHERE id_cheque = '%s'", $id);
		$rsCH = mysql_query($queryCH);
		if (!$rsCH) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCH = mysql_fetch_array($rsCH);
	
		$respuesta = $rowCH['folio_tesoreria'];
		}
	
	else if($tipo_doc == 'DP'){
		$queryDP = sprintf("SELECT folio_deposito FROM te_depositos WHERE id_deposito = '%s'", $id);
		$rsDP = mysql_query($queryDP);
		if (!$rsDP) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowDP = mysql_fetch_array($rsDP);
	
		$respuesta = $rowDP['folio_deposito'];
		}
	
	
	return $respuesta;
}

function beneficiario($id,$tipo_doc){

	if($tipo_doc == 'TR'){
		$queryTR = sprintf("SELECT beneficiario_proveedor, id_beneficiario_proveedor FROM te_transferencia WHERE id_transferencia = '%s'",$id);
		$rsTR = mysql_query($queryTR);
		if (!$rsTR) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTR = mysql_fetch_array($rsTR);
		
		if ($rowTR['beneficiario_proveedor']==1){
			
				$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$rowTR['id_beneficiario_proveedor']);
				$rsProveedor = mysql_query($queryProveedor);
				if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowProveedor = mysql_fetch_array($rsProveedor);
				$respuesta = $rowProveedor['nombre'];
				
			}
		else
			{
				$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowTR['id_beneficiario_proveedor']);
				$rsBeneficiario = mysql_query($queryBeneficiario);
				if (!$rsBeneficiario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
				$respuesta = $rowBeneficiario['nombre_beneficiario'];
			}
		
		}
	else if($tipo_doc == 'CH'){
		
		$queryCH = sprintf("SELECT beneficiario_proveedor, id_beneficiario_proveedor FROM te_cheques WHERE id_cheque = '%s'",$id);
		$rsCH = mysql_query($queryCH);
		if (!$rsCH) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCH = mysql_fetch_array($rsCH);
		
		if ($rowCH['beneficiario_proveedor']==1){
			
				$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$rowCH['id_beneficiario_proveedor']);
				$rsProveedor = mysql_query($queryProveedor);
				if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowProveedor = mysql_fetch_array($rsProveedor);
				$respuesta = $rowProveedor['nombre'];
				
			}
		else
			{
				$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowCH['id_beneficiario_proveedor']);
				$rsBeneficiario = mysql_query($queryBeneficiario);
				if (!$rsBeneficiario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
				$respuesta = $rowBeneficiario['nombre_beneficiario'];
			}
		
		}
	
	
	return $respuesta;
}

?>