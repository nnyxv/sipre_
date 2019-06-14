<?php
require('fpdf.php');
include("FuncionesPHP.php");

class PDF extends FPDF{
	var $FechaDesde;
	var $FechaHasta;
	//Cabecera de página
	function Header(){
		
		$con = ConectarBD();                                                     
				 
		$SqlDes='Select descrip from parametros';
		$exc = EjecutarExec($con,$SqlDes) or die($SqlDes);
		while ($row = ObtenerFetch($exc)) {
			$descrip = $row[0];
		}
		
    		$TituloEncabezado=''; 
		$TituloEmpresa=$descrip;
		$TituloRif=$_SESSION["rifEmpresa"];
	    	$TituloReporte= 'MAYOR ANALÍTICO';
		$TituloRango='Desde '. $this->FechaDesde . ' Hasta ' .$this->FechaHasta; 
		$TE=6;//COLSPAN TituloEncabezado EXCEL
		$TF=2;//COLSPAN Fecha EXCEL
		$TH=8;//COLSPAN Hora EXCEL
		$TR1=8;//COLSPAN TituloReporte EXCEL
		$TR2=8;//COLSPAN  TituloRango EXCEL	
		$logo = "";
		$this->crear_encabezado($logo,$TituloEmpresa,$TituloRif,$TituloEncabezado,$TituloReporte,$TituloRango,$TE,$TF,$TH,$TR1,$TR2);
	}

