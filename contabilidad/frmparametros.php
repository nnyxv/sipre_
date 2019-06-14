<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmparametros -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->


<title>.: SIPRE 2.0 :. Contabilidad - Parametros</title>
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

</head>

<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div> 
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
<!--*****************************************************************************************-->
<!--*************************PANTALLA BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function PantallaBuscar(sObjeto,oArreglo){
    winOpen('PantallaBuscarFormularios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
	document.frmparametros.target='mainFrame';
	document.frmparametros.method='post';
	document.frmparametros.action='frmparametros.php';
	document.frmparametros.StatusOculto.value='BU';
	document.frmparametros.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
function AbrirBus(sObjeto,oArreglo){
	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
}// function AbrirBus(sObjeto,oArreglo){

function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
	if (Alltrim(sValor) != ''){
		document.frmparametros.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
		document.frmparametros.TAValores.value=oArreglo;
		document.frmparametros.method='post';
		document.frmparametros.target='topFrame';
		document.frmparametros.action='BusTablaParametros.php';
		document.frmparametros.submit();
	}// if (Alltrim(sValor) != ''){
}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

<!--*****************************************************************************************-->
<!--**********************************SELECCIONAR TEXTO**************************************-->
<!--*****************************************************************************************-->
function SelTexto(obj){
	if (obj.length != 0){
		obj.select();
	}
}//  function SelTexto(obj){
	
<!--*****************************************************************************************-->
<!--**********************************VALIDAR NUMERICOS**************************************-->
<!--*****************************************************************************************-->
function validar(obj){
	obj.value = new NumberFormat(obj.value).toFormatted();
}

<!--*****************************************************************************************-->
<!--**********************************EJECUTAR**************************************-->
<!--*****************************************************************************************-->
function Ejecutar(sStatus){
	document.frmparametros.target='mainFrame';
	document.frmparametros.method='post';
	document.frmparametros.action='frmparametros.php';
	
	if (sStatus == "LI"){
		document.frmparametros.StatusOculto.value = "LI"
		document.frmparametros.submit();
	}
	if (sStatus == "IN"){
		if (VerificarFechasJ(document.frmparametros)){
		   return false;
		}
		if (CamposBlancosJ(document.frmparametros)){
		   return false;
		}
		document.frmparametros.StatusOculto.value = "IN"
		document.frmparametros.submit();
	}else if (sStatus == "UP"){
		if (VerificarFechasJ(document.frmparametros)){
			return false;
 		}
 		if (CamposBlancosJ(document.frmparametros)) {
			return false;
 		}
 		document.frmparametros.StatusOculto.value = "UP"
 		document.frmparametros.submit();
	}else if (sStatus == "DE"){
		if (confirm('Desea Eliminar el registro')){
 			document.frmparametros.StatusOculto.value = "DE"
			document.frmparametros.submit();
		}
	}else if (sStatus == 'BU'){
		if (document.frmparametros.value== '' || document.frmparametros.Desha.value == 'readonly'){
   			return false;
		}
 		document.frmparametros.StatusOculto.value = "BU"
 		document.frmparametros.submit();
	}
}
</script>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--*****************************************CODIGO PHP**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<?php

if ($StatusOculto == ''){ 
	$StatusOculto ='BU';
}
                                   //L I M P I A R
if ($StatusOculto =='LI'){
	$T_descrip='';
	$xDfec_proceso=date('d');
	$xMfec_proceso=date('m');
	$xAfec_proceso=date('Y');
	$T_gancia='';
	$Tcomp_cierr='';
	$T_CierreA='';
	$T_CtaIngresos='';
	$T_CtaEgresos='';
}


                                   //I N S E R T
if ($StatusOculto =='IN')
{
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
	$con = ConectarBD();
	$sTabla='parametros';
	$sValores='';
	$sCampos='';
	$sCampos.='descrip';
	$sValores.="'".$T_descrip."'";
	$sCampos.=',fec_proceso';
	$sValores.=",'".$xAfec_proceso. '-' .$xMfec_proceso. '-' .$xDfec_proceso."'";
	$sCampos.=',gancia';
	$sValores.=",'".$T_gancia."'";
	$sCampos.=',comp_cierr';
	$sValores.=",'".$Tcomp_cierr."'";
	$sCampos.=',CierreA';
	$sValores.=",'".$T_CierreA."'";
	$sCampos.=',CtaIngresos';
	$sValores.=",'".$T_CtaIngresos."'";
	$sCampos.=',CtaEgresos';
	$sValores.=",'".$T_CtaEgresos."'";
	$SqlStr='';
	$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}

                                   //U P D A T E
if ($StatusOculto =='UP'){
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
	$con = ConectarBD();
	$sTabla='parametros';
	$sCampos='';
	$sCondicion='';
	$sCampos.='descrip= '."'".$T_descrip."'";
	$sCampos.=',fec_proceso= '."'".$xAfec_proceso. '-' .$xMfec_proceso. '-' .$xDfec_proceso."'";
	$sCampos.=',gancia= '."'".$T_gancia."'";
	$sCampos.=',comp_cierr= '."'".$Tcomp_cierr."'";
	if ($T_CierreA== 'Semestral'){
	   $T_CierreA= 0;
	}else{
	   $T_CierreA= 1;
	}
	$sCampos.=',CierreA= '."'".$T_CierreA."'";
	if ($T_CierreA ==0 ){
	   $T_CierreA= 'Semestral';
	}else{
	   $T_CierreA= 'Anual';
	}
	$sCampos.=',CtaIngresos= '."'".$T_CtaIngresos."'";
	$sCampos.=',CtaEgresos= '."'".$T_CtaEgresos."'";
	$sCampos.=',CtaIngresos2= '."'".$T_CtaIngresos2."'";
	$sCampos.=',CtaEgresos2= '."'".$T_CtaEgresos2."'";
	$sCampos.=',CtaIngresos3= '."'".$T_CtaIngresos3."'";
	$sCampos.=',CtaEgresos3= '."'".$T_CtaEgresos3."'";

	$SqlStr='';
	$SqlStr="UPDATE ".$sTabla." SET ".$sCampos;
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}

                                   //D E L E T E
if ($StatusOculto =="DE"){
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
	$con = ConectarBD();
	$sTabla='parametros';
	$sCondicion='';
	$SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			  $StatusOculto ='LI';
			  $T_descrip='';
			  $xDfec_proceso=date('d');
			  $xMfec_proceso=date('m');
			  $xAfec_proceso=date('Y');
			  $T_gancia='';
			  $Tcomp_cierr='';
			  $T_CierreA='';
			  $T_CtaIngresos='';
			  $T_CtaEgresos='';
}


                                   //B U S C A R
if ($StatusOculto =='BU'){
	$con = ConectarBD();
	$sTabla='parametros';
	$sCondicion='';
	$sCampos.='descrip';
	$sCampos.=',fec_proceso';
	$sCampos.=',gancia';
	$sCampos.=',comp_cierr';
	$sCampos.=',CierreA';
	$sCampos.=',CtaIngresos';
	$sCampos.=',CtaEgresos';
	$sCampos.=',CtaIngresos2';
	$sCampos.=',CtaEgresos2';
	$sCampos.=',CtaIngresos3';
	$sCampos.=',CtaEgresos3';
	$sCampos.=',mescierre';
	$SqlStr='Select '.$sCampos.' from '.$sTabla;
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if (NumeroFilas($exc)>0){
		$StatusOculto = 'UP';
		$T_descrip=trim(ObtenerResultado($exc,1));
		$xDfec_proceso=obFecha(ObtenerResultado($exc,2),'D');
		$xMfec_proceso=obFecha(ObtenerResultado($exc,2),'M');
		$xAfec_proceso=obFecha(ObtenerResultado($exc,2),'A');
		$T_gancia=trim(ObtenerResultado($exc,3));
		$Tcomp_cierr=trim(ObtenerResultado($exc,4));
		if (trim(ObtenerResultado($exc,5)) ==0 ){
		   $T_CierreA= 'Semestral';
		}else{
		   $T_CierreA= 'Anual';
		}
		$T_CtaIngresos=trim(ObtenerResultado($exc,6));
		$T_CtaEgresos=trim(ObtenerResultado($exc,7));
		$T_CtaIngresos2=trim(ObtenerResultado($exc,8));
		$T_CtaEgresos2=trim(ObtenerResultado($exc,9));
		$T_CtaIngresos3=trim(ObtenerResultado($exc,10));
		$T_CtaEgresos3=trim(ObtenerResultado($exc,11));
		$T_mescierre=trim(ObtenerResultado($exc,12));
		
	}else{ // if ( NumeroFilas($exc)>0){
		$StatusOculto ='LI';
		$xDfec_proceso=date('d');
		$xMfec_proceso=date('m');
		$xAfec_proceso=date('Y');
		$T_gancia='';
		$Tcomp_cierr='';
		$T_CierreA='';
		$T_CtaIngresos='';
		$T_CtaEgresos='';
		$T_mescierre =0;
	} // if ( NumeroFilas($exc)>0){
}
?>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->

<table border="0" width="100%">
	<tr>
      	<td class="tituloPaginaContabilidad">            
<?php
	 
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
?>
			Par&aacute;metos (Inclusi&oacute;n)
<?php }else{
?>
			Par&aacute;metros (Modificaci&oacute;n)
<?php } ?>            
		</td>            
	</tr>
