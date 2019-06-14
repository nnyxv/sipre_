<?php session_start(); 
include_once('FuncionesPHP.php');
/*if($_SESSION["UsuarioSistema"]!= "001"){
    MJ('SISTEMA EN MANTENIMIENTO. SALIR DEL SISTEMA');
return;
}*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<!--DATOS DE LA FORMA-->

<!--Título: frmDiarios -->

<!--Descripción: Formulario individual-->

<!--Copyright: Copyright (c) Corporación Oriomka, C.A. 2002-->

<!--Empresa: Corporación Oriomka, C.A.-->

<!--Autor: Corporación Oriomka, C.A.-->

<!--@version 1.0-->

<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->
<title>.: SIPRE 2.0 :. Contabilidad - Movimientos Contables</title>
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

<body onLoad="SoloLimpiar();">
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->

<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language="javascript">
	function objetoAjax(){
		var xmlhttp=false;
	 	try{
   			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  		}catch(e){
   			try {
    			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	   		}catch(E){
    			xmlhttp = false;
   			}
  		}
  		if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
   			xmlhttp = new XMLHttpRequest();
  		}
  		return xmlhttp;
	}
</script>

<script language= "javascript" >
 
<!--*****************************************************************************************-->
<!--**********************************Buscar Descripcion General*************************************-->
<!--*****************************************************************************************-->
function BuscarDescripGeneral(sValor,sCampoBuscar,oArreglo){
	if (Alltrim(sValor) != ""){
	//	substring(codigo,1,len(rtrim(" & "'" & Trim(Texcodigo.Text) & "'))) = '" & Trim(Texcodigo.Text) & "'")
		document.frmDiarios.TACondicion.value=sCampoBuscar + "= '" + sValor + "'";
		document.frmDiarios.TAValores.value=oArreglo;
		document.frmDiarios.method='post';
		document.frmDiarios.target='topFrame';
		document.frmDiarios.action='BusTablaParametros.php';
		document.frmDiarios.submit();
	}// if (Alltrim(sValor) != ""){
}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){

<!--********************************************************************************************-->
<!--*************************Buscar Descripcion CODIGO TRANSACCION***************************-->
<!--********************************************************************************************-->
function BuscarDescripCT(sValor,sCampoBuscar,oArreglo){
	if (Alltrim(sValor) != ""){
	//	substring(codigo,1,len(rtrim(" & "'" & Trim(Texcodigo.Text) & "'))) = '" & Trim(Texcodigo.Text) & "'")
		 if (document.frmDiarios.oCTAnterior.value != document.frmDiarios.oCT.value){
			document.frmDiarios.TACondicion.value=sCampoBuscar + "= '" + sValor + "'";
			document.frmDiarios.TAValores.value=oArreglo;
			document.frmDiarios.method='post';
			document.frmDiarios.target='topFrame';
			document.frmDiarios.action='BusTablaParametrosCT.php';
			document.frmDiarios.submit();
		}	
	}// if (Alltrim(sValor) != ""){
}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
 
<!--*****************************************************************************************-->
<!--**********************************Buscar Descripcion*************************************-->
<!--*****************************************************************************************-->
function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
	if (Alltrim(sValor) != ""){
	//	substring(codigo,1,len(rtrim(" & "'" & Trim(Texcodigo.Text) & "'))) = '" & Trim(Texcodigo.Text) & "'")
	  document.frmDiarios.TACondicion.value=sCampoBuscar + "= '" + sValor + "'";
	  document.frmDiarios.TAValores.value=oArreglo;
	  document.frmDiarios.method='post';
	  document.frmDiarios.target='topFrame';
	  document.frmDiarios.action='BusTablaCuenta.php';
	  document.frmDiarios.submit();
	}// if (Alltrim(sValor) != ""){
}//function BuscarDescrip(sValor,sCampoBuscar,oArreglo){
  
<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
function AbrirBus(sObjeto,oArreglo){
	if (document.frmDiarios.Actualizado.value=='1'){
		  alert('El Comprobante ha sido actualizado no puede realizar ninguna modificación');			 
		  return;
	}

	if (document.frmDiarios.oTablaSelec.value == "I" || document.frmDiarios.oTablaSelec.value == "H"){
	   return;
	}
	
	if (sObjeto == "oCC" && document.frmDiarios.oCC.readOnly== true){
	   return;
	}
   	
	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--*************************PANTALLA BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function PantallaBuscar(sObjeto,oArreglo){
	URL = 'PantallaBuscarFormularioDiarios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo;
	//winOpen('PantallaBuscarFormulariosDiarios.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
	msg=open("","Busqueda","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,copyhistory=no,width=830,height=440");
	msg.location = URL;
}// function AbrirBus(sObjeto,oArreglo){

<!--*****************************************************************************************-->
<!--*************************PANTALLA BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function RepImprimir(sTablaPant){
	msg=open("","Reporte","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no");
	sP = "./RepComprobantesDiariosINAVI.php";
	sPar = 'cDesde1='+ document.frmDiarios.oComprobante.value;
	sPar= sPar + '&cHasta1='+ document.frmDiarios.oComprobante.value;
	sPar= sPar + '&cDesde2='+ document.frmDiarios.xAFecha.value + "-" + document.frmDiarios.xMFecha.value + "-" +document.frmDiarios.xDFecha.value;
	sPar= sPar + '&cHasta2='+ document.frmDiarios.xAFecha.value + "-" + document.frmDiarios.xMFecha.value + "-" +document.frmDiarios.xDFecha.value;
	sPar= sPar + '&cDesde4='+ document.frmDiarios.oCC.value;
	sPar= sPar + '&oTablaSelec='+ sTablaPant;
	msg.location = sP+'?'+sPar;
}// function AbrirBus(sObjeto,oArreglo){

//document.topFrame.width = 200
//document.topFrame.height = 200
//document.getElementById("topFrame").width= 200
//document.getElementById("topFrame").height=200
<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
	document.frmDiarios.target='topFrame';
	document.frmDiarios.method='post';
	document.frmDiarios.action='OpSQLDiarios.php';
	document.frmDiarios.StatusOculto.value = "BU"
	document.frmDiarios.submit();  
}// function AbrirBus(sObjeto,oArreglo){

<!--*****************************************************************************************-->
<!--**********************************Limpiar Forma******************************************-->
<!--*****************************************************************************************-->
  function Limpiar(){
		document.frmDiarios.Actualizado.value='';
		document.frmDiarios.oComprobante.value = '';
		document.frmDiarios.AccionRenglon.value = '';
		document.frmDiarios.oNumero.value = '';
		document.frmDiarios.oCodigoCuenta.value= '';     
		document.frmDiarios.oDesMovimiento.value=''; 
		document.frmDiarios.oDebe.value='0.00'; 
		document.frmDiarios.oHaber.value='0.00'; 
		document.frmDiarios.oDocumento.value=''; 
		document.frmDiarios.oDesCuentaTemp.value=''; 	  
		document.frmDiarios.oDesCuenta.value='';
		document.frmDiarios.oConcepto.value=''; 	
		document.frmDiarios.StatusOcultoReng.value = '';
		document.frmDiarios.oCT.value = '';
		document.frmDiarios.oDT.value = '';		
		document.frmDiarios.oIM.value = '';		
		document.frmDiarios.target='FrameDetalle';
		document.frmDiarios.action='frmRenglonesDiarios.php';
  		document.frmDiarios.StatusOculto.value = "IN"
		document.frmDiarios.oVerStatus.value='Estatus  (Inclusión)';
		document.frmDiarios.enespera.value=""
		if (document.frmDiarios.CCSistema.value == ""){
			document.frmDiarios.oCC.value = document.frmDiarios.sValoroCC.value;
			document.frmDiarios.oDesCC.value = document.frmDiarios.sValorDesoCC.value;
			document.frmDiarios.xDFecha.value = document.frmDiarios.sValorDia.value;
			document.frmDiarios.xMFecha.value = document.frmDiarios.sValorMes.value;
			document.frmDiarios.xAFecha.value = document.frmDiarios.sValorAno.value;
		    document.frmDiarios.oCC.readOnly = false;
			//document.frmDiarios.xDFecha.readOnly = false;
		}else{
			//document.frmDiarios.xDFecha.readOnly = false;
		}
  		document.frmDiarios.submit();
		document.frmDiarios.oCC.focus();  
   }//  function Limpiar(){

  function SoloLimpiar(){
  		document.frmDiarios.Actualizado.value='';
		document.frmDiarios.oComprobante.value = '';
		document.frmDiarios.oNumero.value = '';
		document.frmDiarios.oCodigoCuenta.value= '';     
		document.frmDiarios.oDesMovimiento.value=''; 
		document.frmDiarios.oDebe.value='0.00'; 
		document.frmDiarios.oHaber.value='0.00'; 
		document.frmDiarios.oDocumento.value=''; 
		document.frmDiarios.oDesCuentaTemp.value=''; 	  
		document.frmDiarios.oDesCuenta.value='';
		document.frmDiarios.oConcepto.value=''; 
	    document.frmDiarios.oCT.value = '';
		document.frmDiarios.oDT.value = '';		
		document.frmDiarios.oIM.value = '';		
		document.frmDiarios.StatusOcultoReng.value = 'X';
  		document.frmDiarios.StatusOculto.value = "IN"
		document.frmDiarios.oVerStatus.value='Estatus  (Inclusión)';
		if (document.frmDiarios.CCSistema.value == ""){
			/*document.frmDiarios.oCC.value = '';
			document.frmDiarios.oDesCC.value = '';*/
		}
		document.frmDiarios.oCC.focus();  
   }//  function SoloLimpiar(){

