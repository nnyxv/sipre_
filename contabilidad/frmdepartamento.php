<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmdepartamento -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<title>.: SIPRE 2.0 :. Contabilidad - Departamento</title>
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
document.frmdepartamento.target='mainFrame';
document.frmdepartamento.method='post';
document.frmdepartamento.action='frmdepartamento';
document.frmdepartamento.StatusOculto.value='BU';
document.frmdepartamento.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmdepartamento.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmdepartamento.TAValores.value=oArreglo;
document.frmdepartamento.method='post';
document.frmdepartamento.target='topFrame';
document.frmdepartamento.action='BusTablaParametros.php';
document.frmdepartamento.submit();
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
       document.frmdepartamento.target='mainFrame';
       document.frmdepartamento.method='post';
       document.frmdepartamento.action='frmdepartamento.php';
      if (sStatus == "LI"){
        document.frmdepartamento.StatusOculto.value = "LI"
        document.frmdepartamento.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmdepartamento))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmdepartamento))
        {
           return false;
        }
        document.frmdepartamento.StatusOculto.value = "IN"
        document.frmdepartamento.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmdepartamento))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmdepartamento))
         {
            return false;
         }
         document.frmdepartamento.StatusOculto.value = "UP"
         document.frmdepartamento.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmdepartamento.StatusOculto.value = "DE"
         document.frmdepartamento.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmdepartamento.T_codigo.value== '' || document.frmdepartamento.Desha.value == 'readonly'){
           return false;
        }
         document.frmdepartamento.StatusOculto.value = "BU"
         document.frmdepartamento.submit();
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
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='departamento';
        $sValores='';
        $sCampos='';
        $sCampos.='id_departamento';
        $sValores.="'".$T_codigo."'";
        $sCampos.=',nombre_dep';
        $sValores.=",'".$T_descripcion."'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        actualizarCampos('departamento', 1, 'nombre_dep',$T_descripcion);
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //U P D A T E
if ($StatusOculto =='UP')
{
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='departamento';
        $sCampos='';
        $sCondicion='';
        $sCampos.='id_departamento= '."'".$T_codigo."'";
        $sCondicion.='id_departamento= '."'".$T_codigo."'";
        $sCampos.=',nombre_dep= '."'".$T_descripcion."'";
        $SqlStr='';
        $SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        actualizarCampos('departamento', 2, 'id_departamento',$T_codigo);
  $Desha = ' readonly class=cTexBoxdisabled';
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //D E L E T E
if ($StatusOculto =="DE")
{
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='departamento';
        $sCondicion='';
        $sCondicion.='id_departamento= '."'".$T_codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
                actualizarCampos('departamento', 3, 'id_departamento',$T_codigo);
                $StatusOculto ='LI';
                $T_codigo='';
                $T_descripcion='';
}


                                   //B U S C A R
if ($StatusOculto =='BU'){
        $con = ConectarBD();
        $sTabla='departamento';
        $sCondicion='';
        $sCampos.='id_departamento';
        $sCampos.=',nombre_dep';
        $sCondicion.='id_departamento= '."'".$T_codigo."'";
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
<table border="0" width="100%">
	<tr>
    	<td class="tituloPaginaContabilidad">
            
<?php
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
?>
			Departamento Activos (Inclusi&oacute;n)
<?php 
	}else{
		$Desha = 'readonly class=cTexBoxdisabled';
?>
			Departamento Activos (Modificaci&oacute;n)
<?php 
	} 
?>     
    	</td>            
	</tr>
</table>

<form name="frmdepartamento" action="frmdepartamento.php" method="post">

<table width="100%" align="center">
	<tr>
		<td>&nbsp;</td>
	</tr>
   	<tr>    
		<td><fieldset>
		  	<legend class="legend">Datos del Departamento        	</legend>
		  	<table border="0" align="center">
        		<tr>
       				<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>C&oacute;digo:
                    </td>
       				<td align="left">
                    	<input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_codigo"type="text"maxlength=8 size="10" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_codigo?>"> 
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


    </div>
    
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
		<td align="right"><hr/>
        	<button name=BtnGuardar type="submit" value=Guardar onClick="<?php print("Ejecutar('$sEjecut')");?>" class=inputBoton><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            
<?php 
	$Arretabla2[0][0]= 'departamento';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'id_departamento'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'nombre_dep'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_codigo';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmdepartamento'; // Pantalla donde estamos ubicados
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
        		document.frmdepartamento.T_descripcion.focus();
            </script>

<?php 
	}else{ 
?>
<?php 
		if ($T_codigo != ''){ ?>
            <script language='javascript'>
        		document.frmdepartamento.T_descripcion.focus();
  			</script>
<?php 
		}else{ 
?>
            <script language='javascript'>
		        document.frmdepartamento.T_codigo.focus();
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
