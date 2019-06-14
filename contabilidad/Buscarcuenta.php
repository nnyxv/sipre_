<?php session_start();
include("FuncionesPHP.php");
$con = ConectarBD();  
$sValor =$_REQUEST["sValor"];

if($_SESSION["CCSistema"] != ""){
  	$EstadoIM =  "sipre_contabilidad.cuenta";
}else{
  	$EstadoIM =  "sipre_contabilidad.cuenta";
}
$sCondicion = "codigo='".$sValor."'";
$sCampos = "codigo";

$sql = "Select " .$sCampos. " from ". $EstadoIM  ." where " .$sCondicion ;
$rs = EjecutarExec($con,$sql); 
if (NumeroFilas($rs) > 0){
    echo  ObtenerResultado($rs,1);
}else{
    echo "";
}	
?>