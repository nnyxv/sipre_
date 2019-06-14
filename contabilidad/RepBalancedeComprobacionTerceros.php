<?php session_start();
require('fpdf.php');
include("FuncionesPHP.php");
$sUsuario = $_SESSION["UsuarioSistema"];

if ($cDesde3 == 'NO'){
   $iCierre = 0;
}else{ 
   $iCierre = 1;
} 
CargarSaldos($cDesde1,$cHasta1,$cDesde2,$cHasta2,$iCierre);
CargarSaldosTerceros($cDesde1,$cHasta1,$cDesde2,$cHasta2,$iCierre);


$con = ConectarBD();
class PDF extends FPDF{
	var $DesdeHasta;
//Cabecera de página
	function Header(){
		$TituloEncabezado=''; 
		$TituloEmpresa=$_SESSION["sDesBasedeDatos"];
		$TituloRif=$_SESSION["rifEmpresa"];
	    $TituloReporte='BALANCE DE COMPROBACION TERCEROS';
		$TituloRango=$this->DesdeHasta; 
		$TE=6;//COLSPAN TituloEncabezado EXCEL
		$TF=8;//COLSPAN Fecha EXCEL
		$TH=8;//COLSPAN Hora EXCEL
		$TR1=8;//COLSPAN TituloReporte EXCEL
		$TR2=8;//COLSPAN  TituloRango EXCEL	
		$logo = "";
		$this->crear_encabezado($logo,$TituloEmpresa,$TituloRif,$TituloEncabezado,$TituloReporte,$TituloRango,$TE,$TF,$TH,$TR1,$TR2);

		$this->SetXY(1,45);	
   		//$this->Ln(5);	             

        $campos = array ('Codigo','Nombre de Cuenta','Saldo Anterior','Debe','Haber','Saldo Actual');
		$Alinear = array('L','L','R','R','R','R');
		$Bordes =array('B','B','B','B','B','B');
		$Ancho = array ('15','70','30','30','30','30');
        $TamañoLetra=array ('8','8','8','8','8','8');
		$TipoLetra=array ('B','B','B','B','B','B');
        $this->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
	}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->AliasNbPages();
$pdf->ExceloPdf = $ExceloPdf;
$pdf->nameExcel = "BalancedecomprobacionTerceros";
$pdf->DesdeHasta = " Desde " .obFecha($cDesde1) ." Hasta " . obFecha($cHasta1);
$pdf->AddPage();

$TotalSaldoAnterior = 0;
$TotalDebe = 0;
$TotalHaber = 0;
$TotalSaldoActual = 0; 

if ($cDesde3 == 'NO'){
	$SqlStr=" select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe as debe, a.haber,b.terceros ";
}else{ 
    $SqlStr=" select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe + a.debe_cierr  as debe, a.haber + a.haber_cierr as haber,b.terceros ";
} 

$SqlStr.=" from cuentageneral a,cuenta b where a.Deshabilitar = '0' and (a.saldo_ant <> 0 or a.debe <> 0 or a.haber <> 0) and usuario = '$sUsuario' and a.codigo = b.codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if(NumeroFilas($exc)>0){
	$iFila = -1;
	$pdf->Ln(3); 
    
