<?php session_start();
require('fpdf.php');
include("FuncionesPHP.php");
class PDF extends FPDF{
//Cabecera de página
	function Header(){                                                         
		//  llamando a la funcion encabezado del fpdf
    	// $logo = "logo.png";//logo del reporte
		$con = ConectarBD();                                                     
				 
		$SqlDes='Select descrip from parametros';
		$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
		while ($row = ObtenerFetch($exc)) {
			$descrip = $row[0];
		}

		$gerencia = $descrip;//gerencia del deporte
        $titulo ="REPORTES DE COMPROBANTES ";//titulo del reporte
        //$this->crear_encabezado($logo,$gerencia,$titulo);
		$this->crear_encabezado('',$gerencia,$titulo);
        //llamando a la funcion encabezado detalle del fpdf
   		// parametros para 'NombreCelda:Ancho:Alto:TamañoLetra:TipoLetra:Alignear:Bordes:TamañoLetra:'
        $campos = array ('Comprobante','Fecha','Hecho Por','Concepto','Referencia','Monto');
        $Ancho = array ('33','25','40','45','19','19');
		$Alinear = array ('L','L','L','L','L','L');
		$Bordes = array ('1','1','1','1','1','1');
        $this->enc_detalle($campos,$Ancho,7,'','',$Alinear,$Bordes);
         $campos = array ('');
        $Ancho = array ('190');
        $this->enc_detalle($campos,$Ancho,5);
	}

//Pie de página
	function Footer()	{
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
$pdf->SetFont('Arial','',12);
if ($cDesde1== ""){
   $cDesde1= '0';
   $cHasta1= '9999999';
}
if ($cDesde2 == 'ACTUALIZADOS'){
      $AcDesde = 1;
}else{
      $AcDesde = 0;
}


$con = ConectarBD();

$sCampos= " a.comprobant,Max(a.fecha),max(a.usuario_i),max(a.concepto),Max(b.documento),sum(b.debe) as Monto";
$sTabla=" enc_diario a, movimien b";
$sCondicion=" a.comprobant = b.comprobant and actualiza = $AcDesde 
	and a.comprobant between $cDesde1 and $cHasta1 
	group by a.comprobant";
$SqlStr='Select '.$sCampos.' from '.$sTabla. ' where '. $sCondicion ;
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if ( NumeroFilas($exc)>0){
	$iFila = -1;
    while ($row = ObtenerFetch($exc)) {
      	$iFila++;
		$comprobant = trim(ObtenerResultado($exc,1,$iFila));
		$fecha = obFecha(trim(ObtenerResultado($exc,2,$iFila)),'');
		$usuario_i= trim(ObtenerResultado($exc,3,$iFila));
		$concepto= trim(ObtenerResultado($exc,4,$iFila));
		$documento= trim(ObtenerResultado($exc,5,$iFila));
		$Monto= number_format(trim(ObtenerResultado($exc,6,$iFila)),2);
// parametros para 'NombreCelda:Ancho:Alto:TamañoLetra:TipoLetra:Alignear:Bordes:Maximo de longitud'
		$campos = array($comprobant,$fecha,$usuario_i,$concepto,$documento,$Monto);
		$Ancho = array ('33','25','40','45','20','19');
		$Tamaño = array ('8','8','8','8','8','8');
		$Alinear = array ('L','L','L','L','L','R');
		$Bordes = array ('0','0','0','0','0','0');
		$MaxLon = array ('0','0','0','30','0','0');
		$pdf->enc_detalle($campos,$Ancho,4,$Tamaño,'',$Alinear,$Bordes,$MaxLon);
	} 
}
$pdf->Output();
?>