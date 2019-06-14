<?php  session_start();
include_once('FuncionesPHP.php');
//$_SESSION["sBasedeDatos"]= 'dbcontabilidad';
//$iActRev int, Actualizar o reversar si es 0 es actualizacion si es 1 es reverso
//$cDesde  Fecha desde en string
//$cHasta  Fecha hasta en string
//$cComprobantD Comprobante Desde
//$cComprobantH Comprobante Hasta

/*$iActRev = 0;
$cDesde  = '2012-10-01';
$cHasta  = '2012-10-31';*/
/*$cComprobantD = '1';
$cComprobantH ='1';*/


//  nro tranferencia :0025517711550

$dDesde = $cDesde;
$dHasta = $cHasta;

if (strlen(trim($dDesde)) != 0){
	if (!checkdate($xMFecha,$xDFecha,$xAFecha)){
	   MJ('Error en la Fecha Desde');
	   return;
	}
}
if (strlen(trim($dHasta)) != 0){
	if (!checkdate($xMFecha2,$xDFecha2,$xAFecha2)){
	   MJ('Error en la Fecha Hasta');
	   return;
	}
}
$con =  ConectarBD();
   $nContador=0; 
  $SqlStr="     SELECT  COUNT(*) 
	       FROM enc_diario a
	       where a.tipo = 'P'";        
   $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
   if ( NumeroFilas($exc2)>0){
		   $nContador = trim(ObtenerResultado($exc2,1));
		}
	if($nContador !=0){
		MJ('Existe comprobante de precierre en diarios, para poder actualizar debe eliminar el comprobante ');
	   return;
    }	
		
if (strlen(trim($cComprobantD != 0))){
 $cComprobantH = $cComprobantD;
}  

/* para verificar si es comporbante de cierre */

   $SqlStr='Select comp_cierr,Fechacomp_cierr,mescierre from parametros ';
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if (NumeroFilas($exc)>0){
		   $comp_cierr = trim(ObtenerResultado($exc,1));
		   $Fechacom_cierr= trim(ObtenerResultado($exc,2));
		   $mescierre= trim(ObtenerResultado($exc,3));
		}
/* fin para verificar si es comporbante de cierre */
$SqlStr= " delete from  temmovimientosfiltrados ";
$exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 


/* para reverzar e3l mes completo */
if ($cComprobantD == 'TODOS'){
   $SqlStr='update cuenta set debe = 0';
   $SqlStr.=',haber=0';
   $SqlStr.=',debe_cierr=0';
   $SqlStr.=',haber_cierr=0';
   $exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);
   
   
/* para los terceros */   
   $SqlStr='update cuentaterceros set debe = 0';
   $SqlStr.=',haber=0';
   $SqlStr.=',debe_cierr=0';
   $SqlStr.=',haber_cierr=0';
   $exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* Fin para los terceros */      
   
   
   $SqlStr=	'update enc_diario set actualiza = 0	';
   $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
   echo 'proceso de reverso realizado satisfactoriamente'; 
   return;
}
/* fin para reverzar e3l mes completo */

