<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmdeprecactivos -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2006-->

<!--Empresa: Corporación Oriomka, C.A. www.oriomka.net Telf:(0212)7618494-7627666-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--Autor: Desarrollado por Ernesto Garcia 0416-4197573 / 0414-0106485-->

<!--@version 1.0-->


<title>.: SIPRE 2.0 :. Contabilidad - Depreciacion Activos</title>
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

function PnatallaImportarExcel(){
	window.open("frmImportarExcel.php", "_blank", "toolbar=yes, scrollbars=yes, resizable=yes, width=650, height=250");
	//winOpen('frmImportarExcel.php','Cargar Excel', true,800, 600, true, false, true, true);
}


<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
	document.frmdeprecactivos.target='mainFrame';
	document.frmdeprecactivos.method='post';
	document.frmdeprecactivos.action='frmdeprecactivos.php';
	document.frmdeprecactivos.StatusOculto.value='BU';
	document.frmdeprecactivos.submit();
}// function Buscar


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
function AbrirBus(sObjeto,oArreglo){
	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,+'');
}// function AbrirBus(sObjeto,oArreglo){
  
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
	if (Alltrim(sValor) != ''){
		document.frmdeprecactivos.TACondicion.value=sCampoBuscar +"= '" + sValor + "'"
		document.frmdeprecactivos.TAValores.value=oArreglo;
		document.frmdeprecactivos.method='post';
		document.frmdeprecactivos.target='topFrame';
		document.frmdeprecactivos.action='BusTablaParametros.php';
		document.frmdeprecactivos.submit();
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

	function validarActivo(){
		var NCompAdquisicion = document.getElementsByName("NCompAdquisicion")[0].value;
		var N_MesesDepre = document.getElementsByName("N_MesesDepre")[0].value;
		var NValDeprec = document.getElementsByName("NValDeprec")[0].value;
		var NDepreMensual = document.getElementsByName("NDepreMensual")[0].value;
		//ert(NDepreMensual);
		/*if(NCompAdquisicion == 0.00 || N_MesesDepre == 0.00 || NValDeprec == 0.00 || NDepreMensual == 0.00 ) {
		alert('Ninguno de los activod con * no deben estar en Cero');
			return false;
		} else {
			return true;
			} */
			
		if(NValDeprec == 0.00){
			alert('El valor a depreciar no puede ser cero (0), haga click en el campo de COSTO HISTORICO y luego en cualquier área vacía del formulario para que se recalcule');
			return false;
		} else {
			return true;
		}
		if(NDepreMensual == 0.00){
			alert('El valor Depre. Mensual no puede ser cero (0), haga click en el campo de VIDA UTIL y luego en cualquier área vacía del formulario para que se recalcule');
			return false;
		} else {
			return true;
		}
	}
  
<!--*****************************************************************************************-->
<!--**********************************EJECUTAR**************************************-->
<!--*****************************************************************************************-->


function Ejecutar(sStatus){
       document.frmdeprecactivos.target='mainFrame';
       document.frmdeprecactivos.method='post';
       document.frmdeprecactivos.action='frmdeprecactivos.php';
      if (sStatus == "LI"){
        document.frmdeprecactivos.StatusOculto.value = "LI"
        document.frmdeprecactivos.submit();
      }
      if (sStatus == "IN"){
        if (VerificarFechasJ(document.frmdeprecactivos)){
           return false;
        }
        if (CamposBlancosJ(document.frmdeprecactivos)){
           return false;
        }
        document.frmdeprecactivos.StatusOculto.value = "IN"
        document.frmdeprecactivos.submit();
      }else if (sStatus == "UP"){
         if (VerificarFechasJ(document.frmdeprecactivos)){
            return false;
         }
         if (CamposBlancosJ(document.frmdeprecactivos)){
            return false;
         }
         document.frmdeprecactivos.StatusOculto.value = "UP"
         document.frmdeprecactivos.submit();

      }
	  
	  
      else if (sStatus == "DE"){
        if (confirm('Desea Eliminar el registro')){
         document.frmdeprecactivos.StatusOculto.value = "DE"
         document.frmdeprecactivos.submit();
        }
      }
     else if (sStatus == 'BU'){
         if (document.frmdeprecactivos.T_Codigo.value== '' || document.frmdeprecactivos.Desha.value == 'readonly'){
           return false;
        }
         document.frmdeprecactivos.StatusOculto.value = "BU"
         document.frmdeprecactivos.submit();
      }
	  
	   else if (sStatus == 'IMP'){
		
        document.frmdeprecactivos.StatusOculto.value = "IMP"
        document.frmdeprecactivos.submit();
      }
}
function mesesDepreciar(obj){
validar(obj);
       	if(document.frmdeprecactivos.NValDeprec.value!='0.00'){
			var valormes = document.frmdeprecactivos.N_MesesDepre.value;
			var valodep = document.frmdeprecactivos.NValDeprec.value;
			
			valormes = valormes.toString().replace(/\$|\,/g,'');
			valormes=parseFloat(valormes);
			valodep = valodep.toString().replace(/\$|\,/g,'');
			valodep=parseFloat(valodep);
			var totaldepmes =	(valodep/valormes);
			document.frmdeprecactivos.NDepreMensual.value = totaldepmes;
			document.frmdeprecactivos.NDepreMensual.blur();
		}
}

function valorResidual(obj){
validar(obj);
             var valorad = document.frmdeprecactivos.NCompAdquisicion.value;
			var valorres = document.frmdeprecactivos.NValResidual.value;
			
			valorad = valorad.toString().replace(/\$|\,/g,'');
			valorad=parseFloat(valorad);
			valorres = valorres.toString().replace(/\$|\,/g,'');
			valorres=parseFloat(valorres);
			var totaldep =	valorad - valorres;
				document.frmdeprecactivos.NValDeprec.value = totaldep;
				document.frmdeprecactivos.NValDeprec.blur();
}
 
function valorAdquisicion(obj){
validar(obj);
			var valorad = document.frmdeprecactivos.NCompAdquisicion.value;
			var valorres = document.frmdeprecactivos.NValResidual.value;
			
			valorad = valorad.toString().replace(/\$|\,/g,'');
			valorad=parseFloat(valorad);
			valorres = valorres.toString().replace(/\$|\,/g,'');
			valorres=parseFloat(valorres);
			var totaldep =	valorad - valorres;
				document.frmdeprecactivos.NValDeprec.value = totaldep;
				document.frmdeprecactivos.NValDeprec.blur();

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
	$T_Codigo='0';
	$T_Tipo='';
	$xDFecha=date('d');
	$xMFecha=date('m');
	$xAFecha=date('Y');
	$xDFechaDepre=date('d');
	$xMFechaDepre=date('m');
	$xAFechaDepre=date('Y');
	$T_Comprobante='';
	$NCompAdquisicion='0.00';
	$NValResidual='0.00';
	$N_MesesDepre='0.00';
	$NValDeprec='0.00';
	$NDepreMensual='0.00';
	$T_Descripcion='';
	$T_Modelo='';
	$T_Serial='';
	$TDepartamento='';
	$TResponsable='';
	$TUbicacion='';
	$TProveedor='';
	$TObservaciones='';
	$TNodeprec='';
}

                                   //I N S E R T
if ($StatusOculto =='IN'){
//**********************************************************************
/*Código PHP Para Realizar el INSERT*/
//**********************************************************************
	$con = ConectarBD();
	$sTabla='deprecactivos';
	$sValores='';
	$sCampos='';
	/*        $sCampos.='Codigo';
	$sValores.="'".$T_Codigo."'";
	*/        $sCampos.='Tipo';
	$sValores.="'".$T_Tipo."'";
	$sCampos.=',Fecha';
	$sValores.=",'".$xAFecha. '-' .$xMFecha. '-' .$xDFecha."'";
	$sCampos.=',FechaDepre';
	$sValores.=",'".$xAFechaDepre. '-' .$xMFechaDepre. '-' .$xDFechaDepre."'";
	$sCampos.=',Comprobante';
	$sValores.=",'".$T_Comprobante."'";
	$sCampos.=',CompAdquisicion';
	$sValores.=",'".str_replace(',','',$NCompAdquisicion)."'";
	$sCampos.=',ValResidual';
	$sValores.=",'".str_replace(',','',$NValResidual)."'";
	$sCampos.=',MesesDepre';
	$sValores.=",'".str_replace(',','',$N_MesesDepre)."'";
	$sCampos.=',ValDeprec';
	$sValores.=",'".str_replace(',','',$NValDeprec)	."'";
	$sCampos.=',DepreMensual';
	$sValores.=",'".str_replace(',','',$NDepreMensual)."'";
	$sCampos.=',Descripcion';
	$sValores.=",'".$T_Descripcion."'";
	$sCampos.=',modelo';
	$sValores.=",'".$T_Modelo."'";
	$sCampos.=',serial';
	$sValores.=",'".$T_Serial."'";
	$sCampos.=',Departamento';
	$sValores.=",'".$TDepartamento."'";
	$sCampos.=',Responsable';
	$sValores.=",'".$TResponsable."'";
	$sCampos.=',Ubicacion';
	$sValores.=",'".$TUbicacion."'";
	$sCampos.=',Proveedor';
	$sValores.=",'".$TProveedor."'";
	$sCampos.=',Observaciones';
	$sValores.=",'".$TObservaciones."'";
	$sCampos.=',Nodeprec';
	$sValores.=",'".$TNodeprec."'";
	$SqlStr='';
	$SqlStr="INSERT INTO ".$sTabla." (".$sCampos.")  values (".$sValores.")";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	if($TNodeprec == "SI"){
		$T_Codigo = mysql_insert_id();
		crearDepreciacion($T_Codigo);
	} else {}
		echo "<script language='javascript'> alert('Operación Realizada con éxito')</script>";
}


                                   //U P D A T E
if ($StatusOculto =='UP')
{
//**********************************************************************
/*Código PHP Para Realizar el UPDATE*/
//**********************************************************************
	$con = ConectarBD();
	$sTabla='deprecactivos';
	$sCampos='';
	$sCondicion='';
	$sCampos.='Codigo= '."'".$T_Codigo."'";
	$sCondicion.='Codigo= '."'".$T_Codigo."'";
	$sCampos.=',Tipo= '."'".$T_Tipo."'";
	$sCampos.=',Fecha= '."'".$xAFecha. '-' .$xMFecha. '-' .$xDFecha."'";
	$sCampos.=',FechaDepre= '."'".$xAFechaDepre. '-' .$xMFechaDepre. '-' .$xDFechaDepre."'";/**/
	$sCampos.=',Comprobante= '."'".$T_Comprobante."'";
	$sCampos.=',CompAdquisicion= '."'".str_replace(',','',$NCompAdquisicion)."'";
	$sCampos.=',ValResidual= '."'".str_replace(',','',$NValResidual)."'";
	$sCampos.=',MesesDepre= '."'".str_replace(',','',$N_MesesDepre)."'";
	$sCampos.=',ValDeprec= '."'".str_replace(',','',$NValDeprec)."'";
	$sCampos.=',DepreMensual= '."'".str_replace(',','',$NDepreMensual)."'";
	$sCampos.=',Descripcion= '."'".$T_Descripcion."'";
	$sCampos.=',modelo= '."'".$T_Modelo."'";
	$sCampos.=',serial= '."'".$T_Serial."'";
	$sCampos.=',Departamento= '."'".$TDepartamento."'";
	$sCampos.=',Responsable= '."'".$TResponsable."'";
	$sCampos.=',Ubicacion= '."'".$TUbicacion."'";
	$sCampos.=',Proveedor= '."'".$TProveedor."'";
	$sCampos.=',Observaciones= '."'".$TObservaciones."'";
	$sCampos.=',Nodeprec= '."'".$TNodeprec."'";
	$sCampos.=',estatus= '."'". 0 ."'";
	$SqlStr='';
	eliminarDepreciacion($T_Codigo);
	$SqlStr="UPDATE ".$sTabla." SET ".$sCampos." WHERE ".$sCondicion."";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	crearDepreciacion($T_Codigo);
	$Desha = ' readonly class=cTexBoxdisabled';
		echo "<script language='javascript'> 
			alert('Operación Realizada con éxito');
	</script>";
		
}


                                   //D E L E T E
if ($StatusOculto =="DE")
{
//**********************************************************************
/*Código PHP Para Realizar el DELETE*/
//**********************************************************************
        $con = ConectarBD();
        $sTabla='deprecactivos';
        $sCondicion='';
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $SqlStr="DELETE FROM ".$sTabla." WHERE ".$sCondicion."";
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		$StatusOculto ='LI';
		$T_Codigo='0';
		$T_Tipo='';
		$xDFecha=date('d');
		$xMFecha=date('m');
		$xAFecha=date('Y');
		/*$xDFechaDepre=date('d');
		$xMFechaDepre=date('m');
		$xAFechaDepre=date('Y');*/
		
		$fechaDepreciacion = diaUnoMesSiguiente($xDFecha,$xMFecha,$xAFecha);
		$xDFechaDepre=$fechaDepreciacion['xDFechaDepre'];
		$xMFechaDepre=$fechaDepreciacion['xMFechaDepre'];
		$xAFechaDepre=$fechaDepreciacion['xAFechaDepre'];
		
		$T_Comprobante='';
		$NCompAdquisicion='0.00';
		$NValResidual='0.00';
		$N_MesesDepre='0.00';
		$NValDeprec='0.00';
		$NDepreMensual='0.00';
		$T_Descripcion='';
		$T_Modelo='';
		$T_Serial='';
		$TDepartamento='';
		$TResponsable='';
		$TUbicacion='';
		$TProveedor='';
		$TObservaciones='';
		$TNodeprec='';
}


if ($StatusOculto =="IMP")
{
//**********************************************************************
/*IMPRIMIR ETIQUETA*/
//**********************************************************************
echo "<script language='javascript'> window.open('activo_etiqueta_pdf.php?codigo=".$T_Codigo."','MyWindow', 'location=1,status=1,scrollbars=1, width=300,height=300');</script>";

}

                                   //B U S C A R
if ($StatusOculto =='BU'){
	
        $con = ConectarBD();
        $sTabla='deprecactivos';
        $sCondicion='';
        $sCampos.='Codigo';
        $sCampos.=',Tipo';
        $sCampos.=',Fecha';
        $sCampos.=',FechaDepre';
        $sCampos.=',Comprobante';
        $sCampos.=',CompAdquisicion';
        $sCampos.=',ValResidual';
        $sCampos.=',MesesDepre';
        $sCampos.=',ValDeprec';
        $sCampos.=',DepreMensual';
        $sCampos.=',Descripcion';
        $sCampos.=',Ubicacion';
        $sCampos.=',Proveedor';
        $sCampos.=',Observaciones';
        $sCampos.=',Nodeprec';
		$sCampos.=',Departamento';
		$sCampos.=',Responsable';
		$sCampos.=',modelo';
		$sCampos.=',serial';
		$sCampos.=',estatus';
        $sCondicion.='Codigo= '."'".$T_Codigo."'";
        $SqlStr='Select '.$sCampos.' from '.$sTabla. ' Where ' .$sCondicion;
        $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
        if (NumeroFilas($exc)>0){
              $StatusOculto = 'UP';
                $T_Codigo=trim(ObtenerResultado($exc,1));
                $T_Tipo=trim(ObtenerResultado($exc,2));
                $xDFecha=obFecha(ObtenerResultado($exc,3),'D');
                $xMFecha=obFecha(ObtenerResultado($exc,3),'M');
                $xAFecha=obFecha(ObtenerResultado($exc,3),'A');
				
				$xDFechaDepre=obFecha(ObtenerResultado($exc,4),'D');
				$xMFechaDepre=obFecha(ObtenerResultado($exc,4),'M');
				$xAFechaDepre=obFecha(ObtenerResultado($exc,4),'A');

				if($xDFechaDepre == "" && $xMFechaDepre == "" && $xAFechaDepre == ""){
				$fechaDepreciacion = diaUnoMesSiguiente($xDFecha,$xMFecha,$xAFecha);
				$xDFechaDepre=$fechaDepreciacion['xDFechaDepre'];
				$xMFechaDepre=$fechaDepreciacion['xMFechaDepre'];
				$xAFechaDepre=$fechaDepreciacion['xAFechaDepre'];
				}		
						
                $T_Comprobante=trim(ObtenerResultado($exc,5));
                $NCompAdquisicion=trim(ObtenerResultado($exc,6));
                $NValResidual=trim(ObtenerResultado($exc,7));
                $N_MesesDepre=trim(ObtenerResultado($exc,8));
				$NValDeprec=trim(ObtenerResultado($exc,9));
                $NDepreMensual=trim(ObtenerResultado($exc,10));
                $T_Descripcion=trim(ObtenerResultado($exc,11));
                $TUbicacion=trim(ObtenerResultado($exc,12));
                $TProveedor=trim(ObtenerResultado($exc,13));
                $TObservaciones=trim(ObtenerResultado($exc,14));
                $TNodeprec=trim(ObtenerResultado($exc,15));
				$TDepartamento=trim(ObtenerResultado($exc,16));
				$TResponsable=trim(ObtenerResultado($exc,17));
				$T_Modelo=trim(ObtenerResultado($exc,18));
				$T_Serial=trim(ObtenerResultado($exc,19));
				$T_estatus=trim(ObtenerResultado($exc,20));
				
				
   $Desha = ' readonly  class=cTexBoxdisabled';
       }else{ // if ( NumeroFilas($exc)>0){ AQUI CARGO EL FORMULARIO CUANDO SE REGISTRA POR 1ER VES
                $StatusOculto ='LI';
                $T_Tipo='';
				$T_Codigo='0';
                $xDFecha=date('d');
                $xMFecha=date('m');
                $xAFecha=date('Y');
				
				$fechaDepreciacion = diaUnoMesSiguiente($xDFecha,$xMFecha,$xAFecha);
				$xDFechaDepre=$fechaDepreciacion['xDFechaDepre'];
                $xMFechaDepre=$fechaDepreciacion['xMFechaDepre'];
                $xAFechaDepre=$fechaDepreciacion['xAFechaDepre'];
				
                //$xDFechaDepre=date('d');
                //$xMFechaDepre=date('m');
                //$xAFechaDepre=date('Y');
				
                $T_Comprobante='';
                $NCompAdquisicion='0.00';
                $NValResidual='0.00';
                $N_MesesDepre='0.00';
                $NValDeprec='0.00';
                $NDepreMensual='0.00';
                $T_Descripcion='';
				$T_Modelo='';
				$T_Serial='';
                $TUbicacion='';
				$TDepartamento='';
				$TResponsable='';
                $TProveedor='';
                $TObservaciones='';
                $TNodeprec='';		
				
				/*echo '<script language="javascript">alert("es este");</script>';*/	
       } // if ( NumeroFilas($exc)>0){
}/*else{
	
	activoIncompleto();
		
}*/

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
			Depreciaci&oacute;n Activos (Inclusi&oacute;n)
<?php 
	}else{
   		$Desha = 'readonly class=cTexBoxdisabled';
?>
			Depreciaci&oacute;n Activos (Modificaci&oacute;n)
<?php 
	} 
