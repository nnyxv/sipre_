<?php session_start();
$_SESSION["pag"] = 1;
include_once('FuncionesPHP.php');
	 $xMes =obFecha($_SESSION["sFec_Proceso"],'M');
	 $xAno =obFecha($_SESSION["sFec_Proceso"],'A');
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

<title>.: SIPRE 2.0 :. Contabilidad - Cierre Mensual</title>
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
		//document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.target='topFrame';
		document.frmPantallaCierreMes.method='post';
		if (document.frmPantallaCierreMes.oEstado.value != "E"){		
		     document.frmPantallaCierreMes.action='CerrarMes.php';
		}else{
    		document.frmPantallaCierreMes.action='CerrarMesEstado.php';
		}
		document.frmPantallaCierreMes.submit();
}
</script>

<!--*****************************************************************************************-->
<!--*****************************************************************************************-->
<!--************************************FORMULARIO HTML**************************************-->
<!--*****************************************************************************************-->
<!--*****************************************************************************************-->

<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaContabilidad">Cierre Mensual</td>            
        </tr>
</table>

<form name="frmPantallaCierreMes"action="frmPantallaCierreMes.php"method="post">

<table width="100%" border=0>
	<tr>    
		<td><fieldset>
		  	<legend class="legend">Cierre Mensual
        	</legend>
		  	<table border="0" align="center">
        		<tr>
  					<td align="justify" width="100%"> 
<?php 
	if( $_SESSION["CCSistema"] == ''){
		$Estado = "";
?>		 
						Este proceso envia todos los registros que se encuentran actualmente en los movimientos diarios a Archivos hist&oacute;ricos y los movimientos posteriores del mes siguiente los registra como movimientos diarios.
<?php 
	}else{
		$Estado = "E";
?>
						Este proceso envia todos los registros que se encuentran actualmente en los movimientos diarios a archivos hist&oacute;ricos y maovimnientos importados que podr&aacute;n ser visualizados y contabilizados y los movimientos posteriores del mes siguiente los registra como movimientos Diarios.
<?php 
	}
?>		 		 
					</td>		 
				</tr>
   			 <tr>
    			<td>&nbsp;
      			</td>
    		</tr>
		</table>

        <table width="30%" align="center">	
            <tr>
                <td class="tituloCampo" width="144" align="right"> 
                    Mes: 
                </td>
                <td align="left">
                    <input  readonly name="TMesCierre"  value="<?= $xMes ?>" type="text"maxlength=23 size=10 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" class="cTexBoxdisabled">	
                </td>
                <td class="tituloCampo" width="144" align="right">
                    Año: 
                </td>
                <td align="left">
                    <input  readonly  name="TAnoCierre" value="<?= $xAno ?>" type="text"maxlength=23 size=10 onFocus="SelTexto(this);"onKeyPress="CheckNumericJEnter(this.form,this,event,'')" class="cTexBoxdisabled">
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
<input type='hidden' name='oEstado' value="<?=$Estado?>">
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
<p>&nbsp;</p>
<p>&nbsp;</p>



<div class="noprint">
        <?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
<script language="JavaScript" type="text/JavaScript">
</script>



<?php } ?>