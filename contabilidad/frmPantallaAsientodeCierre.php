<?php session_start();
include_once('FuncionesPHP.php');
$_SESSION["pag"] = 3;
  $conectadosNormal= verificarConectados("N");
	if(count($conectadosNormal) > 0){
		echo "<script language='javascript'>
				alert('Existen usuarios conectados debe esperar a que se desconecten');
			  	location.href='ListadoConectados.php';
			  </script>";
	}else{
		registrar("E");
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

<title>.: SIPRE 2.0 :. Contabilidad - Asiento de Cierre</title>
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
function Aceptar(){
		document.frmPantallaAsientodeCierre.target='FrameOperacion';
		document.frmPantallaAsientodeCierre.method='post';
		document.frmPantallaAsientodeCierre.action='AsientodeCierre.php';
		document.frmPantallaAsientodeCierre.submit();
}

function ver(){
		/* document.frmPantallaAsientodeCierre.target='FrameOperacion';
		document.frmPantallaAsientodeCierre.method='post';
		document.frmPantallaAsientodeCierre.action='verquecuenta.php';
		document.frmPantallaAsientodeCierre.submit(); */
}
</script>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaContabilidad">Asiento de Cierre Semestral o Mensual</td>            
        </tr>
</table>
<form name="frmPantallaAsientodeCierre" action="frmPantallaAsientodeCierre.php" method="post">


<table width="100%">
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Asiento de Cierre</legend>
        	<table border="0" align="center">        		
				<tr onClick="ver()">
					<td  height=20 align="justify">Este proceso GENERA el asiento de cierre de las cuentas de resultado, reversando en un asiento las  cuentas de INGRESO y EGRESOS, colocando el total en la cuenta de Ganancias y P&eacute;rdidas, que es parametrizada en la pantalla de Par&aacute;metros.
	                </td> 
            	</tr>
			</table>
            </fieldset>
		</td>
	</tr>
</table>
                

        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>



<table width="100%">
	<tr>
  		<td align="right"><hr/>
        	<button name="BtnAceptar" type="submit" onClick="Aceptar();" value="Aceptar">Aceptar</button>
        </td> 
	</tr>
   	<tr>
  		<td><iframe name="FrameOperacion" border="0" frameborder="0" width="1240" height="460"  marginheight="2" marginwidth="2" scrolling="no" allowtransparency="yes"  style="border: #DBE2ED 1px solid;" id="cboxmain1" align="left"> </iframe>
        </td>
	</tr>    
</table> 

</form>
</div>






<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
<?php } ?>