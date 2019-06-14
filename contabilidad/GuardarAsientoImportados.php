<?php
include("FuncionesPHP.php");
//include("GenerarEnviarContabilidad.php");
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
$con = ConectarBD();
$sCampos="COUNT(*)";
$SqlStr="Select ".$sCampos. " from movenviarcontabilidad";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$NumEle =ObtenerResultado($exc,1) + 1;
}

$sCampos="codigo,descripcion";
$SqlStr="Select ".$sCampos. " from transacciones where codigo = '$idct'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$desTrans =ObtenerResultado($exc,2);
}

$sCampos="codigo,descripcion";
$SqlStr="Select ".$sCampos. " from centrocosto where codigo = '$idcc'";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if (NumeroFilas($exc)>0){
	$desCentro =ObtenerResultado($exc,2);
}
		
	 /*$SqlStr="select a.codigo,b.descripcion,a.desripcion,a.debe,a.haber,a.documento from movenviarcontabilidad a
			left join cuenta b on a.codigo = b.codigo
			where fecha = '$idDia'
			and a.ct = '$idct'
			and a.cc = '$idcc'
			order by comprobant,documento,a.tipo ";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);*/
	
$sCampos="consemensualdiario,fec_proceso";
$SqlStr="Select ".$sCampos. " from parametros";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$MensualDiario="D";
if (NumeroFilas($exc)>0){
	$MensualDiario=ObtenerResultado($exc,1);
	$fec_proseso=ObtenerResultado($exc,2);
}
$xAFecha = date("Y",strtotime($idDia));
$xMFecha =  date("m",strtotime($idDia));
$xmes =  substr($xMFecha,1,1);
$xDFecha = date("d",strtotime($idDia));

/*	if ($MensualDiario == "D"){ 
		$sCondicion = " ano='".$xAFecha."'";
		$sCondicion.=" and mes ='".$xMFecha."'";
		$sCondicion.=" and dia ='".$xDFecha."'";
		$sCondicion.=" and cc = '$idcc'";
	}else{
		$sCondicion = " ano='".$xAFecha."'";
		$sCondicion.=" and mes ='".$xMFecha."'";
    }*/

$xAFechaPro = date("Y",strtotime($fec_proseso));
$xMFechaPro =  date("m",strtotime($fec_proseso));
$xmes =  $xMFecha;
$xDFechaPro = date("d",strtotime($fec_proseso));

$Anomes=$xAFecha.$xMFecha; 
$AnomesPro=$xAFechaPro.$xMFechaPro; 
	
	
if ($Anomes > $AnomesPro){
     $sTabla = "enc_dif";
	 $sTablaM="movimiendif";
	 $desTabla = "Posteriores";
}else{
	$sTabla = "enc_diario";
	 $sTablaM="movimien";
	$desTabla = "Diario";
}

$SqlStr = "SELECT MAX(comprobant) FROM $sTabla
	WHERE MONTH(fecha) = ".strval($xmes)." AND YEAR(fecha) = $xAFecha";
	
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if (is_null(ObtenerResultado($exc,1))){
	$iNroConseComprobant = 1;
}else{
   $iNroConseComprobant = ObtenerResultado($exc,1) + 1;
}

  	//**********************************************************************
  	/*Fin C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
$SqlStr="select comprobant from enviadosacontabilidad 
where fecha = '$idDia' and cc = '$idcc' and ct = '$idct'";  
$exc4 = EjecutarExec($con,$SqlStr) or die($SqlStr);
if (NumeroFilas($exc4)>0){
	$comp=ObtenerResultado($exc4,1);
	if(trim($hdn_adelante) == 0){
		$comp=ObtenerResultado($exc4,1);
		 $SqlStr="delete from movenviarcontabilidad 
				where fecha = '$idDia'
				and ct = '$idct'
				and cc = '$idcc'";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr); 
		return;		
	}				
}		

/* //buscar por los movimiento que concatenan				
$SqlStr = " select documento
from movenviarcontabilidad where (debe <>  0 or haber <> 0)";
$SqlStr.= " and fecha = '$idDia' and ct = '$idct'	and cc = '$idcc' order by comprobant,documento";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
	 if (NumeroFilas($exec)>0){
     		$iFila = -1;
			$orden=0; 
			$DocAnt = "";
            while ($row = ObtenerFetch($exec)) {
     			$orden=$orden+10;  
     			$i++;
            	$iFila++;
    			$documento = trim(ObtenerResultado($exec,1,$iFila)) ; 
			    $SqlStr="select comprobant from enviadosacontabilidad 
				where fecha = '$idDia' and cc = '$idcc' and ct = '$idct' and documento = '$documento'";  
				$exc8 = EjecutarExec($con,$SqlStr) or die($SqlStr);
				if (NumeroFilas($exc8)>0){
						$comp=ObtenerResultado($exc8,1);
					     //  MJ("Ya se ha generado los movimientos en el comprobante Nro :$comp");
						//return;
							echo "<script language='javascript'>           
						       var Pregunta = 0;
								Pregunta = confirm('Ya se ha generado los movimientos en el comprobante Nro :$comp');
							</script>";
						$variablephp = "<script> document.write(Pregunta) </script>";
							if (!$variablephp){
								return;		
							}	 
				}
				
			}
    } */
			
