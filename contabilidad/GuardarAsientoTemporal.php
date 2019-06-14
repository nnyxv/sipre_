<?php
include("FuncionesPHP.php");
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
	$txtTipo = $_REQUEST["txtTipo"];
	$con = ConectarBD();
	$sCampos="COUNT(*)";
	$SqlStr="Select ".$sCampos. " from movimientemp";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if (NumeroFilas($exc)>0){
	    $NumEle =ObtenerResultado($exc,1) + 1;
	}
	
	
	$sCampos="consemensualdiario";
	$SqlStr="Select ".$sCampos. " from parametros";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	$MensualDiario="D";
	if (NumeroFilas($exc)>0){
	    $MensualDiario=ObtenerResultado($exc,1);
	}
	if ($MensualDiario == "D"){ 
		$sCondicion = " ano='".$xAFecha."'";
		$sCondicion.=" and mes ='".$xMFecha."'";
		$sCondicion.=" and dia ='".$xDFecha."'";
		$sCondicion.=" and cc ='ASCIERRE'";
	}else{
		$sCondicion = " ano='".$xAFecha."'";
		$sCondicion.=" and mes ='".$xMFecha."'";
    }
  	$con = ConectarBD();
	/*$sCampos="consecutivo";
	$SqlStr="Select ".$sCampos. " from conse Where ". $sCondicion;
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
    $iNroConseComprobant = 0;
	if (NumeroFilas($exc)>0){
     	     $iNroConseComprobant = ObtenerResultado($exc,1) + 1;
			 $SqlStr="update conse set consecutivo = $iNroConseComprobant where". $sCondicion;
			 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		}else{ 
    	     $iNroConseComprobant = 1;
			 $SqlStr="insert into conse(ano,mes,dia,consecutivo,cc)
			 value('$xAFecha','$xMFecha','$xDFecha',$iNroConseComprobant,'ASCIERRE')";
			 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
    }*/
	$iNroConseComprobant = 999;
  	//**********************************************************************
  	/*Fin C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************

$sTabla = "enc_diario";
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
  	
  	$sValores.="'".$iNroConseComprobant."'";
  	$sValores.=",'".RobFecha($Fecha)."'";
  	$sValores.=",'0'";
  	$sValores.=",'" . $_SESSION['SisNombreUsuario'] . "'";
  	$sValores.=",'". date("g:i:s A") ."'"; 
  	$sValores.=",'". date("d/m/Y") ."'"; 
  	$sValores.=",''"; 
  	$sValores.=",''"; 
  	$sValores.=",''"; 
  	$sValores.=",'ASIENTO DE CIERRE'";   	  	
  	$sValores.=",''";     	
  	$sValores.=",0";    
  	$sValores.=",'CON'";    
  	$sValores.=",". strval($NumEle-1);    
	$sValores.=",'ASCIERRE'";   	  	
	
  	$SqlStr="";
  	$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
  	

$SqlStr = " select codigo
,descripcion
,debe
,haber
,numero
,DT
,CT
,documento
,fecha
,im
from movimientemp where debe <>  0 or haber <> 0";
$i = 0;
$exec =  EjecutarExec($con,$SqlStr) or die($SqlStr); 
	 if (NumeroFilas($exec)>0){
     		$iFila = -1;
			$orden=0;  
            while ($row = ObtenerFetch($exec)) {
     			$orden=$orden+10;  
     			$i++;
            	$iFila++;
    			$codigo = trim(ObtenerResultado($exec,1,$iFila)) ; 
				$descrip = trim(ObtenerResultado($exec,2,$iFila));				
				$debe = ObtenerResultado($exec,3,$iFila);				
				$haber = ObtenerResultado($exec,4,$iFila);				
				$DT = trim(ObtenerResultado($exec,6,$iFila));				
				$CT = trim(ObtenerResultado($exec,7,$iFila));				
				$documento = trim(ObtenerResultado($exec,8,$iFila));		
				$fecha = trim(ObtenerResultado($exec,9,$iFila));				
				$im = trim(ObtenerResultado($exec,10,$iFila));				
$sTabla="movimien";
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
		$sValores.=",'".$sFecha."'";
        $sValores.=",".strval($i); //numero
  		$sValores.=",'$codigo'"; //codigo 
  		$sValores.=",'$descrip'"; //descripcion  
  		$sValores.=",$debe"; // debe
  		$sValores.=",$haber"; // Haber 
  		$sValores.=",$documento"; // documento
  		$sValores.=",".$orden; // OrdenRen
		$sValores.=",'$CT'"; // CT
		$sValores.=",'$DT'"; // DT
		$sValores.=",'ASCIERRE'"; // cc
		$sValores.=",'ASCIERRE'"; // im
  	$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	}
}
	$SqlStr="update parametros set comp_cierr = '$iNroConseComprobant',fechacomp_cierr = '$sFecha'";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	MJ("Se ha Generado el Comprobante de cierre en diarios con el Numero $iNroConseComprobant y Fecha $Fecha se reiniciara el sistema");
?>
<script language="Javascript">
  parent.EjePrincipal();
</script>


