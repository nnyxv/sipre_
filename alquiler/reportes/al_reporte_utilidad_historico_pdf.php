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
		$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC'");
	} else if ($valCadBusq[3] == 2) {// solo facturas con devolucion
		$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC')");
	}
}
	
// BUSQUEDA PARA VEHICULO sqlBusq2
if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[4] == "1"){
		$sqlBusq2 .= $cond.sprintf("id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_contrato_venta)");			
	} else if ($valCadBusq[4] == "2") {
		$sqlBusq2 .= $cond.sprintf("id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_servicio_mantenimiento_compra)");
	} else if ($valCadBusq[4] == "3") {
		$sqlBusq2 .= $cond.sprintf("(id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_contrato_venta)
		OR id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_servicio_mantenimiento_compra))");
	}
}	

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(placa LIKE %s
	OR serial_carroceria LIKE %s)",
		valTpDato("%".$valCadBusq[5]."%", "text"),
		valTpDato("%".$valCadBusq[5]."%", "text"));
}

$query = sprintf("SELECT 
	uni_fis.id_unidad_fisica,
	uni_fis.placa,
	uni_fis.serial_carroceria,		
	marca.nom_marca,
	modelo.nom_modelo,
	version.nom_version,
	ano.nom_ano,
	uni_bas.nom_uni_bas
FROM an_unidad_fisica uni_fis
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
	INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
	INNER JOIN an_version version ON (uni_bas.ver_uni_bas = version.id_version)
	INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano) 
	%s ORDER BY id_unidad_fisica ASC", $sqlBusq2);
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
			$pdf->Cell(750,5,"Histórico de Reporte de Utilidad",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("30","44","85","60","90","90","180","55","55","55");
			$arrayCol = array("Nro. Unidad\n\n",$spanPlaca."\n\n",$spanSerialCarroceria."\n\n","Marca\n\n","Modelo\n\n","Versión\n\n","Unidad\n\n","Venta\n\n","Costo\n\n","Utilidad\n\n");
			
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
		
		while ($rowUnidad = mysql_fetch_assoc($rs)){
			$contFila++;
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 = $cond.sprintf("(q.id_unidad_fisica = %s)",
				valTpDato($rowUnidad['id_unidad_fisica'], "int"));
			
			// FACTURAS VENTA - NOTAS DE CREDITO VENTA
			$query = sprintf("SELECT SUM(total_neto) AS total_venta
			FROM (
				SELECT 
					contrato.id_empresa,
					cxc_fact.fechaRegistroFactura AS fecha_dcto,
					unidad.id_unidad_fisica,
					'FA' AS tipo_dcto,
					(cxc_fact.subtotalFactura - cxc_fact.descuentoFactura) AS total_neto
				FROM al_contrato_venta contrato
					INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
					INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
					INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4
					INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)		
					
				UNION ALL
				
				SELECT 
					contrato.id_empresa,
					cxc_nc.fechaNotaCredito AS fecha_dcto,
					unidad.id_unidad_fisica,
					'NC' AS tipo_dcto,
					((cxc_nc.subtotalNotaCredito - cxc_nc.subtotal_descuento)) * -1 AS total_neto
				FROM al_contrato_venta contrato
					INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
					INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
					INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4			
					INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
				WHERE cxc_nc.tipoDocumento = 'FA'
				) AS q %s %s", $sqlBusq, $sqlBusq3);
			$rsVenta = mysql_query($query);
			if (!$rsVenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowVenta = mysql_fetch_assoc($rsVenta);
				
			// FACTURAS COMPRA - NOTAS DE CREDITO COMPRA
			$query = sprintf("SELECT SUM(costo) AS total_costo
			FROM (
				SELECT 
					cxp_fact.id_empresa,
					cxp_fact.fecha_origen AS fecha_dcto,
					unidad.id_unidad_fisica,
					'FA' AS tipo_dcto,
					serv_mant_compra.costo,
					(cxp_fact.subtotal_factura - cxp_fact.subtotal_descuento) AS total_neto				
				FROM cp_factura cxp_fact
					INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
					INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
					LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
					
				UNION ALL
				
				SELECT 
					cxp_nc.id_empresa,
					cxp_nc.fecha_notacredito AS fecha_dcto,
					unidad.id_unidad_fisica,
					'NC' AS tipo_dcto,
					serv_mant_compra.costo,
					((cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento)) * -1 AS total_neto
				FROM cp_notacredito cxp_nc
					INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento = 'FA')
					INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
					INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
					LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
				) AS q %s %s", $sqlBusq, $sqlBusq3);
			$rsCompra = mysql_query($query);
			if (!$rsCompra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowCompra = mysql_fetch_assoc($rsCompra);
			
			$pdf->Cell($arrayTamCol[0],12,$rowUnidad['id_unidad_fisica'],'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[1],12,$rowUnidad['placa'],'LR',0,'C',true);
			$pdf->Cell($arrayTamCol[2],12,$rowUnidad['serial_carroceria'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[3],12,$rowUnidad['nom_marca'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[4],12,$rowUnidad['nom_modelo'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[5],12,$rowUnidad['nom_version'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[6],12,$rowUnidad['nom_uni_bas'],'LR',0,'L',true);
			$pdf->Cell($arrayTamCol[7],12,number_format($rowVenta['total_venta'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[8],12,number_format($rowCompra['total_costo'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Cell($arrayTamCol[9],12,number_format($rowVenta['total_venta']-$rowCompra['total_costo'], 2, ".", ","),'LR',0,'R',true);
			$pdf->Ln();
			
			$arrayTotal[5] += 1;
			
			$totalFacturas += $rowVenta['total_venta'];
			$totalCosto += $rowCompra['total_costo'];
			$totalUtilidad += $rowVenta['total_venta']-$rowCompra['total_costo'];
		}
			
		$pdf->MultiCell('',0,'',1,'C',true); // CIERRA LINEA DE TABLA
		
		$pdf->Ln();
		
		$pdf->SetFillColor(255);
		$pdf->Cell(562,1,"",'T',0,'L',true);
		$pdf->Ln();
		
		// SUB-TOTAL PRESUPUESTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(399,12,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(180,14,"TOTAL DE TOTALES: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalFacturas,2,".",","),1,0,'R',true);
		$pdf->Cell(55,14,number_format($totalCosto,2,".",","),1,0,'R',true);
		$pdf->Cell(55,14,number_format($totalUtilidad,2,".",","),1,0,'R',true);
		$pdf->Ln();
		$pdf->Ln();

		// TOTAL VENTA
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Venta: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalFacturas,2,".",","),1,0,'R',true);
		$pdf->Ln();
		
		// TOTAL COSTO
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Costo: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalCosto,2,".",","),1,0,'R',true);
		$pdf->Ln();
		
		// TOTAL UTILIDAD
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell(579,14,"",0,0,'L',true);
		$pdf->SetFillColor(204,204,204,204);
		$pdf->Cell(110,14,"Total Utilidad: ",1,0,'L',true);
		$pdf->Cell(55,14,number_format($totalUtilidad,2,".",","),1,0,'R',true);
		
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