$i = 0;
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
$sValores="";
$sCampos="";
$sCampos.="comprobant";
$sCampos.=",fecha";
$sCampos.=",actualiza";
$sCampos.=",Usuario_i";
$sCampos.=",Hora_i";
$sCampos.=",Fecha_i";
$sCampos.=",Usuario_m";
$sCampos.=",Hora_m";
$sCampos.=",Fecha_m";
$sCampos.=",Concepto";
$sCampos.=",Tipo";
$sCampos.=",Soporte";
$sCampos.=",ModuloOrigen";
$sCampos.=",NumeroRenglones";
$sCampos.=",CC";	
$descom = $desTrans ."  ".$desCentro." DIA ".date('d-m-Y',strtotime($idDia)) ;
$sValores.="'".$iNroConseComprobant."'";
$sValores.=",'$idDia'";
$sValores.=",'0'";
$sValores.=",'" . $_SESSION['SisNombreUsuario'] . "'";
$sValores.=",'". date("g:i:s A") ."'"; 
$sValores.=",'". date("d/m/Y") ."'"; 
$sValores.=",''"; 
$sValores.=",''"; 
$sValores.=",''"; 
$sValores.=",'$descom'";   	  	
$sValores.=",''";     	
$sValores.=",0";    
$sValores.=",'AD'";    
$sValores.=",". strval($NumEle-1);    
$sValores.=",'$idcc'";  

// -- INICIO DE LA TRANSACCION PARA EVITAR PERDIDA EN LA INFORMACION INSERTADA --///

EjecutarExec($con,"START TRANSACTION");

// -------------------------------------------------------------- L/A 19/01/2016


$SqlStr="";
$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
  
//auditoria
//auditoria('insert',$sTabla,$sCampos,'envio a contabilidad');	
//fin auditoria

$SqlStr = "select codigo
		  ,desripcion
		  ,debe
		  ,haber
		  ,''
		  ,DT
		  ,CT
		  ,documento
		  ,fecha
		  ,cc
		  ,im
		  ,idobject
		  from movenviarcontabilidad where (debe <>  0 or haber <> 0)";
$SqlStr.= " and fecha = '$idDia' and ct = '$idct'	and cc = '$idcc' order by comprobant,documento";

