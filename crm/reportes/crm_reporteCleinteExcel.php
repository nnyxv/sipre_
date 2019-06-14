<?php 
/*El informe de errores */

//error_reporting (E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);

require_once ("../../connections/conex.php");
/** Include PHPExcel */
require('../../clases/phpExcel_1.7.8/Classes/PHPExcel.php');
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
							 
//DEFINE EL ESTILO
$styleArray = array('borders' => array('inside'=> array( 'style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '555657'))));
$styleArray2 = array('borders' => array('outline'=> array('style' => PHPExcel_Style_Border::BORDER_THICK,'color' => array('argb' => '2D2E30'))));
$styleArrayEmp = array('font' => array('bold' => true),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT));
$styleArrayTitulo = array ('font' =>array('bold' => true,'size' => 12,'name' => 'Verdana','underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE),'alignment' =>array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
						 
$styleArrayHead = array('font' =>  array('bold' => true,'color' => array('rgb' => 'FFFFFF')),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),'fill' =>  array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'D0B562')));

//PARA ALINER EL TEXTO A LA DERECHAR CON LA LETRA EN NEGRITA	
$styleArrayTex = array('font' => array('bold' => true),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT));
						
//PARA CENTAR EL TEXTO
$styleArrayTex2 = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

//RELLENA DE COLOR LAS CELDA INTERNA 
$styleArrayRelleno = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,'rotation' => 90,'startcolor' => array('argb' => 'FEF7E0'),'endcolor' => array('argb' => 'FFFFFFFF')));

//PARA RELLENEAR LA CELDA DEL TOTAL 											
$styleArrayTotales = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,'rotation' => 90,
			'startcolor' => array('argb' => 'FEF7E0'),
			'endcolor' => array('argb' => 'FFFFFFFF')
		),
		'font' => array(
				'bold' => true,
				'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE, 
				'alignment' =>array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT)
		)
);

