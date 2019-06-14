<?php session_start();
function array_envia($array){
   $tmp = serialize($array);
   $tmp = urlencode($tmp);//codifica como una cadena
   return $tmp;
}//fin array_envia

function array_recibe($array){
   $tmp = stripslashes($array);//demarcacion de cadena
   $tmp = urldecode($tmp);//codifica como una cadena
   $tmp = unserialize($tmp);
   return $tmp;
}//fin array_recibe


function ConectarBD(){
$CN ='MY';
if ($CN == 'OD'){
//odbc 
   return odbc_connect("PAKASContabilidad","root","");     
}elseif ($CN ='MY'){
//my_sql
  return mysql_connect($_SESSION["sServidor"],"oriomka_root","oriomka");//Realiza la coneccion al Servidor Remoto
 // return mysql_connect("localhost","root","");//Realiza la coneccion al Servidor Remoto
}       
}//fin ConectarBD()

function ConectarBDAd(){
$CN ='MY';
		if ($CN == 'OD'){
		//ODBC
		   return odbc_connect("conexPHP","root","");     
		}elseif ($CN ='MY'){
		//my_sql
		  $link = mysql_connect("localhost","oriomka_root","oriomka");//Realiza la coneccion al Servidor Remoto
		//  $link = mysql_connect("localhost","root","");//Realiza la coneccion al Servidor Remoto
		   return $link; 
		}   
}//fin ConectarBD2()

function EjecutarExec($Connec,$StringSql){
$CN ='MY';
		if ($CN == 'OD'){
		   return odbc_exec($Connec,$StringSql);      
		}elseif ($CN =='MY'){
		//my_sql   
		 	//mysql_select_db("sipre_contabilidad", $Connec); //Abre la Base de Datos
		 	mysql_select_db($_SESSION["sBasedeDatos"], $Connec); //Abre la Base de Datos			
		   return mysql_query($StringSql,$Connec);
		 
		 }
}//fin EjecutarExec()


function EjecutarProc($Connec,$StringSql){
$CN ='MY';
		if ($CN == 'OD'){
		   return odbc_exec($Connec,$StringSql);      
		}elseif ($CN =='MY'){
		//my_sql   
		 	//mysql_select_db("sipre_contabilidad", $Connec); //Abre la Base de Datos
		 	mysql_select_db($_SESSION["sBasedeDatos"], $Connec); //Abre la Base de Datos			
		   return mysql_query($StringSql,$Connec);
		 }
}//fin EjecutarExec()


function EjecutarExecAd($Connec,$StringSql){
$CN ='MY';
		if ($CN == 'OD'){
		   return odbc_exec($Connec,$StringSql);      
		}elseif ($CN =='MY'){
		//my_sql   
		 	//mysql_select_db("oriomka_sipre_co_config", $Connec); //Abre la Base de Datos
		 	mysql_select_db("sipre_co_config", $Connec); //Abre la Base de Datos			
		   return mysql_query($StringSql,$Connec);
		 }
}//fin EjecutarExecAd()

  
function ObtenerFetch($oCursor){
$CN ='MY';
	if ($CN == 'OD'){
	   return odbc_fetch_row($oCursor);      
	}elseif ($CN =='MY'){   
	//my_sql   
	   return mysql_fetch_row($oCursor);
	}	
}//fin ObtenerFetch()


function ObtenerResultado($oCursor,$Field,$Fila=0){
$CN ='MY';
if ($Fila == '') {
    $Fila = 0; 
}
if ($CN == 'OD'){
   return odbc_result($oCursor,$Field);      
}elseif ($CN =='MY'){     
 return mysql_result($oCursor,$Fila,$Field-1);      
   
}

}//fin ObtenerResultado()


function NumeroFilas($oCursor){
		$CN ='MY';
		if ($CN == 'OD'){
			return odbc_num_rows($oCursor);
		}elseif ($CN =='MY'){    
		   return mysql_num_rows($oCursor);
		}
}

function MJ($sValor){
echo "<script language='javascript'>           
          alert('$sValor'); 
   </script>";
}

function obFecha($sFecha,$sParte=''){
		if ($sParte == ''){
			$sFechaAno = substr($sFecha,0,4);
			$sFechaMes = substr($sFecha,5,2);
			$sFechaDia = substr($sFecha,8,2);
			$sFechaFinal = $sFechaDia ."/". $sFechaMes."/".$sFechaAno;
		}
        if ($sParte == 'D'){
			$sFechaDia = substr($sFecha,8,2);
			$sFechaFinal = $sFechaDia;
		}
		if ($sParte == 'M'){
			$sFechaMes = substr($sFecha,5,2);
			$sFechaFinal = $sFechaMes;
		}
		if ($sParte == 'A'){
			$sFechaAno = substr($sFecha,0,4);
			$sFechaFinal = $sFechaAno;
		}
        return $sFechaFinal;
}
function RobFecha($sFecha,$sParte=''){
		if ($sParte == ''){
			$sFechaDia = substr($sFecha,0,2);
			$sFechaMes = substr($sFecha,3,2);
			$sFechaAno = substr($sFecha,6,4);
			$sFechaFinal =  $sFechaAno."-". $sFechaMes."-".$sFechaDia;
		}
        if ($sParte == 'D'){
			$sFechaDia = substr($sFecha,0,2);
			$sFechaFinal = $sFechaDia;
		}
		if ($sParte == 'M'){
			$sFechaMes = substr($sFecha,3,2);
			$sFechaFinal = $sFechaMes;
		}
		if ($sParte == 'A'){
			$sFechaAno = substr($sFecha,6,4);
			$sFechaFinal = $sFechaAno;
		}
        return $sFechaFinal;
}
function MesLetras($Mes){
$sMesLetra = "";
 if ($Mes == 1){ 
    $sMesLetra = "enero";
 }elseif ($Mes == 2){ 
    $sMesLetra = "febrero";
 }elseif ($Mes == 3){ 
    $sMesLetra = "marzo";
 }elseif ($Mes == 4){ 
    $sMesLetra = "abril";
 }elseif ($Mes == 5){ 
    $sMesLetra = "mayo";
 }elseif ($Mes == 6){ 
    $sMesLetra = "junio";
 }elseif ($Mes == 7){ 
    $sMesLetra = "julio";
 }elseif ($Mes == 8){ 
    $sMesLetra = "agosto";
 }elseif ($Mes == 9){ 
    $sMesLetra = "septiembre";
 }elseif ($Mes == 10){ 
    $sMesLetra = "octubre";
 }elseif ($Mes == 11){ 
    $sMesLetra = "noviembre";
 }elseif ($Mes == 12){ 
    $sMesLetra = "diciembre";
}
return $sMesLetra;
} 

