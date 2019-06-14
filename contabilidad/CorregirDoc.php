<?php session_start();
include_once('FuncionesPHP.php');
$conAd = ConectarBD();
$SqlStr = "Select a.codigo from sipre_co_config.company a
where  a.codigo <> 'sipre_contabilidad' and a.codigo <> 'BASEPRUEBA'";
$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);

 		 while ($row=ObtenerFetch($exc)){
            $TablaEnc = $row[0];		 
		    $Sql = "select comprobant,cc,fecha,documento,numero from  $TablaEnc.movimien "; 
		    $exc1 = EjecutarExec($conAd,$Sql) or die($Sql);
			   while ($row1=ObtenerFetch($exc1)){
			       $comprobant = $row1[0];
				   $cc =$row1[1];
				   $fecha =$row1[2]; 
				   $documento = $row1[3];
				   $numero = $row1[4];
				   $documento = str_replace('/','',$documento);
			   
				   $Sql = " UPDATE $TablaEnc.movimien SET documento ='".$documento."'  
				   where comprobant = $comprobant and cc = '$cc' and fecha = '$fecha' and numero = $numero" ; 
				   $exc2 = EjecutarExec($conAd,$Sql) or die($Sql);
				}	
				$Sql = " UPDATE $TablaEnc.movimien SET documento = right(documento,6)"; 
				   $exc2 = EjecutarExec($conAd,$Sql) or die($Sql);
				
				$Sql = " UPDATE $TablaEnc.movimien SET im = cc where substr(im,1,1)= '2'"; 
				$exc2 = EjecutarExec($conAd,$Sql) or die($Sql); 
					
				 
			$TablaEnc = $row[0];		 
		    $Sql = "select comprobant,cc,fecha,documento,numero from  $TablaEnc.movimiendif "; 
		    $exc1 = EjecutarExec($conAd,$Sql) or die($Sql);
			   while ($row1=ObtenerFetch($exc1)){
			       $comprobant = $row1[0];
				   $cc =$row1[1];
				   $fecha =$row1[2]; 
				   $documento = $row1[3];
				   $numero = $row1[4];
				   $documento = str_replace('/','',$documento);
				   $Sql = " UPDATE $TablaEnc.movimien SET documento ='".$documento."'  
				   where comprobant = $comprobant and cc = '$cc' and fecha = '$fecha' and numero = $numero" ; 
				   $exc2 = EjecutarExec($conAd,$Sql) or die($Sql);
				}
					$Sql = " UPDATE $TablaEnc.movimiendif SET documento = right(documento,6)"; 
				   $exc2 = EjecutarExec($conAd,$Sql) or die($Sql);
				
				$Sql = " UPDATE $TablaEnc.movimiendif SET im = cc where substr(im,1,1)= '2'"; 
				$exc2 = EjecutarExec($conAd,$Sql) or die($Sql); 
				
				
		}	 


echo "LISTO";
?>