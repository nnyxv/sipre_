<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
//$pdf->nombreRegistrado = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$valBusq2 = $_GET['valBusq2'];
$frmVentas = explode("|", $valBusq2);

$valBusq3 = $_GET['valBusq3'];
$frmCostos = explode("|", $valBusq3);

$valBusq4 = $_GET['valBusq4'];
$frmUtilidad = explode("|", $valBusq4);

$valBusq5 = $_GET['valBusq5'];
$frmIndicadores = explode("|", $valBusq5);

$valBusq6 = $_GET['valBusq6'];
$frmGasto = explode("!", $valBusq6);

foreach ($frmGasto as $valor){
	$frmGasto2[] = explode("|", $valor);
}

$cmb = "&";
$sust = "*";

$ventas2 = $frmGasto2[0];
$postVenta2 = $frmGasto2[1];
$nomina2 = $frmGasto2[2];

foreach ($ventas2 as $valor){
	$ventas3[] = explode("_", $valor);
}
foreach ($ventas3 as $valor){
	$valor[0] = str_replace($sust, $cmb, $valor[0]);
	$ventas[$valor[0]] = $valor[1];
	$ventasPorc[$valor[0]] = ($valor[1] / $frmVentas[2])*100;
}

foreach ($postVenta2 as $valor){
	$postVenta3[] = explode("_", $valor);
}
foreach ($postVenta3 as $valor){
	$valor[0] = str_replace($sust, $cmb, $valor[0]);
	$postVenta[$valor[0]] = $valor[1];
	$postVentaPorc[$valor[0]] = ($valor[1] / $frmVentas[2])*100;
}

foreach ($nomina2 as $valor){
	$nomina3[] = explode("_", $valor);
}
foreach ($nomina3 as $valor){
	$valor[0] = str_replace($sust, $cmb, $valor[0]);
	$nomina[$valor[0]] = $valor[1];
	$nominaPorc[$valor[0]] = ($valor[1] / $frmVentas[2])*100;
}
// echo "<pre>";
// var_dump($valBusq6);

	//SUB TOTAL SALARIOS VENTAS
	$subSalarioVent = 	$ventas['Empleados de Mantenimiento y Seguridad']+
						$ventas['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']+
						$ventas['Salario Fijo de Vendedores']+
						$ventas['Salario Gerente de Ventas'];

	//SUB TOTAL SALARIOS POST VENTAS
	$subSalarioPost =	$postVenta['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']+
						$postVenta['Salarios Vendedores de Piezas (P&A)']+
						$postVenta['Salario de Asesores de Servicio']+
						$postVenta['Salario Gerente de Servicio']+
						$postVenta['Salario Jefe de Taller']+
						$postVenta['Empleados de Mantenimiento y Seguridad']+
						$postVenta['Salario Gerente de Piezas'];
					
	//TOTAL VENTAS
	$totalVentas = 	$subSalarioVent +
					$ventas['Desempleo (Federal, Estatal)']+
					$ventas['Seguro de Incapacidad']+
					$ventas['Beneficios generales empelados']+
					$ventas['Seguro Social']+
					$ventas['Renta']+
					$ventas['Property Taxes']+
					$ventas['Teléfono']+
					$ventas['Utilidades']+
					$ventas['Depreciación']+
					$ventas['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']+
					$ventas['Seguros de Propiedad, Transportación, entre otros']+
					$ventas['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']+
					$ventas['Misceláneos'];
					
	//TOTAL GENERALES DEL CONCESIONARIO
	$totalGenConce = 	$nomina['Materiales de Oficina']+
						$nomina['Publicidad']+
						$nomina['Mercadeo']+
						$nomina['Contabilidad']+
						$nomina['Recursos Humanos']+
						$nomina['Sistema, Informática, Asistencia Técnica']+
						$nomina['Legal & Audit Fees']+
						$nomina['Alta Gerencia'];
	
	//TOTAL DE POSTVENTA
	$totalPostV = 	$subSalarioPost+
					$postVenta['Misceláneos']+
					$postVenta['Suplidores Externos (si no se considera en piezas o servicios)']+
					$postVenta['Servicios Externos de Servicio']+
					$postVenta['Seguros de Propiedad, Transportación, entre otros']+
					$postVenta['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']+
					$postVenta['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']+
					$postVenta['Utilidades']+
					$postVenta['Teléfono']+
					$postVenta['Mantenimiento de Equipos, Facilidades y Reparaciones']+
					$postVenta['Depreciación']+
					$postVenta['Property Taxes']+
					$postVenta['Renta']+
					$postVenta['Seguro Social']+
					$postVenta['Beneficios generales empleados']+
					$postVenta['Seguro de Incapacidad']+
					$postVenta['Desempleo (Federal, Estatal)'];
	
	//TOTAL GASTO
	$totalGasto = $totalVentas + $totalGenConce + $totalPostV;
					
