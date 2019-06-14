<?php
require_once ("../connections/conex.php");
require_once('../repuestos/clases/barcode128.inc.php');

/**************************** ARCHIVO PDF ****************************/
require('../repuestos/clases/fpdf/fpdf.php');
require('../repuestos/clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','cm',array('2','4'));
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$codigo = $_GET["codigo"];


	
if ($codigo > 0) {
	

	
	$queryEmpresa = sprintf("SELECT logo_familia FROM vw_iv_empresas_sucursales
	WHERE id_empresa_reg = 1;");
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowEmpresa = mysql_fetch_array($rsEmpresa);
	
	
	$ruta_logo = "../".$rowEmpresa['logo_familia'];
	
	$arreglo = getimagesize($ruta_logo);
	$altoLogoCm = $arreglo[1] / 40;
	$anchoLogoCm = $arreglo[0] / 40;
	$porcentajeReduccion = 100 * 1.4 / $altoLogoCm;
	$anchoReducido = $porcentajeReduccion * $anchoLogoCm / 100;
	$centroLogo = (4 - $anchoReducido) / 2;

	$ruta = "img_codigo_activo.png";
	
	$queryAct = sprintf("SELECT deprecactivos.Codigo,
							   ubicacion.ubicacion,
							   departamento.nombre_dep
						FROM sipre_contabilidad.deprecactivos
						INNER JOIN sipre_contabilidad.ubicacion ON (deprecactivos.Ubicacion = ubicacion.id_ubicacion)
						INNER JOIN sipre_contabilidad.departamento ON (deprecactivos.Departamento = departamento.id_departamento)
							WHERE Codigo =  %s;",
	$codigo);
	$rsAct = mysql_query($queryAct);
	if (!$rsAct) die(mysql_error()."<br><br>Line: ".__LINE__.$queryAct);
	$rowAct = mysql_fetch_array($rsAct);

	$aux = getBarcode($rowAct['Codigo'],"img_codigo_activo",2,1,25,"c",0);

	if ($aux) {
		$anchoPixel = ($aux / 40);
		$centro = (4 - $anchoPixel) / 2;
		for ($i = 0; $i < 1; $i++){
			$pdf->AddPage();
			$pdf->SetFont('Arial','B',6);
			$pdf->SetY(0.2);
			$pdf->Cell(0,0,utf8_decode($rowAct['ubicacion']),0,0,'C');
			$pdf->SetY(0.4);
			$pdf->Cell(0,0,utf8_decode($rowAct['nombre_dep']),0,0,'C');
			$pdf->Image($ruta, '0.5', '0.6', '', '0.8', '','');
			$pdf->Image($ruta_logo, '2.7', '0.9', '', '0.5', '','');
			$pdf->SetY(1.6);
			$pdf->Cell(0,0,$rowAct['Codigo'],0,0,'C');
		}
		unlink($ruta);
	} else {
		echo "Error al Crear el Código de Barra";
	}
}

$pdf->AutoPrint(true);
$pdf->Output();
?>