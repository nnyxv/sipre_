<?php
require('fpdf.php');
include("FuncionesPHP.php");

$con = ConectarBD();
$_SESSION["UsuarioSistema"] = '001';
$_SESSION["sBasedeDatos"] = "sipre_contabilidad";
$sUsuario = $_SESSION["UsuarioSistema"];
$SqlStr=" delete from cuentageneral where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$cDesde1 = "01/07/2006";
$cHasta1 = "30/09/2006"; 
$icierre = "1"; 

MovimientosH($cDesde1,$cHasta1);

$cDesde1 = substr($cDesde1,6,4)."/".substr($cDesde1,3,2)."/". substr($cDesde1,0,2);
$cHasta1 = substr($cHasta1,6,4)."/".substr($cHasta1,3,2)."/". substr($cHasta1,0,2);

$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
,Deshabilitar,FechaDes,Comentario,usuario)  
select codigo,cod_sus,ult_mov,descripcion,0,0,0,0,0,publicacion 
,Deshabilitar,FechaDes,Comentario,'$sUsuario' from cuenta where DesHabilitar = 0";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

/* Buscar Saldos Anteriores de las cuentas Seleccionadas*/
       $SqlStr="select  fec_proceso from parametros";
       $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
       if ( NumeroFilas($exc)>0){
           $dfec_proceso = trim(ObtenerResultado($exc,1)) ; 
       }
		$AnoMesProceso = obFecha($dfec_proceso,'A').obFecha($dfec_proceso,'M');
		$AnoMesDesde = obFecha($cDesde1,'A').obFecha($cDesde1,'M');
		$AnoMesHasta = obFecha($cHasta1,'A').obFecha($cHasta1,'M');
	if($AnoMesDesde == $AnoMesProceso){	
       $SqlStr=" update cuentageneral a,cuenta b set a.saldo_ant = b.saldo_ant
	    where a.codigo = b.codigo";	
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

	}elseif($AnoMesDesde > $AnoMesProceso){
         $SqlStr=" update cuentageneral a,cuenta b set a.saldo_ant = b.saldo_ant
  	     where a.codigo = b.codigo";	
         $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		 
         $SqlStr=" call SaldoAnteriorPosteriores('$cDesde1')";
           $exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());
	}elseif ($AnoMesDesde < $AnoMesProceso){
         $Mesword = MesLetras(strval(obFecha($cDesde1,'M')));	 
		 $Fecha_ano =  obFecha($cDesde1,'A');     
		if (strval(obFecha($cDesde1,'M')) = 6 or strval(obFecha($cDesde1,'M')) = 12){
		
		}else{
   	     $SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword
  	     where a.codigo = b.codigo and fecha_year = $Fecha_ano";	
		} 
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
    }
	/* Fin Buscar Saldos Anteriores de las cuentas Seleccionadas*/
         $SqlStr=" call SumaMovimientos('$sUsuario') ";
         $exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());
return;

$AnoMesDesde = obFecha($cDesde1,'A') + obFecha($cDesde1,'M');
$AnoMesProceso =  obFecha($dProceso,'A') + obFecha($dProceso,'M');
$dDesde = obFecha($cDesde1,'A');
$Mes = obFecha($cDesde1,'M');

   IF ($AnoMesDesde == $AnoMesProceso){
       $SqlStr="select  fec_proceso from parametros";
       $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
       $SqlStr="    select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe as debe, a.haber
					from cuenta a where Deshabilitar = 'NO'";
    }else{
     	$sMesSaldo = MesLetras(strval($Mes));
        $sMesDebe  = substr($sMesSaldo,0,3). '_d';
        $sMesHaber = substr($sMesSaldo,0,3). '_h';
        $sMesDebecierr  = substr($sMesSaldo,0,3). '_cierrd';
        $sMesHabercierr = substr($sMesSaldo,0,3). '_cierrh';

       $SqlStr=" SELECT codigo,descripcion,$sMesSaldo,$sMesDebe,$sMesHaber
				from cnt0000 where fecha_year = $dDesde 
   				group by codigo";
}

class PDF extends FPDF
{
//Cabecera de página
function Header()
{
              //Nombre de la Empresa 
        $this->SetFont('Arial','B',12);
        $this->SetXY(1, 10); 
        $Empresa = $_SESSION["sDesBasedeDatos"];
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
		$Titulo='Balance de Comprobación';
		$this->Cell(0,5,$Titulo,0,0,'C');	             
		$this->SetXY(2,30);	
   		//$this->Ln(5);	             

        $campos = array ('Codigo','Nombre de Cuenta','Saldo Anterior','Debe','Haber','Saldo Actual');
		$Alinear = array('L','L','R','R','R','R');
		$Bordes =array('B','B','B','B','B','B');
		$Ancho = array ('30','55','30','30','30','30');
        $TamañoLetra=array ('8','8','8','8','8','8');
		$TipoLetra=array ('B','B','B','B','B','B');
        $this->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);


}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();


        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
       if ( NumeroFilas($exc)>0){
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
				$SaldoActual = number_format(strval(trim(ObtenerResultado($exc,3,$iFila))) + strval(trim(ObtenerResultado($exc,4,$iFila))) - trim(ObtenerResultado($exc,5,$iFila)),2);
					$campos = array ($codigo,$descripcion,$Sal_ant,$Debe,$Haber,$SaldoActual);
					$Alinear = array('L','L','R','R','R','R');
					$Ancho = array ('30','55','30','30','30','30');
					$TamañoLetra=array ('7','7','7','7','7','7');
					$pdf->enc_detallePre($campos,$Ancho,5,$TamañoLetra,'',$Alinear);
            }
		}	
$pdf->Output();
?>