$idEmpresa = $valCadBusq[0];
$valFecha[0] = date("m", strtotime("01-".$valCadBusq[1]));
$valFecha[1] = date("Y", strtotime("01-".$valCadBusq[1]));
$nroDecimales = ($_GET['lstDecimalPDF'] == 1) ? 2 : 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// RESPORTE DE ABSORCION FINANCIERA
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$posY = 0;

imagestring($img,1,0,$posY,str_pad(utf8_decode("REPORTE DE ABSORCIÓN FINANCIERA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")"), 152, " ", STR_PAD_BOTH),$textColor);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VENTAS Y COSTOS
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$posY += 20;
imagestring($img,1,0,$posY,str_pad(utf8_decode("VENTAS"), 76, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,380,$posY,str_pad(utf8_decode("COSTOS"), 76, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);
imagestring($img,1,402,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("CONCEPTO")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(strtoupper('FACTURADO'), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,410,$posY,str_pad(strtoupper(utf8_decode("CONCEPTO")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(strtoupper('FACTURADO'), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);
imagestring($img,1,402,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode('Venta Total de Servicio')), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmVentas[0], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,410,$posY,str_pad(strtoupper(utf8_decode("Costo de Mano de Obra (Técnicos)")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(formatoNumero($frmCostos[0], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,410,$posY,str_pad(strtoupper(utf8_decode("Costos por incentivo")), 10, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(formatoNumero($frmCostos[1], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode('Venta Total de Piezas')), 24, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmVentas[1], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,410,$posY,str_pad(strtoupper(utf8_decode("Costo Total de Piezas")), 20, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(formatoNumero($frmCostos[2], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);
imagestring($img,1,402,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 2;
imagestring($img,1,0,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);
imagestring($img,1,402,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,412,$posY,str_pad(strtoupper(utf8_decode("Total:")), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(formatoNumero($frmCostos[3], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,6,$posY,str_pad(strtoupper(utf8_decode('Total:')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmVentas[2], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,412,$posY,str_pad(strtoupper(utf8_decode("Total con Incentivos:")), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,610,$posY,str_pad(formatoNumero($frmCostos[4], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

//UTILIDADES
$posY += 26;
imagestring($img,1,0,$posY,str_pad(utf8_decode(strtoupper("Utilidad (Ventas - Costos)")), 76, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 76, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("CONCEPTO")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(strtoupper('FACTURADO'), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,300,$posY,str_pad(strtoupper('%'), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 76, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Departamento de Servicio")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmUtilidad[0], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,300,$posY,str_pad(formatoNumero(($frmUtilidad[0]/$frmUtilidad[2])*100, 1, 0)."%", 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("Departamento de Piezas")), 24, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmUtilidad[1], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,300,$posY,str_pad(formatoNumero(($frmUtilidad[1]/$frmUtilidad[2])*100, 1, 0)."%", 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 76, "-", STR_PAD_BOTH),$textColor);

$posY += 2;
imagestring($img,1,0,$posY,str_pad("", 76, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(strtoupper(utf8_decode("TOTAL:")), 8, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmUtilidad[2], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,6,$posY,str_pad(strtoupper(utf8_decode("Utilidad Total Considerando Incentivos:")), 26, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,200,$posY,str_pad(formatoNumero($frmUtilidad[3], 1, $nroDecimales), 26, " ", STR_PAD_BOTH),$textColor);

//GASTOS GENERALES
$posY += 26;
imagestring($img,1,0,$posY,str_pad(utf8_decode(strtoupper("Gastos Generales (Todo gasto referente a la operación general del concesionario)")), 152, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(utf8_decode("POSTVENTAS"), 76, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,380,$posY,str_pad(utf8_decode("VENTAS"), 76, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);
imagestring($img,1,402,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('CONCEPTO')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(strtoupper(utf8_decode("PAGOS")), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(strtoupper(utf8_decode("%")), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('CONCEPTO')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(strtoupper(utf8_decode("PAGOS")), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(strtoupper(utf8_decode("%")), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);
imagestring($img,1,402,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Salario Gerente de Piezas')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Salario Gerente de Piezas'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Salario Gerente de Piezas'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Salario Gerente de Ventas')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Salario Gerente de Ventas'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Salario Gerente de Ventas'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Salario Jefe de Taller')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Salario Jefe de Taller'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Salario Jefe de Taller'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Salario Gerente de Servicio')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Salario Gerente de Servicio'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Salario Gerente de Servicio'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Salario Fijo de Vendedores')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Salario Fijo de Vendedores'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Salario Fijo de Vendedores'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Salario de Asesores de Servicio')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Salario de Asesores de Servicio'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Salario de Asesores de Servicio'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Salarios Clericales')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Empleados de Mantenimiento ')), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Salarios Vendedores de Piezas (P&A)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Salarios Vendedores de Piezas (P&A)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Salarios Vendedores de Piezas (P&A)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode(' y Seguridad (P&A)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Empleados de Mantenimiento y Seguridad (P&A)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Empleados de Mantenimiento y Seguridad (P&A)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Salarios Clericales')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Empleados de Mantenimiento')), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Sub Total Salarios:')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($subSalarioVent, 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode(' y Seguridad ')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Empleados de Mantenimiento y Seguridad'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Empleados de Mantenimiento y Seguridad'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Desempleo (Federal, Estatal)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Desempleo (Federal, Estatal)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Desempleo (Federal, Estatal)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Seguro de Incapacidad')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Seguro de Incapacidad'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Seguro de Incapacidad'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Sub Total Salarios:')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($subSalarioPost, 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Beneficios generales empelados')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Beneficios generales empelados'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Beneficios generales empelados'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Desempleo (Federal, Estatal)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Desempleo (Federal, Estatal)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Desempleo (Federal, Estatal)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Seguro Social')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Seguro Social'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Seguro Social'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Seguro de Incapacidad')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Seguro de Incapacidad'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Seguro de Incapacidad'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Renta')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Renta'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Renta'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Beneficios generales empleados')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Beneficios generales empleados'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Beneficios generales empleados'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Property Taxes')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Property Taxes'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Property Taxes'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Seguro Social')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Seguro Social'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Seguro Social'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Teléfono')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Teléfono'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Teléfono'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Renta')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Renta'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Renta'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Utilidades')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Utilidades'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Utilidades'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Property Taxes')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Property Taxes'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Property Taxes'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Depreciación')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Depreciación'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Depreciación'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Depreciación')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Depreciación'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Depreciación'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Patentes, Contribuciones y')), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Teléfono')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Teléfono'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Teléfono'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode(' Gastos Gubernamentales')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Utilidades')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Utilidades'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Utilidades'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Seguros de Propiedad,')), 6, " ", STR_PAD_BOTH),$textColor);

$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

$img = @imagecreate(760, 520) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$posY = 0;

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Patentes, Contribuciones y ')), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Transportación, entre otros')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Seguros de Propiedad, Transportación, entre otros'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Seguros de Propiedad, Transportación, entre otros'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode(' Gastos Gubernamentales')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Company Cars y Gastos ')), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Seguros de Propiedad,')), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Asociados')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode(' Transportación, entre otros')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Seguros de Propiedad, Transportación, entre otros'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Seguros de Propiedad, Transportación, entre otros'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Misceláneos')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($ventas['Misceláneos'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($ventasPorc['Misceláneos'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Company Cars y Gastos Asociados)')), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('(Gasolina, Mant, entre otros)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Total: ')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($totalVentas, 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Mantenimiento de Equipos,')), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode(' Facilidades y Reparaciones')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Mantenimiento de Equipos, Facilidades y Reparaciones'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Mantenimiento de Equipos, Facilidades y Reparaciones'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Servicios Externos de Servicio')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Servicios Externos de Servicio'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Servicios Externos de Servicio'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,522,$posY,str_pad(strtoupper(utf8_decode('Generales del Concesionario')), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Suplidores Externos (si no se')), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('considera en piezas o servicios)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Suplidores Externos (si no se considera en piezas o servicios)'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Suplidores Externos (si no se considera en piezas o servicios)'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Misceláneos)')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($postVenta['Misceláneos'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,309,$posY,str_pad(number_format($postVentaPorc['Misceláneos'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Alta Gerencia ')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Alta Gerencia'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Alta Gerencia'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Sistema, Informática,')), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode(' Asistencia Técnica')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Sistema, Informática, Asistencia Técnica'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Sistema, Informática, Asistencia Técnica'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Legal & Audit Fees')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Legal & Audit Fees'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Legal & Audit Fees'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Recursos Humanos')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Recursos Humanos'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Recursos Humanos'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Contabilidad')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Contabilidad'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Contabilidad'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Mercadeo')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Mercadeo'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Mercadeo'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(strtoupper(utf8_decode('Total:')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad(formatoNumero($totalPostV, 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Publicidad')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Publicidad'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Publicidad'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Materiales de Oficina')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($nomina['Materiales de Oficina'], 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(number_format($nominaPorc['Materiales de Oficina'], 1, ".", ",")." %", 6, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('total:')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,592,$posY,str_pad(formatoNumero($totalGenConce, 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);

$posY += 20;
imagestring($img,1,12,$posY,str_pad("", 156, "-", STR_PAD_BOTH),$textColor);

$posY += 2;
imagestring($img,1,12,$posY,str_pad("", 156, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,416,$posY,str_pad(strtoupper(utf8_decode('Total Gastos Generales:')), 6, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,692,$posY,str_pad(formatoNumero($totalGasto, 1, $nroDecimales), 6, " ", STR_PAD_BOTH),$textColor);

//iNDICADOR DE DESEMPEÑO
$posY += 26;
imagestring($img,1,0,$posY,str_pad(utf8_decode(strtoupper("Indicador de Desempeño")), 76, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(utf8_decode("CONCEPTO"), 30, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,192,$posY,str_pad(utf8_decode("FACTURADO"), 30, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,12,$posY,str_pad(utf8_decode("Total Utilidad Posventas"), 30, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,192,$posY,str_pad(formatoNumero($frmIndicadores[0], 1, $nroDecimales), 30, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,28,$posY,str_pad(utf8_decode("Total Utilidad Posventa Considerando"), 30, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,22,$posY,str_pad(utf8_decode("   Incentivos"), 2, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,192,$posY,str_pad(formatoNumero($frmIndicadores[1], 1, $nroDecimales), 30, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,8,$posY,str_pad(utf8_decode("Total Gastos Generales"), 30, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,192,$posY,str_pad(formatoNumero($frmIndicadores[2], 1, $nroDecimales), 30, " ", STR_PAD_BOTH),$textColor);

$posY += 18;
imagestring($img,1,12,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 2;
imagestring($img,1,12,$posY,str_pad("", 74, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,28,$posY,str_pad(utf8_decode("Absorción Financiera de Posventa"), 30, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,192,$posY,str_pad(formatoNumero($frmIndicadores[3], 1, $nroDecimales)." %", 30, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,28,$posY,str_pad(utf8_decode("Absorción Financiera Considerando"), 30, " ", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad(utf8_decode("Mano de Obra"), 28, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,192,$posY,str_pad(formatoNumero($frmIndicadores[4], 1, $nroDecimales)." %", 30, " ", STR_PAD_BOTH),$textColor);



$arrayImg[] = "tmp/"."reporte_venta".$pageNum++.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 60, 760, 520);
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}

function formatoNumero($monto, $idFormatoNumero = 1, $nroDecimales = 2){
	switch($idFormatoNumero) {
		case 1 : return number_format($monto, $nroDecimales, ".", ","); break;
		case 2 : return number_format($monto, $nroDecimales, ",", "."); break;
		case 3 : return number_format($monto, $nroDecimales, ".", ""); break;
		case 4 : return number_format($monto, $nroDecimales, ",", ""); break;
	}
}
?>