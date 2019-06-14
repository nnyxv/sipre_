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

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
 
//Trabajamos con la hoja activa principal
$objPHPExcel->setActiveSheetIndex(0);

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("te_estado_cuenta.estados_principales != 0");
	
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("te_estado_cuenta.id_cuenta = %s",
	valTpDato($valCadBusq[1], "int"));
	
if ($valCadBusq[2] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusqFecha .= $cond.sprintf("DATE(te_estado_cuenta.fecha_registro) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
		valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.estados_principales = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.tipo_documento = %s",
		valTpDato($valCadBusq[5], "text"));
}
	
$querySaldo = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = %s",
	valTpDato($valCadBusq[1], "int"));
$rsSaldo = mysql_query($querySaldo);
if (!$rsSaldo) return die(mysql_error()."\n\nLine: ".__LINE__);
$rowSaldo = mysql_fetch_assoc($rsSaldo);
$saldo = $rowSaldo['saldo'];

//iteramos para los resultados

$query = sprintf("SELECT 
	te_estado_cuenta.id_estado_cuenta,
	te_estado_cuenta.id_empresa,
	te_estado_cuenta.tipo_documento,
	te_estado_cuenta.id_documento,
	te_estado_cuenta.fecha_registro,
	te_estado_cuenta.id_cuenta,
	te_estado_cuenta.id_empresa,
	te_estado_cuenta.monto,                                                       
	te_estado_cuenta.suma_resta,
	te_estado_cuenta.numero_documento,
	te_estado_cuenta.desincorporado,
	te_estado_cuenta.observacion,
	te_estado_cuenta.estados_principales,
	#SOLO PARA ORDENAMIENTO
	if(suma_resta = 0, monto, 0)as debito,                                                  
	if(suma_resta = 1, monto, 0)as credito     
FROM te_estado_cuenta %s %s
ORDER BY te_estado_cuenta.id_estado_cuenta ASC", $sqlBusq, $sqlBusqFecha);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "Fecha Registro");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "T.D.");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, "Nro. Documento");
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Beneficiario");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Descripción");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Débito");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Crédito");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Saldo");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":I".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	if ($row['estados_principales'] == 1) {
		$titleEstado = "Por Aplicar";
	} else if ($row['estados_principales'] == 2) {
		$titleEstado = "Aplicado";
	} else if ($row['estados_principales'] == 3) {
		$titleEstado = "Conciliado";
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $titleEstado);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, date(spanDateFormat." h:i a", strtotime($row['fecha_registro'])));
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, tipoDocumento($row['id_estado_cuenta']));
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, ($row['numero_documento']));
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode(strtoupper(Beneficiario($row['tipo_documento'],$row['id_documento']))));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['observacion']));
	if ($row['suma_resta'] == 0) {
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, $row['monto']);
		$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, 0);
		
		$saldo = $saldo - $row['monto'];
		$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $saldo);
		
		$conta += $row['monto'];
	} else {
		$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, 0);
	}
	if ($row['suma_resta'] == 1) {
		if ($row['tipo_documento'] == "CH ANULADO") {
			$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, 0);
			$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $saldo);
		} else {
			$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['monto']);
			
			$saldo = $saldo + $row['monto'];
			$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $saldo);
			
			$contb += $row['monto'];
		}
	}
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":I".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getNumberFormat()->setFormatCode('"'.$row['abreviacion_moneda_local'].'"#,##0.00');
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":I".$ultimo);
	
for ($col = "A"; $col != "I"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "I", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Estado de Cuenta";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:I7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:I9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
$objPHPExcel->getSecurity()->setLockWindows(true);
$objPHPExcel->getSecurity()->setLockStructure(true);

$objPHPExcel->getProperties()->setCreator("SIPRE 3.0");
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


function tipoDocumento($idEstadoCuenta){	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$idEstadoCuenta);

	$rs = mysql_query($query);
	if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if($row['tipo_documento'] == 'NC'){
		$queryNC = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = %s", 
			valTpDato($row['id_documento'], "int"));
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return die(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_assoc($rsNC);
		if($rowNC['tipo_nota_credito'] == '1'){
			$respuesta = "NC";
		}else if($rowNC['tipo_nota_credito'] == '2'){
			$respuesta = "NC/TD";
		}else if($rowNC['tipo_nota_credito'] == '3'){
			$respuesta = "NC/TC";
		}else if($rowNC['tipo_nota_credito'] == '4'){
			$respuesta = "NC/TR";
		}
	}else{
		$respuesta = $row['tipo_documento']; //DP, ND, TR, CH, CH ANULADO
	}
	
	return $respuesta;
}

function beneficiario($tipoDocumento, $id){
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s",
		valTpDato($id, "int"));

	$rs = mysql_query($query);
	if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if($tipoDocumento == 'DP'){
		$respuesta = utf8_decode("DEPÓSITO");
	}	
	if($tipoDocumento == 'NC'){
		$respuesta = utf8_decode("NOTA DE CRÉDITO");
	}			
	if($tipoDocumento == 'ND'){
		$respuesta = utf8_decode("NOTA DE DÉBITO");
	}
		
	if($tipoDocumento == 'TR'){		
		$query = sprintf("SELECT * FROM te_transferencia WHERE id_transferencia = %s",
			valTpDato($id, "int"));
		$rs = mysql_query($query);
		if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if($row['beneficiario_proveedor'] == 1){
			$respuesta = nombreP($row['id_beneficiario_proveedor']);	
		}else{
			$respuesta = nombreB($row['id_beneficiario_proveedor']);
		}
	}
	
	if($tipoDocumento == 'CH'){		
		$query = sprintf("SELECT * FROM te_cheques WHERE id_cheque = %s",
			valTpDato($id, "int"));
		$rs = mysql_query($query);
		if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if($row['beneficiario_proveedor'] == 1){
			$respuesta = nombreP($row['id_beneficiario_proveedor']);
		}else{
			$respuesta = nombreB($row['id_beneficiario_proveedor']);
		}
	}
	
	if($tipoDocumento == 'CH ANULADO'){			
		$query = sprintf("SELECT * FROM te_cheques_anulados WHERE id_cheque = %s",
			valTpDato($id, "int"));
		$rs = mysql_query($query);
		if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
	
		if($row['beneficiario_proveedor'] == 1){
			$respuesta = nombreP($row['id_beneficiario_proveedor']);
		}else{		
			$respuesta = nombreB($row['id_beneficiario_proveedor']);
		}
	}
	
	return $respuesta;
}

function nombreB($id){
	$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$id);
	$rsBeneficiario = mysql_query($queryBeneficiario);
	if (!$rsBeneficiario) return die(mysql_error()."\n\nLine: ".__LINE__);
	$rowBeneficiario = mysql_fetch_assoc($rsBeneficiario);
	
	$respuesta = $rowBeneficiario['nombre_beneficiario'];

	return $respuesta;
}

function nombreP($id){	
	$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$id);
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) return die(mysql_error()."\n\nLine: ".__LINE__);
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$respuesta = $rowProveedor['nombre'];
	
	return $respuesta;
}

?>