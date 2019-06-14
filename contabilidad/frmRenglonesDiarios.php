<?php  session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script language="JavaScript" src="./GlobalUtility.js">
</script>

<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

<script language="JavaScript">
function jElegir(sCodigoBuscar,sDesBuscar,sObjeto1,sObjeto2,sPlantillaBus){
	eval("parent.opener."+sPlantillaBus+"."+sObjeto1+".value='" +sCodigoBuscar+"'");
   	eval("parent.opener."+sPlantillaBus+"."+sObjeto2+".value='" +sDesBuscar+"'");
   	parent.close();
}

function SobreFila(sDesCuen,obj){
	if (document.frmRenglonesDiarios.sDesactivarColor.value != "SI"){
		if (parent.document.frmDiarios.oNumero.value != ""){
			soCon = "FilaRenglon"+parent.document.frmDiarios.oNumero.value;
			var objFila = document.all(soCon);
			objFila.bgColor='white';
			objFila.color='Black';
		}	
				  
		obj.style.color='white';
		obj.bgColor='#000066';
		parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
	}
}

function FueraFila(sDesCuen,obj){
	if (document.frmRenglonesDiarios.sDesactivarColor.value != "SI"){
		obj.style.color='Black';
		obj.bgColor='white';
		parent.document.frmDiarios.oDesCuenta.value=sDesCuen;
	}		
}

function EditarRenglon(jnumero,jcodigo,jdescripcion,jdebe,jhaber,jdocumento,jDesCuenta,jOrdenRen,jCT,jDT,jIM){
	if (parent.document.frmDiarios.Actualizado.value=='1'){
		alert('El Comprobante ha sido actualizado no puede realizar ninguna modificación');			 
		return;
	 }
	 
	 if (parent.document.frmDiarios.oTablaSelec.value=='H' || parent.document.frmDiarios.oTablaSelec.value=='I'){
		alert('El Comprobante es histórico o importado y no puede realizar ningún tipo de modificación');			 
		return;
	 }
		 
	if(parent.document.frmDiarios.AccionRenglon.value != 'M' && parent.document.frmDiarios.AccionRenglon.value != 'I'){
    	document.frmRenglonesDiarios.sDesactivarColor.value = "SI";
		parent.document.frmDiarios.oNumero.value= jnumero;     
		parent.document.frmDiarios.oCodigoCuenta.value= jcodigo;     
		parent.document.frmDiarios.oDesMovimiento.value=jdescripcion; 
		parent.document.frmDiarios.oDebe.value=jdebe; 
		parent.document.frmDiarios.oHaber.value=jhaber; 
		parent.document.frmDiarios.oDocumento.value=jdocumento; 
		parent.document.frmDiarios.oDesCuentaTemp.value=jDesCuenta; 	  
		parent.document.frmDiarios.oDesCuenta.value=jDesCuenta; 	  
		parent.document.frmDiarios.oOrdenRen.value=jOrdenRen;
		parent.document.frmDiarios.oCT.value=jCT;
		parent.document.frmDiarios.oDT.value=jDT;
		parent.document.frmDiarios.oIM.value=jIM;
		parent.document.frmDiarios.AccionRenglon.value = 'M';
		soCon = "FilaRenglon"+parent.document.frmDiarios.oNumero.value;
		var objFila = document.all(soCon);
		objFila.bgColor='#000066';
		objFila.color='while'
		parent.document.frmDiarios.oCodigoCuenta.focus();
	}  
}

function InsertarRenglon(jnumero,jnumeroAnt){
	if (parent.document.frmDiarios.Actualizado.value=='1'){
		alert('El Comprobante ha sido actualizado, no puede realizar ninguna modificación');			 
		return;
	}
	
	if(parent.document.frmDiarios.AccionRenglon.value != 'M' && parent.document.frmDiarios.AccionRenglon.value != 'I'){
    	document.frmRenglonesDiarios.sDesactivarColor.value = "SI";
    	parent.document.frmDiarios.oCodigoCuenta.focus();
      	document.frmRenglonesDiarios.oNumero.value= jnumero;    
		parent.document.frmDiarios.oNumero.value= jnumero;     
		parent.document.frmDiarios.oCodigoCuenta.value= '';     
		parent.document.frmDiarios.oDesMovimiento.value=''; 
		parent.document.frmDiarios.oDebe.value='0.00'; 
		parent.document.frmDiarios.oHaber.value='0.00'; 
		parent.document.frmDiarios.oDocumento.value=''; 
		parent.document.frmDiarios.oDesCuentaTemp.value=''; 	  
		parent.document.frmDiarios.oDesCuenta.value=''; 	  
		parent.document.frmDiarios.AccionRenglon.value = 'I';
		document.frmRenglonesDiarios.StatusOcultoReng.value="I";
		nNumero = jnumeroAnt;
		document.frmRenglonesDiarios.action="frmRenglonesDiarios.php#"+Alltrim(nNumero.toString());
		document.frmRenglonesDiarios.method="post";
		document.frmRenglonesDiarios.submit();
	}
}

