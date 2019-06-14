<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmcuenta -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>.: SIPRE 2.0 :. Contabilidad - Catálogo de Cuentas</title>
<link rel="icon" type="image/png" href="img/login/icono_sipre_png.png" />
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
<script language="JavaScript"src="GlobalUtility.js">
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
		document.frmcuenta.target='mainFrame';
		document.frmcuenta.method='post';
		document.frmcuenta.action='frmcuenta.php';
		document.frmcuenta.StatusOculto.value='BU';
		document.frmcuenta.submit();
	}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
	function AbrirBus(sObjeto,oArreglo){
    	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  	}// function AbrirBus(sObjeto,oArreglo){
  
	function AbrirBus2(sObjeto,oArreglo){
    	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  	}// function AbrirBus(sObjeto,oArreglo){
		
	function BuscarDescrip2(sValor,sCampoBuscar,oArreglo){
		if (Alltrim(sValor) != ''){
			document.frmcuenta.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
			document.frmcuenta.TAValores.value=oArreglo;
			document.frmcuenta.method='post';
			document.frmcuenta.target='topFrame';
			document.frmcuenta.action='BusTablaParametros.php';
			document.frmcuenta.submit();
 		}// if (Alltrim(sValor) != ''){
	}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

	function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    	if (Alltrim(sValor) != ''){
			document.frmcuenta.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
			document.frmcuenta.TAValores.value=oArreglo;
			document.frmcuenta.method='post';
			document.frmcuenta.target='topFrame';
			document.frmcuenta.action='BusTablaParametros.php';
			document.frmcuenta.submit();
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
    	document.frmcuenta.target='mainFrame';
       	document.frmcuenta.method='post';
       	document.frmcuenta.action='frmcuenta.php';
      	if (sStatus == "LI"){
        	document.frmcuenta.StatusOculto.value = "LI"
        	document.frmcuenta.submit();
      	}
      	
		if (sStatus == "IN"){
        	if (VerificarFechasJ(document.frmcuenta)){
           		return false;
        	}
        	if (CamposBlancosJ(document.frmcuenta)){
           		return false;
        	}
        	document.frmcuenta.StatusOculto.value = "IN"
        	document.frmcuenta.submit();
      	}else if(sStatus == "UP"){
        	if (VerificarFechasJ(document.frmcuenta)){
            	return false;
         	}
         	if (CamposBlancosJ(document.frmcuenta)){
            	return false;
         	}
         	document.frmcuenta.StatusOculto.value = "UP"
         	document.frmcuenta.submit();
      	}else if (sStatus == "DE"){
        	if (confirm('Desea Eliminar el registro')){
         		document.frmcuenta.StatusOculto.value = "DE"
         		document.frmcuenta.submit();
        	}
      	}else if (sStatus == 'BU'){
        	if (document.frmcuenta.T_codigo.value== '' || document.frmcuenta.Desha.value == 'readonly'){
           		return false;
        	}
         	document.frmcuenta.StatusOculto.value = "BU"
         	document.frmcuenta.submit();
      	}
	}
</script>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--*****************************************CODIGO PHP**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<?php
	$Desha = ' class=cTexBox ';

///////////////////////////////////////L I M P I A R////////////////////////////////////////////
	if($StatusOculto =='LI'){
		$T_codigo='';
		$T_descripcion='';
		$xDult_mov=date('d');
		$xMult_mov=date('m');
		$xAult_mov=date('Y');
		$Nsaldo_ant='0.00';
		$Ndebe='0.00';
		$Nhaber='0.00';
		$Ndebe_cierr='0.00';
		$Nhaber_cierr='0.00';
		$TDeshabilitar='';
		$xDFechaDes=date('d');
		$xMFechaDes=date('m');
		$xAFechaDes=date('Y');
		$T_CentroCosto = '';
		$TBancaria = 0;
	}

