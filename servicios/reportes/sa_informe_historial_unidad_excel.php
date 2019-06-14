<?php
set_time_limit(0);
require("../../connections/conex.php");
session_start();

$valForm = json_decode($_GET['valForm'], TRUE);

$fecha1 = $valForm['txtFechaDesde'];
$fecha2 = $valForm['txtFechaHasta'];

$fechaDesde = date("Y/m/d",strtotime($fecha1));
$fechaHasta = date("Y/m/d",strtotime($fecha2));

$valBusq = sprintf("%s|%s|%s|%s|%s",
	$valForm['lstEmpresa'],
	$valForm['txtFechaDesde'],
	$valForm['txtFechaHasta'],
	$valForm['txtIdCliente'],
	$valForm['txtCriterio']
);

if ($fechaDesde > $fechaHasta){
	die("La primera fecha no debe ser mayor a la segunda");
}else{

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

	require('../../clases/phpExcel_1.7.8/Classes/PHPExcel.php');
	require('../../clases/phpExcel_1.7.8/Classes/PHPExcel/Reader/Excel2007.php');

	listadoHistorial('0','','ASC',$valBusq);
}

function listadoHistorial($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {

	$objPHPExcel = new PHPExcel();

    global $spanPlaca;
    global $spanSerialCarroceria;
	global $spanKilometraje;

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

	$idEmpresa = $valCadBusq[0];
    //$valCadBusq[0] id empresa
    //$valCadBusq[1] fecha desde
    //$valCadBusq[2] fecha hasta
    //$valCadBusq[3] id cliente
    //$valCadBusq[4] criterio

    //$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    //$sqlBusq .= $cond.sprintf("orden.id_estado_orden NOT IN (18,21,24)");

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_orden.id_empresa = %s",
                    valTpDato($valCadBusq[0], "int"));
    }

    if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("DATE(sa_orden.tiempo_orden) BETWEEN %s AND %s",
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
    }

    if ($valCadBusq[3] != "" && $valCadBusq[3] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("sa_orden.id_cliente = %s",
                valTpDato($valCadBusq[3],"int"),
                valTpDato($valCadBusq[3],"int"));
    }

    if ($valCadBusq[4] != "" && $valCadBusq[4] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("(en_registro_placas.placa = %s OR
									en_registro_placas.chasis = %s)",
                valTpDato($valCadBusq[4],"text"),
                valTpDato($valCadBusq[4],"text"));
    }

    $query = sprintf("SELECT
					sa_orden.id_orden,
					sa_orden.numero_orden,
					sa_tipo_orden.nombre_tipo_orden,
					sa_estado_orden.nombre_estado,
					sa_orden.tiempo_orden,
					sa_orden.tiempo_finalizado,
					sa_recepcion.id_recepcion,
					CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
					CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_asesor,
					sa_recepcion.kilometraje as kilometraje_vale,
					an_uni_bas.nom_uni_bas,
					an_modelo.nom_modelo,
					an_marca.nom_marca,
					an_ano.nom_ano,
					en_registro_placas.id_registro_placas,
					en_registro_placas.placa,
					en_registro_placas.chasis,
					en_registro_placas.kilometraje as kilometraje_vehiculo,
					en_registro_placas.color
			FROM sa_orden
					INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)                
					INNER JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                
					INNER JOIN en_registro_placas ON sa_cita.id_registro_placas = en_registro_placas.id_registro_placas
					INNER JOIN an_uni_bas ON en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas
					INNER JOIN an_modelo ON an_uni_bas.mod_uni_bas = an_modelo.id_modelo
					INNER JOIN an_marca ON an_uni_bas.mar_uni_bas = an_marca.id_marca
					INNER JOIN an_ano ON an_uni_bas.ano_uni_bas = an_ano.id_ano
					INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
					INNER JOIN sa_estado_orden ON sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden
					INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = sa_orden.id_cliente
					INNER JOIN pg_empleado ON sa_orden.id_empleado = pg_empleado.id_empleado            
            %s
			ORDER BY en_registro_placas.id_registro_placas, id_orden ASC", $sqlBusq); 

    //$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

    //$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($query);
    if (!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }

    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
            $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;
	
	$styleCentrar = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        )
    );
	
	$styleIzquierda = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        )
    );
	
	$styleBold = array(
		'font' => array(
			'bold' => true
		)
	);
	
	$styleBordeAbajo = array(
	    'borders' => array(
			'bottom' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN
			)
		)
	);
	
	$styleBordeArriba = array(
	    'borders' => array(
			'top' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN
			)
		)
	);
	
	$styleFondoVerde = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb' => 'e6ffe6')// verde total
		)
	);
	
	$styleFondoGris = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb' => 'f0f0f0')// gris tituloCampo
		)
	);
	
	$styleFondoGrisOscuro = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb' => 'cccccc')// gris tituloColumna
		)
	);
	
	
	
	/*$objPHPExcel->getActiveSheet()->getStyle()->applyFromArray(array(
		'font' => array(
			'name'      =>  'Arial',
			'size'      =>  8,
			//'bold'      =>  true
		)
	));*/
	
	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
	
	cabeceraExcel($objPHPExcel, $idEmpresa, "H");
	
	$objPHPExcel->getActiveSheet()->mergeCells('A7:H7');
	$objPHPExcel->getActiveSheet()->getStyle('A7')->applyFromArray($styleCentrar);
	$objPHPExcel->getActiveSheet()->getStyle('A7')->applyFromArray($styleBold);
	$objPHPExcel->getActiveSheet()->SetCellValue('A7', 'HISTORIAL DE UNIDAD');
	
	$html = "";
	$arrayIdRegistroPlaca = array();

    $contFila = 8;
    while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
					
		if(!array_key_exists($row["id_registro_placas"], $arrayIdRegistroPlaca)){//ES UNA UNIDAD A LA VEZ						
			//TABLA UNIDAD
			$objPHPExcel->getActiveSheet()->mergeCells('A'.$contFila.':H'.$contFila);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila)->applyFromArray($styleCentrar);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila)->applyFromArray($styleBold);
			$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila)->applyFromArray($styleFondoGrisOscuro);
			
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, 'UNIDAD');
			$contFila++;
			
			$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila)->applyFromArray($styleFondoGris);
			$objPHPExcel->getActiveSheet()->getStyle('C'.$contFila)->applyFromArray($styleFondoGris);
			$objPHPExcel->getActiveSheet()->getStyle('E'.$contFila)->applyFromArray($styleFondoGris);
			$objPHPExcel->getActiveSheet()->getStyle('G'.$contFila)->applyFromArray($styleFondoGris);
			
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, 'Unidad:');
			$objPHPExcel->getActiveSheet()->SetCellValue('B'.$contFila, $row["nom_uni_bas"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$contFila, 'Marca:');
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$contFila, $row["nom_marca"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('E'.$contFila, 'Modelo:');
			$objPHPExcel->getActiveSheet()->SetCellValue('F'.$contFila, $row["nom_modelo"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('G'.$contFila, 'Año:');
			$objPHPExcel->getActiveSheet()->SetCellValue('H'.$contFila, $row["nom_ano"]);
			$contFila++;
			
			$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila)->applyFromArray($styleFondoGris);
			$objPHPExcel->getActiveSheet()->getStyle('C'.$contFila)->applyFromArray($styleFondoGris);
			$objPHPExcel->getActiveSheet()->getStyle('E'.$contFila)->applyFromArray($styleFondoGris);
			$objPHPExcel->getActiveSheet()->getStyle('G'.$contFila)->applyFromArray($styleFondoGris);
			
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, $spanPlaca.':');
			$objPHPExcel->getActiveSheet()->SetCellValue('B'.$contFila, $row["placa"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$contFila, $spanSerialCarroceria.':');
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$contFila, $row["chasis"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('E'.$contFila, 'Color:');
			$objPHPExcel->getActiveSheet()->SetCellValue('F'.$contFila, $row["color"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('G'.$contFila, $spanKilometraje.':');
			$objPHPExcel->getActiveSheet()->SetCellValue('H'.$contFila, $row["kilometraje_vehiculo"]);
			$contFila++;
						
			//$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);
		}
		
		$arrayIdRegistroPlaca[$row["id_registro_placas"]] = "";
		
		//TABLA ORDEN
		$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila.':H'.$contFila)->applyFromArray($styleBordeArriba);
		$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila.':G'.$contFila)->applyFromArray($styleCentrar);
		$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila.':G'.$contFila)->applyFromArray($styleBold);		

		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, 'Nro');
		$objPHPExcel->getActiveSheet()->SetCellValue('B'.$contFila, 'Tipo');
		$objPHPExcel->getActiveSheet()->SetCellValue('C'.$contFila, 'Estado');
		$objPHPExcel->getActiveSheet()->SetCellValue('D'.$contFila, 'Apertura');
		$objPHPExcel->getActiveSheet()->SetCellValue('E'.$contFila, 'Cierre');
		$objPHPExcel->getActiveSheet()->SetCellValue('F'.$contFila, $spanKilometraje);
		$objPHPExcel->getActiveSheet()->SetCellValue('G'.$contFila, 'Asesor');
		$contFila++;
				
		$objPHPExcel->getActiveSheet()->getStyle('A'.$contFila.':G'.$contFila)->applyFromArray($styleIzquierda);
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, $row["numero_orden"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('B'.$contFila, $row["nombre_tipo_orden"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('C'.$contFila, $row["nombre_estado"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('D'.$contFila, tiempoComun($row["tiempo_orden"]));
		$objPHPExcel->getActiveSheet()->SetCellValue('E'.$contFila, tiempoComun($row["tiempo_finalizado"]));
		$objPHPExcel->getActiveSheet()->SetCellValue('F'.$contFila, $row["kilometraje_vale"]);
		$objPHPExcel->getActiveSheet()->SetCellValue('G'.$contFila, $row["nombre_asesor"]);
		$contFila++;
				
		//TABLA FALLAS
		$html .= "<table width=\"100%\">";
			/*$html .= "<tr class=\"tituloColumna\">";
				$html .= "<td>Falla</td>";
				$html .= "<td>Diagnóstico</td>";
				$html .= "<td>Respuesta</td>";
			$html .= "</tr>";*/

		$queryFallas = sprintf("SELECT 
									descripcion_falla, 
									diagnostico_falla, 
									respuesta_falla
								FROM sa_recepcion_falla
								WHERE id_recepcion = %s", $row["id_recepcion"]);
    	$rs = mysql_query($queryFallas);
    	if (!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFallas); }
		
		while($rowFallas = mysql_fetch_assoc($rs)){
			$objPHPExcel->getActiveSheet()->mergeCells('A'.$contFila.':C'.$contFila);
			$objPHPExcel->getActiveSheet()->mergeCells('D'.$contFila.':F'.$contFila);
			$objPHPExcel->getActiveSheet()->mergeCells('G'.$contFila.':I'.$contFila);
			
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
			
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, $rowFallas["descripcion_falla"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$contFila, $rowFallas["diagnostico_falla"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('G'.$contFila, $rowFallas["respuesta_falla"]);
			$contFila++;
		}
		

		//ITEMS DE LA ORDEN
		$idDocumento = $row["id_orden"];
		$arrayItems = array();
		
		$queryRepuestosGenerales = sprintf("SELECT
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.precio_unitario,
			vw_iv_articulos.codigo_articulo,
			vw_iv_articulos.descripcion
		FROM
			vw_iv_articulos
			INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
		WHERE
			sa_det_orden_articulo.id_orden = %s 
			AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO' 
			AND sa_det_orden_articulo.aprobado = 1",
		valTpDato($idDocumento,"int"));
		
		$rsOrdenDetRep = mysql_query($queryRepuestosGenerales);
    	if (!$rsOrdenDetRep) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryRepuestosGenerales); }
		
		while($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)){			
			$arrayItems[] = array(
							'tipo' => 'REP',
							'codigo' => elimCaracter($rowOrdenDetRep['codigo_articulo'],";"),
							'descripcion' => $rowOrdenDetRep['descripcion'],
							'cantidad' => number_format($rowOrdenDetRep['cantidad'],2,".",","),
							'mecanico' => '');
		}
		
		$queryFactDetTemp = sprintf("SELECT
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			sa_det_orden_tempario.id_modo,
			CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_mecanico,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
			sa_det_orden_tempario.precio_tempario_tipo_orden,
			
			IFNULL(sa_det_orden_tempario.id_mecanico, 0) AS id_mecanico
		FROM
			sa_det_orden_tempario
			INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
			LEFT JOIN sa_mecanicos ON sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico
			LEFT JOIN pg_empleado ON sa_mecanicos.id_empleado = pg_empleado.id_empleado
		WHERE
			sa_det_orden_tempario.id_orden = %s  
			AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
		ORDER BY
			sa_det_orden_tempario.id_paquete",
			valTpDato($idDocumento,"int"));
			
		$rsFactDetTemp = mysql_query($queryFactDetTemp);
    	if (!$rsFactDetTemp) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactDetTemp); }
		
		while($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)){
			$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");
						
			$arrayItems[] = array(
							'tipo' => 'MO',
							'codigo' => $rowFactDetTemp['codigo_tempario'],
							'descripcion' => $rowFactDetTemp['descripcion_tempario'],
							'cantidad' => $caractCantTempario,
							'mecanico' => $rowFactDetTemp['nombre_mecanico']);
		}
		
		$queryDetalleTot = sprintf("SELECT
			sa_orden_tot.monto_subtotal,
			sa_det_orden_tot.id_orden_tot,
			sa_orden_tot.numero_tot,
			sa_orden_tot.observacion_factura,
			sa_det_orden_tot.porcentaje_tot
		FROM
			sa_det_orden_tot
			INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
		WHERE
			sa_det_orden_tot.id_orden = %s 
			AND sa_orden_tot.monto_subtotal > 0",
			valTpDato($idDocumento,"int"));
		
		$rsDetalleTot = mysql_query($queryDetalleTot);
    	if (!$rsDetalleTot) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetalleTot); }
		
		while($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)){
			$arrayItems[] = array(
							'tipo' => 'TOT',
							'codigo' => $rowDetalleTot['numero_tot'],
							'descripcion' => $rowDetalleTot['observacion_factura'],
							'cantidad' => '-',
							'mecanico' => '');
		}
		
		$queryDetTipoDocNotas = sprintf("SELECT
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.id_det_orden_nota
		FROM
			sa_det_orden_notas
		WHERE
			sa_det_orden_notas.id_orden = %s",
			valTpDato($idDocumento,"int"));
			
		$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
    	if (!$rsDetTipoDocNotas) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetTipoDocNotas); }
		
		while($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)){
			$arrayItems[] = array(
							'tipo' => 'Nota/Cargo',
							'codigo' => $rowDetTipoDocNotas['id_det_orden_nota'],
							'descripcion' => $rowDetTipoDocNotas['descripcion_nota'],
							'cantidad' => '-',
							'mecanico' => '');
		}
		
		
		$html .= "<table width=\"100%\">";
			/*$html .= "<tr class=\"tituloColumna\">";
				$html .= "<td>Tipo</td>";
				$html .= "<td>Código</td>";
				$html .= "<td>Descripción</td>";
				$html .= "<td>Cantidad</td>";
				$html .= "<td>Técnico</td>";
			$html .= "</tr>";*/

		$aux = count($arrayItems)-1;
		foreach($arrayItems as $indice => $items){
			$clase = (fmod($indice, 2) == 0) ? "trResaltar5" : "trResaltar4";

			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$contFila, $items["tipo"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('B'.$contFila, $items["codigo"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$contFila, $items["descripcion"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$contFila, $items["cantidad"]);
			$objPHPExcel->getActiveSheet()->SetCellValue('E'.$contFila, $items["mecanico"]);
			
			$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			
			if($aux != $indice){
				$contFila++;
			}			
		}
		
		//FINAL RAYA TABLA		
		$html .= "<table width=\"100%\">";
			$html .= "<tr><td colspan=\"20\"><hr /></td></tr>";
		$html .= "</table>";

    }
	
		$tituloDcto = "Informe Historial Unidad";
	
	// Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$tituloDcto.'.xlsx"');
	header('Cache-Control: max-age=0');
	 
	//Creamos el Archivo .xlsx
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$objWriter->save('php://output');

    //return ($html);
}

function tiempoComun($tiempo){
    if($tiempo != "" && $tiempo != " "){
        return date("d-m-Y H:i",strtotime($tiempo));
    }
}

function cabeceraExcel($objPHPExcel, $idEmpresa, $colHasta, $verDatosEmpresa = true, $cantFilasInsert = 8) {
	global $styleArrayColumna;
	global $spanRIF;
	
	$colHasta2 = ord($colHasta);
	if ($colHasta2 == 68) { // ASCII 65 = A
		$colHasta2 -= 1;
	} else if ($colHasta2 == 69) {
		$colHasta2 -= 2;
	} else if ($colHasta2 == 70) {
		$colHasta2 -= 3;
	} else if ($colHasta2 == 71) {
		$colHasta2 -= 4;
	} else if ($colHasta2 >= 72) {
		$colHasta2 -= 5;
	}
	$colHasta2 = chr($colHasta2);
	
	for ($col = "A"; $col <= $colHasta; $col++) {
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
	
	//Titulo del libro y seguridad
	if ($totalRowsEmp > 0 && $verDatosEmpresa == true) {
		$lastColumn = $objPHPExcel->getActiveSheet()->getHighestColumn();
		$lastRow = $objPHPExcel->getActiveSheet()->getHighestRow();
		for ($col = "A"; $col <= $lastColumn; $col++) {
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
		}
		
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C1", utf8_encode($rowEmp['nombre_empresa']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C2", utf8_encode($spanRIF.": ".$rowEmp['rif']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C3", utf8_encode($rowEmp['direccion']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C4", utf8_encode("Telf.: ".$rowEmp['telefono1']." ".$rowEmp['telefono2']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit("C5", utf8_encode($rowEmp['web']), PHPExcel_Cell_DataType::TYPE_STRING);
		$objPHPExcel->getActiveSheet()->getCell("C5")->getHyperlink()->setUrl("http://".$rowEmp['web']);
		
		$colHasta3 = ord($colHasta2);
		$colHasta3++;
		$colHasta3 = chr($colHasta3);
		$objPHPExcel->getActiveSheet()->setCellValueExplicit($colHasta3."1", "Generado".((strlen($_SESSION['nombreEmpleadoSysGts']) > 0) ? " por: ".$_SESSION['nombreEmpleadoSysGts']." el " : ": ").date("d-m-Y h:i a"), PHPExcel_Cell_DataType::TYPE_STRING);
		
//		$objPHPExcel->getActiveSheet()->getStyle($colHasta3."1:".$colHasta."1")->applyFromArray($styleArrayColumna);
	
		$objPHPExcel->getActiveSheet()->mergeCells("C1:".$colHasta2."1");
		$objPHPExcel->getActiveSheet()->mergeCells($colHasta3."1:".$colHasta."1");
		
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
?>