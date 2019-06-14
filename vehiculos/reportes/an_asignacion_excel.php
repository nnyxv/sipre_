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

$idAsignacion = $_GET['idAsignacion'];

// BUSCA LOS DATOS DEL PEDIDO
$queryAsignacion = sprintf("SELECT * FROM an_asignacion
WHERE idAsignacion = %s",
	valTpDato($idAsignacion, "int"));
$rsAsignacion = mysql_query($queryAsignacion);
if (!$rsAsignacion) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowAsignacion = mysql_fetch_assoc($rsAsignacion);
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "Id. Asignacion");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Nro. Referencia");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Id. Unidad ");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Código Unidad");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Id. Cliente ");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Asignados");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Aceptados");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Confirmados");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Costo Unit.");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Tipo Pago");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($styleArrayColumna);
 
//iteramos para los resultados
$query = sprintf("SELECT 
	asig_det.*,
	vw_an_uni_bas.nom_uni_bas,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
FROM an_det_asignacion asig_det
	INNER JOIN vw_an_unidad_basica vw_an_uni_bas ON (asig_det.idUnidadesBasicas = vw_an_uni_bas.id_uni_bas)
	LEFT JOIN cj_cc_cliente cliente ON (asig_det.idCliente = cliente.id)
WHERE asig_det.idAsignacion = %s
ORDER BY asig_det.idDetalleAsignacion ASC;",
	valTpDato($idAsignacion, "int"));
$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila2, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$claseHabilitado = (fmod($contFila2, 2) == 0) ? $styleArrayCampoHabilitado1 : $styleArrayCampoHabilitado2;
	$contFila++;
	$contFila2++;
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $rowAsignacion['idAsignacion']);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $rowAsignacion['referencia_asignacion']);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['idUnidadesBasicas']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['nom_uni_bas']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['id_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['ci_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['cantidadAsignada']);
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['cantidadAceptada']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['cantidadConfirmada']);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($styleArrayCampo);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->applyFromArray($claseHabilitado);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); // Se debe establecer en true para permitir cualquier protección de la hoja!
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
	
	for ($contFila3 = 1; $contFila3 <= $row['cantidadConfirmada']; $contFila3++) {
		$contFila++;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $rowAsignacion['idAsignacion']);
		$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $rowAsignacion['referencia_asignacion']);
		$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['idUnidadesBasicas']);
		$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['nom_uni_bas']);
		$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, $row['id_cliente']);
		$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, $row['ci_cliente']);
		
		$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":K".$contFila)->applyFromArray($clase);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->applyFromArray($claseHabilitado);
		$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
		
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
		$objValidation->setFormula1('"'.cargaLstFormaPagoItm().'"');
	}
}
$ultimo = $contFila;
//$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":J".$ultimo);

for ($col = "A"; $col != "K"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}
$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setVisible(false);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setVisible(false);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setVisible(false);

$tituloDcto = "Asignación Nro. Ref. ".$rowAsignacion['referencia_asignacion'];

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

function cargaLstFormaPagoItm() {
	$query = sprintf("SELECT 
		forma_pago.idFormaPagoAsignacion,
		prov.id_proveedor,
		(CASE
			WHEN prov.nombre IS NOT NULL THEN
				CONCAT_WS(' ', forma_pago.descripcionFormaPagoAsignacion, prov.nombre)
			ELSE
				forma_pago.descripcionFormaPagoAsignacion
		END) AS descripcion_forma_pago,
		forma_pago.alias,
		prov_cred.planMayor
	FROM cp_proveedor prov
		RIGHT JOIN formapagoasignacion forma_pago ON (prov.id_proveedor = forma_pago.idProveedor)
		LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	WHERE (prov.status = 'Activo' OR prov.status IS NULL)
		AND (prov_cred.planMayor = 1 OR prov_cred.planMayor IS NULL)
		AND (prov.credito = 'Si' OR prov.credito IS NULL)
	GROUP BY forma_pago.idFormaPagoAsignacion
	ORDER BY planMayor, forma_pago.descripcionFormaPagoAsignacion, prov.nombre");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayFormaPagoItm[] = str_replace(","," ",utf8_encode($row['idFormaPagoAsignacion'].") ".$row['descripcion_forma_pago']));
	}
	
	return ((count($arrayFormaPagoItm) > 0) ? implode(",",$arrayFormaPagoItm) : "");
}
?>