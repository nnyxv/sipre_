<?php
require_once("../../connections/conex.php");
session_start();
set_time_limit(0);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("24","20","24");
//$pdf->SetMargins("10","10","10");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = utf8_encode($spanRIF.": ".$rowEmp['rif']);
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
			
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = q.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(q.fecha_dcto) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("q.id_empleado_creador = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[4] == 1) {// solo facturas sin devolucion
		$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC' AND q.anulada = 'NO'");
	} else if ($valCadBusq[4] == 2) {// solo facturas con devolucion
		$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC' OR q.anulada = 'SI')");
	}
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.numero_contrato_venta LIKE %s		
	OR q.nro_dcto LIKE %s
	OR q.nro_control_dcto LIKE %s
	OR q.placa LIKE %s
	OR q.serial_carroceria LIKE %s
	OR q.nombre_cliente LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

$query = sprintf("SELECT * 
FROM (
	SELECT 
		cxc_fact.idFactura AS id_dcto,
		cxc_fact.fechaRegistroFactura AS fecha_dcto,
		'FA' AS tipo_dcto,
		cxc_fact.numeroFactura AS nro_dcto,
		cxc_fact.numeroControl AS nro_control_dcto,
		cxc_fact.anulada,
		contrato.fecha_creacion AS fecha_contrato,
		contrato.numero_contrato_venta,
		contrato.id_tipo_contrato,
		tipo_contrato.nombre_tipo_contrato,
		contrato.id_empleado_creador,
		empleado.nombre_empleado,
		contrato.id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,	
		contrato.id_contrato_venta,
		contrato.id_empresa,
		contrato.id_unidad_fisica,
		contrato.estatus_contrato_venta,
		unidad.placa,
		unidad.serial_carroceria,
		(cxc_fact.subtotalFactura - cxc_fact.descuentoFactura) AS total_neto,
		cxc_fact.montoTotalFactura AS total,
		
		(SELECT SUM(cj_cc_factura_iva.subtotal_iva) FROM cj_cc_factura_iva
		WHERE cj_cc_factura_iva.id_factura = cxc_fact.idFactura) AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
	FROM al_contrato_venta contrato
		INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)		
		INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)		
		
	UNION ALL
	
	SELECT 
		cxc_nc.idNotaCredito AS id_dcto,
		cxc_nc.fechaNotaCredito AS fecha_dcto,
		'NC' AS tipo_dcto,
		cxc_nc.numeracion_nota_credito AS nro_dcto,
		cxc_nc.numeroControl AS nro_control_dcto,
		'NO' AS anulada,
		contrato.fecha_creacion AS fecha_contrato,
		contrato.numero_contrato_venta,
		contrato.id_tipo_contrato,
		tipo_contrato.nombre_tipo_contrato,
		contrato.id_empleado_creador,
		empleado.nombre_empleado,
		contrato.id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,	
		contrato.id_contrato_venta,
		contrato.id_empresa,
		contrato.id_unidad_fisica,
		contrato.estatus_contrato_venta,
		unidad.placa,
		unidad.serial_carroceria,
		((cxc_nc.subtotalNotaCredito - cxc_nc.subtotal_descuento)) * -1 AS total_neto,
		(cxc_nc.montoNetoNotaCredito * -1) AS total,
		((SELECT SUM(cj_cc_nota_credito_iva.subtotal_iva) FROM cj_cc_nota_credito_iva
		WHERE cj_cc_nota_credito_iva.id_nota_credito = cxc_nc.idNotaCredito)) * -1 AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
	FROM al_contrato_venta contrato
		INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)		
		INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4			
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	WHERE cxc_nc.tipoDocumento = 'FA'
	) AS q 
	%s ORDER BY fecha_dcto, nro_control_dcto ASC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);

if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila < 1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			//FECHA
			if($valCadBusq[1] != "" && $valCadBusq[2] != ""){
				$fecha = "Desde: ".date(spanDateFormat, strtotime($valCadBusq[1]))." Hasta: ".date(spanDateFormat, strtotime($valCadBusq[2]));
			}
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',7);
			$pdf->Cell(700,20,$fecha,0,0,'R');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(750,5,"Histórico de Reporte de Venta",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("50","44","44","44","44","50","55","110","131","40","77","55");
			$arrayCol = array("Fecha\n\n","Tipo Dcto\n\n","Nro. Dcto\n\n","Nro. Control\n\n","Nro. Contrato\n","Fecha Contrato\n\n","Tipo Contrato\n\n","Vendedor\n\n","Cliente\n\n",$spanPlaca."\n\n",$spanSerialCarroceria."\n\n","Total\n\n");
			
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
		
		while ($row = mysql_fetch_assoc($rs)){
			$contFila++;
			
			$pdf->Cell($arrayTamCol[0],12,date(spanDateFormat, strtotime($row['fecha_dcto'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,$row['tipo_dcto'],'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,$row['nro_dcto'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[3],12,$row['nro_control_dcto'],'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[4],12,$row['numero_contrato_venta'],'LR',0,'R',true);		
			$pdf->Cell($arrayTamCol[5],12,date(spanDateFormat, strtotime($row['fecha_contrato'])),'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[6],12,$row['nombre_tipo_contrato'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[7],12,(substr($row['nombre_empleado'],0,26)),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[8],12,(substr($row['nombre_cliente'],0,30)),'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[9],12,$row['placa'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[10],12,$row['serial_carroceria'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[11],12,number_format($row['total'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Ln();
			
			$arrayTotal[5] += 1;
			$arrayTotal[18] += $row['total'];
			
			$totalNeto += $row['total_neto'];
			$totalIva += $row['total_iva'];
			$totalFacturas += $row['total'];
		}
			
		$pdf->MultiCell('',0,'',1,'C',true); // CIERRA LINEA DE TABLA
		
		$pdf->Ln();
		
		$pdf->SetFillColor(255);
		$pdf->Cell(562,1,"",'T',0,'L',true);
		$pdf->Ln();
		
		// SUB-TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(572,12,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(117,14,"TOTAL DE TOTALES: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($arrayTotal[18],2,".",","),1,0,'R',true);
		$pdf->Ln();
		$pdf->Ln();
		
		// TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Neto: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalNeto,2,".",","),1,0,'R',true);
		$pdf->Ln();
		
		//$precioFinal = $rowEncabezado['subtotal'] + $rowEncabezado['subtotal_iva'];

		// TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Impuesto: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalIva,2,".",","),1,0,'R',true);
		$pdf->Ln();
		
		// TOTAL FACTURA
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Factura(s): ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalFacturas,2,".",","),1,0,'R',true);
		
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