<!--*****************************************************************************************-->
<!--**********************************GUARDAR************************************************-->
<!--*****************************************************************************************-->
  function Guardar(){
  if (document.frmDiarios.enespera.value == "SI"){
        alert("Solicitud de actualizar ya fue enviada, por favor espere.... conexion en proceso");
		return;
  }
 //  if (verRenglones()){
 //      return;
  // }
  
  
        if (document.frmDiarios.Actualizado.value=='1'){
				alert('El Comprobante ha sido actualizado no puede realizar ninguna modificación');			 
				return;
		 }
		 
		  if (document.frmDiarios.oCC.value==""){
		  	 alert('El Centro de Costo no puede ser pasado en blanco');			 
			 document.frmDiarios.oCC.focus();
			return;
		  }
		 
     sStatus=document.frmDiarios.StatusOculto.value;
	   		if (VerificarFechasJ(document.frmDiarios))
  		{
  			return false;
  		}
  		if (CamposBlancosJ(document.frmDiarios))
  		{
  			return false;
  		}
  	if (sStatus == "IN"){
	    document.frmDiarios.enespera.value= 'SI'
		document.frmDiarios.target='topFrame';
		//document.frmDiarios.target='_self';
		document.frmDiarios.method='post';
		document.frmDiarios.action='OpSQLDiarios.php';
  		document.frmDiarios.StatusOculto.value = "IN"
  		document.frmDiarios.submit();
  	}
  	else if (sStatus == "UP")
  	{
	    document.frmDiarios.enespera.value= 'SI'
        document.frmDiarios.target='topFrame';
		//document.frmDiarios.target='_self';
		document.frmDiarios.method='post';
		document.frmDiarios.action='OpSQLDiarios.php';
  		document.frmDiarios.StatusOculto.value = "UP"
  		document.frmDiarios.submit();  
	}
  }
	function  GenerarCom(){
	    document.frmDiarios.enespera.value= 'SI'		
     	document.frmDiarios.target='topFrame';
		document.frmDiarios.method='post';
		document.frmDiarios.action='OpSQLDiarios.php';
  		document.frmDiarios.StatusOculto.value = "IN";
  		document.frmDiarios.submit();
	}