function EliminarRenglon(jnumero){
	if (parent.document.frmDiarios.Actualizado.value=='1'){
		  alert('El Comprobante ha sido actualizado, no puede realizar ninguna modificación');			 
		  return;
   	}
   	
	document.frmRenglonesDiarios.sDesactivarColor.value = "SI";
    soCon = "FilaRenglon"+jnumero;
    var objFila = document.all(soCon);
    objFila.bgColor='#000066';
    objFila.color='while'
	nNumero = jnumero;
    
	if(confirm("Desea eliminar el Renglón?")){
		parent.document.frmDiarios.oNumero.value= jnumero;     
     	document.frmRenglonesDiarios.oNumero.value= jnumero;     
		parent.document.frmDiarios.oCodigoCuenta.value= '';     
		parent.document.frmDiarios.oDesMovimiento.value=''; 
		parent.document.frmDiarios.oDebe.value=0.00; 
		parent.document.frmDiarios.oHaber.value=0.00; 
		parent.document.frmDiarios.oDocumento.value=''; 
		parent.document.frmDiarios.oCT.value='';
		parent.document.frmDiarios.oDT.value='';
		parent.document.frmDiarios.oIM.value='';
		parent.document.frmDiarios.oDesCuentaTemp.value=''; 	  
		parent.document.frmDiarios.oDesCuenta.value='';
		parent.document.frmDiarios.AccionRenglon.value = 'I';
		document.frmRenglonesDiarios.StatusOcultoReng.value="E";
        document.frmRenglonesDiarios.action="frmRenglonesDiarios.php#"+Alltrim(nNumero.toString());
		document.frmRenglonesDiarios.method="post";
		document.frmRenglonesDiarios.submit();
		parent.document.frmDiarios.oCodigoCuenta.focus();
	}
	document.frmRenglonesDiarios.sDesactivarColor.value = "";
 }
</script>

</head>

<body vlink="#FFFFFF" alink="#FFFFFF" link="#FFFFFF" >
<?php
$con = ConectarBD(); 
$dSumaDebe = 0;
$dSumaHaber = 0;
?>
<div id="divGeneralPorcentaje">
<form name="frmRenglonesDiarios" >
<table width="100%"  id="TablaRenglon1"  border="0"  align="left" cellspacing="1" cellpadding="1" >
<?php
 
if ($oTablaSelec == 'D'){
	$sTablaMovimien = "movimien";
}

if ($oTablaSelec == 'P'){
   	$sTablaMovimien = "movimiendif";
} 

if ($oTablaSelec == 'H'){
    $sAno = trim(intval($xMFecha));
    $sTablaMovimien = "movhistorico".$sAno;
} 

if ($oTablaSelec == 'I'){
   $sTablaMovimien = "movimienimportados";
} 
   