function MovimientosH($sFechaDesde,$sFechaHasta){
        $con = ConectarBD();
		//($sFechaDesde = substr($sFechaDesde,6,4)."/".substr($sFechaDesde,3,2)."/". substr($sFechaDesde,0,2);
		//$sFechaHasta = substr($sFechaHasta,6,4)."/".substr($sFechaHasta,3,2)."/". substr($sFechaHasta,0,2);

		$sUsuario = $_SESSION["UsuarioSistema"];
		$sTabla='parametros';
        $sCampos='fec_proceso';
        $SqlStr='Select '.$sCampos.' from '.$sTabla;
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if (NumeroFilas($exc)>0){
             $dfec_proceso=trim(ObtenerResultado($exc,1));
        }
        $SqlStr=" delete from movimientempgeneral where usuario = '$sUsuario'";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			
		$AnoMesProceso = obFecha($dfec_proceso,'A').obFecha($dfec_proceso,'M');
		$AnoMesDesde = obFecha($sFechaDesde,'A').obFecha($sFechaDesde,'M');
		$AnoMesHasta = obFecha($sFechaHasta,'A').obFecha($sFechaHasta,'M');
		$AnoMesDesdeTemp = $AnoMesDesde;
		
				
		       
		
		
            		$AnoMesDesdeIncre = $AnoMesDesde;  
					$MesDesde = obFecha($sFechaDesde,'M');
				    $AnoDesde = obFecha($sFechaDesde,'A');
					$sFechaSumar = date("Y-m-d",mktime(0,0,0,intval($MesDesde),1,intval($AnoDesde))); 
					while($AnoMesDesdeIncre <= $AnoMesHasta){
					 /*   echo "incre".$AnoMesDesdeIncre .'<br>';
						echo "rpceoso".$AnoMesProceso .'<br>';*/
						$AnoBuscar = intval(substr($AnoMesDesdeIncre,0,4));
						$MesBuscar = intval(substr($AnoMesDesdeIncre,4,2));
					//echo " $AnoMesDesdeIncre <= $AnoMesHasta <br>";
								/* PARA LOS HISTORICOS */ 							     
								if ($AnoMesDesdeIncre < $AnoMesProceso){
							/*		echo $AnoMesDesdeIncre."<BR>";
									echo $MesBuscar."<BR>";*/
									$SqlStr="insert into movimientempgeneral
									select 
									comprobant
									,documento
									,codigo    
									,descripcion  
									,debe      
									,haber     
									,numero    
									,fecha
									,cc
									,im
									,'$sUsuario' from movhistorico".strval($MesBuscar)." where year(fecha) =". strval($AnoBuscar);
								}elseif($AnoMesDesdeIncre == $AnoMesProceso){	
								/* PARA LOS ACTUALES */ 			
									$SqlStr="insert into movimientempgeneral
										select 
										comprobant
		    							,documento
										,codigo    
										,descripcion  
										,debe      
										,haber     
										,numero    
										,fecha
										,cc
										,im
    									,'$sUsuario' from movimien ";
								}elseif($AnoMesDesdeIncre > $AnoMesProceso){								
								/* PARA LOS POSTERIORES */
									//echo "paso $MesBuscar $AnoBuscar";	
									$SqlStr="insert into movimientempgeneral
									    select
										comprobant
		    							,documento
										,codigo    
										,descripcion  
										,debe      
										,haber     
										,numero    
										,fecha
										,cc
										,im
										,'$sUsuario' from movimiendif where month(fecha) = $MesBuscar and year(fecha)= $AnoBuscar";
								}					
						$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$sFechaSumar = date("Y-m-d",mktime(0,0,0,intval($MesDesde)+1,1,intval($AnoDesde))); 
						//echo $sFechaSumar;
						$MesDesde = $MesDesde + 1; 
						$AnoMesDesdeIncre = obFecha($sFechaSumar,'A').obFecha($sFechaSumar,'M');
					}
}
function actualizarcampos($s1,$s2,$s3){
	
}


function SaldoAnteriorPosteriores($FechaDesde,$sUsuario){
$con = ConectarBD();
$sUsuario = $_SESSION["UsuarioSistema"];
$SqlStr = "delete from tempsuma where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from tempsuma1 where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


$SqlStr = "
insert into tempsuma1
select a.codigo,sum(b.debe - b.haber) as Monto,'$sUsuario'
from cuenta a,movimien b where a.codigo = substring(b.codigo,1,length(rtrim(a.codigo)))
group by a.codigo
union all
select a.codigo,sum(b.debe - b.haber),'$sUsuario'
 from cuenta a,movimiendif b 
where a.codigo = substring(b.codigo,1,length(rtrim(a.codigo))) and Fecha < '$FechaDesde'  group by a.codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = " insert into tempsuma
select a.codigo,sum(a.Monto) as Monto,'$sUsuario' 
from tempsuma1 a where usuario = '$sUsuario' group by a.codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "
update cuentageneral a,tempsuma b
 set a.saldo_ant = a.saldo_ant + b.Monto
 where a.codigo = b.codigo and a.usuario = '$sUsuario' and b.usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
}


function SumaMovimientos ($sUsuario){ 
$con = ConectarBD();
$SqlStr = " 
insert into tempsumamovimientos
select a.codigo,sum(b.debe) as debe,sum(b.Haber) as haber from cuentageneral a
,movimientempgeneral b where a.codigo = substring(b.codigo,1,length(rtrim(a.codigo)))
and b.usuario = '$sUsuario' 
and a.usuario = '$sUsuario' 
group by a.codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "  update cuentageneral a,tempsumamovimientos b set a.debe = b.debe ,a.haber = b.haber 
where a.codigo = b.codigo and usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
}


