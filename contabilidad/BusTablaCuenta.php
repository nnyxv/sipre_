 <?php session_start();
include_once('FuncionesPHP.php');
$sTabla = "";
$sCampos= "";
$sPlantillaBus= "";

if ($_SESSION["CCSistema"] != ""){
 //  $EstadoCondicion = " and cc = '". $_SESSION["CCSistema"] ."' order by codigo"; 
    $EstadoCondicion =  " order by codigo"; 
}else{
   $EstadoCondicion = " order by codigo"; 
}

$con = ConectarBD();  
$sCondicion1 = "substring(codigo,1,length(rtrim('". $oCodigoCuenta ."'))) ='" . trim($oCodigoCuenta) . "'";
$sCampos1 = "count(*)";
//if($_SESSION["CCSistema"] != ""){
	$sTabla1 = "sipre_contabilidad.cuenta";
//}else{
  // $sTabla1 = "cuenta";
//}

$sql = "Select " .$sCampos1. " from ". $sTabla1  ." where " .$sCondicion1 . $EstadoCondicion ;
$rs4 = EjecutarExec($con,$sql); 
$ss =NumeroFilas($rs4);
if (NumeroFilas($rs4)>0){
	if (ObtenerResultado($rs4,1)>1){
           echo "<script language='Javascript'>           
						alert('La cuenta $oCodigoCuenta tiene asignado auxiliares'); 
   					    parent.mainFrame.document.frmDiarios.oCodigoCuenta.value = '';
   					    parent.mainFrame.document.frmDiarios.oCodigoCuenta.focus();
		   </script>";	
		   return;
	}
}

 

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
$sql = "Select " .$sCampos. " from ". $sTabla  ." where " .$sCondicion. $EstadoCondicion ;
$rs3 = EjecutarExec($con,$sql); 
$sValor1 = "";
if (NumeroFilas($rs3) > 0){
   $sValor1 = ObtenerResultado($rs3,1);
   $sValor = ObtenerResultado($rs3,2);
}else{
     $sValor = "";	
}	
$primeraOb=true;
	  for ($i = 0; $i <= $NumEle - 1; $i++){
			if ($ArrRec[$i][1] == 'O') {
			if ($primeraOb){
    			   $primeraOb =false;
				 if($sValor1 == ""){  
				 echo "<script language='Javascript'>           
				              alert('La Cuenta No existe');
							  parent.mainFrame.document.$sPlantillaBus.oCodigoCuenta.value = '';
							  parent.mainFrame.document.$sPlantillaBus.oCodigoCuenta.focus();
						   </script>";
				}		   
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
