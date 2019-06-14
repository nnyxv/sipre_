<?php
session_start();
include("FuncionesPHP.php");
require('idioma_modulo.php'); // Configuracion del Idioma
require('fpdf.php');

$sUsuario = $_SESSION["UsuarioSistema"];
if ($cDesde3 == 'NO'){
	$iCierre = 0;
}else{ 
	$iCierre = 1;
} 

CargarSaldos($cDesde1,$cHasta1,$cDesde2,$cHasta2,$iCierre);
$con = ConectarBD();
class PDF extends FPDF{
	var $DesdeHasta;
	
	function Header(){//Cabecera de página
		global $balanceComprobacionResumidoTitulo;
		global $desde;
		global $hasta;
		global $codigo;
		global $descripcion;
		global $saldoAnterior;
		global $debe;
		global $haber;
		global $saldoActual;
		global $totales;
		
		$con = ConectarBD();
		$sTabla = 'parametros';
		$sCondicion = '';
		$sCampos = 'fec_proceso, rif, descrip';
		$SqlStr = 'SELECT '.$sCampos.' FROM '.$sTabla;
		$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
		if (NumeroFilas($exc) > 0){
			$xDFecha = obFecha(ObtenerResultado($exc,1),'D');
			$xMFecha = obFecha(ObtenerResultado($exc,1),'M');
			$xAFecha = obFecha(ObtenerResultado($exc,1),'A');
			$_SESSION["rifEmpresa"] = ObtenerResultado($exc,2);
			$_SESSION["descrip"] = ObtenerResultado($exc,3);
		}
		
		$TituloEncabezado = '';
		$TituloEmpresa = $_SESSION["descrip"];//$_SESSION["sDesBasedeDatos"];
		$TituloRif = $_SESSION["rifEmpresa"];
		$TituloReporte = $balanceComprobacionResumidoTitulo;
		$TituloRango = $this->DesdeHasta;
		$TE=6;//COLSPAN TituloEncabezado EXCEL
		$TF=8;//COLSPAN Fecha EXCEL
		$TH=8;//COLSPAN Hora EXCEL
		$TR1=8;//COLSPAN TituloReporte EXCEL
		$TR2=8;//COLSPAN TituloRango EXCEL
		$logo = "";
		$this->crear_encabezado($logo,$TituloEmpresa,$TituloRif,$TituloEncabezado,$TituloReporte,$TituloRango,$TE,$TF,$TH,$TR1,$TR2);
		
		$this->SetXY(1,45);
   		//$this->Ln(5);
		
		$campos = array ($codigo,$descripcion,$saldoAnterior,$debe,$haber,$saldoActual);
		$Alinear = array('L','L','R','R','R','R');
		$Bordes =array('B','B','B','B','B','B');
		$Ancho = array ('20','65','30','30','30','30');
        $TamañoLetra=array ('8','8','8','8','8','8');
		$TipoLetra=array ('B','B','B','B','B','B');
        $this->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
	}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->AliasNbPages();

$pdf->DesdeHasta = $desde." " .obFecha($cDesde1)." ".$hasta." ".obFecha($cHasta1);
$pdf->ExceloPdf = $ExceloPdf;
$pdf->nameExcel = "BalancedecomprobacionResumido";
$pdf->AddPage();

$TotalSaldoAnterior = 0;
$TotalDebe = 0;
$TotalHaber = 0;
$TotalSaldoActual = 0; 

if ($cDesde3 == 'NO'){
   	$SqlStr=" select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe as debe, a.haber ";
}else{ 
	$SqlStr=" select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe + a.debe_cierr  as debe, a.haber + a.haber_cierr as haber ";
} 
				
$SqlStr.=" from cuentageneral a where Deshabilitar = '0' and (a.saldo_ant <> 0 or a.debe <> 0 or a.haber <> 0) and usuario = '$sUsuario' and 
					length(codigo) <= 5 order by codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if(NumeroFilas($exc)>0){
	$iFila = -1;
	$pdf->Ln(3); 
    while ($row = ObtenerFetch($exc)) {
		$iFila++;
		$pdf->SetX(1); 
		
		$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
		$descripcion = trim(ObtenerResultado($exc,2,$iFila)) ; 
		$Sal_ant = number_format(trim(ObtenerResultado($exc,3,$iFila)), 2); 
		$Debe = number_format(trim(ObtenerResultado($exc,4,$iFila)),2); 
		$Haber = number_format(trim(ObtenerResultado($exc,5,$iFila)),2) ; 
		$SaldoActual1 = bcadd(ObtenerResultado($exc,3,$iFila),ObtenerResultado($exc,4,$iFila),2);
		$SaldoActual = bcsub($SaldoActual1,ObtenerResultado($exc,5,$iFila),2);
		//$SaldoActual = number_format(strval(trim(ObtenerResultado($exc,3,$iFila))) + strval(trim(ObtenerResultado($exc,4,$iFila))) - trim(ObtenerResultado($exc,5,$iFila)),2);
		$SaldoActual = number_format($SaldoActual,2);
			
		if(strlen(trim($codigo)) == 1 or (strlen(trim($codigo)) == 2 and substr(trim($codigo),1,1) == ".")){
			$TotalSaldoAnterior = bcadd($TotalSaldoAnterior , ObtenerResultado($exc,3,$iFila),2);
			$TotalDebe = bcadd($TotalDebe , ObtenerResultado($exc,4,$iFila),2);
			$TotalHaber =bcadd($TotalHaber , ObtenerResultado($exc,5,$iFila),2);				
			$SumaSaldoAntDebe = bcadd(ObtenerResultado($exc,3,$iFila) , ObtenerResultado($exc,4,$iFila),2);  
			$TotalSaldoActual = bcadd($TotalSaldoActual,(bcsub($SumaSaldoAntDebe,ObtenerResultado($exc,5,$iFila),2)),2);
		}			
			

					/*if (strlen(trim($codigo)) == 1 or strlen(trim($codigo)) == 2){
						$campos = array ('','','','','','');
                                                 $Ancho = array ('20','65','30','30','30','30');
            					$TamañoLetra=array ('8','7','8','8','8','8');
						$Bordes = array ('1','1','1','1','1','1');
						$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,'',$Alinear,$MaxLon,$Bordes);

					}*/	
		if (strlen(trim($codigo)) < 5){
        	$pdf->Ln(3);
    	}				
				
		$pdf->SetX(1); 

		$campos = array ($codigo,$descripcion,$Sal_ant,$Debe,$Haber,$SaldoActual);
		$Alinear = array('L','L','R','R','R','R');
				$Ancho = array ('20','65','30','30','30','30');
		$TamañoLetra=array ('8','7','8','8','8','8');
		if (strlen(trim($codigo)) < 5){
			$TipoLetra=array ('B','B','B','B','B','B');
		}else{
		   $TipoLetra=array ('','','','','','');
		}
		$MaxLon=array (0,33,0,0,0,0);
		$Bordes = array (0,0,0,0,0,0);
		$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,$TipoLetra,$Alinear,$Bordes,$MaxLon);													
	}
	
	$campos = array ("","Totales:",number_format($TotalSaldoAnterior,2),number_format($TotalDebe,2),number_format($TotalHaber,2), number_format($TotalSaldoActual,2));
	$Alinear = array('L','R','R','R','R','R');
	$Ancho = array ('21','55','30','30','30','30');
	$TamañoLetra=array ('8','8','8','8','8','8');
	$Bordes =array('','','T','T','T','T');
	$TipoLetra=array ('','B','B','B','B','B');

	$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
}	

//auditoria
auditoria('consulta','cuentageneral',$sCampos,'consulta balance de comprobacion resumido, rango: '.$cDesde1." - ".$cHasta1);
//fin auditoria

$pdf->Output();
?>