function CargarSaldos($cDesde1,$cHasta1,$cDesde2='',$cHasta2='',$icierre = 0){
$con = ConectarBD();

//$_SESSION["UsuarioSistema"] = '001';
//$_SESSION["sBasedeDatos"] = "sipre_contabilidad";
/*$cDesde1 = "01/07/2006";
$cHasta1 = "30/09/2006"; 
$icierre = "1"; 
*/
$sUsuario = $_SESSION["UsuarioSistema"];
$SqlStr=" delete from cuentageneral where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


$SqlStr="select  count(*) from enc_diario where actualiza = 0";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


$BuscarEnTabla = false;
if (NumeroFilas($exc)>0){
     $dValor = trim(ObtenerResultado($exc,1)); 
	 if($dValor > 0){
	    $BuscarEnTabla = false;
	 }else{
	    $BuscarEnTabla = True;
	 }
}

$SqlStr="select  fec_proceso,mescierre from parametros";
		       $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		       if ( NumeroFilas($exc)>0){
		           $dfec_proceso = trim(ObtenerResultado($exc,1)) ; 
				   $mescierre = trim(ObtenerResultado($exc,2)) ; 
		       }
				$AnoMesProceso = obFecha($dfec_proceso,'A').obFecha($dfec_proceso,'M');
				$dfec_proceso1 = '01/'.obFecha($dfec_proceso,'M')."/".obFecha($dfec_proceso,'A');
				$AnoMesDesde = obFecha($cDesde1,'A').obFecha($cDesde1,'M');
				$AnoMesHasta = obFecha($cHasta1,'A').obFecha($cHasta1,'M');

/*$cDesde1 = substr($cDesde1,6,4)."/".substr($cDesde1,3,2)."/". substr($cDesde1,0,2);
$cHasta1 = substr($cHasta1,6,4)."/".substr($cHasta1,3,2)."/". substr($cHasta1,0,2);*/
//if($_SESSION["CCSistema"] != ""){
  $EstadoCuenta =  "sipre_contabilidad.cuenta";
/*}else{
  $EstadoCuenta =  "cuenta";
}*/

if($AnoMesProceso == $AnoMesDesde && $AnoMesDesde == $AnoMesHasta && $BuscarEnTabla == True){
if ($icierre == 1){
$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
		,Deshabilitar,FechaDes,Comentario,usuario)  
		select codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
		,Deshabilitar,FechaDes,Comentario,'$sUsuario' from $EstadoCuenta where DesHabilitar = 0 ";
}else{
$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
		,Deshabilitar,FechaDes,Comentario,usuario)  
		select codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,0,0,publicacion 
		,Deshabilitar,FechaDes,Comentario,'$sUsuario' from $EstadoCuenta where DesHabilitar = 0 ";
}
		if ($cDesde2 != ''){
		$SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
		}
			$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		return;
}

if( $AnoMesDesde < $AnoMesProceso && $AnoMesDesde == $AnoMesHasta){
                 $Mesword = MesLetras(strval(obFecha($cDesde1,'M')));	 
				 $Mes_d = substr($Mesword,0,3)."_d";
				 $Mes_h = substr($Mesword,0,3)."_h";
				/* $MesCierr_d = substr($Mesword,0,3)."_cierrd";
				 $MesCierr_h = substr($Mesword,0,3)."_cierrh";*///cambiado ernesto
				 $MesCierr_d = substr("diciembre",0,3)."_cierrd";//cambiado ernesto
				 $MesCierr_h = substr("diciembre",0,3)."_cierrh";//cambiado ernesto
    			 $Fecha_ano =  obFecha($cDesde1,'A');     
			
			if (strval(obFecha($cDesde1,'M')) == $mescierre){
				    if($icierre == 0){
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,'','1900-01-01',descripcion,$Mesword,$Mes_d,$Mes_h,0,0,0 
						,0,'1900-01-01','','$sUsuario' from cnt0000 where fecha_year = $Fecha_ano";
						if ($cDesde2 != ''){
						     $SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
						}
					}else{
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,'','1900-01-01',descripcion,$Mesword,$Mes_d,$Mes_h,$MesCierr_d,$MesCierr_h,0 
						,0,'1900-01-01','','$sUsuario' from cnt0000 where fecha_year = $Fecha_ano";
						if ($cDesde2 != ''){
						     $SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
						}	
					}			
			}else{
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,'','1900-01-01',descripcion,$Mesword,$Mes_d,$Mes_h,0,0,0 
						,0,'1900-01-01','','$sUsuario' from cnt0000 where fecha_year = $Fecha_ano";
						if ($cDesde2 != ''){
						     $SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
						}
			}

			//echo $SqlStr;
			$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			return;
}