if ($StatusOcultoReng == 'B'){ 	
	echo "<script language='Javascript'>           
		          parent.document.frmDiarios.oTotalDebe.value =  '0.00';
				  parent.document.frmDiarios.oTotalHaber.value = '0.00';
  				  parent.document.frmDiarios.oDiferencia.value = '0.00';				
   </script>";
	$sFecha = $xAFecha . '-' . $xMFecha . '-' . $xDFecha; 

//if($_SESSION["CCSistema"] != ""){
 // $oCC = $_SESSION["CCSistema"];
  $EstadoCuenta = "sipre_contabilidad.cuenta";
  $EstadoCT =  "sipre_contabilidad.transacciones";
  $EstadoDT =  "sipre_contabilidad.documentos";
  $EstadoCC =  "sipre_contabilidad.centrocosto";
  $EstadoIM =  "sipre_contabilidad.centrocosto";
/*}else{
  $EstadoCuenta =  "cuenta";
  $EstadoCT =  "transacciones";
  $EstadoDT =  "documentos";
  $EstadoCC =  "centrocosto";
  $EstadoIM =  "centrocosto";
}*/

	$sTabla =  $sTablaMovimien ." a,$EstadoCuenta b";
	$sCampos.= "a.numero";   
	$sCampos.= ",a.codigo";  
	$sCampos.= ",a.descripcion";  
	$sCampos.= ",a.debe";     
	$sCampos.= ",a.haber";     
	$sCampos.= ",a.documento"; 
	$sCampos.= ",b.descripcion";   
	$sCampos.= ",a.OrdenRen";   
	$sCampos.= ",a.ct";   
	$sCampos.= ",a.dt";   
	$sCampos.= ",a.im";   
	$sCondicion = "Comprobant = '".$oComprobante ."' and a.codigo = b.codigo
	and fecha = '$sFecha'  and a.cc='$oCC'  order by a.OrdenRen";

	$sql = "Select " .$sCampos. " from ". $sTabla . " where " . $sCondicion  ; //este query muestra la descripcion que puse
		
	$rs3 = EjecutarExec($con,$sql); 
	$sincredetalle = 0;
	$NumeroAnt=0;
	$iFila = -1;
	$Agregado =  '';  
	
	while ($row = ObtenerFetch($rs3)) {		
		$iFila++;
							
	   	$Arreglo[$sincredetalle][0]= trim(ObtenerResultado($rs3,1,$iFila));   
	   	$Arreglo[$sincredetalle][1]= trim(ObtenerResultado($rs3,2,$iFila));  
	   	$Arreglo[$sincredetalle][2]= str_replace(chr(13), " ",str_replace(chr(10), " ",trim(ObtenerResultado($rs3,3,$iFila))));
	   	$Arreglo[$sincredetalle][3]= ObtenerResultado($rs3,4,$iFila);     
	   	$Arreglo[$sincredetalle][4] = ObtenerResultado($rs3,5,$iFila);     
	   	$Arreglo[$sincredetalle][5]= trim(ObtenerResultado($rs3,6,$iFila));   
	   	$Arreglo[$sincredetalle][6]= trim(ObtenerResultado($rs3,7,$iFila));  
	   	$Arreglo[$sincredetalle][7]= trim(ObtenerResultado($rs3,8,$iFila));   
	   	$Arreglo[$sincredetalle][8]=''; //estatus del renglon 
	   	$Arreglo[$sincredetalle][9]=trim(ObtenerResultado($rs3,9,$iFila)); //CT
	   	$Arreglo[$sincredetalle][10]=trim(ObtenerResultado($rs3,10,$iFila)); //DG
	   	$Arreglo[$sincredetalle][11]=trim(ObtenerResultado($rs3,11,$iFila)); //IM
	   	$dSumaDebe = strval($dSumaDebe) + strval(str_replace(",","",$debe));
	   	$dSumaHaber = strval($dSumaHaber) + strval(str_replace(",","",$haber));
		$NumeroAnt=$numero;
		$sincredetalle++;
	} // while ($row = ObtenerFetch($rs3)) {
	$arregloenv = array_envia($Arreglo);
} //if (StatusOcultoReng == 'B'){ 

 
if ($StatusOcultoReng == 'I'){ 
   	$Arreglo = array_recibe($arrdetalle);
   	$NumEle = count($Arreglo) ;
  	$incTemp = 0;
   	$inc = 0;
   	$bEncontro = false;
   	$Agregado = "";
   	$AsigOrden = 10;
	
	while ($inc <= $NumEle - 1){ 
		if (strval($_POST["oNumero"]) == strval($Arreglo[$inc][0]) ){
			$ArregloTemp[$incTemp][1] ='';
			$ArregloTemp[$incTemp][2] ='';
			$ArregloTemp[$incTemp][3] ='0.00';
			$ArregloTemp[$incTemp][4] ='0.00';
			$ArregloTemp[$incTemp][5] ='';
			$ArregloTemp[$incTemp][6] ='';
			$ArregloTemp[$incTemp][7] =$AsigOrden;
			$Agregado = $ArregloTemp[$incTemp][7];
			$ArregloTemp[$incTemp][8]='I';
			$NumT = "Tmp".strval(rand(0,99999));
    		$ArregloTemp[$incTemp][0] = $NumT;
    		$ArregloTemp[$incTemp][9]=''; //CT
			$ArregloTemp[$incTemp][10]=''; //DG
			$ArregloTemp[$incTemp][11]=''; //IM

			echo "<script language='Javascript'> 
			          parent.document.frmDiarios.oNumero.value = '$NumT';
					  parent.document.frmDiarios.oCodigoCuenta.value= '';     
     			 	  parent.document.frmDiarios.oDesMovimiento.value=''; 
	    			  parent.document.frmDiarios.oDebe.value='0.00'; 
				      parent.document.frmDiarios.oHaber.value='0.00'; 
				      parent.document.frmDiarios.oDocumento.value=''; 
				      parent.document.frmDiarios.oDesCuentaTemp.value=''; 	  
				      parent.document.frmDiarios.oDesCuenta.value='';
		   </script>";
			
			$incTemp++;
			$AsigOrden = $AsigOrden + 10;
		}//        if (strval($_POST["oNumero"]) == strval($Arreglo[$inc][0]) ){
			
		$ArregloTemp[$incTemp][0] = $Arreglo[$inc][0];
		$ArregloTemp[$incTemp][1] = $Arreglo[$inc][1];
		$ArregloTemp[$incTemp][2] = $Arreglo[$inc][2];
		$ArregloTemp[$incTemp][3] = $Arreglo[$inc][3];
		$ArregloTemp[$incTemp][4] = $Arreglo[$inc][4];
		$ArregloTemp[$incTemp][5] = $Arreglo[$inc][5];
		$ArregloTemp[$incTemp][6] = $Arreglo[$inc][6];
		$ArregloTemp[$incTemp][7] = $AsigOrden;
		$ArregloTemp[$incTemp][8] = $Arreglo[$inc][8];
  		$ArregloTemp[$incTemp][9] = $Arreglo[$inc][9]; //CT
    	$ArregloTemp[$incTemp][10]= $Arreglo[$inc][10]; //DG
    	$ArregloTemp[$incTemp][11]= $Arreglo[$inc][11]; //IM

   		$inc++;
   		$incTemp++;
   		$AsigOrden = $AsigOrden + 10;
	}// while ($inc <= $NumEle) 
	$Arreglo = $ArregloTemp;
}//if ($StatusOcultoReng == 'I'){ 

