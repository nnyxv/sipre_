<?php
$styleArrayTitulo = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => '3498db'
		),
		'size' => 12,
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
    ),
	'borders' => array(
		'top' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => '000' // Mismo que trResaltar5:hover
			),
		),
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => '000' // Mismo que trResaltar5:hover
			),
		),
	)
);

$styleArrayCampo = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FFFFFFFF'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7' // Mismo que trResaltar5:hover PERO MAS CLARO
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'd0b562', // Mismo que trResaltar5:hover
        ),
        'endcolor' => array(
            'argb' => 'd0b562', // Mismo que trResaltar5:hover
        ),
    ),
);

$styleArrayCampo2 = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FFFFFFFF'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FFC4D79B'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'FF9BBB59',
        ),
        'endcolor' => array(
            'argb' => 'FF9BBB59',
        ),
    ),
);

$styleArrayCampoHabilitado1 = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => '006100'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FFC4D79B'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'C6EFCE',
        ),
        'endcolor' => array(
            'argb' => 'C6EFCE',
        ),
    ),
);

$styleArrayCampoHabilitado2 = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => '006100'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FFC4D79B'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'EDFAEF',
        ),
        'endcolor' => array(
            'argb' => 'EDFAEF',
        ),
    ),
);

$styleArrayColumna = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => '000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
    ),
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => '3498db' // Mismo que trResaltar5:hover PERO MAS CLARO
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => '3498db', // Mismo que trResaltar5:hover
        ),
        'endcolor' => array(
            'argb' => '3498db', // Mismo que trResaltar5:hover
        ),
    ),
);

$styleArrayFila1 = array(
    'font' => array(
		'color' => array(
			'argb' => '000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_GENERAL,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => '34AAdb' // Mismo que trResaltar5:hover PERO MAS CLARO
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => '3498db', // Mismo que trResaltar5
        ),
        'endcolor' => array(
            'argb' => '3498db', // Mismo que trResaltar5
        ),
    ),
);

$styleArrayFila2 = array(
    'font' => array(
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_GENERAL,
    ),
    'borders' => array(
        'left' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7' // Mismo que trResaltar5:hover PERO MAS CLARO
			),
        ),
        'right' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF95B3D7' // Mismo que trResaltar5:hover PERO MAS CLARO
			),
        ),
    )
);

$styleArrayFilaError = array(
    'font' => array(
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_GENERAL,
    ),
    'borders' => array(
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FFFF0000'
			),
        ),
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startcolor' => array(
            'argb' => 'FFFFEEEE',
        ),
        'endcolor' => array(
            'argb' => 'FFFFEEEE',
        ),
    ),
);

$styleArrayResaltarTotal = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'top' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => '000' // Mismo que trResaltar5:hover
			),
        ),
    )
);

$styleArrayResaltarTotal2 = array(
    'font' => array(
        'bold' => true,
		'color' => array(
			'argb' => 'FF000000'
		),
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    'borders' => array(
        'top' => array(
            'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
			'color' => array(
				'argb' => 'FF9BBB59'
			),
        ),
        'outline' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array(
				'argb' => 'FF9BBB59'
			),
        ),
    )
);

