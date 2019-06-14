<?php session_start();
include_once('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE 2.0 :. Contabilidad - Conteo Activos FIjos</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" src="./GlobalUtility.js">
</script>
<script>

function BuscarJ(sP,parExceloPdf){
day = new Date();
id = day.getTime();

Ubicacion = document.getElementById('TUbicacion').value;
Departamento = document.getElementById('TDepartamento').value;
Responsable = document.getElementById('TResponsable').value;
DesUbicacion = document.getElementById('TDesUbicacion').value;
DesDepartamento = document.getElementById('TDesDepartamento').value;
DesResponsable = document.getElementById('TDesResponsable').value;

//eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=1,menubar=0,resizable=0,"+result+"');");
eval("page" + id + "= open('','" + id + "','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no');");
//sPar = sPar + "&ExceloPdf="+parExceloPdf;
eval("page" + id + ".location ='"+sP+"?ubicacion="+Ubicacion+"&departamento="+Departamento+"&responsable="+Responsable+"&desubicacion="+DesUbicacion+"&desdepartamento="+DesDepartamento+"&desresponsable="+DesResponsable+"'");
}

<!--*****************************************************************************************-->
<!--*************************PANTALLA BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function PantallaBuscar(sObjeto,oArreglo){
    winOpen('PantallaBuscarFormularios.php?oForma=PlantillaBuscarParametros&oObjeto='+	sObjeto+'&TAValores='+oArreglo,'');
  }// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************BUSCAR*************************************************-->
<!--*****************************************************************************************-->
function Buscar(){
	document.frmdeprecactivos.target='mainFrame';
	document.frmdeprecactivos.method='post';
	document.frmdeprecactivos.action='frmdeprecactivos.php';
	document.frmdeprecactivos.StatusOculto.value='BU';
	document.frmdeprecactivos.submit();
}// function AbrirBus(sObjeto,oArreglo){


<!--*****************************************************************************************-->
<!--**********************************Abrir Ventana de Busqueda******************************-->
<!--*****************************************************************************************-->
function AbrirBus(sObjeto,oArreglo){
	winOpen('PantallaBuscar.php?oForma=PlantillaBuscarParametros&oObjeto='+sObjeto+'&TAValores='+oArreglo,'');
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
</script>
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

<table border="0" width="100%">
    <tr>
        <td class="tituloPaginaContabilidad">Conteo Activos Fijos          
        </td>            
    </tr>
</table>

<form action="PlantillaBuscarSiguiente.php" method="post"  name="PlantillaBuscarParametros">

<?php
    $con = ConectarBD();
?>      
<table width="100%">	
               
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
	$Arretabla5[5][0]= 'PlantillaBuscarParametros'; // Pantalla donde estamos ubicados
	$Arretabla5[5][1]= 'P';
	$Arre5 = array_envia($Arretabla5); // Serializar Array
/***********************************************************************************************/
/*****ESTO SE COLOCO POR PROBLEMAS DEL LENGUAJE DE NO PODER LLAMAR LA FUNCION MAS DE DOS VECES********/
/***********************************************************************************************/
	$sTabla = '';
	$sCampos= '';
	$sPlantillaBus= '';
	$sCondicion = $sClaveCon ."= '".$TUbicacion."'";
	$ArrRec = $Arretabla5;
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
	<tr>
		<td>&nbsp;</td>
	</tr>
   	<tr>    
		<td><fieldset>
		  	<legend class="legend">Conteo Activos Fijos</legend>
		  	<table border="0" align="center">
				<tr>
					<td class="tituloCampo" width="140" align="right"> 
                        <span class="textoRojoNegrita">*</span>Ubicaci&oacute;n:
                    </td>
                    <td align="left">
                        <input  onDblClick="<?php print("PantallaBuscar(this.name,'$Arre5')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre5')");?>" name="TUbicacion" id="TUbicacion" type="text"maxlength=3 size=5  onFocus="SelTexto(this);" value="<?=$TUbicacion?>" class="cTexBox">
                        <input readonly name="TDesUbicacion" id="TDesUbicacion" type="text" size=46 class="cTexBoxdisabled" value="<?=$TDesUbicacion?>">
                    </td>
                </tr>
   
<?php 
	$sClaveCon = 'id_departamento'; // Campo Clave para buscar
	$Arretabla3[0][0]= 'departamento'; //Tabla
	$Arretabla3[0][1]= 'T';
	$Arretabla3[1][0]= 'id_departamento'; //Campo1
	$Arretabla3[1][1]= 'C';
	$Arretabla3[2][0]= 'nombre_dep'; //Campo2
	$Arretabla3[2][1]= 'C';
	$Arretabla3[3][0]= 'TDepartamento'; //objeto Campo1
	$Arretabla3[3][1]= 'O';
	$Arretabla3[4][0]= 'TDesDepartamento'; //objeto Campo2
	$Arretabla3[4][1]= 'O';
	$Arretabla3[5][0]= 'PlantillaBuscarParametros'; // Pantalla donde estamos ubicados
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
	$sql = 'Select ' .$sCampos. ' from '. $sTabla  .' where ' .$sCondicion ;
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesDepartamento= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesDepartamento= '';
	}
?> 
				<tr>
    				<td class="tituloCampo" width="140" align="right">
        				<span class="textoRojoNegrita">*</span>Departamento
                    </td>
                    <td align="left">
                            <input  onDblClick="<?php print("AbrirBus(this.name,'$Arre3')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre3')");?>" name="TDepartamento" id="TDepartamento" type="text"maxlength=3 size=5  onFocus="SelTexto(this);" value="<?=$TDepartamento?>" class="cTexBox">
                            <input readonly name="TDesDepartamento" id="TDesDepartamento" type="text" size=46 class="cTexBoxdisabled" value="<?=$TDesDepartamento?>">
                    </td>
				</tr>
  
<?php 
	$sClaveCon = 'id_responsable'; // Campo Clave para buscar
	$Arretabla4[0][0]= 'responsable'; //Tabla
	$Arretabla4[0][1]= 'T';
	$Arretabla4[1][0]= 'id_responsable'; //Campo1
	$Arretabla4[1][1]= 'C';
	$Arretabla4[2][0]= 'nombre_responsable'; //Campo2
	$Arretabla4[2][1]= 'C';
	$Arretabla4[3][0]= 'TResponsable'; //objeto Campo1
	$Arretabla4[3][1]= 'O';
	$Arretabla4[4][0]= 'TDesResponsable'; //objeto Campo2
	$Arretabla4[4][1]= 'O';
	$Arretabla4[5][0]= 'PlantillaBuscarParametros'; // Pantalla donde estamos ubicados
	$Arretabla4[5][1]= 'P';
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
	$rsTem1 = EjecutarExec($con,$sql);
	if (NumeroFilas($rsTem1) > 0){
		 $TDesResponsable= ObtenerResultado($rsTem1,2);
	}else{
		 $TDesResponsable= '';
	}
?> 

					<tr>
                        <td class="tituloCampo" width="140" align="right"> 
                            <span class="textoRojoNegrita">*</span>Responsable:
                        </td>
                        <td class=cabecera align="left"><input  onDblClick="<?php print("AbrirBus(this.name,'$Arre4')");?>"  onBlur="<?php print("BuscarDescrip(this.value,'$sClaveCon','$Arre4')");?>" name="TResponsable" id="TResponsable" type="text"maxlength=3 size=5  onFocus="SelTexto(this);" value="<?=$TResponsable?>" class="cTexBox">
                        <input readonly name="TDesResponsable" id="TDesResponsable" type="text" size=46 class="cTexBoxdisabled" value="<?=$TDesResponsable?>">
                        </td>     
                    </tr>		  
	  		  <!--solo para un espacio en blanco-->
                    <tr> 
                        <td width="101" height="15">
                            <p> </p>
                        </td>
                     </tr>
				</table>
                </fieldset>
		</td>
	</tr>
</table>
     		  <!--Fin solo para un espacio en blanco--> 
<table width="100%">			  
    		  <!--solo para un espacio en blanco-->
<tr> 
	<td width="100%" align="right"><hr/> 
   		<button id="BtnAceptar1" type="button" align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar1" value="PDF" onClick = "<?php print("BuscarJ('reporteUbicacionResponsableActivo.php','P');")?>"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table>              </button>
   		<button  id="BtnAceptar2" type="submit"  align="middle" onkeyup=fn(this.form,this,event,'') name="BtnAceptar2" value="EXCEL" onClick = "<?php print("BuscarJ('reporteUbicacionResponsableActivo.php','E');")?>" style="display:none">EXCEL</button>
  		</td>
	</tr>

</table>

<input type="hidden" name="TexOcultoStatus">
  		  <!--solo para un espacio en blanco-->
<table>
	<tr> 
    	<td width="101" height="15">
    		<p> </p>
    	</td>
        <td class=cabecera>
        	<input name=StatusOculto type=hidden value=''>
          	<input name=TACondicion type=hidden value=''>
          	<input name=TAValores type=hidden value=''>
          	<input name=Desha type=hidden value="<?= $Desha ?>">
        </td>
    </tr>
  <!--Fin solo para un espacio en blanco--> 
</table>

            
     			  <!--*************************************************************************************************-->  
				  <!--*************************************************************************************************-->  				 
				  <!--*********************************esto se coloco para las busuqedas de tablas*********************-->  
 				  <!--*************************************************************************************************-->  				 
  				  <!--*************************************************************************************************-->  				 
    <input type="hidden" name="TAValores"> 
    <input type="hidden" name="TACondicion"> 
			
</form>
</div>
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