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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// LISTADO DE FACTURAS ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("estado_pedido IN (1,2)");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(
	(SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
	WHERE acc_ped.id_pedido = ped_vent.id_pedido
		AND acc_ped.estatus_accesorio_pedido = 0) > 0
	OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
		WHERE paq_ped.id_pedido = ped_vent.id_pedido AND paq_ped.estatus_paquete_pedido = 0) > 0
	OR (SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica
			AND uni_fis.estado_venta = 'RESERVADO') > 0)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(ped_vent.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = ped_vent.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_vent.fecha BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(ped_vent.id_pedido LIKE %s
	OR ped_vent.id_presupuesto LIKE %s
	OR ped_vent.id_cliente LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR uni_fis.placa LIKE %s)",
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
}

$query = sprintf("SELECT 
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	cxc_fact.idFactura AS id_factura_reemplazo,
	cxc_fact.numeroFactura AS numero_factura_reemplazo,
	ped_vent.fecha,
	pres_vent_acc.id_presupuesto_accesorio,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT(uni_bas.nom_uni_bas, ': ', marca.nom_marca, ' ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
	uni_fis.serial_carroceria,
	uni_fis.placa,
	ped_vent.precio_venta,
	
	(ped_vent.precio_venta
		+ IFNULL(ped_vent.precio_venta * (ped_vent.porcentaje_iva + ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
	
	ped_vent.porcentaje_inicial,
	ped_vent.inicial AS monto_inicial,
	ped_vent.total_inicial_gastos AS total_general,
	
	(SELECT an_factura_venta.tipo_factura FROM an_factura_venta
	WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
		AND (SELECT COUNT(an_factura_venta.numeroPedido) FROM an_factura_venta
		WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
			AND tipo_factura IN (1,2)) = 1) AS tipo_factura,
	
	pres_vent.estado AS estado_presupuesto,
	ped_vent.estado_pedido,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM an_pedido ped_vent
	INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
	LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
	LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
	LEFT JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	LEFT JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
	LEFT JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
	LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (ped_vent.id_factura_cxc = cxc_fact.idFactura) %s
ORDER BY id_pedido DESC", $sqlBusq);
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
			array("tamano" => "35", "descripcion" => "ESTATUS\n\n"),
			array("tamano" => "90", "descripcion" => "EMPRESA\n\n"),
			array("tamano" => "40", "descripcion" => "FECHA REGISTRO\n"),
			array("tamano" => "40", "descripcion" => "NRO. PEDIDO\n"),
			array("tamano" => "40", "descripcion" => "NRO. PRESUPUESTO\n"),
			array("tamano" => "115", "descripcion" => "CLIENTE\n\n"),
			array("tamano" => "75", "descripcion" => "VEHICULO / ".$spanSerialCarroceria." / PLACA"),
			array("tamano" => "35", "descripcion" => "TIPO PAGO\n"),
			array("tamano" => "50", "descripcion" => "PRECIO VENTA / INICIAL\n"),
			array("tamano" => "50", "descripcion" => "TOTAL GENERAL\n"));
	} else {
		// ENCABEZADO DE LA TABLA
		$arrayCol = array(
			array("tamano" => "20", "descripcion" => ""),
			array("tamano" => "35", "descripcion" => "ESTATUS"),
			array("tamano" => "100", "descripcion" => "EMPRESA\n"),
			array("tamano" => "60", "descripcion" => "FECHA REGISTRO\n"),
			array("tamano" => "60", "descripcion" => "NRO. PEDIDO\n"),
			array("tamano" => "60", "descripcion" => "NRO. PRESUPUESTO\n"),
			array("tamano" => "145", "descripcion" => "CLIENTE\n"),
			array("tamano" => "100", "descripcion" => "VEHICULO / ".$spanSerialCarroceria." / PLACA"),
			array("tamano" => "50", "descripcion" => "TIPO PAGO\n"),
			array("tamano" => "70", "descripcion" => "PRECIO VENTA / INICIAL\n"),
			array("tamano" => "70", "descripcion" => "TOTAL GENERAL\n"));
	}
	$totalAncho = $arrayCol[0]['tamano'] + $arrayCol[1]['tamano'] + $arrayCol[2]['tamano'] + $arrayCol[3]['tamano'] + $arrayCol[4]['tamano'] + $arrayCol[5]['tamano'] + $arrayCol[6]['tamano'] + $arrayCol[7]['tamano'] + $arrayCol[8]['tamano'] + $arrayCol[9]['tamano'] + $arrayCol[10]['tamano'] + $arrayCol[11]['tamano'];
	
	$pdf->Ln();
	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell($totalAncho,10,"PEDIDOS Y/O ORDENES POR FACTURAR",0,0,'C');
	
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
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "Presupuesto Autorizado";
		} else if ($row['estado_presupuesto'] == 1 || $row['estado_presupuesto'] == "") {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "Pedido Autorizado"; break;
				case 2 : $imgEstatusPedido = "Facturado"; break;
				case 3 : $imgEstatusPedido = "Pedido Desautorizado"; break;
				case 4 : $imgEstatusPedido = "Nota de Crédito"; break;
				case 5 : $imgEstatusPedido = "Anulado"; break;
		}	
		} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "Presupuesto Anulado";
		} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "Presupuesto Desautorizado";
		}
		
		$pdf->Cell($arrayCol[0]['tamano'],14,$contFila,'LR',0,'C',true);
		$pdf->Cell($arrayCol[1]['tamano'],14,$imgEstatusPedido,'LR',0,'C',true);
		$pdf->Cell($arrayCol[2]['tamano'],14,($row['nombre_empresa']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[3]['tamano'],14,date(spanDateFormat, strtotime($row['fecha'])),'LR',0,'C',true);
		$pdf->Cell($arrayCol[4]['tamano'],14,($row['numeracion_pedido']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[5]['tamano'],14,($row['numeracion_presupuesto']),'LR',0,'R',true);
		$pdf->Cell($arrayCol[6]['tamano'],14,($row['id_cliente'].".- ".$row['nombre_cliente']),'LR',0,'L',true);
		$pdf->Cell($arrayCol[7]['tamano'],14,($row['vehiculo']),'LR',0,'C',true);
		$pdf->Cell($arrayCol[8]['tamano'],14,utf8_decode(($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO"),'LR',0,'C',true);
		$pdf->Cell($arrayCol[9]['tamano'],14,number_format($row['precio_venta'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Cell($arrayCol[10]['tamano'],14,number_format($row['total_general'], 2, ".", ","),'LR',0,'R',true);
		$pdf->Ln();
		
		$fill = !$fill;
	}
	
	$pdf->MultiCell($totalAncho,0,'',1,'C',true); // cierra linea de tabla
	
	$pdf->Ln();
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>