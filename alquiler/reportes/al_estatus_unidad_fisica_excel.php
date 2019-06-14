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
						 
//PARA EL ESTILO DEL ENCABEZADO DE LA TABLA						 
$styleArrayHead = array('font' =>  array('bold' => true,
										 'color' => array('rgb' => 'FFFFFF')),
						'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER), 
						'fill' =>      array('type' => PHPExcel_Style_Fill::FILL_SOLID,
											 'color' => array('rgb' => 'd14b02')));
$styleArraySegTitulo = array('font' =>  array('bold' => true,
										 'color' => array('rgb' => 'FFFFFF')),
						'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT), 
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
							'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT), 
							'font' => array('bold' => true,
											'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE));
/*****FIN DEFINE LOS ESTILO PARA EL EXCEL*****/
		
$nombreArchivo = sprintf("Estatus_Unidades_Fisicas_%s.xlsx",date("d/m/Y"));
$titulo = "Estatus de las Unidades Fisicas";

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

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO', 'ACTIVO FIJO')");
	
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
		valTpDato($valCadBusq[3], "int"));
}
	
if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
}
	
if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[5])."'", "defined", "'".str_replace(",","','",$valCadBusq[5])."'"));
}
	
if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_estado_adicional IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[8])."'", "defined", "'".str_replace(",","','",$valCadBusq[8])."'"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.placa LIKE %s
	OR numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

//CONSULTA LOS ESTATUS DE LA TABLA
$sqlEstatus =  sprintf("SELECT DISTINCT uni_fis.id_estado_adicional, estado_adicional.nombre_estado
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_unidad_estado_adicional estado_adicional ON (uni_fis.id_estado_adicional = estado_adicional.id_estado_adicional)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		%s
	ORDER BY uni_fis.id_estado_adicional DESC ",$sqlBusq);
$rsEstatus = mysql_query($sqlEstatus);
if (!$rsEstatus) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlEstatus);

$numEstatus = mysql_num_rows($rsEstatus);
$countRs = 1;