<!--*****************************************************************************************-->
<!--**********************************ELIMINAR************************************************-->
<!--*****************************************************************************************-->
   function Eliminar(){
      if (document.frmDiarios.Actualizado.value=='1'){
				alert('El Comprobante ha sido actualizado no puede realizar ninguna modificación');			 
				return;
		 }
  		if (confirm('Desea Eliminar el registro')){
			document.frmDiarios.target='topFrame';
			document.frmDiarios.method='post';
			document.frmDiarios.action='OpSQLDiarios.php';
	  		document.frmDiarios.StatusOculto.value = "DE"
	  		document.frmDiarios.submit();  		}
  	}//   function Eliminar(){
<!--*****************************************************************************************-->
<!--**********************************BUSCARRENGLONES****************************************-->
<!--*****************************************************************************************-->
	
  function jBuscarRenglones(sValorOcul){
  if (document.frmDiarios.Actualizado.value=='1'){
				alert('El Comprobante ha sido actualizado no puede realizar ninguna modificación');			 
				return;
		 }
		 
ajax=objetoAjax();
		ajax.open("GET", "Buscarcuenta.php?sValor="+document.frmDiarios.oCodigoCuenta.value,false);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				document.frmDiarios.oCodigoCuenta.value = ajax.responseText;
  			}
 		}
 		ajax.send(null)		 
		
  if (document.frmDiarios.oCodigoCuenta.value == ""){
    alert("El Codigo de Cuenta es Obligatorio");
	document.frmDiarios.oCodigoCuenta.focus();
	return;
  }
  
  ajax=objetoAjax();
		ajax.open("GET", "Buscartransacciones.php?sValor="+document.frmDiarios.oCT.value,false);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				document.frmDiarios.oCT.value = ajax.responseText;
  			}
 		}
 		ajax.send(null)		 
  
  if (document.frmDiarios.oCT.value == ""){
    alert("El CT es oBligatorio");
	document.frmDiarios.oCT.focus();
	return;
  }
  
   ajax=objetoAjax();
		ajax.open("GET", "Buscardocumentos.php?sValor="+document.frmDiarios.oDT.value,false);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				document.frmDiarios.oDT.value = ajax.responseText;
  			}
 		}
 		ajax.send(null)
		
  if (document.frmDiarios.oDT.value == ""){
    alert("El DT es oBligatorio");
	document.frmDiarios.oDT.focus();
	return;
  }
		ajax=objetoAjax();
		ajax.open("GET", "BuscarImputacion.php?sValorIM="+document.frmDiarios.oIM.value,false);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				document.frmDiarios.oIM.value = ajax.responseText;
  			}
 		}
 		ajax.send(null)		
		
if (document.frmDiarios.oIM.value == ""){
     alert("El Imputacion es oBligatorio");
	document.frmDiarios.oIM.focus();
	return;
  }
  
  if (document.frmDiarios.oDebe.value == "0.00" && document.frmDiarios.oHaber.value == "0.00"){
    alert("El Debe o Haber son oBligatorios");
	document.frmDiarios.oDT.focus();
	return;
  }
        
  				document.frmDiarios.StatusOcultoReng.value = sValorOcul;
				if (document.frmDiarios.AccionRenglon.value == 'M'){
					document.frmDiarios.StatusOcultoReng.value = 'M';
				}// if (document.frmDiarios.AccionRenglon.value == 'M'){
				if (document.frmDiarios.AccionRenglon.value == 'I'){
						document.frmDiarios.StatusOcultoReng.value = 'M';
				}//if (document.frmDiarios.AccionRenglon.value == 'I'){
				if (document.frmDiarios.AccionRenglon.value == '' || document.frmDiarios.AccionRenglon.value == 'N'){
					document.frmDiarios.StatusOcultoReng.value = 'N';
				}//if (document.frmDiarios.AccionRenglon.value == '' || document.frmDiarios.AccionRenglon.value == 'N'){	
				document.frmDiarios.sDesactivarColor.value = "";
				document.frmDiarios.method='post';
				document.frmDiarios.target='FrameDetalle';
				document.frmDiarios.action='frmRenglonesDiarios.php#'+document.frmDiarios.oOrdenRen.value;
				document.frmDiarios.AccionRenglon.value == '';
				document.frmDiarios.oCodigoCuenta.focus();
				document.frmDiarios.submit();
			
				if (document.frmDiarios.AccionRenglon.value == 'M'){
					document.frmDiarios.oCodigoCuenta.value= '';
					document.frmDiarios.oDebe.value='0.00';
					document.frmDiarios.oHaber.value='0.00';
					document.frmDiarios.oDesCuentaTemp.value='';
					document.frmDiarios.oDesCuenta.value='';
				}//if (document.frmDiarios.AccionRenglon.value == 'M'){
					 document.frmDiarios.oCodigoCuenta.focus();
  }//jBuscarRenglones(sValorOcul){


<!--*****************************************************************************************-->
<!--**********************************BUSCAR CUENTA TEMPORAL*********************************-->
<!--*****************************************************************************************-->
  function BuscarCuentaTemp(obj){
		document.frmDiarios.oDesCuenta.value=document.frmDiarios.oDesCuentaTemp.value;
	   if (obj.length != 0){ 
			obj.select();
		}
  }//  function BuscarCuentaTemp(obj){
