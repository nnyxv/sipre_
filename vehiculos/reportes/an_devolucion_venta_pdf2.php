<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idDcto = $valCadBusq[0];
$idUnidadFisica = $valCadBusq[1];

$queryDcto = sprintf("SELECT
	nota_cred.id_empresa,
	nota_cred.numeracion_nota_credito,
	fact_vent.numeroFactura,
	nota_cred.fechaNotaCredito,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	cliente.id,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	cliente.direccion,
	cliente.telf,
	cliente.otrotelf,
	nota_cred.observacionesNotaCredito,
	nota_cred.subtotalNotaCredito AS subtotal_nota_credito,
	nota_cred.porcentaje_descuento,
	nota_cred.subtotal_descuento,
	nota_cred.baseimponibleNotaCredito AS base_imponible_nota_credito,
	fact_vent.porcentajeIvaFactura AS porcentaje_iva,
	nota_cred.ivaNotaCredito AS subtotal_iva,
	fact_vent.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
	nota_cred.ivaLujoNotaCredito AS subtotal_iva_lujo,
	nota_cred.montoExentoCredito AS monto_exento,
	nota_cred.montoExoneradoCredito AS monto_exonerado
FROM cj_cc_notacredito nota_cred
	INNER JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura)
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
WHERE nota_cred.idNotaCredito = %s
	AND nota_cred.idDepartamentoNotaCredito = 2",
	valTpDato($idDcto, "int"));
$rsDcto = mysql_query($queryDcto);
if (!$rsDcto) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowDcto = mysql_fetch_array($rsDcto);