$nContador  = 0;
$ComDesc = '';
if (strlen(trim($cComprobantD)) == 0){
   $SqlStr=" SELECT  b.comprobant,a.fecha  FROM enc_diario a, movimien b
	      WHERE a.comprobant = b.comprobant   
		   and a.fecha = b.fecha
	       and a.actualiza = 0
	       and a.fecha = b.fecha
	       and a.fecha between '$dDesde' and '$dHasta'
                    GROUP BY b.comprobant,a.fecha
                    HAVING round(sum(b.debe),2) <> round(sum(b.haber),2)";
   $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
   if ( NumeroFilas($exc2)>0){
		   $ComDesc = trim(ObtenerResultado($exc2,1));
		   $FechaDesc = trim(ObtenerResultado($exc2,2));
		}

   $SqlStr="     SELECT  COUNT(*) 
	       FROM enc_diario a,movimien b
	       where a.comprobant = b.comprobant        
	       and a.actualiza = '$iActRev'
	       and a.fecha = b.fecha";
		   
	if (strlen(trim($dDesde)) != 0){
     	$SqlStr.=" and a.fecha between '$dDesde' and '$dHasta'";
	}
	
	$SqlStr.="  GROUP BY b.codigo
	       ORDER BY b.codigo";
		 
   $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
   if ( NumeroFilas($exc2)>0){
		   $nContador = trim(ObtenerResultado($exc2,1));
		}
} else { //if (strlen((trim($cComprobantD)) == 0){  
   $SqlStr=" SELECT   MAX(b.comprobant),a.fecha  FROM enc_diario a, movimien b
	       where a.comprobant = b.comprobant  
	       and a.actualiza = $iActRev
	       and a.fecha = b.fecha
		   and a.fecha = '$dDesde'
           and a.comprobant between  '$cComprobantD' and '$cComprobantH'
                    GROUP BY b.comprobant,a.fecha
                    HAVING round(sum(b.debe),2) <> round(sum(b.haber),2)";
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
					   $ComDesc = trim(ObtenerResultado($exc2,1));
					   $FechaDesc= trim(ObtenerResultado($exc2,2));
					}         

					
    $SqlStr="  SELECT  COUNT(*) 
	       FROM enc_diario a,movimien b
	       where a.comprobant = b.comprobant        
	       and a.actualiza = $iActRev
	       and a.fecha = b.fecha
		   and a.fecha = '$dDesde'
           and a.comprobant between  '$cComprobantD' and '$cComprobantH'
	       GROUP BY b.codigo
	       ORDER BY b.codigo";
		    $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
					   $nContador = trim(ObtenerResultado($exc2,1));
			   }         
}

     if ($iActRev  == 0){ 
          if  (strlen(trim($ComDesc)) != 0)
                {
				  MJ('El Comprobante Nro '. $ComDesc .' de fecha ' .obFecha($FechaDesc) .' de Diario esta descuadrado');
                  return;
                }
     }


          if ($nContador == 0)
            {
              if ($iActRev == 0)
                 {
						MJ('No existen registros para ser Actualizados');
                  return;          
                 }else{ 
						MJ('No existen registros para ser Reversados');
                      return;          
                }
            }
            
/*aqui tengo que filtrar los parametros*/
if (strlen(trim($cComprobantD)) == 0){  
$SqlStr= "	INSERT temmovimientosfiltrados
	       SELECT a.comprobant,b.codigo,sum(b.debe) AS debe,sum(b.haber) as haber,max(b.fecha)
	       FROM enc_diario a,movimien b
	       where a.comprobant = b.comprobant   
	       and a.actualiza = $iActRev
	       and a.fecha = b.fecha
		   and a.cc= b.cc
	       and a.fecha between '$dDesde' and '$dHasta'
	       GROUP BY b.codigo,a.comprobant
	       ORDER BY b.codigo,a.comprobant";
  $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
}else{
$SqlStr= "	INSERT temmovimientosfiltrados
	       SELECT a.comprobant,b.codigo,sum(b.debe) AS debe,sum(b.haber) as haber,max(b.fecha)
	       FROM enc_diario a,movimien b
	       where a.comprobant = b.comprobant    
	       and a.actualiza = $iActRev
	       and a.fecha = b.fecha
		   and a.cc= b.cc
		   and a.fecha = '$dDesde'
           and a.comprobant between '$cComprobantD' and '$cComprobantH'
	       GROUP BY b.codigo,a.comprobant
	       ORDER BY b.codigo,a.comprobant";
  $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
}    
/*fin aqui tengo que filtrar los parametros*/

