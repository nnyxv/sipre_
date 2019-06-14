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

function claveFiltroEmpleado(){

	//AVERIGUAR VENTA O POSTVENTA
	$queryUsuario = sprintf("SELECT id_empleado,
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
    clave_filtro,
		(CASE clave_filtro
			  WHEN 1 THEN 'Ventas'
              WHEN 2 THEN 'Ventas'
			  WHEN 4 THEN 'Postventa'
              WHEN 5 THEN 'Postventa'
              WHEN 6 THEN 'Postventa'
              WHEN 7 THEN 'Postventa'
              WHEN 8 THEN 'Postventa'
              WHEN 26 THEN 'Postventa'
              WHEN 400 THEN 'Postventa'
		END) AS tipo

	FROM pg_empleado
		INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
	WHERE id_empleado = %s ",
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"));

	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryUsuario);
	$row = mysql_fetch_array($rsUsuario);

	return array(true, $rowClave['clave_filtro'], $row['tipo']);

}

$objPHPExcel = new PHPExcel();

$valCadBusq = explode("|", $_GET['valBusq']);
$idEmpresa = $valCadBusq[0];

//Array ( [0] => 1 [1] => 19 [2] => 15 [3] => 04-10-2016 [4] => 06-10-2016 [5] => );


//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seg.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	
	$arrayClave = claveFiltroEmpleado();
	if($arrayClave[0] == true){
		if($arrayClave[1] == 1 || $arrayClave[1] == 2){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(seg.id_empleado_creador = %s OR id_empleado_vendedor = %s 
					OR (SELECT jefe_equipo FROM crm_equipo equipo WHERE activo = %s AND jefe_equipo = %s LIMIT %s))",
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato(1,"int"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato(1,"int"));
		}
	}else{
		$objResponse->alert($arrayClave[1]);
	}
	
	if($valCadBusq[3] != "-1" && $valCadBusq[3] != "" && $valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1]) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seg_dia.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("act.id_actividad = %s",
				valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("CONCAT_WS(' ',cliente.nombre, cliente.apellido) LIKE %s
		OR ((SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
					WHERE empleado.id_empleado = seg_dia. id_empleado_vendedor) IN (
								SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
										WHERE empleado.id_empleado = seg_dia.id_empleado_vendedor AND 
											CONCAT_WS(' ',nombre_empleado, empleado.apellido) LIKE %s))
		OR ((SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
				INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
			WHERE prospecto_vehiculo.id_cliente = seg.id_cliente LIMIT 1) LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}

	
	
	$query = sprintf("	SELECT DISTINCT
							CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
							CASE
								WHEN cliente.tipo_cuenta_cliente = 1 THEN 'Prospecto'
								ELSE 'Cliente'
							END AS tipo_cliente,
							cliente.ci,
							cliente.id,
							CONCAT_WS(' ',emp.nombre_empleado,emp.apellido) AS nombre_empleado
						FROM
							crm_seguimiento seg
						INNER JOIN crm_seguimiento_diario AS seg_dia ON seg_dia.id_seguimiento = seg.id_seguimiento
						INNER JOIN crm_actividad_seguimiento AS act_seg ON seg.id_seguimiento = act_seg.id_seguimiento
						INNER JOIN crm_actividad AS act ON act_seg.id_actividad = act.id_actividad
						INNER JOIN cj_cc_cliente AS cliente ON seg.id_cliente = cliente.id
						INNER JOIN pg_empleado AS emp ON seg_dia.id_empleado_vendedor = emp.id_empleado %s",
					 $sqlBusq);


	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s ", $query, $sqlOrd);
		
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
	
		if ($totalRows > 0 && $row['nombre_cliente'] != '') {
			
			
		$query2 = sprintf("SELECT DISTINCT
							seg.id_seguimiento,
							act.nombre_actividad,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
							cliente.ci,
							act_seg.id_actividad_seguimiento,
							act_eje.fecha_asignacion,
							act_eje.fecha_creacion,
							act.tipo,
							CASE
								WHEN act.activo = 1 THEN 'Activo'
								ELSE 'Inactivo'
							END AS estatus,
							CONCAT_WS(' ', emp.nombre_empleado, emp.apellido) AS nombre_empleado
						FROM crm_actividad_seguimiento AS act_seg
						INNER JOIN crm_seguimiento AS seg ON act_seg.id_seguimiento = seg.id_seguimiento
						INNER JOIN crm_actividad AS act ON act_seg.id_actividad = act.id_actividad
						INNER JOIN crm_seguimiento_diario AS seg_dia ON seg_dia.id_seguimiento = seg.id_seguimiento
						INNER JOIN cj_cc_cliente AS cliente ON seg.id_cliente = cliente.id
						INNER JOIN crm_actividades_ejecucion AS act_eje ON act_eje.id_actividad = act.id_actividad AND act_eje.id = cliente.id AND act_eje.id_actividad_seguimiento = act_seg.id_actividad_seguimiento
						INNER JOIN pg_empleado AS emp ON seg_dia.id_empleado_vendedor = emp.id_empleado 
						WHERE cliente.id = %s ",
						utf8_encode($row['id']));
				
			$rsLimitDet = mysql_query($query2);
			if (!$rsLimitDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryDet);
			$totalRowsDet = mysql_num_rows($rsLimitDet);
			
			if($totalRowsDet > 0){
				
				$contFila++;
				
				$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nombre Cliente:");
				$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_cliente']));
				$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
				$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Vendedor:");
				$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['nombre_empleado']));
				$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->applyFromArray($styleArrayCampo);
				
				$contFila++;
				
				$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "C.I.:");
				$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['ci']));
				$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
				$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Tipo de Cliente:");
				$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, utf8_encode($row['tipo_cliente']));
				$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->applyFromArray($styleArrayCampo);
				
				$contFila++;
				
				$primero = $contFila;				
				$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nombre de Actividad");
				$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Fecha de Asignacion");
				$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha de Creacion");
				$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Tipo de Venta");
				$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Estatus");
				
				$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($styleArrayColumna);
			}
		}
		
		$arrayTotal = NULL;
		$contFila2 = 0;
		
		while ($rowDet = mysql_fetch_assoc($rsLimitDet)) {
			$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
			$contFila++;
			$contFila2++;
			
			$date = new DateTime($rowAct['fecha_asignacion']);
			$fecha_asig =  $date->format('d-m-Y');
				
			$date = new DateTime($rowAct['fecha_creacion']);
			$fecha_creacion =  $date->format('d-m-Y');
			
			$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, utf8_encode($rowDet['nombre_actividad']));
			$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($fecha_asig));
			$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, utf8_encode($fecha_creacion));
			$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $rowDet['tipo']);
			$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($rowDet['estatus']));
		
			$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":E".$contFila)->applyFromArray($clase);
		
			$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
		
			
		}
		
			$contFila++;
			$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":E".$contFila);
		
	}

for ($col = "A"; $col != "E"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "G", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Actividades de los Prospectos";
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