</table>

<form name="frmparametros"action="frmparametros.php"method="post">

<table width="100%">
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Par&aacute;metros</legend>
		  	<table border="0" align="center">
       		  <tr>
    				<td class="tituloCampo" width="140" align="right">
                    	<span class="textoRojoNegrita">*</span>Empresa:
                    </td>
       				<td align="left">
       					<input  name="T_descrip" type="text" maxlength=30 size="30" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'T_gancia')" value="<?=$T_descrip?>" class="cTexBox"></td>
   				</tr>
   				<tr>
   					<td class="tituloCampo" width="140" align="right">
                    	Fecha de Proceso:
                    </td>
   					<td align="left">
           				<input  name="xDfec_proceso" type="text" maxlength=2 size=1 onFocus= "SelTexto(this);" onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDfec_proceso?>" readonly  class=" cNumdisabled ">
           				<input  name="xMfec_proceso" type="text" maxlength=2 size=1 onFocus="SelTexto(this);" onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMfec_proceso?>" readonly class=" cNumdisabled ">
           				<input  name="xAfec_proceso" type="text" maxlength=4 size=4 onFocus="SelTexto(this);" onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAfec_proceso?>" readonly class=" cNumdisabled ">
       				</td>
                    
<?php 
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_gancia'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesgancia'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre1 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_gancia."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesgancia= ObtenerResultado($rsTem1,2);	 
	}else{
		 $TDesgancia= '';
	}
