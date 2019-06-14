<?php 
/*El informe de errores */
error_reporting (E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
/** Include PHPExcel */
include_once("FuncionesPHP.php");
$con = ConectarBD();
require_once ('PHPExcel1.8.0/Classes/PHPExcel.php');

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
$styleArrayTitulo = array ('font' =>array(
						 				'bold' => true,
										'size' => 12 ,
										'name' => 'Verdana',
										'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE),
						 'alignment' =>array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
						 
$styleArrayHead = array('font' =>  array('bold' => true,
										 'color' => array('rgb' => 'FFFFFF')),
						'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER), 
						'fill' =>      array('type' => PHPExcel_Style_Fill::FILL_SOLID,
											 'color' => array('rgb' => '5E6879')));
//PARA ALINER EL TEXTO A LA DERECHAR CON LA LETRA EN NEGRITA	
$styleArrayTex = array('font' => array('bold' => true),
					   'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT));
						
//PARA CENTAR EL TEXTO
$styleArrayTex2 = array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));

//RELLENA DE COLOR LAS CELDA INTERNA 
$styleArrayRelleno = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
											'rotation' => 90,
											'startcolor' => array('argb' => 'ECEDEF'),
											'endcolor' => array('argb' => 'FFFFFFFF')));
//PARA RELLENEAR LA CELDA DEL TOTAL 											
$styleArrayTotales = array('fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
										   'rotation' => 90,
										   'startcolor' => array('argb' => 'ECEDEF'),
											'endcolor' => array('argb' => 'FFFFFFFF')),
							'font' => array('bold' => true,
											'underline'  =>  PHPExcel_Style_Font :: UNDERLINE_SINGLE));
											
		//CONSULTO PARA SABER EL NOMBRE USUARIO 
		$sqlUser = "SELECT * FROM sipre_co_config.usuario WHERe nombre = '".$_SESSION["UsuarioSistema"]."' ;";
		$queryUser = mysql_query($sqlUser);  
			if(!$queryUser){ die("Error: ".mysql_error().__LINE__); } 
		$rowsUser = mysql_fetch_array($queryUser);
		
		//CONSULTO LOS DATOS DE LA EMPRESA
		$SqlEmp = "SELECT id_empresa, nombre_empresa,logo_empresa,rif,web FROM sipre_automotriz.pg_empresa WHERE id_empresa = 1";
		//echo $SqlEmp."<br>";
		$queryEmp = mysql_query($SqlEmp);  
		if(!$queryEmp){ die("Error: ".mysql_error().__LINE__); } 
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
		$objDrawing->setPath("../".$logoEmpresa);
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
		
