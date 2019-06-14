<?php  session_start();
require('fpdf.php');
include("FuncionesPHP.php");
$sUsuario = $_SESSION["UsuarioSistema"];
class PDF extends FPDF{
	var $DesdeHasta;
	//Cabecera de página
	function Header(){
      	//Nombre de la Empresa 
		
		$con = ConectarBD();                                                     
				 
		$SqlDes='Select descrip from parametros';
		$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
		while ($row = ObtenerFetch($exc)) {
			$descrip = $row[0];
		}
		
        $this->SetFont('Arial','B',12);
        $this->SetXY(1, 10); 
		$Empresa = $descrip;
	    $this->Cell(45,10,$Empresa,0,0,'L');

      $lUbi = 175;

      //Colocar pagina
		$this->SetFont('Arial','B',6);
		$this->SetXY($lUbi, 10); 
		$Pagina='Página: '.$this->PageNo().'/{nb}' ;
		$this->Cell(30,5,$Pagina,0,0,'L');	

      //Colocar Fecha y hora
		$this->SetFont('Arial','B',6);
        $this->SetXY($lUbi, 15); 
		$fecha = date("d/m/Y");
		$hora = date("g:i:s A");
	    $this->Cell(30,5, $fecha.'  '.$hora ,0,0,'L');
		
		//Colocar Usuario
		$this->SetFont('Arial','B',6);
		$this->SetXY($lUbi, 20); 
		$Usuario='Emitido Por: '.$_SESSION['UsuarioSistema'];
		$this->Cell(30,5,$Usuario,0,0,'L');	
		
		//Colocar Usuario
		$this->SetFont('Arial','B',10);
		$this->SetXY(1, 20); 
		$Titulo='Comprobantes Descuadrados';
		$this->Cell(180,5,$Titulo,0,1,'C');	             
		$this->Cell(160,5,$this->DesdeHasta,0,0,'C');	             

		
		$this->SetXY(5,30);	
   		//$this->Ln(5);	             

        $campos = array ('Comprobante','Centro Costo','Fecha','Debe','Haber','Diferencia');
		$Alinear = array('L','L','R','R','R','R');
		$Bordes =array('B','B','B','B','B','B');
		$Ancho = array ('20','20','25','30','30','30');
        $TamañoLetra=array ('8','8','8','8','8','8');
		$TipoLetra=array ('B','B','B','B','B','B');
        $this->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->AliasNbPages();

$pdf->DesdeHasta = "";
$pdf->AddPage();

$conAd = ConectarBD();
					$Sql = "select comprobant,cc,fecha,sum(Debe),Sum(Haber) from 
					sipre_contabilidad.movimien  group by comprobant,cc,fecha having round(sum(Debe),2) <> round(Sum(Haber),2)
					union all
					select comprobant,cc,fecha,sum(Debe),Sum(Haber) from 
					sipre_contabilidad.movimiendif group by comprobant,cc,fecha having round(sum(Debe),2) <> round(Sum(Haber),2)
					 order by fecha" ; 
				    $exc1 = EjecutarExec($conAd,$Sql) or die($Sql);
			$iFila=-1;
			if($exc1 != ""){
					$pdf->Ln(3);
			       $campos = array ($DesTabla);
					$Alinear = array('L');
					$Ancho = array ('20');
					$TamañoLetra=array ('8');
					$MaxLon=array (0);
					$TipoLetra=array ('B');
					$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,$TipoLetra,$Alinear,$MaxLon);
					$pdf->Ln(3);
					
			   while ($row1=ObtenerFetch($exc1)){
			   $iFila++;
				$comprobant = $row1[0]; 
				$cc = $row1[1];
				$fecha = obFecha($row1[2]); 
				$Debe = number_format($row1[3],2); 
				$Haber = number_format($row1[4],2) ; 
				$Diferencia =  number_format(bcsub($row1[3],$row1[4],2),2);
				
			
					$campos = array ($comprobant,$cc,$fecha,$Debe,$Haber,$Diferencia);
					$Alinear = array('L','L','R','R','R','R');
					$Ancho = array ('20','20','25','30','30','30');
					$TamañoLetra=array ('8','8','8','8','8','8');
					$MaxLon=array (0,25,0,0,0,0);
					$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,'',$Alinear,$MaxLon);
			  }	
			 } 
			 
		
		
$pdf->Output();
?>