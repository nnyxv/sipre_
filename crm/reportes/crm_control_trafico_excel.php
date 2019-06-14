<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
require_once("../../connections/conex.php");
session_start();

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

include("clase_excel.php");

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];

//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if($valCadBusq[2] != "-1" && $valCadBusq[2] != "" && $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1]) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento_diario.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("observacion_seguimiento LIKE %s
		OR CONCAT_WS(' ',cliente.nombre, cliente.apellido) LIKE %s
		OR ((SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
					WHERE empleado.id_empleado = seguimiento_diario. id_empleado_vendedor) IN (
								SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
										WHERE empleado.id_empleado = seguimiento_diario.id_empleado_vendedor AND 
											CONCAT_WS(' ',nombre_empleado, empleado.apellido) LIKE %s))
		OR ((SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
				INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
			WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		seguimiento_diario.id_empleado_vendedor,
		(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado 
				WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nombre_vendedor,
		(SELECT pg_empleado.cedula FROM pg_empleado 
				WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS ci_vendedor
	FROM crm_seguimiento seguimiento 
		INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
		INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
		INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id   
		LEFT JOIN crm_posibilidad_cierre posibilidad_cierre ON posibilidad_cierre.id_posibilidad_cierre = perfil_prospecto.id_posibilidad_cierre
		LEFT JOIN crm_equipo equipo ON equipo.id_equipo = seguimiento_diario.id_equipo
		INNER JOIN grupositems ON grupositems.idItem = (SELECT id_medio FROM an_prospecto_vehiculo
															INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
														WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
		INNER JOIN pg_empleado empleado ON empleado.id_empleado = seguimiento.id_empleado_creador
	%s",
	 $sqlBusq);
	
	$rsLimit = mysql_query($query);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$totalRows = mysql_num_rows($rsLimit);
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
	
		if ($totalRows > 0 && $row['nombre_vendedor'] != '') {
			
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("seguimiento_diario.id_empleado_vendedor = %s",
					valTpDato($row['id_empleado_vendedor'], "int"));
			
			$queryDet = sprintf("SELECT
				seguimiento.id_seguimiento, seguimiento.id_cliente, seguimiento.id_empleado_creador, seguimiento.id_empleado_actualiza, seguimiento.id_empresa, seguimiento.observacion_seguimiento,
				CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
				seguimiento_diario.id_seguimiento_diario, seguimiento_diario.id_equipo, equipo.jefe_equipo, seguimiento_diario.id_empleado_vendedor, fecha_registro, fecha_asignacion_vendedor,
				perfil_prospecto.id_perfil_prospecto, perfil_prospecto.id_posibilidad_cierre, fechaProximaEntrevista,
				posibilidad_cierre.nombre_posibilidad_cierre, img_posibilidad_cierre,
				grupositems.item,
				(SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
							INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
						WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS nom_uni_bas,
		
					(SELECT precio_unidad_basica  FROM an_prospecto_vehiculo prospecto_vehiculo
							INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
						WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS precio_unidad_basica,
		
					IFNULL((SELECT COUNT(an_unidad_fisica.id_uni_bas) FROM an_unidad_fisica
						WHERE  an_unidad_fisica.id_uni_bas = (SELECT an_uni_bas.id_uni_bas FROM an_prospecto_vehiculo
																	INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
							AND estado_venta = 'DISPONIBLE'
						GROUP BY an_unidad_fisica.id_uni_bas ), 0) AS disponible_unidad_fisica,
		
				IFNULL((SELECT uni_bas.nom_uni_bas
							FROM an_tradein tradein
						INNER JOIN cj_cc_anticipo cxc_ant ON tradein.id_anticipo = cxc_ant.idAnticipo
						INNER JOIN an_unidad_fisica uni_fis ON tradein.id_unidad_fisica = uni_fis.id_unidad_fisica
						INNER JOIN an_uni_bas uni_bas ON uni_fis.id_uni_bas = uni_bas.id_uni_bas
							WHERE cxc_ant.idCliente = seguimiento.id_cliente), '-') AS tradeIn,
		
				CONCAT_WS(' ',empleado.nombre_empleado, empleado.apellido) AS nombre_usuario_creador,
				(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado
						WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nombre_vendedor,
				(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado
					WHERE pg_empleado.id_empleado = seguimiento.id_empleado_creador) AS nombre_creador
			FROM crm_seguimiento seguimiento
				INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
				INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
				INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id
				LEFT JOIN crm_posibilidad_cierre posibilidad_cierre ON posibilidad_cierre.id_posibilidad_cierre = perfil_prospecto.id_posibilidad_cierre
				LEFT JOIN crm_equipo equipo ON equipo.id_equipo = seguimiento_diario.id_equipo
				INNER JOIN grupositems ON grupositems.idItem = (SELECT id_medio FROM an_prospecto_vehiculo
																	INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
				INNER JOIN pg_empleado empleado ON empleado.id_empleado = seguimiento.id_empleado_creador
			%s %s",
					$sqlBusq, $sqlBusq2);
				
			$rsLimitDet = mysql_query($queryDet);
			if (!$rsLimitDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryDet);
			$totalRowsDet = mysql_num_rows($rsLimitDet);
			
			if($totalRowsDet > 0){
				$contFila++;
				
				$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Vendedor:");
				$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_vendedor']));
				
				$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
				
				$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":E".$contFila);
				
				$contFila++;
				$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "C.I.:");
				$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['ci_vendedor']));
				
				$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
				
				$objPHPExcel->getActiveSheet()->mergeCells("B".$contFila.":E".$contFila);
				
				$contFila++;
				$primero = $contFila;
				
				$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "  Id  ");
				$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Nombre Cliente");
				$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Seguimiento Creado Por  ");
				$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Fecha Asignacion  ");
				$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Estatus  ");
				
				$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($styleArrayColumna);
			}
		}
		
		$arrayTotal = NULL;
		$contFila2 = 0;
		
		while ($rowDet = mysql_fetch_assoc($rsLimitDet)) {
			$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
			$contFila++;
			$contFila2++;
		
			$date = new DateTime($rowDet['fecha_registro']);
			$fecha_reg =  $date->format('d-m-Y');
			
			$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $rowDet['id_seguimiento']);
			$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($rowDet['nombre_cliente']));
			$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($rowDet['nombre_vendedor']));
			$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $fecha_reg);
			$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($rowDet['nombre_posibilidad_cierre']));
		
			$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($clase);
		
			$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		}
		
		$ultimo = $contFila;
		
		if ($totalRowsDet > 0) {
			$contFila++;
			$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total de Clientes:");
			$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $totalRowsDet);
		
			$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
			$objPHPExcel->getActiveSheet()->getStyle("C".$contFila.":"."E".$contFila)->applyFromArray($styleArrayResaltarTotal);
		
			$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
			$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":B".$contFila);
		
			$contFila++;
			$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
		}
	}

for ($col = "A"; $col != "E"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "G", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Vendedores";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:G7");


//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE ".cVERSION);
//$objPHPExcel->getProperties()->setLastModifiedBy("autor");
$objPHPExcel->getProperties()->setTitle($tituloDcto);
//$objPHPExcel->getProperties()->setSubject("Asunto");
//$objPHPExcel->getProperties()->setDescription("Descripcion");

// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
header('Cache-Control: max-age=0');
 
//Creamos el Archivo .xlsx
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>