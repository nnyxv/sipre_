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

$query = sprintf("SELECT
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
				WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nobre_vendedor
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
$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);


$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "  Id  ");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Nombre Cliente  ");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Seguimiento  ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Vendedor  ");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Fecha de Asignacion  ");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Estatus  ");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "         ");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "         ");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":F".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
		
	if($row['nobre_vendedor'] == ''){
		$seguimiento = 'NO';
		$row['nobre_vendedor'] = 'SIN ASIGNAR';
	} else{
		$seguimiento = 'SI';
	}
	$date = new DateTime($row['fecha_registro']);
	$fecha_reg =  $date->format('d-m-Y');
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $row['id_seguimiento']);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $seguimiento);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['nobre_vendedor']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $fecha_reg);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['nombre_posibilidad_cierre']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['nombre_posibilidad_cierre']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "         ");
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "         ");
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":F".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":F".$ultimo);

for ($col = "A"; $col != "F"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "H", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Clientes";
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