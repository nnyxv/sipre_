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
	if ($valCadBusq[3] == 1) {// solo facturas sin devolucion
		$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC' AND q.activa = 1");
	} else if ($valCadBusq[3] == 2) {// solo facturas con devolucion
		$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC' OR q.activa = 0 OR q.activa IS NULL)");
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(q.nro_dcto LIKE %s
	OR q.nro_control_dcto LIKE %s
	OR q.placa LIKE %s
	OR q.serial_carroceria LIKE %s
	OR q.nombre_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"),
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

$query = sprintf("SELECT * 
FROM (
	SELECT 
		cxp_fact.id_factura AS id_dcto,
		cxp_fact.fecha_origen AS fecha_dcto,
		'FA' AS tipo_dcto,
		cxp_fact.numero_factura_proveedor AS nro_dcto,
		cxp_fact.numero_control_factura AS nro_control_dcto,
		cxp_fact.activa,
		cxp_fact.id_proveedor,
		prov.nombre AS nombre_proveedor,			
		cxp_fact.id_empresa,
		unidad.placa,
		unidad.serial_carroceria,
		serv_mant.descripcion_servicio_mantenimiento,
		serv_mant_compra.costo,			
		
		(cxp_fact.subtotal_factura - cxp_fact.subtotal_descuento) AS total_neto,
		cxp_fact.total_cuenta_pagar AS total,
		
		(SELECT SUM(cp_factura_iva.subtotal_iva) FROM cp_factura_iva
		WHERE cp_factura_iva.id_factura = cxp_fact.id_factura) AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
	FROM cp_factura cxp_fact
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
		INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
		LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
		
	UNION ALL
	
	SELECT 
		cxp_nc.id_notacredito AS id_dcto,
		cxp_nc.fecha_notacredito AS fecha_dcto,
		'NC' AS tipo_dcto,
		cxp_nc.numero_nota_credito AS nro_dcto,
		cxp_nc.numero_control_notacredito AS nro_control_dcto,
		0 AS activa,
		cxp_nc.id_proveedor,
		prov.nombre AS nombre_proveedor,			
		cxp_nc.id_empresa,			
		unidad.placa,
		unidad.serial_carroceria,
		serv_mant.descripcion_servicio_mantenimiento,
		(serv_mant_compra.costo) * -1 AS costo,	
		
		((cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento)) * -1 AS total_neto,
		(cxp_nc.total_cuenta_pagar * -1) AS total,
		
		((SELECT SUM(cp_notacredito_iva.subtotal_iva_notacredito) FROM cp_notacredito_iva
		WHERE cp_notacredito_iva.id_notacredito = cxp_nc.id_notacredito)) * -1 AS total_iva,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa			
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento = 'FA')
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)			
		INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
		INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
		LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
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
			$pdf->Cell(750,5,"Histórico de Reporte de Gastos",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("50","44","44","44","180","45","80","147","55","55");
			$arrayCol = array("Fecha\n\n","Tipo Dcto\n\n","Nro. Dcto\n\n","Nro. Control\n\n","Proveedor\n\n",$spanPlaca."\n\n",$spanSerialCarroceria."\n\n","Serv. Mant.\n\n","Total\n\n","Costo\n\n");
			
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
			$pdf->Cell($arrayTamCol[4],12,$row['nombre_proveedor'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[5],12,$row['placa'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[6],12,$row['serial_carroceria'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[7],12,$row['descripcion_servicio_mantenimiento'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[8],12,number_format($row['total'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[9],12,number_format($row['costo'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Ln();
			
			$arrayTotal[5] += 1;
			
			//documento se repite tanto como detalles de costo tenga el vehiculo
			if(!in_array($row['tipo_dcto'].$row['id_dcto'], $arrayDuplicado)) {
				$totalNeto += $row['total_neto'];
				$totalIva += $row['total_iva'];
				$totalFacturas += $row['total'];
				
				$arrayTotal[18] += $row['total'];
			}
			
			$arrayDuplicado[] = $row['tipo_dcto'].$row['id_dcto'];
		
			$totalCosto += $row['costo'];
		}
			
		$pdf->MultiCell('',0,'',1,'C',true); // CIERRA LINEA DE TABLA
		
		$pdf->Ln();
		
		$pdf->SetFillColor(255);
		$pdf->Cell(562,1,"",'T',0,'L',true);
		$pdf->Ln();
		
		// SUB-TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(487,12,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(147,14,"TOTAL DE TOTALES: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($arrayTotal[18],2,".",","),1,0,'R',true);
		$pdf->Cell(55,14,number_format($totalCosto,2,".",","),1,0,'R',true);
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
		$pdf->Ln();
		
		// TOTAL COSTO UNIDAD
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Costo: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalCosto,2,".",","),1,0,'R',true);
		
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