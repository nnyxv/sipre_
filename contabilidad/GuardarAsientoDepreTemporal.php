<?php
include("FuncionesPHP.php");
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
$txtTipo = $_REQUEST["txtTipo"];
//se agrego para que tome en cuenta la fecha:
$fec = $_REQUEST["txtF"];

$con = ConectarBD();

//VALIDAR SI EXISTEN ACTIVOS A DEPRECIAR, Y SI TIENEN DATOS INCOMPLETOS
$strSql = "SELECT * FROM deprecactivos WHERE estatus = 1";
$exec =  EjecutarExec($con,$strSql) or die($strSql); 

if (NumeroFilas($exec)>0){
	echo "<script type='text/javascript'>		
			alert('Existen activos con datos incompletos');		
			location.href='frmdeprecactivos.php';
	  </script>";

	return;
}
//*********************************************************************

/*	$query = sprintf("SELECT * FROM enc_dif WHERE comprobant = %s Fecha =  ", 
		$numcomprobant = 9999,
		date($_POST['Fecha'],'Y-m-d'));
	$rs = mysql_query($query,$con);
	$num = mysql_num_rows($rs);
	echo $num;
		if(!$num){
			echo "<script>alert('existe');</script>";	
		}*/
	
$sCampos="COUNT(*)";
$SqlStr="SELECT ".$sCampos. " FROM movimientemp";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if (NumeroFilas($exc)>0){
$NumEle =ObtenerResultado($exc,1) + 1;
}

$SqlStr='SELECT fec_proceso FROM parametros ';
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if(NumeroFilas($exc) > 0){
$fproceso = trim(ObtenerResultado($exc,1));
}
$dDesde1  = $fproceso;
$anomes = obFecha($fproceso,"A").obFecha($fproceso,"M");

$fechaviene = $_REQUEST['Fecha'];

$anomesviene = RobFecha($fproceso,"A").RobFecha($fproceso,"M");

$SqlStr="SELECT consemensualdiario FROM parametros";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$MensualDiario = "D";

if (NumeroFilas($exc)>0){
$MensualDiario=ObtenerResultado($exc,1);
}

if ($MensualDiario == "D"){ 
	$sCondicion = " ano ='".$xAFecha."'";
	$sCondicion.=" AND mes ='".$xMFecha."'";
	$sCondicion.=" AND dia ='".$xDFecha."'";
	$sCondicion.=" AND cc ='ASCIERRE'";
}else{
	$sCondicion = " ano ='".$xAFecha."'";
	$sCondicion.=" AND mes ='".$xMFecha."'";
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
$iNroConseComprobant = 9999;
//**********************************************************************
/*Fin C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
//**********************************************************************

if($anomes == $anomesviene){ 
	$sTabla = "enc_diario";
}else{
	$sTabla = "enc_dif";
}

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
$sValores.=",'".$fec."'";
$sValores.=",'0'";
$sValores.=",'" . $_SESSION['SisNombreUsuario'] . "'";
$sValores.=",'". date("g:i:s A") ."'"; 
$sValores.=",'". date("Y-m-d H:i:s") ."'"; 
$sValores.=",''"; 
$sValores.=",''"; 
$sValores.=",''"; 
$sValores.=",'ASIENTO DE DEPRECIACION'";   	  	
$sValores.=",''";     	
$sValores.=",0";    
$sValores.=",'CON'";    
$sValores.=",". strval($NumEle-1);    
$sValores.=",'DEPRE'";   	  	

$SqlStr="";
$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  VALUES (".$sValores.")";
//$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
$exc = mysql_query($SqlStr,$con);
			
if(!$exc){
	echo "<script>
			alert('Ya existe un asiento con esta fecha de proceso');
				window.location='frmPantallaAsientodeDepreciacion.php';
			</script>";
			
	return;
}

//auditoria
auditoria('insert',$sTabla,$sCampos,'insert encabezado depreciacion, fecha: '.$Fecha);
//fin auditoria
		
$SqlStr = "SELECT codigo,descripcion,debe,haber,numero,DT,CT,documento,fecha,im 
				FROM movimientemp 
			WHERE debe <>  0 OR haber <> 0";
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
									
		if($anomes == $anomesviene){ 
			$sTabla="movimien";
		}else{
			$sTabla="movimiendif";
		}
		
		$sCampos="";
		$sCampos.="comprobant";  
		$sCampos.=",fecha";       
		$sCampos.=",numero";      
		$sCampos.=",codigo";      
		$sCampos.=",descripcion"; 
		$sCampos.=",debe";        
		$sCampos.=",haber";       
		$sCampos.=",documento"; 
		$sCampos.=",generado"; 												 
		$sCampos.=",OrdenRen"; 						 
		$sCampos.=",dt"; 
		$sCampos.=",ct";
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
		$sValores.=",'$documento'"; // documento
		$sValores.=",''";
		$sValores.=",".$orden; // OrdenRen
		$sValores.=",'$DT'"; // DT
		$sValores.=",'$CT'"; // CT
		$sValores.=",'DEPRE'"; // cc
		$sValores.=",'00'"; // im
		
		$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  VALUES (".$sValores.")";
		$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
		
		//auditoria
		auditoria('insert',$sTabla,$sCampos,'insert detalle depreciacion, fecha: '.$Fecha);
	//fin auditoria		
		
		//$exc = mysql_query($SqlStr,$con);
		if(!$exc){
			echo "<script>
					alert('Ya existe un asiento con esta fecha de proceso');
						window.location='frmPantallaAsientodeDepreciacion.php';
					</script>";
					
			return;
		}
	}
}
	//MJ("Se ha Generado el Comprobante de depreciacion $iNroConseComprobant y Fecha $Fecha");
	
	echo "<script type='text/javascript'>
			alert('Se ha Generado el Comprobante de depreciacion $iNroConseComprobant y Fecha $Fecha');
						location.href='frmPantallaAsientodeDepreciacion.php';
			</script>"
?>