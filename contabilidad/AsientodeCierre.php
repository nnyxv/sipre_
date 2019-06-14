<?php session_start();
include("FuncionesPHP.php");
$con = ConectarBD();
$SqlStr = "update parametros set MensajeRet = ''";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 

$SqlStr = " select 
 fec_proceso
,gancia 
,ctaingresos
,ctaegresos
,CierreA
,comp_cierr
,fechacomp_cierr
,mescierre
,ctaingresos2
,ctaegresos2
,ctaingresos3
,ctaegresos3
from parametros";
$exc =  EjecutarExec($con,$SqlStr) or die($SqlStr); 

if (NumeroFilas($exc)>0){
			$dProceso    = trim(ObtenerResultado($exc,1));
			$CtaGancias  = trim(ObtenerResultado($exc,2));
			$CtaIngresos = trim(ObtenerResultado($exc,3));
			$CtaEgresos  = trim(ObtenerResultado($exc,4));
			$TipoCierre  = ObtenerResultado($exc,5);
			$Comp_cierr  = ObtenerResultado($exc,6);
			$FechaComp_cierr  = ObtenerResultado($exc,7);
			$mescierre  = ObtenerResultado($exc,8);
			$CtaIngresos2 = ObtenerResultado($exc,9);
			$CtaEgresos2 = ObtenerResultado($exc,10);
			$CtaIngresos3 = ObtenerResultado($exc,11);
			$CtaEgresos3 = ObtenerResultado($exc,12);
}

$Ano = obFecha($dProceso,"A");
$mes = obFecha($dProceso,"M");
$Ultimodia = ultimo_dia(intval($mes),intval($Ano));

$FechaDocumento = $Ano."-".$mes."-".$Ultimodia;
$mes_cierre = substr($FechaComp_cierr,5,2);
$mes_proc = substr($dProceso,5,2);

if($mes_proc != $mes_cierre){
	MJ('No se puede generar el Asiento de Cierre ya que hay meses previos sin cerrar y/o no es fecha de Cierre Fiscal');
	return;
}


$SqlStr = " select  Descripcion from cuenta where codigo = '$CtaGancias'";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
  $Descripcion = trim(ObtenerResultado($exec,1));
}

if ($TipoCierre == 1){
   if (obFecha($dProceso,"M") != $mescierre)
     {
       MJ('No se puede Generar el Comprobante ya que no es Fecha de Cierre');
	   return;
     }
}

/*if ($TipoCierre == 0){
  if (obFecha($dProceso,"M") != 6 && obFecha($dProceso,"M") != 12){
              MJ('No se puede Generar el Comprobante ya que no es Fecha de Cierre');
     		  return;
     }
}*/



if (strlen(trim($CtaGancias)) == 0){
              MJ('La cuenta de ganancias o perdida de la tabla de parametros debe ser llenada');
			  return;
}

if (strlen(trim($CtaIngresos)) == 0){
     MJ('La cuenta de Ingresos de la tabla de parametros debe ser llenada');
     return;
}

if (strlen(trim($CtaEgresos)) == 0){
		MJ('La cuenta de Egresos de la tabla de parametros debe ser llenada');
     return;
    }


$nContador = 0;
$SqlStr = " select  Count(*) from enc_diario a  where a.Actualiza = 0";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
	$nContador = trim(ObtenerResultado($exec,1));
}
  if ($nContador != 0){
          MJ('Existen comprobantes que no han sido actualizados');
		  return;
  }
$nContador = 0;
$SqlStr = " select  Count(*) from cuenta a  where a.codigo = '$CtaGancias'";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
         $nContador = trim(ObtenerResultado($exec,1));
}
if ($nContador == 0){ 
  MJ('La cuenta de ganancias o perdida de la tabla de parametros no concuerda con ninguna cuenta de movimiento del Catalogo de cuentas');
  return;
}

$nContador = 0;
$SqlStr = " select  Count(*) from cuenta a  where substr(trim(codigo),1,length(trim('$CtaGancias'))) = '$CtaGancias'";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
         $nContador = trim(ObtenerResultado($exec,1));
}
  if ($nContador > 1){
     MJ('La cuenta de ganancias o perdida de la tabla de parametros es una cuenta que no permite movimientos ');
	 return;
  }

if (strlen(trim($Comp_cierr)) != 0){
              MJ('Ya se ha generado el asiento de cierre con el Nro '. $Comp_cierr .' y de Fecha '. obFecha($FechaComp_cierr));
			  return;
}


$lenIn = strlen(trim($CtaIngresos));
$lenEg = strlen(trim($CtaEgresos));
$lenIn2 = strlen(trim($CtaIngresos2));
$lenEg2 = strlen(trim($CtaEgresos2));
$lenIn3 = strlen(trim($CtaIngresos3));
$lenEg3 = strlen(trim($CtaEgresos3));




$GananciaPerdida = 0;
$SqlStr = " delete  from movimientemp";
$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 	
$SqlStr = " select codigo,descripcion
	from cuenta	where length(TRIM(codigo)) >= 5";


