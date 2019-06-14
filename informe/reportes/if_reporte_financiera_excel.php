<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");
session_start();

ob_start();
/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];

$valBusq2 = $_GET['valBusq2'];
$frmVentas = explode("|", $valBusq2);

$valBusq3 = $_GET['valBusq3'];
$frmCostos = explode("|", $valBusq3);

$valBusq4 = $_GET['valBusq4'];
$frmUtilidad = explode("|", $valBusq4);

$valBusq5 = $_GET['valBusq5'];
$frmIndicadores = explode("|", $valBusq5);

$idEmpresa = $valCadBusq[0];
$valFecha[0] = date("m", strtotime("01-".$valCadBusq[1]));
$valFecha[1] = date("Y", strtotime("01-".$valCadBusq[1]));

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
	$ventas[$valor[0]] = ($valor[1] != "0") ? valTpDato(number_format($valor[1], 2, ".", ","),"cero_por_vacio") : "-      ";
	$ventas4[$valor[0]] = $valor[1];
	$ventasPorc[$valor[0]] = ($valor[1] != "0") ? valTpDato(number_format(($valor[1] / $frmVentas[2])*100, 1, ".", ","),"cero_por_vacio") : "0.0 ";
}
foreach ($postVenta2 as $valor){
	$postVenta3[] = explode("_", $valor);
}
foreach ($postVenta3 as $valor){
	$valor[0] = str_replace($sust, $cmb, $valor[0]);
	$postVenta[$valor[0]] = ($valor[1] != '0') ? valTpDato(number_format($valor[1], 2, ".", ","),"cero_por_vacio") : "-      ";
	$postVenta4[$valor[0]] = $valor[1];
	$postVentaPorc[$valor[0]] = ($valor[1] != '0') ? valTpDato(number_format(($valor[1] / $frmVentas[2])*100, 1, ".", ","),"cero_por_vacio") : "0.0 ";
}
foreach ($nomina2 as $valor){
	$nomina3[] = explode("_", $valor);
}
foreach ($nomina3 as $valor){
	$valor[0] = str_replace($sust, $cmb, $valor[0]);
	$nomina[$valor[0]] = ($valor[1] != '0') ? valTpDato(number_format($valor[1], 2, ".", ","),"cero_por_vacio") : "-      ";
	$nomina4[$valor[0]] = $valor[1];
	$nominaPorc[$valor[0]] = ($valor[1] != '0') ? valTpDato(number_format(($valor[1] / $frmVentas[2])*100, 1, ".", ","),"cero_por_vacio") : "0.0 ";
}
// echo "<pre>";
// var_dump($ventas);exit;

//SUB TOTAL SALARIOS VENTAS
	$subSalarioVent = 	valTpDato(number_format($ventas4['Empleados de Mantenimiento y Seguridad']+
							$ventas4['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']+
							$ventas4['Salario Fijo de Vendedores']+
							$ventas4['Salario Gerente de Ventas'], 2, ".", ","),"cero_por_vacio");
	
	$subSalarioVent2 = 	$ventas4['Empleados de Mantenimiento y Seguridad']+
						$ventas4['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']+
						$ventas4['Salario Fijo de Vendedores']+
						$ventas4['Salario Gerente de Ventas'];
	
//SUB TOTAL SALARIOS POST VENTAS
	$subSalarioPost =	valTpDato(number_format($postVenta4['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']+
							$postVenta4['Salarios Vendedores de Piezas (P&A)']+
							$postVenta4['Salario de Asesores de Servicio']+
							$postVenta4['Salario Gerente de Servicio']+
							$postVenta4['Salario Jefe de Taller']+
							$postVenta4['Empleados de Mantenimiento y Seguridad']+
							$postVenta4['Salario Gerente de Piezas'], 2, ".", ","),"cero_por_vacio");
		
	$subSalarioPost2 =		$postVenta4['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']+
							$postVenta4['Salarios Vendedores de Piezas (P&A)']+
							$postVenta4['Salario de Asesores de Servicio']+
							$postVenta4['Salario Gerente de Servicio']+
							$postVenta4['Salario Jefe de Taller']+
							$postVenta4['Empleados de Mantenimiento y Seguridad']+
							$postVenta4['Salario Gerente de Piezas'];