$styleArrayTotales = array(
	'font' => array(
		'bold' => true,
		'color' => array(
			'argb' => 'FF000000'
		),
	),
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
	),
	'borders' => array(
		'top' => array(
			'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
			'color' => array(
				'argb' => 'FEF7E0' // Mismo que trResaltar5:hover
			),
		),
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FEF7E0' // Mismo que trResaltar5:hover
			),
		),
	)
);

		$idEmpresa = ($_GET['idEmpresa'] == "") ? 1 : $_GET['idEmpresa'];
		//CONSULTO LOS DATOS DE LA EMPRESA
		$SqlEmp = sprintf("SELECT id_empresa, nombre_empresa,logo_empresa,rif,web 
								FROM pg_empresa 
							WHERE id_empresa = %s",
						 $idEmpresa);
		//echo $SqlEmp."<br>";
		$queryEmp = mysql_query($SqlEmp);  
		if(!$queryEmp){ die("Error empresa: ".mysql_error().__LINE__); } 
		$rowsEmp = mysql_fetch_array($queryEmp);
		
		//llamando a la funcion encabezado del fpdf
		$idEmpresa = $rowsEmp['id_empresa'];
		$nombreEmpresa = $rowsEmp['nombre_empresa'];
		$logoEmpresa = $rowsEmp['logo_empresa'];//logo del reporte
		$rif = $rowsEmp['rif'];
		$web = $rowsEmp['web'];
		
		$fecha = date("d/m/Y");
		$hora = date("h:i. A");

		$objDrawing = new PHPExcel_Worksheet_Drawing();
		$objDrawing->setName('Logo');
		$objDrawing->setDescription('Logo');
		$objDrawing->setPath("../../".$logoEmpresa);
		$objDrawing->setHeight(80);
		$objDrawing->setWidth(100);
		$objDrawing->setCoordinates('A1'); 
		$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		
		//Data de la empresa
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('C1', $nombreEmpresa);
		$objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArrayEmp);
		$objPHPExcel->getActiveSheet()->setCellValue('C2', $rif);
		$objPHPExcel->getActiveSheet()->getStyle('C2')->applyFromArray($styleArrayEmp);
		$objPHPExcel->getActiveSheet()->setCellValue('C3',$web);
		$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
		$objPHPExcel->getActiveSheet()->getCell('C3')->getHyperlink()->setUrl("http://".$web);
		$objPHPExcel->getActiveSheet()->getStyle('C3')->applyFromArray($styleArrayEmp);

		$nombreArchivo = "Listado_de_clientes.xlsx";
		$titulo = "Listado de Clientes";
		
		//titulo de la hoja
		$objPHPExcel->getActiveSheet()->setCellValue('B5', 'Listado de Clientes');
		$objPHPExcel->getActiveSheet()->mergeCells('B5:L5');
		//$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getStyle('B5:L5')->applyFromArray($styleArrayTitulo);

		//Datos de la cabecera del cuadro
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('B7', 'Empresa')
					->setCellValue('C7', 'id Cliente')
					->setCellValue('D7', 'Nombre / Apellido')
					->setCellValue('E7', 'Fecha Creación')
					->setCellValue('F7', 'Fecha Nacimiento')
					->setCellValue('G7', 'Sexo')
					->setCellValue('H7', 'CI /R.I.F')
					->setCellValue('I7', 'Telefono')
					->setCellValue('J7', 'Correo')
					->setCellValue('K7', 'Tipo de cliente')
					->setCellValue('L7', 'Modelo');
		
		//DEFINE EL MARGEN DE CADA COLUMNA
		$objPHPExcel->getActiveSheet()->mergeCells('B7:B7');
		$objPHPExcel->getActiveSheet()->mergeCells('C7:C7');
		$objPHPExcel->getActiveSheet()->mergeCells('D7:D7');
		$objPHPExcel->getActiveSheet()->mergeCells('E7:E7');
		$objPHPExcel->getActiveSheet()->mergeCells('F7:F7');
		$objPHPExcel->getActiveSheet()->mergeCells('G7:G7');
		$objPHPExcel->getActiveSheet()->mergeCells('H7:H7');
		$objPHPExcel->getActiveSheet()->mergeCells('I7:I7');
		$objPHPExcel->getActiveSheet()->mergeCells('J7:J7');
		$objPHPExcel->getActiveSheet()->mergeCells('K7:K7');
		$objPHPExcel->getActiveSheet()->mergeCells('L7:L7');
		
		//DEFINE EL ESTILO DE LA FILA	
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
		
		$objPHPExcel->getActiveSheet()->getStyle('B7:L7')->applyFromArray($styleArrayHead);
		
		if($_GET['idEmpresa'] != "") {
			$sqlBusq = sprintf("WHERE cj_cc_cliente_empresa.id_empresa = %s",
			$_GET['idEmpresa']);
		}
		
		$valCadBusq = explode("|", $_GET['valBusq']);
		$startRow = $pageNum * $maxRows;

		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cj_cc_cliente_empresa.id_empresa = %s",
					valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("crm_perfil_prospecto.sexo = %s",
					valTpDato($valCadBusq[1], "text"));
		}
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "" && $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fcreacion BETWEEN %s AND %s ",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "text"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "text"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("status = %s ",
					valTpDato($valCadBusq[4], "text"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo = %s ",
					valTpDato($valCadBusq[5], "text"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_cuenta_cliente = %s ",
					valTpDato($valCadBusq[6], "int"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("id_modelo = %s ",
					valTpDato($valCadBusq[7], "int"));
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf(" CONCAT_Ws(' ', nombre, apellido) LIKE %s
			OR cj_cc_cliente.correo LIKE %s
			OR CONCAT_WS('-', lci, ci) LIKE %s",
					valTpDato("%".$valCadBusq[8]."%", "text"),
					valTpDato("%".$valCadBusq[8]."%", "text"),
					valTpDato("%".$valCadBusq[8]."%", "text"));
		}
		$sqlListClient = sprintf("SELECT DISTINCT cj_cc_cliente.id, cj_cc_cliente_empresa.id_empresa,nombre_empresa,
										tipo,CONCAT_Ws(' ', nombre, apellido) AS nombre_apellido_cliente,
										CONCAT_WS('-', lci, ci) AS ci_cliente, telf, 
										cj_cc_cliente.correo, status, tipo_cuenta_cliente,
										fecha_nacimiento, crm_perfil_prospecto.sexo,
										sa_cita.id_registro_placas,
										en_registro_placas.id_unidad_fisica,
										id_unidad_basica, placa,
										an_uni_bas.id_uni_bas, mar_uni_bas, nom_marca, mod_uni_bas, id_modelo, nom_modelo, cj_cc_cliente.fcreacion
									FROM cj_cc_cliente
										LEFT JOIN cj_cc_cliente_empresa ON cj_cc_cliente_empresa.id_cliente = cj_cc_cliente.id
										LEFT JOIN pg_empresa ON pg_empresa.id_empresa = cj_cc_cliente_empresa.id_empresa
										LEFT JOIN crm_perfil_prospecto ON crm_perfil_prospecto.id = cj_cc_cliente.id
										LEFT JOIN sa_cita ON cj_cc_cliente.id = sa_cita.id_cliente_contacto
										LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas
										LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
										LEFT JOIN an_marca ON id_marca = mar_uni_bas
										LEFT JOIN an_modelo ON id_modelo = mod_uni_bas 
									%s GROUP BY cj_cc_cliente.id", $sqlBusq);
						
		$queryListActCLient = mysql_query($sqlListClient);
		
		if(!$queryListActCLient){ die("Erro de conexio".mysql_error().__LINE__); } 
		$totalRegistros = mysql_num_rows($queryListActCLient);	

		$count = 7;
		$totalFilas = ($count + $totalRegistros);
			while($rowlListCLient = mysql_fetch_array($queryListActCLient)){
				$count++;
				
				$sqlModelo = sprintf("SELECT id_registro_placas,id_cliente_registro, chasis, placa, en_registro_placas.kilometraje,
											en_registro_placas.id_unidad_basica,id_uni_bas, des_uni_bas,mar_uni_bas, nom_marca,
											mod_uni_bas,nom_modelo,des_modelo,fecha_venta
										FROM en_registro_placas
											LEFT JOIN cj_cc_cliente ON cj_cc_cliente.id = en_registro_placas.id_cliente_registro
											LEFT JOIN an_uni_bas ON an_uni_bas.id_uni_bas = en_registro_placas.id_unidad_basica
											LEFT JOIN an_marca ON an_marca.id_marca = an_uni_bas.mar_uni_bas
											LEFT JOIN an_modelo ON an_modelo.id_modelo = an_uni_bas.mod_uni_bas
										WHERE id_cliente_registro = %s
										ORDER BY placa",$rowlListCLient['id']);
						
				$queryModelo = mysql_query($sqlModelo);
				if(!$queryModelo){ die("Erro en los modelos".mysql_error().__LINE__); }
				$totalModelos = mysql_num_rows($queryModelo);
				
				$modelo = "";
				$aux = "";
				while($rowsModelos = mysql_fetch_array($queryModelo)){
					$aux ++;
						$separa = ($totalModelos == $aux)? "":", " ;	
					$modelo .= $rowsModelos['nom_modelo'].$separa;
				}
				
			$fechaCreacion = ($rowlListCLient['fcreacion'] == "") ? "" : date('d-m-Y',strtotime($rowlListCLient['fcreacion']));
			$fechaNacimiento = ($rowlListCLient['fecha_nacimiento'] == "") ? "" : date('d-m-Y',strtotime($rowlListCLient['fecha_nacimiento']));
	
			$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('B'.$count, trim($rowlListCLient['nombre_empresa']))
						->setCellValue('C'.$count, trim($rowlListCLient['id']))
						->setCellValue('D'.$count, trim(utf8_encode($rowlListCLient['nombre_apellido_cliente'])))
						->setCellValue('E'.$count, trim($fechaCreacion))
						->setCellValue('F'.$count, trim($fechaNacimiento))
						->setCellValue('G'.$count, trim($rowlListCLient['sexo']))
						->setCellValue('H'.$count, trim($rowlListCLient['ci_cliente']))
						->setCellValue('I'.$count, trim($rowlListCLient['telf']))
						->setCellValue('J'.$count, trim($rowlListCLient['correo']))
						->setCellValue('K'.$count, trim($rowlListCLient['tipo']))
						->setCellValue('L'.$count, trim($modelo));
						
			//DEFINE EL TAMAÑO DE LA CELDA SEGUN LA CANTIDAD DE CARACTERES QUE TRAIGA EL REGISTRO
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

			$objPHPExcel->getActiveSheet()->getStyle('C'.$count.':C'.$count)->applyFromArray($styleArrayTex2);
			$objPHPExcel->getActiveSheet()->getStyle('E'.$count.':E'.$count)->applyFromArray($styleArrayTex2);
			$objPHPExcel->getActiveSheet()->getStyle('G'.$count.':G'.$count)->applyFromArray($styleArrayTex2);
			$objPHPExcel->getActiveSheet()->getStyle('H'.$count.':H'.$count)->applyFromArray($styleArrayTex2);
			
			(fmod($count, 2) == 0) ? "" : $objPHPExcel->getActiveSheet()->getStyle('B'.$count.':L'.$count)->applyFromArray($styleArrayRelleno);
			}	
		
			//COLO EL BORDER A LAS CELDAS
			$objPHPExcel->getActiveSheet()->getStyle('B7:L'.$totalFilas)->applyFromArray($styleArray);//INTERNO
			$objPHPExcel->getActiveSheet()->getStyle('B7:L'.$totalFilas)->applyFromArray($styleArray2);//EXTERNO
		
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($totalFilas +1), 'TOTAL CLIENTE EN '.$nombreEmpresa.' :');
			$objPHPExcel->getActiveSheet()->mergeCells('B'.($totalFilas +1).':I'.($totalFilas +1));
			$objPHPExcel->getActiveSheet()->getStyle('B'.($totalFilas +1))->applyFromArray($styleArrayTex);/*DEFINE EL ESTILO DE LA FILA DE TOTAL*/	

			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('J'.($totalFilas +1), $totalRegistros);
			$objPHPExcel->getActiveSheet()->mergeCells('J'.($totalFilas +1).':L'.($totalFilas +1));
			$objPHPExcel->getActiveSheet()->getStyle('J'.($totalFilas +1))->applyFromArray($styleArrayTotales);

//Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle($titulo); //"'".$tituloPenstana."'"
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