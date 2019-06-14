<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmusuario -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->

<title>.: SIPRE 2.0 :. Contabilidad - Usuarios</title>

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
<script language="JavaScript" src="GlobalUtility.js">
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
document.frmusuario.target='mainFrame';
document.frmusuario.method='post';
document.frmusuario.action='frmusuario.php';
document.frmusuario.StatusOculto.value='BU';
document.frmusuario.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
  function AbrirBus(sObjeto,oArreglo){
      winOpen('PantallaBuscarAD.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmusuario.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmusuario.TAValores.value=oArreglo;
document.frmusuario.method='post';
document.frmusuario.target='topFrame';
document.frmusuario.action='BusTablaParametrosAD.php';
document.frmusuario.submit();
 }// if (Alltrim(sValor) != ''){
 }//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
 
 function AbrirBus2(sObjeto,oArreglo){
      winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){
function BuscarDescrip2(sValor,sCampoBuscar,oArreglo){
    if (Alltrim(sValor) != ''){
document.frmusuario.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
document.frmusuario.TAValores.value=oArreglo;
document.frmusuario.method='post';
document.frmusuario.target='topFrame';
document.frmusuario.action='BusTablaParametros.php';
document.frmusuario.submit();
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
       document.frmusuario.target='mainFrame';
       document.frmusuario.method='post';
       document.frmusuario.action='frmusuario.php';
      if (sStatus == "LI"){
        document.frmusuario.StatusOculto.value = "LI"
        document.frmusuario.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmusuario))
        {
           return false;
        }
        if (CamposBlancosJ(document.frmusuario))
        {
           return false;
        }
        document.frmusuario.StatusOculto.value = "IN"
        document.frmusuario.submit();
      }
      else if (sStatus == "UP")
      {
         if (VerificarFechasJ(document.frmusuario))
         {
            return false;
         }
         if (CamposBlancosJ(document.frmusuario))
         {
            return false;
         }
         document.frmusuario.StatusOculto.value = "UP"
         document.frmusuario.submit();
      }
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmusuario.StatusOculto.value = "DE"
         document.frmusuario.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmusuario.T_Nombre.value== '' || document.frmusuario.Desha.value == 'readonly'){
           return false;
        }
         document.frmusuario.StatusOculto.value = "BU"
         document.frmusuario.submit();
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
                $T_Nombre='';
                $T_Clave='';
                $T_Confirmar='';
                $T_Acceso='';
                $TBloqueo='';
                $T_cedula= '';
                $T_nombusuario= '';
                $T_email= '';
                $Ttelefono= '';
                $Tdepartamento= '';               
                $Textension= '';
                $T_CentroCosto= '';
				$TSolicitud= 0;
}


                                   //I N S E R T
if ($StatusOculto =='IN')
{
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
if(VerificarAcceso(16, "I")){
        $con = ConectarBDAd();
        $sTabla='usuario';
        $sValores='';
        $sCampos='';
        $sCampos.='Nombre';
        $sValores.="'".$T_Nombre."'";
        $sCampos.=',Clave';
        $sValores.=",'".$T_Clave."'";
        $sCampos.=',Acceso';
        $sValores.=",'".$T_Acceso."'";
        $sCampos.=',Bloqueo';
        $sValores.=",'".$TBloqueo."'";
		$sCampos.=',cedula';
        $sValores.=",'".$T_cedula."'";
		$sCampos.=',nombusuario';
        $sValores.=",'".$T_nombusuario."'";
		$sCampos.=',email';
        $sValores.=",'".$T_email."'";
		$sCampos.=',telefono';
        $sValores.=",'".$Ttelefono."'";
        $sCampos.=',departamento';
        $sValores.=",'".$Tdepartamento."'";
        $sCampos.=',extension';
        $sValores.=",'".$Textension."'";
        $sCampos.=',centrocosto';
        $sValores.=",'".$T_CentroCosto."'";
		$sCampos.=',solicitud';
        $sValores.=",'".$TSolicitud."'";
        $SqlStr='';
        $SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
        $exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
        echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}else{
	echo "<script language='javascript'> alert('No tiene permisos para realizar esta operacion')</script>";
}
	//auditoria
	auditoria('insert','usuario',$sCampos);
	//fin auditoria
}

                                   //U P D A T E