//TOTAL VENTAS
	$totalVentas = 	valTpDato(number_format($subSalarioVent2 +
						$ventas4['Desempleo (Federal, Estatal)']+
						$ventas4['Seguro de Incapacidad']+
						$ventas4['Beneficios generales empelados']+
						$ventas4['Seguro Social']+
						$ventas4['Renta']+
						$ventas4['Property Taxes']+
						$ventas4['Teléfono']+
						$ventas4['Utilidades']+
						$ventas4['Depreciación']+
						$ventas4['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']+
						$ventas4['Seguros de Propiedad, Transportación, entre otros']+
						$ventas4['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']+
						$ventas4['Misceláneos'], 2, ".", ","),"cero_por_vacio");
		
	$totalVentas2 = 	$subSalarioVent2 +
						$ventas4['Desempleo (Federal, Estatal)']+
						$ventas4['Seguro de Incapacidad']+
						$ventas4['Beneficios generales empelados']+
						$ventas4['Seguro Social']+
						$ventas4['Renta']+
						$ventas4['Property Taxes']+
						$ventas4['Teléfono']+
						$ventas4['Utilidades']+
						$ventas4['Depreciación']+
						$ventas4['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']+
						$ventas4['Seguros de Propiedad, Transportación, entre otros']+
						$ventas4['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']+
						$ventas4['Misceláneos'];
	
//TOTAL GENERALES DEL CONCESIONARIO
	$totalGenConce = 	valTpDato(number_format($nomina4['Materiales de Oficina']+
							$nomina4['Publicidad']+
							$nomina4['Mercadeo']+
							$nomina4['Contabilidad']+
							$nomina4['Recursos Humanos']+
							$nomina4['Sistema, Informática, Asistencia Técnica']+
							$nomina4['Legal & Audit Fees']+
							$nomina4['Alta Gerencia'], 2, ".", ","),"cero_por_vacio");
	
	$totalGenConce2 = 		$nomina4['Materiales de Oficina']+
							$nomina4['Publicidad']+
							$nomina4['Mercadeo']+
							$nomina4['Contabilidad']+
							$nomina4['Recursos Humanos']+
							$nomina4['Sistema, Informática, Asistencia Técnica']+
							$nomina4['Legal & Audit Fees']+
							$nomina4['Alta Gerencia'];
	
//TOTAL DE POSTVENTA
	$totalPostV = 	valTpDato(number_format($subSalarioPost2+
						$postVenta4['Misceláneos']+
						$postVenta4['Suplidores Externos (si no se considera en piezas o servicios)']+
						$postVenta4['Servicios Externos de Servicio']+
						$postVenta4['Seguros de Propiedad, Transportación, entre otros']+
						$postVenta4['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']+
						$postVenta4['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']+
						$postVenta4['Utilidades']+
						$postVenta4['Teléfono']+
						$postVenta4['Mantenimiento de Equipos, Facilidades y Reparaciones']+
						$postVenta4['Depreciación']+
						$postVenta4['Property Taxes']+
						$postVenta4['Renta']+
						$postVenta4['Seguro Social']+
						$postVenta4['Beneficios generales empleados']+
						$postVenta4['Seguro de Incapacidad']+
						$postVenta4['Desempleo (Federal, Estatal)'], 2, ".", ","),"cero_por_vacio");
	
	$totalPostV2 = 		$subSalarioPost2+
						$postVenta4['Misceláneos']+
						$postVenta4['Suplidores Externos (si no se considera en piezas o servicios)']+
						$postVenta4['Servicios Externos de Servicio']+
						$postVenta4['Seguros de Propiedad, Transportación, entre otros']+
						$postVenta4['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']+
						$postVenta4['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']+
						$postVenta4['Utilidades']+
						$postVenta4['Teléfono']+
						$postVenta4['Mantenimiento de Equipos, Facilidades y Reparaciones']+
						$postVenta4['Depreciación']+
						$postVenta4['Property Taxes']+
						$postVenta4['Renta']+
						$postVenta4['Seguro Social']+
						$postVenta4['Beneficios generales empleados']+
						$postVenta4['Seguro de Incapacidad']+
						$postVenta4['Desempleo (Federal, Estatal)'];
//TOTAL GASTO
	$totalGasto = valTpDato(number_format($totalVentas2 + $totalGenConce2 + $totalPostV2, 2, ".", ","),"cero_por_vacio");

$contFila = 0;

//VENTAS Y COSTOS
$contFila++;

$tituloDcto = "VENTAS";
$objPHPExcel->getActiveSheet()->setCellValue("B{$contFila}", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("B{$contFila}:C{$contFila}")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("B{$contFila}:C{$contFila}");

$tituloDcto = "COSTOS";
$objPHPExcel->getActiveSheet()->setCellValue("E{$contFila}", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("E{$contFila}:F{$contFila}")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("E{$contFila}:F{$contFila}");

$contFila ++;

$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Concepto ");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Facturado   ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "              ");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Concepto    ");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Facturado   ");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "                   ");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "                   ");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":C".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":F".$contFila)->applyFromArray($styleArrayColumna);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Venta Total de Servicio");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmVentas[0], 2, ".", ","),"cero_por_vacio"));
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Costo de Mano de Obra (Técnicos)");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, valTpDato(number_format($frmCostos[0], 2, ".", ","),"cero_por_vacio"));

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Costos por incentivo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "-      ");                   //valTpDato(number_format($frmCostos[1], 2, ".", ","),"cero_por_vacio")

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Venta Total de Piezas");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmVentas[1], 2, ".", ","),"cero_por_vacio"));
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Costo Total de Piezas");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, valTpDato(number_format($frmCostos[2], 2, ".", ","),"cero_por_vacio"));

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, valTpDato(number_format($frmCostos[3], 2, ".", ","),"cero_por_vacio"));