?>  
    	</td>            
	</tr>
</table>

<form name="frmdeprecactivos" action="frmdeprecactivos.php" method="post">

<table width="100%" align="center">
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>        
		<td><fieldset>
		  	<legend class="legend">Datos de Depreciaci&oacute;n</legend>
		  	<table border="0" align="center">
       		 	<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>C&oacute;digo:
                    </td>
                    <td align="left">
                        <input readonly="true"<?= $Desha ?>  onBlur="Ejecutar('BU');" name="T_Codigo"type="text"maxlength=6 size="10" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')"  value="<?php if($T_Codigo){echo $T_Codigo;} else { echo '0';} ?>" class="cNum"> 
                    </td>
                    <td class="tituloCampo" width="140" align="right">
                        <span class="textoRojoNegrita">*</span>Descripci&oacute;n:
                    </td>
                    <td  class=cabecera align="left">
                        <input  name="T_Descripcion"type="text"maxlength=100 size="46" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Descripcion?>"  class="cTexBox"> 
                    </td>
   				</tr>
                
<?php 
	$sClaveCon = 'Codigo'; // Campo Clave para buscar
	$Arretabla1[0][0]= 'tipoactivo'; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= 'Codigo'; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= 'Descripcion'; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= 'T_Tipo'; //objeto Campo1
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= 'TDesTipo'; //objeto Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmdeprecactivos'; // Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	
	
	$Arre1 = array_envia($Arretabla1); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$T_Tipo."'";
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
		 $TDesTipo= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesTipo= '';
	}
