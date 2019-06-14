<?php 
//El informe de errores 
/*error_reporting (E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);*/
// Include PHPExcel 
include_once("../../connections/conex.php");
include_once("../../inc_sesion.php");
require_once('../../clases/phpExcel_1.7.8/Classes/PHPExcel.php');

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
							 ->setLastModifiedBy("Maarten Balliauw")
							 ->setTitle("PHPExcel Test Document")
							 ->setSubject("PHPExcel Test Document")
							 ->setDescription("Test document for PHPExcel, generated using PHP classes.")
							 ->setKeywords("office PHPExcel php")
							 ->setCategory("Test result file");
							 
/*****DEFINE LOS ESTILO PARA EL EXCEL*****/
//PARA COLOCAR EL BORDER INTERNO DE LA TABLA
$styleArray = array('borders' => array('inside'=> array( 
					'style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '555657'))));
					
//PARA COLOCAR EL BORDER EXTERIOR DE LA TABLA
$styleArray2 = array('borders' => array('outline'=> array(
					'style' => PHPExcel_Style_Border::BORDER_THICK,'color' => array('argb' => '2D2E30'))));
//
$styleArrayEmp = array('font' => 
							array('bold' => true),
						'alignment' => array(
											'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT));
					
//PARA DARLE ESTILO AL TITULO DE LA HOJA EXCEL
$styleArrayTitulo = array ('font' =>
								array('bold' => true,
									'size' => 12 ,
									'name' => 'Verdana',
									'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE),
						 	'alignment' =>array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
						 
//PAR EL ESTILO DEL ENCABEZADO DE LA TABLA						 
$styleArrayHead = array('font' =>  array('bold' => true,
										 'color' => array('rgb' => 'FFFFFF')),
						'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER), 
						'fill' =>      array('type' => PHPExcel_Style_Fill::FILL_SOLID,
											 'color' => array('rgb' => 'd14b02')));
											 
//PARA ALINER EL TEXTO A LA DERECHAR CON LA LETRA EN NEGRITA	
$styleArrayTex = array('font' => array('bold' => true),
					   'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT));

//PARA ALINER EL TEXTO AL CENTRO CON LA LETRA EN NEGRITA	
$styleArrayTexCenter = array('font' => array('bold' => true),
					   'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

//PARA CENTAR EL TEXTO
$styleArrayTex2 = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

//RELLENA DE COLOR LAS CELDA INTERNA 
$styleArrayRelleno = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
											'rotation' => 90,
											'startcolor' => array('argb' => 'ffede2'),
											'endcolor' => array('argb' => 'FFFFFFFF')));
											
//PARA RELLENEAR LA CELDA DEL TOTAL 											
$styleArrayTotales = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
										   'rotation' => 90,
										   'startcolor' => array('argb' => 'ECEDEF'),
											'endcolor' => array('argb' => 'FFFFFFFF')),
							'font' => array('bold' => true,
											'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE));
/*****FIN DEFINE LOS ESTILO PARA EL EXCEL*****/
		
	$nombreArchivo = sprintf("Historico_Contratos_Alquiler_%s.xlsx",date("d/m/Y"));
	$titulo = "Historico Contratos de Alquiler";
	
	$fecha = date("d/m/Y");
	$hora = date("h:i. A");

	//CONSULTO LOS DATOS DE LA EMPRESA
$SqlEmp = sprintf("SELECT id_empresa, nombre_empresa,logo_empresa,rif,web  FROM pg_empresa 
					WHERE id_empresa = %s",
				   $_SESSION['idEmpresaUsuarioSysGts']);
$queryEmp = mysql_query($SqlEmp);  
if(!$queryEmp){ die("Error: ".mysql_error().__LINE__); } 
$rowsEmp = mysql_fetch_array($queryEmp);

	$objDrawing = new PHPExcel_Worksheet_Drawing();
	$objDrawing->setName('Logo');
	$objDrawing->setDescription('Logo');
	$objDrawing->setPath("../../".$rowsEmp['logo_empresa']);
	$objDrawing->setHeight(80);
	$objDrawing->setWidth(100);
	$objDrawing->setCoordinates('A1'); 
	$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
	
	//DATOS DE LA EMPRESA
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getActiveSheet()->setCellValue('C1',$rowsEmp['nombre_empresa']);
	$objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArrayEmp);
	$objPHPExcel->getActiveSheet()->setCellValue('C2', $rowsEmp['rif']);
	$objPHPExcel->getActiveSheet()->getStyle('C2')->applyFromArray($styleArrayEmp);
	$objPHPExcel->getActiveSheet()->setCellValue('C3',$rowsEmp['web']);
	$objPHPExcel->getActiveSheet()->mergeCells('C3:E3');
	$objPHPExcel->getActiveSheet()->getCell('C3')->getHyperlink()->setUrl("http://".$rowsEmp['web']);
	$objPHPExcel->getActiveSheet()->getStyle('C3')->applyFromArray($styleArrayEmp);
	
//DATOS DEL EMPLEADO QUE GENERA EL INFORME	
$sqlUser = sprintf("SELECT id_usuario, nombre_usuario, pg_empleado.id_empleado, 
						CONCAT_WS(' ',nombre_empleado, apellido) AS nombre_apellido_empleado
					  FROM pg_usuario
						INNER JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado 
					  WHERE id_usuario = %s;",
					$_SESSION['idUsuarioSysGts']);
	$queryUser = mysql_query($sqlUser);  
		if(!$queryUser){ die("Error: ".mysql_error().__LINE__); } 
	$rowsUser = mysql_fetch_array($queryUser);
		
	$objPHPExcel->getActiveSheet()->setCellValue('N1', $fecha);
	$objPHPExcel->getActiveSheet()->getStyle('N1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->setCellValue('N2', $hora);
	$objPHPExcel->getActiveSheet()->getStyle('N2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->setCellValue('L3', 'Genereado por:');
	$objPHPExcel->getActiveSheet()->getStyle('L3')->applyFromArray($styleArrayTex);
	$objPHPExcel->getActiveSheet()->setCellValue('M3', $rowsUser['nombre_apellido_empleado']);
	$objPHPExcel->getActiveSheet()->mergeCells('M3:N3');

	//TITULO DE LA HOJA
	$objPHPExcel->getActiveSheet()->setCellValue('B5',$titulo);
	$objPHPExcel->getActiveSheet()->mergeCells('B5:N5');
	$objPHPExcel->getActiveSheet()->getStyle('B5:N5')->applyFromArray($styleArrayTitulo);
		
$valCadBusq = explode("|", $_GET['valBusq']); 

if ($valCadBusq['1'] != "" && $valCadBusq['1'] != NULL && $valCadBusq['2'] != "" && $valCadBusq['2'] != NULL) {
	$filaInicio = 9;
	$objPHPExcel->getActiveSheet()->setCellValue('B7', "Contratos de alquiler desde: ".$valCadBusq['1']." Hasta ".$valCadBusq['2']);
	$objPHPExcel->getActiveSheet()->getStyle('B7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->mergeCells('B7:N7');
} else {
	$filaInicio = 7;
}

	//Datos de la cabecera del cuadro
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('B'.$filaInicio, 'Empresa')
		->setCellValue('C'.$filaInicio, 'Fecha Creado')
		->setCellValue('D'.$filaInicio, 'Nro. Contrato')
		->setCellValue('E'.$filaInicio, 'Nro. Presupuesto')
		->setCellValue('F'.$filaInicio, 'Tipo Contrato')
		->setCellValue('G'.$filaInicio, 'Cliente')
		->setCellValue('H'.$filaInicio, 'Placa')
		->setCellValue('I'.$filaInicio, 'Serial Carroceria')
		->setCellValue('J'.$filaInicio, 'Fecha Salida')
		->setCellValue('K'.$filaInicio, 'Fecha Entrada')
		->setCellValue('L'.$filaInicio, 'Días Contrato')
		->setCellValue('M'.$filaInicio, 'Tipo de Pago')
		->setCellValue('N'.$filaInicio, 'Total Contrato	');
	$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':N'.$filaInicio)->applyFromArray($styleArrayHead);

	//CONSULTA LOS CONTRATOS 
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(contrato.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = contrato.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(contrato.fecha_creacion) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("contrato.id_empleado_creador = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("contrato.estatus_contrato_venta = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(contrato.numero_contrato_venta LIKE %s		
		OR presupuesto.numero_presupuesto_venta LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		contrato.id_contrato_venta,
		contrato.numero_contrato_venta,
		contrato.id_tipo_contrato,
		contrato.id_empresa,
		contrato.id_cliente,
		contrato.observacion,
		contrato.id_presupuesto_venta,
		contrato.id_unidad_fisica,
		contrato.condicion_pago,
		contrato.estatus_contrato_venta,
		contrato.fecha_creacion,
		contrato.fecha_salida,
		contrato.fecha_entrada,
		contrato.dias_contrato,
		presupuesto.numero_presupuesto_venta,
		tipo_contrato.nombre_tipo_contrato,
		empleado.nombre_empleado,
		unidad.placa,
		unidad.serial_carroceria,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,		
		(contrato.subtotal - contrato.subtotal_descuento) AS total_neto,
		contrato.total_contrato AS total,
		
		(SELECT SUM(al_contrato_venta_iva.subtotal_iva) FROM al_contrato_venta_iva
		WHERE al_contrato_venta_iva.id_contrato_venta = contrato.id_contrato_venta) AS total_iva,
				
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), 
		vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
		FROM al_contrato_venta contrato
		INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)
		INNER JOIN cj_cc_cliente cliente ON (contrato.id_cliente = cliente.id)
		INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
		LEFT JOIN al_presupuesto_venta presupuesto ON (contrato.id_presupuesto_venta = presupuesto.id_presupuesto_venta) %s", $sqlBusq);

	$rs = mysql_query($query);  
	if(!$rs){ die("Error: ".mysql_error().__LINE__.$query); } 
	$totalRows = mysql_num_rows($rs);
	$count = $filaInicio;
	$totalFilas = ($count + $totalRows);
	while($rows = mysql_fetch_array($rs)){
			$count++;
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('B'.$count, $rows['nombre_empresa'])
					->setCellValue('C'.$count, date(spanDateFormat." h:m a", strtotime($rows['fecha_creacion'])))
					->setCellValue('D'.$count, $rows['numero_contrato_venta'])
					->setCellValue('E'.$count, $rows['numero_presupuesto_venta'])
					->setCellValue('F'.$count, utf8_encode($rows['nombre_tipo_contrato']))
					->setCellValue('G'.$count, utf8_encode($rows['nombre_cliente']))
					->setCellValue('H'.$count, utf8_encode($rows['placa']))
					->setCellValue('I'.$count, utf8_encode($rows['serial_carroceria']))
					->setCellValue('J'.$count, date(spanDateFormat." h:i a", strtotime($rows['fecha_salida'])))
					->setCellValue('K'.$count, date(spanDateFormat." h:i a", strtotime($rows['fecha_entrada'])))
					->setCellValue('L'.$count, $rows['dias_contrato'])
					->setCellValue('M'.$count, ($rows['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO")
					->setCellValue('N'.$count, number_format($rows['total'], 2, ".", ","));
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);  

		//PARA EL ESTILO DEL ESTATUDO DEL CONTRATO
		$color = ($rows['condicion_pago'] == 0) ? "ffffcc": "e6ffe6";
		$objPHPExcel->getActiveSheet()->getStyle('M'.$count.':M'.$count)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle('M'.$count.':M'.$count)->getFill()->getStartColor()->setARGB($color);
		$objPHPExcel->getActiveSheet()->getStyle('M'.$count.':M'.$count)->applyFromArray($styleArrayTexCenter);
		
		//EL ESTILO GENRAL DEL CUADRO
		$objPHPExcel->getActiveSheet()->getStyle('B'.$count.':L'.$count)->applyFromArray($styleArrayTex2);
		$objPHPExcel->getActiveSheet()->getStyle('N'.$count.':N'.$count)->applyFromArray($styleArrayTex);
		(fmod($count, 2) == 0) ? "" : $objPHPExcel->getActiveSheet()->getStyle('B'.$count.':L'.$count)->applyFromArray($styleArrayRelleno);
		(fmod($count, 2) == 0) ? "" : $objPHPExcel->getActiveSheet()->getStyle('N'.$count.':N'.$count)->applyFromArray($styleArrayRelleno);
	
	}

//LE COLOCA EL BORDER A TODA LA TABLA
$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':N'.$totalFilas)->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':N'.$totalFilas)->applyFromArray($styleArray2);

//Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle($titulo); //"'".$tituloPenstana."'"co
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client's web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename='.$nombreArchivo);
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>