$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":"."F".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmVentas[2], 2, ".", ","),"cero_por_vacio"));
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Total con Incentivos:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, valTpDato(number_format($frmCostos[3], 2, ".", ","),"cero_por_vacio"));

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."C".$contFila)->applyFromArray($styleArrayResaltarTotal);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":"."F".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);


//UTILIDADES
$contFila += 3;

$tituloDcto = "UTILIDADES (VENTAS - COSTOS)";
$objPHPExcel->getActiveSheet()->setCellValue("B{$contFila}", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("B{$contFila}:D{$contFila}")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("B{$contFila}:D{$contFila}");

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Concepto         ");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Facturado   ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "%");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":D".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Departamento de Servicio");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmUtilidad[0], 2, ".", ",")));
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, valTpDato(number_format(($frmUtilidad[0]/$frmUtilidad[2])*100, 0, ".", ","))."%");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Departamento de Piezas");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmUtilidad[1], 2, ".", ",")));
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, valTpDato(number_format(($frmUtilidad[1]/$frmUtilidad[2])*100, 0, ".", ","))."%");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmUtilidad[2], 2, ".", ",")));
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "              ");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Utilidad Total Considerando Incentivos:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmUtilidad[3], 2, ".", ",")));

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);


//GASTOS GENERALES
$contFila += 3;

$tituloDcto = "Gastos Generales (Todo gasto referente a la operación general del concesionario)";
$objPHPExcel->getActiveSheet()->setCellValue("B{$contFila}", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("B{$contFila}:G{$contFila}")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("B{$contFila}:G{$contFila}");

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Concepto         ");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Facturado   ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "%");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Concepto         ");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Facturado   ");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "%");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":G".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Salario Gerente de Piezas");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Salario Gerente de Piezas']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Salario Gerente de Piezas']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Salario Gerente de Ventas");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Salario Gerente de Ventas']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Salario Gerente de Ventas']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Salario Jefe de Taller");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Salario Jefe de Taller']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Salario Jefe de Taller']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Salario Gerente de Servicio");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "-      "); //$postVenta['Salario Gerente de Servicio']
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "0.0 %"); //$postVentaPorc['Salario Gerente de Servicio'].

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Salario Fijo de Vendedores");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Salario Fijo de Vendedores']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Salario Fijo de Vendedores']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Salario de Asesores de Servicio");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Salario de Asesores de Servicio']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Salario de Asesores de Servicio']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Salarios Vendedores de Piezas (P&A)");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Salarios Vendedores de Piezas (P&A)']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Salarios Vendedores de Piezas (P&A)']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Empleados de Mantenimiento y Seguridad");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Empleados de Mantenimiento y Seguridad']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Empleados de Mantenimiento y Seguridad']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.))");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Sub Total Salarios:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $subSalarioVent);

$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila.":"."G".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empleados de Mantenimiento y Seguridad ");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Empleados de Mantenimiento y Seguridad']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Empleados de Mantenimiento y Seguridad']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Desempleo (Federal, Estatal)");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Desempleo (Federal, Estatal)']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Desempleo (Federal, Estatal)']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Sub Total Salarios:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $subSalarioPost);

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Seguro de Incapacidad");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Seguro de Incapacidad']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Seguro de Incapacidad']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Desempleo (Federal, Estatal)");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Desempleo (Federal, Estatal)']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Desempleo (Federal, Estatal)']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Beneficios generales empelados");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Beneficios generales empelados']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Beneficios generales empelados']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Seguro de Incapacidad");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Seguro de Incapacidad']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Seguro de Incapacidad']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Seguro Social");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Seguro Social']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Seguro Social']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Beneficios generales empleados");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Beneficios generales empleados']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Beneficios generales empleados']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Renta");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Renta']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Renta']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Seguro Social");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Seguro Social']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Seguro Social']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Property Taxes");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Property Taxes']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Property Taxes']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Renta");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Renta']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Renta']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Teléfono']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Teléfono']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Property Taxes");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Property Taxes']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Property Taxes']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Utilidades");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Utilidades']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Utilidades']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Depreciación");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Depreciación']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Depreciación']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Depreciación");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Depreciación']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Depreciación']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Teléfono']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Teléfono']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Utilidades");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Utilidades']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Utilidades']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Seguros de Propiedad, Transportación, entre otros");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Seguros de Propiedad, Transportación, entre otros']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Seguros de Propiedad, Transportación, entre otros']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Misceláneos");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $ventas['Misceláneos']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $ventasPorc['Misceláneos']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Seguros de Propiedad, Transportación, entre otros");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Seguros de Propiedad, Transportación, entre otros']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Seguros de Propiedad, Transportación, entre otros']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $totalVentas);

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":"."G".$contFila)->applyFromArray($styleArrayResaltarTotal);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Mantenimiento de Equipos, Facilidades y Reparaciones");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Mantenimiento de Equipos, Facilidades y Reparaciones']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Mantenimiento de Equipos, Facilidades y Reparaciones']." %");

