<?php

//change
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
		$sqlBusq .= $cond.sprintf("(ped_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = ped_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(estado_pedido = %s)",
		valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ped_vent.id_pedido LIKE %s
		OR ped_vent.numeracion_pedido LIKE %s
		OR ped_vent.id_presupuesto LIKE %s
		OR ped_vent.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		ped_vent.fecha,
		CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
		ped_vent.id_cliente,
		cliente.ci AS ci_cliente,
		CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.placa,
		
		(ped_vent.precio_venta
			+ IFNULL(ped_vent.precio_venta * (ped_vent.porcentaje_iva + ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
		
		ped_vent.porcentaje_inicial,
		ped_vent.inicial AS monto_inicial,
		ped_vent.total_inicial_gastos AS total_general,
		
		(SELECT COUNT(fac_vent_det_vehic.id_factura_detalle_vehiculo)
		FROM cj_cc_factura_detalle_vehiculo fac_vent_det_vehic
			INNER JOIN cj_cc_encabezadofactura fact_vent ON (fac_vent_det_vehic.id_factura = fact_vent.idFactura)
		WHERE fact_vent.numeroPedido = ped_vent.id_pedido
			AND anulada <> 'SI'
			AND fact_vent.idDepartamentoOrigenFactura = 2) AS cant_vehic,
		
		pres_vent.estado AS estado_presupuesto,
		ped_vent.estado_pedido,
		CONCAT_WS(' ',empleado.nombre_empleado,empleado.apellido) AS asesor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido ped_vent
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica)
		LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
		LEFT JOIN an_uni_bas uni_bas ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
			LEFT JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN pg_empleado empleado ON (ped_vent.asesor_ventas = empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryFinal = sprintf("%s %s ", $query, $sqlOrd);

	
	$rsFinal = mysql_query($queryFinal);
	if (!$rsFinal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}


	
$contFila = 0;
$arrayTotal = NULL;
$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Estatus Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Presuspuesto");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Vehiculo");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Asesor");



$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rsFinal)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;

	//Verfificandoel tipo de estado a traves del pedido

	$EstatusPedido = "";
	switch ($row['estado_pedido']) {
		case 1 : $EstatusPedido = "Pedido Autorizado"; break;
		case 2 : $EstatusPedido = "Facturado"; break;
		case 3 : $EstatusPedido = "Pedido Desautorizado"; break;
		case 4 : $EstatusPedido = "Factura (Con Devolución)"; break;
		case 5 : $EstatusPedido = "Anulado"; break;
	}


	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $EstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, date(spanDateFormat, strtotime($row['fecha'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, $row['numeracion_pedido'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, $row['numeracion_presupuesto'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['id_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['nombre_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("I".$contFila, $row['vehiculo']);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("J".$contFila,$row['serial_carroceria'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, $row['placa'], PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, (($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO"));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, utf8_encode($row['asesor']));
	
	//AQUI
		
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":M".$ultimo);
	
$contFila++;



$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":M".$contFila);

for ($col = "A"; $col != "N"; $col++) { 
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M");

$tituloDcto = "Histórico Documento Venta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:U7");

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

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