?> 
	
				<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Tipo de Activo:
                    </td>
                    <td align="left">
                    	<input  onDblClick="<?php print("AbrirBus(this.name,'$Arre1')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre1')");?>" name="T_Tipo"type="text"maxlength=3 size=5  onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'xDFecha')" value="<?=$T_Tipo?>" class="cTexBox">
                    <input readonly name="TDesTipo"type="text" size=35 class="cTexBoxdisabled" value="<?=$TDesTipo?>">
                    
                    </td>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Modelo:
                    </td>
                    <td align="left"><input  name="T_Modelo"type="text"maxlength=80 size="46" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Modelo?>"  class="cTexBox"> 
                    </td>
  				</tr>
                <tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        Fecha de Compra: 
                    </td>
                    <td  class=cabecera align="left">
                        <input  name="xDFecha"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFecha?>" class="cNum">
                        <input  name="xMFecha"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFecha?>" class="cNum">
                        <input  name="xAFecha"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFecha?>" class="cNum">
                    </td>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Serial:
                    </td>
                    <td align="left">
                        <input  name="T_Serial"type="text"maxlength=100 size="46" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Serial?>"  class="cTexBox"> 
                    </td>
				</tr>
				<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        Inicia Depreciaci&oacute;n:
                    </td>
                    <td align="left">
                       <input  name="xDFechaDepre"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xDFechaDepre?>" class="cNum">
                       <input  name="xMFechaDepre"type="text"maxlength=2 size=1 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xMFechaDepre?>" class="cNum">
                        <input  name="xAFechaDepre"type="text"maxlength=4 size=4 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" value="<?=$xAFechaDepre?>" class="cNum"></p>
                    </td>
                    
  <?php 
