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
	$sqlBusq .= $cond.sprintf("ch.id_departamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(ch.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = ch.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ch.fecha_cheque BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ch.estatus = %s",
		valTpDato($valCadBusq[3], "int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ch.estado_cheque IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ch.id_departamento IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(ch.numero_cheque LIKE %s
	OR banco.nombreBanco LIKE %s
	OR cliente.nombre LIKE %s
	OR cliente.apellido LIKE %s
	OR ch.observacion_cheque LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

$query = sprintf("SELECT
	ch.id_cheque,
	ch.tipo_cheque,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	ch.monto_neto_cheque,
	IF (ch.estatus = 1, ch.saldo_cheque, 0) AS saldo_cheque,
	ch.fecha_cheque,
	ch.numero_cheque,
	banco.nombreBanco,
	ch.id_departamento,
	ch.estatus,
	IF (ch.estatus = 1, ch.estado_cheque, NULL) AS estado_cheque,
	(CASE ch.estatus
		WHEN 1 THEN
			(CASE ch.estado_cheque
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
			END)
		ELSE
			'Anulado'
	END) AS descripcion_estado_cheque,
	ch.observacion_cheque,
	
	ch.id_empleado_registro AS id_empleado_creador,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	ch.fecha_anulado,
	ch.id_empleado_anulado,
	vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
	ch.motivo_anulacion,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, 
		CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), 
		vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
	
FROM cj_cc_cheque ch
	INNER JOIN cj_cc_cliente cliente ON (ch.id_cliente = cliente.id)
	INNER JOIN bancos banco ON (ch.id_banco_cliente = banco.idBanco)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (ch.id_empleado_registro = vw_pg_empleado_creador.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (ch.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ch.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY id_cheque DESC", $sqlBusq);
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
			array("tamano" => "25", "descripcion" => "MODULO"),
			array("tamano" => "35", "descripcion" => "ESTATUS\n\n"),
			array("tamano" => "90", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "40", "descripcion" => "FECHA CHEQUE\n"),
			array("tamano" => "50", "descripcion" => "BANCO\n\n"),
			array("tamano" => "40", "descripcion" => "NRO. CHEQUE\n"),
			array("tamano" => "115", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "75", "descripcion" => "ESTADO CHEQUE\n\n"),
			array("tamano" => "50", "descripcion" => "SALDO CHEQUE\n"),
			array("tamano" => "50", "descripcion" => "TOTAL CHEQUE\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => ""),
			array("tamano" => "35", "descripcion" => "MODULO"),
			array("tamano" => "35", "descripcion" => "ESTATUS"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n"),
			array("tamano" => "60", "descripcion" => "FECHA CHEQUE\n"),
			array("tamano" => "70", "descripcion" => "BANCO\n"),
			array("tamano" => "60", "descripcion" => "NRO. CHEQUE\n"),
			array("tamano" => "160", "descripcion" => "CLIENTE\n"),
			array("tamano" => "90", "descripcion" => "ESTADO CHEQUE\n"),
			array("tamano" => "70", "descripcion" => "SALDO CHEQUE\n"),
			array("tamano" => "70", "descripcion" => "TOTAL CHEQUE\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'];
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,"CHEQUES",0,0,'C');
	
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
		$pdf->Cell($arrayCol[4]['tamano'],14,date(spanDateFormat, strtotime($row['fecha_cheque'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['nombreBanco']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['numero_cheque']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,($row['id_cliente'].".- ".$row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,($row['descripcion_estado_cheque']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,number_format($row['saldo_cheque'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[10]['tamano'],14,number_format($row['monto_neto_cheque'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
		
		$arrayTotal[9] += $row['saldo_cheque'];
		$arrayTotal[10] += $row['monto_neto_cheque'];
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