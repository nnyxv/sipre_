<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();
for($iMes = 1; $iMes <= 11; $iMes++){
$Mesword = MesLetras($iMes);
$Mes_d = substr($Mesword,0,3)."_d";
$Mes_h = substr($Mesword,0,3)."_h";
echo $Mesword. "<br>";
$MesMov = "sipre_contabilidad.movhistorico".trim($iMes);
$SqlStr = "Select codigo,sum(debe),sum(haber) from $MesMov  group by codigo order by codigo";
	$rs4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
						if (NumeroFilas($rs4)>0){
							while($row = mysql_fetch_array($rs4)){
							
							$codigo  = trim($row[0]);
							$debe = $row[1];
							$haber = $row[2];
							     $sCondicion1 = "codigo = '$codigo' and fecha_year = 2008"; 
								$sql = "Select $Mes_d,$Mes_h from ". "sipre_contabilidad.cnt0000 where " .$sCondicion1 ;
								$rs1 = EjecutarExec($con,$sql) or die($sql); 
								if (NumeroFilas($rs1)>0){
								      $row1 = mysql_fetch_array($rs1);
									      $debecnt = $row1[0];             
										  $habercnt = $row1[1];
										  if($debe != $debecnt || $haber != $habercnt){
										      echo "El codigo $codigo en el mes de $Mesword esta diferente debe =$debe debecnt=$debecnt  haber =$haber habercnt=$habercnt <br>"; 
										  }
								}else{
								              echo "El codigo $codigo en el mes de $Mesword  no existe en cnt0000"; 
								}
								
							}
						}
  

}
echo "proceso realizado satisfactoriamente";





?>