$sClaveCon = 'id_ubicacion'; // Campo Clave para buscar
$Arretabla5[0][0]= 'ubicacion'; //Tabla
$Arretabla5[0][1]= 'T';
$Arretabla5[1][0]= 'id_ubicacion'; //Campo1
$Arretabla5[1][1]= 'C';
$Arretabla5[2][0]= 'ubicacion'; //Campo2
$Arretabla5[2][1]= 'C';
$Arretabla5[3][0]= 'TUbicacion'; //objeto Campo1
$Arretabla5[3][1]= 'O';
$Arretabla5[4][0]= 'TDesUbicacion'; //objeto Campo2
$Arretabla5[4][1]= 'O';
$Arretabla5[5][0]= 'frmdeprecactivos'; // Pantalla donde estamos ubicados
$Arretabla5[5][1]= 'P';
$Arre5 = array_envia($Arretabla5); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
$sTabla = '';
$sCampos= '';
$sPlantillaBus= '';
$sCondicion = $sClaveCon ."= '".$TUbicacion."'";
$ArrRec =$Arretabla5;
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
     $TDesUbicacion= ObtenerResultado($rsTem1,2);
}else{
     $TDesUbicacion= '';
}
  ?>                   
                    
                    
                    
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Ubicaci&oacute;n:
                    </td>
                    <td align="left">
                        <input  onDblClick="<?php print("AbrirBus(this.name,'$Arre5')");?>" onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre5')");?>" name="TUbicacion"type="text"maxlength=3 size=5 onFocus="SelTexto(this);" value="<?=$TUbicacion?>" class="cTexBox">
                        <input readonly name="TDesUbicacion"type="text" size=46 class="cTexBoxdisabled" value="<?=$TDesUbicacion?>">
                    </td>
                </tr>
				<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>N&deg;. Factura:
                    </td>
                    <td align="left">
                        <input  name="T_Comprobante"type="text"maxlength="12" size="11" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$T_Comprobante?>" class="cTexBox"> 
                    </td>
                    
