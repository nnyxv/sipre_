<?php session_start(); 
include_once('FuncionesPHP.php');
$con = ConectarBD();
$SqlStr = "select fec_proceso from parametros";

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr); 

if (NumeroFilas($exc)>0){
	$dProceso = trim(ObtenerResultado($exc,1));
}

if ($StatusOculto =="IN" || $StatusOculto =="UP"){
	$oDiferencia = $_REQUEST["oDiferencia"]; 
	
	//prueba
	if ($StatusOculto =="IN"){
		if($oTablaSelec == 'D'){
			auditoria('insert','enc_diario/movimien',$sCampos,'se crea nuevo comprobante');
		}elseif ($oTablaSelec == 'P'){
			auditoria('insert','enc_dif/movimiendif',$sCampos,'se crea nuevo comprobante ');
		}
	}elseif($StatusOculto =="UP"){
		if($oTablaSelec == 'D'){
			auditoria('update','enc_diario/movimien',$sCampos,'update en comprobante'.$oComprobante);
		}elseif ($oTablaSelec == 'P'){
			auditoria('update','enc_dif/movimiendif',$sCampos,'update en comprobante '.$oComprobante);
		}
	}
	//fin prueba
	
	
	
	if ($oDiferencia != 0){
		MJ("EL COMPROBANTE ESTA DESCUADRADO");
	} 
}

if ($StatusOculto =="DE"){
	if($oTablaSelec == 'D'){
		auditoria('delete','enc_diario/movimien',$sCampos,'se elimina comprobante'.$oComprobante);
	}elseif ($oTablaSelec == 'P'){
		auditoria('delete','enc_dif/movimiendif',$sCampos,'se elimina comprobante '.$oComprobante);
	}
}


// INSERTAR 
if ($oTablaSelec == "I" && $StatusOculto =="IN"){ 
	if (obFecha($dProceso,'M') == $xMFecha){
		$TablaSelec = "enc_diario";
		$TablaSeleMov = "movimien";
		 $TipoComprobante = " Diario ";
	}
	if ($xMFecha > obFecha($dProceso,'M')){
		$TablaSelec = "enc_dif";
		$TablaSeleMov = "movimiendif";
		 $TipoComprobante = " Posteriores ";
	}
	if ( $xMFecha < obFecha($dProceso,'M')){
		 MJ('Imposible importar movimientos Historicos. El Comprobante que pretender importar tiene fecha de meses ya cerrados');
		 return;
	}
}

if ($oTablaSelec == "I" && $StatusOculto =="BU"){ 
	$TablaSelec = "enc_importados";
	$TablaSeleMov = "movimienimportados";
}

if ($oTablaSelec == 'D'){
	$TablaSelec = "enc_diario";
	$TablaSeleMov = "movimien";
} 		

if ($oTablaSelec == 'P'){ 
	$TablaSelec = "enc_dif";
	$TablaSeleMov = "movimiendif";
}			

if ($oTablaSelec == 'H'){ 
	$TablaSelec = "enc_historico";
}			
?>	
		
<?php
if ($_SESSION["CCSistema"] != ""){
	$oCC= $_SESSION["CCSistema"];
	$oDesCC = $_SESSION["DesCCSistema"];			
}
  //L I M P I A R
if ($StatusOculto =="LI"){
	$T_Codigo = "";
  	$T_Descripcion = "";
}
  //I N S E R T
