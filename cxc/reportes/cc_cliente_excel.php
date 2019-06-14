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

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$rs = mysql_query(sprintf("SELECT IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa FROM vw_iv_empresas_sucursales vw_iv_emp_suc WHERE vw_iv_emp_suc.id_empresa_reg = %s;", valTpDato($valCadBusq[0], "int")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayEmpresa[] = $row['nombre_empresa'];
	}
	$arrayCriterioBusqueda[] = "Empresa: ".((isset($arrayEmpresa)) ? implode(", ", $arrayEmpresa) : "");
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$lstTipoPago = array("no" => "Contado", "si" => "Crédito");
	foreach ($lstTipoPago as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[1]))) {
			$arrayTipoPago[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Tipo de Dcto.: ".((isset($arrayTipoPago)) ? implode(", ", $arrayTipoPago) : "");
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$lstEstatus = array("Activo" => "Activo", "Inactivo" => "Inactivo");
	foreach ($lstEstatus as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[2]))) {
			$arrayEstatus[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Estatus: ".((isset($arrayEstatus)) ? implode(", ", $arrayEstatus) : "");
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$lstPagaImpuesto = array(0 => "No", 1 => "Si");
	foreach ($lstPagaImpuesto as $indice => $valor) {
		if (in_array($indice, explode(",", $valCadBusq[3]))) {
			$arrayPagaImpuesto[] = $valor;
		}
	}
	$arrayCriterioBusqueda[] = "Paga Impuesto: ".((isset($arrayPagaImpuesto)) ? implode(", ", $arrayPagaImpuesto) : "");
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$rs = mysql_query(sprintf("SELECT * FROM pg_modulos WHERE descripcionModulo IN (%s);", valTpDato($valCadBusq[5], "text")));
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayModulo[] = $row['descripcionModulo'];
	}
	$arrayCriterioBusqueda[] = "Módulo: ".((isset($arrayModulo)) ? implode(", ", $arrayModulo) : "");
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$arrayCriterioBusqueda[] = "Criterio: ".$valCadBusq[6];
}

$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");

////////// CRITERIO DE BUSQUEDA //////////
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa LIKE %s",
		valTpDato($valCadBusq[0], "text"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("credito LIKE %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status LIKE %s",
		valTpDato($valCadBusq[2], "text"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("paga_impuesto = %s ",
		valTpDato($valCadBusq[3], "boolean"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipocliente IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$arrayBusq = "";
	if (in_array(1, explode(",",$valCadBusq[5]))) { // Prospecto
		$arrayBusq[] = sprintf("((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0 AND tipo_cuenta_cliente = 1)");
	}
	if (in_array(2, explode(",",$valCadBusq[5]))) { // Prospecto Aprobado (Cliente)
		$arrayBusq[] = sprintf("((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0 AND tipo_cuenta_cliente = 2)");
	}
	if (in_array(3, explode(",",$valCadBusq[5]))) { // Cliente Sin Prospectación
		$arrayBusq[] = sprintf("((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) = 0 AND tipo_cuenta_cliente = 2)");
	}
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
	OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
	OR cliente.nit LIKE %s
	OR cliente.licencia LIKE %s
	OR cliente.telf LIKE %s
	OR cliente.correo LIKE %s
	OR perfil_prospecto.compania LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

//iteramos para los resultados
$query = sprintf("SELECT DISTINCT
	cliente.id,
	cliente.tipo,
	cliente.nombre,
	cliente.apellido,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	cliente.nit AS nit_cliente,
	cliente.licencia AS licencia_cliente,
	cliente.direccion,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
	cliente.credito,
	cliente.status,
	cliente.tipocliente,
	cliente.bloquea_venta,
	cliente.paga_impuesto,
	perfil_prospecto.compania,
	(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
	(CASE cliente.tipo_cuenta_cliente
		WHEN (1) THEN
			1
		WHEN (2) THEN
			IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
	END) AS tipo_cuenta_cliente,
	(CASE cliente.tipo_cuenta_cliente
		WHEN (1) THEN
			'Prospecto'
		WHEN (2) THEN
			IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
				'Prospecto Aprobado (Cliente Venta)',
				'Sin Prospectación (Cliente Post-Venta)')
	END) AS descripcion_tipo_cuenta_cliente,
	vw_pg_empleado.nombre_empleado
FROM cj_cc_cliente cliente
	LEFT JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s
ORDER BY cliente.id DESC", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$contFila = 0;

$contFila++;
$primero = $contFila;

$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, "");
$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, "Id");
$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $spanClienteCxC);
$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, "Nombre");
$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, "Apellido");
$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, "Direccion");
$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, "Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, "Otro Teléfono");
$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, "Correo Electrónico");
$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, "Paga Impuesto");
$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, "Tipo");
$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, "Tipo de Pago");