<?php 
$sClaveCon = 'id_departamento'; // Campo Clave para buscar
$Arretabla3[0][0]= 'sipre_automotriz.pg_departamento '; //Tabla
$Arretabla3[0][1]= 'T';
$Arretabla3[1][0]= 'id_departamento'; //Campo1
$Arretabla3[1][1]= 'C';
$Arretabla3[2][0]= 'nombre_departamento'; //Campo2
$Arretabla3[2][1]= 'C';
$Arretabla3[3][0]= 'TDepartamento'; //objeto Campo1
$Arretabla3[3][1]= 'O';
$Arretabla3[4][0]= 'TDesDepartamento'; //objeto Campo2
$Arretabla3[4][1]= 'O';
$Arretabla3[5][0]= 'frmdeprecactivos'; // Pantalla donde estamos ubicados
$Arretabla3[5][1]= 'P';
$Arre3 = array_envia($Arretabla3); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
$sTabla = '';
$sCampos= '';
$sPlantillaBus= '';
$sCondicion = $sClaveCon ."= '".$TDepartamento."'";
$ArrRec =$Arretabla3;
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
$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' . $sCondicion ;
	//echo $sCondicion.'<br>';
	//echo $sql; 
$rsTem1 = EjecutarExec($con,$sql);
if (NumeroFilas($rsTem1) > 0){
     $TDesDepartamento= ObtenerResultado($rsTem1,2);
}else{
     $TDesDepartamento= '';
}
  ?> 
                    
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Departamento:
                    </td>
                    <td align="left">
                        <input  onDblClick="<?php print("AbrirBus(this.name,'$Arre3')");?>"
                     onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre3')");?>" 
                     name="TDepartamento"type="text"maxlength=3 size=5  onFocus="SelTexto(this);" value="<?=$TDepartamento?>" class="cTexBox">
                        <input readonly name="TDesDepartamento"type="text" size=46 class="cTexBoxdisabled" value="<?=$TDesDepartamento?>">
                    </td>
				</tr>
                <tr>
                    <td  class="tituloCampo" width="140" align="right"> 
                        Costo Hist&oacute;rico:
                    </td>
                    <td align="left">
                        <input  name="NCompAdquisicion"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="valorAdquisicion(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$NCompAdquisicion?>" class="cNum"> 
                    </td>
        