if ($StatusOculto =="IN"){
  	if(VerificarAcceso($numeroMenu, 'I') == false){  
		MJ('Usted no esta autorizado para introducir registros');
	  	return;
	}
  	$Arreglo = array_recibe($arrdetalle);
 	$NumEle = count($Arreglo);
  
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
	$con = ConectarBD();
	$SqlStr = "start transaction;";
	$exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);

  	/*$SqlStr = "SELECT MAX(comprobant) FROM $TablaSelec 
		WHERE DAY(fecha) = $xDFecha
		AND MONTH(fecha) = $xMFecha
		AND YEAR(fecha) = $xAFecha
		AND cc = '".$oCC."'";*/
		
	$SqlStr = "SELECT MAX(comprobant) FROM $TablaSelec 
				WHERE MONTH(fecha) = $xMFecha
				AND YEAR(fecha) = $xAFecha";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	
	if (is_null(ObtenerResultado($exc,1))){
	    $iNroConseComprobant = 1;
	}else{
	   $iNroConseComprobant = ObtenerResultado($exc,1) + 1;
	}
	/*$sCampos="consemensualdiario";
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
		$sCondicion.=" and cc ='".$oCC."'";
	}else{
		$sCondicion = " ano='".$xAFecha."'";
		$sCondicion.=" and mes ='".$xMFecha."'";
    }
  	$con = ConectarBD();
	$sCampos="consecutivo";
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
			 value('$xAFecha','$xMFecha','$xDFecha',$iNroConseComprobant,'$oCC')";
			 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			
    }*/
  	//**********************************************************************
  	/*Fin C&oacute;digo PHP Para Realizar el INSERT CON LA TABLA CONSE */
 	//**********************************************************************
	$sTabla = $TablaSelec;//tabla de los encabezados: enc_diario, enc_dif, enc_historico, enc_importados
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
  	$sValores.=",'".$xAFecha."-".$xMFecha."-".$xDFecha."'";
  	$sValores.=",'0'";
  	$sValores.=",'" . $_SESSION['SisNombreUsuario'] . "'";
  	$sValores.=",'". date("g:i:s A") ."'"; 
  	$sValores.=",'". date("Y/m/d") ."'"; 
  	$sValores.=",''"; 
  	$sValores.=",''"; 
  	$sValores.=",''"; 
  	$sValores.=",'". str_replace("\'","",str_replace('\"','',$oConcepto)) ."'";   	  	
  	$sValores.=",''";     	
  	$sValores.=",0";    
  	$sValores.=",'CON'";    
  	$sValores.=",". strval($NumEle-1);    
	$sValores.=",'". $oCC ."'";   	  	
	
  	$SqlStr="";
	$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
	
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
  	  		
	$sTabla=$TablaSeleMov;//tabla para el detalle: movimien, movimiendif, movimienimportados
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
	$sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 
	
	for($i = 0;$i <= $NumEle - 1; $i++){
        //echo $Arreglo[$i][5];		
		
		//prueba
		$cod = $Arreglo[$i][1];
		$sqlcta = "select descripcion from cuenta where codigo ='".$cod."'";						
		
		$exc = EjecutarExec($con,$sqlcta) or die($sqlcta);
		while ($row = ObtenerFetch($exc)){
			$desc = $row[0];			
		//fin
		
		//$sValores.=",'".$desc. "'"; //en caso de modificacion descripcion del movimiento (CT), pero en caso de insert debe ser la descripción de la cuenta....
		
		//

			if($Arreglo[$i][1] != ""){					
				$sValores="";
				$sValores.="'".$iNroConseComprobant."'";
				$sValores.=",'".$sFecha."'";
				$sValores.=",".strval($i); //numero
				$sValores.=",'".$Arreglo[$i][1]. "'"; //codigo 
				$sValores.=",'".str_replace("\'","",str_replace('\"','',$Arreglo[$i][2])). "'"; //en caso de modificacion descripcion del movimiento (CT), pero en caso de insert debe ser la descripción de la cuenta....
				$sValores.=",".strval(str_replace(',','',$Arreglo[$i][3])); // debe
				$sValores.=",".strval(str_replace(',','',$Arreglo[$i][4])); // Haber 
				$sValores.=",'".$Arreglo[$i][5]. "'"; // documento
				$sValores.=",".$Arreglo[$i][7]; // OrdenRen
				$sValores.=",'".$Arreglo[$i][9]. "'"; // CT
				$sValores.=",'".$Arreglo[$i][10]. "'"; // DT
				$sValores.=",'".$oCC. "'"; // cc
				$sValores.=",'".$Arreglo[$i][11]. "'"; // IM
				$SqlStr="";
				$Arreglo[$i][0] = strval($i);
				
				if($Arreglo[$i][8] != "NE" && $Arreglo[$i][8] != "E"){					
					$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";					
					
					$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);					
				} 
				$Arreglo[$i][8] = "";
			}
		}
	}
 
	if ($oTablaSelec != "I"){
		$NumEle= $NumEle-1;
		$arregloenv = array_envia($Arreglo);
		echo "<script language='javascript'> alert('Operación Realizada con exito')	</script>";
   		echo "<script language='javascript'>  parent.mainFrame.document.frmDiarios.oVerStatus.value= 'Estatus  (Modificación)'
        parent.mainFrame.document.frmDiarios.StatusOculto.value= 'UP'
		parent.mainFrame.document.frmDiarios.enespera.value= ''		   
		parent.mainFrame.document.frmDiarios.oNumeroRenglones.value='$NumEle'; 
		parent.mainFrame.document.frmDiarios.arrdetalle.value='$arregloenv'; 
		//parent.mainFrame.FrameDetalle.document.frmRenglonesDiarios.arrdetalle.value='$arregloenv';
		parent.mainFrame.document.frmDiarios.oComprobante.value='$iNroConseComprobant';
   		parent.mainFrame.document.frmDiarios.oTablaSelec.value = '$oTablaSelec';
		parent.mainFrame.document.frmDiarios.target='topFrame';
		parent.mainFrame.document.frmDiarios.method='post';
		parent.mainFrame.document.frmDiarios.action='OpSQLDiarios.php';
		parent.mainFrame.document.frmDiarios.StatusOculto.value = 'BU'
		parent.mainFrame.document.frmDiarios.submit();  
		</script>";
	}else{
    	MJ('Se ha Generado el comprobante de '. $TipoComprobante . '  Nro: ' .  $iNroConseComprobant  .' y de Fecha: '. obFecha($sFecha));
		$TablaSelec = "enc_importados";
		$TablaSeleMov = "movimienimportados";
        $StatusOculto ="DE";
  	}
    $SqlStr = "commit;";
	$exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr);

}
  
  //U P D A T E
