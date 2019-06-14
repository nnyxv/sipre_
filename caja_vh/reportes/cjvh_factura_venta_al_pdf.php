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
$txtCriterio = $valCadBusq[3];

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// FACTURACIÓN DE VEHICULOS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
				$pdf->Cell(560,20,"Fecha de Emisión: ".$fechaHoy.'  '.$horaActual."",0,0,'R');
				$pdf->Ln();
				
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(562,5,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"FACTURACIÓN DE ALQUILER",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("60","45","45","50","50","94","40","50","45","45","40");
			$arrayCol = array("EMPRESA\n\n","FECHA\n\n","NRO. CONTRATO\n\n","NRO. PRESUPUESTO\n","TIPO CONTRATO\n\n","CLIENTE\n\n","PLACA\n\n",strtoupper($spanSerialCarroceria)."\n\n","FECHA SALIDA\n","FECHA ENTRADA\n","TOTAL GENERAL\n");
			
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
		
		//CONSULTA EL LISTADO DE CONTRATOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("contrato.estatus_contrato_venta IN (2)");
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(contrato.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = contrato.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE(contrato.fecha_creacion) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(contrato.numero_contrato_venta LIKE %s		
			OR presupuesto.numero_presupuesto_venta LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"),
				valTpDato("%".$valCadBusq[3]."%", "text"));
		}
		
		$queryDetalle = sprintf("SELECT 
			contrato.id_contrato_venta,
			contrato.numero_contrato_venta,
			contrato.id_tipo_contrato,
			contrato.id_empresa,
			contrato.id_cliente,
			contrato.observacion,
			contrato.id_presupuesto_venta,
			contrato.id_unidad_fisica,
			contrato.condicion_pago,
			contrato.estatus_contrato_venta,
			contrato.fecha_creacion,
			contrato.fecha_salida,
			contrato.fecha_entrada,
			contrato.dias_contrato,
			presupuesto.numero_presupuesto_venta,
			tipo_contrato.nombre_tipo_contrato,
			empleado.nombre_empleado,
			unidad.placa,
			unidad.serial_carroceria,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,		
			(contrato.subtotal - contrato.subtotal_descuento) AS total_neto,
			contrato.total_contrato AS total,
			
			(SELECT SUM(al_contrato_venta_iva.subtotal_iva) FROM al_contrato_venta_iva
			WHERE al_contrato_venta_iva.id_contrato_venta = contrato.id_contrato_venta) AS total_iva,
					
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
			FROM al_contrato_venta contrato
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)
			INNER JOIN cj_cc_cliente cliente ON (contrato.id_cliente = cliente.id)
			INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
			LEFT JOIN al_presupuesto_venta presupuesto ON (contrato.id_presupuesto_venta = presupuesto.id_presupuesto_venta) %s ORDER BY numero_contrato_venta DESC", $sqlBusq);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$fecha = $rowDetalle['fecha_creacion'];
				$numeroContrato = $rowDetalle['numero_contrato_venta'];
				$numeroPresupuesto = $rowDetalle['numero_presupuesto_venta'];
				$tipoContrato = $rowDetalle['nombre_tipo_contrato'];
				$nombreCliente = $rowDetalle['nombre_cliente'];				
				$placa = $rowDetalle['placa'];
				$serialCarroceria = $rowDetalle['serial_carroceria'];
				$fechaSalida = $rowDetalle['fecha_salida'];
				$fechaEntrada = $rowDetalle['fecha_entrada'];
				$totalGeneral = $rowDetalle['total'];
					
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,date(spanDateFormat, strtotime($fecha)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroContrato),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroPresupuesto),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($tipoContrato),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[5],12,utf8_encode($nombreCliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[6],12,utf8_encode($placa),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[7],12,utf8_encode($serialCarroceria),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[8],12,date(spanDateFormat, strtotime($fechaSalida)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[9],12,date(spanDateFormat, strtotime($fechaEntrada)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[10],12,number_format($totalGeneral,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$precioventaTotal += $totalGeneral;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
						
			// TOTAL ANTCIPOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(438,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL: ",1,0,'L',true);
			$pdf->Cell(54,14,number_format($precioventaTotal,2,".",","),1,0,'R',true);
			
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
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>