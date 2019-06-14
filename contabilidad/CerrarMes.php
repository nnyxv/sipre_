<?php  session_start();
include_once('FuncionesPHP.php');

$Mes = intval($TMesCierre);
 
$Ano =$TAnoCierre;	 
$AnoMes =trim($TAnoCierre).trim($TMesCierre); 


$cfechaproc2=date("Y-m-d",mktime(0,0,0,$Mes+1,1,$Ano));   //esta es la fecha del proximo mes a procesar
 
/* para verificar si es comprobante de cierre */
 $con = ConectarBD();
   $SqlStr='SELECT fec_proceso,cierrea FROM parametros ';
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if (NumeroFilas($exc)>0){
		   $dfechaproc = trim(ObtenerResultado($exc,1));
		   $tipocierre = trim(ObtenerResultado($exc,2));
		}
/* fin para verificar si es comporbante de cierre */

//
	//MJ($dfechaproc);
// $anomesviene = date("y",$dfechaproc).date("m",$dfechaproc);
$anomesviene = substr($dfechaproc,0,4).substr($dfechaproc,5,2);

//VERIFICAR SI EXISTEN COMPORBANTES DESCUADRADOS
  $SqlStr = "SELECT a.comprobant,a.fecha FROM enc_diario a WHERE a.comprobant AND a.actualiza = 0";
   $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
   if (NumeroFilas($exc2)>0){
		$ComDesc = trim(ObtenerResultado($exc2,1));
		$FechaDesc = trim(ObtenerResultado($exc2,2));
	}
		
if  (strlen(trim($ComDesc)) != 0){
	MJ('El Comprobante Nro '. $ComDesc .' de fecha ' .obFecha($FechaDesc) .' de Diario no esta Actualizado');
	return;
}

//VALIDAR SI EXISTEN ACTIVOS A DEPRECIAR, Y SI TIENEN DATOS INCOMPLETOS
$strSql = "SELECT * FROM deprecactivos WHERE estatus = 1 and year(fecha)='".$Ano."'";

$ejec = mysql_query($strSql,$con);
		
$faltaCompletar = mysql_num_rows($ejec);
if($faltaCompletar){
	echo"<script language='javascript'>		
			alert('Existen activos con datos incompletos');		
	  </script>";	 
	header("Location:frmdeprecactivos.php");
	
}else{
	$MesL = MesLetras($Mes);
	//VALIDA QUE HAYA ACTIVOS A DEPRECIAR EN LA FECHA DEL MES A CERRAR
	$strSql = "SELECT * FROM deprecactivos WHERE year(fecha)='".$Ano."' AND Nodeprec='SI' AND year(FechaDepre) = '".$Ano."' AND month(FechaDepre)='".$Mes."' ";		
	$exc3 = EjecutarExec($con,$strSql) or die($strSql);

	if (NumeroFilas($exc3)>0){
		//VERIFICAR SI ANTES DEL CIERRE DE MES SE REALIZO LA DEPRECIACIÓN DE ACTIVOS CORRESPONDIENTE
		if($AnoMes == $anomesviene){						
			$SqlStr = "select * from enc_dif where comprobant = 9999 and year(fecha) = ".$Ano." and month(fecha) = '".$Mes."'";
		}else{			
			$SqlStr = "select * from enc_diario where comprobant = 9999 and year(fecha) = ".$Ano." and month(fecha) = '".$Mes."'";		
		}
		$exc3 = EjecutarExec($con,$SqlStr) or die($SqlStr);
		if (NumeroFilas($exc3)<1){
			MJ('No se ha realizado el proceso de depreciaciónnnnn correspondiente al mes de '.$MesL);
			return;
		}		
		
		if($AnoMes <> $anomesviene){
			$SqlStr = "select * from movimien where comprobant = 9999 and year(fecha) = ".$Ano." and month(fecha) = '".$Mes."'";		
		}else{
			$SqlStr = "select * from movimiendif where comprobant = 9999 and year(fecha) = ".$Ano." and month(fecha) = '".$Mes."'";		
		}
		$exc3 = EjecutarExec($con,$SqlStr) or die($SqlStr);
		if (NumeroFilas($exc3)<1){
			MJ('No se ha realizado el proceso de depreciación correspondienteeeeee al mes de '.$MesL);
			return;
		}	
	}
}


