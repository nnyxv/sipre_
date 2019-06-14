<?php
require_once ("../../connections/conex.php");
require_once('../../inc_sesion.php');
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');
require_once('../../clases/barcode128.inc.php');

/*El informe de errores */
//error_reporting (E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);

$ruta = "tmp/img_codigo.png";

$queryEmpresa = "SELECT logo_familia FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$_GET['session']."'"; //ide
$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
$rowEmpresa = mysql_fetch_array($rsEmpresa);
$ruta_logo = "../../".$rowEmpresa['logo_familia'];

$arreglo = getimagesize($ruta_logo);
$aux = getBarcode($_GET['idArt'],'tmp/img_codigo'); //id

if ($aux){
	$pdf = new PDF_AutoPrint('L','cm',array('3.18','5.71'));
	$anchoPixel = ($aux[0]/ 37.79);
	$centro = (5.50 - $anchoPixel) / 2;
	$altoLogoCm = $arreglo[1] / 37.79;
	$anchoLogoCm = $arreglo[0] / 37.79;
	$porcentajeReduccion = 100 * 1.4 / $altoLogoCm;
	$anchoReducido = $porcentajeReduccion * $anchoLogoCm / 100;
	$centroLogo = (5.71 - $anchoReducido) / 2;

	$pdf->AddPage();
	$pdf->Image($ruta, $centro, '0.2', '', '', '','');
	$pdf->Image($ruta_logo, ($centroLogo + 0.5), '1.78', '', '1', '','');
	
	$pdf->AutoPrint(true);
	$pdf->Output();
	
	if (file_exists($ruta)) {
		unlink($ruta);	
	}
}else{
	echo "Error generando codigo de barras";
}
?>