while($rowEstatus = mysql_fetch_assoc($rsEstatus)){
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_fis.id_estado_adicional = %s",
		valTpDato($rowEstatus['id_estado_adicional'], "int"));
	
	if($countRs != 1){
		$filaInicio = $filaInicio + 2;
		$filaInicioMarco = $filaInicio;
	}else{ 
		$filaInicio = 7;
		$filaInicioMarco = 7; 
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue('B'.$filaInicio,"Estatus: ".$rowEstatus['nombre_estado']);
	$objPHPExcel->getActiveSheet()->mergeCells('B'.$filaInicio.':M'.$filaInicio);
	$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':M'.$filaInicio)->applyFromArray($styleArraySegTitulo);
	$filaInicio ++ ;
	//Datos de la cabecera del cuadro
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('B'.$filaInicio, 'Nro Unidad')
		->setCellValue('C'.$filaInicio, $spanSerialCarroceria)
		->setCellValue('D'.$filaInicio, $spanSerialMotor)
		->setCellValue('E'.$filaInicio, $spanPlaca)
		->setCellValue('F'.$filaInicio, 'Marca')
		->setCellValue('G'.$filaInicio, 'Modelo')
		->setCellValue('H'.$filaInicio, 'Clase')
		->setCellValue('I'.$filaInicio, 'Uso')
		->setCellValue('J'.$filaInicio, 'Año')
		->setCellValue('K'.$filaInicio, 'Última Fecha Alquiler')
		->setCellValue('L'.$filaInicio, 'Días Alquilado')
		->setCellValue('M'.$filaInicio, 'Días Sin Alquiler');
	$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':M'.$filaInicio)->applyFromArray($styleArrayHead);

	$countRs++;
	
	//DATOS DE LA UNIDAD FISICA
	$sqlUniFisica = sprintf("SELECT uni_fis.id_unidad_fisica, vw_iv_modelo.nom_marca, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version, vw_iv_modelo.nom_ano, uni_fis.id_clase, clase.nom_clase, uni_fis.serial_motor, uni_fis.serial_carroceria, uni_fis.placa, uni_fis.estado_venta, alm.nom_almacen, estado_adicional.nombre_estado,
	
		(SELECT MAX(DATE(contrato.fecha_salida)) 
			FROM al_contrato_venta contrato 
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS ultima_fecha_alquilado,
		
		(SELECT contrato.dias_contrato
			FROM al_contrato_venta contrato
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND contrato.estatus_contrato_venta = 1
			ORDER BY contrato.id_contrato_venta DESC LIMIT 1) AS dias_alquilado,
			
		(SELECT SUM(IF(contrato.estatus_contrato_venta = 1, contrato.dias_contrato, contrato.dias_total)) 
			FROM al_contrato_venta contrato
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS total_dias_alquilado,
		
		(IF((SELECT COUNT(contrato.id_contrato_venta) 
				FROM al_contrato_venta contrato 
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica 
				AND contrato.estatus_contrato_venta = 1) = 0,					
			(SELECT ABS(DATEDIFF(CURDATE(), DATE(contrato.fecha_final)))
				FROM al_contrato_venta contrato
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
				ORDER BY contrato.id_contrato_venta DESC LIMIT 1),
			0)) AS dias_sin_alquilar
				
	FROM an_unidad_fisica uni_fis 
		INNER JOIN an_unidad_estado_adicional estado_adicional ON (uni_fis.id_estado_adicional = estado_adicional.id_estado_adicional)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)		
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase) %s %s", $sqlBusq, $sqlBusq2);

	$rsUniFisica = mysql_query($sqlUniFisica);
	if (!$rsUniFisica) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlUniFisica);
	
	$totalUniFisica = mysql_num_rows($rsUniFisica);
	$count = 0;
	$arrayTotalUnidad = array();
	$totalDiasAlquilado = array();
	$totalTotalDiasAlquilado = array();
	$totalDiasSinAlquilar = array();
	
	while($rowUniFisica = mysql_fetch_array($rsUniFisica)){
		$filaInicio ++;
		$count ++;
		$arrayTotalUnidad[] = $rowUniFisica['id_unidad_fisica'];
		$totalDiasAlquilado[] = $rowUniFisica["dias_alquilado"];
		$totalTotalDiasAlquilado[] = $rowUniFisica["total_dias_alquilado"];		
		$totalDiasSinAlquilar[] = $rowUniFisica["dias_sin_alquilar"];
		
		$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('B'.$filaInicio, $rowUniFisica['id_unidad_fisica'])
			->setCellValue('C'.$filaInicio, $rowUniFisica['serial_carroceria'])
			->setCellValue('D'.$filaInicio, $rowUniFisica['serial_motor'])
			->setCellValue('E'.$filaInicio, $rowUniFisica['placa'])
			->setCellValue('F'.$filaInicio, utf8_encode($rowUniFisica['nom_marca']))
			->setCellValue('G'.$filaInicio, utf8_encode($rowUniFisica['nom_modelo']))
			->setCellValue('H'.$filaInicio, utf8_encode($rowUniFisica['nom_clase']))
			->setCellValue('I'.$filaInicio, utf8_encode($rowUniFisica['nom_almacen']))
			->setCellValue('J'.$filaInicio, $rowUniFisica['nom_ano'])
			->setCellValue('K'.$filaInicio, ($rowUniFisica["ultima_fecha_alquilado"] != "") ? date(spanDateFormat, strtotime($rowUniFisica["ultima_fecha_alquilado"])) : "-")
			->setCellValue('L'.$filaInicio, intval($rowUniFisica["dias_alquilado"])." (".intval($rowUniFisica["total_dias_alquilado"]).")")
			->setCellValue('M'.$filaInicio, intval($rowUniFisica['dias_sin_alquilar']));
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

		//EL ESTILO INTERNO DEL CUADRO
		$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':M'.$filaInicio)->applyFromArray($styleArrayTex2);
		(fmod($filaInicio, 2) == 0) ? "" : $objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':M'.$filaInicio)->applyFromArray($styleArrayRelleno);
		
		//FILA DE TOTALES
		if($count == $totalUniFisica){
			$filaInicio ++;
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$filaInicio,"Total Unidad Física ".$rowEstatus['estatus'].": ");
			$objPHPExcel->getActiveSheet()->mergeCells('B'.$filaInicio.':D'.$filaInicio);
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$filaInicio,count($arrayTotalUnidad));
			$objPHPExcel->getActiveSheet()->mergeCells('E'.$filaInicio.':E'.$filaInicio);
			$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicio.':E'.$filaInicio)->applyFromArray($styleArrayTotales);
			
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$filaInicio,"Total Días: ");
			$objPHPExcel->getActiveSheet()->mergeCells('J'.$filaInicio.':K'.$filaInicio);
			$objPHPExcel->getActiveSheet()->setCellValue('L'.$filaInicio,array_sum($totalDiasAlquilado)." (".array_sum($totalTotalDiasAlquilado).")");
			$objPHPExcel->getActiveSheet()->setCellValue('M'.$filaInicio,array_sum($totalDiasSinAlquilar));
			
			$objPHPExcel->getActiveSheet()->getStyle('J'.$filaInicio.':M'.$filaInicio)->applyFromArray($styleArrayTotales);
		}
	}
	$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicioMarco .':M'.($filaInicio -1))->applyFromArray($styleArray);
	$objPHPExcel->getActiveSheet()->getStyle('B'.$filaInicioMarco .':M'.($filaInicio -1))->applyFromArray($styleArray2);
	//$filaInicioMarco ++;
}

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