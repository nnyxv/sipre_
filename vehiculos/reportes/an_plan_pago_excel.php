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

$idPedido = $_GET['idPedido'];

// BUSCA LOS DATOS DEL PEDIDO
$queryPedido = sprintf("SELECT * FROM an_pedido_compra ped_comp
WHERE ped_comp.idPedidoCompra = %s",
	valTpDato($idPedido, "int"));
$rsPedido = mysql_query($queryPedido);
if (!$rsPedido) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPedido = mysql_fetch_assoc($rsPedido);
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Nro. Pedido");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Id. Detalle");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Id. Unidad ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Código Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Id. Cliente ");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $spanSerialCarroceria);
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $spanSerialMotor);
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Nro. Vehículo");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Condición");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Almacén");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Color Externo 1");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Color Interno 1");
$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, "Año");
$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $spanPlaca);
$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, "Estatus");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($styleArrayColumna);
 
//iteramos para los resultados
$query = sprintf("SELECT
	ped_comp.idPedidoCompra,
	
	ped_comp_det.idSolicitud,
	uni_fis.id_unidad_fisica,
	uni_bas.id_uni_bas,
	uni_bas.nom_uni_bas,
	modelo.nom_modelo,
	vers.nom_version,
	ped_comp_det.flotilla,
	
	forma_pago_asig.idFormaPagoAsignacion,
	prov.id_proveedor,
	prov.nombre,
	(CASE
		WHEN (descripcionFormaPagoAsignacion IS NULL OR descripcionFormaPagoAsignacion = '') THEN
			prov.nombre
		WHEN (descripcionFormaPagoAsignacion IS NOT NULL AND descripcionFormaPagoAsignacion <> '') THEN
			forma_pago_asig.descripcionFormaPagoAsignacion
	END) AS descripcionFormaPagoAsignacion,
	
	ped_comp_det.estado,
	
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	
	(uni_fis.estado_venta + 0) AS estado_venta,
	uni_fis.estado_compra,
	
	ano.id_ano,
	uni_fis.id_condicion_unidad,
	uni_fis.id_color_externo1,
	uni_fis.id_color_interno1,
	uni_fis.id_almacen,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	
	(CASE estado_compra
		WHEN 'COMPRADO' THEN
			(SELECT fact_comp_det_unidad.id_factura_compra FROM an_factura_compra_detalle_unidad fact_comp_det_unidad
			WHERE fact_comp_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		WHEN 'REGISTRADO' THEN
			(SELECT fact_det_unidad.id_factura FROM cp_factura_detalle_unidad fact_det_unidad
			WHERE fact_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad)
	END) AS id_factura_compra,
	
	(SELECT retencion.idRetencionCabezera
	FROM cp_factura_detalle_unidad fact_comp_det_unidad
		INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
		INNER JOIN cp_retenciondetalle retencion_det ON (fact_comp_det_unidad.id_factura = retencion_det.idFactura)
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad
	LIMIT 1) AS idRetencionCabezera,
	
	(SELECT id_notacargo FROM cp_notadecargo
	WHERE id_detalles_pedido_compra = ped_comp_det.idSolicitud) AS id_nota_cargo,
	
	(SELECT numero_notacargo FROM cp_notadecargo
	WHERE id_detalles_pedido_compra = ped_comp_det.idSolicitud) AS numero_nota_cargo
		
FROM cp_proveedor prov
	RIGHT JOIN formapagoasignacion forma_pago_asig ON (prov.id_proveedor = forma_pago_asig.idProveedor)
	INNER JOIN an_solicitud_factura ped_comp_det ON (forma_pago_asig.idFormaPagoAsignacion = ped_comp_det.idFormaPagoAsignacion)
	LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
	INNER JOIN an_uni_bas uni_bas ON (ped_comp_det.idUnidadBasica = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
	LEFT JOIN cj_cc_cliente cliente ON (ped_comp_det.id_cliente = cliente.id)
WHERE ped_comp.idPedidoCompra = %s
	AND forma_pago_asig.idFormaPagoAsignacion NOT IN (4)
ORDER BY ped_comp_det.idSolicitud ASC;",
	valTpDato($idPedido, "int"));
$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$claseHabilitado = (fmod($contFila, 2) == 0) ? $styleArrayCampoHabilitado1 : $styleArrayCampoHabilitado2;
	$contFila++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $rowPedido['idPedidoCompra']);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $row['idSolicitud']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['id_uni_bas']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['nom_uni_bas']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['id_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['ci_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['serial_carroceria']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['serial_motor']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['serial_chasis']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, cargaLstCondicionItm($row['id_condicion_unidad']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, cargaLstAlmacenItm($rowPedido['id_empresa'], $row['id_almacen']));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, cargaLstColorItm($row['id_color_externo1']));
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, cargaLstColorItm($row['id_color_interno1']));
	$objPHPExcel->getActiveSheet()->setCellValue("N".$contFila, cargaLstAnoItm($row['ano']));
	$objPHPExcel->getActiveSheet()->setCellValue("O".$contFila, $row['placa']);
	$objPHPExcel->getActiveSheet()->setCellValue("P".$contFila, cargaLstEstatusItm($row['estado_venta']));
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":P".$contFila)->applyFromArray($clase);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":P".$contFila)->applyFromArray($claseHabilitado);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("N".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("O".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("P".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); // Se debe establecer en true para permitir cualquier protección de la hoja!
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila.":P".$contFila)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
	
	$objValidation = $objPHPExcel->getActiveSheet()->getCell("J".$contFila)->getDataValidation();
	$objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
	$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
	$objValidation->setAllowBlank(false);
	$objValidation->setShowInputMessage(true);
	$objValidation->setShowErrorMessage(true);
	$objValidation->setShowDropDown(true);
	$objValidation->setErrorTitle('Error de entrada');
	$objValidation->setError('El valor no está en la lista');
	$objValidation->setPromptTitle('Elija de la lista');
	$objValidation->setPrompt('Por favor, elija un valor de la lista desplegable');
	$objValidation->setFormula1('"'.cargaLstCondicionItm().'"');
	
	$objValidation = $objPHPExcel->getActiveSheet()->getCell("K".$contFila)->getDataValidation();
	$objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
	$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
	$objValidation->setAllowBlank(false);
	$objValidation->setShowInputMessage(true);
	$objValidation->setShowErrorMessage(true);
	$objValidation->setShowDropDown(true);
	$objValidation->setErrorTitle('Error de entrada');
	$objValidation->setError('El valor no está en la lista');
	$objValidation->setPromptTitle('Elija de la lista');
	$objValidation->setPrompt('Por favor, elija un valor de la lista desplegable');
	$objValidation->setFormula1('"'.cargaLstAlmacenItm($rowPedido['id_empresa']).'"');
	
	$objValidation = $objPHPExcel->getActiveSheet()->getCell("L".$contFila)->getDataValidation();
	$objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
	$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
	$objValidation->setAllowBlank(false);
	$objValidation->setShowInputMessage(true);
	$objValidation->setShowErrorMessage(true);
	$objValidation->setShowDropDown(true);
	$objValidation->setErrorTitle('Error de entrada');
	$objValidation->setError('El valor no está en la lista');
	$objValidation->setPromptTitle('Elija de la lista');
	$objValidation->setPrompt('Por favor, elija un valor de la lista desplegable');
	$objValidation->setFormula1('"'.cargaLstColorItm().'"');
	
	$objValidation = $objPHPExcel->getActiveSheet()->getCell("M".$contFila)->getDataValidation();
	$objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
	$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
	$objValidation->setAllowBlank(false);
	$objValidation->setShowInputMessage(true);
	$objValidation->setShowErrorMessage(true);
	$objValidation->setShowDropDown(true);
	$objValidation->setErrorTitle('Error de entrada');
	$objValidation->setError('El valor no está en la lista');
	$objValidation->setPromptTitle('Elija de la lista');
	$objValidation->setPrompt('Por favor, elija un valor de la lista desplegable');
	$objValidation->setFormula1('"'.cargaLstColorItm().'"');
	
	$objValidation = $objPHPExcel->getActiveSheet()->getCell("N".$contFila)->getDataValidation();
	$objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
	$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
	$objValidation->setAllowBlank(false);
	$objValidation->setShowInputMessage(true);
	$objValidation->setShowErrorMessage(true);
	$objValidation->setShowDropDown(true);
	$objValidation->setErrorTitle('Error de entrada');
	$objValidation->setError('El valor no está en la lista');
	$objValidation->setPromptTitle('Elija de la lista');
	$objValidation->setPrompt('Por favor, elija un valor de la lista desplegable');
	$objValidation->setFormula1('"'.cargaLstAnoItm().'"');
	
	$objValidation = $objPHPExcel->getActiveSheet()->getCell("P".$contFila)->getDataValidation();
	$objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
	$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
	$objValidation->setAllowBlank(false);
	$objValidation->setShowInputMessage(true);
	$objValidation->setShowErrorMessage(true);
	$objValidation->setShowDropDown(true);
	$objValidation->setErrorTitle('Error de entrada');
	$objValidation->setError('El valor no está en la lista');
	$objValidation->setPromptTitle('Elija de la lista');
	$objValidation->setPrompt('Por favor, elija un valor de la lista desplegable');
	$objValidation->setFormula1('"'.cargaLstEstatusItm().'"');
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":P".$ultimo);

