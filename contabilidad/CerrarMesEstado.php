<?php  session_start();
include_once('FuncionesPHP.php');
$Cod = $T_Company;
$TDebe = $TextDebe;
$THaber = $TextHaber;

$Mes = intval($Mes);
$TAnoCierre = $Ano;
$TMesCierre = $Mes;
$AnoMes = trim($TAnoCierre).trim($TMesCierres);  
$cfechaproc2 = date("Y-m-d",mktime(0,0,0,$Mes+1,1,$Ano));   //esta es la fecha del proximo mes a procesar

/* para verificar si es comporbante de cierre */
 $con = ConectarBD();
   $SqlStr="Select fec_proceso,cierrea from $Cod.parametros ";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if ( NumeroFilas($exc)>0){
		   $dfechaproc = trim(ObtenerResultado($exc,1));
		   $tipocierre = trim(ObtenerResultado($exc,2));
		}
/* fin para verificar si es comporbante de cierre */


/* para verificar si es comporbante de cierre */
 $con = ConectarBD();
   $SqlStr="Select fec_proceso,cierrea from sipre_contabilidad.parametros ";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if ( NumeroFilas($exc)>0){
		   $dfechaprocOriomka = trim(ObtenerResultado($exc,1));
		   $tipocierreOriomka = trim(ObtenerResultado($exc,2));
		   if (strval(obfecha($dfechaprocOriomka,"M")) == $Mes && strval(obfecha($dfechaprocOriomka,"A")) == $Ano){ 
		   $TablaPrincipalEnc = "enc_diario";
		   $TablaPrincipalDet = "movimien";
		   }else{
		   $TablaPrincipalEnc = "enc_dif";
		   $TablaPrincipalDet = "movimiendif";
		   }
		}
/* fin para verificar si es comporbante de cierre */



//VERIFICAR SI EXISTEN COMPORBANTES DESCUADRADOS
 $SqlStr=" SELECT   MAX(b.comprobant),a.fecha  FROM $Cod.enc_diario a, $Cod.movimien b
	       where a.comprobant = b.comprobant  
	       and a.fecha = b.fecha
           GROUP BY b.comprobant,a.fecha
           HAVING round(sum(b.debe),2) <> round(sum(b.haber),2) ";
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
					   $ComDesc = trim(ObtenerResultado($exc2,1));
					   $FechaDesc= trim(ObtenerResultado($exc2,2));
					}         
 if  (strlen(trim($ComDesc)) != 0)
	{
	  MJ('El Comprobante Nro '. $ComDesc .' de fecha ' .obFecha($FechaDesc) .' de Diario esta Descuadrado');
	  return;
	}
		
//FIN VERIFICAR SI EXISTEN COMPORBANTES DESCUADRADOS
		

 

//Comienzo de la transaccion BEGIN 
 $SqlStr="BEGIN";
 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
//fin Comienzo de la transaccion BEGIN 
/*
 $SqlStr=" INSERT cnt0000 (codigo,Descripcion,fecha_year)
     select rtrim(a.codigo),a.Descripcion,$Ano  From cuenta a left join cnt0000 b 
     on rtrim(a.codigo) = rtrim(b.codigo) AND b.fecha_year = $Ano
     WHERE b.codigo is null";
 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
*/
 
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
 


/* se hizo asi por MYSQL no acepta un form en el update 

$Sql= "Select codigo,saldo_ant,debe,haber,debe_cierr,haber_cierr  from cuenta";
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
/* colocar e catalogo de cuenta en cero  
$SqlStr= " update cuenta
set saldo_ant = cuenta.saldo_ant +  (cuenta.debe + cuenta.debe_cierr) - (cuenta.haber + cuenta.haber_cierr),
cuenta.debe = 0,cuenta.haber = 0,cuenta.debe_cierr = 0,cuenta.haber_cierr = 0";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* fin colocar e catalogo de cuenta en cero*/  


/*Transferir a hisrtorico */  
$SqlStr = "insert into $Cod.enc_historico
   select * from $Cod.enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$TablaMesHis = "$Cod.movhistorico".trim($Mes);
$SqlStr = "insert into $TablaMesHis
select * from $Cod.movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

/*Transferir a importados de la principal*/ 
echo "paso1";
$SqlStr = "insert into sipre_contabilidad.$TablaPrincipalEnc
   select * from $Cod.enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
echo "paso2";

$SqlStr = "insert into sipre_contabilidad.$TablaPrincipalDet
select * from $Cod.movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "delete from $Cod.enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "delete from $Cod.movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* fin Transferir a hisrtorico */

/*Transferir Diferidos a Diarios */
$SqlStr = "insert into $Cod.enc_diario
  select * from $Cod.enc_dif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into $Cod.movimien
  select * from $Cod.movimiendif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "
delete from $Cod.enc_dif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
   $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
   
"delete from $Cod.movimiendif
   where month('$cfechaproc2') = month(fecha) and year('$cfechaproc2') = year(fecha)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*Fin Transferir Diferidos a Diarios */

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

/*blanquear el comprobante de cierre y aginar nueva fecha de proceso*/ 
$SqlStr = " update $Cod.parametros set comp_cierr = '',fec_proceso = '$cfechaproc2'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin blanquear el comprobante de cierre y aginar nueva fecha de proceso*/ 

/*$SqlStr = " update parametros 
set eliminarbancos = 0 ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);*/

/*asignar que el mes ya esta cerrado*/
$SqlStr = " Insert Into $Cod.mesclose (anomes,cerrado) values ($AnoMes,1)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin asignar que el mes ya esta cerrado*/

$TDebe = strval(str_replace(',','',$TDebe));
$THaber = strval(str_replace(',','',$THaber));


/*asignar que el mes ya esta cerrado*/
$SqlStr = " Insert Into sipre_co_config.comcerrada (mes,ano,codigo,debe,haber) values ($Mes,$Ano,'$Cod',$TDebe,$THaber)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin asignar que el mes ya esta cerrado*/


/*Cerrar transaccion*/
$SqlStr = "COMMIT";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
/*fin Cerrar Transaccion*/

MJ('Proceso Finalizado Satisfactoriamente.');
?>
<form name="Cierre">
<script language="Javascript">
parent.mainFrame.document.frmPantallaCerrarTodas.BtnVer.onclick();
</script>
</form>