<?php
	$NCompAdquisicion = str_replace(",","",$NCompAdquisicion);
    echo"<script language='Javascript'>
          document.frmdeprecactivos.NCompAdquisicion.value=new NumberFormat($NCompAdquisicion).toFormatted();
    </script>"    
?>        

<?php 
$sClaveCon = 'id_empleado'; // Campo Clave para buscar
$Arretabla4[0][0]= 'sipre_automotriz.pg_empleado 
LEFT JOIN sipre_automotriz.pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento
LEFT JOIN sipre_automotriz.pg_departamento ON pg_departamento.id_departamento = pg_cargo_departamento.id_departamento
'; //Tabla
$Arretabla4[0][1]= 'T';
$Arretabla4[1][0]= 'id_empleado'; //Campo1
$Arretabla4[1][1]= 'C';
$Arretabla4[2][0]= 'CONCAT_WS(" ",nombre_empleado,apellido) AS nomber_empleado'; //Campo2
$Arretabla4[2][1]= 'C';
$Arretabla4[3][0]= 'pg_cargo_departamento.id_departamento'; //Campo2
$Arretabla4[3][1]= 'C';
$Arretabla4[4][0]= 'TResponsable'; //objeto Campo1
$Arretabla4[4][1]= 'O';
$Arretabla4[5][0]= 'TDesResponsable'; //objeto Campo2
$Arretabla4[5][1]= 'O';
$Arretabla4[6][0]= 'frmdeprecactivos'; // Pantalla donde estamos ubicados
$Arretabla4[6][1]= 'P';
$Arre4 = array_envia($Arretabla4); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
$sTabla = '';
$sCampos= '';
$sPlantillaBus= '';
$sCondicion = $sClaveCon ."= '".$TResponsable."'";
$ArrRec =$Arretabla4;
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
/*SELECT id_empleado, pg_cargo_departamento.id_cargo_departamento,
      CONCAT_WS(' ',nombre_empleado,apellido) AS nomber_empleado,
      pg_cargo_departamento.id_departamento, nombre_departamento
  FROM pg_empleado
LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento
LEFT JOIN pg_departamento ON pg_departamento.id_departamento = pg_cargo_departamento.id_departamento
  WHERE pg_cargo_departamento.id_departamento = ;*/
  	//echo $sql;
