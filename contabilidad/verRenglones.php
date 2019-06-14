<?php session_start();
include("FuncionesPHP.php");
$con = ConectarBD();  

$Arreglo = array_recibe($arrdetalle);
$NumEle = count($Arreglo);

for($i = 0;$i <= $NumEle - 1; $i++){
		$codigo=$Arreglo[$i][1];//codigo;
		$ct = $Arreglo[$i][9]; // CT;
		$dt = $Arreglo[$i][10]; // DT;
		$im = $Arreglo[$i][11]; // IM;
         $cadena="";
		//Buscar codigo de cuenta 
		$sCondicion = "codigo='".trim($codigo)."'";
		$sCampos = "codigo";
		$j = $i + 1;
		$sql = "Select " .$sCampos. " from sipre_contabilidad.cuenta where " .$sCondicion ;
		$rs1 = EjecutarExec($con,$sql) or die($sql) ; 
		if (NumeroFilas($rs1) == 0){
			$cadena=  "el codigo de cuenta $codigo en la  linea $j no existe";
			break;
		}	

		$sCondicion = "codigo='".trim($ct)."'";
		$sCampos = "codigo";
		$j = $i + 1;
		$sql = "Select " .$sCampos. " from sipre_contabilidad.transacciones where " .$sCondicion ;
		$rs1 = EjecutarExec($con,$sql) or die($sql) ; 
		if (NumeroFilas($rs1) == 0){
			$cadena=  "el codigo de transaccion $ct en la  linea $j no existe";
			break;
		}	

		$sCondicion = "codigo='".trim($dt)."'";
		$sCampos = "codigo";
		$j = $i + 1;
		$sql = "Select " .$sCampos. " from sipre_contabilidad.documentos where " .$sCondicion ;
		$rs1 = EjecutarExec($con,$sql) or die($sql) ; 
		if (NumeroFilas($rs1) == 0){
			$cadena=  "el codigo de documento $dt en la  linea $j no existe";
			break;
		}	

		$sCondicion = "codigo='".trim($im)."'";
		$sCampos = "codigo";
		$j = $i + 1;
		$sql = "Select " .$sCampos. " from sipre_contabilidad.centrocosto where " .$sCondicion ;
		$rs1 = EjecutarExec($con,$sql) or die($sql) ; 
		if (NumeroFilas($rs1) == 0){
			$cadena=  "el codigo de imputacion $im en la  linea $j no existe";
			break;
		}	
}
echo $cadena;
?>