if ($StatusOcultoReng == 'M'){ 
	$Arreglo = array_recibe($arrdetalle);
   	$NumEle = count($Arreglo);
   	$bEncontro = false;
   	$NumeroAnt=0;
   	for ($inc = 0; $inc <= $NumEle-1; $inc++){ 
    	if (strval($oNumero) == strval($Arreglo[$inc][0])){
    		$Arreglo[$inc][1] =$oCodigoCuenta;
			$Arreglo[$inc][2] =$oDesMovimiento;
			$Arreglo[$inc][3] =$oDebe;
			$Arreglo[$inc][4] =$oHaber;
			$Arreglo[$inc][5] =$oDocumento;
			$Arreglo[$inc][6] =$oDesCuenta;
			$Arreglo[$inc][9] = $oCT;
			$Arreglo[$inc][10] = $oDT;
			$Arreglo[$inc][11] = $oIM;
		   	
			if ($Arreglo[$inc][8] == ""){
     			$Arreglo[$inc][7] =$oOrdenRen;
    		   $Arreglo[$inc][8] ="M";
			}elseif ($Arreglo[$inc][8] == "I"){    
     		   $Arreglo[$inc][8] ="N";
      	   	} //    if (strval($oNumero) == strval($Arreglo[$inc][0])){
				
            $Agregado =  $Arreglo[$inc][7];   
			echo "<script language='Javascript'>           
		          parent.document.frmDiarios.oNumero.value = '';
				  parent.document.frmDiarios.oCodigoCuenta.value= '';     
     			  parent.document.frmDiarios.oDesMovimiento.value=''; 
    			  parent.document.frmDiarios.oDebe.value='0.00'; 
			      parent.document.frmDiarios.oHaber.value='0.00'; 
			      parent.document.frmDiarios.oDocumento.value=''; 
			      parent.document.frmDiarios.oDesCuentaTemp.value=''; 	  
			      parent.document.frmDiarios.oDesCuenta.value='';
				  parent.document.frmDiarios.AccionRenglon.value ='';
				  parent.document.frmDiarios.oCT.value ='';
				  parent.document.frmDiarios.oDT.value ='';
				  parent.document.frmDiarios.oIM.value ='';
        	</script>";
		}
    } //  for ($inc = 0; $inc <= $NumEle-1; $inc++){ 
}//if ($StatusOcultoReng == 'M'){ 