function cabeceraExcel($objPHPExcel, $idEmpresa, $colHasta, $verDatosEmpresa = true, $cantFilasInsert = 8) {
	global $styleArrayColumna;
	global $spanRIF;
	
	$existe = false;
	$letraD = "C";
	$letraH = "C";
	while ($existe == false) {
		for ($cont = 1; $cont <= 4; $cont++) {
			$letraH++;
		}
		
		if ($letraD == $colHasta) {
			$colHastaEmpresa = "C";
			$existe = true;
		} else if ($letraH == $colHasta) {
			$colHastaEmpresa = $letraD;
			$existe = true;
		} else {
			$letraD++;
			$letraH = $letraD;
		}
	}
	
	$colHastaAutoSize = $colHasta;
	$colHastaAutoSize++;
	for ($col = "A"; $col != $colHastaAutoSize; $col++) {
		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
	}
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$queryEmp = sprintf("SELECT
		vw_iv_emp_suc.id_empresa_reg,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		vw_iv_emp_suc.rif,
		vw_iv_emp_suc.direccion,
		vw_iv_emp_suc.telefono1,
		vw_iv_emp_suc.telefono2,
		vw_iv_emp_suc.web,
		vw_iv_emp_suc.logo_familia
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE vw_iv_emp_suc.id_empresa_reg = %s
		OR ((%s = -1 OR %s IS NULL) AND vw_iv_emp_suc.id_empresa_suc IS NULL);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsEmp = mysql_num_rows($rsEmp);
	$rowEmp = mysql_fetch_assoc($rsEmp);
	
	(strlen($rowEmp['telefono1']) > 1) ? $arrayTelefono[] = $rowEmp['telefono1'] : "";
	(strlen($rowEmp['telefono2']) > 1) ? $arrayTelefono[] = $rowEmp['telefono2'] : "";
	
	//Titulo del libro y seguridad
	if ($totalRowsEmp > 0 && $verDatosEmpresa == true) {
		$lastColumn = $objPHPExcel->getActiveSheet()->getHighestColumn();
		$lastRow = $objPHPExcel->getActiveSheet()->getHighestRow();
		for ($col = "A"; $col != $lastColumn; $col++) {
			for ($fil = 1; $fil <= $lastRow; $fil++) {
				$cellval = $objPHPExcel->getActiveSheet()->getCell($col.$fil)->getValue();
				$datatype = $objPHPExcel->getActiveSheet()->getCell($col.$fil)->getDataType();
				
				$arrayInfo[] = array(
					$col.($fil + $cantFilasInsert),
					$cellval,
					$datatype);
			}
		}
		
		if (file_exists("../../".$rowEmp['logo_familia']) && strlen("../../".$rowEmp['logo_familia']) > 4) {
			$objDrawing = new PHPExcel_Worksheet_Drawing();
			$objDrawing->setName('Logo');
			$objDrawing->setDescription('Logo');
			$objDrawing->setPath("../../".$rowEmp['logo_familia']);
			$objDrawing->setHeight(100);
			$objPHPExcel->getActiveSheet()->insertNewRowBefore(1, $cantFilasInsert);
			$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
		} else {
			$objPHPExcel->getActiveSheet()->insertNewRowBefore(1, $cantFilasInsert);
		}
		
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C1", utf8_encode($rowEmp['nombre_empresa']), PHPExcel_Cell_DataType::TYPE_STRING);
		if (strlen($rowEmp['rif']) > 1) {
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("C2", utf8_encode($spanRIF.": ".$rowEmp['rif']), PHPExcel_Cell_DataType::TYPE_STRING);
		}
		if (strlen($rowEmp['direccion']) > 1) {
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("C3", utf8_encode($rowEmp['direccion']), PHPExcel_Cell_DataType::TYPE_STRING);
		}
		if (isset($arrayTelefono)) {
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("C4", utf8_encode("Telf.: ".implode(" / ", $arrayTelefono)), PHPExcel_Cell_DataType::TYPE_STRING);
		}
		if (strlen($rowEmp['web']) > 1) {
			$objPHPExcel->getActiveSheet()->setCellValueExplicit("C5", utf8_encode($rowEmp['web']), PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->getCell("C5")->getHyperlink()->setUrl("http://".$rowEmp['web']);
		}
		
		
		$colDesdeGenerado = $colHastaEmpresa;
		$colDesdeGenerado++;
		$objPHPExcel->getActiveSheet()->setCellValueExplicit($colDesdeGenerado."1", "Generado".((strlen($_SESSION['nombreEmpleadoSysGts']) > 0) ? " por: ".$_SESSION['nombreEmpleadoSysGts']." el " : ": ").date(spanDateFormat." h:i:s a"), PHPExcel_Cell_DataType::TYPE_STRING);
		
		$objPHPExcel->getActiveSheet()->getStyle($colDesdeGenerado."1:".$colHasta."1")->applyFromArray($styleArrayColumna);
		
		$objPHPExcel->getActiveSheet()->mergeCells("C1:".$colHastaEmpresa."1");
		$objPHPExcel->getActiveSheet()->mergeCells($colDesdeGenerado."1:".$colHasta."1");
		
		$objPHPExcel->getActiveSheet()->mergeCells("C2:".$colHasta."2");
		$objPHPExcel->getActiveSheet()->mergeCells("C3:".$colHasta."3");
		$objPHPExcel->getActiveSheet()->mergeCells("C4:".$colHasta."4");
		$objPHPExcel->getActiveSheet()->mergeCells("C5:".$colHasta."5");
		
		// INSERTA DE NUEVO LA INFORMACION RESPETANDO EL TIPO DE DATOS QUE TENIA
		if (isset($arrayInfo)) {
			foreach ($arrayInfo as $indice => $valor) {
				$objPHPExcel->getActiveSheet()->getCell($arrayInfo[$indice][0])->setValueExplicit($arrayInfo[$indice][1], $arrayInfo[$indice][2]);
			}
		}
	}
}

function ultimaColumnaExcel($letraD, $contColumnnas) {
	$letraH = $letraD;
	for ($cont = 1; $cont != $contColumnnas; $cont++) {
		$letraH++;
	}
	
	return $letraH;
}
?>