$idEmpresa = $rowDcto['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

// VERIFICA VALORES DE CONFIGURACION
$queryConfigDatosGNV = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfigDatosGNV = mysql_query($queryConfigDatosGNV, $conex);
if (!$rsConfigDatosGNV) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowConfigDatosGNV = mysql_fetch_assoc($rsConfigDatosGNV);

$img = @imagecreate(510, 606) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,340,10,str_pad(utf8_decode("NOTA DE CRÉDITO SERIE - V"), 34, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,340,30,utf8_decode("NOTA CRÉDITO NRO."),$textColor);
imagestring($img,2,425,27,": ".$rowDcto['numeracion_nota_credito'],$textColor);

imagestring($img,1,340,40,utf8_decode("FECHA EMISIÓN"),$textColor);
imagestring($img,1,425,40,": ".date(spanDateFormat, strtotime($rowDcto['fechaNotaCredito'])),$textColor);

imagestring($img,1,340,70,utf8_decode("FACTURA NRO."),$textColor);
imagestring($img,1,425,70,": ".$rowDcto['numeroFactura'],$textColor);

imagestring($img,1,340,80,utf8_decode("VENDEDOR"),$textColor);
imagestring($img,1,425,80,": ".strtoupper($rowDcto['nombre_empleado']),$textColor);


imagestring($img,1,210,30,utf8_decode("CÓDIGO"),$textColor);
imagestring($img,1,240,30,": ".$rowDcto['id'],$textColor);

imagestring($img,1,5,40,strtoupper($rowDcto['nombre_cliente']),$textColor); // <----

$direccionCliente = strtoupper(str_replace(",", "", elimCaracter(utf8_encode($rowDcto['direccion']),";")));
imagestring($img,1,5,50,trim(substr($direccionCliente,0,55)),$textColor); // <----

imagestring($img,1,5,60,trim(substr($direccionCliente,55,35)),$textColor); // <----
imagestring($img,1,195,60,$spanClienteCxC,$textColor);
imagestring($img,1,225,60,": ".$rowDcto['ci_cliente'],$textColor);

imagestring($img,1,5,70,trim(substr($direccionCliente,90,35)),$textColor); // <----
imagestring($img,1,195,70,"TELEF.",$textColor);
imagestring($img,1,225,70,": ".$rowDcto['telf'],$textColor);

imagestring($img,1,5,80,trim(substr($direccionCliente,125,35)),$textColor); // <----
imagestring($img,1,235,80,$rowDcto['otrotelf'],$textColor);



// BUSCA LOS DATOS DE LA UNIDAD
$queryUnidad = sprintf("SELECT 
	uni_bas.nom_uni_bas,
	marca.nom_marca,
	modelo.nom_modelo,
	vers.nom_version,
	ano.nom_ano,
	uni_fis.placa,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	color1.nom_color AS color_externo,
	nota_cred_det_vehic.precio_unitario,
	uni_bas.com_uni_bas,
	codigo_unico_conversion,
	marca_kit,
	marca_cilindro,
	modelo_regulador,
	serial1,
	serial_regulador,
	capacidad_cilindro,
	fecha_elaboracion_cilindro
FROM cj_cc_nota_credito_detalle_vehiculo nota_cred_det_vehic
	INNER JOIN an_unidad_fisica uni_fis ON (nota_cred_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
WHERE nota_cred_det_vehic.id_nota_credito = %s",
	valTpDato($idDcto, "int"));
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_array($rsUnidad);

$posY = 100;

imagestring($img,1,0,$posY,"----------------------------------------------------------------------------------------------------------",$textColor);
imagestring($img,1,0,$posY+10,str_pad(utf8_decode("CÓDIGO"), 18, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,95,$posY+10,str_pad(utf8_decode("DESCRIPCIÓN"), 65, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,425,$posY+10,str_pad(utf8_decode("TOTAL"), 17, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,0,$posY+20,"----------------------------------------------------------------------------------------------------------",$textColor);

$posY += 30;
if ($totalRowsUnidad > 0) {
	imagestring($img,1,0,$posY,strtoupper($rowUnidad['nom_uni_bas']),$textColor);
	imagestring($img,1,95,$posY,utf8_decode("MARCA"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_marca']),$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,utf8_decode("MODELO"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_modelo']),$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,utf8_decode("VERSIÓN"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_version']),$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,utf8_decode("AÑO"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_ano']),$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanPlaca)),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['placa']),$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialCarroceria)),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial_carroceria']),$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialMotor)),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial_motor']),$textColor);
	
	$posY += 20;
	imagestring($img,1,95,$posY,utf8_decode("COLOR CARROCERIA"),$textColor);
	imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['color_externo']),$textColor);
	
	if ($rowConfigDatosGNV['valor'] == 1
	|| ($rowConfigDatosGNV['valor'] == 2
		&& (strlen($rowUnidad['codigo_unico_conversion']) > 1
			|| strlen($rowUnidad['marca_kit']) > 1
			|| strlen($rowUnidad['marca_cilindro']) > 1
			|| strlen($rowUnidad['modelo_regulador']) > 1
			|| strlen($rowUnidad['serial1']) > 1
			|| strlen($rowUnidad['serial_regulador']) > 1
			|| strlen($rowUnidad['capacidad_cilindro']) > 1
			|| strlen($rowUnidad['fecha_elaboracion_cilindro']) > 1))) {
		if ($rowUnidad['com_uni_bas'] == 2 || $rowUnidad['com_uni_bas'] == 5) {
			$posY += 20;
			imagestring($img,1,95,$posY,str_pad(utf8_decode("SISTEMA GNV"), 65, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("CÓDIGO UNICO"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['codigo_unico_conversion']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("MARCA KIT"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_kit']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("MARCA CILINDRO"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_cilindro']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("MODELO REGULADOR"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['modelo_regulador']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("SERIAL 1"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial1']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("SERIAL REGULADOR"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial_regulador']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("CAPACIDAD CILINDRO (NG)"),$textColor);
			imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['capacidad_cilindro']),$textColor);
			
			$posY += 10;
			imagestring($img,1,95,$posY,utf8_decode("FECHA ELAB. CILINDRO"),$textColor);
			imagestring($img,1,210,$posY,($rowUnidad['fecha_elaboracion_cilindro']) ? ": ".date(spanDateFormat, strtotime($rowUnidad['fecha_elaboracion_cilindro'])) : ": "."----------",$textColor);
		}
	}
	
	$posY += 10;
	imagestring($img,1,95,$posY,"-----------------------------------------------------------------",$textColor);
	
	$posY += 10;
	imagestring($img,1,95,$posY,utf8_decode("MONTO TOTAL"),$textColor);
	imagestring($img,1,425,$posY,str_pad(number_format($rowUnidad['precio_unitario'],2,".",","), 17, " ", STR_PAD_LEFT),$textColor);

	$posY += 20;
}


$queryDet = sprintf("SELECT
	nota_cred_det_acc.id_nota_credito_detalle_accesorios,
	nota_cred_det_acc.id_accesorio,
	nota_cred_det_acc.costo_compra,
	nota_cred_det_acc.precio_unitario,
	(CASE
		WHEN nota_cred_det_acc.id_iva = 0 THEN
			CONCAT(acc.nom_accesorio, ' (E)')
		ELSE
			acc.nom_accesorio
	END) AS nom_accesorio,
	nota_cred_det_acc.tipo_accesorio
FROM cj_cc_nota_credito_detalle_accesorios nota_cred_det_acc
	INNER JOIN an_accesorio acc ON (nota_cred_det_acc.id_accesorio = acc.id_accesorio)
WHERE nota_cred_det_acc.id_nota_credito = %s",
	valTpDato($idDcto, "int"));
