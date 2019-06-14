<?php session_start();
include("FuncionesPHP.php");
$con = ConectarBD();  
$sValorIM =$_REQUEST["sValorIM"];

if($_SESSION["CCSistema"] != ""){
  $EstadoIM =  "sipre_contabilidad.centrocosto";
}else{
  $EstadoIM =  "centrocosto";
}
$sCondicion = "codigo='".$sValorIM."'";
$sCampos = "codigo";

$sql = "Select " .$sCampos. " from ". $EstadoIM  ." where " .$sCondicion ;
$rs = EjecutarExec($con,$sql); 
if (NumeroFilas($rs) > 0){
    echo  ObtenerResultado($rs,1);
}else{
    echo "";
}	
?>