//////////////////////////////////////////I N S E R T////////////////////////////////////////////
	if ($StatusOculto =='IN'){
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
	        $con = ConectarBD();
	        $sTabla='cuenta';
	        $sValores='';
	        $sCampos='';
	        $sCampos.='codigo';
	        $sValores.="'".$T_codigo."'";
	        $sCampos.=',descripcion';
	        $sValores.=",'".$T_descripcion."'";
	        $sCampos.=',ult_mov';
	        $sValores.=",'".$xAult_mov. '-' .$xMult_mov. '-' .$xDult_mov."'";
	        $sCampos.=',saldo_ant';
	        $sValores.=",'".str_replace(',','',$Nsaldo_ant)."'";
	        $sCampos.=',debe';
	        $sValores.=",'".str_replace(',','',$Ndebe)."'";
	        $sCampos.=',haber';
	        $sValores.=",'".str_replace(',','',$Nhaber)."'";
	        $sCampos.=',debe_cierr';
	        $sValores.=",'".str_replace(',','',$Ndebe_cierr)."'";
	        $sCampos.=',haber_cierr';
	        $sValores.=",'".str_replace(',','',$Nhaber_cierr)."'";
	        $sCampos.=',Deshabilitar';
	        $sValores.=",'".$TDeshabilitar."'";
	        $sCampos.=',FechaDes';
	        $sValores.=",'".$xAFechaDes. '-' .$xMFechaDes. '-' .$xDFechaDes."'";
		$sCampos.=',cc';  
		$sValores.=",'".$T_CentroCosto."'";				 
		$sCampos.=',bancaria';  
		$sValores.=",$TBancaria";				 
				
        	$SqlStr='';
        
		$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
	        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        
	        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
		
		//auditoria
		auditoria('insert',$sTabla,$sCampos,'insert cuenta en catalogo: '.$T_codigo);
		//fin auditoria
	}


///////////////////////////////////////////U P D A T E//////////////////////////////////////////
	if ($StatusOculto =='UP'){
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
	    	$con = ConectarBD();
	        $sTabla='cuenta';
	        $sCampos='';
	        $sCondicion='';
	        $sCampos.='codigo= '."'".$T_codigo."'";
	        $sCondicion.='codigo= '."'".$T_codigo."'";
	        $sCampos.=',descripcion= '."'".$T_descripcion."'";
	        $sCampos.=',ult_mov= '."'".$xAult_mov. '-' .$xMult_mov. '-' .$xDult_mov."'";
	        $sCampos.=',saldo_ant= '."'".str_replace(',','',$Nsaldo_ant)."'";
	        $sCampos.=',debe= '."'".str_replace(',','',$Ndebe)."'";
	        $sCampos.=',haber= '."'".str_replace(',','',$Nhaber)."'";
	        $sCampos.=',debe_cierr= '."'".str_replace(',','',$Ndebe_cierr)."'";
	        $sCampos.=',haber_cierr= '."'".str_replace(',','',$Nhaber_cierr)."'";
	        $sCampos.=',Deshabilitar= '."'".$TDeshabilitar."'";
	        $sCampos.=',FechaDes= '."'".$xAFechaDes. '-' .$xMFechaDes. '-' .$xDFechaDes."'";
		$sCampos.=",cc = '$T_CentroCosto'";
		$sCampos.=",Bancaria = $TBancaria";
		
		$campos_act = 'codigo, descripcion, ult_mov, saldo_ant, debe, haber, debe_cierr, haber_cierr, deshabilitar,
		FechaDes, cc, Bancaria';
        	$SqlStr='';
        
        	$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
        	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
  		
		$Desha = ' readonly class=cTexBoxdisabled';
        	echo "<script language='javascript'> alert('Operación realizada con éxito')</script>";
		
		//auditoria
		auditoria('update',$sTabla,$campos_act,'update cuenta en catalogo: '.$T_codigo);
		//fin auditoria
	}