//MovimientosH($cDesde1,$cHasta1);
        		$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
		,Deshabilitar,FechaDes,Comentario,usuario)  
		select codigo,cod_sus,ult_mov,descripcion,0,0,0,0,0,publicacion 
		,Deshabilitar,FechaDes,Comentario,'$sUsuario' from $EstadoCuenta where DesHabilitar = 0 ";
		if ($cDesde2 != ''){
		$SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
		}
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
		       $SqlStr="select  fec_proceso from parametros";
		       $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		       if ( NumeroFilas($exc)>0){
		           $dfec_proceso = trim(ObtenerResultado($exc,1)) ; 
		       }
		
			if($AnoMesDesde == $AnoMesProceso){	
		       $SqlStr=" update cuentageneral a,cuenta b set a.saldo_ant = b.saldo_ant
			    where a.codigo = b.codigo and  usuario = '$sUsuario'";	
		        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			}elseif($AnoMesDesde > $AnoMesProceso){
		         $SqlStr=" update cuentageneral a,cuenta b set a.saldo_ant = b.saldo_ant
		  	     where a.codigo = b.codigo and  usuario = '$sUsuario'";	
				 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
				 $SqlStr= " select  
				  Codigo
				,sum(debe) as debe
				,sum(haber) as haber
				,max(fecha) as fecha  
				from vmovimientodo where  fecha >= '$dfec_proceso1' and fecha < '$cDesde1' group by Codigo  ";
				$rs3 = EjecutarExec($con,$SqlStr) or die($SqlStr) ; 
				$sincredetalle = 0;
				$NumeroAnt=0;
				$iFila = -1;
				while ($row = ObtenerFetch($rs3)) {
					$iFila++;
						$CodigoMov = trim(ObtenerResultado($rs3,1,$iFila));
						$DebeMov = trim(ObtenerResultado($rs3,2,$iFila));
						$HaberMov = trim(ObtenerResultado($rs3,3,$iFila));
						$FechaMov = trim(ObtenerResultado($rs3,4,$iFila));
						
						  $SqlStr= "   update cuentageneral set saldo_ant = ROUND(saldo_ant,2) + ROUND($DebeMov,2) - ROUND($HaberMov,2)
							where (length(ltrim(rtrim(cuentageneral.codigo))) < length(ltrim(rtrim('$CodigoMov')))
						   and ltrim(rtrim(cuentageneral.codigo)) = substring('$CodigoMov',1,length(ltrim(rtrim(cuentageneral.codigo)))))
						   or cuentageneral.codigo = '$CodigoMov' and  usuario = '$sUsuario'"; 
						   $exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());

						 }	
				 
				 
			      //SaldoAnteriorPosteriores($cDesde1,$sUsuario);
			}elseif ($AnoMesDesde < $AnoMesProceso){
		         $Mesword = MesLetras(strval(obFecha($cDesde1,'M')));	 
				 $Mes_d = substr($Mesword,0,3)."_d";
				 $Mes_h = substr($Mesword,0,3)."_h";
				 $MesCierr_d = substr("diciembre",0,3)."_cierrd";
				 $MesCierr_h = substr("diciembre",0,3)."_cierrh";

				 
			 $Fecha_ano =  obFecha($cDesde1,'A');     
				//if (strval(obFecha($cDesde1,'M')) == 6 or strval(obFecha($cDesde1,'M')) == 12){
				if (strval(obFecha($cDesde1,'M')) == $mescierre){
				    if($icierre == 0){
				    	$SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword,a.debe = b.$Mes_d,a.haber =	b.$Mes_h
						 where a.codigo = b.codigo and fecha_year = $Fecha_ano";	
					}else{
				    	$SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword,a.debe = b.$Mes_d + b.$MesCierr_d,a.haber+ $MesCierr_h = b.$Mes_h
						 where a.codigo = b.codigo and fecha_year = $Fecha_ano ";	
					}
				}else{
		   	     $SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword
		  	     where a.codigo = b.codigo and fecha_year = $Fecha_ano";	
				} 
		        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
    }

	

	
	  /*$SqlStr= "     update cuentageneral,vmovimientodo b set cuentageneral.debe = cuentageneral.debe + b.debe,
	                         cuentageneral.haber = cuentageneral.haber + b.haber
	       where  b.fecha between  '$cDesde1' and '$cHasta1' and (length(ltrim(rtrim(cuentageneral.codigo))) < length(ltrim(rtrim(b.codigo)))
	       and ltrim(rtrim(cuentageneral.codigo)) = substring(b.codigo,1,length(ltrim(rtrim(cuentageneral.codigo)))))
	       or cuentageneral.codigo = b.codigo ";
	        $exec4 = EjecutarExec($con,$SqlStr) or die($SqlStr); */
			

$SqlStr= " select  
Codigo
,sum(debe) as debe
,sum(haber) as haber
,max(fecha) as fecha  
from vmovimientodo where  fecha between  '$cDesde1' and '$cHasta1' group by Codigo  ";
//echo $SqlStr;
$rs3 = EjecutarExec($con,$SqlStr) or die($SqlStr) ; 
$sincredetalle = 0;
$NumeroAnt=0;
$iFila = -1;
while ($row = ObtenerFetch($rs3)) {
	$iFila++;
	    $CodigoMov = trim(ObtenerResultado($rs3,1,$iFila));
        $DebeMov = trim(ObtenerResultado($rs3,2,$iFila));
        $HaberMov = trim(ObtenerResultado($rs3,3,$iFila));
        $FechaMov = trim(ObtenerResultado($rs3,4,$iFila));
		
		  $SqlStr= "   update cuentageneral set debe = debe + $DebeMov,
	                         haber = haber + $HaberMov
                           where (length(ltrim(rtrim(cuentageneral.codigo))) < length(ltrim(rtrim('$CodigoMov')))
	       and ltrim(rtrim(cuentageneral.codigo)) = substring('$CodigoMov',1,length(ltrim(rtrim(cuentageneral.codigo)))))
	       or cuentageneral.codigo = '$CodigoMov' and  usuario = '$sUsuario'"; 
		   $exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());

		}	
			
			
	/* Fin Buscar Saldos Anteriores de las cuentas Seleccionadas*/
       //  $SqlStr=" call SumaMovimientos('$sUsuario') ";
       //  $exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());
}



function CargarSaldosAnalitico($cDesde1,$cHasta1,$cDesde2='',$cHasta2='',$icierre = 0){
$con = ConectarBD();
//$_SESSION["UsuarioSistema"] = '001';MovimientosH
//$_SESSION["sBasedeDatos"] = "sipre_contabilidad";
/*$cDesde1 = "01/07/2006";
$cHasta1 = "30/09/2006"; 
$icierre = "1"; 
*/
$sUsuario = $_SESSION["UsuarioSistema"];
$SqlStr=" delete from cuentageneral where rtrim(usuario) = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


$SqlStr="select  count(*) from enc_diario where actualiza = 0";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$BuscarEnTabla = false;
if (NumeroFilas($exc)>0){
     $dValor = trim(ObtenerResultado($exc,1)); 
	 if($dValor > 0){
	    $BuscarEnTabla = false;
	 }else{
	    //echo "paso";
	    $BuscarEnTabla = True;
	 }
}

$SqlStr="select  fec_proceso,mescierre from parametros";
		       $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		       if ( NumeroFilas($exc)>0){
		           $dfec_proceso = trim(ObtenerResultado($exc,1)) ; 
				   $mescierre = trim(ObtenerResultado($exc,2)) ; 
		       }
				$AnoMesProceso = obFecha($dfec_proceso,'A').obFecha($dfec_proceso,'M');
				$AnoMesDesde = obFecha($cDesde1,'A').obFecha($cDesde1,'M');
				$AnoMesHasta = obFecha($cHasta1,'A').obFecha($cHasta1,'M');


/*$cDesde1 = substr($cDesde1,6,4)."/".substr($cDesde1,3,2)."/". substr($cDesde1,0,2);
$cHasta1 = substr($cHasta1,6,4)."/".substr($cHasta1,3,2)."/". substr($cHasta1,0,2);*/
//if($_SESSION["CCSistema"] != ""){
  $EstadoCuenta =  "sipre_contabilidad.cuenta";
/*}else{
  $EstadoCuenta =  "cuenta";
}*/
//echo "$AnoMesProceso == $AnoMesDesde $BuscarEnTabla " ; 
//&& $BuscarEnTabla == True

if($AnoMesProceso == $AnoMesDesde OR $AnoMesDesde > $AnoMesProceso){
		if ($icierre == 1){
				        $SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,cod_sus,ult_mov,ltrim(rtrim(descripcion)),saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,'$sUsuario' from $EstadoCuenta where DesHabilitar = 0 ";
		}else{
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
	 					,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,cod_sus,ult_mov,ltrim(rtrim(descripcion)),saldo_ant,debe,haber,0,0,publicacion 
						,Deshabilitar,FechaDes,Comentario,'$sUsuario' from $EstadoCuenta where DesHabilitar = 0 ";
        }
				if ($cDesde2 != ''){
				$SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
				}
				$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

				if($AnoMesDesde > $AnoMesProceso){	
					$SqlStr=" select codigo from cuentageneral where usuario = '$sUsuario'";
					$exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);
							  while ($row1 = ObtenerFetch($exc1)) {
							            $codCuenta = $row1[0];
										$SqlStr=" select sum(debe),sum(haber) from movimien
												where codigo = '$codCuenta' and fecha < '$cDesde1'
												union all
												select sum(debe),sum(haber) from movimiendif
												where codigo = '$codCuenta' and fecha < '$cDesde1'";
												$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
												 $sumDebe = 0;
												 $sumHaber = 0;
												  while ($row = ObtenerFetch($exc)) {
												     if(is_null($row[0])){$row[0] = 0;}
													 if(is_null($row[1])){$row[1] = 0;}
													 $sumDebe = bcadd($sumDebe,$row[0],2);
													 $sumHaber =bcadd($sumHaber,$row[1],2);
												  }
												 $SqlStr=" update cuentageneral 
												 set saldo_ant = saldo_ant + $sumDebe - $sumHaber
												 where codigo = '$codCuenta'  and  usuario = '$sUsuario'"; 
												 $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
							  }	
				}			
				
		
}

