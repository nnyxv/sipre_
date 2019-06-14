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
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
}

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_ant.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxc_ant.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[3] == "2") {
		$sqlBusq .= $cond.sprintf("DATE(cxc_ant.fecha_anulado) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else {
		$sqlBusq .= $cond.sprintf("cxc_ant.fechaAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.estatus = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.estadoAnticipo IN (%s)",
		valTpDato($valCadBusq[5], "campo"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("
	(SELECT COUNT(cxc_pago.id_concepto)
	FROM cj_cc_detalleanticipo cxc_pago
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
	WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
		AND cxc_pago.id_forma_pago IN (11)
		AND cxc_pago.id_concepto IN (%s)) > 0",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
			AND cxc_pago.id_forma_pago IN (11)) LIKE %s
	OR cxc_ant.observacionesAnticipo LIKE %s
	OR cxc_ant.motivo_anulacion LIKE %s)",
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"));
}

$query = sprintf("SELECT
	cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
	cxc_ec.tipoDocumentoN,
	cxc_ec.tipoDocumento,
	cxc_ant.idAnticipo,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cxc_ant.montoNetoAnticipo,
	cxc_ant.totalPagadoAnticipo,
	IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
	cxc_ant.fechaAnticipo,
	cxc_ant.numeroAnticipo,
	cxc_ant.idDepartamento,
	IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
	(CASE cxc_ant.estatus
		WHEN 1 THEN
			(CASE cxc_ant.estadoAnticipo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Asignado Parcial'
				WHEN 3 THEN 'Asignado'
				WHEN 4 THEN 'No Cancelado (Asignado)'
			END)
		ELSE
			'Anulado'
	END) AS descripcion_estado_anticipo,
	cxc_ant.observacionesAnticipo,
	
	cxc_ant.id_empleado_creador,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	cxc_ant.fecha_anulado,
	cxc_ant.id_empleado_anulado,
	vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
	cxc_ant.motivo_anulacion,
	
	(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
	FROM cj_cc_detalleanticipo cxc_pago
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
	WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
		AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
	
	cxc_ec_nd.idEstadoDeCuenta AS id_estado_cuenta_nota_cargo,
	cxc_ec_nd.tipoDocumentoN AS id_tipo_documento_nota_cargo,
	cxc_ec_nd.tipoDocumento AS tipo_documento_nota_cargo,
	cxc_nd.idNotaCargo,
	cxc_nd.id_empresa,
	cxc_nd.fechaRegistroNotaCargo,
	cxc_nd.fechaVencimientoNotaCargo,
	cxc_nd.numeroNotaCargo,
	cxc_nd.numeroControlNotaCargo,
	cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo_nota_cargo,
	cxc_nd.observacionNotaCargo,
	
	(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
	FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
		INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
	WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
	cxc_ant.estatus
FROM cj_cc_anticipo cxc_ant
	INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
	LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ant.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'AN')
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_ant.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_ant.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	LEFT JOIN cj_cc_notadecargo cxc_nd ON ((cxc_ant.idAnticipo = cxc_nd.id_anticipo_anulado AND cxc_nd.id_anticipo_anulado IS NOT NULL)
			OR (cxc_ant.idAnticipo = cxc_nd.id_anticipo_bono AND cxc_nd.id_anticipo_bono IS NOT NULL))
		LEFT JOIN cj_cc_estadocuenta cxc_ec_nd ON (cxc_nd.idNotaCargo = cxc_ec_nd.idDocumento AND cxc_ec_nd.tipoDocumento LIKE 'ND')
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY idAnticipo DESC", $sqlBusq);
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
			array("tamano" => "40", "descripcion" => "FECHA REGISTRO\n"),
			array("tamano" => "40", "descripcion" => "NRO. ANTICIPO\n"),
			array("tamano" => "115", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "50", "descripcion" => "CONCEPTO\n\n"),
			array("tamano" => "75", "descripcion" => "ESTADO ANTICIPO\n\n"),
			array("tamano" => "50", "descripcion" => "SALDO ANTICIPO\n"),
			array("tamano" => "50", "descripcion" => "TOTAL ANTICIPO\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => ""),
			array("tamano" => "35", "descripcion" => "MODULO"),
			array("tamano" => "35", "descripcion" => "ESTATUS"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n"),
			array("tamano" => "60", "descripcion" => "FECHA REGISTRO\n"),
			array("tamano" => "60", "descripcion" => "NRO. ANTICIPO\n"),
			array("tamano" => "160", "descripcion" => "CLIENTE\n"),
			array("tamano" => "70", "descripcion" => "CONCEPTO\n"),
			array("tamano" => "90", "descripcion" => "ESTADO ANTICIPO\n"),
			array("tamano" => "70", "descripcion" => "SALDO ANTICIPO\n"),
			array("tamano" => "70", "descripcion" => "TOTAL ANTICIPO\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'];
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,"ANTICIPOS",0,0,'C');
	
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
		
		switch($row['idDepartamento']) {
			case 0 : $imgDctoModulo = "R"; break;
			case 1 : $imgDctoModulo = "S"; break;
			case 2 : $imgDctoModulo = "V"; break;
			case 3 : $imgDctoModulo = "A"; break;
			case 4 : $imgDctoModulo = "AL"; break;
			case 5 : $imgDctoModulo = "F"; break;
			default : $imgDctoModulo = $row['idDepartamento'];
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
		$pdf->Cell($arrayCol[4]['tamano'],14,date(spanDateFormat, strtotime($row['fechaAnticipo'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['numeroAnticipo']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['id_cliente'].".- ".$row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,($row['descripcion_concepto_forma_pago']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,($row['descripcion_estado_anticipo']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,number_format($row['saldoAnticipo'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[10]['tamano'],14,number_format($row['montoNetoAnticipo'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
		
		$arrayTotal[9] += $row['saldoAnticipo'];
		$arrayTotal[10] += $row['montoNetoAnticipo'];
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