	while($row = ObtenerFetch($exc)) {
    	$iFila++;
		$pdf->SetX(1); 
				
		$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
		$descripcion = trim(ObtenerResultado($exc,2,$iFila)) ; 
		$Sal_ant = number_format(trim(ObtenerResultado($exc,3,$iFila)), 2); 
		$Debe = number_format(trim(ObtenerResultado($exc,4,$iFila)),2); 
		$Haber = number_format(trim(ObtenerResultado($exc,5,$iFila)),2) ; 
		$terceros = trim(ObtenerResultado($exc,6,$iFila)); 
	//	echo "$codigo , $terceros  <br>";
		
		$SaldoActual1 = bcadd(ObtenerResultado($exc,3,$iFila),ObtenerResultado($exc,4,$iFila),2);
		$SaldoActual = bcsub($SaldoActual1,ObtenerResultado($exc,5,$iFila),2);
				
				// $SaldoActual = number_format(strval(trim(ObtenerResultado($exc,3,$iFila))) + strval(trim(ObtenerResultado($exc,4,$iFila))) - trim(ObtenerResultado($exc,5,$iFila)),2);
			
		if(strlen(trim($codigo)) == 1 or (strlen(trim($codigo)) == 2 and substr(trim($codigo),1,1) == ".")){
			$TotalSaldoAnterior = bcadd($TotalSaldoAnterior , ObtenerResultado($exc,3,$iFila),2);
			$TotalDebe = bcadd($TotalDebe , ObtenerResultado($exc,4,$iFila),2);
			$TotalHaber =bcadd($TotalHaber , ObtenerResultado($exc,5,$iFila),2);				
			$SumaSaldoAntDebe = bcadd(ObtenerResultado($exc,3,$iFila) , ObtenerResultado($exc,4,$iFila),2);  
			$TotalSaldoActual = bcadd($TotalSaldoActual,(bcsub($SumaSaldoAntDebe,ObtenerResultado($exc,5,$iFila),2)),2);
		}			
					
		$SaldoActual = number_format($SaldoActual,2);
		$campos = array ($codigo,$descripcion,parentesis($Sal_ant),$Debe,$Haber,parentesis($SaldoActual));
		$Alinear = array('L','L','R','R','R','R');
		$Ancho = array ('15','70','30','30','30','30');


		$TamañoLetra=array ('6','7','7','7','7','7');
		$MaxLon=array (0,25,0,0,0,0);
		
		$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,'',$Alinear,$MaxLon);
					
					/* VERIFICAR SI LA CUENTA ES DE TERCEROS */
					/*$SqlStr="select count(*) from cuenta where codigo = '$codigo' and terceros = 'SI'";
					$excTerceros = EjecutarExec($con,$SqlStr) or die($SqlStr);
					$contadorTerceros = ObtenerResultado($exc,1,0);*/
		$sUsuario = $_SESSION["UsuarioSistema"];
		if($terceros == 'SI'){
						/*$SqlStr="select max(tabla) as tabla,max(campos) as campos,
						sum(debe) as debe,sum(haber) as haber,idobjeto,sum(saldo_ant),combinado  
						from cuentatercerosgeneral 
						where cuenta = '$codigo' and usuario = '$sUsuario' and (saldo_ant <> 0 or debe <> 0 or haber <> 0) GROUP BY COMBINADO";
						*/
						
			$SqlStr="select max(tabla) as tabla,max(campos) as campos,
			sum(debe) as debe,sum(haber) as haber,max(idobject) as idobject,0,combinado  
			from movimiendif 
			where codigo = '$codigo' 
			and fecha between '$cDesde1' and '$cHasta1' 
			and (debe <> 0 or haber <> 0) and not combinado is null GROUP BY COMBINADO";
							
							
							/*$SqlStr="select tabla as tabla,campos as campos,
							debe as debe,haber as haber,idobject,0,combinado  
							from movimiendif 
							where codigo = '$codigo' 
							and fecha between '$cDesde1' and '$cHasta1' 
							and (debe <> 0 or haber <> 0) and not combinado is null";*/
			$excTerceros = EjecutarExec($con,$SqlStr) or die($SqlStr);
			$Fila3ro= -1; 
							
			while ($rowTerceros = ObtenerFetch($excTerceros)) {
				$Fila3ro++;
				$Tabla3ro    = ObtenerResultado($excTerceros,1,$Fila3ro);
				$Campos3ro   = explode("|",ObtenerResultado($excTerceros,2,$Fila3ro));
				$Debe3ro     = number_format(ObtenerResultado($excTerceros,3,$Fila3ro),2);
				$Haber3ro    = number_format(ObtenerResultado($excTerceros,4,$Fila3ro),2);
				$IdObjeto3ro = ObtenerResultado($excTerceros,5,$Fila3ro);
				$Sal_ant3ro = ObtenerResultado($excTerceros,6,$Fila3ro);
				$combinado3ro = ObtenerResultado($excTerceros,7,$Fila3ro);
				$SaldoActual3ro = bcadd($Sal_ant3ro,ObtenerResultado($excTerceros,3,$Fila3ro));
				$SaldoActual3ro = bcsub($SaldoActual3ro,ObtenerResultado($excTerceros,4,$Fila3ro));
				$arra = explode("-",$combinado3ro);
				$IdObjeto3ro = $arra[1];
								
				if($Campos3ro[1] == "concat(nombre,,apellido)"){
					$Campos3ro[1]  = "concat_ws(' ',nombre,apellido)";
				}
				
				$Tabla3ro = "sipre_automotriz.".$Tabla3ro;
				$SqlStr="select ". $Campos3ro[1] ." from $Tabla3ro where ".$Campos3ro[0] ." = $IdObjeto3ro";
				$execNomTerceros = EjecutarExec($con,$SqlStr) or die($SqlStr);
									  //  echo $SqlStr."<br>";
				if (NumeroFilas($execNomTerceros)>0){
					$rowNomTerceros = ObtenerFetch($execNomTerceros);
					$NomTerceros = $rowNomTerceros[0];//ObtenerResultado($execNomTerceros,1,0);
				}
				
				$pdf->SetX(1); 
				$pdf->SetTextColor(0,0,204);
				$campos = array ("",trim($NomTerceros),parentesis($Sal_ant3ro),$Debe3ro,$Haber3ro,parentesis($SaldoActual3ro));
						//		$campos = array ("",trim($combinado3ro),parentesis($Sal_ant3ro),$Debe3ro,$Haber3ro,parentesis($SaldoActual3ro));
				$Alinear = array('L','L','R','R','R','R');
				$Ancho = array ('15','70','30','30','30','30');
				$TamañoLetra=array ('6','7','7','7','7','7');
				$MaxLon=array (0,25,0,0,0,0);
				$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,'',$Alinear,$MaxLon);	   
				$pdf->SetTextColor(0,0,0);
			}
		