$rsTem1 = EjecutarExec($con,$sql);
if (NumeroFilas($rsTem1) > 0){
     $TDesResponsable= ObtenerResultado($rsTem1,2);
}else{
     $TDesResponsable= '';
}
  ?> 
                    <td  class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Responsable:
                    </td>
                    <td align="left">
                        <input onDblClick="<?php print("AbrirBus(this.name,'$Arre4')");?>" 
                     onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre4')");?>" name="TResponsable"type="text"maxlength=3 size=5  onFocus="SelTexto(this);" value="<?=$TResponsable?>" class="cTexBox">
                         <input readonly name="TDesResponsable"type="text" size=46 class="cTexBoxdisabled" value="<?=$TDesResponsable?>">
                     </td>
				</tr>
    
				<tr style="display:none">
                    <td class="tituloCampo" width="140" align="right"> 
                        Valor Residual:
                    </td> 
                    <td align="left">
                        <input  name="NValResidual"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="valorResidual(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$NValResidual?>" class="cNum" readonly= > 
                    </td>
                </tr>
        
<?php
  	$NValResidual = str_replace(",","",	$NValResidual);
    echo"<script language='Javascript'>
            document.frmdeprecactivos.NValResidual.value=new NumberFormat($NValResidual).toFormatted();
    </script>"    
?>
				<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Vida &Uacute;til:
                    </td>
                    <td align="left">
                        <input  name="N_MesesDepre"type="text"maxlength=20  size=20  onFocus="SelTexto(this);" onBlur="mesesDepreciar(this);" onKeyPress="return CheckNumericJEnter(this.form,this,event,'T_Descripcion')" value="<?=$N_MesesDepre?>" class="cNum"> 
                    </td>
<?php
    $N_MesesDepre = str_replace(",","",	$N_MesesDepre);
    echo"<script language='Javascript'>
          document.frmdeprecactivos.N_MesesDepre.value=new NumberFormat($N_MesesDepre).toFormatted();
    </script>"    

?>
				
                    <td  class="tituloCampo" width="140" align="right"> 
                        Proveedor:
                    </td>
                    <td align="left">
                        <input  name="TProveedor"type="text"maxlength=100 size="55" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$TProveedor?>" class="cTexBox"> 
                    </td>
			    </tr>
				<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        Valor a Depreciar
                    </td>
                    <td align="left">
                        <input  name="NValDeprec"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$NValDeprec?>" readonly class=" cNumdisabled "> 
                    </td>

<?php
        $NValDeprec = str_replace(",","", $NValDeprec);
    echo"<script language='Javascript'>
          document.frmdeprecactivos.NValDeprec.value=new NumberFormat($NValDeprec).toFormatted();
    </script>"    ?>
                    <td class="tituloCampo" width="140" align="right"> 
                        Observaciones:
                    </td>
                    <td align="left">
                        <input  name="TObservaciones"type="text"maxlength=100 size="55" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" value="<?=$TObservaciones?>" class="cTexBox"> 
                    </td>
				</tr>
   				<tr>
                    <td class="tituloCampo" width="140" align="right"> 
                        Depre. Mensual:
                    </td>
                    <td align="left">
                        <input  name="NDepreMensual"type="text"maxlength=20  size=20  onFocus="SelTexto(this);"onblur="validar(this);"onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" value="<?=$NDepreMensual?>" readonly class=" cNumdisabled "> 
                    </td>
       
    
<?php
	$NDepreMensual = str_replace(",","",	$NDepreMensual);
    echo"<script language='Javascript'>
          document.frmdeprecactivos.NDepreMensual.value=new NumberFormat($NDepreMensual).toFormatted();
    </script>"
?>
                    <td class="tituloCampo" width="140" align="right"> 
                        Depreciar:
                    </td>
                    <td  class="cabecera" align="left">
                        <select  name="TNodeprec" size="1"  onKeyPress="fn(this.form,this,event,'');"  class="cTexBox">
                            <option value="NO">NO</option>
                            <option value="SI">SI</option>
                        </select>
            
<?php
    echo"<script language='Javascript'>
          document.frmdeprecactivos.TNodeprec.value='$TNodeprec';
    </script>"    ?>      
                    </td>
                </tr>
  </table>
  </fieldset>
  </td>
  </tR>
  </table>
  
