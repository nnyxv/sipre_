<?php session_start();
require('fpdf.php');
include_once("FuncionesPHP.php");
class PDF extends FPDF{
var $TituloFormato;

//Cabecera de página
	function Header(){                                                         
      //  llamando a la funcion encabezado del fpdf
        $logo = "";//logo del reporte
        $gerencia = $_SESSION["sDesBasedeDatos"];//gerencia del deporte
        $titulo ="Configuracion de ".$this->TituloFormato;//titulo del reporte
        $this->crear_encabezado($logo,$gerencia,$titulo);
        //llamando a la funcion encabezado detalle del fpdf
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

if ($cDesde1== ""){
   $cDesde1= '';
   $cHasta1= 'zzzzzzzzzzzzzzzzzzzz';
}

$con = ConectarBD();

$sCampos =" a.formato,a.descripcion,b.orden,b.titulo,b.ubicacion,b.subrayado
,c.codigocuenta,d.descripcion,c.PoN,b.numero";
$sTabla =" formatos a,cuenta d, balance_a b left join cuentasconfiguradas c on (b.numero = c.numerorenglon and c.codigoformato = b.formato) ";
$sCondicion = " a.formato  = '$cDesde1'
and a.formato = b.formato
and d.codigo = c.codigocuenta
order by a.formato,b.orden";

$SqlStr='Select '.$sCampos.' from '.$sTabla. ' where '. $sCondicion ;		
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$pdf=new PDF();
$Primera = true;

if(NumeroFilas($exc)>0){
	$iFila = -1;
	$sNumeroAnt = "";
    
	while ($row = ObtenerFetch($exc)) {
    	$iFila++;
		$Formato = trim(ObtenerResultado($exc,1,$iFila)) ; 
		$Descripcion = trim(ObtenerResultado($exc,2,$iFila));
		$Orden = trim(ObtenerResultado($exc,3,$iFila));
		$Titulo = trim(ObtenerResultado($exc,4,$iFila));
		$Ubicacion = trim(ObtenerResultado($exc,5,$iFila));
		$Subrayado = trim(ObtenerResultado($exc,6,$iFila));
		$CodigoCuenta = trim(ObtenerResultado($exc,7,$iFila));
		$DescripcionCuenta = trim(ObtenerResultado($exc,8,$iFila));
		$PoN = trim(ObtenerResultado($exc,9,$iFila));
		$Numero = trim(ObtenerResultado($exc,10,$iFila));
				
		if($Primera){
			$pdf->TituloFormato= $Descripcion; 
			$pdf->AliasNbPages();
			$pdf->AddPage();
			$pdf->SetFont('Arial','',10);
			$Primera = false;
		}
				
		if($Numero != $sNumeroAnt){
			$sNumeroAnt = $Numero;
			$campos = array ($Orden,$Titulo,$Ubicacion,$Subrayado);
			$Ancho = array ('20','100','30','20');
			$Alinear = array ('L','L','L','L');
			$Bordes = array ('0','0','0','0');
			$TipoLetras = array ('B','B','B','B');
			$TamañoLetra = array ('8','8','8','8');
			$pdf->enc_detalle($campos,$Ancho,4,$TamañoLetra,$TipoLetras,$Alinear,$Bordes);
						
			$campos = array ($CodigoCuenta,$DescripcionCuenta);
			$Ancho = array ('20','100');
			$Alinear = array ('L','L');
			$Bordes = array ('0','0');
			$TamañoLetra = array ('7','7');
			$pdf->enc_detalle($campos,$Ancho,4,$TamañoLetra,'',$Alinear,$Bordes);
				
		}else{
			$campos = array ($CodigoCuenta,$DescripcionCuenta);
			$Ancho = array ('20','100');
			$Alinear = array ('L','L');
			$Bordes = array ('0','0');
			$TamañoLetra = array ('7','7');
			$pdf->enc_detalle($campos,$Ancho,4,$TamañoLetra,'',$Alinear,$Bordes);
		}
	} 
}

//auditoria
auditoria('consulta','formatos/cuenta/balance_a/cuentasconfiguradas',$sCampos,'consulta reporte configurac, rango: '.$cDesde1." - ".$cHasta1);
//fin auditoria

$pdf->Output();
?>