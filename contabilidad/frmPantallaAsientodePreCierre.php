<?php session_start();
include_once('FuncionesPHP.php');
$_SESSION["pag"] = 5;
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

<title>.: SIPRE 2.0 :. Contabilidad - Asiento de PreCierre</title>
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
		document.frmPantallaAsientodePreCierre.target='FrameOperacion';
		document.frmPantallaAsientodePreCierre.method='post';
		document.frmPantallaAsientodePreCierre.action='AsientodePreCierre.php';
		document.frmPantallaAsientodePreCierre.submit();
}

function ver(){
	/*  	document.frmPantallaAsientodePreCierre.target='FrameOperacion';
		document.frmPantallaAsientodePreCierre.method='post';
		document.frmPantallaAsientodePreCierre.action='verquecuenta.php';
		document.frmPantallaAsientodePreCierre.submit();  */
}
</script>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<table border="0" width="100%">
	<tr>
    	<td class="tituloPaginaContabilidad">Generar Asiento de Pre-Cierre
        </td>
    </tr>
</table>
<form name="frmPantallaAsientodePreCierre" action="frmPantallaAsientodePreCierre.php" method="post">

<table width="100%">
	<tr>
   		<td>&nbsp;</td>
    </tr>
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Asiento de Pre-Cierre</legend>
        	<table border="0" align="center">
                <tr onClick="ver()">
                    <td align="justify">Este proceso GENERA el asiento de Pre Cierre de las cuentas de resultado, reversando en un asiento las  cuentas de INGRESO y EGRESOS, colocando el total en la cuenta de Ganancias y P&eacute;rdidas, que es parametrizada en la pantalla de Par&aacute;metros. No podr&aacute; realizar el cierre de ejercicio con un asiento de Pre Cierre.
                    </td> 
                </tr>
                <tr>
                    <td>&nbsp;
                    </td>
                </tr>
			</table>


			<table width="25%" align="center">
				<tr>
                    <td class="tituloCampo" width="140" align="right">
                        Mes y A&ntilde;o:
                    </td>
                    <td>
                        <select id="txtMes" name="txtMes">
                            <option value=0>..</option>
<?php 
	for($imes=1;$imes<=12;$imes++){
		echo "<option value=$imes>$imes</option>";    
	}
?>
                        </select>
                    </td>
                    <td>
                        <select id="txtAno" name="txtAno">
                            <option value=0>....</option>
<?php 
	for($iAno=2011;$iAno<=2030;$iAno++){
		echo "<option value=$iAno>$iAno</option>";    
	}
?>
                        </select>
                    </td> 
                </tr>
            </table>
            </fieldset>
		</td>
	</tr>
</table>

<table width="100%">
	<tr>
  		<td align="right"><hr/>
        	<button name="BtnAceptar" type="submit" onClick="Aceptar();" value="Aceptar">Aceptar</button>
        </td> 
	</tr>
</table>

<table width="100%">   
   	<tr>
  		<td align="center"><iframe name="FrameOperacion" border="0" frameborder="0" width="1240" height="460"  marginheight="2" marginwidth="2" scrolling="no" allowtransparency="yes" style="border: #DBE2ED 0px solid;" id="cboxmain1" align="left"> </iframe></td>
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