// ojojojojojojojojojoj begin transaction
$exc5 = EjecutarExec($con,"BEGIN");
if ($Option != 5){
$SqlStr= " select  
Comprobant 
,CodigoMov
,sum(debe) as debe
,sum(haber) as haber
,fecha  
from temmovimientosfiltrados group by Comprobant,CodigoMov ";
$rs3 = EjecutarExec($con,$SqlStr); 
$sincredetalle = 0;
$NumeroAnt=0;
$iFila = -1;
while ($row = ObtenerFetch($rs3)) {
	$iFila++;
	    $CompMov = trim(ObtenerResultado($rs3,1,$iFila));
	    $CodigoMov = trim(ObtenerResultado($rs3,2,$iFila));
        $DebeMov = trim(ObtenerResultado($rs3,3,$iFila));
        $HaberMov = trim(ObtenerResultado($rs3,4,$iFila));
        $FechaMov = trim(ObtenerResultado($rs3,5,$iFila));
	
       /* si es actualizacion o reverso*/
       if ($iActRev == 1){
             $DebeMov  = strval($DebeMov) * -1;
            $HaberMov = strval($HaberMov) * -1;
       }  
       /* fin si es actualizacion o reverso*/
       
       if ($comp_cierr == $CompMov && $CompMov == 999){ 
	   /*para actualizar las cuentas del comprobante de cierre*/
	  $SqlStr= "     update cuenta set debe_cierr = debe_cierr + $DebeMov,
	                         haber_cierr = haber_cierr + $HaberMov,
                                       ult_mov = '$FechaMov'
	       where (length(ltrim(rtrim(cuenta.codigo))) < length(ltrim(rtrim('$CodigoMov')))
	       and ltrim(rtrim(cuenta.codigo)) = substring('$CodigoMov',1,length(ltrim(rtrim(cuenta.codigo)))))
	       or cuenta.codigo = '$CodigoMov'";
	        $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
	   /*fin para actualizar las cuentas del comprobante de cierre */ 
       }else{
	   /*para actualizar las cuentas */
	  $SqlStr= "   update cuenta set debe = debe + $DebeMov,
	                         haber = haber + $HaberMov,
                                 ult_mov = '$FechaMov'
	       where (length(ltrim(rtrim(cuenta.codigo))) < length(ltrim(rtrim('$CodigoMov')))
	       and ltrim(rtrim(cuenta.codigo)) = substring('$CodigoMov',1,length(ltrim(rtrim(cuenta.codigo)))))
	       or cuenta.codigo = '$CodigoMov'"; 
	   /*fin para actulaizar las cuentas */ 
	       $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
       }
}  
/*para los terceros*/
$SqlStr= " SELECT ''as comprobant,b.cuenta,sum(b.debe) AS debe,sum(b.haber) as haber,max(b.fecha)
	       ,idobjeto,tabla FROM enlaceterceros b
	       where b.fecha between '$dDesde' and '$dHasta'
	       GROUP BY b.cuenta
	       ORDER BY b.cuenta";
		   $excTerceros = EjecutarExec($con,$SqlStr) or die($SqlStr);
		   $iFila = -1;
				while ($row = ObtenerFetch($excTerceros)) {
				   $iFila++;
					$CompMov = trim(ObtenerResultado($excTerceros,1,$iFila));
					$CodigoMov = trim(ObtenerResultado($excTerceros,2,$iFila));
					$DebeMov = trim(ObtenerResultado($excTerceros,3,$iFila));
					$HaberMov = trim(ObtenerResultado($excTerceros,4,$iFila));
					$FechaMov = trim(ObtenerResultado($excTerceros,5,$iFila));
					$idobjetoMov = trim(ObtenerResultado($excTerceros,6,$iFila));
					$tablaMov = trim(ObtenerResultado($excTerceros,7,$iFila));
					
					/* si es actualizacion o reverso*/
					if ($iActRev == 1){
						$DebeMov  = strval($DebeMov) * -1;
						$HaberMov = strval($HaberMov) * -1;
					}  
					/* fin si es actualizacion o reverso*/
					/*para actualizar las cuentas */
					$SqlStr= "   update cuentaterceros set debe = debe + $DebeMov,
	                         haber = haber + $HaberMov
					where cuentaterceros.cuenta = '$CodigoMov' 
					and cuentaterceros.idobjeto = rtrim('$idobjetoMov')
					and cuentaterceros.tabla = rtrim('$tablaMov')
					"; 
					/*fin para actulaizar las cuentas */ 
					$exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
		   		} //while ($row = ObtenerFetch($excTerceros)) {
			/*fin para los terceros*/
     
}else{// para reverzar todos los del mes 
   /*para actualizar las cuentas*/
	  $SqlStr= "   update cuenta set debe =0,
	                         haber = 0,
							 debe_cierr =0,
	                         haber_cierr = 0";
	       $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
		   /*Para los terceros*/
		   $SqlStr= "   update cuentaterceros set debe =0,
	                         haber = 0,
							 debe_cierr =0,
	                         haber_cierr = 0";
	       $exec5 = EjecutarExec($con,$SqlStr) or die($SqlStr); 	   
		   /*fin para los terceros*/
		   MJ('proceso DE REVERSO realizado satisfactoriamente');
}


if ($iActRev  == 0){
  $iActualiza = 1;
}else{
  $iActualiza = 0;
}


if (strlen(trim($cComprobantD)) == 0){
if (strlen(trim($dDesde)) != 0){ 
	  $SqlStr= " UPDATE enc_diario set actualiza = $iActualiza
	       where actualiza = '$iActRev'
               and fecha between '$dDesde' and '$dHasta'";
}else{
	  $SqlStr= " UPDATE enc_diario set actualiza = $iActualiza
	       where actualiza = '$iActRev'";
}
	  $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
}else{
      $SqlStr= "  UPDATE enc_diario set actualiza = $iActualiza
	       where actualiza = '$iActRev'
		       and fecha = '$dDesde'
               and comprobant between  '$cComprobantD' and '$cComprobantH'";
      $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
}
$exc5 = EjecutarExec($con,"COMMIT");

MJ('proceso realizado satisfactoriamente');
?>