if($AnoMesDesde < $AnoMesProceso){
                 $Mesword = MesLetras(strval(obFecha($cDesde1,'M')));	 
				 $Mes_d = substr($Mesword,0,3)."_d";
				 $Mes_h = substr($Mesword,0,3)."_h";
				 $MesCierr_d = substr("diciembre",0,3)."_cierrd";
				 $MesCierr_h = substr("diciembre",0,3)."_cierrh";
    			 $Fecha_ano =  obFecha($cDesde1,'A');     
			
			if (strval(obFecha($cDesde1,'M')) == $mescierre){
				    if($icierre == 0){
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,'','1900-01-01',ltrim(rtrim(descripcion)),$Mesword,$Mes_d,$Mes_h,0,0,0 
						,0,'1900-01-01','','$sUsuario' from cnt0000 where fecha_year = $Fecha_ano";
						if ($cDesde2 != ''){
						     $SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
						}
					}else{
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,'','1900-01-01',ltrim(rtrim(descripcion)),$Mesword,$Mes_d,$Mes_h,$MesCierr_d,$MesCierr_h,0 
						,0,'1900-01-01','','$sUsuario' from cnt0000 where fecha_year = $Fecha_ano";
						if ($cDesde2 != ''){
						     $SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
						}	
					}	
						
					
					
			}else{
						$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
						,Deshabilitar,FechaDes,Comentario,usuario)  
						select codigo,'','1900-01-01',ltrim(rtrim(descripcion)),$Mesword,$Mes_d,$Mes_h,0,0,0 
						,0,'1900-01-01','','$sUsuario' from cnt0000 where fecha_year = $Fecha_ano";
						if ($cDesde2 != ''){
						     $SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
						}
			}

			
			$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			
		
			
		
}


MovimientosH($cDesde1,$cHasta1);

	/*	$SqlStr=" insert into cuentageneral (codigo,cod_sus,ult_mov,descripcion,saldo_ant,debe,haber,debe_cierr,haber_cierr,publicacion 
		,Deshabilitar,FechaDes,Comentario,usuario)  
		select codigo,cod_sus,ult_mov,descripcion,0,0,0,0,0,publicacion 
		,Deshabilitar,FechaDes,Comentario,'$sUsuario' from $EstadoCuenta where DesHabilitar = 0 ";
		if ($cDesde2 != ''){
		$SqlStr.= " and codigo between '$cDesde2' and  '$cHasta2' ";
		}
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

		
		       
			
			if($AnoMesDesde == $AnoMesProceso){	
		       $SqlStr=" update cuentageneral a,cuenta b set a.saldo_ant = b.saldo_ant
			    where a.codigo = b.codigo";	
		        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			}elseif($AnoMesDesde > $AnoMesProceso){
		         $SqlStr=" update cuentageneral a,cuenta b set a.saldo_ant = b.saldo_ant
		  	     where a.codigo = b.codigo";	
		         $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			      SaldoAnteriorPosteriores($cDesde1,$sUsuario);
			}elseif ($AnoMesDesde < $AnoMesProceso){
		         $Mesword = MesLetras(strval(obFecha($cDesde1,'M')));	 
				 $Mes_d = substr($Mesword,0,3)."_d";
				 $Mes_h = substr($Mesword,0,3)."_h";
				 $MesCierr_d = substr($Mesword,0,3)."_cierrd";
				 $MesCierr_h = substr($Mesword,0,3)."_cierrh";

				 
			 $Fecha_ano =  obFecha($cDesde1,'A');     
				if (strval(obFecha($cDesde1,'M')) == 6 or strval(obFecha($cDesde1,'M')) == 12){
				    if($icierre == 0){
				    	$SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword,a.debe = b.$Mes_d,a.haber =	b.$Mes_h
						 where a.codigo = b.codigo and fecha_year = $Fecha_ano";	
					}else{
				    	$SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword,a.debe = b.$Mes_d + b.$MesCierr_d,a.haber+ $MesCierr_h = b.$Mes_h
						 where a.codigo = b.codigo and fecha_year = $Fecha_ano ";	
					}
				}else{
		   	     $SqlStr=" update cuentageneral a,cnt0000 b set a.saldo_ant = b.$Mesword
		  	     where a.codigo = b.codigo and fecha_year = $Fecha_ano";	
				} 
		        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
    }
	/* Fin Buscar Saldos Anteriores de las cuentas Seleccionadas*/
        // $SqlStr=" call SumaMovimientos('$sUsuario') ";
        // $exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());*/
}




