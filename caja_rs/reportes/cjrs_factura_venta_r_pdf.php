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
/////////////////////////////////////////////////////// FACTURACIÓN DE REPUESTOS ///////////////////////////////////////////////////////
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
			$pdf->Cell(562,10,"FACTURACIÓN DE REPUESTOS",0,0,'C');
			
			$pdf->Ln(); $pdf->Ln();
			
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("40","60","60","60","60","135","50","50","50");
			$arrayCol = array("FECHA\n","NRO. PEDIDO\n","NRO. REFEREN.\n","NRO. PRESUP.\n","NRO. SINIEST.\n","CLIENTE\n","TIPO PAGO\n","ITEMS\n","TOTAL\n");
			
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
		
		//CONSULTA EL LISTADO DE ANTICIPOS SEGUN BUSQUEDA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus_pedido_venta <> 0
		AND estatus_pedido_venta <> 1
		AND estatus_pedido_venta <> 3
		AND estatus_pedido_venta <> 4");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(estatus_pedido_venta = 2 AND id_empleado_aprobador IS NOT NULL)");
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(vw_iv_pedidos_venta.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = vw_iv_pedidos_venta.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fecha BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(id_pedido_venta_propio LIKE %s
			OR id_pedido_venta_referencia LIKE %s
			OR numeracion_presupuesto LIKE %s
			OR ci_cliente LIKE %s
			OR nombre_cliente LIKE %s)",
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"));
		}
	
		$query = sprintf("SELECT 
				(SELECT COUNT(ped_venta_det.id_pedido_venta) AS items
				FROM iv_pedido_venta_detalle ped_venta_det
				WHERE (ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta)) AS items,
				
				(SELECT SUM(ped_venta_det.cantidad) AS pedidos
				FROM iv_pedido_venta_detalle ped_venta_det
				WHERE (ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta)) AS pedidos,
				
				(SELECT SUM(ped_venta_det.pendiente) AS pendientes
				FROM iv_pedido_venta_detalle ped_venta_det
				WHERE (ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta)) AS pendientes,
				
				((vw_iv_pedidos_venta.subtotal - vw_iv_pedidos_venta.subtotal_descuento)
				+
				IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_venta_iva ped_iva
					WHERE (ped_iva.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta)), 0)) AS total,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
				vw_iv_pedidos_venta.nombre_cliente,
				vw_iv_pedidos_venta.id_pedido_venta_propio,
				vw_iv_pedidos_venta.id_pedido_venta_referencia,
				vw_iv_pedidos_venta.fecha,
				vw_iv_pedidos_venta.numeracion_presupuesto,
				vw_iv_pedidos_venta.numero_siniestro,
				vw_iv_pedidos_venta.condicion_pago
			FROM
				vw_iv_pedidos_venta
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_pedidos_venta.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s 
			ORDER BY id_pedido_venta DESC", $sqlBusq);
		$rsDetalle = mysql_query($query);
		if (!$rsDetalle) return die(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsDetalle);
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
			$contFila++;
			
			// RESTAURACION DE COLOR Y FUENTE
			($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			

			$pdf->Cell($arrayTamCol[0],12,date(spanDateFormat, strtotime($rowDetalle['fecha'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,utf8_encode($rowDetalle['id_pedido_venta_propio']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,utf8_encode($rowDetalle['id_pedido_venta_referencia']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[3],12,utf8_encode($rowDetalle['numeracion_presupuesto']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[4],12,utf8_encode($rowDetalle['numero_siniestro']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[5],12,utf8_encode($rowDetalle['nombre_cliente']),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[6],12,(($rowDetalle['condicion_pago'] == 1) ? "CONTADO" : "CRÉDITO"),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[7],12,utf8_encode($rowDetalle['items']),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[8],12,number_format($rowDetalle['total'],2,".",","),'LR',0,'R',true);
			$pdf->Ln();
						
			$fill = !$fill;
			
			$montoTotal += $rowDetalle['total'];
		}
		
		$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
		
		$pdf->Ln();
		
		// TOTAL DOCUMENTOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(465,14,"",'T',0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(50,14,"TOTALES: ",1,0,'R',true);
			$pdf->Cell(50,14,number_format($montoTotal,2,".",","),1,0,'R',true);
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>