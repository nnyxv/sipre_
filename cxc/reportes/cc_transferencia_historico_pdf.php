<?php
require_once ("../../connections/conex.php");
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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// LISTADO DE ANTICIPOS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tb.id_departamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(tb.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = tb.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tb.fecha_transferencia BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tb.estatus = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tb.estado_transferencia IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tb.id_departamento IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(tb.numero_transferencia LIKE %s
	OR banco.nombreBanco LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR tb.observacion_transferencia LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

$query = sprintf("SELECT
	tb.id_transferencia,
	tb.tipo_transferencia,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	tb.monto_neto_transferencia,
	IF (tb.estatus = 1, tb.saldo_transferencia, 0) AS saldo_transferencia,
	tb.fecha_transferencia,
	tb.numero_transferencia,
	banco.nombreBanco,
	tb.id_departamento,
	tb.estatus,
	IF (tb.estatus = 1, tb.estado_transferencia, NULL) AS estado_transferencia,
	(CASE tb.estatus
		WHEN 1 THEN
			(CASE tb.estado_transferencia
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
			END)
		ELSE
			'Anulado'
	END) AS descripcion_estado_transferencia,
	tb.observacion_transferencia,
	
	tb.id_empleado_registro AS id_empleado_creador,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	tb.fecha_anulado,
	tb.id_empleado_anulado,
	vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
	tb.motivo_anulacion,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, 
		CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), 
		vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
	
FROM cj_cc_transferencia tb
	INNER JOIN cj_cc_cliente cliente ON (tb.id_cliente = cliente.id)
	INNER JOIN bancos banco ON (tb.id_banco_cliente = banco.idBanco)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (tb.id_empleado_registro = vw_pg_empleado_creador.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (tb.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (tb.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY id_transferencia DESC", $sqlBusq);
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
			array("tamano" => "20", "descripcion" => "\n\n\n"),
			array("tamano" => "25", "descripcion" => "MODULO\n\n"),
			array("tamano" => "35", "descripcion" => "ESTATUS\n\n\n"),
			array("tamano" => "90", "descripcion" => "EMPRESA\n\n\n"),
			array("tamano" => "40", "descripcion" => "FECHA TRANSFERENCIA\n"),
			array("tamano" => "50", "descripcion" => "BANCO\n\n\n"),
			array("tamano" => "40", "descripcion" => "NRO. TRANSFERENCIA\n"),
			array("tamano" => "115", "descripcion" => "CLIENTE\n\n\n"),
			array("tamano" => "75", "descripcion" => "ESTADO TRANSFERENCIA\n\n"),
			array("tamano" => "50", "descripcion" => "SALDO TRANSFERENCIA\n"),
			array("tamano" => "50", "descripcion" => "TOTAL TRANSFERENCIA\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => "\n\n"),
			array("tamano" => "35", "descripcion" => "MODULO\n\n"),
			array("tamano" => "35", "descripcion" => "ESTATUS\n\n"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "60", "descripcion" => "FECHA TRANSFERENCIA\n"),
			array("tamano" => "70", "descripcion" => "BANCO\n\n"),
			array("tamano" => "60", "descripcion" => "NRO. TRANSFERENCIA\n"),
			array("tamano" => "160", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "90", "descripcion" => "ESTADO TRANSFERENCIA\n\n"),
			array("tamano" => "70", "descripcion" => "SALDO TRANSFERENCIA\n"),
			array("tamano" => "70", "descripcion" => "TOTAL TRANSFERENCIA\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'];
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,"TRANSFERENCIAS",0,0,'C');
	
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
		
		switch($row['id_departamento']) {
			case 0 : $imgDctoModulo = "R"; break;
			case 1 : $imgDctoModulo = "S"; break;
			case 2 : $imgDctoModulo = "V"; break;
			case 3 : $imgDctoModulo = "A"; break;
			case 4 : $imgDctoModulo = "AL"; break;
			case 5 : $imgDctoModulo = "F"; break;
			default : $imgDctoModulo = $row['id_departamento'];
		}
		
		switch($row['estatus']) {
			case 0 : $imgEstatus = "Anulado"; break;
			case 1 : $imgEstatus = "Activo"; break;
			default : $imgEstatus = $row['estatus'];
		}
		
		$pdf->Cell($arrayCol[0]['tamano'],14,$contFila,'LR',0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,$imgDctoModulo,'LR',0,'C',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,$imgEstatus,'LR',0,'C',true);
		$pdf->Cell($arrayCol[3]['tamano'],14,($row['nombre_empresa']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[4]['tamano'],14,date(spanDateFormat, strtotime($row['fecha_transferencia'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['nombreBanco']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['numero_transferencia']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,($row['id_cliente'].".- ".$row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,($row['descripcion_estado_transferencia']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,number_format($row['saldo_transferencia'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[10]['tamano'],14,number_format($row['monto_neto_transferencia'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
		
		$arrayTotal[9] += $row['saldo_transferencia'];
		$arrayTotal[10] += $row['monto_neto_transferencia'];
	}
	
	$pdf->MultiCell($totalAncho,0,'',1,'C',true); // cierra linea de tabla
	
	$pdf->Ln();
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'],14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell($arrayCol[8]['tamano'],14,"TOTALES: ",1,0,'R',true);
	$pdf->Cell($arrayCol[9]['tamano'],14,number_format($arrayTotal[9],2,".",","),1,0,'R',true);
	$pdf->Cell($arrayCol[10]['tamano'],14,number_format($arrayTotal[10],2,".",","),1,0,'R',true);
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>