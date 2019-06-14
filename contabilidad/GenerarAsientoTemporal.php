<?php session_start();
include("FuncionesPHP.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<script language="javascript">
function Generar(){
	document.GenerarAsientosTemporal.target='mainFrame';
	document.GenerarAsientosTemporal.method='post';
	if(document.GenerarAsientosTemporal.txtTipo.value == 'P'){
		document.GenerarAsientosTemporal.action='GuardarPreAsientoTemporal.php';
		} else if(document.GenerarAsientosTemporal.txtTipo.value == 'D'){
		document.GenerarAsientosTemporal.action='GuardarAsientoDepreTemporal.php';
	} else {	
		document.GenerarAsientosTemporal.action='GuardarAsientoTemporal.php';
	}
	document.GenerarAsientosTemporal.submit();
}	
</script>	

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
<div class="noprint">
<?php 
//echo "vacio??".$parTipo;
	//if(($parTipo=="") or ($parTipo=="D")){
	if($parTipo=="D"){
		include("banner_contabilidad2.php"); 
	}
?></div>	
   
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />-->
<form name="GenerarAsientosTemporal" method="post">

<table border="0" width="100%">
        <tr>
<?php 
	if($parTipo!="P"){ 
?>
        	<td class="tituloPaginaContabilidad">
<?php 
	}else{ 
?>
        	<td>
<?php
	} 
?>
<?php 
	$parTipo = trim($parTipo);
    if($parTipo==""){
?>
            	Resultado del Asiento de Cierre Semestral o Mensual
<?php  
	}else{ 
		if($parTipo=="P"){
?>
                &nbsp;
<?php  
		}else{ ?>
                Resultado del Asiento de Depreciaci&oacute;n
<?php 	}						
	}			
?>
             </td>            
        </tr>
</table>

<table align="center" width="100%"> 
    <tr class="tituloColumna" align="center">
        <td class="tituloCampo" width="110">C&oacute;digo</td>		
        <td class="tituloCampo" width="25">CT</td>		
        <td class="tituloCampo" width="250">Descripci&oacute;n</td>		
        <td class="tituloCampo" width="150">Debe</td>		
        <td class="tituloCampo" width="150">Haber</td>		
        <td class="tituloCampo" width="25">DT</td>		
        <td class="tituloCampo" width="100">Documento</td>		
    </tr>
</table>	

<input type="hidden" id="txtTipo" name="txtTipo" value="<?=trim($parTipo)?>">
                    
<table width="100%" align="center"> 
	<tr>
    	<td>
        	<iframe name="FrameDetalle" src="GenerarRenglonesAsientoTemporal.php" frameborder="0" width="1240" height="300"  marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes"  style="border: #DBE2ED 0px solid;" id="cboxmain1" align="left"> </iframe>
        </td>		
	</tr>                     
</table>

<table align="center" width="100%" border="0" class="Acceso"> 
	<tr>
    	<td class="tituloCampo" width="130" align="right">Fecha:</td>
        <td width="181" align="left" class="cabecera">
        	<input  type="text" readonly name="Fecha" class="cNumdisabled" value="0.00">        </td>
		<td class="tituloCampo" width="149" align="right">Totales:</td>		
        <td width="171" align="left" class="cabecera">
        	<input type="text" disabled name="TotalDebe" class="cNumdisabled" value="0.00">
        </td>		
        <td width="187" align="left" class="cabecera">
        	<input type="text" disabled name="TotalHaber" class="cNumdisabled"  value="0.00">
        </td>		
        <td width="28" align="left" class="cabecera"></td>		
        <td width="120" align="left" class="cabecera"></td>		
	</tr>
</table>		


  <div class="x-box-bl">
            <div class="x-box-br">
                <div class="x-box-bc"></div>
            </div>
        </div>
    
<table width="100%">
	<tr>
    	<td align="right"><hr/>
<?php if($parTipo=="P"){?>
            <button name="BtnGenerar1" type="button" maxlength="23" size="10" onClick="Generar();" value="Generar Asientos Pre Cierre Diferido">Generar Asientos Pre Cierre Diferido</button></font></td> 
<?php }elseif($parTipo=="D"){ ?>
            <button name="BtnGenerar" type="button" maxlength="23" size="10" onClick="Generar();" value="Generar Asientos de Depreciaci&oacute;n">Generar Asientos de Depreciaci&oacute;n</button></font></td> 
<?php }else{ ?>	
            <button name="BtnGenerar" type="button" maxlength="23" size="10" onClick="Generar();" value="Generar Asientos en Diarios">Generar Asientos en Diarios</button></font></td> 
<?php } ?>
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


<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>
</html>