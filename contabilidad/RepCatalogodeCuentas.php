<?php session_start();
require('fpdf.php');
include_once("FuncionesPHP.php");
class PDF extends FPDF{
//Cabecera de página
	function Header(){    
		$con = ConectarBD();                                                     
				 
		$SqlDes='Select descrip from parametros';
		$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
		while ($row = ObtenerFetch($exc)) {
			$descrip = $row[0];
		}
								 
		//llamando a la funcion encabezado del fpdf
		$logo = "logo.png";//logo del reporte
		$gerencia = $_SESSION["sDesBasedeDatos"]; //gerencia del deporte
		$this->SetFont('Arial','B',12);
	//        $this->Cell(100,8,$_SESSION["sDesBasedeDatos"],0,0);
		$this->Cell(100,8,$descrip,0,0);
		//$this->Image('logo.png',10,8,33);
		//Arial bold 15
		$this->SetFont('Arial','B',10);
		
		$titulo ="CATALOGO DE CUENTAS";//titulo del reporte
		$this->Ln(10);
						 $fecha = date("d/m/Y");
			//Arial bold 7
		$this->Cell(160,5,'',0,0);
		$this->SetFont('Arial','B',7);
		$this->Cell(30,5,'Fecha: '.$fecha,0,0);
		//Salto de línea
		$this->Ln(3);
		//Movernos a la derecha
		$this->Cell(160+ 0,5,'',0,0);
		//colocar hora
		$hora = date("g:i:s A");
		//Arial bold 7
		$this->SetFont('Arial','B',7);
		$this->Cell(30,5,'Hora: '.$hora,0,0);
		$this->Ln(5);
		$this->SetFont('Arial','B',10);
		$this->Cell(180,5,$titulo,0,0,'C');
		$this->Ln(5);
		
		$campos = array ('Código','Descripción');
		$Ancho = array ('80','100');
		$Alinear = array ('L','L');
		$Bordes = array ('1','1');
		$this->enc_detalle($campos,$Ancho,7,'','',$Alinear,$Bordes);
		$campos = array ('');
		$Ancho = array ('190');
		$this->enc_detalle($campos,$Ancho,5);
	}
	
	//Pie de página
	function Footer(){
		//Posición: a 1,5 cm del final
		$this->SetY(-15);
		//Arial italic 8
		$this->SetFont('Arial','I',8);
		//Número de página
		$this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
	}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

if ($cDesde1== ""){
   $cDesde1= '';
   $cHasta1= 'zzzzzzzzzzzzzzzzzzzz';
}

$con = ConectarBD();

//if($_SESSION["CCSistema"] != ""){
  $EstadoCuenta =  "sipre_contabilidad.cuenta";
/*}else{
  $EstadoCuenta =  "cuenta";
}*/

$sTabla=$EstadoCuenta;
$sCondicion=" codigo between '$cDesde1' and '$cHasta1' order by codigo";
$sCampos='Codigo';
$sCampos.=',Descripcion';
$SqlStr='Select '.$sCampos.' from '.$sTabla. ' where '. $sCondicion ;
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if ( NumeroFilas($exc)>0){
	$iFila = -1;
	while ($row = ObtenerFetch($exc)) {
		$iFila++;
		$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
		$descripcion = trim(ObtenerResultado($exc,2,$iFila));
		$campos = array ($codigo,$descripcion);
		$Ancho = array ('80','100');
		$Alinear = array ('L','L');
		$pdf->enc_detalle($campos,$Ancho,5,'','',$Alinear,$Bordes);
	} 
}
$pdf->Output();
?>