if ($StatusOculto =='UP'){	
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
	$con = ConectarBDAd();
	$sTabla='usuario';
	$sCampos='';
	$sCondicion='';
	$sCondicion.='Nombre= '."'".$T_Nombre."'";
	$sCampos.='Nombre= '."'".$T_Nombre."'";
	$sCampos.=',Clave= '."'".$T_Clave."'";
	$sCampos.=',Acceso= '."'".$T_Acceso."'";
	$sCampos.=',Bloqueo= '."'".$TBloqueo."'";
	$sCampos.=',cedula= '."'".$T_cedula."'";
	$sCampos.=',nombusuario= '."'".$T_nombusuario."'";
	$sCampos.=',email= '."'".$T_email."'";
	$sCampos.=',telefono= '."'".$Ttelefono."'";
	$sCampos.=',departamento= '."'".$Tdepartamento."'";
	$sCampos.=',extension= '."'".$Textension."'";
	$sCampos.=',centrocosto= '."'".$T_CentroCosto."'";
	$sCampos.=',solicitud= '."'".$TSolicitud."'";
	$SqlStr='';
	$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
	$exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
	$Desha = ' readonly class=cTexBoxdisabled';
	echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
	
	//auditoria
//	auditoria('update','usuario',$sCampos,'se modifico el usuario '.$T_Nombre);
	auditoria('update','usuario','Nombre, Clave, Acceso, Bloqueo, cedula, nombusuario,emil, telefono,departamento, extension, centrocosto, solicitud','se modifico el usuario '.$T_Nombre);
	//fin auditoria	
}


                                   //D E L E T E
if ($StatusOculto =="DE"){
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
	$con = ConectarBDAd();
	$sTabla='usuario';
	$sCondicion='';
	$sCondicion.='Nombre= '."'".$T_Nombre."'";
	$sCampos='estado = 0';
	//$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
	
	$SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
	$exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
	
	//auditoria
	auditoria('delete','usuario',$sCampos,'se elimino el usuario '.$T_Nombre);
	//fin auditoria
	
	$StatusOculto ='LI';
	$T_Nombre='';
	$T_Clave='';
	$T_Confirmar='';
	$T_Acceso='';
	$TBloqueo='';
	$T_cedula= '';
	$T_nombusuario= '';
	$T_email= '';
	$Ttelefono= '';
	$Tdepartamento= '';
	$Textension= '';
	$T_CentroCosto= '';
	$TSolicitud= 0;
}


                                   //B U S C A R