	//Pie de página
	function Footer(){
        //Posición: a 1,5 cm del final
        $this->SetY(-15);
        //Arial italic 8
        $this->SetFont('Arial','I',8);
        //Número de página
        $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
	}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->FechaDesde = obFecha($cDesde2);
$pdf->FechaHasta = obFecha($cHasta2);
$pdf->ExceloPdf = $ExceloPdf;
$pdf->nameExcel = "MayorAnalitico";
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',7);

if($cDesde3 == 'Fecha'){
	$OrdenSel = "fecha,documento";
}elseif($cDesde3 == 'Comprobante'){
	$OrdenSel = "comprobant";
}elseif($cDesde3 == 'Referencia'){
	$OrdenSel = "documento";
}elseif($cDesde3 == 'Debe'){
	$OrdenSel = "debe";  
}elseif($cDesde3 == 'Haber'){
	$OrdenSel = "haber";  
} 

$con = ConectarBD();
$sUsuario = $_SESSION["UsuarioSistema"];

//MovimientosH($cDesde2,$cHasta2);

CargarSaldosAnalitico($cDesde2,$cHasta2);

$sTabla="cuentageneral a left join movimientempgeneral b on a.codigo = b.codigo and a.usuario = b.usuario and b.fecha between '$cDesde2' and '$cHasta2'";
$sCondicion=" a.codigo between '$cDesde1' and '$cHasta1'";
$sCondicion.= " and a.usuario = '$sUsuario'";
$sCondicion.= "  order by a.codigo,b.$OrdenSel,b.numero";

$sCampos='b.Fecha';
$sCampos.=',b.comprobant';
$sCampos.=',b.Descripcion';
$sCampos.=',b.Documento';
$sCampos.=',b.Debe';
$sCampos.=',b.Haber';
$sCampos.=',a.codigo';
$sCampos.=',a.Descripcion';
$sCampos.=',a.saldo_ant';
$sCampos.=',b.cc';

$SqlStr='Select '.$sCampos.' from '.$sTabla. ' where '. $sCondicion ;

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	
if(NumeroFilas($exc)>0){
	$iFila = -1;
	$CuentaAnt = '';
	$sPrimera = "SI";
	$comprobant_ant ="";
	$fecha_ant ="";
	$cc_ant="";
	$sumadebe = 0;
	$sumahaber = 0;
	$ij = 0;
           
	while ($row = ObtenerFetch($exc)) {
		$contador++;
		$iFila++;
		$ij++;
		$Fecha = obFecha(trim(ObtenerResultado($exc,1,$iFila))); 
		$comprobant = trim(ObtenerResultado($exc,2,$iFila));
		$Descripcion = trim(ObtenerResultado($exc,3,$iFila)) ; 
		$Documento = trim(ObtenerResultado($exc,4,$iFila));
		$Debe = trim(ObtenerResultado($exc,5,$iFila)); 
		$Haber = trim(ObtenerResultado($exc,6,$iFila));
		$CodigoAct = trim(ObtenerResultado($exc,7,$iFila));
		$DescripcionCuen = trim(ObtenerResultado($exc,8,$iFila));
		$Saldo_ant = trim(ObtenerResultado($exc,9,$iFila));
		$CC = trim(ObtenerResultado($exc,10,$iFila));
		
		if(is_null($comprobant) || $comprobant == ""){
			$co = 'Código';
			$campos = array ($co,$CodigoAct, '  ' . $DescripcionCuen,'Saldo Anterior:',number_format($Saldo_ant,2));
			$Ancho = array ('15','35','70','35','35');
			$Alinear = array('L','L','L','R','R');
			$Bordes =array('0','0','0','0','0');
			$TamañoLetra=array ('8','8','8','8');
			$TipoLetra=array ('B','','','B','');
			$MaxLon=array (0,0,75,0,0);
			$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes,$MaxLon);
		}else{
			if ($CuentaAnt != $CodigoAct){ 
				if($sumadebe !=0 or $sumahaber != 0){
					$campos = array ('','','','','Total',number_format($sumadebe,2),number_format($sumahaber,2),'');
					$Ancho = array ('12','7','7','90','15','20','20','20');
					$Alinear = array('L','R','L','L','L','R','R','R');
					$Bordes =array('B','B','B','B','B','TB','TB','B');
					$TamañoLetra=array ('6','6','6','6','6','6','6','6');
					$TipoLetra=array ('B','B','B','B','B','B','B','B');
					$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
					
					if ($pdf->ExceloPdf!="E"){
						$pdf->AddPage(); 
					}
					$ij=1;
				}				
				$sumadebe =0;
				$sumahaber=0;
			
				$pdf->Ln(5); 
				$CuentaAnt = $CodigoAct;
				
				if ($sPrimera == "NO"){
				  /*  $campos = array (str_repeat (' - ',60));
				   $Ancho = array ('190');
				   $Alinear = array('L','L','L','R','R');
				   $Bordes =array('');
				   $TamañoLetra=array ('10');
				   $TipoLetra=array ('');
					$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes); */
				}
				
				$sPrimera = "NO";
				$campos = array (utf8_encode('Código'),$CodigoAct,$DescripcionCuen,'Saldo Anterior:',number_format($Saldo_ant,2));
				$Ancho = array ('15','35','70','35','35');
				$Alinear = array('L','L','L','R','R');
				$Bordes =array('0','0','0','0','0');
				$TamañoLetra=array ('8','8','8','8');
				$TipoLetra=array ('B','','','B','');
				$MaxLon=array (0,0,75,0,0);
				
				$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes,$MaxLon);
					
				$campos = array ('Fecha','Com.','CC',utf8_decode('Descripción'),'Documento','Debe','Haber','Saldo Actual');
				$Ancho = array ('12','7','7','90','15','20','20','20');
					//$Ancho = array ('12','7','7','80','15','15','15','25');
				$Alinear = array('L','R','L','L','L','R','R','R');
				$Bordes =array('B','B','B','B','B','B','B','B');
				$TamañoLetra=array ('8','8','8','8','8','8','8','8');
				$TipoLetra=array ('B','B','B','B','B','B','B','B');
				
				$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
				   
				$saldo= $Saldo_ant+$Debe-$Haber;
			}else{
				$saldo= $saldo+$Debe-$Haber;	
			}
			
			$sumadebe = bcadd($sumadebe,$Debe,2);
			$sumahaber = bcadd($sumahaber,$Haber,2);
			$Fecha = str_replace("/","-",$Fecha);
						
			if ($comprobant_ant !=$comprobant or $fecha_ant != $Fecha or $cc_ant != $CC){
				$comprobant_ant =$comprobant;
				$fecha_ant =$Fecha;
				$cc_ant=$CC;
			//if(!is_null($comprobant) && $comprobant != ""){
				$Tabla1 ="enc_general c";
				$sCondicion= "  c.comprobant = $comprobant ";
				$sCondicion.= " and c.fecha = '". date('Y-m-d',strtotime($Fecha)) ."'";
				$sCondicion.= " and c.cc = '$CC'";	
				$sCampos=' c.Concepto';
				$SqlStr='Select '.$sCampos.' from '.$Tabla1. ' where '. $sCondicion ;
				
				$exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);
				
				if ( NumeroFilas($exc1)>0){
					$Concepto = trim(ObtenerResultado($exc1,1,0)); 
				}
		//}	
			}
			//echo "$Fecha,$comprobant,$CC,$Descripcion / $Concepto ,$Documento,$Debe,$Haber, $saldo";
		} 	
		
		if($comprobant != ""){
			$campos = array ($Fecha,$comprobant,$CC,substr($Descripcion,0,70),$Documento,number_format($Debe,2),number_format($Haber,2), number_format($saldo,2));
			$Ancho = array ('12','7','7','90','15','20','20','20');
			$Alinear = array('L','R','L','L','L','R','R','R');
			$Bordes =array('0','0','0','0','0','0','0','0');
			$TamañoLetra=array ('6','6','7','6','6','7','7','7');
			$TipoLetra=array ('','','','','','','','');
			$TMax=array ('','','',90,'','','','');
			
			$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,$TipoLetra,$Alinear,$Bordes,$TMax);
			
			if($ij == 73){
				$pdf->Ln(5); 
			   $campos = array (utf8_decode('Código'),$CodigoAct,$DescripcionCuen,'Saldo Anterior:',number_format($Saldo_ant,2));
				$Ancho = array ('15','35','70','35','35');
				$Alinear = array('L','L','L','R','R');
				$Bordes =array('0','0','0','0','0');
				$TamañoLetra=array ('8','8','8','8');
				$TipoLetra=array ('B','','','B','');
				$MaxLon=array (0,0,75,0,0);
					
				$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes,$MaxLon);
					
				$campos = array ('Fecha','Com.','CC',utf8_decode('Descripción'),'Documento','Debe','Haber','Saldo Actual');
				$Ancho = array ('12','7','7','90','15','20','20','20');
					//$Ancho = array ('12','7','7','80','15','15','15','25');
				$Alinear = array('L','R','L','L','L','R','R','R');
				$Bordes =array('B','B','B','B','B','B','B','B');
				$TamañoLetra=array ('8','8','8','8','8','8','8','8');
				$TipoLetra=array ('B','B','B','B','B','B','B','B');
				
				$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
				
				$ij = 0;
			}
		} 
	} 
		
	if($sumadebe !=0 or $sumahaber != 0){
		$campos = array ('','','','','Total',number_format($sumadebe,2),number_format($sumahaber,2),'');
					  // $Ancho = array ('12','7','7','80','15','15','15','25');
	    $Ancho = array ('12','7','7','90','15','20','20','20');
		$Alinear = array('L','R','L','L','L','R','R','R');
		$Bordes =array('B','B','B','B','B','TB','TB','B');
		$TamañoLetra=array ('6','6','6','6','6','6','6','6');
		$TipoLetra=array ('B','B','B','B','B','B','B','B');
					   
		$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
		
		$ij++;
	}				     
}

//auditoria
auditoria('consulta','cuentageneral/movimientempgeneral/enc_general',$sCampos,'consulta mayor analítico, rango: '.$cDesde2." - ".$cHasta2);
//fin auditoria

$pdf->Output();
               /* $Fecha = trim(ObtenerResultado($exc,1,$iFila)) ; 
                $comprobant = trim(ObtenerResultado($exc,2,$iFila));
				$Descripcion = trim(ObtenerResultado($exc,3,$iFila)) ; 
                $Documento = trim(ObtenerResultado($exc,4,$iFila));
				$Debe = trim(ObtenerResultado($exc,5,$iFila)) ; 
                $Haber = trim(ObtenerResultado($exc,6,$iFila));
		        $r_campos = array ($Fecha,$comprobant,$Descripcion,$Documento,$Debe,$Haber);
                $celdas = array ('5','5','5','5','5','15','5');
                $pdf->enc_detalle($r_campos,$r_celdas,3,'L');*/
?>