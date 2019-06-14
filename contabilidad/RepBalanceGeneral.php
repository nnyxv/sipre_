<?php
require('fpdf.php');
include("FuncionesPHP.php");
class PDF extends FPDF{
	var $titulo_cen;
	var $FechaDesde;
	//Cabecera de página
	function Header(){
		$TituloEncabezado=''; 
		$TituloEmpresa=$_SESSION["sDesBasedeDatos"];
		$TituloRif=$_SESSION["rifEmpresa"];
		$TituloReporte=$this->titulo_cen;
		$TituloRango="AL ".$this->FechaDesde; 
		$TE=6;//COLSPAN TituloEncabezado EXCEL
		$TF=8;//COLSPAN Fecha EXCEL
		$TH=8;//COLSPAN Hora EXCEL
		$TR1=8;//COLSPAN TituloReporte EXCEL
		$TR2=8;//COLSPAN  TituloRango EXCEL	
		$logo = "";
		$this->crear_encabezado($logo,$TituloEmpresa,$TituloRif,$TituloEncabezado,$TituloReporte,$TituloRango,$TE,$TF,$TH,$TR1,$TR2);
			
	//        $this->crear_encabezado($logo,$gerencia,$titulo);
			//llamando a la funcion encabezado detalle del fpdf
			/*$campos = array ('Codigo','Nombre de Cuenta','Saldo Anterior','Debe','Haber','Saldo Actual');
			$celdas = array ('20','50','30','25','25','30');
			$this->enc_detalle($campos,$celdas,1);*/
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
$con = ConectarBD();
$SqlStr="select titulo_cen from formatos where formato = '$cDesde2'"; 
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$pdf->titulo_cen = trim(ObtenerResultado($exc,1,0));
$pdf->FechaDesde = obFecha($cDesde1);
$pdf->ExceloPdf = $ExceloPdf;
$pdf->nameExcel = "BalancePersonalizado";
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

$sUsuario = $_SESSION["UsuarioSistema"];        
$cHasta1 = substr($cDesde1,0,7).'-01';

if ($cDesde3 == "NO"){
	$iCierre = 0;
}else{
	$iCierre = 1;
}

CargarSaldos($cHasta1,$cDesde1,'','',$iCierre); 
/*$SqlStr="call CargarFormatos('$cDesde','$sUsuario')";
$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());*/
CargarFormatos($cDesde2,$sUsuario);
 
$SqlStr="select * from temppersonalizados where usuario = '$sUsuario' order by id";
$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
if(NumeroFilas($exc)>0){
	$iFila = -1;
    
	while ($row = ObtenerFetch($exc)) {
		$iFila++;
		$ubicacion = trim(ObtenerResultado($exc,1,$iFila));
		$titulo  = trim(ObtenerResultado($exc,2,$iFila)) ;
		$Saldo = number_format(trim((ObtenerResultado($exc,3,$iFila))),2);
		$Saldo=parentesis($Saldo);
		$Subra = trim(ObtenerResultado($exc,6,$iFila));
				
		$Bordes= "";
		$campos = "";
		$TipoLetra="";
		$TipoLetra=array('','','','');
		
		if($ubicacion=='SOTI' || $ubicacion=='LINE'){
        	$campos = array($titulo,"","","");
			$TipoLetra=array('B','','','');
		}					
				
		if ($ubicacion=='TITU' || $ubicacion=='DETA'){
			$campos = array ($titulo,$Saldo,"","");
			if($Subra != "NO"){
				$Bordes= array ('0','B','','');
			}
			
			if($ubicacion=='TITU'){
				$TipoLetra=array ('B','B','','');
			}
		} 
				
		if($ubicacion=='TITU2' || $ubicacion=='DETA2'){
			$campos = array ($titulo,"",$Saldo,"");
			
			if ($Subra != "NO"){
				$Bordes= array ('','','B','');
			}
			
			if($ubicacion=='TITU2'){
				$TipoLetra=array ('B','','B','');
			}
		}
			
		if ($ubicacion=='TITU3' || $ubicacion=='DETA3'){
			$campos = array ($titulo,"","",$Saldo);
			if($Subra != "NO"){
				$Bordes= array ('','','','B');
			}
			
			if($ubicacion=='TITU3'){
				$TipoLetra=array ('B','','','B');
			}
		}
				
		$Ancho = array ('100','30','30','30');
	   	$Alinear = array ('L','R','R','R');	
								 
		$pdf->enc_detallePre($campos,$Ancho,5,'',$TipoLetra,$Alinear,$Bordes);
		
		if ($Subra == "DB"){
			$campos="";
			$campos= array('','','','');	
			
			$pdf->enc_detallePre($campos,$Ancho,2,'',$TipoLetra,$Alinear,$Bordes);				
		}
	} 
}

//auditoria
auditoria('consulta','temppersonalizados',$sCampos,'consulta balance personalizado, fecha desde: '.$cDesde1);
//fin auditoria

$pdf->Output();
?>