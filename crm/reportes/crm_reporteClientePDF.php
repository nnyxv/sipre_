<?php 
require_once ("../../connections/conex.php");
require_once('../../inc_sesion.php');
require('../../clases/fpdf/fpdf.php');

/*El informe de errores 
error_reporting (E_ALL);
ini_set('display_errors', TRUE);	
ini_set('display_startup_errors', TRUE);*/



class PDF extends FPDF
{
// Cabecera de página

	function Header(){
		
		$queryEmpresa = sprintf("SELECT nombre_empresa,rif,logo_familia,web FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			$_SESSION['idEmpresaUsuarioSysGts']); //ide
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$rowEmpresa = mysql_fetch_array($rsEmpresa);
		$nombreEmp = $rowEmpresa['nombre_empresa'];
		$rifEmp = $rowEmpresa['rif'];
		$web = $rowEmpresa['web'];
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
		$titulo = utf8_decode("Listado de Clientes");
		$fecha = date("d/m/Y");
		$hora = date("h:i. A");
		

//FILA PARA EL LOGO NOMBRE EMPRESA Y FECHA
				$this->SetFont('Arial','B',10);
				$this->Image($ruta_logo,15,17,80);
				$this->Cell(100,8,'	',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$nombreEmp,0,0);
				$this->SetFont('Arial','',7);
				//$this->Cell(580,8,'Fecha: '.$fecha,0,0,'R');
				
				$this->Ln(10);
				//FILA PARA RIF Y HORA
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$rifEmp,0,0);
				$this->SetFont('Arial','',7);
				$this->Cell(580,8,'Fecha: '.$fecha,0,0,'R');
				
				$this->Ln(10);
				//FILA PARA DIRECCIONES WEB
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$web,0,0);
				
				$this->Ln(15);
				//TITULO
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',15);
				$this->Cell(680,8,$titulo,0,0,'C');
				$this->Ln(15);
	}
	
	
/*

	$this->Ln(10);
				//FILA PARA RIF Y HORA
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$rifEmp,0,0);
				$this->SetFont('Arial','',7);
				$this->Cell(580,8,'Fecha: '.$fecha,0,0,'R');
*/
	
	//Tabla Cabecera
	function headerTable($cabecera){
		$ancho = array("100","25","200","85","30","50","85","40","50","20");//ancho por cada celda de la cabecera
		$posiscion = array("C","C","C","C","C","C","C","C","C","C");
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],15,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($data){
		$ancho = array("13","15","29","83","28","22");//ancho por cada celda de la cabecera
		$posiscion = array("C","C","C","L","C","C");
			$this->Ln();
				foreach($data as $clave => $valor){
					$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);	
				}
	}
	
	// Pie de página
	function Footer(){
		
		$this->SetY(-15);// Posición: a 1,5 cm del final
		$this->SetFont('Arial','I',8);// Arial italic 8
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');// Número de página
	}
}

	//Creación del objeto pdf de la clase heredada
	//$pdf = new PDF();
	$pdf= new PDF('L','pt');
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->Ln(5);// Salto de línea
	$pdf->SetFont('Arial','B',12);

	//DETALLES DE LA SOLICITUD
	$pdf->SetFont('Arial','B',12);
	$pdf->Ln(10);
	$header = array("Empresa", "Id", "Nombre / Aprellido", "F. Nacimiento","Sexo","CI / R.I.f","Telf","Correo","Tipo Cliente","Modelo");
	$pdf->SetFont('Arial','B',12);
	$pdf->headerTable($header);
	$total = array();
	
	
	/*while($rowsD = mysql_fetch_array($rsD)){
		$total[] = $rowsD["cantidad"]*$rowsD["precio_sugerido"];
		if(!$rowsD["fecha_requerida"] == "" ){
			$fechaRequerida = date("d-m-Y", strtotime($rowsD["fecha_requerida"]));
		}	
	//substr($rowDetallOrdeComp["codigo_articulo"],0,14)	
		$tabDatos = array($rowsD["cantidad"],$rowsD["unidad"],
							substr($rowsD["codigo_articulo_prov"],0,14),
							cambiaLetras(substr($rowsD["descripcion"],0,35)),
						  $fechaRequerida,	$rowsD["precio_sugerido"]);
		$pdf->SetFont('Arial','',12);
		$pdf->bodyTable($tabDatos);
	}*/
	
	$pdf->Ln(10);

	$pdf->Output()
?>