//******************************************************************************************
		
//FIN VERIFICAR SI EXISTEN COMPORBANTES DESCUADRADOS

//Comienzo de la transaccion BEGIN 
 $SqlStr="BEGIN";
 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
//fin Comienzo de la transaccion BEGIN 

 $SqlStr="INSERT cnt0000 (codigo,Descripcion,fecha_year)
     SELECT rtrim(a.codigo),a.Descripcion,$Ano FROM cuenta a left join cnt0000 b 
     on rtrim(a.codigo) = rtrim(b.codigo) AND b.fecha_year = $Ano
     WHERE b.codigo is null;";
 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

/* CIERRE DE TERCEROS */
     $SqlStr=" INSERT cntterceros (combinado,cuenta,idobjeto,tabla,campos,fecha_year)
     select concat_ws('-',rtrim(a.combinado),$AnoMes),a.cuenta,a.idobjeto,a.tabla,a.campos,$Ano 
	 From cuentaterceros a left join cntterceros b on rtrim(a.combinado) = rtrim(b.combinado)
	 AND b.fecha_year = $Ano WHERE b.combinado is null";
     $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* FIN CIERRE DE TERCEROS */
 
 
 
 $sMes = '';
 $sPorcion = '';
if ($Mes == 1){
	$sMes = 'enero';
}elseif($Mes == 2){
	$sMes = 'febrero';
}elseif($Mes == 3){
	$sMes = 'marzo';
}elseif($Mes == 4){
	$sMes = 'abril';
}elseif($Mes == 5){
	$sMes = 'mayo';
}elseif($Mes == 6){
	$sMes = 'junio';
}elseif($Mes == 7){
	$sMes = 'julio';
}elseif($Mes == 8){
	$sMes = 'agosto';
}elseif($Mes == 9){
	$sMes = 'septiembre';
}elseif($Mes == 10){
	$sMes = 'octubre';
}elseif($Mes == 11){
	$sMes = 'noviembre';
}elseif($Mes == 12){
	$sMes = 'diciembre';
}
$sPorciond =  trim(substr($sMes,0,3)). "_d" ;
$sPorcionh =  trim(substr($sMes,0,3)). "_h"; 
$sPorciondc =  trim(substr($sMes,0,3)). "_cierrd" ;
$sPorcionhc =  trim(substr($sMes,0,3)). "_cierrh"; 
 
/* se hizo asi por MYSQL no acepta un form en el update*/ 

/*$Sql= "Select codigo,saldo_ant,debe,haber,debe_cierr,haber_cierr  from cuenta";
$rsReco = EjecutarExec($con,$Sql);
if (NumeroFilas($rsReco)>0){
				$iFila = -1;
			while ($row = ObtenerFetch($rsReco)) {
				$iFila++;
				$codigo = trim(ObtenerResultado($rsReco,1,$iFila));
			    $saldo_ant = trim(ObtenerResultado($rsReco,2,$iFila));
			    $debe = trim(ObtenerResultado($rsReco,3,$iFila)); 
			    $haber = trim(ObtenerResultado($rsReco,4,$iFila)); 
			    $debe_cierr = trim(ObtenerResultado($rsReco,5,$iFila));
			    $haber_cierr = trim(ObtenerResultado($rsReco,6,$iFila));
				if ($Mes == 6 || $Mes == 12){
						$SqlStr= " update cnt0000 set $sMes = $saldo_ant,$sPorciond = $debe,
						$sPorcionh = $haber,$sPorciondc = $debe_cierr,$sPorcionhc = $haber_cierr  	
						 where codigo = '$codigo' and fecha_year = $Ano ";
                }else{
                       $SqlStr= "	update cnt0000 set $sMes = $saldo_ant,$sPorciond = $debe,$sPorcionh = $haber 
			             where codigo = '$codigo' and fecha_year = $Ano ";
                }	
				$exec = EjecutarExec($con,$SqlStr);
   		   }
}	
*/
				if ($Mes == 6 || $Mes == 12){
						$SqlStr= " update cnt0000 a,cuenta b set a.$sMes = b.saldo_ant,a.$sPorciond = b.debe,
						a.$sPorcionh = b.haber,a.$sPorciondc = b.debe_cierr,a.$sPorcionhc = b.haber_cierr  	
						 where a.codigo = b.codigo and fecha_year = $Ano ";
                }else{
                       $SqlStr= "	update cnt0000 a,cuenta b set a.$sMes = b.saldo_ant,a.$sPorciond = b.debe,a.$sPorcionh = b.haber 
			             where a.codigo = b.codigo and fecha_year = $Ano ";
                }	
                $exec = EjecutarExec($con,$SqlStr) or die($SqlStr);
				
				
				/* CIERRE DE TERCEROS */
                    $SqlStr= "	update cntterceros a,cuentaterceros b set a.$sMes = b.saldo_ant,a.$sPorciond = b.debe,a.$sPorcionh = b.haber 
					where a.combinado = b.combinado and fecha_year = $Ano ";
					$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
				/* FIN CIERRE DE TERCEROS */


					