///////////////////////////////////////////D E L E T E////////////////////////////////////////////
	if ($StatusOculto =="DE"){
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
	    	$con = ConectarBD();
	        $sTabla='cuenta';
	        $sCondicion='';
	        $sCondicion.='codigo= '."'".$T_codigo."'";
        
		$SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
       		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
		//auditoria
		auditoria('delete',$sTabla,$sCampos,'delete cuenta en catalogo: '.$T_codigo);
		//fin auditoria
        
		$StatusOculto ='LI';
		$T_codigo='';
		$T_descripcion='';
		$xDult_mov=date('d');
		$xMult_mov=date('m');
		$xAult_mov=date('Y');
		$Nsaldo_ant='0.00';
		$Ndebe='0.00';
		$Nhaber='0.00';
		$Ndebe_cierr='0.00';
		$Nhaber_cierr='0.00';
		$TDeshabilitar='';
		$xDFechaDes=date('d');
		$xMFechaDes=date('m');
		$xAFechaDes=date('Y');
		$T_CentroCosto = "";
		$TBancaria = 0;
	}

///////////////////////////////////////////B U S C A R/////////////////////////////////////////////
	if ($StatusOculto =='BU'){
		$con = ConectarBD();
	        $sTabla='cuenta';
	        $sCondicion='';
	        $sCampos.='codigo';
	        $sCampos.=',descripcion';
	        $sCampos.=',ult_mov';
	        $sCampos.=',saldo_ant';
	        $sCampos.=',debe';
	        $sCampos.=',haber';
	        $sCampos.=',debe_cierr';
	        $sCampos.=',haber_cierr';
	        $sCampos.=',Deshabilitar';
	        $sCampos.=',FechaDes';
	        $sCampos.=',cc';
		$sCampos.=',Bancaria';
       		$sCondicion.='codigo= '."'".$T_codigo."'";
        
		$SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
       	 	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        
        if ( NumeroFilas($exc)>0){
              $StatusOculto = 'UP';
                $T_codigo=trim(ObtenerResultado($exc,1));
                $T_descripcion=trim(ObtenerResultado($exc,2));
		$xDult_mov=obFecha(ObtenerResultado($exc,3),'D');
                $xMult_mov=obFecha(ObtenerResultado($exc,3),'M');
                $xAult_mov=obFecha(ObtenerResultado($exc,3),'A');
                $Nsaldo_ant=trim(ObtenerResultado($exc,4));
                $Ndebe=trim(ObtenerResultado($exc,5));
                $Nhaber=trim(ObtenerResultado($exc,6));
                $Ndebe_cierr=trim(ObtenerResultado($exc,7));
                $Nhaber_cierr=trim(ObtenerResultado($exc,8));
                $TDeshabilitar=trim(ObtenerResultado($exc,9));
                $xDFechaDes=obFecha(ObtenerResultado($exc,10),'D');
                $xMFechaDes=obFecha(ObtenerResultado($exc,10),'M');
                $xAFechaDes=obFecha(ObtenerResultado($exc,10),'A');
		$T_CentroCosto = trim(ObtenerResultado($exc,11));
		$TBancaria = trim(ObtenerResultado($exc,12));
   		$Desha = ' readonly  class=cTexBoxdisabled';
       	}else{ // if ( NumeroFilas($exc)>0){
                $StatusOculto ='LI';
                $T_descripcion='';
                $xDult_mov=date('d');
                $xMult_mov=date('m');
                $xAult_mov=date('Y');
                $Nsaldo_ant='0.00';
                $Ndebe='0.00';
                $Nhaber='0.00';
                $Ndebe_cierr='0.00';
                $Nhaber_cierr='0.00';
                $TDeshabilitar='';
                $xDFechaDes=date('d');
                $xMFechaDes=date('m');
                $xAFechaDes=date('Y');
		$T_CentroCosto = "";
		$TBancaria = 0;
       	} // if ( NumeroFilas($exc)>0){
}
?>


<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->

<div id="divInfo" class="print">
<table border="0" width="100%">
    <tr>
        <td class="tituloPaginaContabilidad">
        
<?php
if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
?>
            Cat&aacute;logo de Cuentas (Inclusión)
<?php 
}else{
?>
            Cat&aacute;logo de Cuentas (Modificación)
<?php 
} 
?>
        
        </td>            
	</tr>
</table>  
       