if ($StatusOculto =='BU'){
	$con = ConectarBDAd();
	$sTabla='usuario';
	$sCondicion='';
	$sCampos.='Nombre';
	$sCampos.=',Clave';
	$sCampos.=',Acceso';
	$sCampos.=',Bloqueo';
	$sCampos.=',cedula';
	$sCampos.=',nombusuario';
	$sCampos.=',email';
	$sCampos.=',telefono';
	$sCampos.=',departamento';
	$sCampos.=',extension';
	$sCampos.=',centrocosto';
	$sCampos.=',Solicitud';
	$sCondicion.='Nombre= '."'".$T_Nombre."'";
	$SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
	$exc = EjecutarExecAd($con,$SqlStr) or die($SqlStr);
	
	//auditoria
	auditoria('select','usuario',$sCampos,'consulta');
        //fin auditoria
	
	if(NumeroFilas($exc)>0){
    	$StatusOculto = 'UP';
		$T_Nombre=trim(ObtenerResultado($exc,1));
		$T_Clave=trim(ObtenerResultado($exc,2));
		$T_Confirmar=trim(ObtenerResultado($exc,2));
		$T_Acceso=trim(ObtenerResultado($exc,3));
		$TBloqueo=trim(ObtenerResultado($exc,4));
		$T_cedula=trim(ObtenerResultado($exc,5));
		$T_nombusuario=trim(ObtenerResultado($exc,6));
		$T_email=trim(ObtenerResultado($exc,7));
		$Ttelefono=trim(ObtenerResultado($exc,8));
		$Tdepartamento=trim(ObtenerResultado($exc,9));
		$Textension=trim(ObtenerResultado($exc,10));
		$T_CentroCosto=trim(ObtenerResultado($exc,11));
		$TSolicitud=trim(ObtenerResultado($exc,12));
   		$Desha = ' readonly  class=cTexBoxdisabled';
	}else{ // if ( NumeroFilas($exc)>0){
		$StatusOculto ='LI';
		$T_Clave='';
		$T_Confirmar='';
		$T_Acceso='';
		$TBloqueo='';
		$T_cedula= '';
		$T_nombusuario= '';
		$T_email= '';
		$Ttelefono= '';
		$Tdepartamento= '';
		$Textension= '';
		$T_CentroCosto= '';
		$TSolicitud= 0;
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
			Usuarios (Inclusi&oacute;n)
<?php 
	}else{
		$Desha = 'readonly class=cTexBoxdisabled';
?>
			Usuarios (modificaci&oacute;n)
<?php 
	} 
	?>        
        </td>            
    </tr>
</table>

<form name="frmusuario"action="frmusuario.php"method="post">				     
<table width="50%" align="left">
	<tr>
		<td>&nbsp;</td>
	</tr>
   	<tr>    
		<td><fieldset>
		  	<legend class="legend">Datos de Acceso</legend>
		  	<table border="0" align="center">
       		 	<tr>
					<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Login:
                    </td>
                    <td align="left">
                    	<input <?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_Nombre"type="text"maxlength=10 size="20" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Nombre?>"> </td>
               </tr>
               <tr>
               		<td class="tituloCampo" width="140" align="right"> 
                   		<span class="textoRojoNegrita">*</span>Password:
                   	</td>
                   	<td align="left">
                   		<input  name="T_Clave" type="password" maxlength=10 size="20" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Clave?>" class="cTexBox"> 
                    </td>
               	</tr>
				<tr>
                	<td class="tituloCampo" width="140" align="right"> 
                   		<span class="textoRojoNegrita">*</span>Confirmar Password:
                   	</td>
                   <td align="left">
                   		<input  name="T_Confirmar" type="password" maxlength=10 size="20" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Confirmar?>" class="cTexBox"> </td>
               	</tr>

<?php 
	$sClaveCon = 'Codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'encmapaacceso'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'Codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'Descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_Acceso'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesAcceso'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmusuario'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre1 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_Acceso."'";
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
	$con = ConectarBDAd();
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExecAd($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesAcceso= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesAcceso= '';
	}
?> 
				<tr>
					<td class="tituloCampo" width="140" align="right"> 
                    	<span class="textoRojoNegrita">*</span>Mapa de Acceso:
                    </td>
					<td align="left">
                   		<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre1')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre1')");?>" name="T_Acceso"type="text"maxlength=3 size=5  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TBloqueo')" value="<?=$T_Acceso?>" class="cTexBox">
        				<input readonly name="TDesAcceso" type="text" size=52 class="cTexBoxdisabled" value="<?=$TDesAcceso?>">
      				</td>
  				</tr>
                <tr>
                    <td class="tituloCampo" width="140" align="right"> 
                    	Bloqueo:
                    </td>
                    <td align="left">
                    	<select name="TBloqueo" size "3" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
                        	<option value=NO>NO</option>
                          	<option value=SI>SI</option>                                
                    	</select>
<?php
	echo"<script language='Javascript'>
          document.frmusuario.TBloqueo.value='$TBloqueo';
    </script>";
?>
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" width="140" align="right"> 
                    	Solicitud Cambio de Clave:
                    </td>
                    <td align="left">
                    	<select name="TSolicitud" size "3" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" class="cTexBox">
                        	<option value=0>NO</option>
                          	<option value=1>SI</option>
                  		</select>
