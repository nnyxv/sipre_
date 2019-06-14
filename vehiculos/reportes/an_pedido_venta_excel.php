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

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("estado_pedido IN (1,2,3,4)");

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("(
	(SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
	WHERE acc_ped.id_pedido = ped_vent.id_pedido
		AND acc_ped.estatus_accesorio_pedido = 0) > 0
	OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
		WHERE paq_ped.id_pedido = ped_vent.id_pedido AND paq_ped.estatus_paquete_pedido = 0) > 0
	OR (SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica
			AND uni_fis.estado_venta = 'RESERVADO') > 0)");

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
	if ($valCadBusq[3] == "00") {
		$sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
	} else if ($valCadBusq[3] == "22") {
		$sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
	} else if ($valCadBusq[3] == "33") {
		$sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
	} else {
		$sqlBusq .= $cond.sprintf("(ped_vent.estado_pedido = %s)",
			valTpDato($valCadBusq[3], "int"));
	}
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(ped_vent.id_pedido LIKE %s
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
		valTpDato("%".$valCadBusq[4]."%", "text"));
}

$query = sprintf("SELECT 
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
	pres_vent.id_presupuesto,
	pres_vent.numeracion_presupuesto,
	cxc_fact.idFactura AS id_factura_reemplazo,
	cxc_fact.numeroFactura AS numero_factura_reemplazo,
	ped_vent.fecha,
	pres_vent_acc.id_presupuesto_accesorio,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	
	(ped_vent.precio_venta
		+ IFNULL(ped_vent.precio_venta * (ped_vent.porcentaje_iva + ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
	
	ped_vent.porcentaje_inicial,
	ped_vent.inicial AS monto_inicial,
	ped_vent.total_inicial_gastos AS total_general,
	
	(SELECT an_factura_venta.tipo_factura FROM an_factura_venta
	WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
		AND (SELECT COUNT(an_factura_venta.numeroPedido) FROM an_factura_venta
		WHERE an_factura_venta.numeroPedido = ped_vent.id_pedido
			AND tipo_factura IN (1,2)) = 1) AS tipo_factura,
	
	pres_vent.estado AS estado_presupuesto,
	ped_vent.estado_pedido,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM an_pedido ped_vent
	INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
	LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
	LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
	LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
	LEFT JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		LEFT JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (ped_vent.id_factura_cxc = cxc_fact.idFactura) %s
ORDER BY ped_vent.id_pedido DESC", $sqlBusq);

$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Estado");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Empresa");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Fecha");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nro. Presupuesto");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Nro. Presupuesto Accesorios");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Id Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Cliente");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Vehículo");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Tipo de Pago");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Precio Venta");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, "% ".$spanInicial);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $spanInicial);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, "Total General");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
		
	$imgEstatusPedido = "";
	if ($row['estado_presupuesto'] == 0 && $row['estado_presupuesto'] != "") {
		$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
	} else if ($row['estado_presupuesto'] == 1 || $row['estado_presupuesto'] == "") {
		switch ($row['estado_pedido']) {
			case 1 : $imgEstatusPedido = "Pedido Autorizado"; break;
			case 2 : $imgEstatusPedido = "Facturado"; break;
			case 3 : $imgEstatusPedido = "Pedido Desautorizado"; break;
			case 4 : $imgEstatusPedido = "Nota de Crédito"; break;
			case 5 : $imgEstatusPedido = "Anulado"; break;
		}
	} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
		$imgEstatusPedido = "Presupuesto Anulado";
	} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
		$imgEstatusPedido = "Presupuesto Desautorizado";
	}	
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgEstatusPedido);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, utf8_encode($row['nombre_empresa']));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, date(spanDateFormat, strtotime($row['fecha'])));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("D".$contFila, utf8_encode($row['numeracion_pedido']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("E".$contFila, utf8_encode($row['numeracion_presupuesto']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['id_presupuesto_accesorio']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['id_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, utf8_encode($row['ci_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, utf8_encode($row['nombre_cliente']));
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['vehiculo']));
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("K".$contFila, utf8_encode($row['serial_carroceria']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValueExplicit("L".$contFila, utf8_encode($row['placa']), PHPExcel_Cell_DataType::TYPE_STRING);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, ($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO");
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $row['precio_venta']);
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['porcentaje_inicial']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $row['monto_inicial']);
	$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $row['total_general']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":Q".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);	
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	
	$arrayTotal[7] = $cantFact++;
	$arrayTotal[19] += $row['precio_venta'];
	$arrayTotal[20] += $row['monto_inicial'];
	$arrayTotal[21] += $row['total_general'];
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":Q".$ultimo);
	
$contFila++;
$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Total:");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, $arrayTotal[19]);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, $arrayTotal[20]);
$objPHPExcel->getActiveSheet()->setCellValue("Q".$contFila, $arrayTotal[21]);

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->applyFromArray($styleArrayCampo);
$objPHPExcel->getActiveSheet()->getStyle("D".$contFila.":"."Q".$contFila)->applyFromArray($styleArrayResaltarTotal);

$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
$objPHPExcel->getActiveSheet()->getStyle("Q".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');

$objPHPExcel->getActiveSheet()->mergeCells("A".$contFila.":C".$contFila);

for ($col = "A"; $col != "Q"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "Q");

$tituloDcto = "Listado Pedido Venta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:Q7");

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