 <?php include_once('FuncionesPHP.php');
$sTabla = "";
$sCampos= "";
$sPlantillaBus= "";
$sCondicion = str_replace("\\","",$TACondicion);
$ArrRec =array_recibe($TAValores);
$NumEle = count($ArrRec);
for ($i = 0; $i <= $NumEle; $i++){
    if ($ArrRec[$i][1] == 'T') {
       $sTabla = $ArrRec[$i][0];	
	}
	if ($ArrRec[$i][1] == 'C') {
      $sCampos.= $ArrRec[$i][0].',';	
	}
	if ($ArrRec[$i][1] == 'P') {
      $sPlantillaBus= trim($ArrRec[$i][0]);	
	}
}
  
$sCampos = substr($sCampos,0,strlen($sCampos)-1);
$con = ConectarBDAd();  
$sql = "Select " .$sCampos. " from ". $sTabla  ." where " .$sCondicion ;
echo $sql;
$rs3 = EjecutarExecAd($con,$sql); 
if (NumeroFilas($rs3) > 0){
   $sValor = ObtenerResultado($rs3,2);
}else{
     $sValor = "";	
}	
$primeraOb=true;
	  for ($i = 0; $i <= $NumEle - 1; $i++){
			if ($ArrRec[$i][1] == 'O') {
			if ($primeraOb){
    			   $primeraOb =false;
			}else{
					   $sObjeto = $ArrRec[$i][0];	
						  echo "<script language='Javascript'>     
						  parent.mainFrame.document.$sPlantillaBus.$sObjeto.value = '$sValor';
						   </script>";
              }				   
			   $ArrRec[$i][1] = 'X';
			}
		}

?>
