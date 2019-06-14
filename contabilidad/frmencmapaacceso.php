<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmencmapaacceso -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<script type="text/javascript" src="yui-utilities.js"></script>     
<script type="text/javascript" src="ext-yui-adapter.js"></script>     <!-- ENDLIBS 
<script type="text/javascript" src="ext-all.js"></script>
<script type="text/javascript" src="forms.js"></script>
<link rel="stylesheet" type="text/css" href="forms.css" />
<link rel="stylesheet" type="text/css" href="examples.css" />-->

<title>.: SIPRE 2.0 :. Contabilidad - Mapas de Acceso</title>
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
    winOpen('PantallaBuscarFormulariosAD.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
document.frmencmapaacceso.target='mainFrame';
document.frmencmapaacceso.method='post';
document.frmencmapaacceso.action='frmencmapaacceso.php';
document.frmencmapaacceso.StatusOculto.value='BU';
document.frmencmapaacceso.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscarAD.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmencmapaacceso.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmencmapaacceso.TAValores.value=oArreglo;
document.frmencmapaacceso.method='post';
document.frmencmapaacceso.target='topFrame';
document.frmencmapaacceso.action='BusTablaParametrosAd.php';
document.frmencmapaacceso.submit();
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
<!--**********************************Ver Acceso**************************************-->
<!--*****************************************************************************************-->

function VerAccesos(){
         document.frmencmapaacceso.action = "frmVerAccesos.php";
         document.frmencmapaacceso.submit();
}


<!--*****************************************************************************************-->
<!--**********************************EJECUTAR**************************************-->
<!--*****************************************************************************************-->
function Ejecutar(sStatus){
       document.frmencmapaacceso.target='mainFrame';
       document.frmencmapaacceso.method='post';
       document.frmencmapaacceso.action='frmencmapaacceso.php';
      if (sStatus == "LI"){
        document.frmencmapaacceso.StatusOculto.value = "LI"
        document.frmencmapaacceso.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmencmapaacceso))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmencmapaacceso))
        {
           return false;
        }
        document.frmencmapaacceso.StatusOculto.value = "IN"
        document.frmencmapaacceso.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmencmapaacceso))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmencmapaacceso))
         {
            return false;
         }
         document.frmencmapaacceso.StatusOculto.value = "UP"
         document.frmencmapaacceso.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmencmapaacceso.StatusOculto.value = "DE"
         document.frmencmapaacceso.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmencmapaacceso.T_Codigo.value== '' || document.frmencmapaacceso.Desha.value == 'readonly'){
           return false;
        }
         document.frmencmapaacceso.StatusOculto.value = "BU"
         document.frmencmapaacceso.submit();
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
                $T_Codigo='';
                $T_Descripcion='';
}


                                   //I N S E R T
if ($StatusOculto =='IN')
{
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBDAd();
        $sTabla='encmapaacceso';
        $sValores='';
        $sCampos='';
        $sCampos.='Codigo';
        $sValores.="'".$T_Codigo."'";
        $sCampos.=',Descripcion';
        $sValores.=",'".$T_Descripcion."'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //U P D A T E
if ($StatusOculto =='UP')
{
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
        $con = ConectarBDAd();
        $sTabla='encmapaacceso';
        $sCampos='';
        $sCondicion='';
        $sCampos.='Codigo= '."'".$T_Codigo."'";
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $sCampos.=',Descripcion= '."'".$T_Descripcion."'";
        $SqlStr='';
        $SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
        $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
  $Desha = ' readonly class=cTexBoxdisabled';
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //D E L E T E
if ($StatusOculto =="DE")
{
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
        $con = ConectarBDAd();
        $sTabla='encmapaacceso';
        $sCondicion='';
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
        $sTabla='mapaacceso';
        $sCondicion='';
        $sCondicion.='CodigoMapa= '."'".$T_Codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);

		
                $StatusOculto ='LI';
                $T_Codigo='';
                $T_Descripcion='';
}


                                   //B U S C A R
if ($StatusOculto =='BU'){
        $con = ConectarBDAd();
        $sTabla='encmapaacceso';
        $sCondicion='';
        $sCampos.='Codigo';
        $sCampos.=',Descripcion';
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
        $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
        if ( NumeroFilas($exc)>0){
              $StatusOculto = 'UP';
                $T_Codigo=trim(ObtenerResultado($exc,1));
                $T_Descripcion=trim(ObtenerResultado($exc,2));
   $Desha = ' readonly  class=cTexBoxdisabled';
       }else{ // if ( NumeroFilas($exc)>0){
                $StatusOculto ='LI';
                $T_Descripcion='';
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
   			Mapas de Acceso (Inclusi&oacute;n)
       <?php }else{
   $Desha = 'readonly class=cTexBoxdisabled';
       ?>
		   Mapas de Acceso (Modificaci&oacute;n)
    <?php } ?>
            
		</td>            
	</tr>
</table>

<form name="frmencmapaacceso"action="frmencmapaacceso.php"method="post">
<table width="100%" align="center">
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Datos de Mapa de Acceso</legend>
		  	<table border="0" align="center">
       		 	<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>C&oacute;digo:
                    </td>
                    <td align="left">
                        <input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_Codigo"type="text"maxlength=3 size="10" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Codigo?>"> 
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Descripci&oacute;n:
                    </td>
                    <td align="left">
                        <input  name="T_Descripcion"type="text"maxlength=100 size="100" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Descripcion?>" class="cTexBox"> 
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
		<td align="right"><hr/>
        	<button name=BtnGuardar type="submit" value=Guardar onClick="<?php print("Ejecutar('$sEjecut')");?>" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            
<?php 
	$Arretabla2[0][0]= 'encmapaacceso';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'Codigo'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'Descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_Codigo';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_Descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmencmapaacceso'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>
        	<button name=BtnBuscar type="submit" value=Buscar onClick="<?php print("PantallaBuscar('T_Codigo','$ArreGeneral')");?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button> 
            
<?php
	if (!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){ 
?>
  			<button name=BtnIncluir type="submit" value=Incluir onClick="Ejecutar('LI');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Incluir</td></tr></table></button>
  			<button name=BtnEliminar type="submit" value=Eliminar onClick="Ejecutar('DE');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
  	<button name="BtnVerAccesos " type="submit" value="Ver Accesos" onClick="VerAccesos();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/view.png"/></td><td>&nbsp;</td><td>Ver Accesos</td></tr></table></button>		   
            <script language='javascript'>
        		document.frmencmapaacceso.T_Descripcion.focus();
            </script>
<?php 
	}else{ 
?>
<?php 
		if ($T_Codigo !=  ''){
?>
			 <script language='javascript'>
        		 document.frmencmapaacceso.T_Descripcion.focus();
             </script>
<?php 
		}else{?>
			  <script language='javascript'>
			  	document.frmencmapaacceso.T_Codigo.focus();
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
<p>&nbsp;</p>



<div class="noprint">
 	<?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
