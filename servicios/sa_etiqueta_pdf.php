<?php

//Lo usa sa_registro_vehiculos.php para mostrar solo la etiqueta: var window=setPopup('sa_etiqueta_pdf.php?id='+id+'&ide='+id_empresa,'print'
require_once ("../connections/conex.php");
require_once('../inc_sesion.php');
require('clases/fpdf/fpdf.php');
require('clases/fpdf/fpdf_print.inc.php');
require_once('clases/barcode128.inc.php');

$ruta = "clases/temp_codigo/img_codigo.png";
$id_empresa=intval($_GET['ide']);

$queryEmpresa = "SELECT logo_familia FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$id_empresa."'";
$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
$rowEmpresa = mysql_fetch_array($rsEmpresa);
$ruta_logo = "../".$rowEmpresa['logo_familia'];

$arreglo = getimagesize($ruta_logo);

$aux = getBarcode($_GET['id'],'clases/temp_codigo/img_codigo');

if ($aux){
	$pdf = new PDF_AutoPrint('L','cm',array('3.18','5.71'));
	$anchoPixel = ($aux / 37.79);
	$centro = (5.71 - $anchoPixel) / 2;
	$altoLogoCm = $arreglo[1] / 37.79;
	$anchoLogoCm = $arreglo[0] / 37.79;
	$porcentajeReduccion = 100 * 1.4 / $altoLogoCm;
	$anchoReducido = $porcentajeReduccion * $anchoLogoCm / 100;
	$centroLogo = (5.71 - $anchoReducido) / 2;

	$pdf->AddPage();
/*	$pdf->SetFont('Arial','B',3);
	$pdf->Cell(0,0,$queryEmpresa);*/
	$pdf->Image($ruta, $centro, '0.2', '', '', '','');
	$pdf->Image($ruta_logo, ($centroLogo + 0.5), '1.78', '', '1', '','');
	
	$pdf->AutoPrint(true);
	$pdf->Output();
}
else{
	echo "Error";
}
?>