if ($StatusOcultoReng == 'E'){ 
	$Arreglo = array_recibe($arrdetalle);
   	$NumEle = count($Arreglo);
   	$bEncontro = false;
   	$NumeroAnt=0;
   	for ($inc = 0; $inc <= $NumEle-1; $inc++){ 
	    if ($oNumero == $Arreglo[$inc][0]){
			$bEncontro = true;
		   	if (trim($Arreglo[$inc][8]) == "" || $Arreglo[$inc][8] == "M"){
    			$Arreglo[$inc][8] ="E";
		   	}else{//if ($Arreglo[$inc][8] == "" || $Arreglo[$inc][8] == "M"){
   		    	$Arreglo[$inc][8] ="NE";
		   	} //if ($Arreglo[$inc][8] == "" || $Arreglo[$inc][8] == "M"){
		   	
			$sDesactivarColor = "";
			echo "<script language='Javascript'>           
		          parent.document.frmDiarios.oNumero.value = '';
   			      parent.document.frmDiarios.AccionRenglon.value = '';
        	</script>";   
		}//if ($oNumero == $Arreglo[$inc][0]){
	}//   for ($inc = 0; $inc <= $NumEle-1; $inc++){ 	
}//if ($StatusOcultoReng == 'E'){ 

if ($StatusOcultoReng == 'N'){ 
	$Arreglo = array_recibe($arrdetalle);
   	$NumEle = count($Arreglo);
   	$bEncontro = false;
   	$NumeroAnt=0;
    $Elegido= 0; 
   	if(!$Arreglo){
    	$inc=0; 
		$NumT = "Tmp".'0';
   	}else{//if (!$Arreglo){
		$inc=$NumEle;    
		$NumT = "Tmp".strval(rand(0,99999));
   	}//   if (!$Arreglo){	
	
	
	//$Arreglo[$inc][2] =$oDesMovimiento;
    
	$bEncontro = true;
	$Arreglo[$inc][0] =$NumT;
	$Arreglo[$inc][1] =$oCodigoCuenta;
	$Arreglo[$inc][2] =$oDesMovimiento;
	$Arreglo[$inc][3] =$oDebe;
	$Arreglo[$inc][4] =$oHaber;
	$Arreglo[$inc][5] =$oDocumento;
	$Arreglo[$inc][6] =$oDesCuenta;
	$Arreglo[$inc][7] ='0';
	$Arreglo[$inc][8] ='N';
	$Arreglo[$inc][9] =$oCT;
	$Arreglo[$inc][10] =$oDT;
	$Arreglo[$inc][11] =$oIM;
	$Elegido= 0;
			
    echo "<script language='Javascript'>           
		          parent.document.frmDiarios.oNumeroConsecutivo.value = '$Elegido';
        </script>";   
	$iOrdenRen = 10;
}//if ($StatusOcultoReng == 'N'){ 



$NumEle = count($Arreglo);
$NumeroAnt=0; 
$iNumeroShow = 0; 

