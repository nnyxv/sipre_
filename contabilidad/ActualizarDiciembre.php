<?php  session_start();
include_once('FuncionesPHP.php');
//$_SESSION["sBasedeDatos"]= 'dbcontabilidad';
//$iActRev int, Actualizar o reversar si es 0 es actualizacion si es 1 es reverso
//$cDesde  Fecha desde en string
//$cHasta  Fecha hasta en string
//$cComprobantD Comprobante Desde
//$cComprobantH Comprobante Hasta

/*$iActRev = 0;
$cDesde  = '2006-08-01';
$cHasta  = '2006-08-31';
$cComprobantD = '1';
$cComprobantH ='1';*/
// ojojojojojojojojojoj begin transaction
$con = ConectarBD();
$SqlStr= " select  
Codigo
,sum(saldo_ant) as saldo_ant
,sum(debe) as debe
,sum(haber) as haber
,sum(saldo_act) as saldo_act
from movimientemp3 group by Codigo ";
$rs3 = EjecutarExec($con,$SqlStr); 
$sincredetalle = 0;
$NumeroAnt=0;
$iFila = -1;
if($iActRev == 0){
	  $SqlStr= "   update cuenta set 
							 Saldo_ant = 0,
							 debe =0,
	                         haber = 0,
							 debe_cierr =0,
	                         haber_cierr = 0";
	       $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
while ($row = ObtenerFetch($rs3)) {
	$iFila++;
        $Codigo = trim(ObtenerResultado($rs3,1,$iFila));
		$Saldo_ant = trim(ObtenerResultado($rs3,2,$iFila));
        $Debe = trim(ObtenerResultado($rs3,3,$iFila));
        $Haber = trim(ObtenerResultado($rs3,4,$iFila));
		$Saldo_act = trim(ObtenerResultado($rs3,5,$iFila));

		/*para actualizar las cuentas */
	  $SqlStr= "   update cuenta set Saldo_ant = Saldo_ant + $Saldo_ant,
							 debe = debe + $Debe,
	                         haber = haber + $Haber
	       where (length(rtrim(cuenta.codigo)) < length(rtrim('$Codigo'))
	       and rtrim(cuenta.codigo) = substring('$Codigo',1,length(rtrim(cuenta.codigo))))
	       or cuenta.codigo = '$Codigo'"; 
	   /*fin para actulaizar las cuentas */ 
	       $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
      
}       
}else{// para reverzar todos los del mes 
   /*para actualizar las cuentas */
	  $SqlStr= "   update cuenta set 
							 Saldo_ant = 0,
							 debe =0,
	                         haber = 0,
							 debe_cierr =0,
	                         haber_cierr = 0";
	       $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
}
$exc5 = EjecutarExec($con,"COMMIT");
MJ('proceso realizado satisfactoriamente');
?>