function auditoria($sentencia, $tabla, $camposClave){
	$sentencia= str_replace("'", "\"", $sentencia);
	$con = ConectarBDAd();
    $sTabla='auditoria';
    $sValores='';
    $sCampos='';
    $sCampos.='usuario';
    $sValores.="'".$_SESSION["UsuarioSistema"]."'";
    $sCampos.=',sentencia';
    $sValores.=",'".$sentencia."'";
    $sCampos.=',tabla';
    $sValores.=",'".$tabla."'";
    $sCampos.=',campos';
    $sValores.=",'".$camposClave."'";
    $sCampos.=',fechaHora';
    $sValores.=",current_timestamp()";
    $SqlStr='';
    $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
    $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	return $exc;
}

function VerificarAcceso($numeroMenu, $sentencia){
	$con = ConectarBDAd();
	$SqlStr='';
    $SqlStr="SELECT a.habilitar FROM mapaacceso a, usuario u, menus m WHERE u.nombre= "."'".$_SESSION["UsuarioSistema"]."' ".
    		"AND a.codigomapa= u.acceso AND a.nroopcion= ".$numeroMenu." AND m.numero= ".$numeroMenu;
    $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
        if (NumeroFilas($exc)>0){
        	$habilitar= trim(ObtenerResultado($exc,1));
        	$permiso= 0;
        	if ($sentencia == "I" || $sentencia == "i") {
        		$permiso= substr($habilitar, 0, 1);
        	}elseif ($sentencia == "U" || $sentencia == "u"){
        		$permiso= substr($habilitar, 1, 1);
        	}elseif ($sentencia == "D" || $sentencia == "d"){
        		$permiso= substr($habilitar, 2, 1);
        	}elseif ($sentencia == "S" || $sentencia == "s"){
        		$permiso= substr($habilitar, 3, 1);
        	}
        	if ($permiso == 1) {
        		return true;
        	}else{
        		return false;
        	}
        }else{
        	return false;
        }
}

function verificarConectados($modo){
	$con = ConectarBDAd();
	$SqlStr='';
	if ($modo == "N" || $modo == "n") {
		$sCondicion.="conectado= '1N'";
	}elseif ($modo == "E" || $modo == "e") {
		$sCondicion.="conectado= '1E'";
	}
	$sCondicion.=" AND nombre <> '".$_SESSION["UsuarioSistema"]."'";
	$sCampos= "";
	$sCampos.= "nombusuario, telefono, email, departamento, extension";
	$SqlStr= "SELECT ".$sCampos." FROM usuario WHERE ".$sCondicion;
	$exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
	while($row= mysql_fetch_row($exc)){
		$conectados[]= array("nombre" => $row[0], "tlfn" => $row[1], "email" => $row[2], "dpto" => $row[3], "ext" => $row[4]);
		$contador++;
	}
	return $conectados;
}

function registrar($modo){
	$con = ConectarBDAd();
	$SqlStr='';
	if ($modo == "N" || $modo == "n") {
		$sCampos.="conectado= '1N'";
	}elseif ($modo == "E" || $modo == "e") {
		$sCampos.="conectado= '1E'";
	}
	$sCondicion='';
    $sCondicion.='nombre= '."'".$_SESSION["UsuarioSistema"]."'";
	$SqlStr= "UPDATE usuario SET ".$sCampos." WHERE ".$sCondicion;
	$exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
}
function CargarFormatos($sFormato='',$sUsuario='001'){
$con = ConectarBD();
$SqlStr = " delete from tempformato1 where usuario = '". $sUsuario ."'";

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr." ".mysql_error());
$SqlStr = "insert into tempformato1(NumeroRenglon,saldo_ant,debe,haber,usuario)
      select a.NumeroRenglon,sum(round(b.saldo_ant,2) * If(a.PoN='-',-1,1)) as saldo_ant
	,round(sum(round((b.debe),2) * If(a.PoN='-',-1,1)),2)  + round(sum(round((b.debe_cierr),2) * If(a.PoN='-',-1,1)),2) as debe ,round(sum(round((b.haber),2) * If(a.PoN='-',-1,1)),2) + round(sum(round((b.haber_cierr),2) * If(a.PoN='-',-1,1)),2) as haber,'".$sUsuario ."'
	from cuentasconfiguradas a,cuentageneral b
	where ltrim(rtrim(a.codigocuenta)) = ltrim(rtrim(b.codigo))
	and a.codigoFormato = '".$sFormato."'
	and b.usuario = '". $sUsuario ."'
	group by a.CodigoFormato,a.NumeroRenglon 
";

//select a.NumeroRenglon,sum(b.saldo_ant * If(a.PoN='-',-1,1)) as saldo_ant
//	,sum(round((b.debe),2) * If(a.PoN='-',-1,1))  + sum(round((b.debe_cierr),2) * If(a.PoN='-',-1,1)) as debe ,sum(round((b.haber),2) * If(a.PoN='-',-1,1)) + sum(round((b.haber_cierr),2) * If(a.PoN='-',-1,1)) as haber,'".$sUsuario ."'
//select a.NumeroRenglon,sum(b.saldo_ant * If(a.PoN='-',-1,1)) as saldo_ant
//	,sum(b.debe * If(a.PoN='-',-1,1))  + sum(b.debe_cierr * If(a.PoN='-',-1,1)) as debe ,sum(b.haber * If(a.PoN='-',-1,1)) + sum((b.haber_cierr) * If(a.PoN='-',-1,1)) as haber,'".$sUsuario ."'

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "delete from temppersonalizados where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


$SqlStr = "insert into temppersonalizados(ubicacion,titulo,monto,usuario,subrayado)
select a.ubicacion,a.titulo,b.saldo_ant+b.debe-haber,'$sUsuario',a.subrayado
from balance_a a left join tempformato1 b
on a.numero = b.numeroRenglon  and b.usuario = '$sUsuario'
where a.formato = '$sFormato'
order by orden";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr.mysql_error());

}

function parentesis($valor){
           $valor = strval(str_replace(',','',$valor)); 
		 if ($valor < 0){
            return "(".number_format(strval($valor)*-1,2).")";		 
		 }else{
		   return number_format($valor,2);		 
		 }
}

