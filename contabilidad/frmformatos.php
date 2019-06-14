<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmformatos -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<title>.: SIPRE 2.0 :. Contabilidad - Formatos de Reportes</title>
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
<!--************************VER CONFIGURACION DE REPORTE*************************************-->
<!--*****************************************************************************************-->
function VerConfiguracion(){
document.frmformatos.target='mainFrame';
document.frmformatos.method='post';
document.frmformatos.action='ConfiguracionReportes.php';
document.frmformatos.StatusOculto.value='BU';
document.frmformatos.submit();

}
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
document.frmformatos.target='mainFrame';
document.frmformatos.method='post';
document.frmformatos.action='frmformatos.php';
document.frmformatos.StatusOculto.value='BU';
document.frmformatos.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmformatos.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmformatos.TAValores.value=oArreglo;
document.frmformatos.method='post';
document.frmformatos.target='topFrame';
document.frmformatos.action='BusTablaParametros.php';
document.frmformatos.submit();
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
       document.frmformatos.target='mainFrame';
       document.frmformatos.method='post';
       document.frmformatos.action='frmformatos.php';
      if (sStatus == "LI"){
        document.frmformatos.StatusOculto.value = "LI"
        document.frmformatos.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmformatos))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmformatos))
        {
           return false;
        }
        document.frmformatos.StatusOculto.value = "IN"
        document.frmformatos.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmformatos))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmformatos))
         {
            return false;
         }
         document.frmformatos.StatusOculto.value = "UP"
         document.frmformatos.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmformatos.StatusOculto.value = "DE"
         document.frmformatos.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmformatos.T_formato.value== '' || document.frmformatos.Desha.value == 'readonly'){
           return false;
        }
         document.frmformatos.StatusOculto.value = "BU"
         document.frmformatos.submit();
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

                                   //L I M P I A R
if ($StatusOculto =='LI'){
	$T_formato='';
    $T_descripcion='';
    $Ttitulo_cen='';
}
                                   //I N S E R T
if ($StatusOculto =='IN'){
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='formatos';
        $sValores='';
        $sCampos='';
        $sCampos.='formato';
        $sValores.="'".$T_formato."'";
        $sCampos.=',descripcion';
        $sValores.=",'".$T_descripcion."'";
        $sCampos.=',titulo_cen';
        $sValores.=",'".$Ttitulo_cen."'";
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
    $sTabla='formatos';
    $sCampos='';
    $sCondicion='';
    $sCampos.='formato= '."'".$T_formato."'";
    $sCondicion.='formato= '."'".$T_formato."'";
    $sCampos.=',descripcion= '."'".$T_descripcion."'";
    $sCampos.=',titulo_cen= '."'".$Ttitulo_cen."'";
    $SqlStr='';
    $SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
    $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
  	$Desha = ' readonly class=cTexBoxdisabled';
    echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}
                                   //D E L E T E
if ($StatusOculto =="DE"){
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
	$con = ConectarBD();
	$sTabla='formatos';
	$sCondicion='';
	$sCondicion.='formato= '."'".$T_formato."'";
	$SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	$StatusOculto ='LI';
	$T_formato='';
	$T_descripcion='';
	$Ttitulo_cen='';
}
                                   //B U S C A R
if ($StatusOculto =='BU'){
	$con = ConectarBD();
	$sTabla='formatos';
	$sCondicion='';
	$sCampos.='formato';
	$sCampos.=',descripcion';
	$sCampos.=',titulo_cen';
	$sCondicion.='formato= '."'".$T_formato."'";
	$SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if ( NumeroFilas($exc)>0){
		$StatusOculto = 'UP';
		$T_formato=trim(ObtenerResultado($exc,1));
		$T_descripcion=trim(ObtenerResultado($exc,2));
		$Ttitulo_cen=trim(ObtenerResultado($exc,3));
		$Desha = ' readonly  class=cTexBoxdisabled';
	}else{ // if ( NumeroFilas($exc)>0){
		$StatusOculto ='LI';
		$T_descripcion='';
		$Ttitulo_cen='';
	} // if ( NumeroFilas($exc)>0){
}

?>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->

<table border="0" width="100%">
	<tr>
     	<td class="tituloPaginaContabilidad">
            
<?php
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
?>
			Formatos de los Reportes Financieros (Inclusi&oacute;n)
<?php 
	}else{
		$Desha = 'readonly class=cTexBoxdisabled';
?>
	  		Formatos de los Reportes Financieros (Modificaci&oacute;n)
<?php } ?>
            
    	</td>            
	</tr>
</table>

<form name="frmformatos"action="frmformatos.php"method="post">

<table width="100%" align="center">
	<tr>
		<td>&nbsp;</td>
	</tr>
   <tr>    
		<td><fieldset>
		  	<legend class="legend">Datos del Formato
        	</legend>
		  	<table border="0" align="center">
        		<tr>
       				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>C&oacute;digo:
                    </td>
       				<td align="left">
       					<input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_formato"type="text"maxlength=3 size="10" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_formato?>"> 
                    </td>
   				</tr>
		    	<tr>
       				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Descripci&oacute;n:
                	</td>
       				<td align="left">
       					<input  name="T_descripcion"type="text"maxlength=70 size="100" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_descripcion?>" class="cTexBox"> 
                    </td>
   				</tr>
   				<tr>
       				<td class="tituloCampo" width="140" align="right"> 
                    	T&iacute;tulo de Centro:
                    </td>
       				<td align="left">
       					<input  name="Ttitulo_cen"type="text"maxlength=70 size="100" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$Ttitulo_cen?>" class="cTexBox"> 
                    </td>
   				</tr>
			</table>
			</fieldset>
		</td>
    </tr>
</table>

<table width="100%">
	<tr>
<?php
	$sEjecut= '';
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
		   $sEjecut='IN';
	}else{
		$sEjecut='UP';
	}
?>
		<td align="right"><hr/>
        	<button name=BtnGuardar type="submit" value=Guardar onClick="<?php print("Ejecutar('$sEjecut')");?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            
<?php 
	$Arretabla2[0][0]= 'formatos';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'formato'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_formato';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmformatos'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>
			<button name=BtnBuscar type="submit" value=Buscar onClick="<?php print("PantallaBuscar('T_Codigo','$ArreGeneral')");?>"Class = inputBoton ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button>
            
<?php
	if(!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){ 
?>
  			<button name=BtnIncluir type="submit" value=Incluir onClick="Ejecutar('LI');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Incluir</td></tr></table></button>
  			<button name=BtnEliminar type="submit" value=Eliminar onClick="Ejecutar('DE');" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
  			<button name="BtnVerConfiguracion" type="submit" value="Configuración del Reporte" onClick="VerConfiguracion();" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/diagnostico.png"/></td><td>&nbsp;</td><td>Configuraci&oacute;n del Reporte</td></tr></table></button>		   
        <script language='javascript'>
             document.frmformatos.T_descripcion.focus();
         </script>
<?php 
	}else{ 
?>
<?php 
		if ($T_formato !=  ''){?>
		 <script language='javascript'>
			   document.frmformatos.T_descripcion.focus();
		 </script>
<?php 
		}else{?>
	 	 <script language='javascript'>
			document.frmformatos.T_formato.focus();
		 </script>
<?php 
		} 
?>						  
<?php 
	} 
?>
    	</td>
	</tr>
</table>

<td class=cabecera><input name=StatusOculto type=hidden value=''>
                      <input name=TACondicion type=hidden value=''>
                      <input name=TAValores type=hidden value=''>
                      <input name=Desha type=hidden value="<?= $Desha ?>">
  </td>
</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