<?php
    echo"<script language='Javascript'>
          document.frmusuario.TSolicitud.value='$TSolicitud';
    </script>";
?>
                    </td>
                </tr>
			</table>
            </fieldset>
            </td>
            </tr>
            </table>
            
<table width="50%" align="right">
            	<tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>
                    <fieldset>
                    <legend class="legend">Datos de Usuario</legend>      
                                                    
                    <table>
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                <span class="textoRojoNegrita">*</span>C&eacute;dula:
                            </td>
                            <td align="left">
                                <input  name="T_cedula" type="text" maxlength=8 size="20" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$T_cedula?>" class="cTexBox"> 
                            </td>
                        </tr>
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                <span class="textoRojoNegrita">*</span>Nombre:
                            </td>
                            <td align="left">
                                <input  name="T_nombusuario" type="text" maxlength=100 size="60" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$T_nombusuario?>" class="cTexBox"> 
                            </td>
                        </tr>
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                <span class="textoRojoNegrita">*</span>Email
                            </td>
                            <td align="left">
                                <input  name="T_email" type="text" maxlength=60 size="60" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$T_email?>" class="cTexBox">
                            </td>
                       </tr>
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                Tel&eacute;fono:
                            </td>
                            <td height=20 class=cabecera align="left">
                                <input  name="Ttelefono" type="text" maxlength=60 size="60" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$Ttelefono?>" class="cTexBox"> 
                            </td>
                        </tr>
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                Departamento:
                            </td>
                            <td align="left">
                                <input  name="Tdepartamento" type="text" maxlength=100 size="60" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$Tdepartamento?>" class="cTexBox"> 
                            </td>
                        </tr>
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                Extensi&oacute;n:
                            </td>
                            <td align="left">
                                <input  name="Textension" type="text" maxlength=100 size="60" onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'')" value="<?=$Textension?>" class="cTexBox"> 
                            </td>
                        </tr>

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
	$Arretabla2[5][0]= 'frmusuario'; // Pantalla donde estamos ubicados
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
                        <tr>
                            <td class="tituloCampo" width="140" align="right"> 
                                <span class="textoRojoNegrita">*</span>Centro de Costo:
                            </td>
                            <td align="left">
                                <input  onDblClick="<?php print("AbrirBus2(this.name,'$Arre2')");?>"  onBlur="<?php print("BuscarDescrip2(this.value,'$sClaveCon','$Arre2')");?>" name="T_CentroCosto"type="text"maxlength=8 size=10  onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'TBloqueo')" value="<?=$T_CentroCosto?>" class="cTexBox">
                    <input readonly name="TDesCentroCosto" type="text" size=52 class="cTexBoxdisabled" value="<?=$TDesCentroCosto?>">
                            </td>
                        </tr>
                    </table>
                    </fieldset>
				</td>
            </tr>
			
		</td>
	</tr>
</table>
	

  </div>
  <table width="100%" cellspacing="2">
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
            
<?php 
	$Arretabla2[0][0]= 'usuario';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'Nombre'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'nombusuario'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_Nombre';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_nombusuario';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmusuario'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>
        	<button name=BtnBuscar type="submit" value=Buscar onClick="<?php print("PantallaBuscar('T_Nombre','$ArreGeneral')");?>" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button> 
<?php
 if (!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){ ?>
  			<button name=BtnIncluir type="submit" value=Incluir onClick="Ejecutar('LI');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Incluir</td></tr></table></button>
  			<button name=BtnEliminar type="submit" value=Eliminar onClick="Ejecutar('DE');" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
	<script language='javascript'>
        document.frmusuario.T_Clave.focus();
    </script>

<?php 
	}else{ ?>

<?php if ($T_Nombre !=  ''){?>
	<script language='javascript'>
        document.frmusuario.T_Clave.focus();
    </script>

<?php }else{?>
	<script language='javascript'>
        document.frmusuario.T_Nombre.focus();
    </script>
<?php } ?>
<?php 
} ?>
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


 <div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>

</body>
</html>