<table width="100%">
<?php
	$con = ConectarBD();
	$strSql = "SELECT * FROM deprecactivos WHERE estatus = 1;";
	$ejec = mysql_query($strSql,$con);
	
	$activos = "";
	$numRow = mysql_num_rows($ejec);
	while ($rowSql = mysql_fetch_assoc($ejec)) {
			if ($activos == "") {
				$activos.= $rowSql['Codigo'];
			} else {
				$activos.= ", ".$rowSql['Codigo'];
			}
	}
	if ($numRow > 0){
?>
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" class="divMsjError" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table>
                    <tr>
                        <td>Datos Incompletos: <?php echo $activos?></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
	<tr>
<?php
	}
?>
<?php
	$sEjecut= '';
	if ($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE'){
		$sEjecut='IN';
	}else{
		$sEjecut='UP';
	}
?>                     
		<td align="right">
        <hr/>
        	<button name=BtnGuardar type="submit" value=Guardar onClick="<?php print(" if(validarActivo()) { Ejecutar('$sEjecut'); }");?> "><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>

<?php 
	$Arretabla2[0][0]= 'deprecactivos';//Tabla
	$Arretabla2[0][1]= 'T';
	$Arretabla2[1][0]= 'Codigo'; //Campo1
	$Arretabla2[1][1]= 'C';
	$Arretabla2[2][0]= 'Descripcion'; //Campo2
	$Arretabla2[2][1]= 'C';
	$Arretabla2[3][0]= 'T_Codigo';
	$Arretabla2[3][1]= 'O';
	$Arretabla2[4][0]= 'T_Descripcion';
	$Arretabla2[4][1]= 'O';
	$Arretabla2[5][0]= 'frmdeprecactivos'; // Pantalla donde estamos ubicados
	$Arretabla2[5][1]= 'P';
	$Arretabla2[6][0]= 'modelo'; //Campo2
	$Arretabla2[6][1]= 'C';
	$Arretabla2[7][0]= 'T_Modelo';
	$Arretabla2[7][1]= 'O';
	$Arretabla2[8][0]= 'serial'; //Campo2
	$Arretabla2[8][1]= 'C';
	$Arretabla2[9][0]= 'T_Serial';
	$Arretabla2[9][1]= 'O';
	$Arretabla2[10][0]= 'estatus'; //Campo2
	$Arretabla2[10][1]= 'C';	
	$ArreGeneral = array_envia($Arretabla2); // Serializar Array
?>

        	<button name="BtnBuscar" id="BtnBuscar" type="submit" value="Buscar" onClick="<?php print("PantallaBuscar('T_Codigo','$ArreGeneral')");?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button>
        <a href="download.php?file=archivo_carga_activos_fijos.xlsx" style="text-decoration:none">
        	<button name="BtnDescargar" id="BtnDescargar" type="submit" value="Formato Excel" src="../img/iconos/page_excel.png"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Formato Excel</td></tr></table></button> 
        </a>
        	<button name="BtnImportar" id="BtnImportar" type="submit" value="Importar Excel" onClick="PnatallaImportarExcel()" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar Excel</td></tr></table></button> 
        
<?php
	if (!($StatusOculto =='LI' || $StatusOculto =='' || $StatusOculto =='DE')){
	  
	  /*echo "<script language='javascript'>alert($StatusOculto);</script>";*/
	  //activoIncompleto();
	  
?>
  			<button name=BtnIncluir type="submit" value=Incluir onClick="Ejecutar('LI');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Incluir</td></tr></table></button>
  			<button name=BtnEliminar type="submit" value=Eliminar onClick="Ejecutar('DE');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
  			<button name=BtnImprimir type="submit" value=Imprimir_Etiqueta onClick="Ejecutar('IMP');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png"/></td><td>&nbsp;</td><td>Imprimir Etiqueta</td></tr></table></button>
		<script language='javascript'>
               document.frmdeprecactivos.T_Tipo.focus();
        </script>

<?php 
	}else{
?>
<?php  
		if ($T_Codigo != ''){ /*echo "<script language='javascript'>alert($T_Codigo);</script>";*/ //activoIncompleto(); 
?>
          		 
		<script language='javascript'>
			   document.frmdeprecactivos.T_Tipo.focus();
        </script>
<?php 
		}else{ /*echo "<script language='javascript'>alert('D');</script>";*/
?>
        <script language='javascript'>
              document.frmdeprecactivos.T_Codigo.focus();
        </script>
<?php 
		} 
?>						
<?php 
	} 
?>
		</td>
	</tr>
    <tr>
    	<td class=cabecera align="left">
            <input name=StatusOculto type=hidden value=''>
            <input name="TACondicion" type=hidden value=''>
            <input name="TAValores" type=hidden value=''>
            <input name="Desha" type=hidden value="<?= $Desha ?>">                    
		</td>
	</tr>
</table>
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