<!--*****************************************************************************************-->
<!--**********************************BUSCAR CUENTA TEMPORAL*********************************-->
<!--*****************************************************************************************-->
  function BuscarCuentaTempCT(obj){
		document.frmDiarios.oDesCuenta.value=document.frmDiarios.oDesCuentaTemp.value;
		document.frmDiarios.oCTAnterior.value=obj.value;
	   if (obj.length != 0){ 
			obj.select();
		}
  }//  function BuscarCuentaTemp(obj){
<!--*****************************************************************************************-->
<!--**********************************SELECCIONAR TEXTO**************************************-->
<!--*****************************************************************************************-->
  function SelTexto(obj){
	   if (obj.length != 0){ 
			obj.select();
		}
  }//  function SelTexto(obj){
  
<!--*****************************************************************************************-->
<!--**********************************VALIDAR FORMATO****************************************-->
<!--*****************************************************************************************-->
  function validar(obj){
  if (obj.name == "oDebe"){
    if (obj.value != 0){
       document.frmDiarios.oHaber.value = new NumberFormat(0).toFormatted();
   }
 } 
  if (obj.name == "oHaber"){
    if (obj.value != 0){
       document.frmDiarios.oDebe.value = new NumberFormat(0).toFormatted();
     }
  }
  
  obj.value = new NumberFormat(obj.value).toFormatted();
  }//   function validar(obj){
    
<!--*****************************************************************************************-->
<!--**********************************ENTER CODIGO*******************************************-->
<!--*****************************************************************************************-->
  function EnterCodigo(evt){
      if (event.keyCode == 13){
	  	document.frmDiarios.oDesMovimiento.focus();
	  }//      if (event.keyCode == 13){
  }//  function EnterCodigo(evt){

  function EnterConcepto(evt){
      if (event.keyCode == 13){
	  	document.frmDiarios.oCodigoCuenta.focus();
	  }//      if (event.keyCode == 13){
  }//    function EnterConcepto(evt){
  
  function Reconvertir(obj){
   	       document.frmDiarios.method='post';
		   document.frmDiarios.target='topFrame';
		   document.frmDiarios.sNombreObj.value = obj.name;
		   document.frmDiarios.dValor.value = obj.value;
		   document.frmDiarios.action='Reconvertir.php';
		   document.frmDiarios.submit();
  }//    function Reconvertir{

  function verRenglones(){
     	ajax=objetoAjax();
 		ajax.open("GET", "verRenglones.php?arrdetalle="+document.frmDiarios.arrdetalle.value,true);
 		ajax.onreadystatechange=function() {
  			if (ajax.readyState==4) {
   				if (ajax.responseText == ""){
				   return false;
				}else{
					alert(ajax.responseText);
				   return true;
				}
  			}
 		}
 		ajax.send(null)
    }
</script>
  <!--*****************************************************************************************-->
  <!--*****************************************************************************************-->
  <!--*****************************************CODIGO PHP**************************************-->
  <!--*****************************************************************************************-->
  <!--*****************************************************************************************-->

  <!--*****************************************************************************************-->
  <!--*****************************************************************************************-->
  <!--************************************FORMULARIO HTML**************************************-->
  <!--*****************************************************************************************-->
  <!--*****************************************************************************************-->

<table border="0" width="100%">
	<tr>
     	<td class="tituloPaginaContabilidad">
<?php 
$cad=substr($oTablaSelec,0,1);


if ($cad == 'D'){
$numeroMenu = 3;
$TablaSelec = "enc_diario";
//	$xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
 ?>
         Movimientos Diarios
<?php } ?>			

<?php if ($cad == 'P'){
$numeroMenu = 4;
  //  $xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	//$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
//	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	//$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes+1,1,$xAno)); 
	//$xDia =obFecha($sFechaSumar,'D');
	//$xMes =obFecha($sFechaSumar,'M');
	//$xAno =obFecha($sFechaSumar,'A');
$TablaSelec = "enc_dif";
?>	
         Movimientos Posteriores
<?php } ?>			

<?php if ($cad == 'H'){ 
    $xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes-1,1,$xAno)); 
	$xDia =obFecha($sFechaSumar,'D');
	$xMes =obFecha($sFechaSumar,'M');
	$xAno =obFecha($sFechaSumar,'A');
$TablaSelec = "enc_historico";
?>	
         Movimientos Hist&oacute;ricos
<?php } ?>			
<?php if ($cad == 'I'){ 
    $xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes-1,1,$xAno)); 
	$xDia =obFecha($sFechaSumar,'D');
	$xMes =obFecha($sFechaSumar,'M');
	$xAno =obFecha($sFechaSumar,'A');
$TablaSelec = "enc_importados";
?>	
         Movimientos Importados
<?php } ?>	
            
    	</td>            
	</tr>
</table>

<form name="frmDiarios" action="frmDiarios.php" method="post">
	<input name="Actualizado" type="hidden">	
  	<input name="enespera" type="hidden">	
<table  width="100%" border="0"  align="center" cellpadding="0" cellspacing="0" >
    <!--DWLayoutTable border-collapse:  collapse;-->
    <tr>
    	<td>&nbsp;</td>
    </tr>
    <tr> 
    	<td  height="15" valign="top" width="940" align="right">
        	<input   size='50' style="border: 0px solid #FFFFFF" name="oVerStatus" value="Estatus  (Inclusi&oacute;n)" disabled></td>
    </tr>