for ($col = "A"; $col != "P"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setVisible(false);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setVisible(false);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setVisible(false);

$tituloDcto = "Pedido Nro. ".$rowPedido['idPedidoCompra'];

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

function cargaLstAlmacenItm($idEmpresa, $selId = "-1") {
	$query = sprintf("SELECT * FROM an_almacen
	WHERE an_almacen.id_empresa = %s
	ORDER BY nom_almacen;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		if ($selId == $row['id_almacen']) { return utf8_encode($row['id_almacen'].") ".$row['nom_almacen']); }
		
		$arrayAlmacenItm[] = utf8_encode($row['id_almacen'].") ".$row['nom_almacen']);
	}
	
	return (count($arrayAlmacenItm) > 0 && $selId == "-1") ? implode(",",$arrayAlmacenItm) : "";
}

function cargaLstAnoItm($selId = "-1") {
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano LIMIT 15;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		if ($selId == $row['id_ano']) { return utf8_encode($row['nom_ano']); }
		
		$arrayAnoItm[] = utf8_encode($row['nom_ano']);
	}
	
	return (count($arrayAnoItm) > 0 && $selId == "-1") ? implode(",",$arrayAnoItm) : "";
}

function cargaLstColorItm($selId = "-1") {
	$query = sprintf("SELECT * FROM an_color ORDER BY nom_color LIMIT 15;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		if ($selId == $row['id_color']) { return utf8_encode($row['id_color'].") ".$row['nom_color']); }
		
		$arrayColorItm[] = utf8_encode($row['id_color'].") ".$row['nom_color']);
	}
	
	return (count($arrayColorItm) > 0 && $selId == "-1") ? implode(",",$arrayColorItm) : "";
}

function cargaLstCondicionItm($selId = "-1") {
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		if ($selId == $row['id_condicion_unidad']) { return utf8_encode($row['id_condicion_unidad'].") ".$row['descripcion']); }
		
		$arrayCondicionItm[] = utf8_encode($row['id_condicion_unidad'].") ".$row['descripcion']);
	}
	
	return (count($arrayCondicionItm) > 0 && $selId == "-1") ? implode(",",$arrayCondicionItm) : "";
}

function cargaLstEstatusItm($selId = "-1") {
	$array[2] = "Buen Estado";		$array[3] = "Siniestrado";
	
	foreach ($array as $indice => $valor) {
		if ($selId == $indice) { return utf8_encode($indice.") ".$array[$indice]); }
		
		$arrayEstatusItm[] = utf8_encode($indice.") ".$array[$indice]);
	}
	
	return (count($arrayEstatusItm) > 0 && $selId == "-1") ? implode(",",$arrayEstatusItm) : "";
}
?>