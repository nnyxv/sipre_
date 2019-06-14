<?php  session_start();
include_once('FuncionesPHP.php');
require "formato.inc.php";
$con = ConectarBD();
/*
$Saldo_ant = round(4749527.70,2);
$tipo_conv="bsf";	
$monto_Rec_bf=trim(cvMoneda($Saldo_ant, true, 2, $tipo_conv, false));
echo $monto_Rec_bf;

return;
*/



$SqlStr= "delete from movimientemp3";
$rs3 = EjecutarExec($con,$SqlStr); 
	
$SqlStr= " select  codigo,diciembre + (dic_d + dic_cierrd) - (dic_h + dic_cierrh)
from cnt0000 where fecha_year = 2007  order by  codigo  ";   
$rs3 = EjecutarExec($con,$SqlStr) or die($SqlStr); 

			  
while ($row = ObtenerFetch($rs3)) {
	$iFila++;
        $Codigo = trim(ObtenerResultado($rs3,1,$iFila));
		$Saldo_ant = ObtenerResultado($rs3,2,$iFila);
        $Negativo = "NO";
		
		if ($Saldo_ant < 0 ){
		$Negativo = "SI";
		}
		
		$tipo_conv="bsf";	
		$monto_Rec_bf=cvMoneda($Saldo_ant, true, 2, $tipo_conv, false);
		if ($Negativo == "SI"){
           $monto_Rec_bf = bcmul($monto_Rec_bf,-1,2);
        }		
	
		$sCondicion1 = "substring(codigo,1,length(rtrim('". trim($Codigo )."'))) ='" . trim($Codigo) . "'";

			$SqlStr= " select  COUNT(*)
			from cuenta WHERE  $sCondicion1 ";   
			$rs6 = EjecutarExec($con,$SqlStr); 
		if (ObtenerResultado($rs6,1)==1){
			$SqlStr= " insert into movimientemp3(codigo,Saldo_act)
					      values ('$Codigo',$monto_Rec_bf)";
			        $rs4 = EjecutarExec($con,$SqlStr); 
		}		
}
            $SqlStr = "insert into movimientemp3(codigo,Saldo_act)
					      values ('32501',-5.96)";
			        $rs4 = EjecutarExec($con,$SqlStr); 


$sincredetalle = 0;
$NumeroAnt=0;
$iFila = -1;
	  $SqlStr= "   update cuenta set 
							 Saldo_ant = 0";
$exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
$SqlStr= " select  
Codigo
,sum(saldo_ant) as saldo_ant
,sum(debe) as debe
,sum(haber) as haber
,sum(saldo_act) as saldo_act
from movimientemp3 group by Codigo ";
$rs3 = EjecutarExec($con,$SqlStr); 


while ($row = ObtenerFetch($rs3)) {
	$iFila++;
        $Codigo = trim(ObtenerResultado($rs3,1,$iFila));
		$Saldo_ant = trim(ObtenerResultado($rs3,2,$iFila));
        $Debe = trim(ObtenerResultado($rs3,3,$iFila));
        $Haber = trim(ObtenerResultado($rs3,4,$iFila));
		$Saldo_act = trim(ObtenerResultado($rs3,5,$iFila));


	       $SqlStr= "   update cuenta set Saldo_ant = Saldo_ant + $Saldo_act
	       where (length(rtrim(cuenta.codigo)) < length(rtrim('$Codigo'))
	       and rtrim(cuenta.codigo) = substring('$Codigo',1,length(rtrim(cuenta.codigo))))
	       or cuenta.codigo = '$Codigo'"; 
	       $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
}       
MJ('proceso realizado satisfactoriamente');
?>