</table>
<!--<div style="width:1000px;">
	<div class="x-box-tl"><div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div></div>
    <div class="x-box-ml"><div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">-->

    <!--DWLayoutTable border-collapse:  collapse;-->
<?php if ($cad == 'D'){
		$numeroMenu = 3;
		$TablaSelec = "enc_diario";
		//	$xDia =obFecha($_SESSION["sFec_Proceso"],'D');
			$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
			$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
 ?>
         <!--<h3 style="margin-bottom:10px;">Movimientos Diarios</h3>-->
<?php } ?>			

<?php if ($cad == 'P'){
$numeroMenu = 4;
  //  $xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	//$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
//	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	//$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes+1,1,$xAno)); 
	//$xDia =obFecha($sFechaSumar,'D');
	//$xMes =obFecha($sFechaSumar,'M');
	//$xAno =obFecha($sFechaSumar,'A');
$TablaSelec = "enc_dif";
?>	
  <!--       <h3 style="margin-bottom:10px;">Movimientos Posteriores</h3>-->
<?php } ?>			

<?php if ($cad == 'H'){ 
    $xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes-1,1,$xAno)); 
	$xDia =obFecha($sFechaSumar,'D');
	$xMes =obFecha($sFechaSumar,'M');
	$xAno =obFecha($sFechaSumar,'A');
$TablaSelec = "enc_historico";
?>	
   <!--      <h3 style="margin-bottom:10px;">Movimientos Hist&oacute;ricos</h3>-->
<?php } ?>			
<?php if ($cad == 'I'){ 
    $xDia =obFecha($_SESSION["sFec_Proceso"],'D');
	$xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	$xAno =obFecha($_SESSION["sFec_Proceso"],'A');
	$sFechaSumar = date("Y-m-d",mktime(0,0,0,$xMes-1,1,$xAno)); 
	$xDia =obFecha($sFechaSumar,'D');
	$xMes =obFecha($sFechaSumar,'M');
	$xAno =obFecha($sFechaSumar,'A');
$TablaSelec = "enc_importados";
?>	
         <!--<h3 style="margin-bottom:10px;">Movimientos Importados</h3>-->
<?php } ?>			

<!--<div class="x-form-bd" id="container">-->
<!--<fieldset>-->

<table width="100%"  border="0" align="center">	
	<tr>
        <td>
        	<fieldset>
            <legend class="legend">Datos del Comprobante</legend>
            	<table border="0" align="center">
                	<tr>
						<td class="tituloCampo" width="140" align="right"> 
        					Comprobante: 
                        </td>    
                        <td align="left">    
                            <input  onFocus="document.frmDiarios.oCC.focus();"  readonly="true" onChange="Buscar();" <?php print($bDesha);?> name="oComprobante" type="text"maxlength=12 size="10" class="cTexBoxdisabled" value="<?php print(trim($oComprobante));?>"> </td>

<?php	//para el centro de costo

$Arretabla[0][0]= "centrocosto"; //Tabla
$Arretabla[0][1]= 'T';
$Arretabla[1][0]= "codigo"; //Campo1
$Arretabla[1][1]= 'C';
$Arretabla[2][0]= "descripcion"; //Campo2
$Arretabla[2][1]= 'C';
$Arretabla[3][0]= "oCC"; //Objeto del Campo1
$Arretabla[3][1]= 'O';
$Arretabla[4][0]= "oDesCC"; //Objeto del Campo2
$Arretabla[4][1]= 'O';
$Arretabla[5][0]= 'frmDiarios';// Pantalla donde estamos ubicados
$Arretabla[5][1]= 'P';
$sClaveCon = "codigo"; // Campo Clave para buscar
$ArreCC = array_envia($Arretabla); // Serializar Array
?>
<?php 
	
	$sRead= "";
	$sDeshabilitar= " class='cTexBox'";
	$sNumDeshabilitar= " class='cNum'";
	if($cad == 'H' || $cad == 'I'){
		$sDeshabilitar= " readonly class='cTexBoxdisabled' ";
	  	$sRead= "readonly";
	  	$sNumDeshabilitar= " readonly  class='cNum' ";
	}else {
		if ($_SESSION["CCSistema"] != ""){
			$sReadCosto = " readonly disabled ";	   
		 }
	}
	if ($_REQUEST["oCC"] != ""){
		$sValoroCC = $_REQUEST["oCC"];	   
		$sValorDesoCC = $_REQUEST["oDesCC"];	   
	}
?>	
	
                        <td  class="tituloCampo" width="140" align="right">
                            Centro de Costo:
                        </td>
                        <td align="left">  
                            <input onFocus="SelTexto(this);" onKeyPress="fn(this.form,this,event,'xDFecha')" maxlength="8"  onDblClick="<?php print("AbrirBus(this.name,'$ArreCC')");?>" id="oCC"  name="oCC"  type="text" size="10" onBlur="<?php print("BuscarDescripGeneral(this.value,'$sClaveCon','$ArreCC')");?>"  readonly class='cTexBoxdisabled' >
                            <input  disabled maxlength="60" name="oDesCC" class="cTexBox" type="text" size="40" value="<?= $_SESSION["DesCCSistema"] ?>">
                        </td>
                        <td class="tituloCampo" width="140" align="right">
                            Fecha:
                        </td>
                        <td align="left">
                            <input class="cTexBoxdisabled" readonly id="xDFecha" name="xDFecha" type="text"maxlength=2 onKeyPress="return CheckNumericJEnter(this.form,this,event,'oConcepto')" onFocus="SelTexto(this);" size="1" value="<?php  print($xDia); ?>">
                            <input class='cTexBoxdisabled' readonly name="xMFecha" type="text" maxlength=2 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="1" class="cNumdisabled" value="<?php  print($xMes); ?>">
                            <input class='cTexBoxdisabled' readonly name="xAFecha" type="text"maxlength=4 onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" size="4" class="cNumdisabled" value="<?php  print($xAno); ?>">	
                        </td>
                    </tr>

                    <tr>
                        <td class="tituloCampo" width="140" align="right"> 
                            Concepto:
                        </td>
                        <td align="left"  width="930" colspan="4">
                            <input <?= $sDeshabilitar?> name="oConcepto" onKeyPress="fn(this.form,this,event,'oCodigoCuenta')" onFocus="SelTexto(this);" type="text"maxlength=255 size="130"   value="<?php print(trim($oConcepto));?>"> 
                        </td>
                    </tr>
                    <tr>
                        <td  class="tituloCampo" width="140" align="right">
                            Cuenta:
                        </td>
                        <td align="left"  width="930" colspan="4"><input onFocus="this.form.oCodigoCuenta.focus();" readonly="false" style="color:#666666"  name="oDesCuenta" type="text"maxlength=80 size="130" class="cTexBox"  o value="<?php print(trim($oCuenta));?>"> 
                        </td>
					</tr>
				</table>
                </fieldset>
		</td>
	</tr>