$rsDet = mysql_query($queryDet);
if (!$rsDet) die(mysql_error()."<br><br>Line: ".__LINE__);
while ($rowDet = mysql_fetch_array($rsDet)) {
	imagestring($img,1,95,$posY,strtoupper($rowDet['nom_accesorio']),$textColor);
	imagestring($img,1,425,$posY,strtoupper(str_pad(number_format($rowDet['precio_unitario'], 2, ".", ","), 17, " ", STR_PAD_LEFT)),$textColor);
	
	$posY += 10;
}


$posY = 530;
$observacionFactura = strtoupper($rowDcto['observacionFactura']);
imagestring($img,1,0,$posY,"OBSERVACIONES :",$textColor);
imagestring($img,1,0,$posY+10,strtoupper(trim(substr($observacionFactura,0,55))),$textColor);
imagestring($img,1,0,$posY+20,strtoupper(trim(substr($observacionFactura,55,55))),$textColor);
imagestring($img,1,0,$posY+30,strtoupper(trim(substr($observacionFactura,110,55))),$textColor);
imagestring($img,1,0,$posY+40,strtoupper(trim(substr($observacionFactura,165,55))),$textColor);

$subTotal = number_format($rowDcto['subtotal_nota_credito'],2,".",",");
imagestring($img,1,295,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,380,$posY,":",$textColor);
imagestring($img,1,425,$posY,str_pad($subTotal, 17, " ", STR_PAD_LEFT),$textColor); // <----

$posY += 10;
$baseImponible = number_format($rowDcto['base_imponible_nota_credito'],2,".",",");
imagestring($img,1,295,$posY,"BASE IMPONIBLE",$textColor);
imagestring($img,1,380,$posY,":",$textColor);
imagestring($img,1,425,$posY,str_pad($baseImponible, 17, " ", STR_PAD_LEFT),$textColor); // <----

$posY += 10;
$porcentajeIva = number_format($rowDcto['porcentaje_iva'],2,".",",")."%";
$calculoIva = number_format($rowDcto['subtotal_iva'],2,".",",");
imagestring($img,1,295,$posY,"I.V.A.",$textColor);
imagestring($img,1,380,$posY,":",$textColor);
imagestring($img,1,390,$posY,str_pad($porcentajeIva, 8, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,425,$posY,str_pad($calculoIva, 17, " ", STR_PAD_LEFT),$textColor); // <----

if ($rowDcto['subtotal_iva_lujo'] > 0) {
	$posY += 10;
	$porcentajeIva = number_format($rowDcto['porcentaje_iva_lujo'],2,".",",")."%";
	$calculoIva = number_format($rowDcto['subtotal_iva_lujo'],2,".",",");
	imagestring($img,1,295,$posY,"I.V.A. LUJO",$textColor);
	imagestring($img,1,380,$posY,":",$textColor);
	imagestring($img,1,390,$posY,str_pad($porcentajeIva, 8, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,425,$posY,str_pad($calculoIva, 17, " ", STR_PAD_LEFT),$textColor); // <----
}

$posY += 10;
$montoExento = number_format($rowDcto['monto_exento'],2,".",",");
imagestring($img,1,295,$posY,"MONTO EXENTO",$textColor);
imagestring($img,1,380,$posY,":",$textColor);
imagestring($img,1,425,$posY,str_pad($montoExento, 17, " ", STR_PAD_LEFT),$textColor); // <----

$posY += 10;
$montoExonerado = number_format($rowDcto['monto_exonerado'],2,".",",");
imagestring($img,1,295,$posY,"MONTO EXONERADO",$textColor);
imagestring($img,1,380,$posY,":",$textColor);
imagestring($img,1,425,$posY,str_pad($montoExonerado, 17, " ", STR_PAD_LEFT),$textColor); // <----

$posY += 8;
imagestring($img,1,295,$posY,"-------------------------------------------",$textColor);

$posY += 8;
$totalFactura = $rowDcto['subtotal_nota_credito']-$rowDcto['subtotal_descuento']+$rowDcto['subtotal_iva']+$rowDcto['subtotal_iva_lujo'];
$montoTotalFactura = number_format($totalFactura,2,".",",");
imagestring($img,1,295,$posY,"TOTAL FACTURA",$textColor);
imagestring($img,1,380,$posY,":",$textColor);
imagestring($img,1,425,$posY,str_pad($montoTotalFactura, 17, " ", STR_PAD_LEFT),$textColor); // <----

$arrayImg[] = "tmp/"."asignacion_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
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