<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmtipoactivo -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->


<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />
<script type="text/javascript" src="yui-utilities.js"></script>     
<script type="text/javascript" src="ext-yui-adapter.js"></script> -->    <!-- ENDLIBS -->
<!--<script type="text/javascript" src="ext-all.js"></script>
<script type="text/javascript" src="forms.js"></script>
<link rel="stylesheet" type="text/css" href="forms.css" />
<link rel="stylesheet" type="text/css" href="examples.css" />
-->

<title>.: SIPRE 2.0 :. Contabilidad - Tipos de Ativos</title>
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
document.frmtipoactivo.target='mainFrame';
document.frmtipoactivo.method='post';
document.frmtipoactivo.action='frmtipoactivo.php';
document.frmtipoactivo.StatusOculto.value='BU';
document.frmtipoactivo.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmtipoactivo.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmtipoactivo.TAValores.value=oArreglo;
document.frmtipoactivo.method='post';
document.frmtipoactivo.target='topFrame';
document.frmtipoactivo.action='BusTablaParametros.php';
document.frmtipoactivo.submit();
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
       document.frmtipoactivo.target='mainFrame';
       document.frmtipoactivo.method='post';
       document.frmtipoactivo.action='frmtipoactivo.php';
      if (sStatus == "LI"){
        document.frmtipoactivo.StatusOculto.value = "LI"
        document.frmtipoactivo.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmtipoactivo))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmtipoactivo))
        {
           return false;
        }
        document.frmtipoactivo.StatusOculto.value = "IN"
        document.frmtipoactivo.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmtipoactivo))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmtipoactivo))
         {
            return false;
         }
         document.frmtipoactivo.StatusOculto.value = "UP"
         document.frmtipoactivo.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmtipoactivo.StatusOculto.value = "DE"
         document.frmtipoactivo.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmtipoactivo.T_Codigo.value== '' || document.frmtipoactivo.Desha.value == 'readonly'){
           return false;
        }
         document.frmtipoactivo.StatusOculto.value = "BU"
         document.frmtipoactivo.submit();
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
                $T_CodDebe='';
                $T_CodHaber='';
				$T_MesesDepre='';
}


                                   //I N S E R T