?> 
				<tr>
      				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Ganancia y P&eacute;rdida:
                    </td>
        			<td align="left"> 
                    	<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre1')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre1')");?>" name="T_gancia" type="text" maxlength=80 size=15  onFocus="SelTexto(this);"        onKeyPress="fn(this.form,this,event,'T_CierreA')" value="<?=$T_gancia?>" class="cTexBox">
        				<input readonly name="TDesgancia" type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesgancia?>">
      				</td>
  				</tr>
   				<tr>
       				<td class="tituloCampo" width="140" align="right">
                    	Comp. Cierre:
                    </td>
       				<td align="left">
                    	<input  name="Tcomp_cierr" type="text" maxlength=11 size="11" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$Tcomp_cierr?>" readonly class=" cTexBoxdisabled "> 
                    </td>
   				</tr>
				<tr>
    				<td class="tituloCampo" width="140" align="right"> 
                	    <span class="textoRojoNegrita">*</span>Cierre:
           	    </td>
    				<td align="left">
                    	<select  name="T_CierreA" size "3" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
  							<option value=Semestral>Semestral</option>
							<option value=Anual>Anual</option>
						</select>
<?php
	echo"<script language='Javascript'>
          document.frmparametros.T_CierreA.value='$T_CierreA';
    </script>"    
?>
    				</td>
				</tr>
<?php 
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_CtaIngresos'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCtaIngresos'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre2 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CtaIngresos."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesCtaIngresos= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCtaIngresos= '';
	}
?> 
				<tr>
      				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Cta. Ingresos:</td>
        			<td align="left">
        				<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre2')");?>" name="T_CtaIngresos" type="text" maxlength=80 size=15  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'T_CtaEgresos')" value="<?=$T_CtaIngresos?>" class="cTexBox">
        				<input readonly name="TDesCtaIngresos" type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesCtaIngresos?>">
      				</td>
  				</tr>
<?php 
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_CtaEgresos'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCtaEgresos'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre3 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CtaEgresos."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesCtaEgresos= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCtaEgresos= '';
	}
?> 
				<tr>
      				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Cta. Egresos:</td>
      				<td align="left">
          				<input onDblClick="<?php print("AbrirBus(this.name,'$Arre3')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre3')");?>" name="T_CtaEgresos" type="text" maxlength=80 size=15  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'BtnGuardar')" value="<?=$T_CtaEgresos?>" class="cTexBox">
          				<input readonly name="TDesCtaEgresos"type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesCtaEgresos?>">
      				</td>
				</tr>
