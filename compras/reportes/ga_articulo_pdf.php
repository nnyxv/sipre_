<?php 
require_once ("../../connections/conex.php");
require_once('../../inc_sesion.php');
require('../../clases/fpdf/fpdf.php');
require_once('../../clases/barcode128.inc.php');

/*El informe de errores 
error_reporting (E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);*/

class PDF extends FPDF
{
// Cabecera de página
	function Header(){		
		$queryEmpresa = "SELECT nombre_empresa,rif,logo_familia FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$_GET['session']."'"; //ide
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$rowEmpresa = mysql_fetch_array($rsEmpresa);
		$nombreEmp = $rowEmpresa['nombre_empresa'];
		$rifEmp = $rowEmpresa['rif'];
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
		$titulo = utf8_decode("Descripción del Artículo");
	
		$ruta = "tmp/img_codigo.png";
		$aux = getBarcode($_GET['idArt'],'tmp/img_codigo');
		$this->Image($ruta, 175,10,20);
	
		$this->Image($ruta_logo,10,8,33);
		  
		$this->SetFont('Arial','B',9);// Arial bold 15
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,5,$nombreEmp,0,0,'C'); // Título
		$this->Ln(5);// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,5,$rifEmp,0,0,'R'); // Título
		$this->Ln(20);// Salto de línea
		$this->SetFont('Arial','B',15);// Arial bold 15
		$this->SetY(25);// Posición: a 1,5 cm del final
		$this->Cell(0,5,$titulo,0,0,'C'); // Título
		
		if (file_exists($ruta)) {
			unlink($ruta);	
		}
	}
	
	//Tabla simple
	function headerTable($cabecera){
		$ancho = array("40","125","30");//ancho por cada celda de la cabecera
		$posiscion = array("C","L","C");
		// Cabecera
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],7,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($data){
		$ancho = array("40","125","30");//ancho por cada celda de la cabecera
		$posiscion = array("C","L","C");
		$this->Ln();
		foreach($data as $clave => $valor){
			$this->Cell($ancho[$clave],7,$valor,1,0,$posiscion[$clave]);	
		}
	}	
	
	// Pie de página
	function Footer(){		
		$this->SetY(-15);// Posición: a 1,5 cm del final
		$this->SetFont('Arial','I',8);// Arial italic 8
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');// Número de página
	}
}


$query = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", 
	valTpDato($_GET['idArt'], "int"));
$rs = mysql_query($query) or die(mysql_error());
$row = mysql_fetch_array($rs);
	
// Creación del objeto de la clase heredada
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
	

$pdf->SetFont('Arial','B',12);
$pdf->Ln(15);// Salto de línea
//$pdf->Cell(30);//Movernos a la derecha
$pdf->Cell(55,5,"Marca:",0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,$row['marca']); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(55,5,utf8_decode("Tipo De Artículo:"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,$row['tipo_articulo']); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(55,5,utf8_decode("Código:"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,$row['codigo_articulo']); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);	
$pdf->Cell(55,5,utf8_decode("Cód. Articulo (Proveedor):"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,$row['codigo_articulo_prov']); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(55,5,utf8_decode("Descripción:"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,utf8_decode($row['descripcion'])); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(55,5,utf8_decode("Unidad:"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,$row['unidad']); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(55,5,utf8_decode("Sección:"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,utf8_decode($row['descripcion_seccion'])); // Título
$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(55,5,utf8_decode("sub-Sección:"),0,0,'L'); // Título

$pdf->SetFont('Arial','',12);
$pdf->Write(5,$row['descripcion_subseccion']); // Título
$pdf->Ln(10);// Salto de línea

$pdf->SetFont('Arial','B',12);// Arial bold 15
$pdf->SetY(75);// Posición: a 1,5 cm del final
$pdf->Ln(20);// Salto de línea
//CONSULTA LOS ARTICULOS ALTERNO
$pdf->Cell(195,7,utf8_decode("Artículos Sustitutos"),1,0,'C'); // Título
$pdf->Ln();
		
$querySust = sprintf("SELECT 
	id_articulo_codigo_sustituto, 
	ga_articulos_codigos_sustitutos.id_articulo,
	id_articulo_sustituto, 
	codigo_articulo,
	vw_ga_articulos.descripcion, 
	existencia
FROM ga_articulos_codigos_sustitutos 
	LEFT JOIN vw_ga_articulos ON vw_ga_articulos.id_articulo = ga_articulos_codigos_sustitutos.id_articulo_sustituto
WHERE ga_articulos_codigos_sustitutos.id_articulo = %s;",
valTpDato($_GET['idArt'], "int"));
$rsSust = mysql_query($querySust) or die(mysql_error());
$numSust = mysql_num_rows($rsSust);

$header = array(utf8_decode("Codigo Artículo"), utf8_decode("Descripción"), utf8_decode("Existencia"));
$pdf->SetFont('Arial','B',12);
$pdf->headerTable($header, $tabDatos);

if(!$numSust){
	$pdf->SetFont('Arial','B',10);
	$pdf->Ln();// Salto de línea
	$pdf->Cell(195,7,utf8_decode("No Exite Artículos Sustitutos"),1,0,'C'); // Título
} else{
	while($rows = mysql_fetch_array($rsSust)){
		$tabDatos = array( $rows["codigo_articulo"],$rows["descripcion"].$numSust,$rows["existencia"]);
		$pdf->SetFont('Arial','',11);
		$pdf->bodyTable($tabDatos);
	}
}
	

$pdf->Ln(15);
//CONSULTA LOS ARTICULOS ALTERNO	
$pdf->SetFont('Arial','B',12);// Arial bold 15
$pdf->Cell(195,7,utf8_decode("Artículos Alternos"),1,0,'C'); // Título
$pdf->Ln(7);

$queryAlte = sprintf("SELECT 
	id_articulo_codigo_alterno, 
	ga_articulos_codigos_alternos.id_articulo,
	id_articulo_alterno,
	codigo_articulo,
	vw_ga_articulos.descripcion, 
	existencia
FROM ga_articulos_codigos_alternos
	LEFT JOIN vw_ga_articulos ON vw_ga_articulos.id_articulo = ga_articulos_codigos_alternos.id_articulo_alterno
WHERE ga_articulos_codigos_alternos.id_articulo = %s;",
valTpDato($_GET['idArt'], "int"));
$rsAlte = mysql_query($queryAlte) or die(mysql_error());
$numAlte = mysql_num_rows($rsAlte);

$header = array(utf8_decode("Codigo Artículo"), utf8_decode("Descripción"), "Existencia");
$pdf->SetFont('Arial','B',12);
$pdf->headerTable($header, $tabDatos);

if(!$numAlte){
	$pdf->SetFont('Arial','B',10);
	$pdf->Ln();// Salto de línea
	$pdf->Cell(195,7,utf8_decode("No Exite Artículos Alternos"),1,0,'C'); // Título
} else{
	while($rows = mysql_fetch_array($rsAlte)){
		$tabDatos = array( $rows["codigo_articulo"],$rows["descripcion"],$rows["existencia"]);
		$pdf->SetFont('Arial','',11);
		$pdf->bodyTable($tabDatos);
	}
}
	
$pdf->Output();
?>