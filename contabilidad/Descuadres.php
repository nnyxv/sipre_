<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();

$sCampos= "codigo";
  $sCampos.= ",enero";
  $sCampos.= ",ene_d";
  $sCampos.= ",ene_h";
  
  $sCampos.= ",febrero";
  $sCampos.= ",feb_d";
  $sCampos.= ",feb_h";
  
  $sCampos.= ",marzo";
  $sCampos.= ",mar_d";
  $sCampos.= ",mar_h";
  
  $sCampos.= ",abril";
  $sCampos.= ",abr_d";
  $sCampos.= ",abr_h";
  
  $sCampos.= ",mayo";
  $sCampos.= ",may_d";
  $sCampos.= ",may_h";

  $sCampos.= ",junio";
  $sCampos.= ",jun_d";
  $sCampos.= ",jun_h";

  $sCampos.= ",julio";
  $sCampos.= ",jul_d";
  $sCampos.= ",jul_h";

  $sCampos.= ",agosto";
  $sCampos.= ",ago_d";
  $sCampos.= ",ago_h";

  $sCampos.= ",septiembre";
  $sCampos.= ",sep_d";
  $sCampos.= ",sep_h";

  $sCampos.= ",octubre";
  $sCampos.= ",oct_d";
  $sCampos.= ",oct_h";

  $sCampos.= ",noviembre";
  $sCampos.= ",nov_d";
  $sCampos.= ",nov_h";

  $sCampos.= ",diciembre";
  $sCampos.= ",dic_d";
  $sCampos.= ",dic_h";

  
$SqlStr=" SELECT  $sCampos  FROM  sipre_contabilidad.cnt0000 where fecha_year = 2008";
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
			          while ($row = mysql_fetch_row($exc2)){
					  $oCodigoCuenta=$row[0];
					  $sCondicion1 = "substring(codigo,1,length(rtrim('". $oCodigoCuenta ."'))) ='" . trim($oCodigoCuenta) . "'";
						$sCampos1 = "count(*)";
						$sTabla1 = "sipre_contabilidad.cuenta";

						$sql = "Select " .$sCampos1. " from ". $sTabla1  ." where " .$sCondicion1 . $EstadoCondicion ;
						$rs4 = EjecutarExec($con,$sql); 
						if (NumeroFilas($rs4)>0){
							if (ObtenerResultado($rs4,1)==1){
							 $i = 1; 
								while ($i < 28){
									if(round(($row[$i]+$row[$i+1]-$row[$i+2]),2) != round($row[$i+3],2)){
									 $a = $row[$i];
									 $b = $row[$i+1];
									 $c = $row[$i+2];
											echo "Cuenta = ".$row[0] . "Mes =".$i. " Monto Anterior= $a | $b | $c  ".($row[$i]+$row[$i+1]-$row[$i+2])."Monto Actual=".$row[$i+3]."<br>"; 
								   }
								    $i = $i + 3; 
							   }
	
							}
						}
					  
					  
					  
						  
					   }
					}        
 
 echo "Proceso Finalizado Satisfactoriamente";
	
  
  
  
?>