$i = 0;
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
if (NumeroFilas($exec)>0){
	$iFila = -1;
	$orden=0; 
	$DocAnt = "";
	$idAnt = "";
	while ($row = ObtenerFetch($exec)) {
		$orden=$orden+10;  
		$i++;
		$iFila++;
		$codigo = trim(ObtenerResultado($exec,1,$iFila)) ; 
		$descrip = trim(ObtenerResultado($exec,2,$iFila));	
		$descrip = addslashes($descrip);			
		$debe = ObtenerResultado($exec,3,$iFila);				
		$haber = ObtenerResultado($exec,4,$iFila);				
		$DT = trim(ObtenerResultado($exec,6,$iFila));				
		$CT = trim(ObtenerResultado($exec,7,$iFila));				
		$documento = trim(ObtenerResultado($exec,8,$iFila));		
		$fecha = trim(ObtenerResultado($exec,9,$iFila));				
		$cc = trim(ObtenerResultado($exec,10,$iFila));
		$im = trim(ObtenerResultado($exec,11,$iFila));	
		$idobject =trim(ObtenerResultado($exec,12,$iFila));	
/* 			    $SqlStr="select comprobant from enviadosacontabilidad 
				where fecha = '$idDia' and cc = '$idcc' and ct = '$idct' and documento = '$documento'";  
				$exc18 = EjecutarExec($con,$SqlStr) or die($SqlStr);
				if (NumeroFilas($exc18)==0){  */
				
				
		  if (($DocAnt != $documento)or($idAnt!=$idobject)){
			$DocAnt= $documento;
			$idAnt= $idobject;
			$SqlStr ="insert into enviadosacontabilidad (comprobant 
			,fecha   
			,documento 
			,dt       
			,ct       
			,cc       
			,im       
			,idobject) values (
			'$iNroConseComprobant'
			,'$fecha'
			,'$documento'
			,'$DT'
			,'$CT'
			,'$cc'
			,'$im'
			,$idobject
			)";  
			$exec6 =  EjecutarExec($con,$SqlStr) or die($SqlStr); 						
		}

// -------- CONDICION PARA VERIFICAR QUE NO EXISTE ERROR EN EL INSERT ANTERIOR A LA TABLA ENVIADOSACONTABILIDAD
//          SI EL INSERT FALLA EL ROOLBACK SE ACTIVA Y ANULARA TODA LA PETICION 			

		if ($exec6 === False) {			
			mysql_query("ROLLBACK");
			echo "<script type=\"text/javascript\">
						alert(\"Error al Guardar "+$SqlStr+"\");
				</script>";
		}
		
		//auditoria
		//auditoria('insert','enviadosacontabilidad',$sCampos,'envio a contabilidad documento/id '.$documento."/".$idobject);	
		//fin auditoria

		
// -------------------------------------------------------------- L/A 19/01/2016
				

		$sCampos="";
		$sCampos.="comprobant";  
		$sCampos.=",fecha";       
		$sCampos.=",numero";      
		$sCampos.=",codigo";      
		$sCampos.=",descripcion"; 
		$sCampos.=",debe";        
		$sCampos.=",haber";       
		$sCampos.=",documento";   
		$sCampos.=",OrdenRen"; 
		$sCampos.=",ct"; 
		$sCampos.=",dt"; 
		$sCampos.=",cc"; 
		$sCampos.=",im"; 
		$sFecha = RobFecha($Fecha); 
	    $sValores="";
	    $sValores.="'".$iNroConseComprobant."'";
		$sValores.=",'".$fecha."'";
        $sValores.=",".strval($i); //numero
  		$sValores.=",'$codigo'"; //codigo 
  		$sValores.=",'$descrip'"; //descripcion  
  		$sValores.=",$debe"; // debe
  		$sValores.=",$haber"; // Haber 
  		$sValores.=",'$documento'"; // documento
  		$sValores.=",".$orden; // OrdenRen
		$sValores.=",'$CT'"; // CT
		$sValores.=",'$DT'"; // DT
		$sValores.=",'$im'"; // cc
		$sValores.=",'$im'"; // im
  		$SqlStr="INSERT INTO ".$sTablaM." (".$sCampos.")  values (".$sValores.")";
		
  		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
		//auditoria
		//auditoria('insert',$sTablaM,$sCampos,'envio a contabilidad documento '.$documento);	
		//fin auditoria

	}
	//}
	
	//auditoria
	auditoria('inserts','enviadosacontabilidad/'.$sTabla.'/'.$sTablaM,$sCampos,'envio a contabilidad');	
	//fin auditoria

// ------- EN CASO DE QUE LLEGUE A ESTE PUNTO ES QUE LA TRANSACCION SE EJECUTO SIN PROBLEMAS Y SE GUARDA TODOS LOS CAMBIOS

	mysql_query("COMMIT");

// -------------------------------------------------------------- L/A 19/01/2016
	
}

$SqlStr="delete from movenviarcontabilidad 
			where fecha = '$idDia'
			and ct = '$idct'
			and cc = '$idcc'";
		
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

//auditoria
auditoria('delete','movenviarcontabilidad',$sCampos,'delete de la tabla movenviaracontabilidad luego del envio a contabilidad');	
//fin auditoria
		
	MJ("Se ha Generado el Comprobante con el Numero $iNroConseComprobant de  Fecha: ".date("d-m-Y",strtotime($idDia)));
	
function ingresarEnviados($cuentacontable,$descripcion,$Debe,$Haber,$documento
						,$ct,$dt,$cc,$comprobant,$fecha,$tipo,$im='',$idobject){
$con = ConectarBD();
$SqlStr = "insert into movenviarcontabilidad( 		 
	  codigo,
	  desripcion,
	  debe,
	  haber,
	  documento,
	  ct,
	  dt,
	  cc,
	  comprobant,
	  fecha,
	  tipo,
	  im,
	  idobject
  )
	values 
	('$cuentacontable'
	,'$descripcion'
	,$Debe
	,$Haber
	,'$documento'
	,'$ct'
	,'$dt'
	,'$cc'
	,$comprobant
	,'$fecha'
	,'$tipo'
	,'$im'
	,$idobject
   )	 
";
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
}	

function hacerTerceros($cc,$ct,$fecha){
$con = ConectarBD();
$sCampos="cc,ct,funcion";
	$SqlStr="SELECT ".$sCampos. " FROM funcionterceros where cc='$cc' AND ct '$ct'";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);	
	   while ($row = ObtenerFetch($exec)){
			$cc = trim(ObtenerResultado($exec,1,$iFila)); 
			$ct = trim(ObtenerResultado($exec,2,$iFila));				
			$funcion = trim(ObtenerResultado($exec,3,$iFila));
			$cadena = $funcion."(0,'$fecha','$fecha')"; 
			//echo $cadena;
			//eval($cadena);
		}
}

?>
<script language="Javascript">
  parent.LimpiaDetalle();
</script>