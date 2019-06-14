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

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// LISTADO DE NOTAS DE DEBITO ///////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($idModuloPpal)) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idDepartamentoOrigenNotaCargo IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.aplicaLibros = %s",
		valTpDato($valCadBusq[3], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.estadoNotaCargo IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
	OR cxc_nd.numeroControlNotaCargo LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR cxc_nd.observacionNotaCargo LIKE %s
	OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_chasis LIKE %s
	OR uni_fis.placa LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

$query = sprintf("SELECT
	cxc_nd.idNotaCargo,
	cxc_nd.numeroNotaCargo,
	cxc_nd.numeroControlNotaCargo,
	cxc_nd.fechaRegistroNotaCargo,
	cxc_nd.fechaVencimientoNotaCargo,
	cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_nd.estadoNotaCargo,
	(CASE cxc_nd.estadoNotaCargo
		WHEN 0 THEN 'No Cancelado'
		WHEN 1 THEN 'Cancelado'
		WHEN 2 THEN 'Cancelado Parcial'
	END) AS descripcion_estado_nota_cargo,
	cxc_nd.montoTotalNotaCargo,
	cxc_nd.saldoNotaCargo,
	cxc_nd.observacionNotaCargo,
	cxc_nd.aplicaLibros,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
		INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
	
	(IFNULL(cxc_nd.subtotalNotaCargo, 0)
		- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
	
	(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
		+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
	
	(IFNULL(cxc_nd.subtotalNotaCargo, 0)
		- IFNULL(cxc_nd.descuentoNotaCargo, 0)
		+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
			+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
	
	uni_fis.id_unidad_fisica,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM cj_cc_notadecargo cxc_nd
	INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
	LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY idNotaCargo DESC", $sqlBusq);
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
			array("tamano" => "40", "descripcion" => "NRO. NOTA DE DÉBITO\n"),
			array("tamano" => "130", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "100", "descripcion" => "MOTIVO\n\n"),
			array("tamano" => "45", "descripcion" => "ESTADO NOTA DE DÉBITO\n"),
			array("tamano" => "50", "descripcion" => "SALDO NOTA DE DÉBITO\n"),
			array("tamano" => "50", "descripcion" => "TOTAL NOTA DE DÉBITO\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => "\n\n"),
			array("tamano" => "35", "descripcion" => "MODULO\n\n"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "60", "descripcion" => "FECHA REGISTRO\n\n"),
			array("tamano" => "60", "descripcion" => "NRO. NOTA DE DÉBITO\n"),
			array("tamano" => "160", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "135", "descripcion" => "MOTIVO\n\n"),
			array("tamano" => "60", "descripcion" => "ESTADO NOTA DE DÉBITO\n"),
			array("tamano" => "70", "descripcion" => "SALDO NOTA DE DÉBITO\n"),
			array("tamano" => "70", "descripcion" => "TOTAL NOTA DE DÉBITO\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'];
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,utf8_decode("NOTAS DE DÉBITO"),0,0,'C');
	
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
		
		$pdf->MultiCell($arrayCol[$indice]['tamano'],14,utf8_decode($arrayCol[$indice]['descripcion']),1,'C',true);
		
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
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		switch($row['estatus']) {
			case 0 : $imgEstatus = "Anulado"; break;
			case 1 : $imgEstatus = "Activo"; break;
			default : $imgEstatus = $row['estatus'];
		}
		
		$pdf->Cell($arrayCol[0]['tamano'],14,$contFila,'LR',0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,$imgDctoModulo,'LR',0,'C',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,($row['nombre_empresa']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[3]['tamano'],14,date(spanDateFormat, strtotime($row['fechaRegistroNotaCargo'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[4]['tamano'],14,($row['numeroNotaCargo']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['id_cliente'].".- ".$row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['descripcion_motivo']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,($row['descripcion_estado_nota_cargo']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,number_format($row['saldoNotaCargo'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,number_format($row['montoTotalNotaCargo'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
		
		$arrayTotal[9] += $row['saldoNotaCargo'];
		$arrayTotal[10] += $row['montoTotalNotaCargo'];
	}
	
	$pdf->MultiCell($totalAncho,0,'',1,'C',true); // cierra linea de tabla
	
	$pdf->Ln();
	
	// TOTAL DOCUMENTOS
	$pdf->SetFillColor(255,255,255);
	$pdf->Cell($arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'],14,"",'T',0,'L',true);
	$pdf->SetFillColor(204,204,204,204);
	$pdf->Cell($arrayCol[7]['tamano'],14,"TOTALES: ",1,0,'R',true);
	$pdf->Cell($arrayCol[8]['tamano'],14,number_format($arrayTotal[9],2,".",","),1,0,'R',true);
	$pdf->Cell($arrayCol[9]['tamano'],14,number_format($arrayTotal[10],2,".",","),1,0,'R',true);
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>