if ($StatusOculto =="UP"){
	if(VerificarAcceso($numeroMenu, 'U') == false){  
		MJ('Usted no esta autorizado para realizar modificaciones');
		return;
	}
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el UPDATE*/
  	//**********************************************************************
	$sTabla=$TablaSeleMov;
	$Arreglo = array_recibe($arrdetalle);
	$sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 
	$NumEle = count($Arreglo);
	$con = ConectarBD(); 
	$sCampos=" MAX(numero)";
	$sCondicion=" comprobant='".$oComprobante."'"; 
	$sCondicion.=" and fecha='".$sFecha."'";
	$sCondicion.=" and cc='".$oCC."'"; 
	
	$SqlStr="Select ".$sCampos. " from " .$sTabla. " where ". $sCondicion;
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	
    $iNroConseMov = 0;
  	
	if (NumeroFilas($exc)>0){
		if(is_null(ObtenerResultado($exc,1))){
     		$iNroConseMov = 1;
		}else{
    	    $iNroConseMov = ObtenerResultado($exc,1) + 1;
		 }
    }

	$sTabla=$TablaSeleMov;
	$sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 
	$NumeroRenglonesT = $iNroConseMov;
	for($i = 0;$i <= $NumEle - 1; $i++){
		$sCampos='';
		$sCondicion='';
		$sValores='';
		if ($Arreglo[$i][8]== 'N'){
			if($Arreglo[$i][1] != ""){					
				$sCampos="comprobant";  
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
				$sValores="";
				$sValores.="'".$oComprobante."'";
				$sValores.=",'".$sFecha."'";
				$sValores.=",".strval($NumeroRenglonesT); //numero
				$sValores.=",'".$Arreglo[$i][1]. "'"; //codigo 
				$sValores.=",'".str_replace("\'","",str_replace('\"','',$Arreglo[$i][2])). "'"; //descripcion  
				$sValores.=",".strval(str_replace(',','',$Arreglo[$i][3])); // debe
				$sValores.=",".strval(str_replace(',','',$Arreglo[$i][4])); // Haber 
				$sValores.=",'".$Arreglo[$i][5]. "'"; // documento
				$sValores.=",".$Arreglo[$i][7]; // OrdenRen
				$sValores.=",'".$Arreglo[$i][9]. "'"; // CT
				$sValores.=",'".$Arreglo[$i][10]. "'"; // DT
				$sValores.=",'$oCC'"; // cc
				$sValores.=",'".$Arreglo[$i][11]. "'"; // IM
				$SqlStr='';
				$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
				$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);				
				
				$Arreglo[$i][0] =strval($NumeroRenglonesT);
				$Arreglo[$i][8] = "";
				$NumeroRenglonesT = $NumeroRenglonesT + 1;
			}		   
		}else if($Arreglo[$i][8] == 'M'){	
			if($Arreglo[$i][1] != ""){	
				$sCondicion=" numero=".$Arreglo[$i][0]; 
				$sCondicion.=" and comprobant='".$oComprobante."'"; 
				$sCondicion.=" and fecha='".$sFecha."'"; 
				$sCondicion.=" and cc='".$oCC."'"; 
				
				$sCampos.="fecha='".$sFecha."'";
				$sCampos.=",codigo='".$Arreglo[$i][1]. "'"; //codigo 
				$sCampos.=",descripcion='".str_replace("\'","",str_replace('\"','',$Arreglo[$i][2])). "'"; //descripcion  
				$sCampos.=",debe=".strval(str_replace(',','',$Arreglo[$i][3])); // debe
				$sCampos.=",haber=".strval(str_replace(',','',$Arreglo[$i][4])); // Haber 
				$sCampos.=",documento='".$Arreglo[$i][5]. "'"; // documento
				$sCampos.=",OrdenRen=".$Arreglo[$i][7]; // OrdenRen
				$sCampos.=",ct='".$Arreglo[$i][9]."'"; // CT
				$sCampos.=",dt='".$Arreglo[$i][10]."'"; // DT
				$sCampos.=",im='".$Arreglo[$i][11]."'"; // IM
				$Arreglo[$i][8] = "";
				
				$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
				$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
							
			}	
		}else if($Arreglo[$i][8] == 'E'){	
				$sCondicion=" numero=".$Arreglo[$i][0]; 
				$sCondicion.=" and comprobant='".$oComprobante."'"; 
				$sCondicion.=" and fecha='".$sFecha."'"; 
				$sCondicion.=" and cc='".$oCC."'"; 
				$SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
				$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
				
			
						
		}else if($Arreglo[$i][8] == ''){	
			if($Arreglo[$i][1] != ""){	
				$sCondicion=" numero=".$Arreglo[$i][0]; 
				$sCondicion.=" and comprobant='".$oComprobante."'"; 
				$sCondicion.=" and fecha='".$sFecha."'"; 
				$sCondicion.=" and cc='".$oCC."'"; 
				$sCampos="OrdenRen=".$Arreglo[$i][7]; // OrdenRen
				$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
				$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);	
				
			}	
		}	
		$Arreglo[$i][8] = '';	
	} 
    $sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 
  	$sTabla=$TablaSelec;
  	$sCampos="";
  	$sCondicion="comprobant='".$oComprobante."'";
	$sCondicion.=" and fecha='".$sFecha."'";
	$sCondicion.=" and cc='".$oCC."'"; 
  	$sCampos="";
   	$sCampos.="Usuario_m='" . $_SESSION['SisNombreUsuario'] . "'";
  	$sCampos.=",Hora_m='". date("g:i:s A") ."'";
  	$sCampos.=",Fecha_m='". date("Y/m/d") ."'"; ;
  	$sCampos.=",Concepto='". str_replace("\'","",str_replace('\"','',$oConcepto)) ."'";
  	$sCampos.=",Tipo=''";
  	$sCampos.=",Soporte=0";
	$sCampos.=",NumeroRenglones=".strval($NumeroRenglonesT);
	$sCampos.=",CC='".$oCC."'";
  	$SqlStr="";
  	$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	
	$operacion = utf8_encode('Operación');
	$exito = utf8_encode('éxito');
	echo "<script language='javascript'>
			parent.mainFrame.document.frmDiarios.enespera.value= ''		
         	alert('$operacion Realizada con $exito')
			parent.mainFrame.document.frmDiarios.target='topFrame';
		    parent.mainFrame.document.frmDiarios.method='post';
		    parent.mainFrame.document.frmDiarios.action='OpSQLDiarios.php';
  		    parent.mainFrame.document.frmDiarios.StatusOculto.value = 'BU'
  		    parent.mainFrame.document.frmDiarios.submit();  
	</script>";
}
  //D E L E T E