$tituloDcto = "GENERALES DEL CONCESIONARIO";
$objPHPExcel->getActiveSheet()->setCellValue("E{$contFila}", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("E{$contFila}:G{$contFila}")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("E{$contFila}:G{$contFila}");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Servicios Externos de Servicio");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Servicios Externos de Servicio']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Servicios Externos de Servicio']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Alta Gerencia");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $nomina['Alta Gerencia']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $nominaPorc['Alta Gerencia']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;


$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Suplidores Externos (si no se considera en piezas o servicios)");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Suplidores Externos (si no se considera en piezas o servicios)']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Suplidores Externos (si no se considera en piezas o servicios)']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Legal & Audit Fees");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $nomina['Legal & Audit Fees']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $nominaPorc['Legal & Audit Fees']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;


$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Misceláneos");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $postVenta['Misceláneos']);
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $postVentaPorc['Misceláneos']." %");

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Sistema, Informática, Asistencia Técnica");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $nomina['Sistema, Informática, Asistencia Técnica']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $nominaPorc['Sistema, Informática, Asistencia Técnica']." %");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $totalPostV);

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Recursos Humanos");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "-      "); //$nomina['Recursos Humanos']
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "0.0 %");  //$nominaPorc['Recursos Humanos'].

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Contabilidad");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "-      "); //$nomina['Contabilidad']
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "0.0 %"); //$nominaPorc['Contabilidad']

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Mercadeo");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "-      "); //$nomina['Mercadeo']
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "0.0 %"); //$nominaPorc['Mercadeo']

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Publicidad");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $nomina['Publicidad']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $nominaPorc['Publicidad']." %");

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Materiales de Oficina ");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $nomina['Materiales de Oficina']);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $nominaPorc['Materiales de Oficina']." %");

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $totalGenConce);

$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("E".$contFila.":"."G".$contFila)->applyFromArray($styleArrayResaltarTotal);

$contFila += 2;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total Gastos Generales:");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $totalGasto);

$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":E".$contFila);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":E".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."G".$contFila)->applyFromArray($styleArrayResaltarTotal);



//INDICADOR DE DESEMPEÑO
$contFila += 3;

$tituloDcto = "INDICADOR DE DESEMPEÑO";
$objPHPExcel->getActiveSheet()->setCellValue("B{$contFila}", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("B{$contFila}:D{$contFila}")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("B{$contFila}:D{$contFila}");

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Concepto         ");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Facturado   ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "%");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":D".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total Utilidad Posventas");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmIndicadores[0], 2, ".", ","),"cero_por_vacio"));
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "              ");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total Utilidad Posventa Considerando Incentivos");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmIndicadores[1], 2, ".", ","),"cero_por_vacio"));
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "              ");

$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Total Gastos Generales");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, valTpDato(number_format($frmIndicadores[2], 2, ".", ","),"cero_por_vacio"));
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "              ");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Absorción Financiera de Posventa:");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, valTpDato(number_format($frmIndicadores[3], 0, ".", ","),"cero_por_vacio")." %");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);

$contFila++;

$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Absorción Financiera Considerando Comision Mano de Obra:");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, valTpDato(number_format($frmIndicadores[4], 0, ".", ","),"cero_por_vacio")." %");

$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->applyFromArray($styleArrayColumna);
$objPHPExcel->getActiveSheet()->getStyle("B".$contFila.":"."D".$contFila)->applyFromArray($styleArrayResaltarTotal);



//CONFIGURACIONES DEL EXCEL
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("B".$primero.":F".$ultimo);
$objPHPExcel->getActiveSheet()->removeAutoFilter("D".$primero.":D".$ultimo);

for ($col = "A"; $col != "F"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "H", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Absorción Financiera";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:H7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "B&uacute;squeda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:L9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);

$objPHPExcel->getProperties()->setCreator("SIPRE ".cVERSION);
$objPHPExcel->getProperties()->setTitle($tituloDcto);

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
header('Cache-Control: max-age=0');
 
//Creamos el Archivo .xlsx
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
ob_end_flush();
?>