if ($StatusOculto =='IN')
{
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='tipoactivo';
        $sValores='';
        $sCampos='';
        $sCampos.='Codigo';
        $sValores.="'".$T_Codigo."'";
        $sCampos.=',Descripcion';
        $sValores.=",'".$T_Descripcion."'";
        $sCampos.=',CodDebe';
        $sValores.=",'".$T_CodDebe."'";
        $sCampos.=',CodHaber';
        $sValores.=",'".$T_CodHaber."'";
		$sCampos.=',MesesDepre';
        $sValores.=",'".$T_MesesDepre."'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //U P D A T E
if ($StatusOculto =='UP')
{
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='tipoactivo';
        $sCampos='';
        $sCondicion='';
        $sCampos.='Codigo= '."'".$T_Codigo."'";
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $sCampos.=',Descripcion= '."'".$T_Descripcion."'";
        $sCampos.=',CodDebe= '."'".$T_CodDebe."'";
        $sCampos.=',CodHaber= '."'".$T_CodHaber."'";
		$sCampos.=',MesesDepre= '."'".$T_MesesDepre."'";
        $SqlStr='';
        $SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
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
        $sTabla='tipoactivo';
        $sCondicion='';
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
                $StatusOculto ='LI';
                $T_Codigo='';
                $T_Descripcion='';
                $T_CodDebe='';
                $T_CodHaber='';
				$T_MesesDepre='';

}


                                   //B U S C A R
if ($StatusOculto =='BU'){
        $con = ConectarBD();
        $sTabla='tipoactivo';
        $sCondicion='';
        $sCampos.='Codigo';
        $sCampos.=',Descripcion';
        $sCampos.=',CodDebe';
        $sCampos.=',CodHaber';
		$sCampos.=',MesesDepre';
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
        if ( NumeroFilas($exc)>0){
              $StatusOculto = 'UP';
                $T_Codigo=trim(ObtenerResultado($exc,1));
                $T_Descripcion=trim(ObtenerResultado($exc,2));
                $T_CodDebe=trim(ObtenerResultado($exc,3));
                $T_CodHaber=trim(ObtenerResultado($exc,4));
				$T_MesesDepre=trim(ObtenerResultado($exc,5));
   $Desha = ' readonly  class=cTexBoxdisabled';
       }else{ // if ( NumeroFilas($exc)>0){
                $StatusOculto ='LI';
                $T_Descripcion='';
                $T_CodDebe='';
                $T_CodHaber='';
				$T_MesesDepre='';
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
    		Tipos de Activos (Inclusi&oacute;n)
<?php 
	}else{
		$Desha = 'readonly class=cTexBoxdisabled';
?>
    		Tipos de Activos (Modificaci&oacute;n)
<?php 
	} 
?>
		</td>            
	</tr>
</table>

<form name="frmtipoactivo"action="frmtipoactivo.php"method="post">
<table width="100%" align="center">
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Datos del Tipo de Activo</legend>
		  	<table border="0" align="center">
        		<tr>       
    				<td class="tituloCampo" width="140" align="right">
                    	<span class="textoRojoNegrita">*</span>C&oacute;digo:
                    </td>
					<td align="left">
                    	<input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_Codigo"type="text"maxlength=20 size="20" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Codigo?>">
                    </td>
				</tr>
				<tr>
    				<td class="tituloCampo" width="140" align="right">
                    	<span class="textoRojoNegrita">*</span>Descripci&oacute;n:
                    </td>
					<td align="left">
                    	<input  name="T_Descripcion"type="text"maxlength=100 size="55" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Descripcion?>" class="cTexBox">
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
	$Arretabla1[3][0]= 'T_CodDebe'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCodDebe'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmtipoactivo'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre1 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CodDebe."'";
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
		 $TDesCodDebe= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCodDebe= '';
	}
?> 
				<tr>
    				<td class="tituloCampo" width="140" align="right">
                    	<span class="textoRojoNegrita">*</span>C&oacute;digo Debe:
                    </td>
        			<td align="left">
                		<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre1')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre1')");?>" name="T_CodDebe"type="text"maxlength=80 size=15  onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'T_CodHaber')" value="<?=$T_CodDebe?>" class="cTexBox">
						<input readonly name="TDesCodDebe"type="text" size=55 class="cTexBoxdisabled" value="<?=$TDesCodDebe?>"> 
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
	$Arretabla1[3][0]= 'T_CodHaber'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesCodHaber'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmtipoactivo'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre2 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_CodHaber."'";
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
		 $TDesCodHaber= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesCodHaber= '';
	}
?> 
				<tr>
       				<td class="tituloCampo" width="140" align="right">
                    	<span class="textoRojoNegrita">*</span>C&oacute;digo Haber:
                    </td>
					<td align="left">
                    	<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre2')");?>" name="T_CodHaber"type="text"maxlength=80 size=15  onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'BtnGuardar')" value="<?=$T_CodHaber?>" class="cTexBox">
						<input readonly name="TDesCodHaber"type="text" size=55 class="cTexBoxdisabled" value="<?=$TDesCodHaber?>">
                    </td>
				</tr>
    			<tr>
       				<td class="tituloCampo" width="140" align="right">
                    	<span class="textoRojoNegrita">*</span>Meses a Depreciar:
                    </td>
					<td align="left">
                    	<input  name="T_MesesDepre"type="text"maxlength=100 size="100" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_MesesDepre?>" class="cTexBox">
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
	$Arretabla2[0][0]= 'tipoactivo';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'Codigo'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'Descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_Codigo';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_Descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmtipoactivo'; // Pantalla donde estamos ubicados
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
    			 document.frmtipoactivo.T_Descripcion.focus();
             </script>
<?php 
	}else{ ?>
<?php 
		if ($T_Codigo !=  ''){?>
	   		<script language='javascript'>
				 document.frmtipoactivo.T_Descripcion.focus();
			 </script>
<?php 
		}else{
?>
			<script language='javascript'>
			  document.frmtipoactivo.T_Codigo.focus();
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


<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
