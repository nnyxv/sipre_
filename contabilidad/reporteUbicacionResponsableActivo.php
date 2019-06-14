<?php session_start();
require('fpdf.php');
include_once("FuncionesPHP.php");
class PDF extends FPDF{
//Cabecera de página
	function Header(){                                                         
        //llamando a la funcion encabezado del fpdf
		
		$con = ConectarBD();                                                     
				 
		$SqlDes='Select descrip from parametros';
		$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
		while ($row = ObtenerFetch($exc)) {
			$descrip = $row[0];
		}
		
        $logo = "logo.png";//logo del reporte
        $gerencia = $_SESSION["sDesBasedeDatos"]; //gerencia del deporte
		$this->SetFont('Arial','B',12);
        $this->Cell(100,8,$descrip,0,1);
		$this->SetFont('Arial','',10);
		//TEXTO DEPARTAMENTO
        $this->Cell(35,8,"DEPARTAMENTO:",0,0);
		//DEPARTAMENTO
        $this->Cell(100,8,$_GET['desdepartamento'],0,1);	
		//TEXTO RESPONSABLE
        $this->Cell(30,8,"RESPONSABLE:",0,0);
		//UBICACION
        $this->Cell(100,8,$_GET['desresponsable'],0,1);	
		//TEXTO UBICACION
        $this->Cell(25,8,utf8_decode("UBICACIÓN:"),0,0);
		//UBICACION
        $this->Cell(100,8,$_GET['desubicacion'],0,1);		
		//$this->Image('logo.png',10,8,33);
		//Arial bold 15
		$this->SetFont('Arial','B',10);
		
       // $titulo ="VALOR ACTIVOS FIJOS";//titulo del reporte
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
		
        $campos = array (utf8_decode('Código'),utf8_decode('Descripción'),'Cant.','Serial',utf8_decode('Observación'));
        //$campos = utf8_decode($campos);
		$Ancho = array ('15','75','15','20','55');
		$Alinear = array ('L','L','C','L','L');
		$Bordes = array ('1','1','1','1','1');
        $this->enc_detalle($campos,$Ancho,7,'','',$Alinear,$Bordes);
         $campos = array ('');
        $Ancho = array ('190');
        $this->enc_detalle($campos,$Ancho,5);}

//Pie de página
function Footer()
{
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
  $EstadoCuenta =  "sipre_contabilidad.deprecactivos";
/*}else{
  $EstadoCuenta =  "cuenta";
}*/  
	   
	    $sTabla=$EstadoCuenta;
		$anoMes = date("Y").date("m");
        $sCondicion=" Ubicacion = ".$_GET['ubicacion']." AND Departamento = ".$_GET['departamento']." AND Responsable = ".$_GET['responsable']." ORDER BY Codigo";
	   $sCampos='Codigo';
        $sCampos.=', Descripcion';
        $sCampos.=", '1' AS Cantidad";
        $sCampos.=", '' AS Serial";
        $sCampos.=", Observaciones";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' where '. $sCondicion ;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
       if ( NumeroFilas($exc)>0){
     		$iFila = -1;
            while ($row = ObtenerFetch($exc)) {
            	$iFila++;
    			$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
                $descripcion = trim(ObtenerResultado($exc,2,$iFila));
                $cantidad = trim(ObtenerResultado($exc,3,$iFila));
                $serial = trim(ObtenerResultado($exc,4,$iFila));
                $observacion = trim(ObtenerResultado($exc,5,$iFila));
                $campos = array ($codigo,$descripcion,$cantidad,$serial,$observacion);
				$Ancho = array ('15','75','15','20','55');
				$Alinear = array ('L','L','C','L','L');
		        $pdf->enc_detalle($campos,$Ancho,5,'','',$Alinear,$Bordes);
           }
       /* $campos = array ('');
        $Ancho = array ('190');
        $pdf->enc_detalle($campos,$Ancho,5);
		
		$sqlResumen='SELECT SUM(valorlibro) from '.$sTabla. ' where '. $sCondicion;
        $exc = EjecutarExec($con,$sqlResumen) or die($sqlResumen);
		$row = ObtenerFetch($exc);
		$total = trim($row['0']) ; 
		$campos = array ('TOTAL',$total);
        $Ancho = array ('135','45');
		$Alinear = array ('C','R');
		$Bordes = array ('1','1');
        $pdf->enc_detalle($campos,$Ancho,7,'','',$Alinear,$Bordes);*/
		  
}
$pdf->Output();
?>