</table>

<table width="100%"  align="center">
	<tr>
        <td height="12" width="110" valign="top"> <p></p></td>
        <td height="12" width="5" valign="top" align="center"> <p></p></td>
        <td height="12" width="280" valign="top" > <p></p></td>
        <td height="12" width="141" valign="top"  align="right"> <p>
	    	<button type="submit" name="BtnRDebe" onClick="Reconvertir(document.frmDiarios.oDebe)" value="Reconvertir">Reconvertir</button>
    	</td>
    	<td height="12" width="123" valign="top"  align="right"> <p>
    		<button type="submit" name="BtnRHaber" onClick="Reconvertir(document.frmDiarios.oHaber)" value="Reconvertir">Reconvertir</button>
    	</td>
        <td height="12" width="20" valign="top"> <p></p></td>
        <td height="12" width="193" valign="top"> <p></p></td>
        <td height="12" width="193" valign="top"> <p></p></td>
        <td height="12" width="33" valign="top"></td>
        
    </tr>
    <tr class="tituloColumna" align="center">
        <td width="206">
        	C&oacute;digo de Cuenta
        </td>
        <td width="4" align="center"> 
        	CT
        </td>
        <td width="280"> 
        	Descripción del Movimiento
        </td>
        <td width="141"> 
        	Debe
        </td>
        <td width="130"> 
        	Haber
        </td>
        <td width="20"> 
        	DT
        </td>
        <td width="193"> 
        	Documento
        </td>
        <td width="193"> 
        	Imputaci&oacute;n
        </td>
        <td width="33"></td>
        
    </tr>
	<tr>
    
<?php 
//if($_SESSION["CCSistema"] != ""){
	$EstadoCuenta =  "sipre_contabilidad.cuenta";
	$EstadoCT =  "sipre_contabilidad.transacciones";
	$EstadoDT =  "sipre_contabilidad.documentos";
	$EstadoIM =  "sipre_contabilidad.centrocosto";
	/*}else{
	  $EstadoCuenta =  "cuenta";
	  $EstadoCT =  "transacciones";
	  $EstadoDT =  "documentos";
	  $EstadoIM =  "centrocosto";
	}*/
	
	$Arretabla[0][0]= $EstadoCuenta; //Tabla
	$Arretabla[0][1]= 'T';
	$Arretabla[1][0]= "Codigo"; //Campo1
	$Arretabla[1][1]= 'C';
	$Arretabla[2][0]= "Descripcion"; //Campo2
	$Arretabla[2][1]= 'C';
	$Arretabla[3][0]= "Codigo"; //Campo1
	$Arretabla[3][1]= 'C';
	$Arretabla[4][0]= "Descripcion"; //Campo2
	$Arretabla[4][1]= 'C';
	$Arretabla[5][0]= "oCodigoCuenta"; //Objeto del Campo1
	$Arretabla[5][1]= 'O';
	$Arretabla[6][0]= "oDesCuenta"; //Objeto del Campo2
	$Arretabla[6][1]= 'O';
	$Arretabla[7][0]= "oCodcuentaTemp"; //Objeto del Campo2 para unos procesos temporales que debia hacer
	$Arretabla[7][1]= 'O';
	$Arretabla[8][0]= "oDesCuentaTemp"; //Objeto del Campo2 para unos procesos temporales que debia hacer
	$Arretabla[8][1]= 'O';
	$Arretabla[9][0]= 'frmDiarios';// Pantalla donde estamos ubicados
	$Arretabla[9][1]= 'P';
	
	$sClaveCon = "codigo"; // Campo Clave para buscar
	$Arre = array_envia($Arretabla); // Serializar Array

	$Arretabla1[0][0]= $EstadoCuenta; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= "Codigo"; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= "Descripcion"; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= "oCodigoCuenta"; //Objeto del Campo2
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= "oDesCuenta"; //Objeto del Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmDiarios';// Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$Arre1 = array_envia($Arretabla1); // Serializar Array
	//onKeyPress="return CheckNumericJEnter(event,this.form,this)"
	
	$Arretabla1[0][0]= $EstadoCT; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= "codigo"; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= "descripcion"; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= "oCT"; //Objeto del Campo2
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= "oDesMovimiento"; //Objeto del Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmDiarios';// Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$ArreCT = array_envia($Arretabla1); // Serializar Array
	//onKeyPress="return CheckNumericJEnter(event,this.form,this)"
	
	$Arretabla1[0][0]= $EstadoDT; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= "codigo"; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= "descripcion"; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= "oDT"; //Objeto del Campo2
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= "oDesripcionDT"; //Objeto del Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmDiarios';// Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$ArreDT = array_envia($Arretabla1); // Serializar Array
	
	$Arretabla1[0][0]= $EstadoIM; //Tabla
	$Arretabla1[0][1]= 'T';
	$Arretabla1[1][0]= "codigo"; //Campo1
	$Arretabla1[1][1]= 'C';
	$Arretabla1[2][0]= "descripcion"; //Campo2
	$Arretabla1[2][1]= 'C';
	$Arretabla1[3][0]= "oIM"; //Objeto del Campo2
	$Arretabla1[3][1]= 'O';
	$Arretabla1[4][0]= "oDesripcionIM"; //Objeto del Campo2
	$Arretabla1[4][1]= 'O';
	$Arretabla1[5][0]= 'frmDiarios';// Pantalla donde estamos ubicados
	$Arretabla1[5][1]= 'P';
	$ArreIM = array_envia($Arretabla1); // Serializar Array

