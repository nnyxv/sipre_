<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$maxRows = 18;
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT
	fact_comp.*,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	prov.nombre,
	prov.direccion,
	prov.telefono,
	prov.fax,
	prov.contacto,
	prov.correococtacto,
	fact_comp_imp.total_advalorem,
	vw_pg_empleado.nombre_empleado
FROM cp_factura fact_comp
	INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
	LEFT JOIN cp_factura_importacion fact_comp_imp ON (fact_comp.id_factura = fact_comp_imp.id_factura)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (fact_comp.id_empleado_creador = vw_pg_empleado.id_empleado)
WHERE fact_comp.id_factura = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];
$idModoCompra = $rowEncabezado['id_modo_compra'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$queryClaveMov = sprintf("SELECT
	clave_mov.id_clave_movimiento,
	clave_mov.descripcion,
	(CASE tipo
		WHEN 1 THEN 'COMPRA'
		WHEN 2 THEN 'ENTRADA'
		WHEN 3 THEN 'VENTA'
		WHEN 4 THEN 'SALIDA'
	END) AS tipo_movimiento
FROM an_kardex kardex
	INNER JOIN pg_clave_movimiento clave_mov ON (kardex.claveKardex = clave_mov.id_clave_movimiento)
WHERE kardex.id_documento = %s
	AND clave_mov.tipo = 1;",
	valTpDato($idDocumento,"int"));
$rsClaveMov = mysql_query($queryClaveMov, $conex);
if (!$rsClaveMov) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowClaveMov = mysql_fetch_assoc($rsClaveMov);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$posY = 0;
imagestring($img,1,160,$posY,str_pad("REGISTRO DE COMPRA", 62, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,320,$posY,utf8_decode("ID REG. COMPRA"),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['id_factura'],$textColor);

$posY += 9;
imagestring($img,1,320,$posY,utf8_decode("FECHA REGISTRO"),$textColor);
imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_origen'])),$textColor);

$posY += 9;
imagestring($img,1,160,$posY,utf8_decode("FACT. PROV. NRO."),$textColor);
imagestring($img,1,245,$posY,": ".$rowEncabezado['numero_factura_proveedor'],$textColor);
imagestring($img,1,320,$posY,utf8_decode("NRO. CONTROL"),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['numero_control_factura'],$textColor);

$posY += 9;
imagestring($img,1,160,$posY,utf8_decode("FECHA FACT. PROV."),$textColor);
imagestring($img,1,245,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_factura_proveedor'])),$textColor);

$posY += 9;
imagestring($img,1,160,$posY,utf8_decode("TIPO"),$textColor);////////////////////////////////////////////////////
imagestring($img,1,245,$posY,": ".strtoupper($rowClaveMov['tipo_movimiento']),$textColor);
imagestring($img,1,320,$posY,utf8_decode("CLAVE"),$textColor);////////////////////////////////////////////////////
imagestring($img,1,390,$posY,": ".strtoupper($rowClaveMov['descripcion']),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,str_pad(("DATOS DEL PROVEEDOR"), 94, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,utf8_decode("RAZÓN SOCIAL"),$textColor);
imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre']),$textColor);
imagestring($img,1,280,$posY,utf8_decode($spanProvCxP),$textColor);
imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,utf8_decode("CONTACTO"),$textColor);/////////////////////////////////////////
imagestring($img,1,45,$posY,": ".strtoupper(substr($rowEncabezado['contacto'],0,30)),$textColor);
imagestring($img,1,310,$posY,utf8_decode("EMAIL"),$textColor);///////////////////////////////////////////////////
imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['correococtacto']),$textColor);

$direccionProveedor = strtoupper(str_replace(",", " ", $rowEncabezado['direccion']));
$posY += 9;
imagestring($img,1,0,$posY,utf8_decode("DIRECCIÓN"),$textColor);/////////////////////////////////////////
imagestring($img,1,45,$posY,": ".trim(substr($direccionProveedor,0,48)),$textColor);
imagestring($img,1,310,$posY,utf8_decode("TELÉFONO"),$textColor);///////////////////////////////////////////////////
imagestring($img,1,350,$posY,": ".$rowEncabezado['telefono'],$textColor);

$posY += 9;
imagestring($img,1,55,$posY,trim(substr($direccionProveedor,48,48)),$textColor);
imagestring($img,1,310,$posY,utf8_decode("FAX"),$textColor);///////////////////////////////////////////////////
imagestring($img,1,350,$posY,": ".$rowEncabezado['fax'],$textColor);

// BUSCA LOS DATOS DE LA UNIDAD
$queryUnidad = sprintf("SELECT 
	vw_iv_modelo.nom_uni_bas,
	uni_fis.placa,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	vw_iv_modelo.nom_marca,
	vw_iv_modelo.nom_modelo,
	vw_iv_modelo.nom_version,
	ano.nom_ano,
	uni_fis.id_unidad_fisica,
	uni_fis.registro_legalizacion,
	uni_fis.registro_federal,
	cond_unidad.descripcion AS condicion_unidad,
	uni_fis.kilometraje,
	uso.nom_uso,
	clase.nom_clase,
	color_ext1.nom_color AS color_externo,
	color_int1.nom_color AS color_interno,
	alm.nom_almacen,
	combustible.nom_combustible,
	pais_origen.nom_origen,
	uni_fis.marca_cilindro,
	uni_fis.capacidad_cilindro,
	uni_fis.fecha_elaboracion_cilindro,
	uni_fis.marca_kit,
	uni_fis.modelo_regulador,
	uni_fis.serial_regulador,
	uni_fis.codigo_unico_conversion,
	uni_fis.serial1,
	rec.id_notacredito as reconvercion,
	fact_comp_det_unidad.costo_unitario
FROM cp_factura fact_comp
	LEFT JOIN cp_factura_importacion fact_comp_imp ON (fact_comp.id_factura = fact_comp_imp.id_factura)
	LEFT JOIN cp_reconversion rec on (fact_comp.id_factura = rec.id_factura)
	INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (fact_comp.id_factura = fact_comp_det_unidad.id_factura)
	INNER JOIN an_unidad_fisica uni_fis ON (fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad)
	LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
	INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
	INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
	INNER JOIN an_uni_bas uni_bas ON (vw_iv_modelo.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
	INNER JOIN an_combustible combustible ON (uni_bas.com_uni_bas = combustible.id_combustible)
WHERE fact_comp.id_factura = %s;",
	valTpDato($idDocumento, "int"));
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowUnidad = mysql_fetch_array($rsUnidad);

$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 55, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper($rowUnidad['nom_uni_bas']),$textColor);
imagestring($img,1,115,$posY,utf8_decode("MARCA"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_marca']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("MODELO"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_modelo']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("VERSIÓN"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_version']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("AÑO"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_ano']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,strtoupper(utf8_decode($spanPlaca)),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['placa']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,strtoupper(utf8_decode($spanSerialCarroceria)),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['serial_carroceria']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,strtoupper(utf8_decode($spanSerialMotor)),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['serial_motor']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("NRO. VEHÍCULO"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['serial_chasis']),$textColor);

$posY += 20;
imagestring($img,1,115,$posY,utf8_decode("REGISTRO LEGALIZACIÓN"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['registro_legalizacion']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("REGISTRO FEDERAL"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['registro_federal']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("ESTADO VEHICULO"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['condicion_unidad']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("USO"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_uso']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("CLASE"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_clase']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("COLOR CARROCERIA"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['color_externo']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("TIPO TAPICERIA"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['color_interno']),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("ALMACEN"),$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_almacen']),$textColor);

if (strlen($rowUnidad['nom_origen']) > 0) {
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("ORIGEN"),$textColor);
	imagestring($img,1,220,$posY,": ".strtoupper($rowUnidad['nom_origen']),$textColor);
}

if ($rowUnidad['com_uni_bas'] == 2 || $rowUnidad['com_uni_bas'] == 5) {
	$posY += 20;
	imagestring($img,1,115,$posY,str_pad(utf8_decode("SISTEMA GNV"), 65, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("CÓDIGO UNICO"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['codigo_unico_conversion']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("MARCA KIT"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_kit']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("MARCA CILINDRO"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_cilindro']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("MODELO REGULADOR"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['modelo_regulador']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("SERIAL 1"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial1']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("SERIAL REGULADOR"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial_regulador']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("CAPACIDAD CILINDRO (NG)"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['capacidad_cilindro']),$textColor);
	
	$posY += 10;
	imagestring($img,1,115,$posY,utf8_decode("FECHA ELAB. CILINDRO"),$textColor);
	imagestring($img,1,210,$posY,": ".date(spanDateFormat, strtotime($rowUnidad['fecha_elaboracion_cilindro'])),$textColor);
}

$posY += 10;
imagestring($img,1,115,$posY,str_pad("", 55, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,115,$posY,utf8_decode("MONTO TOTAL"),$textColor);
if ($rowEncabezado['total_advalorem'] > 0) {
	imagestring($img,1,220,$posY,": AD-VALOREM ".str_pad(number_format($rowEncabezado['total_advalorem'], 2, ".", ","), 21, " ", STR_PAD_LEFT),$textColor);
}
imagestring($img,1,395,$posY,str_pad(number_format($rowUnidad['costo_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);

// DETALLES DE LOS ADICIONALES
$queryDetalle = sprintf("SELECT
	IF(iva > 0, acc.nom_accesorio, CONCAT(acc.nom_accesorio, ' (E)')) AS nom_accesorio,
	fact_comp_det_acc.costo_unitario
FROM cp_factura_detalle_accesorio fact_comp_det_acc
	INNER JOIN an_accesorio acc ON (fact_comp_det_acc.id_accesorio = acc.id_accesorio)
WHERE fact_comp_det_acc.id_factura = %s
ORDER BY fact_comp_det_acc.id_factura_detalle_accesorio",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	$posY += 10;
	imagestring($img,1,115,$posY,($rowDetalle['nom_accesorio']),$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($rowDetalle['costo_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}

$posY = 430;

$observacionFactura = preg_replace("/[\"?]/"," ",preg_replace("/[\r?|\n?]/"," ",utf8_encode($rowEncabezado['observacion_factura'])));
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(substr($observacionFactura,0,94)),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(substr($observacionFactura,94,94)),$textColor);

$queryGasto = sprintf("SELECT
	fact_comp_gasto.id_factura_gasto,
	fact_comp_gasto.id_factura,
	fact_comp_gasto.tipo,
	fact_comp_gasto.porcentaje_monto,
	fact_comp_gasto.monto,
	fact_comp_gasto.estatus_iva,
	fact_comp_gasto.id_iva,
	fact_comp_gasto.iva,
	gasto.id_gasto,
	IF ((fact_comp_gasto.id_iva > 0), gasto.nombre, CONCAT_WS(' ', gasto.nombre, '(E)')) AS nombre,
	gasto.id_modo_gasto
FROM pg_gastos gasto
	INNER JOIN cp_factura_gasto fact_comp_gasto ON (gasto.id_gasto = fact_comp_gasto.id_gasto)
WHERE fact_comp_gasto.id_factura = %s
	AND fact_comp_gasto.id_modo_gasto IN (1, 3);",
	valTpDato($rowEncabezado['id_factura'], "text"));
$rsGasto = mysql_query($queryGasto);
if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$posY = 450;
while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
	$porcGasto = $rowGasto['porcentaje_monto'];
	$montoGasto = $rowGasto['monto'];
	
	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(substr($rowGasto['nombre'],0,25)),$textColor);
	imagestring($img,1,130,$posY,":",$textColor);
	imagestring($img,1,140,$posY,str_pad(number_format($porcGasto, 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,175,$posY,str_pad(number_format($montoGasto, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
		if ($rowGasto['id_iva'] > 0) {
			$totalGastosConIvaOrigen += $montoGasto;
		} else if ($rowGasto['id_iva'] == 0) {
			$totalGastosSinIvaOrigen += $montoGasto;
		}
	} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
		if ($rowGasto['id_iva'] > 0) {
			$totalGastosConIvaLocal += $montoGasto;
		} else if ($rowGasto['id_iva'] == 0) {
			$totalGastosSinIvaLocal += $montoGasto;
		}
	}
}
			
			
$posY = 450;

$subTotal = number_format($rowEncabezado['subtotal_factura'], 2, ".", ",");
$posY += 9;
imagestring($img,1,255,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,1,395,$posY,str_pad($subTotal, 15, " ", STR_PAD_LEFT),$textColor);

$descuento = number_format($rowEncabezado['subtotal_descuento'], 2, ".", ",");
if ($descuento > 0) {
	$posY += 9;
	imagestring($img,1,255,$posY,"DESCUENTO",$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,395,$posY,str_pad($descuento, 15, " ", STR_PAD_LEFT),$textColor);
}

$gastosConIva = number_format($totalGastosConIvaOrigen + $totalGastosConIvaLocal, 2, ".", ",");
if ($gastosConIva > 0) {
	$posY += 9;
	imagestring($img,1,255,$posY,"GASTOS C/IMPTO",$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,395,$posY,str_pad($gastosConIva, 15, " ", STR_PAD_LEFT),$textColor);
}

$queryIvaFact = sprintf("SELECT
	iva.observacion,
	fact_comp_iva.base_imponible,
	fact_comp_iva.iva,
	fact_comp_iva.subtotal_iva
FROM cp_factura_iva fact_comp_iva
	INNER JOIN pg_iva iva ON (fact_comp_iva.id_iva = iva.idIva)
WHERE id_factura = %s;",
	valTpDato($rowEncabezado['id_factura'], "text"));
$rsIvaFact = mysql_query($queryIvaFact);
if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
	$posY += 9;
	imagestring($img,1,255,$posY,"BASE IMPONIBLE",$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$posY += 9;
	imagestring($img,1,255,$posY,substr($rowIvaFact['observacion'],0,14),$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,360,$posY,str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 6, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	
	$totalIva += $rowIvaFact['subtotal_iva'];
}

$gastosSinIva = number_format($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal, 2, ".", ",");
if ($gastosSinIva > 0) {
	$posY += 9;
	imagestring($img,1,255,$posY,"GASTOS S/IMPTO",$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,395,$posY,str_pad($gastosSinIva, 15, " ", STR_PAD_LEFT),$textColor); // <---
}

$montoExento = $rowEncabezado['monto_exento'] - ($totalGastosSinIvaOrigen + $totalGastosSinIvaLocal);
if ($montoExento > 0) {
	$posY += 9;
	imagestring($img,1,255,$posY,"MONTO EXENTO",$textColor);
	imagestring($img,1,325,$posY,":",$textColor);
	imagestring($img,1,395,$posY,str_pad(number_format($montoExento, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor); // <---
}

$posY += 7;
imagestring($img,1,255,$posY,str_pad("", 54, "-", STR_PAD_LEFT),$textColor);

$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $totalIva + $totalGastosSinIvaOrigen + $totalGastosConIvaOrigen + $totalGastosSinIvaLocal + $totalGastosConIvaLocal;

if ($rowEncabezado['reconvercion'] == NULL) {
if ($rowEncabezado['fecha_origen']>='2018-08-01' and $rowEncabezado['fecha_origen']<'2018-08-20') {
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format(($totalFactura/100000), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}else if ($rowEncabezado['fecha_origen']>='2018-08-20') {
	
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
$posY += 7;
imagestring($img,1,255,$posY,"TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}else{
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}
}else{
	if ($rowEncabezado['fecha_origen']>='2018-08-01' and $rowEncabezado['fecha_origen']<'2018-08-20') {
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}else if ($rowEncabezado['fecha_origen']>='2018-08-20') {
	
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL Bs.S",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format(($totalFactura), 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
$posY += 7;
imagestring($img,1,255,$posY,"TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format($totalFactura*100000, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}else{
	$posY += 7;
imagestring($img,1,255,$posY,"TOTAL",$textColor);
imagestring($img,1,325,$posY,":",$textColor);
imagestring($img,2,380,$posY,str_pad(number_format($totalFactura, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
}


}



$pageNum++;
$arrayImg[] = "tmp/"."asignacion_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO 
		if ($idEmpresa != "") {
			if (strlen($rowEmp['logo_familia']) > 5) {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			}
			
			$pdf->SetY(15);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',5);
			$pdf->SetX(88);
			$pdf->Cell(200,9,$rowEmp['nombre_empresa'],0,2,'L');
			
			if (strlen($rowEmp['rif']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
			}
			if (strlen($rowEmp['direccion']) > 1) {
				$direcEmpresa = $rowEmp['direccion'].".";
				$telfEmpresa = "";
				if (strlen($rowEmp['telefono1']) > 1) {
					$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
				}
				if (strlen($rowEmp['telefono2']) > 1) {
					$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
					$telfEmpresa .= $rowEmp['telefono2'];
				}
				
				$pdf->SetX(88);
				$pdf->Cell(100,9,$direcEmpresa." ".$telfEmpresa,0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',7);
		$pdf->Cell(0,8,((strlen($rowEncabezado['nombre_empleado']) > 0) ? "Registrado por: ".$rowEncabezado['nombre_empleado'] : ""),0,0,'L');
		$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}
?>
