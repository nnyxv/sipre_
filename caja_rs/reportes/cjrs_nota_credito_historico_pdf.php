<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");
session_start();
set_time_limit(0);
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

($_GET["lstOrientacionPDF"] == "V") ? $pdf = new PDF_AutoPrint('P','pt','Letter') : $pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("10","10","10");
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

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// LISTADO DE NOTAS DE CREDITO ///////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("cxc_nc.estatus_nota_credito IN (2)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.fechaNotaCredito BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.id_empleado_vendedor IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.aplicaLibros = %s",
		valTpDato($valCadBusq[4], "boolean"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.estadoNotaCredito IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nc.numeracion_nota_credito LIKE %s
	OR cxc_nc.numeroControl LIKE %s
	OR cxc_fact.numeroFactura LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR cxc_nc.observacionesNotaCredito LIKE %s
	OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) LIKE %s)",
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"),
		valTpDato("%".$valCadBusq[7]."%", "text"));
}

$query = sprintf("SELECT
	cxc_ec.idEstadoDeCuenta AS id_estado_cuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxc_nc.idNotaCredito,
	cxc_nc.id_empresa,
	cxc_nc.fechaNotaCredito,
	cxc_nc.numeracion_nota_credito,
	cxc_nc.numeroControl,
	cxc_nc.idDepartamentoNotaCredito AS id_modulo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_nc.estadoNotaCredito,
	(CASE cxc_nc.estadoNotaCredito
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado No Asignado'
		WHEN 2 THEN 'Asignado Parcial'
		WHEN 3 THEN 'Asignado'
	END) AS descripcion_estado_nota_credito,
	cxc_nc.aplicaLibros,
	
	cxc_ec2.idEstadoDeCuenta AS id_estado_cuenta_factura,
	cxc_ec2.tipoDocumentoN AS tipo_documento_n_factura,
	cxc_ec2.tipoDocumento AS tipo_documento_factura,
	cxc_fact.idFactura,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl AS numero_control_factura,
	cxc_fact.idDepartamentoOrigenFactura AS id_modulo_factura,
	
	cxc_nc.subtotalNotaCredito,
	cxc_nc.subtotal_descuento,
	(IFNULL(cxc_nc.subtotalNotaCredito, 0)
		- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
	IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
			WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
	cxc_nc.montoNetoNotaCredito,
	cxc_nc.saldoNotaCredito,
	cxc_nc.observacionesNotaCredito,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
		INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo,
	
	(CASE cxc_nc.idDepartamentoNotaCredito
		WHEN 0 THEN
			IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
					WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
		WHEN 1 THEN
			(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
					WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
						WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
						WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
						WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
		WHEN 2 THEN
			(IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
					WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
				+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
						WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0))
		WHEN 3 THEN
			IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
					WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
	END) AS cant_items,
	
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_notacredito cxc_nc
	INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'NC')
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	LEFT JOIN cj_cc_estadocuenta cxc_ec2 ON (cxc_fact.idFactura = cxc_ec2.idDocumento AND cxc_ec2.tipoDocumento LIKE 'FA')
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY cxc_nc.idNotaCredito DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);

$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if ($totalRows > 0) {
	$pdf->AddPage();
	
	if ($_GET["lstOrientacionPDF"] == "V") {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => "\n\n"),
			array("tamano" => "25", "descripcion" => "MODULO\n\n"),
			array("tamano" => "90", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "40", "descripcion" => "FECHA REGISTRO\n\n"),
			array("tamano" => "40", "descripcion" => "NRO. NOTA DE CRÉDITO\n"),
			array("tamano" => "40", "descripcion" => "NRO. CONTROL\n\n"),
			array("tamano" => "35", "descripcion" => "NRO. FACTURA\n"),
			array("tamano" => "120", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "35", "descripcion" => "TIPO PAGO\n\n"),
			array("tamano" => "45", "descripcion" => "ESTADO NOTA DE CRÉDITO\n"),
			array("tamano" => "50", "descripcion" => "SALDO NOTA DE CRÉDITO\n"),
			array("tamano" => "50", "descripcion" => "TOTAL NOTA DE CRÉDITO\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => "\n\n"),
			array("tamano" => "35", "descripcion" => "MODULO\n\n"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "60", "descripcion" => "FECHA REGISTRO\n\n"),
			array("tamano" => "60", "descripcion" => "NRO. NOTA DE CRÉDITO\n"),
			array("tamano" => "60", "descripcion" => "NRO. CONTROL\n\n"),
			array("tamano" => "35", "descripcion" => "NRO. FACTURA\n"),
			array("tamano" => "150", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "50", "descripcion" => "TIPO PAGO\n\n"),
			array("tamano" => "60", "descripcion" => "ESTADO NOTA DE CRÉDITO\n"),
			array("tamano" => "70", "descripcion" => "SALDO NOTA DE CRÉDITO\n"),
			array("tamano" => "70", "descripcion" => "TOTAL NOTA DE CRÉDITO\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'];
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,("NOTAS DE CRÉDITO"),0,0,'C');
	
	$pdf->Ln();
	
	if (strlen($nombreCajaPpal) > 0) {
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$pdf->Cell($totalAncho,10,"(".$nombreCajaPpal.")",0,0,'C');
		
		$pdf->Ln();
	}
	
	$pdf->Ln();
	
	$posY = $pdf->GetY();
	$posX = $pdf->GetX();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',6);
	
	foreach ($arrayCol as $indice => $valor) {
		$pdf->SetY($posY);
		$pdf->SetX($posX);
		
		$pdf->MultiCell($arrayCol[$indice]['tamano'],14,$arrayCol[$indice]['descripcion'],1,'C',true);
		
		$posX += $arrayCol[$indice]['tamano'];
	}
	
	while ($row = mysql_fetch_assoc($rs)) {
		// RESTAURACION DE COLOR Y FUENTE
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "R"; break;
			case 1 : $imgDctoModulo = "S"; break;
			case 2 : $imgDctoModulo = "V"; break;
			case 3 : $imgDctoModulo = "A"; break;
			case 4 : $imgDctoModulo = "AL"; break;
			case 5 : $imgDctoModulo = "F"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "Factura (Con Devolución)" : "Factura";
		
		$pdf->Cell($arrayCol[0]['tamano'],14,$contFila,'LR',0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,$imgDctoModulo,'LR',0,'C',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,($row['nombre_empresa']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[3]['tamano'],14,date(spanDateFormat, strtotime($row['fechaNotaCredito'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[4]['tamano'],14,($row['numeracion_nota_credito']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['numeroControl']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['numeroFactura']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,($row['id_cliente'].".- ".$row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,(($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"),'LR',0,'C',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,($row['descripcion_estado_nota_credito']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[10]['tamano'],14,number_format($row['saldoNotaCredito'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[11]['tamano'],14,number_format($row['montoNetoNotaCredito'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
		
		$arrayTotal[9] += $row['saldoNotaCredito'];
		$arrayTotal[10] += $row['montoNetoNotaCredito'];
	}
	
	$pdf->MultiCell($totalAncho,0,'',1,'C',true); // cierra linea de tabla
	
	$pdf->Ln();
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'],14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell($arrayCol[9]['tamano'],14,"TOTALES: ",1,0,'R',true);
	$pdf->Cell($arrayCol[10]['tamano'],14,number_format($arrayTotal[9],2,".",","),1,0,'R',true);
	$pdf->Cell($arrayCol[11]['tamano'],14,number_format($arrayTotal[10],2,".",","),1,0,'R',true);
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>