//onKeyPress="return CheckNumericJEnter(event,this.form,this)"
 ?>
<input  name="oNumero" type="hidden" value="">

<?php 
	$sDeshabilitar= " class='cTexBox'";
	$sNumDeshabilitar= " class='cNum'";
	if($cad == 'H' || $cad == 'I'){
		$sDeshabilitar= " readonly class='cTexBoxdisabled' ";
		$sRead= "readonly";
		$sNumDeshabilitar= " readonly  class='cNum' ";
	} 
?>
	
    	
		<td height="12"  valign="top"> 
        	<input <?=$sDeshabilitar?> maxlength="80"   onMouseMove="BuscarCuentaTemp('');"  onFocus="BuscarCuentaTemp(this);"   onKeyPress="fnCuenta(this.form,this,event,'')"  onDblClick="<?php print("AbrirBus(this.name,'$Arre1')");?>" name="oCodigoCuenta" class="cTexBox" type="text" size="18" onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre')");?>" >  </td>
        <td height="12"  valign="top"> 
            <input <?=$sDeshabilitar?> maxlength="250"  onMouseMove="BuscarCuentaTemp('');" onFocus="BuscarCuentaTempCT(this);"  onKeyPress="fn(this.form,this,event,'')" name="oCT" class="cTexBox" type="text" size="3" onDblClick="<?php print("AbrirBus(this.name,'$ArreCT')");?>" onBlur="<?php print("BuscarDescripCT(this.value,'$sClaveCon','$ArreCT')");?>" ></td>
        <td height="12"  valign="top"> 
            <input <?=$sDeshabilitar?> maxlength="250"   class="cTexBox" onMouseMove="BuscarCuentaTemp('');" onFocus="BuscarCuentaTemp(this);"  onKeyPress="fn(this.form,this,event,'')" name="oDesMovimiento"  onKeyPress="fn(this.form,this,'')"  type="text" size="35"></td>
        <td height="12"  valign="top" align="right"> 
            <input <?=$sNumDeshabilitar?> maxlength="20"  onMouseMove="BuscarCuentaTemp('');" onKeyPress="return CheckNumericJEnter(this.form,this,event,'')"  onFocus="BuscarCuentaTemp(this);" name="oDebe" type="text" size="18" onBlur="validar(this)"></td><!--AQUI-->
        <td height="12"  valign="top" align="right"> <input <?=$sNumDeshabilitar?> maxlength="20"  onMouseMove="BuscarCuentaTemp('');" onKeyPress="return CheckNumericJEnter(this.form,this,event,'')"  onFocus="BuscarCuentaTemp(this);" name="oHaber"  type="text" size="16" onBlur="validar(this)"></td>
        <td height="12"  valign="top"> <input <?=$sDeshabilitar?> maxlength="250"  onMouseMove="BuscarCuentaTemp('');" onFocus="BuscarCuentaTemp(this);"  onKeyPress="fn(this.form,this,event,'')" name="oDT" class="cTexBox" type="text" size="3" onDblClick="<?php print("AbrirBus(this.name,'$ArreDT')");?>" onBlur="<?php print("BuscarDescripGeneral(this.value,'$sClaveCon','$ArreDT')");?>" ></td>
        <td height="12"  valign="top"> <input <?=$sDeshabilitar?> maxlength="6"  onMouseMove="BuscarCuentaTemp('');" onFocus="BuscarCuentaTemp(this);" name="oDocumento"  onKeyPress="return CheckNumericJEnter(this.form,this,event,'')" class="cTexBox" type="text" size="15"></td>
        <td height="12"  valign="top"> <input <?=$sDeshabilitar?> maxlength="250"  onMouseMove="BuscarCuentaTemp('');" onFocus="BuscarCuentaTemp(this);"  onKeyPress="fn(this.form,this,event,'BrnAgregar')" name="oIM" class="cTexBox" type="text" size="10" onDblClick="<?php print("AbrirBus(this.name,'$ArreIM')");?>" onBlur="<?php print("BuscarDescripGeneral(this.value,'$sClaveCon','$ArreIM')");?>" ></td>
        <td height="12"  valign="top">
        	<button <?=$sDeshabilitar?> type="button"  onMouseMove="BuscarCuentaTemp('');" onFocus="BuscarCuentaTemp(this);"  name="BrnAgregar"  class="inputBoton" onClick="jBuscarRenglones('');" value="...">...</button></td>
	</tr>
</table>

<table width="930"  align="center">
	<tr>
		<td width="100%"  valign="top" colspan="2">
    		<iframe  name="FrameDetalle" frameborder="0"  width="1230" height="250"  marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes" style="border: #DBE2ED 0px solid;" id="cboxmain1" align="left"></iframe>
		</td>
	</tr>
</table>

