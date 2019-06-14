<?php
include("FuncionesPHP.php");
  	//**********************************************************************
  	/*Fin C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
		$con = ConectarBD();
				$SqlStr="select comprobant from enviadosacontabilidad 
				where fecha = '$idDia' and cc = '$idcc' and ct = '$idct'";  
				$exc4 = EjecutarExec($con,$SqlStr) or die($SqlStr);
				if (NumeroFilas($exc4)>0){
					$comp=ObtenerResultado($exc4,1);
					   echo "<input type='hidden' id='hdn_adelante' name='hdn_adelante' value=$comp>";
				}else{
	                   echo "<input type='hidden' id='hdn_adelante' name='hdn_adelante' value=''>";
                }				
?>