/* colocar e catalogo de cuenta en cero*/  
$SqlStr= " update cuenta
set saldo_ant = cuenta.saldo_ant +  (cuenta.debe + cuenta.debe_cierr) - (cuenta.haber + cuenta.haber_cierr),
cuenta.debe = 0,cuenta.haber = 0,cuenta.debe_cierr = 0,cuenta.haber_cierr = 0";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* fin colocar e catalogo de cuenta en cero*/  
 

/*Transferir a hisrtorico */  
$SqlStr = "insert into enc_historico
   select * from enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$TablaMesHis = "movhistorico".trim($Mes);
$SqlStr = "insert into $TablaMesHis
select * from movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "delete from enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "delete from movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* fin Transferir a hisrtorico */

/*Transferir Diferidos a Diarios */
$SqlStr = "insert into enc_diario
  select * from enc_dif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into movimien
  select * from movimiendif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

				/* CIERRE DE TERCEROS */
					$SqlStr = "insert into enlaceterceroshistorico
					select * from enlaceterceros
					where month('$dfechaproc') = month(fecha) and year('$dfechaproc') = year(fecha)";
					$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
				/* FIN CIERRE DE TERCEROS */


$SqlStr = "
delete from enc_dif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
   $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
   
$SqlStr = "delete from movimiendif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*Fin Transferir Diferidos a Diarios */

/* CIERRE DE TERCEROS */
		$SqlStr = "delete from enlaceterceros
		where month('$cfechaproc') = month(fecha) and year('$cfechaproc') = year(fecha)";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* FIN CIERRE DE TERCEROS */


/*Transferir Activos a Historicos */
/*$SqlStr = "
insert into DeprecActivosHist
select 
Tipo            
,Fecha           
,CompAdquisicion 
,ValResidual     
,ValDeprec       
,DepreMensual    
,MesesDepre      
,Descripcion     
,Ubicacion       
,Proveedor       
,Depreciar       
,DeprecAcum      
,Comprobante     
,PrimeraDepre    
,Nodeprec        
,TotDepFecha     
,FechaDepre      
,Observaciones   
,Usuario_i       
,Hora_i          
,Fecha_i         
,Usuario_m       
,Hora_m          
,Fecha_m         
,ActivoPerteneciente 
,PagoParcial         
,Codigo 
,$dfechaproc               
from deprecactivos";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);*/
/*Fin Transferir Activos a Historicos */

/*blanquear el comprobante de cierre y asignar nueva fecha de proceso*/ 
$SqlStr = " update parametros set comp_cierr = '',fec_proceso = '$cfechaproc2'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin blanquear el comprobante de cierre y aginar nueva fecha de proceso*/ 

/*$SqlStr = " update parametros 
set eliminarbancos = 0 ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);*/

/*asignar que el mes ya esta cerrado*/
$SqlStr = " Insert Into mesclose (anomes,cerrado) values ($AnoMes,1)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin asignar que el mes ya esta cerrado*/

/*Cerrar transaccion*/
$SqlStr = "COMMIT";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin Cerrar Transaccion*/

MJ('Proceso Finalizado Satisfactoriamente se reiniciara el sistema en este momento.');

?>
<script language="Javascript">
  parent.EjePrincipal();
</script>
