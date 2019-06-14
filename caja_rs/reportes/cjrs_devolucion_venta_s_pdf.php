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

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
$txtFechaDesde = $valCadBusq[1];
$txtFechaHasta = $valCadBusq[2];
$lstCondicionPago = $valCadBusq[3];
$txtCriterio = $valCadBusq[4];

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// NOTAS DE CRÉDITO DE SERVICIOS //////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	$idEmpresa);
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila<1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			// CABECERA DEL DOCUMENTO 
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',6);
				$pdf->SetX(100);
				$pdf->Cell(200,9,utf8_encode($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,utf8_encode($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,utf8_encode($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			
			$pdf->Cell('',8,'',0,2);
			
			//FECHA
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7);
				$pdf->Cell(560,20,"Fecha de Emisión: ".date(spanDateFormat." H:i:s"),0,0,'R');
				$pdf->Ln();
				
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(562,5,"CAJA DE REPUESTOS Y SERVICIOS",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"NOTAS DE CRÉDITO DE SERVICIOS",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","60","60","60","60","114","50","60");
			$arrayCol = array("EMPRESA\n\n","FECHA REGISTRO\n\n","NRO. FACTURA\n\n","NRO. CONTROL\n\n","NRO. ORDEN\n\n","CLIENTE\n\n","TIPO PAGO\n\n","TOTAL\n\n");
			
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
		
		//CONSULTA EL LISTADO DE FACTURAS SEGUN BUSQUEDA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("anulada = %s AND numeroPedido IS NOT NULL ",
			valTpDato('NO', "text"));
			
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idDepartamentoOrigenFactura = %s",
			valTpDato(1, "int"));
			
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fac.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fechaRegistroFactura BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
	
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("condicionDePago = %s",
				valTpDato($valCadBusq[3], "int"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("numeroPedido = %s
				OR numeroFactura = %s)",
				valTpDato($valCadBusq[4], "text"),
				valTpDato($valCadBusq[4], "text"));
		}
		
		// DETALLE DEL LSITADO
			$queryDetalle = sprintf("SELECT 
				idFactura,
				numeroControl,
				fechaRegistroFactura,
				numeroFactura,
				montoTotalFactura,
				CONCAT_WS(' ',cli.nombre,cli.apellido) as nombre_cliente,
				CONCAT_WS('-',cli.lci,cli.ci) as ci_cliente,
				numeroPedido,
				idDepartamentoOrigenFactura,
				anulada,
				condicionDePago,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
			FROM cj_cc_encabezadofactura fac
				INNER JOIN cj_cc_cliente cli ON (fac.idCliente = cli.id)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fac.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s ORDER BY idFactura DESC", $sqlBusq);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$fechaRegistroFactura = $rowDetalle['fechaRegistroFactura'];
				$numeroFactura = $rowDetalle['numeroFactura'];
				$numeroControl = $rowDetalle['numeroControl'];
				$numeroPedido = $rowDetalle['numeroPedido'];
				$nombreCliente = $rowDetalle['nombre_cliente'];
				$condicionPago = $rowDetalle['condicionDePago'];
				$montoFactura = $rowDetalle['montoTotalFactura'];
				
				if ($condicionPago == 0)
					$condicionPago = 'Credito';
				else
					$condicionPago = 'Contado';
					
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,date(spanDateFormat, strtotime($fechaRegistroFactura)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroFactura),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroControl),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($numeroPedido),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[5],12,utf8_encode($nombreCliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[6],12,utf8_encode($condicionPago),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[7],12,number_format($montoFactura,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$montoTotalFactura += $montoFactura;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
					
			// TOTAL FACTURAS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(422,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL: ",1,0,'L',true);
			$pdf->Cell(70,14,number_format($montoTotalFactura,2,".",","),1,0,'R',true);
			
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