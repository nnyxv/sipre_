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
,mescierre
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
$anotexto = trim($_REQUEST["txtAno"]);
$mestexto = trim($_REQUEST["txtMes"]);
$anoproceso = date("Ym",strtotime($dProceso));
$fechainicio = "$anotexto-$mestexto-01";
$fechafinal = "01/$mestexto/$anotexto";
$fechafinal = date("Y-m-d",mktime(0,0,0,intval($mestexto)+1,1,intval($anotexto)));
$mestexto2 	 = date("m",strtotime($fechafinal));
$anotexto2 	 = date("Y",strtotime($fechafinal));
$fechafinal = date("Y-m-d",mktime(0,0,0,intval($mestexto2),1-1,intval($anotexto2)));

if($_REQUEST["txtMes"] == 0){
   MJ('Debe Seleccionar un Mes');
   return;
}
if($_REQUEST["txtAno"] == 0){
   MJ('Debe Seleccionar un Año');
   return;
}
 if($mescierre != $_REQUEST["txtMes"]){
   MJ('No se puede generar un comprobante de Pre-Cierre en fecha distinta al cierre de periodo');
   return;
} 

if($_REQUEST["txtMes"] <9){
  $mestexto = "0".trim($_REQUEST["txtMes"]);
}
$anomestexto = $_REQUEST["txtAno"].$mestexto;

if($anomestexto <=$anoproceso){
     MJ('para un comprobante de precierre debe estar en posteriores');
     return;
}


$Ano = obFecha($dProceso,"A");
$mes = obFecha($dProceso,"M");
$Ultimodia = ultimo_dia(intval($mes),intval($Ano));
$FechaDocumento = $Ano."-".$mes."-".$Ultimodia;

$SqlStr = " select  Descripcion from cuenta where codigo = '$CtaGancias'";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
  $Descripcion = trim(ObtenerResultado($exec,1));
}

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
$SqlStr = " select  comprobant,fecha from movimien a 
group by comprobant,fecha 
having sum(debe) <> sum(haber) ";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
	$comprobante = trim(ObtenerResultado($exec,1));
	$fecha = trim(ObtenerResultado($exec,2));
	   $nContador= 1;
       MJ('El comprobante '.$comprobante .' de fecha: '.date('d-m-Y',strtotime($fecha)).'  esta descuadrado en diario');
		return;
}

// movimiento diferidos descuadrados
$nContador = 0;
  $SqlStr = " select  comprobant,fecha from movimiendif a 
  where fecha <= '$fechafinal'
group by comprobant,fecha 
having sum(debe) <> sum(haber) ";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
    $nContador=1;
	$comprobante = trim(ObtenerResultado($exec,1));
	$fecha = trim(ObtenerResultado($exec,2));
}
  if ($nContador != 0){
          MJ('El comprobante '.$comprobante .' de fecha: '.date('d-m-Y',strtotime($fecha)).'  esta descuadrado en diferido');
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

/* if (strlen(trim($Comp_cierr)) != 0){
              MJ('Ya se ha generado el asiento de cierre con el Nro '. $Comp_cierr .' y de Fecha '. obFecha($FechaComp_cierr));
			  return;
} */

CargarSaldos($fechainicio,$fechafinal,'4','99999999999',0);


$lenIn = strlen(trim($CtaIngresos));
$lenEg = strlen(trim($CtaEgresos));
$lenIn2 = strlen(trim($CtaIngresos2));
$lenEg2 = strlen(trim($CtaEgresos2));
$lenIn3 = strlen(trim($CtaIngresos3));
$lenEg3 = strlen(trim($CtaEgresos3));


$sUsuario = $_SESSION["UsuarioSistema"];

$GananciaPerdida = 0;
$SqlStr = " delete  from movimientemp";
$exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 	
$SqlStr = " select codigo,descripcion
	from cuentageneral	where usuario = '$sUsuario' and length(TRIM(codigo)) >= 5";


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
        			$SqlStr = "select count(*) from cuentageneral where usuario = '$sUsuario' and substring(trim(codigo),1,length(trim('$codigo'))) = '$codigo'";
				    $exec1 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
					$ContAux = 0;
					 if ( NumeroFilas($exec1)>0){
  					     $ContAux  = trim(ObtenerResultado($exec1,1));
					 }
					 if($ContAux == 1){
        					$SaldoMovi = 0;
                 			$SqlStr = "select round(a.saldo_ant,2) +round(a.debe,2)-round(a.haber,2) from cuentageneral a where usuario = '$sUsuario' and codigo = '$codigo'" ;
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
									 values('$codigo','$descrip',$SaldoMovi,0,$iNumero,'00','00','$fechafinal','$fechafinal','ASCIERRE')";
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
									 values('$codigo','$descrip',0,$SaldoHaber,$iNumero,'00','00','$fechafinal','$fechafinal','ASCIERRE')";
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
											 values('$CtaGancias','$Descripcion',$GananciaPerdida,0,$iNumero,'00','00','$fechafinal','$fechafinal','ASCIERRE')";
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
										     values ('$CtaGancias','$Descripcion',0,$GananHaber,$iNumero,'00','00','$fechafinal','$fechafinal','ASCIERRE')";
										     $exec3 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 		
					}

	header("Location:GenerarAsientoTemporal.php?parTipo=P");


?>
 
   