<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--T�tulo: frmtransacciones -->

<!--Descripci�n: Formulario individual-->

<!--Copyright: Copyright (c) Corporaci�n Oriomka, C.A. 2006-->

<!--Empresa: Corporaci�n Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporaci�n Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<title>.: SIPRE 2.0 :. Contabilidad - Transacciones</title>
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
<script language="JavaScript"src="GlobalUtility.js">
</script>
</head>

<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->

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
document.frmtransacciones.target='mainFrame';
document.frmtransacciones.method='post';
document.frmtransacciones.action='frmtransacciones';
document.frmtransacciones.StatusOculto.value='BU';
document.frmtransacciones.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmtransacciones.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmtransacciones.TAValores.value=oArreglo;
document.frmtransacciones.method='post';
document.frmtransacciones.target='topFrame';
document.frmtransacciones.action='BusTablaParametros.php';
document.frmtransacciones.submit();
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
       document.frmtransacciones.target='mainFrame';
       document.frmtransacciones.method='post';
       document.frmtransacciones.action='frmtransacciones.php';
      if (sStatus == "LI"){
        document.frmtransacciones.StatusOculto.value = "LI"
        document.frmtransacciones.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmtransacciones))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmtransacciones))
        {
           return false;
        }
        document.frmtransacciones.StatusOculto.value = "IN"
        document.frmtransacciones.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmtransacciones))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmtransacciones))
         {
            return false;
         }
         document.frmtransacciones.StatusOculto.value = "UP"
         document.frmtransacciones.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmtransacciones.StatusOculto.value = "DE"
         document.frmtransacciones.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmtransacciones.T_codigo.value== '' || document.frmtransacciones.Desha.value == 'readonly'){
           return false;
        }
         document.frmtransacciones.StatusOculto.value = "BU"
         document.frmtransacciones.submit();
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
                $T_codigo='';
                $T_descripcion='';
}


                                   //I N S E R T
if ($StatusOculto =='IN')
{
//**********************************************************************
/*C�digo PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='transacciones';
        $sValores='';
        $sCampos='';
        $sCampos.='codigo';
        $sValores.="'".$T_codigo."'";
        $sCampos.=',descripcion';
        $sValores.=",'".$T_descripcion."'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        actualizarCampos('transacciones', 1, 'descripcion',$T_descripcion);
        echo "<script language='javascript'> alert('Operaci�n Realizada con �xito')</script>";
}


                                   //U P D A T E
if ($StatusOculto =='UP')
{
//**********************************************************************
/*C�digo PHP Para Realizar el UPDATE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='transacciones';
        $sCampos='';
        $sCondicion='';
        $sCampos.='codigo= '."'".$T_codigo."'";
        $sCondicion.='codigo= '."'".$T_codigo."'";
        $sCampos.=',descripcion= '."'".$T_descripcion."'";
        $SqlStr='';
        $SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        actualizarCampos('transacciones', 2, 'codigo',$T_codigo);
  $Desha = ' readonly class=cTexBoxdisabled';
        echo "<script language='javascript'> alert('Operaci�n Realizada con �xito')</script>";
}


                                   //D E L E T E
if ($StatusOculto =="DE")
{
//**********************************************************************
/*C�digo PHP Para Realizar el DELETE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='transacciones';
        $sCondicion='';
        $sCondicion.='codigo= '."'".$T_codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
                actualizarCampos('transacciones', 3, 'codigo',$T_codigo);
                $StatusOculto ='LI';
                $T_codigo='';
                $T_descripcion='';
}


                                   //B U S C A R
if ($StatusOculto =='BU'){
        $con = ConectarBD();
        $sTabla='transacciones';
        $sCondicion='';
        $sCampos.='codigo';
        $sCampos.=',descripcion';
        $sCondicion.='codigo= '."'".$T_codigo."'";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if ( NumeroFilas($exc)>0){
              $StatusOculto = 'UP';
                $T_codigo=trim(ObtenerResultado($exc,1));
                $T_descripcion=trim(ObtenerResultado($exc,2));
   $Desha = ' readonly  class=cTexBoxdisabled';
       }else{ // if ( NumeroFilas($exc)>0){
                $StatusOculto ='LI';
                $T_descripcion='';
       } // if ( NumeroFilas($exc)>0){
}


?>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--<link rel='stylesheet' type='text/css' href='./resources/css/ext-all.css'>-->

<div id="divInfo" class="print">
<table border="0" width="100%">
	<tr>
    	<td class="tituloPaginaContabilidad">           
<?php
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
?>
   			Transacciones (Inclusi&oacute;n)
<?php 
	}else{
   		$Desha = 'readonly class=cTexBoxdisabled';
?>
		   Transacciones (Modificaci&oacute;n) 
<?php 
	} 
?>
		</td>            
	</tr>
</table>

<form name="frmtransacciones"action="frmtransacciones.php"method="post">

<table width="100%" align="center">
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
        <td><fieldset>
            <legend class="legend">Datos de la Transacci&oacute;n</legend>
            <table border="0" align="center">
                <tr>    
    				<td class="tituloCampo" width="140" align="right">
                    	 <span class="textoRojoNegrita">*</span>C&oacute;digo:
                    </td>
       				<td align="left">
                    	<input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_codigo"type="text"maxlength=3 size="10" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_codigo?>"> 
                    </td>
   				</tr>
   				<tr>
       				<td class="tituloCampo" width="140" align="right"> 
                    	 <span class="textoRojoNegrita">*</span>Descripci&oacute;n:
                    </td>
       				<td align="left">
                    	<input  name="T_descripcion"type="text"maxlength=60 size="100" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_descripcion?>" class="cTexBox"> 
                   	</td>
   				</tr>
  			</table>
            </fieldset>
		</td>            
	</tr>
</table>

<table width="100%">
	<tr>
<?PHP
	$sEjecut= '';
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
	  $sEjecut='IN';
	}else{
	  $sEjecut='UP';
	}
?>
		<td align="right" width=700><hr/> 
        	<button name=BtnGuardar type="submit" value=Guardar onClick="<?php print("Ejecutar('$sEjecut')");?>" class=inputBoton><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            
<?php 
	$Arretabla2[0][0]= 'transacciones';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'codigo'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_codigo';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmtransacciones'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>
        
        	<button name=BtnBuscar type="submit" value=Buscar onClick="<?php print("PantallaBuscar('T_Codigo','$ArreGeneral')");?>"Class = inputBoton ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button> 
            
<?php
	if (!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){ 
?>

			<button name=BtnIncluir type="submit" value=Incluir onClick="Ejecutar('LI');"class=inputBoton><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Incluir</td></tr></table></button>
			<button name=BtnEliminar type="submit" value=Eliminar onClick="Ejecutar('DE');" class=inputBoton><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
            <script language='javascript'>
        		document.frmtransacciones.T_descripcion.focus();
            </script>
<?php 
	}else{ 
?>
<?php 
		if ($T_codigo != ''){ 
?>
            <script language='javascript'>
		        document.frmtransacciones.T_descripcion.focus();
  			</script>
<?php 
		}else{ 
?>
            <script language='javascript'>
		        document.frmtransacciones.T_codigo.focus();
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
  </div>
  <td class=cabecera><input name=StatusOculto type=hidden value=''>
                      <input name=TACondicion type=hidden value=''>
                      <input name=TAValores type=hidden value=''>
                      <input name=Desha type=hidden value="<?= $Desha ?>">
  </td>
</form>

 <div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