if ($StatusOculto =="DE"){
	$sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el DELETE*/
  	//**********************************************************************
	$con = ConectarBD(); 
    $sTabla=$TablaSeleMov;
  	$sCondicion="comprobant='".$oComprobante."'";
	$sCondicion.=" and fecha='".$sFecha."'";
	$sCondicion.=" and cc='".$oCC."'"; 
	
	$SqlStr="delete from ".$sTabla." WHERE ".$sCondicion;
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);		

	$sTabla=$TablaSelec;
  	$sCondicion="comprobant='".$oComprobante."'";
	$sCondicion.=" and fecha='".$sFecha."'";
	$sCondicion.=" and cc='".$oCC."'"; 
	
	$SqlStr="delete from ".$sTabla." WHERE ".$sCondicion;
  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	
	$xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');

	echo "<script language='Javascript'>           
				  parent.mainFrame.document.frmDiarios.enespera.value= ''		
            	  parent.mainFrame.document.frmDiarios.oComprobante.value = '';
				  parent.mainFrame.document.frmDiarios.xDFecha.value= '$xDia';     
				  parent.mainFrame.document.frmDiarios.xMFecha.value= '$xMes';     
				  parent.mainFrame.document.frmDiarios.xAFecha.value= '$xAno';     
     			  parent.mainFrame.document.frmDiarios.oNumero.value = '';
				  parent.mainFrame.document.frmDiarios.oCodigoCuenta.value= '';     
     			  parent.mainFrame.document.frmDiarios.oDesMovimiento.value=''; 
    			  parent.mainFrame.document.frmDiarios.oDebe.value='0.00'; 
			      parent.mainFrame.document.frmDiarios.oHaber.value='0.00'; 
			      parent.mainFrame.document.frmDiarios.oDocumento.value=''; 
			      parent.mainFrame.document.frmDiarios.oDesCuentaTemp.value=''; 	  
			      parent.mainFrame.document.frmDiarios.oDesCuenta.value='';
     			  parent.mainFrame.document.frmDiarios.oConcepto.value=''; 	
				  parent.mainFrame.document.frmDiarios.StatusOculto.value = 'IN';
            	  parent.mainFrame.document.frmDiarios.oVerStatus.value='Estatus  (Inclusión)';
				  parent.mainFrame.document.frmDiarios.StatusOcultoReng.value = 'X';
    			  parent.mainFrame.document.frmDiarios.target='FrameDetalle';
    			  parent.mainFrame.document.frmDiarios.action='frmRenglonesDiarios.php';
  				  parent.mainFrame.document.frmDiarios.oTablaSelec.value = '$oTablaSelec';
				  //parent.mainFrame.document.frmDiarios.oCC.value = '';
			  	  //parent.mainFrame.document.frmDiarios.oDesCC.value = '';
				  parent.mainFrame.document.frmDiarios.submit();

        </script>";
}
  //B U S C A R
