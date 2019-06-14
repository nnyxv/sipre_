<?php session_start();
include_once('FuncionesPHP.php');
$_SESSION["UsuarioSistema"] = "001";
$_SESSION["sServidor"]= "localhost";
$con= conectarBDAd();
$sql = "Select * from company where substr(codigo,1,1) = 'E' or codigo = 'sipre_contabilidad'";
//$sql = "Select * from company where codigo = 'inicial_inavi'";
$rs3 = EjecutarExecAD($con,$sql); 

while($Filas = mysql_fetch_row($rs3)){      
    $_SESSION["sBasedeDatos"] = $Filas[0];
	$con= conectarBD();
    $sql = "Select comprobant,fecha,cc,concepto from enc_dif where concepto like '%\"%'";
	//$sql = "Select comprobant,fecha,cc,concepto from enc_dif";
	$rs1 = EjecutarExec($con,$sql); 
	while($Filas = mysql_fetch_row($rs1)){      
		$comprobant = $Filas[0];
		$fecha = $Filas[1];
		$cc = $Filas[2];
		$concepto = str_replace("'","",str_replace('"','',$Filas[3]));
	$sql = "Update enc_dif set concepto = '". $concepto 
	."' where comprobant = '".$comprobant ."' and fecha = '". $fecha ."' and cc='". $cc ."'";  
	echo $sql ." <br> ";
	$rs2 = EjecutarExec($con,$sql); 
	}
}

