<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];


// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT
	vale_entrada.id_empresa,
	vale_entrada.numero_vale_entrada,
	vale_entrada.fecha_vale_entrada,
	contrato.id_contrato_venta,
	contrato.numero_contrato_venta,
	contrato.id_unidad_fisica,	
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
	contrato.observacion,
	vale_entrada.total_vale_entrada,
	vale_entrada.subtotal,
	vale_entrada.porcentaje_descuento,
	vale_entrada.subtotal_descuento,
	vale_entrada.monto_exento,
	vale_entrada.monto_exonerado
FROM al_vale_entrada vale_entrada
	INNER JOIN al_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_entrada.id_vale_entrada)
	INNER JOIN al_contrato_venta contrato ON (vale_salida.id_contrato_venta = contrato.id_contrato_venta)
	INNER JOIN cj_cc_cliente cliente ON (vale_entrada.id_cliente = cliente.id)
	INNER JOIN pg_empleado empleado ON (contrato.id_empleado_creador = empleado.id_empleado)
WHERE vale_entrada.id_vale_entrada = %s",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_array($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

if(in_array($rowConfig403['valor'],array(1,2,3))){
	$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);

	$posY = 9;
	imagestring($img,1,300,$posY,str_pad("VALE DE ENTRADA", 34, " ", STR_PAD_BOTH),$textColor);

	$posY += 9;
	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("VALE ENT. NRO."),$textColor);
	imagestring($img,2,374,$posY-3,": ".$rowEncabezado['numero_vale_entrada'],$textColor);

	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("FECHA VALE"),$textColor);
	imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_vale_entrada'])),$textColor);

	/*$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
	imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaVencimientoFactura'])),$textColor);*/

	$posY += 18;
	imagestring($img,1,300,$posY,utf8_decode("CONTRATO NRO."),$textColor);
	imagestring($img,1,375,$posY,": ".$rowEncabezado['numero_contrato_venta'],$textColor);

	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("VENDEDOR"),$textColor);
	imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);

	$posY = 28;
	imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);

	$posY += 9;
	imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,60)),$textColor); // <----

	if (in_array($rowConfig409['valor'],array("","1"))) {
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
	}

	$direccionCliente = strtoupper(str_replace(";", "", $rowEncabezado['direccion_cliente']));
	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,54)),$textColor);

	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,54,54)),$textColor);

	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,108,30)),$textColor);
	imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
	imagestring($img,1,195,$posY,": ".$rowEncabezado['telf'],$textColor);

	$posY += 9;
	imagestring($img,1,0,$posY,trim(substr($direccionCliente,138,30)),$textColor);
	imagestring($img,1,205,$posY,$rowEncabezado['otrotelf'],$textColor);


	// BUSCA LOS DATOS DE LA UNIDAD
	$queryUnidad = sprintf("SELECT
		uni_bas.nom_uni_bas,
		marca.nom_marca,
		modelo.nom_modelo,
		vers.nom_version,
		ano.nom_ano,
		uni_fis.placa,
		(CASE uni_fis.id_condicion_unidad
			WHEN 1 THEN	'NUEVO'
			WHEN 2 THEN	'USADO'
			WHEN 3 THEN	'USADO PARTICULAR'
		END) AS condicion_unidad,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.kilometraje,
		color1.nom_color AS color_externo,
		uni_bas.com_uni_bas,
		codigo_unico_conversion,
		marca_kit,
		marca_cilindro,
		modelo_regulador,
		serial1,
		serial_regulador,
		capacidad_cilindro,
		fecha_elaboracion_cilindro,
		nom_clase
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
		INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
	WHERE uni_fis.id_unidad_fisica = %s",
		valTpDato($rowEncabezado["id_unidad_fisica"], "int"));
	$rsUnidad = mysql_query($queryUnidad);
	if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsUnidad = mysql_num_rows($rsUnidad);
	$rowUnidad = mysql_fetch_array($rsUnidad);

	$posY = 90;
	imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 18, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,95,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 45, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,95,$posY,str_pad(utf8_decode("PRECIO"), 90, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,95,$posY,str_pad(utf8_decode("DÍAS/CANT."), 114, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,1,380,$posY,str_pad(utf8_decode("TOTAL"), 26, " ", STR_PAD_BOTH),$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

	$posY += 9;
	if ($totalRowsUnidad > 0) {
		
		imagestring($img,1,0,$posY,strtoupper(utf8_decode("VEHÍCULO")),$textColor);
		//imagestring($img,1,95,$posY,utf8_decode("MARCA/MODELO/VERSIÓN"),$textColor);
		imagestring($img,1,95,$posY,strtoupper($rowUnidad['nom_marca']." ".$rowUnidad['nom_modelo']." ".$rowUnidad['nom_version']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(substr($rowUnidad['nom_uni_bas'],0,18)),$textColor);//codigo
		//imagestring($img,1,95,$posY,utf8_decode("AÑO/COLOR CARROCERIA"),$textColor);
		imagestring($img,1,95,$posY,strtoupper($rowUnidad['nom_clase']." ".$rowUnidad['nom_ano']." ".$rowUnidad['color_externo']),$textColor);
		
		$posY += 12;
		imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanPlaca)).": ",$textColor);
		imagestring($img,2,155,$posY-3,strtoupper($rowUnidad['placa']),$textColor);
		//imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['placa']),$textColor);
	
		$posY += 12;
		//imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialCarroceria)),$textColor);
		//imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['serial_carroceria']),$textColor);

		$posY += 18;
	}

	$queryDet = sprintf("SELECT
		precio.nombre_precio,
        det_precio.descripcion,		
		al_cont_det_precio.precio,
		al_cont_det_precio.dias_calculado,
		al_cont_det_precio.total_precio,
				
		IF ((SELECT COUNT(det_precio_impuesto.id_contrato_venta_precio_impuesto)
				FROM al_contrato_venta_precio_impuesto det_precio_impuesto
				WHERE det_precio_impuesto.id_contrato_venta_precio = al_cont_det_precio.id_contrato_venta_precio) = 0,
			CONCAT('(E) ',precio.nombre_precio), 
			precio.nombre_precio) AS nombre_precio
		
	FROM al_contrato_venta_precio al_cont_det_precio
		INNER JOIN al_precios_detalle det_precio ON (al_cont_det_precio.id_precio_detalle = det_precio.id_precio_detalle)
		INNER JOIN al_precios precio ON (al_cont_det_precio.id_precio = precio.id_precio)
	WHERE al_cont_det_precio.id_contrato_venta = %s",
		valTpDato($rowEncabezado["id_contrato_venta"], "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>".$queryDet);
	while ($rowDet = mysql_fetch_array($rsDet)) {
		imagestring($img,1,0,$posY,strtoupper(substr($rowDet['nombre_precio'],0,18)),$textColor);
		imagestring($img,1,95,$posY,strtoupper(substr($rowDet['descripcion'],0,40)),$textColor);
		imagestring($img,1,310,$posY,formatoNumero($rowDet["precio"]),$textColor);
		imagestring($img,1,370,$posY,$rowDet["dias_calculado"],$textColor);
		imagestring($img,1,380,$posY,strtoupper(str_pad(formatoNumero($rowDet['total_precio']), 18, " ", STR_PAD_LEFT)),$textColor);

		$posY += 9;
	}
	
	$posY += 9; //linea de espacio

	$queryDet = sprintf("SELECT
        al_cont_det_acc.id_tipo_accesorio,
		al_cont_det_acc.id_contrato_venta_accesorio,
		al_cont_det_acc.id_accesorio,
		al_cont_det_acc.costo,
		al_cont_det_acc.precio,
		al_cont_det_acc.cantidad,
		
		IF ((SELECT COUNT(al_cont_det_acc_impuesto.id_contrato_venta_accesorio)
				FROM al_contrato_venta_accesorio_impuesto al_cont_det_acc_impuesto
				WHERE al_cont_det_acc_impuesto.id_contrato_venta_accesorio = al_cont_det_acc.id_contrato_venta_accesorio) = 0,
			CONCAT('(E) ', acc.nom_accesorio), 
			acc.nom_accesorio) AS nom_accesorio,
			
		acc.des_accesorio
	FROM al_contrato_venta_accesorio al_cont_det_acc
		INNER JOIN an_accesorio acc ON (al_cont_det_acc.id_accesorio = acc.id_accesorio)
	WHERE al_cont_det_acc.id_contrato_venta = %s",
		valTpDato($rowEncabezado["id_contrato_venta"], "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowDet = mysql_fetch_array($rsDet)) {
		imagestring($img,1,0,$posY,strtoupper(substr($rowDet['nom_accesorio'],0,18)),$textColor);
		imagestring($img,1,95,$posY,strtoupper(substr($rowDet['des_accesorio'],0,60)),$textColor);
		imagestring($img,1,310,$posY,formatoNumero($rowDet["precio_unitario"]),$textColor);
		imagestring($img,1,370,$posY,$rowDet["cantidad"],$textColor);
		imagestring($img,1,380,$posY,strtoupper(str_pad(formatoNumero($rowDet["cantidad"] * $rowDet['precio_unitario']), 18, " ", STR_PAD_LEFT)),$textColor);

		$posY += 9;
	}


	$posY = 460;

	$posY += 9;
	imagestring($img,1,0,$posY,"OBSERVACIONES :",$textColor);

	$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacion']), 45);
	if (isset($arrayObservacionDcto)) {
		foreach ($arrayObservacionDcto as $indice => $valor) {
			$posY += 9;
			imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
		}
	}

	$posY = 460;

	$posY += 9;
	imagestring($img,1,255,$posY,str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal']), 18, " ", STR_PAD_LEFT),$textColor); // <----

	if ($rowEncabezado['subtotal_descuento'] > 0) {
		$posY += 9;
		imagestring($img,1,255,$posY,str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_descuento']), 18, " ", STR_PAD_LEFT),$textColor); // <----
	}

	$queryIva = sprintf("SELECT
		iva.observacion,
		al_cont_iva.base_imponible,
		al_cont_iva.iva,
		al_cont_iva.subtotal_iva
	FROM al_contrato_venta_iva al_cont_iva
		INNER JOIN pg_iva iva ON (al_cont_iva.id_iva = iva.idIva)
	WHERE id_contrato_venta = %s;",
		valTpDato($rowEncabezado["id_contrato_venta"], "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$posY += 9;
		imagestring($img,1,255,$posY,str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,395,$posY,str_pad(formatoNumero($rowIva['base_imponible']), 15, " ", STR_PAD_LEFT),$textColor);

		$posY += 9;
		imagestring($img,1,255,$posY,str_pad(substr($rowIva['observacion'],0,16), 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,350,$posY,str_pad(formatoNumero($rowIva['iva'])."%", 8, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,395,$posY,str_pad(formatoNumero($rowIva['subtotal_iva']), 15, " ", STR_PAD_LEFT),$textColor);

		$totalIva += $rowIva['subtotal_iva'];
	}

	$posY += 9;
	imagestring($img,1,255,$posY,str_pad("MONTO EXENTO", 16, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['monto_exento']), 18, " ", STR_PAD_LEFT),$textColor); // <----

	if($rowEncabezado['monto_exonerado'] > 0){
		$posY += 9;
		imagestring($img,1,255,$posY,str_pad("MONTO EXONERADO", 16, " ", STR_PAD_RIGHT).":",$textColor);
		imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['monto_exonerado']), 18, " ", STR_PAD_LEFT),$textColor); // <----
	}
	
	$posY += 8;
	imagestring($img,1,260,$posY,"------------------------------------------",$textColor);

	$posY += 8;
	imagestring($img,1,255,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
	imagestring($img,2,362,$posY,str_pad(formatoNumero($rowEncabezado['total_vale_entrada']), 18, " ", STR_PAD_LEFT),$textColor);

	$arrayImg[] = "tmp/"."vale_entrada_alquiler".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
}


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Vehículos)
$queryConfig206 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 206 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig206 = mysql_query($queryConfig206, $conex);
if (!$rsConfig206) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig206 = mysql_num_rows($rsConfig206);
$rowConfig206 = mysql_fetch_assoc($rsConfig206);

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
$rutaLogo = "../../".$rowEmp["logo_familia"];

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();

		$pdf->Image($valor, 15, $rowConfig206['valor'], 580, 688);

		if ($idEmpresa > 0 && $rowConfig403['valor'] == 3) {
			$pdf->Image($rutaLogo,15,$rowConfig206['valor'] + 5,80);
		}
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

function formatoNumero($monto){
    return number_format($monto, 2, ".", ",");
}
?>