for($inc = 0; $inc <= $NumEle-1; $inc++){ 
	$iNumeroShow++;
/*	    if ($inc == $NumEle-1){
		 setType($dSumaDebe,"double");
	     echo "Listo :".number_format($dSumaDebe,2);
	    }*/
	$ojo = $Arreglo[$inc][8];
	$numero = $Arreglo[$inc][0];
	$codigo = $Arreglo[$inc][1];
	$descripcion = utf8_encode($Arreglo[$inc][2]);
   	$debe = $Arreglo[$inc][3];
   	$haber = $Arreglo[$inc][4];
   	$documento = $Arreglo[$inc][5];
   	$DesCuenta = $Arreglo[$inc][6];
   	$oCT = $Arreglo[$inc][9];
   	$oDT = $Arreglo[$inc][10];
   	$oIM = $Arreglo[$inc][11];
		
	if ($StatusOcultoReng != 'N'){ 
		$OrdenRen=$Arreglo[$inc][7];
	}else{
		$Arreglo[$inc][7] =$iOrdenRen;
    	$OrdenRen =$iOrdenRen;
		$Agregado =  $Arreglo[$inc][7];
	} 
	
	$sStilo	= "bgColor='white'";
    if($Agregado == $Arreglo[$inc][7] && $StatusOcultoReng == 'I'){
		$sStilo	= "bgColor='#000066' style='color:#FFFFFF'";
	}else{//     if($Agregado == $Arreglo[$inc][7]){
		$sStilo	= "bgColor='white'";
	}//     if($Agregado == $Arreglo[$inc][7]){
	   
	if ($Arreglo[$inc][8] != "E" && $Arreglo[$inc][8] != "NE"){
		//acá es cuando ingresa el renglon
		$debe = str_replace(",","",$debe);
		$haber = str_replace(",","",$haber);
	
		$dSumaDebe = bcadd($dSumaDebe,$debe,2);
		$dSumaHaber = bcadd($dSumaHaber,$haber,2);

	   //$dSumaDebe= str_replace(".","",$dSumaDebe);
	   /*echo "<script language='Javascript'> 
   		parent.document.frmDiarios.oTotalDebe.value =  new NumberFormat($dSumaDebe/100).toFormatted()
		   		parent.document.frmDiarios.refresh
		</script>"; */
?>

	 
	<tr id="<?php print('FilaRenglon'.trim($numero)); ?>"   style="cursor:pointer"   onMouseOut="FueraFila('',this);" onMouseMove="SobreFila('<?=trim($DesCuenta)?>',this);" class="<?=$clase?> ">
    	<A name=<?=trim($OrdenRen)?> ></A>
		<td width="2%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>" >
			<?=$iNumeroShow?> 
        </td>
		<td width="8%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>" align="left">
			<?=trim($codigo)?> 
         </td>
		<td width="2%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>" align="left">
			<?=trim($oCT)?> 
        </td>
		<td width="21%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>" align="left">
			<?php print($descripcion);?>
        </td>
    	<td  width="9%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>" align="left">
        <?=number_format($debe,2);?>
		
<?php 
/*	echo "<script language='Javascript'> 
	document.write(new NumberFormat('$debe').toFormatted()); 
</script>";*/ 
?>
		</td>
		<td width="9%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>"  align="left">
		<?=number_format($haber,2);?>
		</td>
		<td width="6%" title="Editar Renglón" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>" align="left">
			<?=trim($oDT)?> 
        </td>
    	<td width="9%" onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>"  align="left">
			<?php print($documento);?>
        </td>
		<td width="4%"   onClick="<?php print("EditarRenglon('$numero','$codigo','$descripcion','$debe','$haber','$documento','$DesCuenta','$OrdenRen','$oCT','$oDT','$oIM')")?>">
			<?php print($oIM);?>
        </td>				
		<td width="2%" height="8" class="RenglonesMov"><a href="<?php print("javascript:InsertarRenglon('$numero','$OrdenRen')")?>"><img title="Insertar Renglón" src="../img/iconos/add.png"></a></td>
		<td width="2%" height="8" class="RenglonesMov"><a href="<?php print("javascript:EliminarRenglon('$numero')")?>"><img  title="Eliminar Renglón" src="../img/iconos/ico_delete.png"></a></td>
		
	</tr> 
	
<?php  	
	}//   if ($Arreglo[$inc][8] != "E" && $Arreglo[$inc][8] != "NE"){
	
	if ($StatusOcultoReng == 'N'){
    	$iOrdenRen = $iOrdenRen + 10;
	} //    if ($StatusOcultoReng == 'N'){
}//for ($inc = 0; $inc <= $NumEle; i++;){ 

$arregloenv = array_envia($Arreglo);
$dDiferencia= bcsub(str_replace(",","",$dSumaDebe),str_replace(",","",$dSumaHaber),2);

$dSumaDebe = number_format($dSumaDebe,2);
$dSumaHaber = number_format($dSumaHaber,2);
 
$dDiferencia = number_format($dDiferencia,2);
echo "<script language='Javascript'>           
		parent.document.frmDiarios.oTotalDebe.value = '$dSumaDebe' ;
		parent.document.frmDiarios.oTotalHaber.value = '$dSumaHaber';
  		parent.document.frmDiarios.oDiferencia.value = '$dDiferencia';				
		parent.document.frmDiarios.oCodigoCuenta.value= '';     
		parent.document.frmDiarios.oDebe.value='0.00'; 
		parent.document.frmDiarios.oHaber.value='0.00'; 
		parent.document.frmDiarios.oDesCuentaTemp.value=''; 	  
		parent.document.frmDiarios.oDesCuenta.value='';
		parent.document.frmDiarios.arrdetalle.value = '$arregloenv';
   </script>";
?>
  
</table>
   <input name="StatusOcultoReng" type="hidden" value="">
   <input name="arrdetalle" type="hidden" value='<?php  print($arregloenv); ?>'>
    <input name="oNumero" type="hidden" value=''>
   <input name="sDesactivarColor" type="hidden" value= <?= $sDesactivarColor ?>>
    <input name="oTablaSelec" type="hidden" value=<?=$oTablaSelec?>>
</form>
</div>
</body>
</html>