switch($_GET['orientacion']){
	
	case 'P'://LISTA DE ACTIVOS

		$nombreArchivo = "Listado_activo_fijo.xlsx";
		$titulo = "Listado de Activos Fijo";
		
		//fecha y hora
		$objPHPExcel->getActiveSheet()->setCellValue('G1', $fecha);
		$objPHPExcel->getActiveSheet()->mergeCells('G1:H1');
		$objPHPExcel->getActiveSheet()->getStyle('G1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->setCellValue('H2', $hora);
		$objPHPExcel->getActiveSheet()->getStyle('H1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->setCellValue('E3', 'Genereado por:');
		$objPHPExcel->getActiveSheet()->mergeCells('E3:F3');
		$objPHPExcel->getActiveSheet()->getStyle('E3:F3')->applyFromArray($styleArrayTex);
		$objPHPExcel->getActiveSheet()->setCellValue('G3', $rowsUser['nombusuario']);

		//titulo de la hoja
		$objPHPExcel->getActiveSheet()->setCellValue('B5', $titulo);
		$objPHPExcel->getActiveSheet()->mergeCells('B5:I5');
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getStyle('B5:I5')->applyFromArray($styleArrayTitulo);

		//Datos de la cabecera del cuadro
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('B7', 'Id Activo')
					->setCellValue('C7', 'Código Cuenta')
					->setCellValue('D7', 'Descripcion')
					->setCellValue('I7', 'Retirado');
		$objPHPExcel->getActiveSheet()->mergeCells('D7:H7');
		$objPHPExcel->getActiveSheet()->getStyle('B7:I7')->applyFromArray($styleArrayHead);
		
		$sqlListAct = "SELECT * FROM sipre_contabilidad.deprecactivos ORDER BY Codigo ASC;";
			$queryListAct = mysql_query($sqlListAct);
		if(!$queryListAct){ die("Erro de conexio".mysql_error().__LINE__); } 
		$totalRegistros = mysql_num_rows($queryListAct);	
					
		$count = 7;
		$totalFilas = ($count + $totalRegistros);
			while($rowlListAct = mysql_fetch_array($queryListAct)){
				$count++;
				
			$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('B'.$count, $rowlListAct['Codigo'])
						->setCellValue('C'.$count, $rowlListAct['Tipo'])
						->setCellValue('D'.$count, $rowlListAct['Descripcion'])
						->setCellValue('I'.$count, 'X');
			$objPHPExcel->getActiveSheet()->mergeCells('D'.$count.':H'.$count);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);  
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			
			$objPHPExcel->getActiveSheet()->getStyle('B'.$count.':C'.$count)->applyFromArray($styleArrayTex2);
			(fmod($count, 2) == 0) ? "" : $objPHPExcel->getActiveSheet()->getStyle('B'.$count.':I'.$count)->applyFromArray($styleArrayRelleno);
			
			//$fin['fin']= $count;
			}	
		
		//le colo un borde a las celdas
		$objPHPExcel->getActiveSheet()->getStyle('B7:I'.$totalFilas)->applyFromArray($styleArray);
		$objPHPExcel->getActiveSheet()->getStyle('B7:I'.$totalFilas)->applyFromArray($styleArray2);
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($totalFilas +1), 'TOTAL ACTIVOS FIJO:');
			$objPHPExcel->getActiveSheet()->mergeCells('B'.($totalFilas +1).':G'.($totalFilas +1));
			$objPHPExcel->getActiveSheet()->getStyle('B'.($totalFilas +1))->applyFromArray($styleArrayTex);	
							
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H'.($totalFilas +1), $totalRegistros);
			$objPHPExcel->getActiveSheet()->mergeCells('H'.($totalFilas +1).':I'.($totalFilas +1));
			$objPHPExcel->getActiveSheet()->getStyle('H'.($totalFilas +1))->applyFromArray($styleArrayTotales);
			break; //FIN LISTA ACTIVO
			
	case 'L': //DEPRECIACION ACTIVOS
	
		$nombreArchivo = "Depreciacion_activo_fijo.xls";
		$titulo = "Depreciación Activos Fijo";

		//fecha y hora
		$objPHPExcel->getActiveSheet()->setCellValue('M1', $fecha);
		$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getStyle('M1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->setCellValue('M2',$hora);
		$objPHPExcel->getActiveSheet()->getStyle('M2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->setCellValue('K3', 'Genereado por:');
		$objPHPExcel->getActiveSheet()->setCellValue('L3', $rowsUser['nombusuario']);
		$objPHPExcel->getActiveSheet()->mergeCells('L3:M3');
		$objPHPExcel->getActiveSheet()->getStyle('K3')->applyFromArray($styleArrayTex);

		$sqlFech = "SELECT DATE_FORMAT(DATE_SUB(Fechacomp_cierr, INTERVAL 1 YEAR),'%d-%m-%Y') AS fecha_cierre_periodo_anterior,
						DATE_FORMAT(Fechacomp_cierr,'%d-%m-%Y') AS fecha_cierre_periodo_actual
					FROM sipre_contabilidad.parametros;";
		$queryFec= mysql_query($sqlFech);
		if(!$queryFec){ die("Error: ".mysql_error().__LINE__); } 
		$rowsFech = mysql_fetch_array($queryFec);
		
		//titulo de la hoja
		$objPHPExcel->getActiveSheet()->setCellValue('A5', $titulo);
		$objPHPExcel->getActiveSheet()->mergeCells('A5:M5');
		$objPHPExcel->getActiveSheet()->getStyle('A5:M5')->applyFromArray($styleArrayTitulo);

		//Fechas del periodo
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('K7', 'Fecha de Cierre Periodo Anterior:')
					->mergeCells('K7:L7');
		$objPHPExcel->getActiveSheet()->getStyle('K7:L7')->applyFromArray($styleArrayTex);
		$objPHPExcel->getActiveSheet()->setCellValue('M7',$rowsFech['fecha_cierre_periodo_anterior'])
					->setCellValue('K8', 'Fecha de Cierre Periodo Actual: ')
					->mergeCells('K8:L8');
		$objPHPExcel->getActiveSheet()->getStyle('K8:L8')->applyFromArray($styleArrayTex);
		$objPHPExcel->getActiveSheet()->setCellValue('M8',$rowsFech['fecha_cierre_periodo_actual']);
					
		$sqlDepAct= "SELECT deprecactivos.Codigo, tipo, tipoactivo.Descripcion AS  tipo_Activo_Descripcion,
			DATE_FORMAT(Fecha,'%d-%m-%Y') AS fecha_compra, anomes, deprecactivos.Descripcion,
			deprecactivos.MesesDepre AS Vida_Util,
			  #CALCULO DE LA VUP_ANTERIOR
						  (SELECT COUNT(a.id) FROM sipre_contabilidad.con_depreciacion a
						  WHERE a.anomes <= DATE_FORMAT(
							(SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 1 YEAR) FROM sipre_contabilidad.parametros  limit 1), '%Y%m')
							AND a.anomes >= DATE_FORMAT(
							  (SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 2 YEAR) FROM sipre_contabilidad.parametros limit 1), '%Y%m')
							  AND a.codigoactivos = deprecactivos.Codigo) AS VUP_ANTERIOR,
			  #CALCULO DE LA VUP_ACTUAL
						(SELECT COUNT(b.anomes) FROM sipre_contabilidad.con_depreciacion b
						  WHERE b.anomes >= DATE_FORMAT(FechaDepre,'%Y%m')
						 AND b.anomes <= DATE_FORMAT(
						   (SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros limit 1), '%Y%m')
							AND b.codigoactivos = deprecactivos.Codigo) AS VUP_ACTUAL,
			  #CALCULO DE LA VU_RESTANTE = (Vida_Util - VUP_ANTERIOR - VIDA UTIL P ACTUAL)
					   (deprecactivos.MesesDepre - (SELECT COUNT(b.anomes) FROM sipre_contabilidad.con_depreciacion b
										 WHERE b.anomes >= DATE_FORMAT(FechaDepre,'%Y%m')
										 AND b.anomes <= DATE_FORMAT(
										   (SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros limit 1), '%Y%m')
										 AND b.codigoactivos = deprecactivos.Codigo)) AS VU_RESTANTE,
			 #CALCULO DE GSTO ANUAL
						IF(DATE_FORMAT(Fecha, '%Y') <
					  (SELECT DATE_FORMAT(Fechacomp_cierr, '%Y')
						FROM sipre_contabilidad.parametros LIMIT 1), 12,COUNT(SUBSTRING(anomes,5))) AS GSTO_ANUAL,
						CompAdquisicion AS COSTO_HIST,
						round((DepreMensual), 2) AS DEP_MENSUAL,
			 #CALCULO DE LA DEP ACUMULADA ANTERIOR = (COSTO_HIST / Vida_Util * VUP_ANTERIOR)
						ROUND((CompAdquisicion / deprecactivos.MesesDepre *
						(SELECT COUNT(a.id) FROM sipre_contabilidad.con_depreciacion a
						  WHERE a.anomes <= DATE_FORMAT(
							(SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 1 YEAR) FROM sipre_contabilidad.parametros limit 1), '%Y%m')
								AND a.anomes >= DATE_FORMAT(
								(SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 2 YEAR) FROM sipre_contabilidad.parametros limit 1), '%Y%m')
									AND a.codigoactivos = deprecactivos.Codigo)),2) AS DEP_ACUML_ANT,
			 #CALCULO DEL PERIODO = (GSTO_ANUAL * DEP_MENSUAL)
					  ROUND(((IF(DATE_FORMAT(Fecha, '%Y') < (
						SELECT DATE_FORMAT(Fechacomp_cierr, '%Y')
						  FROM sipre_contabilidad.parametros LIMIT 1), 12,COUNT(SUBSTRING(anomes,5))))*DepreMensual),2) AS DE_PERIODO
					FROM sipre_contabilidad.deprecactivos
						LEFT JOIN sipre_contabilidad.con_depreciacion ON deprecactivos.Codigo = con_depreciacion.codigoactivos
						LEFT JOIN  sipre_contabilidad.tipoactivo ON deprecactivos.tipo = tipoactivo.Codigo
						WHERE estatus = 0
					AND SUBSTRING(anomes,1,4) <= YEAR((SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros LIMIT 1))
					AND SUBSTRING(anomes,5) <= MONTH((SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros LIMIT 1))
					GROUP BY deprecactivos.Codigo ORDER BY deprecactivos.tipo, anomes, deprecactivos.Codigo ASC;";
		
		$querDepAct = mysql_query($sqlDepAct);
		if(!$querDepAct){ die("Erro de conexio".mysql_error().__LINE__); } 
		$totalRegistros = mysql_num_rows($querDepAct);
		
		$cont = 0;
		$countCell = 10;
		$countCellMarco = 11;
		$cabecera1 = array();
		$pie = array();
		
		$totalCostoHist = 0;
		$totalDepAcumulAnt = 0;
		$totalDepPerio = 0;
		$totalValorNeto = 0;
		$totalFilas = 0;
		
		while($rows = mysql_fetch_array($querDepAct)){
		$cont++;
		
			if($rows['DEP_ACUML_ANT'] == "" || $rows['DEP_ACUML_ANT'] == NULL){
				$DEP_ACUML_ANT= 0.00;	
			} else {
				$DEP_ACUML_ANT = $rows['DEP_ACUML_ANT'];
			}
			
			//CALCULO DE LA DEP_ACUMULADA
			$DEP_ACUMULADA = ($DEP_ACUML_ANT + $rows['DE_PERIODO']);
				
			//CALCULO PARA EL V_NETO
			$V_NETO = ($rows['COSTO_HIST'] - $DEP_ACUML_ANT - $rows['DE_PERIODO']);	
			
		if(!empty($pie) && end($pie) != $rows['tipo']){
			
			$countCell++;
			$totalFilas++;
			$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('A'.$countCell, 'TOTAL:')
						->mergeCells('A'.$countCell.':F'.$countCell);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$countCell)->applyFromArray($styleArrayTex);
			$objPHPExcel->getActiveSheet()->setCellValue('H'.$countCell,$totalCostoHist);
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$countCell,$totalDepAcumulAnt);
			$objPHPExcel->getActiveSheet()->setCellValue('K'.$countCell,$totalDepPerio);
			$objPHPExcel->getActiveSheet()->setCellValue('M'.$countCell,$totalValorNeto);
			
			$objPHPExcel->getActiveSheet()->getStyle('H'.$countCell)->applyFromArray($styleArrayTotales);
			$objPHPExcel->getActiveSheet()->getStyle('J'.$countCell)->applyFromArray($styleArrayTotales);
			$objPHPExcel->getActiveSheet()->getStyle('K'.$countCell)->applyFromArray($styleArrayTotales);
			$objPHPExcel->getActiveSheet()->getStyle('M'.$countCell)->applyFromArray($styleArrayTotales);
			
			$totalCostoHist = 0;
			$totalDepAcumulAnt = 0;
			$totalDepPerio = 0;
			$totalValorNeto = 0;
			
			$countCell += 2;
			
			$objPHPExcel->getActiveSheet()->getStyle('A'.$inicio['inicio'].':M'.$inicio['fin'])->applyFromArray($styleArray2);
			
		}	
			
			$pie[] = $rows['tipo'];
			
		if(!in_array($rows['tipo'],$cabecera1)){
				$cabecera1[] = $rows['tipo'];
				$inicio['inicio'] = $countCell;
				
				$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('B'.$countCell, 'Código tipo de activo fijo:');
				$objPHPExcel->getActiveSheet()->getStyle('B'.$countCell)->applyFromArray($styleArrayTex);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$countCell, $rows['tipo'])
							->mergeCells('B'.$countCell.':C'.$countCell)
				
							->setCellValue('E'.$countCell, 'Descripción:');
				$objPHPExcel->getActiveSheet()->getStyle('E'.$countCell)->applyFromArray($styleArrayTex);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$countCell, $rows['tipo_Activo_Descripcion'])
							->mergeCells('F'.$countCell.':M'.$countCell);
				$objPHPExcel->getActiveSheet()->getStyle('A'.$countCell.':M'.$countCell)->applyFromArray($styleArrayRelleno);
					$countCell++;
				$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A'.$countCell, 'id')
							->setCellValue('B'.$countCell, 'Fecha Compra')
							->setCellValue('C'.$countCell, 'Vida Util')
							->setCellValue('D'.$countCell, 'VU P Anteior')
							->setCellValue('E'.$countCell, 'VU P actual')
							->setCellValue('F'.$countCell, 'VU Restante')
							->setCellValue('G'.$countCell, 'Gto Anual')
							->setCellValue('H'.$countCell, 'Costo Hist')
							->setCellValue('I'.$countCell, 'DEP Mensual')
							->setCellValue('J'.$countCell, 'DEP acumul. Ant')
							->setCellValue('K'.$countCell, 'DEP Periodo')
							->setCellValue('L'.$countCell, 'DPE Acumulada')
							->setCellValue('M'.$countCell, 'V Neto');
				$objPHPExcel->getActiveSheet()->getStyle('A'.$countCell.':M'.$countCell)->applyFromArray($styleArrayHead);
			
		}
				$countCell++;
				$inicio['fin'] = $countCell;
				$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A'.$countCell, $rows['Codigo'])
							->setCellValue('B'.$countCell, $rows['fecha_compra'])
							->setCellValue('C'.$countCell, utf8_encode(convertirMeses($rows['Vida_Util'])))
							->setCellValue('D'.$countCell, utf8_encode(convertirMeses($rows['VUP_ANTERIOR'])))
							->setCellValue('E'.$countCell, utf8_encode(convertirMeses($rows['VUP_ACTUAL'])))
							->setCellValue('F'.$countCell, utf8_encode(convertirMeses($rows['VU_RESTANTE'])))
							->setCellValue('G'.$countCell, $rows['GSTO_ANUAL'])
							->setCellValue('H'.$countCell, round($rows['COSTO_HIST'],2))
							->setCellValue('I'.$countCell, round($rows['DEP_MENSUAL'],2))
							->setCellValue('J'.$countCell, round($DEP_ACUML_ANT,2))
							->setCellValue('K'.$countCell, round($rows['DE_PERIODO'],2))
							->setCellValue('L'.$countCell, round($DEP_ACUMULADA,2))
							->setCellValue('M'.$countCell, round($V_NETO,2));
				$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('k')->setAutoSize(true);
				$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
				
				$objPHPExcel->getActiveSheet()->getStyle('A'.$countCell.':B'.$countCell)->applyFromArray($styleArrayTex2);
				$objPHPExcel->getActiveSheet()->getStyle('G'.$countCell.':M'.$countCell)->applyFromArray($styleArrayTex2);
				
				$objPHPExcel->getActiveSheet()->getStyle('A'.($countCell - 1).':M'.$countCell)->applyFromArray($styleArray);	
				
				(fmod($countCell, 2) == 0) ? $objPHPExcel->getActiveSheet()->getStyle('A'.$countCell.':M'.$inicio['fin'])->applyFromArray($styleArrayRelleno): "";

				$totalCostoHist += $rows['COSTO_HIST'];
				$totalDepAcumulAnt += $DEP_ACUML_ANT;
				$totalDepPerio += $rows['DE_PERIODO'];
				$totalValorNeto += $V_NETO; 
		
		//CUANDO SEA EL ULTIMO TIPO DE ACTIVO DE LA CONSULTA
		if($totalRegistros == $cont){
			
			$countCell++;
			$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A'.$countCell, 'TOTAL:')
							->mergeCells('A'.$countCell.':F'.$countCell);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$countCell)->applyFromArray($styleArrayTex);	

			$objPHPExcel->getActiveSheet()->setCellValue('H'.$countCell,$totalCostoHist);
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$countCell,$totalDepAcumulAnt);
			$objPHPExcel->getActiveSheet()->setCellValue('K'.$countCell,$totalDepPerio);
			$objPHPExcel->getActiveSheet()->setCellValue('M'.$countCell,$totalValorNeto);			
			
			//RESALTATOALES LOS TOTALES
			$objPHPExcel->getActiveSheet()->getStyle('H'.$countCell)->applyFromArray($styleArrayTotales);
			$objPHPExcel->getActiveSheet()->getStyle('J'.$countCell)->applyFromArray($styleArrayTotales);
			$objPHPExcel->getActiveSheet()->getStyle('K'.$countCell)->applyFromArray($styleArrayTotales);
			$objPHPExcel->getActiveSheet()->getStyle('M'.$countCell)->applyFromArray($styleArrayTotales);
			
			$objPHPExcel->getActiveSheet()->getStyle('A'.$inicio['inicio'].':M'.$inicio['fin'])->applyFromArray($styleArray2);
			
		}	
				
	}
					break;//FIN DEPRECIACION
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