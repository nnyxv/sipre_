<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");

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
$txtFechaDesde = $valCadBusq[1];
$txtFechaHasta = $valCadBusq[2];
$lstCondicionPago = $valCadBusq[3];
$txtCriterio = $valCadBusq[4];

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// NOTAS DE CR�DITO DE VEHICULOS ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
				$fechaHoy = date(spanDateFormat);
				$horaActual = date("H:i:s");
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7);
				$pdf->Cell(560,20,"Fecha de Emisi�n: ".$fechaHoy.'  '.$horaActual."",0,0,'R');
				$pdf->Ln();
				
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(562,5,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"NOTAS DE CR�DITO DE ALQUILER",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","60","70","70","124","40","50","50");
			$arrayCol = array("EMPRESA\n","FECHA FACTURA\n","NRO. FACTURA\n","NROL. CONTROL\n","CLIENTE\n","CONDICI�N DE PAGO\n","TOTAL\n","SALDO\n");
			
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
		
		//CONSULTA EL LISTADO DE ANTICIPOS SEGUN BUSQUEDA
		$sqlBusq .= $cond.sprintf(" WHERE idDepartamentoOrigenFactura IN (4) AND anulada LIKE 'NO' ");
		
		/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estadoFactura != %s",
			valTpDato(1, "int"));*/
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("c.id_empresa = %s",
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
			$sqlBusq .= $cond.sprintf("numeroFactura LIKE %s
				OR numeroControl LIKE %s
				OR c.idCliente IN (SELECT cl.id FROM cj_cc_cliente cl WHERE cl.nombre LIKE %s OR cl.apellido LIKE  %s))",
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"));
		}
			
		// DETALLE DEL LSITADO
			$queryDetalle = sprintf("SELECT
				c.idFactura,
				c.numeroControl,
				c.fechaRegistroFactura,
				c.numeroFactura,
				c.montoTotalFactura,
				c.saldoFactura,
				c.idCliente,
				c.condicionDePago,
				c.numeroPedido,
				c.idDepartamentoOrigenFactura,
				(SELECT CONCAT_WS(' ',cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id= c.idCliente) AS nombre_cliente,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM
				cj_cc_encabezadofactura c 
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (c.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s ORDER BY idFactura DESC", $sqlBusq);
				
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$fechaRegistroFactura = $rowDetalle['fechaRegistroFactura'];
				$numeroFactura = $rowDetalle['numeroFactura'];
				$numeroControl = $rowDetalle['numeroControl'];
				$nombre_cliente = $rowDetalle['nombre_cliente'];
				$condicionDePago = $rowDetalle['condicionDePago'];
				$montoFactura = $rowDetalle['montoTotalFactura'];
				$saldoFactura = $rowDetalle['saldoFactura'];

				
				if ($estadoAnticipo == 0){
					$estadoAnticipo = 'No Cancelado';
				} else if ($estadoAnticipo == 1){
					$estadoAnticipo = 'Cancelado/No Asignado';
				} else if ($estadoAnticipo == 2){
					$estadoAnticipo = 'Asignado Parcial';
				} else if ($estadoAnticipo == 3){
					$estadoAnticipo = 'Asignado';
				}
		
		 		if($condicionDePago == 0){
					$condicionDePago = "Credito";
				} else {
					$condicionDePago = "Contado";
				}
				
					$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
					$pdf->Cell($arrayTamCol[1],12,date(spanDateFormat, strtotime($fechaRegistroFactura)),'LR',0,'C',true);
					$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroFactura),'LR',0,'C',true);
					$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroControl),'LR',0,'C',true);
					$pdf->Cell($arrayTamCol[4],12,utf8_encode($nombre_cliente),'LR',0,'L',true);
					$pdf->Cell($arrayTamCol[5],12,utf8_encode($condicionDePago),'LR',0,'C',true);
					$pdf->Cell($arrayTamCol[6],12,number_format($montoFactura,2,".",","),'LR',0,'R',true);
					$pdf->Cell($arrayTamCol[7],12,number_format($saldoFactura,2,".",","),'LR',0,'R',true);
					$pdf->Ln();
						
				$saldoTotalFactura += $saldoFactura;
				$montoTotalFactura += $montoFactura;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
						
			// TOTAL ANTCIPOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(392,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTALES: ",1,0,'L',true);
			$pdf->Cell(50,14,number_format($montoTotalFactura,2,".",","),1,0,'R',true);
			$pdf->Cell(50,14,number_format($saldoTotalFactura,2,".",","),1,0,'R',true);
			
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
		
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"P�gina ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>