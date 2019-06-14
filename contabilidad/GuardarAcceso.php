<?php session_start(); 
include_once('FuncionesPHP.php');
$T_Codigo= $_REQUEST['T_Codigo'];
$con = ConectarBDAd();  
//los numeros del menu no pueden pasar mas de 99 por que se va a tomar solo los dos primeros numeros
$sql = "delete from mapaacceso where CodigoMapa='".  $T_Codigo ."'"; 
$exc = EjecutarExecAd($con,$sql) or die($sql);  
$sCampos= "tipo,titulo,NOP,Numero";
$sql = "Select " .$sCampos. " from menus  order by Orden ";
$exc = EjecutarExecAd($con,$sql) or die($sql);  
if (NumeroFilas($exc) > 0){
	$iFila=-1;
	while($row = ObtenerFetch($exc)) {
       	$iFila++;
		$Tipo = ObtenerResultado($exc,1,$iFila);
        $NumeroCorre = ObtenerResultado($exc,4,$iFila);
				    
        if ($NumeroCorre <= 9){
    		$NumeroT = "E0". $NumeroCorre ."_" ;
    	}else{
    		$NumeroT = "E".trim($NumeroCorre) ."_";
    	}  	
	
	   	if ($Tipo == "P"){
    		$sValores=$NumeroCorre.",";
			$sValores.="'". $ConcaCadena."',";
    		$sValores.="'". $T_Codigo ."'";
    		$sCampos= "NroOpcion,Habilitar,CodigoMapa";
			$sql = "insert into mapaacceso(" .$sCampos. ") values (". $sValores .")" ;   
    	    $exc1 = EjecutarExecAd($con,$sql) or die($sql);  
		}else{
			$CboAcceso = $NumeroT."A";
			$CboIncluir = $NumeroT."I";
			$CboModificar = $NumeroT."M";
			$CboEliminar = $NumeroT."E";
			$CboConsultar = $NumeroT."C";
			$ConcaCadena =""; 
							
			if ($_POST[$CboAcceso] == "SI"){
				
				if($_POST[$CboIncluir] == "SI"){
				   $ConcaCadena.="1";		
				}else{
				   $ConcaCadena.="0";		
			}	
		
			if($_POST[$CboModificar] == "SI"){
				$ConcaCadena.="1";		
			}else{
				$ConcaCadena.="0";		
			}	
								
			if($_POST[$CboEliminar] == "SI"){
			   $ConcaCadena.="1";		
			}else{
			   $ConcaCadena.="0";		
			}	         				         				
								
			if($_POST[$CboConsultar] == "SI"){
			   $ConcaCadena.="1";		
			}else{
			   $ConcaCadena.="0";		
			}	 
			
			$sValores=$NumeroCorre.",";
		    $sValores.="'". $ConcaCadena."',";
        	$sValores.="'". $T_Codigo ."'";
         	$sCampos= "NroOpcion,Habilitar,CodigoMapa";
		    
			$sql = "insert into mapaacceso(" .$sCampos. ") values (". $sValores .")" ;   
        	$exc1 = EjecutarExecAd($con,$sql) or die($sql);  
		}	
							
	}	
        				
     
         			
	}//while($row = odbc_fetch_row($exc)) 
}//if (odbc_num_rows($exc) > 0){
header("Location: frmVerAccesos.php?T_Codigo=".trim($T_Codigo));
?>

<form>
<input type="hidden" name="T_Codigo" value="<?php print($T_Codigo); ?>">
</form>