$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($styleArrayColumna);

while ($row = mysql_fetch_assoc($rs)) {
	$clase = (fmod($contFila, 2) == 0) ? $styleArrayFila1 : $styleArrayFila2;
	$contFila++;
	
	switch($row['status']) {
		case "Inactivo" : $imgEstatus = "Inactivo"; break;
		case "Activo" : $imgEstatus = "Activo"; break;
		default : $imgEstatus = "Inactivo";
	}
	
	switch ($row['tipo_cuenta_cliente']) {
		case 1 : $imgTipoCuentaCliente = "Prospecto"; break;
		case 2 : $imgTipoCuentaCliente = "Prospecto Aprobado (Cliente Venta)"; break;
		default : $imgTipoCuentaCliente = "Sin Prospectación (Cliente Post-Venta)"; break;
	}
	
	$objPHPExcel->getActiveSheet()->setCellValue("A".$contFila, $imgEstatus);
	$objPHPExcel->getActiveSheet()->setCellValue("B".$contFila, $imgTipoCuentaCliente);
	$objPHPExcel->getActiveSheet()->setCellValue("C".$contFila, $row['id']);
	$objPHPExcel->getActiveSheet()->setCellValue("D".$contFila, $row['ci_cliente']);
	$objPHPExcel->getActiveSheet()->setCellValue("E".$contFila, utf8_encode($row['nombre']));
	$objPHPExcel->getActiveSheet()->setCellValue("F".$contFila, utf8_encode($row['apellido']));
	$objPHPExcel->getActiveSheet()->setCellValue("G".$contFila, utf8_encode($row['direccion']));
	$objPHPExcel->getActiveSheet()->setCellValue("H".$contFila, $row['telf']);
	$objPHPExcel->getActiveSheet()->setCellValue("I".$contFila, $row['otrotelf']);
	$objPHPExcel->getActiveSheet()->setCellValue("J".$contFila, utf8_encode($row['correo']));
	$objPHPExcel->getActiveSheet()->setCellValue("K".$contFila, (($row['paga_impuesto'] == 1) ? "SI" : "NO"));
	$objPHPExcel->getActiveSheet()->setCellValue("L".$contFila, $row['tipo']);
	$objPHPExcel->getActiveSheet()->setCellValue("M".$contFila, $arrayTipoPago[strtoupper($row['credito'])]);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila.":M".$contFila)->applyFromArray($clase);
	
	$objPHPExcel->getActiveSheet()->getStyle("A".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("B".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("C".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("D".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$objPHPExcel->getActiveSheet()->getStyle("E".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("F".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("G".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("H".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("I".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("J".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet()->getStyle("K".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("L".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet()->getStyle("M".$contFila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
$ultimo = $contFila;
$objPHPExcel->getActiveSheet()->setAutoFilter("A".$primero.":M".$ultimo);

for ($col = "A"; $col != "L"; $col++) {
	$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

cabeceraExcel($objPHPExcel, $idEmpresa, "M", true, ((isset($arrayCriterioBusqueda)) ? 9 : 8));

$tituloDcto = "Clientes";
$objPHPExcel->getActiveSheet()->setCellValue("A7", $tituloDcto);
$objPHPExcel->getActiveSheet()->getStyle("A7")->applyFromArray($styleArrayTitulo);
$objPHPExcel->getActiveSheet()->mergeCells("A7:L7");

if (isset($arrayCriterioBusqueda)) {
	$objPHPExcel->getActiveSheet()->setCellValue("A9", "Búsqueda por: ".implode("     ", $arrayCriterioBusqueda));
	$objPHPExcel->getActiveSheet()->getStyle("A9")->applyFromArray($styleArrayTitulo);
	$objPHPExcel->getActiveSheet()->mergeCells("A9:L9");
}

//Titulo del libro y seguridad
$objPHPExcel->getActiveSheet()->setTitle(substr($tituloDcto,0,30));
$objPHPExcel->getActiveSheet()->getSheetView()->setZoomScale(90);
//$objPHPExcel->getSecurity()->setLockWindows(true);
//$objPHPExcel->getSecurity()->setLockStructure(true);

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