			$SqlStr="select descripcion,'',
							debe as debe,haber as haber,0,0,combinado,comprobant,fecha,documento  
							from movimiendif
							where codigo = '$codigo' 
							and fecha between '$cDesde1' and '$cHasta1' 
							and (debe <> 0 or haber <> 0) and combinado is null";
			$excTerceros = EjecutarExec($con,$SqlStr) or die($SqlStr);
			$Fila3ro= -1; 
			
			while ($rowTerceros = ObtenerFetch($excTerceros)) {
				$Fila3ro++;
				$descripcion = ObtenerResultado($excTerceros,1,$Fila3ro);
				$Debe3ro     = number_format(ObtenerResultado($excTerceros,3,$Fila3ro),2);
				$Haber3ro    = number_format(ObtenerResultado($excTerceros,4,$Fila3ro),2);
				$IdObjeto3ro = ObtenerResultado($excTerceros,5,$Fila3ro);
				$Sal_ant3ro = ObtenerResultado($excTerceros,6,$Fila3ro);
				$combinado3ro = ObtenerResultado($excTerceros,7,$Fila3ro);
				$comprobant = ObtenerResultado($excTerceros,8,$Fila3ro);
				$fecha = ObtenerResultado($excTerceros,9,$Fila3ro);
				$documento= ObtenerResultado($excTerceros,10,$Fila3ro);
								
				$mostrar = $comprobant ."|".$fecha;
				$NomTerceros = substr($descripcion,0,35);
							
				$pdf->SetX(1); 
				$pdf->SetTextColor(255,0,0);
				$campos = array ($mostrar,trim($NomTerceros),$documento,$Debe3ro,$Haber3ro,parentesis($SaldoActual3ro));
						//		$campos = array ("",trim($combinado3ro),parentesis($Sal_ant3ro),$Debe3ro,$Haber3ro,parentesis($SaldoActual3ro));
				$Alinear = array('L','L','R','R','R','R');
				$Ancho = array ('15','70','30','30','30','30');
				$TamañoLetra=array ('6','7','7','7','7','7');
				$MaxLon=array (0,25,0,0,0,0);
			
				$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,'',$Alinear,$MaxLon);	   
				$pdf->SetTextColor(0,0,0);
			}														
		}					
					/* FIN VERIFICAR SI LA CUENTA ES DE TERCEROS */ 					
 	}
	
	$campos = array ("","Totales:",number_format($TotalSaldoAnterior,2),number_format($TotalDebe,2),number_format($TotalHaber,2), number_format($TotalSaldoActual,2));
	$Alinear = array('L','R','R','R','R','R');
	$Ancho = array ('21','55','30','30','30','30');
	$TamañoLetra=array ('7','7','7','7','7','7');
	$Bordes =array('','','T','T','T','T');
	$TipoLetra=array ('','B','B','B','B','B');
	
	$pdf->enc_detallePre($campos,$Ancho,3,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);
}	

//auditoria
auditoria('consulta','cuentageneral/movimiendif',$sCampos,'consulta balance de comprobacion terceros, rango: '.$cDesde1." - ".$cHasta1);
//fin auditoria

$pdf->Output();
?>