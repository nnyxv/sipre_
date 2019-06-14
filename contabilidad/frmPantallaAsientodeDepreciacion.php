<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();
	$SqlStr='SELECT Fechacomp_cierr FROM parametros';
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		if(NumeroFilas($exc) > 0){
			$fproceso = trim(ObtenerResultado($exc,1));
		}
	$dDesde1  = $fproceso;
	$anoMes = obFecha($fproceso,"M");
	$Anomes = obFecha($fproceso,"A");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Asiento de Depreciacion</title>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">
</head>

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">

<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

<body>
<div id="divGeneralPorcentaje">
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div>
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--***********************************FUNCIONES JAVASCRIPT**********************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<script language="JavaScript"src="./GlobalUtility.js"></script>
<script language= "javascript" >
function fun_Aceptar(dm,da){
	//alert(da);
	document.frmPantallaAsientodeDepreciacion.target='mainFrame';
	document.frmPantallaAsientodeDepreciacion.method='post';
	//document.frmPantallaAsientodeDepreciacion.action='GenerarAsientoDepre.php?desMes=dm&desAno=da';	
	document.frmPantallaAsientodeDepreciacion.action='GenerarAsientoDepre.php?desMes='+dm+'&desAno='+da;	
	//alert(document.frmPantallaAsientodeDepreciacion.action='GenerarAsientoDepre.php?desMes='+dm+'&desAno='+da);
	document.frmPantallaAsientodeDepreciacion.submit();
}
function validar(){
	desdeMes = document.getElementById("desMes").selectedIndex;
	//desdeAno = document.getElementById("desAno").selectedIndex;
	desdeAno = document.getElementById("desAno").value;
		if(desdeMes == "" && desdeAno == ""){
			alert("Debe seleccionar un rango de fecha");
		} else {
			fun_Aceptar(desdeMes,desdeAno)
		}
}
</script>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<table border="0" width="100%">
	<tr>
	   	<td class="tituloPaginaContabilidad">
        	Generar Asiento de Depreciaci&oacute;n
        </td>
    </tr>
</table>

<form name="frmPantallaAsientodeDepreciacion" action="frmPantallaAsientodeDepreciacion.php" method="post">

<table width="100%">
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Asiento de Depreciaci&oacute;n</legend>
        	<table border="0" align="center">
        		<tr>
                    <td align="justify" width="100%"> 
                        ATENCI&Oacute;N: Este proceso genera el asiento de depreciación de los activos, el mismo debe realizarse antes de cada cierre mensual. 	
                    </td> 
                </tr>
                <tr>
                	<td>&nbsp;</td>
                </tr>
            </table>

            <table width="25%" align="center">
                <tr>
                    <td class="tituloCampo" width="141" align="right">Mes:
                    </td>
                    <td width="28%" align="left">
                        <select id="desMes" name="desMes">
                            <option value="">...</option>
<?php 
	$num = 01;
	for($i = round($num,2); $i <= 12; $i++){
		if($i < 10){
			$num = "0".$i;
		}else {
			$num = $i;
		}
		if($num == $anoMes)
			$checked = "selected='selected'";
		else 
			$checked = "";
		echo "<option value=".$num." ".$checked.">".$num."</option>";	
	}
?>
			</select>
                  </td>
                  <td class="tituloCampo" width="141" align="right">
                      Año:
                  </td>
                  <td width="32%" align="left">
                      <select id="desAno" name="desAno">
                          <option value="">...</option>
<?php 
	for($i = 2010; $i <= 2040; $i++){
		if($i == $Anomes)
			$checked = "selected='selected'";
		else 
			$checked = "";
		echo "<option value=".$i." ".$checked.">".$i."</option>";							
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
        	<button name="BtnAceptar" type="button" onClick="validar();" class="inputBoton">Aceptar</button>
                <!--<input name="BtnAceptar" type="button" maxlength="23" size="10" onClick="validar();" value="Aceptar" class="inputBoton"></font>-->
		</td> 
	</tr>
</table>
    
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
