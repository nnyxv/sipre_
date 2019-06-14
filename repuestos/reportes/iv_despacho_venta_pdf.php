<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 34;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DEL DOCUMENTO
$query = sprintf("SELECT 
	bulto_vent.*,
	ped_vent.id_empresa,
	ped_vent.id_pedido_venta,
	ped_vent.id_pedido_venta_propio,
	vw_iv_fact_vent.numeroFactura,
	vw_iv_fact_vent.nombre_cliente
FROM vw_iv_facturas_venta vw_iv_fact_vent
	RIGHT JOIN iv_bulto_venta bulto_vent ON (vw_iv_fact_vent.idFactura = bulto_vent.id_factura_venta)
	INNER JOIN iv_pedido_venta ped_vent ON (bulto_vent.id_pedido_venta = ped_vent.id_pedido_venta)
WHERE bulto_vent.id_bulto_venta = %s",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_assoc($rs);

$idEmpresa = $row['id_empresa'];

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT 
	bulto_vent_det.*,
	vw_iv_art_datos_basicos.codigo_articulo,
	vw_iv_art_datos_basicos.descripcion
FROM iv_bulto_venta_detalle bulto_vent_det
	INNER JOIN iv_pedido_venta_detalle ped_vent_det ON (bulto_vent_det.id_pedido_venta_detalle = ped_vent_det.id_pedido_venta_detalle)
	INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_basicos ON (ped_vent_det.id_articulo = vw_iv_art_datos_basicos.id_articulo)
WHERE bulto_vent_det.id_bulto_venta = %s
ORDER BY vw_iv_art_datos_basicos.codigo_articulo",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	if (fmod($contFila, $maxRows) == 1) {
		$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$backgroundGris = imagecolorallocate($img, 230, 230, 230);
		$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
		
		$posY = 0;
		imagestring($img,1,300,$posY,str_pad("BULTO DE DESPACHO", 34, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("BULTO NRO."),$textColor);
		imagestring($img,2,375,$posY-3,": ".$row['numero_bulto'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("FACTURA NRO."),$textColor);
		imagestring($img,1,375,$posY,": ".$row['numeroFactura'],$textColor);
		
		$posY += 9;
		imagestring($img,1,300,$posY,utf8_decode("PEDIDO NRO."),$textColor);
		imagestring($img,1,375,$posY,": ".$row['id_pedido_venta_propio'],$textColor);
		
		$posY += 9;
		/*if ($row['condicionDePago'] == 0) { // 0 = Credito, 1 = Contado
			imagestring($img,1,385,$posY,"CRED. ".number_format($row['diasDeCredito'])." DIAS",$textColor);
		}*/
		
		$posY += 9;
		/*imagestring($img,1,300,$posY,utf8_decode("PEDIDO NRO."),$textColor);
		imagestring($img,1,375,$posY,": ".$rowPedido['id_pedido_venta_propio'],$textColor);*/
		
		$posY += 9;
		/*imagestring($img,1,300,$posY,utf8_decode("VENDEDOR"),$textColor);
		imagestring($img,1,375,$posY,": ".strtoupper($row['nombre_empleado']),$textColor);*/
		
		$posY = 90;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad("CODIGO", 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad("DESCRIPCIÓN", 39, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,315,$posY,str_pad("SERIAL", 31, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
	}
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
	imagestring($img,1,115,$posY,strtoupper(str_pad(substr($rowDetalle['descripcion'],0,39), 39, " ", STR_PAD_RIGHT)." ".$rowDetalle['codigo_articulo_prov']),$textColor);
	imagestring($img,1,315,$posY,str_pad($rowDetalle['serial_articulo'], 31, " ", STR_PAD_BOTH),$textColor);
		
	if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."despacho_venta".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	}
}

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

//$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
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