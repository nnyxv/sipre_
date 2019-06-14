<?php
require_once ("../../connections/conex.php");
session_start();
set_time_limit(0);
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
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

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// FACTURACIÓN DE SERVICIOS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) return die(mysql_error()."\n\nLine: ".__LINE__);
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
				$pdf->Cell(200,9,($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			
			$pdf->Ln(); $pdf->Ln(); $pdf->Ln();
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(562,10,"Fecha de Emisión: ".date(spanDateFormat),0,0,'R');
			$pdf->Ln();
			$pdf->Cell(562,10,"CAJA DE REPUESTOS Y SERVICIOS",0,0,'C');
			$pdf->Ln();
			$pdf->Cell(562,10,"FACTURACIÓN DE SERVICIOS",0,0,'C');
			
			$pdf->Ln(); $pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("40","40","50","50","50","79","50","105","50","50");
			$arrayCol = array("FECHA\n","NRO OR\n","NRO RECEP.\n","CATÁLOGO\n","PLACA\n","CHASIS\n","NRO OR RE\n","CLIENTE\n","TIPO ORDEN\n","TOTAL\n");
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
				
				$pdf->MultiCell($arrayTamCol[$indice],14,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//RESTAURACION DE COLORES Y FUENTES
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		//$pdf->SetFillColor(234,244,255); // blanco
		$pdf->SetFillColor(255,255,255); // azul
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_orden.id_estado_orden = %s AND vw_sa_orden.modo_factura != 'VALE SALIDA'",
			13); //TERMINADO
				
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_sa_orden.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_sa_orden.id_tipo_orden = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_sa_orden.id_estado_orden = %s",
				valTpDato($valCadBusq[2], "int"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf(" vw_sa_orden.nombre LIKE %s
			OR vw_sa_orden.apellido_cliente_vale LIKE %s
			OR vw_sa_orden.numero_orden = %s
			OR vw_sa_orden.placa LIKE %s
			OR vw_sa_orden.chasis LIKE %s)",
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato($valCadBusq[3], "int"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"));
		}
	
		$query = sprintf("SELECT 
				cj_cc_cliente.nombre AS nombre_cliente,
				cj_cc_cliente.apellido AS apellido_cliente,
				cj_cc_cliente.lci,
				cj_cc_cliente.ci,
				cj_cc_cliente.nit,
				vw_sa_orden.*,
				sa_retrabajo_orden.id_orden_retrabajo,
				(SELECT vw_sa_orden.numero_orden FROM vw_sa_orden WHERE vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden_retrabajo) AS numero_orden_retrabajo
			FROM cj_cc_cliente
				INNER JOIN vw_sa_orden ON (cj_cc_cliente.id = vw_sa_orden.id_cliente_pago_orden)
				LEFT JOIN sa_retrabajo_orden ON (vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden) %s ORDER BY numero_orden DESC", $sqlBusq);
		$rsDetalle = mysql_query($query);
		if (!$rsDetalle) return die(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
			$pdf->Cell($arrayTamCol[0],12,date(spanDateFormat, strtotime($rowDetalle['tiempo_orden_registro'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,utf8_encode($rowDetalle['numero_orden']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,utf8_encode($rowDetalle['numeracion_recepcion']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[3],12,utf8_encode($rowDetalle['nom_uni_bas']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[4],12,utf8_encode($rowDetalle['placa']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],12,utf8_encode($rowDetalle['chasis']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[6],12,utf8_encode($rowDetalle['numero_orden_retrabajo']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[7],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[8],12,utf8_encode($rowDetalle['nombre_tipo_orden']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[9],12,number_format($rowDetalle['total'],2,".",","),'LR',0,'R',true);
			$pdf->Ln();
					
			$fill = !$fill;
						
			$montoTotal += $rowDetalle['total'];
		}
		
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->Ln();
		
		// TOTAL DOCUMENTOS
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(464,14,"",'T',0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(50,14,"TOTALES: ",1,0,'R',true);
		$pdf->Cell(50,14,number_format($montoTotal,2,".",","),1,0,'R',true);
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>