if ($StatusOculto =="BU"){	
  	//**********************************************************************
  	/*C&oacute;digo PHP Para Realizar el UPDATE*/
  	//**********************************************************************
	$sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 
    $con = ConectarBD(); 
  	$sTabla=$TablaSelec;
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
	$sCampos.=",cc";
	
	$sCondicion="";
  	$sCondicion.="Comprobant='".$oComprobante."'";
	$sCondicion.=" and fecha='".$sFecha."'";
	$sCondicion.=" and cc='".$oCC."'"; 
  	$SqlStr="";
	$SqlStr="Select ".$sCampos. " from " .$sTabla." WHERE ".$sCondicion."";
   	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
// ObtenerFetch($rs3)
  	if (NumeroFilas($exc)>0){
   		$Comprobante = trim(ObtenerResultado($exc,1));
  		$xDia = obFecha(ObtenerResultado($exc,2),'D');
		$xMes = obFecha(ObtenerResultado($exc,2),'M');
		$xAno = obFecha(ObtenerResultado($exc,2),'A');
		$Actualizado = Trim(ObtenerResultado($exc,3));
		$Usuario_i = Trim(ObtenerResultado($exc,4));
        $Hora_i =  Trim(ObtenerResultado($exc,5));
    	$Fecha_i = Trim(ObtenerResultado($exc,6));
    	$Usuario_m = Trim(ObtenerResultado($exc,7));
        $Hora_m =  Trim(ObtenerResultado($exc,8));
    	$Fecha_m = Trim(ObtenerResultado($exc,9));
  	    $Concepto =  Trim(ObtenerResultado($exc,10));
  	    $Tipo =  Trim(ObtenerResultado($exc,11));
  	    $Soporte =  Trim(ObtenerResultado($exc,12));
  	    $ModuloOrigen =  Trim(ObtenerResultado($exc,13));
	    $NumeroRenglones=  Trim(ObtenerResultado($exc,14));
		$oCC=  Trim(ObtenerResultado($exc,15));
		
		$SqlStr="Select descripcion from centrocosto WHERE codigo = '$oCC'";
   	    $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
		if (NumeroFilas($exc2)>0){
		   $oDesCC = trim(ObtenerResultado($exc2,1));    
		}
  	}else{
  		$Comprobante = '';
  		$xDia = '';
		$xMes = '';
		$xAno = '';
		$Actualizado = '0';
		$Usuario_i = '';
        $Hora_i =  '';
    	$Fecha_i = '';
    	$Usuario_m = '';
        $Hora_m =  '';
    	$Fecha_m = '';
  	    $Concepto =  '';
  	    $Tipo =  '';
  	    $Soporte =  '';
  	    $ModuloOrigen =  '';
	    $NumeroRenglones=  '';
		/*$oCC= '';
		$oDesCC = '';*/
		   if ($_SESSION["CCSistema"] != ""){
				$oCC= $_SESSION["CCSistema"];
				$oDesCC = $_SESSION["DesCCSistema"];			
		   }

  	}
   	$sDesActualiza = '';
    if ($Actualizado==1){
		$sDesActualiza = 'Actualizado';
	}
	
	$modificacion = utf8_encode('Modificación');
	
  	echo "<script language='Javascript'>
	           	  parent.mainFrame.document.frmDiarios.xDFecha.value= '$xDia';     
				  parent.mainFrame.document.frmDiarios.xMFecha.value= '$xMes';     
				  parent.mainFrame.document.frmDiarios.xAFecha.value= '$xAno';     
     			  parent.mainFrame.document.frmDiarios.oDesCuenta.value='';
     			  parent.mainFrame.document.frmDiarios.oConcepto.value='$Concepto';
     			  parent.mainFrame.document.frmDiarios.oVerStatus.value='Estatus ($modificacion) $sDesActualiza'; 	
				  parent.mainFrame.document.frmDiarios.enespera.value= ''		
     			  parent.mainFrame.document.frmDiarios.StatusOculto.value= 'UP'	
				  parent.mainFrame.document.frmDiarios.method='post';
   				  parent.mainFrame.document.frmDiarios.target='FrameDetalle';
    			  parent.mainFrame.document.frmDiarios.action='frmRenglonesDiarios.php';
				  parent.mainFrame.document.frmDiarios.StatusOcultoReng.value = 'B';
  				  parent.mainFrame.document.frmDiarios.oTablaSelec.value = '$oTablaSelec';
  				  parent.mainFrame.document.frmDiarios.Actualizado.value = $Actualizado;
				  parent.mainFrame.document.frmDiarios.oCC.value = '$oCC';
				  parent.mainFrame.document.frmDiarios.oDesCC.value = '$oDesCC';
	     		  parent.mainFrame.document.frmDiarios.submit();
  	</script>";
    
}//($StatusOculto =="BU")

if ($_SESSION["CCSistema"] == ""){	
	if ($StatusOculto =="LI" or $StatusOculto =="DE"){
		echo "<script language='Javascript'>
			           	  //parent.mainFrame.document.frmDiarios.xDFecha.readOnly = false;     
						  parent.mainFrame.document.frmDiarios.oCC.value.readOnly= false;
		  	</script>";
	}else{ 
		echo "<script language='Javascript'>
		                  objDia = parent.mainFrame.document.all('xDFecha')
						  objDia.readOnly =  true;
						  objCC = parent.mainFrame.document.all('oCC')
						  objCC.readOnly =  true;
		  	</script>";

	}//($StatusOculto =="LI" or $StatusOculto =="DE")
}else{	      
	if ($StatusOculto =="LI" or $StatusOculto =="DE"){
		echo "<script language='Javascript'>
			           	  //parent.mainFrame.document.frmDiarios.xDFecha.readOnly = false;     
		  	</script>";
	}else{ 
		echo "<script language='Javascript'>
		                  objDia = parent.mainFrame.document.all('xDFecha')
						  objDia.readOnly =  true;
		  	</script>";
	}		
}//($_SESSION["CCSistema"] == "")	
?>