<form name="frmcuenta" action="frmcuenta.php" method="post" >      
<table width="100%">
	<tr>
		<td>&nbsp;</td>
	</tr>	
	<tr>    
		<td><fieldset>
		  <legend class="legend">Datos del Cat&aacute;logo</legend>
        	<table border="0" align="center">
        		<tr>
    				<td  height=20 class="tituloCampo" width="140" align="right">
	       				<span class="textoRojoNegrita">*</span>C&oacute;digo:
       				</td>   
	 				<td height=20 align="left">            
       					<input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_codigo"type="text"maxlength=80 size="35" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_codigo?>">
        			</td>
                    <td  height=20 class="tituloCampo" width="140" align="right"> Debe Cierre:</td>			
            		<td height=20 align="left" valign="top">
              			<input  name="Ndebe_cierr"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Ndebe_cierr?>" readonly class=" cNumdisabled ">
            		</td>

<?php 
	echo"<script language='Javascript'>  document.frmcuenta.Ndebe_cierr.value=new NumberFormat($Ndebe_cierr).toFormatted(); </script>"; 
?> 
				</tr>	
				<tr>
		 			<td  height=20 class="tituloCampo" width="140" align="right">
			         	<span class="textoRojoNegrita">*</span> Descripci&oacute;n:
                    </td>
          			<td height=20 align="left" valign="top">
              			<input  name="T_descripcion"type="text"maxlength=100 size="55" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TDeshabilitar')" value="<?=$T_descripcion?>" class="cTexBox" >
          			</td>
                   	<td  height=20 class="tituloCampo" width="140" align="right"> Haber Cierre:
                    </td>				  
            		<td height=20 align="left" valign="top">
              			<input  name="Nhaber_cierr"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Nhaber_cierr?>" readonly class=" cNumdisabled ">
            		</td>
              	</tr>
              
<?php 
	echo"<script language='Javascript'> document.frmcuenta.Nhaber_cierr.value=new NumberFormat($Nhaber_cierr).toFormatted(); </script>";    
?>

				<tr>
					<td  height=20 class="tituloCampo" width="140" align="right">&Uacute;ltimo Movimiento:
					</td>
		  			<td height=20 align="left" valign="top">
              			<input  name="xDult_mov"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDult_mov?>" readonly class=" cNumdisabled ">
              			<input  name="xMult_mov"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMult_mov?>" readonly class=" cNumdisabled ">
              			<input  name="xAult_mov"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAult_mov?>" readonly class=" cNumdisabled ">
          			</td>
               		<td  height=20 class="tituloCampo" width="140" align="right"> Deshabilitar:
                    </td>
				    <td  height=20 align="left" width="140"valign="top">  
              			<select  name="TDeshabilitar" size "3" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
                			<option value=0>NO</option>
                			<option value=1>SI</option>
              			</select>
            		</td>             
                    
<?php  
	echo"<script language='Javascript'> document.frmcuenta.TDeshabilitar.value='$TDeshabilitar'; </script>";
?>

	   			</tr>
                
    			<tr>
     				<td  height=20 class="tituloCampo" width="140" align="right">Saldo Anterior:
					</td>
		  			<td height=20 align="left" valign="top">
              			<input  name="Nsaldo_ant"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Nsaldo_ant?>" readonly class=" cNumdisabled ">
          			</td>
<?php
	echo"<script language='Javascript'>   document.frmcuenta.Nsaldo_ant.value=new NumberFormat($Nsaldo_ant).toFormatted(); </script>";
?>
    
                   	<td  height=20 class="tituloCampo" width="140" align="right"> Fecha Deshabilitado:
                    </td>				  
            		<td height=20 align="left" valign="top">
              			<input  name="xDFechaDes"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFechaDes?>" class="cNum">
              			<input  name="xMFechaDes"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFechaDes?>" class="cNum">
              			<input  name="xAFechaDes"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFechaDes?>" class="cNum">
            		</td>
          