<?php
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_CtaIngresos2'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCtaIngresos2'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre2 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CtaIngresos2."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesCtaIngresos2= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCtaIngresos2= '';
	}
?> 
				<tr>
      				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Cta. Ingresos 2:
                    </td>
       				<td align="left">
       					<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre2')");?>" name="T_CtaIngresos2" type="text" maxlength=80 size=15  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'T_CtaEgresos2')" value="<?=$T_CtaIngresos2?>" class="cTexBox">
        				<input readonly name="TDesCtaIngresos2" type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesCtaIngresos2?>">
      				</td>
  				</tr>
<?php 
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_CtaEgresos2'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCtaEgresos2'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre3 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CtaEgresos2."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesCtaEgresos2= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCtaEgresos2= '';
	}
?> 
				<tr>
                	<td class="tituloCampo"  width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Cta. Egresos 2:
                    </td>
                    <td align="left">
                    	<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre3')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre3')");?>" name="T_CtaEgresos2" type="text" maxlength=80 size=15  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'BtnGuardar')" value="<?=$T_CtaEgresos2?>" class="cTexBox">
                    	<input readonly name="TDesCtaEgresos2"type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesCtaEgresos2?>">
                    </td>
				</tr>  
<?php
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_CtaIngresos3'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCtaIngresos3'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre2 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CtaIngresos3."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesCtaIngresos3= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCtaIngresos3= '';
	}
?> 
				<tr>
      				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>*Cta. Ingresos 3:
                    </td>
                    <td align="left">
	                    <input  onDblClick="<?php print("AbrirBus(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre2')");?>" name="T_CtaIngresos3" type="text" maxlength=80 size=15  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'T_CtaEgresos3')" value="<?=$T_CtaIngresos3?>" class="cTexBox">
                        <input readonly name="TDesCtaIngresos3" type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesCtaIngresos3?>">
      				</td>
  				</tr>		
<?php 
	$sClaveCon = 'codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'cuenta'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_CtaEgresos3'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCtaEgresos3'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmparametros'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre3 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CtaEgresos3."'";
	$ArrRec =$Arretabla1;
	$NumEle = count($ArrRec);
	for ($i = 0; $i <= $NumEle; $i++){
		if ($ArrRec[$i][1] == 'T') {
		   $sTabla = $ArrRec[$i][0];
		}
		if ($ArrRec[$i][1] == 'C') {
		  $sCampos.= $ArrRec[$i][0].',';
		}
		if ($ArrRec[$i][1] == 'P') {
		  $sPlantillaBus= trim($ArrRec[$i][0]);
		}
	}
	$sCampos = substr($sCampos,0,strlen($sCampos)-1);
	$con = ConectarBD();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesCtaEgresos3= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCtaEgresos3= '';
	}
?> 
				<tr>
					<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Cta. Egresos 3:
                    </td>
					<td align="left">
						<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre3')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre3')");?>" name="T_CtaEgresos3" type="text" maxlength=80 size=15  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'BtnGuardar')" value="<?=$T_CtaEgresos3?>" class="cTexBox">
        				<input readonly name="TDesCtaEgresos3"type="text" size=45 class="cTexBoxdisabled" value="<?=$TDesCtaEgresos3?>">
      				</td>
				</tr>  
                <tr>
                    <td class="tituloCampo" width="140" align="right"> 
                    	Mes Cierre:
                    </td> 
                    <td align="left" >
                         <input  name="T_mescierre" type="text" maxlength=2 size=15 onKeyPress="fn(this.form,this,event,'BtnGuardar')" value="<?=$T_mescierre?>" class="cTexBox">
                    </td>
				</tr>
			
    	</table>
        </fieldset>
        </td>
	</tr>    
</table>
            


<table  width="100%">
	<tr>
<?PHP
	$sEjecut= '';
    if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
    	$sEjecut='IN';
    }else{
        $sEjecut='UP';
    }
?>
         <td align="right"><hr/>
         	<button name=BtnGuardar type="submit" value=Guardar onClick="<?php print("Ejecutar('$sEjecut')");?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
         </td>
	</tr>
</table>  
  </tr>      
    <td class=cabecera><input name=StatusOculto type=hidden value=''>
                      <input name=TACondicion type=hidden value=''>
                      <input name=TAValores type=hidden value=''>
                      <input name=Desha type=hidden value="<?= $Desha ?>">
  </td>
<p><?php echo "<script language='JavaScript'>
     document.frmparametros.T_descrip.focus();
</script>"?>  
</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>
