<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

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

// DATOS DE LA SOLICITUD
$query = sprintf("SELECT 
	sa_det_solicitud_repuestos.id_det_solicitud_repuesto,
	sa_det_solicitud_repuestos.id_det_orden_articulo,
	sa_det_solicitud_repuestos.tiempo_despacho,
	sa_det_solicitud_repuestos.id_estado_solicitud,
	sa_det_orden_articulo.id_det_orden_articulo,
	sa_det_orden_articulo.id_articulo,
	sa_solicitud_repuestos.id_solicitud,
	sa_solicitud_repuestos.numero_solicitud,
	sa_solicitud_repuestos.estado_solicitud,
	sa_solicitud_repuestos.id_jefe_repuesto,
	sa_solicitud_repuestos.tiempo_recibo_jefe_repuesto,
	sa_solicitud_repuestos.id_empleado_recibo,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	orden.id_orden,
	orden.numero_orden,
	sa_solicitud_repuestos.tiempo_solicitud,
	orden.id_estado_orden,
	sa_solicitud_repuestos.id_jefe_taller,
	vw_sa_vales_recepcion.id_recepcion,
	vw_sa_vales_recepcion.numeracion_recepcion,
	vw_sa_vales_recepcion.lci_cliente_pago,
	vw_sa_vales_recepcion.ci_cliente_pago,
	CONCAT_WS(' ', vw_sa_vales_recepcion.nombre_cliente_pago, vw_sa_vales_recepcion.apellido_cliente_pago) AS nombre_cliente_pago,
	CONCAT_WS(' ', vw_sa_vales_recepcion.nombre, vw_sa_vales_recepcion.apellido) AS nombre_cliente,
	vw_sa_vales_recepcion.lci,
	vw_sa_vales_recepcion.ci,
	sa_det_solicitud_repuestos.id_casilla
FROM sa_det_solicitud_repuestos
	INNER JOIN sa_det_orden_articulo ON (sa_det_solicitud_repuestos.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
	INNER JOIN sa_solicitud_repuestos ON (sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud)
	INNER JOIN iv_articulos art ON (sa_det_orden_articulo.id_articulo = art.id_articulo)
	INNER JOIN sa_orden orden ON (sa_solicitud_repuestos.id_orden = orden.id_orden)
	INNER JOIN vw_sa_vales_recepcion ON (orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
WHERE sa_det_solicitud_repuestos.id_solicitud = %s",
	valTpDato($valCadBusq[1], "int"));
$rs = mysql_query($query) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_array($rs);

// DATOS DEL APROBADOR
$queryMecanico = sprintf("SELECT *, CONCAT_WS(' ', nombre_empleado, apellido) as nombre_empleado FROM pg_empleado
WHERE id_empleado = '%s'",
	$row['id_jefe_taller']);
$rsMecanico = mysql_query($queryMecanico) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowMecanico = mysql_fetch_array($rsMecanico);

// DATOS DEL VEHICULO
$queryRecepcion = sprintf("SELECT 
	vw_sa_vales_recepcion.placa,
	vw_sa_vales_recepcion.chasis,
	orden.id_recepcion,
	orden.id_orden
FROM sa_orden orden
	INNER JOIN vw_sa_vales_recepcion ON (orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
WHERE orden.id_orden = '%s'",
	$row['id_orden']);
$rsRecepcion = mysql_query($queryRecepcion) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowRecepcion = mysql_fetch_array($rsRecepcion);

//if ($totalRows > 0) {
	$pdf->nombreRegistrado = $row['nombre_empleado'];
	$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
	$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
	$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
	$pdf->direccion = $rowEmp['direccion'];
	$pdf->telefono1 = $rowEmp['telefono1'];
	$pdf->telefono2 = $rowEmp['telefono2'];
	$pdf->web = $rowEmp['web'];
	$pdf->mostrarHeader = 1;
	
	$pdf->AddPage();
	
	$pdf->Cell('',8,'',0,2);
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(455);
	$pdf->Cell(60,12,utf8_decode("Fecha: "),0,0,'R');
	$pdf->Cell(100,12,utf8_decode(date(spanDateFormat,strtotime($row['tiempo_solicitud']))),0,0,'L');
	$pdf->Ln();
	
	$pdf->Cell(50,12,utf8_decode("Cliente: "),0,0,'R');
	if($rowRecepcion['id_cliente_pago'] == NULL) {
		$pdf->Cell(45,12,$row['nombre_cliente'],0,0,'L');
	} else {
		$pdf->Cell(45,12,$row['nombre_cliente_pago'],0,0,'L');
	}
	$pdf->SetX(455);
	$pdf->Cell(60,12,utf8_decode("Nro. Orden: "),0,0,'R');
	$pdf->Cell(45,12,$row['numero_orden'],0,0,'L');
	$pdf->Ln();
	
	$pdf->Cell(50,12,utf8_decode("Placa: "),0,0,'R');
	$pdf->Cell(80,12,$rowRecepcion['placa'],0,0,'L');
	$pdf->Cell(50,12,utf8_decode("Chasis: "),0,0,'R');
	$pdf->Cell(80,12,$rowRecepcion['chasis'],0,0,'L');
	$pdf->SetX(455);
	$pdf->Cell(60,12,utf8_decode("Nro. Solicitud: "),0,0,'R');
	$pdf->Cell(45,12,$row['numero_solicitud'],0,0,'L');
	$pdf->Ln();
	
	$pdf->SetX(455);
	$pdf->Cell(60,12,utf8_decode("Estado Solicitud: "),0,0,'R');
	if($row['estado_solicitud'] == 1)
		$pdf->Cell(45,12,"Abierta",0,0,'L');
	else if($row['estado_solicitud'] == 2)
		$pdf->Cell(45,12,"Aprobada",0,0,'L');
	else if($row['estado_solicitud'] == 3)
		$pdf->Cell(45,12,"Despachada",0,0,'L');
	else if($row['estado_solicitud'] == 4)
		$pdf->Cell(45,12,"Devuelto",0,0,'L');
	else if($row['estado_solicitud'] == 0)
		$pdf->Cell(45,12,"-",0,0,'L');
	$pdf->Ln();
	
	$pdf->SetX(455);
	$pdf->Cell(60,12,utf8_decode("Aprobado por: "),0,0,'R');
	$pdf->Cell(45,12,$rowMecanico['nombre_empleado'],0,0,'L');
	$pdf->Ln();
	
	$pdf->Cell('',10);
	$pdf->Ln();
	
	// COLUMNAS
	// Colores, ancho de línea y fuente en negrita
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',6.8);
	
	$arrayTamCol = array("94","50","230","83","55","50");
	$arrayCol = array("Código","Tipo","Descripción","Almacen","Ubicacion","Cantidad");
	
	foreach ($arrayCol as $indice => $valor) {
		$pdf->Cell($arrayTamCol[$indice],16,utf8_decode($valor),1,0,'C',true);
	}
	$pdf->Ln();
	
	$queryDet = sprintf("SELECT 
		det_sol_rep.id_solicitud,
		det_orden_art.id_articulo,
		det_sol_rep.id_casilla,
		vw_art_emp_ubic.codigo_articulo,
		vw_art_emp_ubic.descripcion,
		vw_art_emp_ubic.descripcion_almacen,
		vw_art_emp_ubic.ubicacion,
		1 AS cantidad
	FROM sa_det_orden_articulo det_orden_art
		INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
		INNER JOIN vw_iv_articulos_empresa_ubicacion vw_art_emp_ubic ON (det_sol_rep.id_casilla = vw_art_emp_ubic.id_casilla) AND (vw_art_emp_ubic.id_articulo = det_orden_art.id_articulo)
	WHERE det_sol_rep.id_solicitud = %s",
		valTpDato($valCadBusq[1],"int"));
	
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	// DATA
	while ($rowDet = mysql_fetch_assoc($rsDet)) {
		// Restauración de colores y fuentes
		($fill == true) ? $pdf->SetFillColor(234,244,255) : $pdf->SetFillColor(255,255,255);
			
		$pdf->Cell($arrayTamCol[0],16,elimCaracter($rowDet['codigo_articulo'],";"),'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[1],16,$rowDet['tipo_articulo'],'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[2],16,substr($rowDet['descripcion'],0,50),'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[3],16,$rowDet['descripcion_almacen'],'LR',0,'L',true);
		$pdf->Cell($arrayTamCol[4],16,str_replace("-[]", "", $rowDet['ubicacion']),'LR',0,'C',true);
		$pdf->Cell($arrayTamCol[5],16,number_format(($rowDet['cantidad']), 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
	}
	$pdf->Cell(array_sum($arrayTamCol),0,'','T');
//}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>