<?php 
	$sClaveCon = 'Codigo'; // Campo Clave para buscar
	$Arretabla2[0][0]= 'centrocosto'; //Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'Codigo'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'Descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_CentroCosto'; //objeto Campo1
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'TDesCentroCosto'; //objeto Campo2
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmcuenta'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$Arre2 = array_envia($Arretabla2); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CentroCosto."'";
	$ArrRec =$Arretabla2;
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
	$con = ConectarBDAd();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	
	if (NumeroFilas($rsTem1) > 0){
		$TDesCentroCosto= ObtenerResultado($rsTem1,2);
	}else{
		$TDesCentroCosto= '';
	}
	
?>
                    
        		</tr>
    
				<tr>
					<td  height=20 class="tituloCampo" width="140" align="right"> Debe:
                    </td>				  
            		<td height=20 align="left" valign="top">
              			<input  name="Ndebe"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Ndebe?>" readonly class=" cNumdisabled ">
            		</td>
                    <td height=20 class="tituloCampo" width="140" align="right">Centro Costo:
                    </td>
        			<td height=20 align="left" width="500" ><input  onDblClick="<?php print("AbrirBus2(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip2(this.value,'$sClaveCon','$Arre2')");?>" name="T_CentroCosto"type="text" maxlength=8 size=10  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TBloqueo')" value="<?=$T_CentroCosto?>" class="cTexBox">
        				<input readonly name="TDesCentroCosto" type="text" size=52 class="cTexBoxdisabled" value="<?=$TDesCentroCosto?>">
      				</td>
              	</tr>
<?php
	echo"<script language='Javascript'> document.frmcuenta.Ndebe.value=new NumberFormat($Ndebe).toFormatted(); </script>";
?>
            <td  height=20 class="tituloCampo" width="140" align="right"> Haber:</td>
                      
            <td height=20 align="left" valign="top">
                  <input  name="Nhaber"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$Nhaber?>" readonly class=" cNumdisabled ">
                </td>
    
    <?php 
        echo"<script language='Javascript'> document.frmcuenta.Nhaber.value=new NumberFormat($Nhaber).toFormatted(); </script>"; 
    ?>
    
            <td  height=20 class="tituloCampo" width="140" align="right"> Bancaria:</td>
              
            <td  height=20 align="left" width="140"valign="top">  
                <select  name="TBancaria" size "3" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
                    <option value=0>NO</option>
                    <option value=1>SI</option>
                </select>
            </td>            
        </tr>

<?php  
	echo"<script language='Javascript'> document.frmcuenta.TBancaria.value='$TBancaria'; </script>";
?>

    </table>
    </fieldset>	

 
<?php
	$sEjecut= '';
  	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
		$sEjecut='IN';
  	}else{
  		$sEjecut='UP';
	}
?>
                
                <tr>
                <td align="right"><hr/>                
           <button name=BtnGuardar type="submit" value=Guardar onClick="<?php print("Ejecutar('$sEjecut');");?>" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>

<?php 
	$Arretabla2[0][0]= 'cuenta';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'codigo'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_codigo';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmcuenta'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>

        <button name=BtnBuscar type="submit" value=Buscar onClick="<?php print("PantallaBuscar('T_Codigo','$ArreGeneral')");?>" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button> 

<?php
	if(!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){ 
?>

  <button name=BtnIncluir type="submit" value=Incluir onClick="Ejecutar('LI');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Incluir</td></tr></table></button>
  <button name=BtnEliminar type="submit" value=Eliminar onClick="Ejecutar('DE');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
        <script language='javascript'>
             document.frmcuenta.T_descripcion.focus();
         </script>
    
<?php 
	}else{ 
		if ($T_codigo !=  ''){
?>
					 <script language='javascript'>
        		               document.frmcuenta.T_descripcion.focus();
                	       </script>
<?php 
		}else{
?>
                      <script language='javascript'>
                            document.frmcuenta.T_codigo.focus();
                          </script>
<?php 
		} 						  
	} 
?>
           </td>
           	</tr>
</table>	
	  
 </div>
<input name=StatusOculto type=hidden value=''>
<input name=TACondicion type=hidden value=''>
<input name=TAValores type=hidden value=''>
<input name=Desha type=hidden value="<?= $Desha ?>">
                                                                  					 
</form>
</div>


<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>


</body>
   
</html>