function ultimo_dia($mes,$ano)
 {return strftime("%d", mktime(0, 0, 0, $mes+1, 0, $ano));}
 
 
 function crearDepreciacion($codigo) {
     $condicion = "";
	     $con = ConectarBD();
        $sTabla='deprecactivos';
        $sCondicion='';
        $sCampos.='Codigo';
        $sCampos.=',Tipo';
        $sCampos.=',Fecha';
        $sCampos.=',FechaDepre';
        $sCampos.=',Comprobante';
        $sCampos.=',CompAdquisicion';
        $sCampos.=',ValResidual';
        $sCampos.=',MesesDepre';
        $sCampos.=',ValDeprec';
        $sCampos.=',DepreMensual';
        $sCampos.=',Descripcion';
        $sCampos.=',Ubicacion';
        $sCampos.=',Proveedor';
        $sCampos.=',Observaciones';
        $sCampos.=',Nodeprec';
        $sCondicion.="Codigo= '".$codigo."'";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
        if ( NumeroFilas($exc)>0){
               $T_Codigo=trim(ObtenerResultado($exc,1));
                $T_Tipo=trim(ObtenerResultado($exc,2));
                $Fecha=ObtenerResultado($exc,3);
                $FechaDepre=ObtenerResultado($exc,4);
                $T_Comprobante=trim(ObtenerResultado($exc,5));
                $NCompAdquisicion=trim(ObtenerResultado($exc,6));
                $NValResidual=trim(ObtenerResultado($exc,7));
                $N_MesesDepre=trim(ObtenerResultado($exc,8));
				$NValDeprec=trim(ObtenerResultado($exc,9));
                $NDepreMensual=trim(ObtenerResultado($exc,10));
                $T_Descripcion=trim(ObtenerResultado($exc,11));
                $TUbicacion=trim(ObtenerResultado($exc,12));
                $TProveedor=trim(ObtenerResultado($exc,13));
                $TObservaciones=trim(ObtenerResultado($exc,14));
                $TNodeprec=trim(ObtenerResultado($exc,15));
				$fechainicio1 = explode('-', date('d-m-Y',strtotime($FechaDepre)));
				$dia = $fechainicio1[0];
				$mes = $fechainicio1[1];
				$ano = $fechainicio1[2];
				$fechafin = date("Y-m-d", mktime(0, 0, 0, $mes + $N_MesesDepre, $dia, $ano));
	     }
		 
    $fechaInicio = split("-", $FechaDepre);
	$diaInicio = intval($fechaInicio[2]);
    $mesInicio = intval($fechaInicio[1]);
    $anoInicio = $fechaInicio[0];
    $anoMesInicio = $fechaInicio[0] . $fechaInicio[1];

    $fechaFin = split("-", $fechafin);
    $diaFin = intval($fechaFin[2]);
    $mesFin = intval($fechaFin[1]);
    $anoFin = $fechaFin[0];
    $anoMesFin = $fechaFin[0] . $fechaFin[1];

    $depreciacionDiaria = $NDepreMensual / 30;
    $depreciacionAcumulada = 0;
    $valorLibros = $NValDeprec;
if($diaInicio >30){
$diaInicio = 30; 
}				   
    for ($anoCiclo = $anoInicio; $anoCiclo <= $anoFin; $anoCiclo++) {
        for ($mesCiclo = 1; $mesCiclo <= 12; $mesCiclo++) {
            $anoMesCiclo = $anoCiclo . str_pad($mesCiclo, 2, '0', STR_PAD_LEFT);
            if ($anoMesCiclo >= $anoMesInicio && $anoMesCiclo <= $anoMesFin) {
                if ($mesInicio == $mesCiclo && $anoInicio == $anoCiclo) {
                    $diasDepreciacion = (30 - $diaInicio);
                    $montoDepreciacion = bcmul($diasDepreciacion, $depreciacionDiaria, 2);
                    $depreciacionAcumulada = $montoDepreciacion;
                    $valorLibros = bcsub($valorLibros, $montoDepreciacion, 2);
                } else if ($mesFin == $mesCiclo && $anoFin == $anoCiclo) {
                    $diasDepreciacion = (30 - (30 - $diaInicio));
                    $montoDepreciacion = $valorLibros;
                    $depreciacionAcumulada = bcadd($depreciacionAcumulada, $montoDepreciacion, 2);
                    $valorLibros = bcsub($valorLibros, $montoDepreciacion, 2);
                } else if ($anoMesCiclo > $anoMesInicio && $anoMesCiclo < $anoMesFin) {
                    $diasDepreciacion = 30;
                    $montoDepreciacion = $NDepreMensual;
                    $depreciacionAcumulada = bcadd($depreciacionAcumulada, $montoDepreciacion, 2);
                    $valorLibros = bcsub($valorLibros, $montoDepreciacion, 2);
                }
                   
					$campos = '';
					$valores = '';
					$campos.="codigoactivos";
					$campos.=",anomes";
					$campos.=",diasdepreciacion";
					$campos.=",valordepreciado";
					$campos.=",depreciacionacumulada";
					$campos.=",valorlibro";
					$valores.="'". $codigo ."'";
					$valores.=",".$anoMesCiclo;
					$valores.=",".$diasDepreciacion;
					$valores.=",".$montoDepreciacion;
					$valores.=",".$depreciacionAcumulada;
					$valores.=",".$valorLibros;
					$query = 'INSERT INTO con_depreciacion (' . $campos . ')  values (' . $valores . ')';
					$exc = EjecutarExec($con,$query) or die($query); 
				
            }
        }
    } 
} 

  function eliminarDepreciacion($codigo){
     $con = ConectarBD();
$query = "delete from con_depreciacion where codigoactivos ='$codigo'";
$exc = EjecutarExec($con,$query) or die($query);
}  
  
function operacionAritmetica($cadena,$fechamesano,$fechaproceso,$mescierre,$icierre=0,$tipo=1,$arrayPar){
    $operaciones = array('+', '-', '*', '/', '(', ')');
    $operacionesRemplazo = array(',', ',', ',', ',', '', '');
    $cadenaCodigoCuenta= str_replace($operaciones, $operacionesRemplazo, trim($cadena));
    $arrayCodigoCuenta = split(",", $cadenaCodigoCuenta);
    $cantCodigoCuenta = count($arrayCodigoCuenta);



    for ($i = 0; $i < $cantCodigoCuenta; $i++) {
        $arraySaldosCuenta[$arrayCodigoCuenta[$i]] = buscarSaldosContables($arrayCodigoCuenta[$i],$fechamesano,$fechaproceso,$mescierre,$icierre=0,$tipo,$arrayPar);
		//echo $arrayCodigoCuenta[$i] . " = " .$arraySaldosCuenta[$arrayCodigoCuenta[$i]] ."<br>";
		
    }

$arraySaldosCuenta = str_replace(".", ",",$arraySaldosCuenta);
$cadenaSaldos = str_replace($arrayCodigoCuenta, $arraySaldosCuenta, trim($cadena));

   /* echo "cadena original: " . $cadena . "<br>";
    echo "cadena cuentas: " . $cadenaCodigoCuenta . "<br>";
    echo "cadena final: " . $cadenaSaldos . "<br>";*/

 $cadenaSaldos = str_replace(",", ".",$cadenaSaldos);
    $formula = '$resultados = ' . $cadenaSaldos . ';'; 
//	echo $formula;
    eval($formula);
    return $resultados;
}