<table width="100%">
	<tr>
     	<td width="206">&nbsp;</td>
        <td width="4">&nbsp;</td>
        <td width="135">&nbsp;</td>
        <td class="tituloCampo" width="140" align="right"> 
            Total:
         </td>
         <td width="5">&nbsp;</td>
         <td align="left">
     		<input disabled align="left" name="oTotalDebe" class="cNumdisabled" type="text" size="20">     	
		
        	<input  disabled align="left" name="oTotalHaber" class="cNumdisabled" type="text" size="20"> 
		</td>
         <td class="tituloCampo" width="140" align="right">
            Diferencia: 
         <td align="left">
            <input  readonly name="oDiferencia" class="cNumdisabled" type="text" size="15">
         </td>
	</tr>
</table>
</div>
<?php 
$Arretabla2[0][0]= $TablaSelec; //Tabla
$Arretabla2[0][1]= 'T';
$Arretabla2[1][0]= "Comprobant"; //Campo1
$Arretabla2[1][1]= 'C';
$Arretabla2[2][0]= "Concepto"; //Campo2
$Arretabla2[2][1]= 'C';
$Arretabla2[3][0]= "oComprobante"; //Objeto del Campo2
$Arretabla2[3][1]= 'O';
$Arretabla2[4][0]= "oConcepto"; //Objeto del Campo2
$Arretabla2[4][1]= 'O';
$Arretabla2[5][0]= 'frmDiarios';// Pantalla donde estamos ubicados
$Arretabla2[5][1]= 'P';
$Arre2 = array_envia($Arretabla2); // Serializar Array

//onKeyPress="return CheckNumericJEnter(event,this.form,this)"
?>
<table align="center" width="100%">
	<tr>
   		<td align="right"><hr/>
	<?php   if ($cad != 'H' && $cad != 'I'){  ?>

        	<button name="BtnGuardar" type="submit" value="Guardar" onClick="Guardar();">Guardar</button>
   		   	<button name="BtnImprimir" type="submit" value="Imprimir" onClick="<?php print(" RepImprimir('$cad') ");?>">Imprimir</button>
		   	<button name="BtnBuscar" type="submit" value="Buscar" onClick="<?php print("PantallaBuscar('oComprobante','$Arre2')");?>">Buscar</button>
           	<button name="BtnIncluir"  type="submit" value="Incluir" onClick="Limpiar();">Incluir</button>
		   
			<?php 
			//if(VerificarAcceso($numeroMenu, 'D') == false){  
    	    	  echo "<button name='BtnEliminar' type='submit' value='Eliminar' onClick='Eliminar();'>Eliminar</button>";
			//}?> 	   
	<?php   }else{  ?>
		   	<button name="BtnBuscar" type="submit" value="Buscar" onClick="<?php print("PantallaBuscar('oComprobante','$Arre2')");?>">Buscar</button>
  	       	<button name="BtnImprimir" type="submit" value="Imprimir" onClick="<?php print("RepImprimir('$cad')");?>">Imprimir</button>
	<?php   } ?>
	<?php 	if ($cad == 'I'){  ?>		   
	    	<button name="GenerarComprobante" type="submit" value="Generar Comprobante Contable" onClick="<?php print("GenerarCom()");?>">Generar Comprobante Contable</button>
			<?php } ?>  
		</td>
	</tr>
</table>
<input name="StatusOculto" type="hidden" value="IN">
<input name="StatusOcultoReng" type="hidden" value="">
<input name="AccionRenglon" type="hidden" value="">
<input type="hidden" name="TAValores"> 
<input type="hidden" name="TACondicion"> 
<input type="hidden" name="oDesCuentaTemp"> 
<input type="hidden" name="oCodcuentaTemp"> 
<input name="arrdetalle" type="hidden"  value="">
<input name="oOrdenRen" type="hidden" value="">
<input name="sDesactivarColor" type="hidden" value="">
<input name="oNumeroConsecutivo" type="hidden" value=0>
<input name="oFecha" type="hidden" value=0>
<input name="oNumeroRenglones" type="hidden" value=0>
<input name="oTablaSelec" type="hidden" value='<?=$cad?>'>
<input name="oCTAnterior" type="hidden" value=''>
<input name="oDTAnterior" type="hidden" value=''>  
<input name="oDesripcionDT" type="hidden" value=''> 
<input name="oDesripcionIM" type="hidden" value=''> 
<input name="CCSistema" type="hidden" value='<?= $_SESSION["CCSistema"]?>'> 
<input name="sNombreObj" type="hidden" value=''> 
<input name="dValor" type="hidden" value=''> 

<script language="Javascript"> 
  	document.frmDiarios.oCC.value= '<?=$sValoroCC ?>'	   
  	document.frmDiarios.oDesCC.value=  '<?=$sValorDesoCC?>'	   
</script>
  
<input name="sValorDesoCC" type="hidden" value="<?= $sValorDesoCC ?>">
<input name="sValoroCC" type="hidden" value="<?= $sValoroCC ?>">  
<input name="sValorDia" type="hidden" value="<?= $xDia ?>">  
<input name="sValorMes" type="hidden" value="<?= $xMes ?>">  
<input name="sValorAno" type="hidden" value="<?= $xAno ?>">  
<input name="numeroMenu" type="hidden" value="<?= $numeroMenu ?>">  


<input name="DiaProceso" type="hidden" value="<?php $DiaProceso ?>">
<input name="MesProceso" type="hidden" value="<?php $MesProceso ?>">  
<input name="AnoProceso" type="hidden" value="<?php $AnoProceso ?>">
  
  
         </fieldset>
         </div>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div></div>
    </div>
    </div>
</form>

<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>

</body>
</html>