$SqlStr.=" and (";
		
	if (trim($CtaIngresos) != ""){
		$SqlStr.=" 	substring(codigo,1,$lenIn) = '$CtaIngresos'"; 
	}	
	if (trim($CtaEgresos) != ""){
		$SqlStr.="	or substring(codigo,1,$lenEg) = '$CtaEgresos'";
	}
	
	if (trim($CtaIngresos2) != ""){
		$SqlStr.=" 	or substring(codigo,1,$lenIn2) = '$CtaIngresos2'"; 
	}	
	if (trim($CtaEgresos2) != ""){
		$SqlStr.="	or substring(codigo,1,$lenEg2) = '$CtaEgresos2'";
	}
	
	if (trim($CtaIngresos3) != ""){
		$SqlStr.=" 	or substring(codigo,1,$lenIn3) = '$CtaIngresos3'"; 
	}	
	if (trim($CtaEgresos3) != ""){
		$SqlStr.="	or substring(codigo,1,$lenEg3) = '$CtaEgresos3'";
	}
	
$SqlStr.=") order by codigo";

  //  and (SUBSTRING(codigo,1,length('$CtaIngresos')) = '$CtaIngresos' or SUBSTRING(codigo,1,length('$CtaEgresos')) = '$CtaEgresos')
  //	order by codigo";
    $exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
	 if (NumeroFilas($exec)>0){
         	$iNumero=0;  
     		$iFila = -1;
     		$iAsi = 0;
			$subAnt = ""; 
            while ($row = ObtenerFetch($exec)) {
            	$iFila++;
    			$codigo = trim(ObtenerResultado($exec,1,$iFila)) ; 
                $descrip = 'ASIENTO DE CIERRE';//trim(ObtenerResultado($exec,2,$iFila));				
        			$SqlStr = "select count(*) from cuenta where substring(trim(codigo),1,length(trim('$codigo'))) = '$codigo'";
				    $exec1 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
					$ContAux = 0;
					 if ( NumeroFilas($exec1)>0){
  					     $ContAux  = trim(ObtenerResultado($exec1,1));
					 }
					 if($ContAux == 1){
        					$SaldoMovi = 0;
                 			$SqlStr = "select round(a.saldo_ant,2) +round(a.debe,2)-round(a.haber,2) from cuenta a where codigo = '$codigo'" ;
           				    $exec2 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 											
							if ( NumeroFilas($exec2)>0){
							    $SaldoMovi = ObtenerResultado($exec2,1);
							}
							if ($SaldoMovi != 0){
					/* 		if ($subAnt != substr($codigo,0,1)){
                                  $GananciaPerdida = $SaldoMovi;							
								  $subAnt = substr($codigo,0,1);
							}else{ */
							     $GananciaPerdida = bcadd($GananciaPerdida , $SaldoMovi, 2);							
                            							
                                  $SaldoMovi = bcmul($SaldoMovi,-1,2);
						$iAsi++;	
						}
				
				                   $iNumero++;  
								   if($SaldoMovi > 0){
									 $SqlStr = " insert into movimientemp
									 (codigo
									 ,descripcion  
						             ,debe             
					                 ,haber            
 						             ,numero          
						             ,DT             
  						             ,CT
									 ,documento
									 ,fecha
									 ,im)             
									 values('$codigo','$descrip',$SaldoMovi,0,$iNumero,'00','00','$FechaDocumento','$FechaDocumento','ASCIERRE')";
								     $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 	
									}else{
									$SaldoHaber = bcmul($SaldoMovi,-1,2);
									 $SqlStr = " insert into movimientemp
									 (codigo
									 ,descripcion  
						             ,debe             
					                 ,haber            
 						             ,numero          
						             ,DT             
  						             ,CT
									 ,documento
									 ,fecha
									 ,im)             
									 values('$codigo','$descrip',0,$SaldoHaber,$iNumero,'00','00','$FechaDocumento','$FechaDocumento','ASCIERRE')";
									 $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 	
									}
	
					 }
			}
	 }			
                   $iNumero++;  
				   if ($GananciaPerdida > 0){
	  			     			 $SqlStr = " insert into movimientemp
		 									 (codigo
											 ,descripcion  
								             ,debe             
					    		             ,haber            
 						        		     ,numero          
								             ,DT             
  								             ,CT
											 ,documento
											 ,fecha
											 ,im)
											 values('$CtaGancias','$Descripcion',$GananciaPerdida,0,$iNumero,'00','00','$FechaDocumento','$FechaDocumento','ASCIERRE')";
										     $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 			 
					}else{
					
                     					$GananHaber = bcmul($GananciaPerdida,-1,2); 
			         			 $SqlStr = " insert into movimientemp
		 									 (codigo
											 ,descripcion  
								             ,debe             
					    		             ,haber            
 						        		     ,numero          
								             ,DT             
  								             ,CT
											 ,documento
											 ,fecha
											 ,im)               
										     values ('$CtaGancias','$Descripcion',0,$GananHaber,$iNumero,'00','00','$FechaDocumento','$FechaDocumento','ASCIERRE')";
										     $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 		
					}

	header("Location:GenerarAsientoTemporal.php");


?>
 
   