function buscarSaldosContables($codigo,$fechamesano,$fechaproceso,$mescierre,$icierre=0,$tipo,$arrayPar) {
		
        $sUsuario = $_SESSION["UsuarioSistema"];
        $con = ConectarBD();
		$dfec_proceso =$fechaproceso;
		$AnoMesProceso = obFecha($dfec_proceso,'A').obFecha($dfec_proceso,'M');
		$AnoMesDesde = substr($fechamesano,2,4).substr($fechamesano,0,2);
		$Fecha_ano=substr($fechamesano,2,4);
		$Fecha_mes=substr($fechamesano,0,2);
		if ($AnoMesDesde < $AnoMesProceso){
			 $Mesword = MesLetras(strval($Fecha_mes));	 
				 $Mes_d = substr($Mesword,0,3)."_d";
				 $Mes_h = substr($Mesword,0,3)."_h";
				 $MesCierr_d = substr("diciembre",0,3)."_cierrd";//cambiado ernesto
				 $MesCierr_h = substr("diciembre",0,3)."_cierrh";//cambiado ernesto
				 if($tipo==1){// mensual
						if (strval($Fecha_mes) == $mescierre){
								if($icierre == 0){
									$SqlStr=" select $Mes_d-$Mes_h from cnt0000 where fecha_year = $Fecha_ano";
									$SqlStr.= " and codigo = '$codigo'";
								}else{
									$SqlStr=" select ($Mes_d+$MesCierr_d)-($Mes_h,$MesCierr_h) 
									from cnt0000 where fecha_year = $Fecha_ano";
									$SqlStr.= " and codigo = '$codigo'";
								}			
						}else{
									$SqlStr=" select $Mes_d-$Mes_h from cnt0000 where fecha_year = $Fecha_ano";
									$SqlStr.= " and codigo = '$codigo'";
						}
				}elseif($tipo==2){// acumulado		 
						/* if (strval($Fecha_mes) == $mescierre){
								if($icierre == 0){
									$SqlStr=" select $Mesword+$Mes_d-$Mes_h from cnt0000 where fecha_year = $Fecha_ano";
									$SqlStr.= " and codigo = '$codigo'";
								}else{
									$SqlStr=" select $Mesword+($Mes_d+$MesCierr_d)-($Mes_h,$MesCierr_h) 
									from cnt0000 where fecha_year = $Fecha_ano";
									$SqlStr.= " and codigo = '$codigo'";
								}			
						}else{
									$SqlStr=" select $Mesword+$Mes_d-$Mes_h from cnt0000 where fecha_year = $Fecha_ano";
									$SqlStr.= " and codigo = '$codigo'";
						} */
						   $saldo = 0;
						   foreach ($arrayPar as $arr) {
								if($arr[0]==$codigo){
								   $saldo = $arr[1];
								}
							}
				}
		}
		
		
		
		if ($AnoMesDesde == $AnoMesProceso){
			if($tipo==1){// mensual
				$SqlStr=" select sum(debe)-sum(haber) from movimien where ";
			    $SqlStr.= " substring(codigo,1,length(ltrim(rtrim('$codigo')))) = '$codigo'";
			}elseif($tipo==2){// acumulado	
					   $saldo = 0;
					   foreach ($arrayPar as $arr) {
								if($arr[0]==$codigo){
								   $saldo = $arr[1];
								}
							}
			}	
		}			
		
		
		if ($AnoMesDesde > $AnoMesProceso){
			if($tipo==1){// mensual	
				$SqlStr=" select sum(debe)-sum(haber) from movimiendif where ";
			    $SqlStr.= " substring(codigo,1,length(ltrim(rtrim('$codigo')))) = '$codigo'";
				$SqlStr.= " and year(fecha) = $Fecha_ano and month(fecha) = ".strval($Fecha_mes) ;
			}elseif($tipo==2){// acumulado	
				 $saldo = 0;
					foreach ($arrayPar as $arr) {
						if($arr[0]==$codigo){
						   $saldo = $arr[1];
						}
					}
			}	
		}
			
		    
			if($tipo==1){	
				$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
				$saldo = 0;
				if (NumeroFilas($exc)>0){
		            $saldo=ObtenerResultado($exc,1,0);
				}	
			}			
		
    return abs($saldo);
}

function SaldoAnteriorPosterioresInd($FechaDesde,$sUsuario,$codigo){
$con = ConectarBD();
$sUsuario = $_SESSION["UsuarioSistema"];
$SqlStr = "delete from tempsuma where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from tempsuma1 where usuario = '$sUsuario'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "
insert into tempsuma1
select a.codigo,sum(b.debe - b.haber) as Monto,'$sUsuario'
from cuenta a,movimien b where a.codigo = substring(b.codigo,1,length(rtrim(a.codigo)))
and substring(b.codigo,1,length(ltrim(rtrim('$codigo')))) = '$codigo'
union all
select a.codigo,sum(b.debe - b.haber),'$sUsuario'
 from cuenta a,movimiendif b 
where a.codigo = substring(b.codigo,1,length(rtrim(a.codigo))) and Fecha < '$FechaDesde' 
 and substring(b.codigo,1,length(ltrim(rtrim('$codigo')))) = '$codigo'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = " insert into tempsuma
select a.codigo,sum(a.Monto) as Monto,'$sUsuario' 
from tempsuma1 a where usuario = '$sUsuario' group by a.codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "
update cuentageneral a,tempsuma b
 set a.saldo_ant = a.saldo_ant + b.Monto
 where a.codigo = b.codigo and a.usuario = '$sUsuario' and b.usuario = '$sUsuario'
 a.codigo = '$codigo'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
}



/* echo "resultado: ".operacionAritmetica("(112+113-114)/(115*116)");
echo "<br>******************************************<br>";
echo "resultado: ".operacionArismetica("(112+113+114+115+116)*116-112"); */
?>
 
 
 

