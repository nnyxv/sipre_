<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE 2.0 :. Contabilidad - Pantalla de Busqueda</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" src="./GlobalUtility.js">
</script>
<script language="JavaScript">

function jBuscar(){
   document.PantallaBuscarFormularios.method='post';
   document.PantallaBuscarFormularios.target='FrameDetalle';
   document.PantallaBuscarFormularios.action='RenglonesBusquedaFormularios.php';
   document.PantallaBuscarFormularios.submit();
}

function jSalir(){
   window.close();
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
<body onLoad="javascript:document.PantallaBuscarFormularios.TexCodigoBus.focus();" class="bodyVehiculos">
<div id="divGeneralPorcentaje">
<?php include("FuncionesPHP.php");?>
<div id="divInfo" class="print">
<form name="PantallaBuscarFormularios" Method="post">

<table border="0" width="100%">
	<tr>
       	<td class="tituloPaginaContabilidad">Buscar
        </td>
    </tr>
</table>

<table  width="100%" border="0"  align="right" cellpadding="0" cellspacing="0">
	<tr>
       	<td>&nbsp;</td>
    </tr>
	<tr align="left">
		<td align="right" class="tituloCampo" width="70">C&oacute;digo</td>
        <td>
        	<input size=28 onKeyPress="fn(this.form,this,event,'')" class="colorfondo" type="text" name="TexCodigoBus" value=" ">
        </td>
		<td align="right" class="tituloCampo" width="70">Descripci&oacute;n</td>
        <td>
        	<input size=51 onKeyPress="fn(this.form,this,event,'')" type="text" name="TexDescripcionBus" value="" style="text-align:center" class="colorfondo">
        </td>
        <td align="left">
        	<button type="submit" name="BtnBuscar"  value="Buscar" onClick="jBuscar();">Buscar</button>						
        </td>
       <!--<td width="30%" class="cabeceraNegra">Estatus</td>-->
	</tr>
<!--	<tr>
    	<td>&nbsp;</td>
    </tr>-->
    
	<tr>
    	<td width="100%" colspan="30"><br /><iframe name="FrameDetalle" frameborder="0" width="100%" height="330"  marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes" name="I5" style="border: #DBE2ED 0px solid;" id="cboxmain1" align="left"> </iframe> </td>
	</tr>
	<tr>
		<td width="100%"  align="center" colspan="4" class="cabecera2">
     		<button class="inputBoton"   type="button" name="BtnSalir" id="BtnSalir"  value="   Salir   " onClick="jSalir();" >Salir</button>
    	</td>
    </tr>
</table> 


<input size=40 type="hidden" name="TexBusoForma" value="<?php  print($oForma); ?>">
<input size=40 type="hidden" name="TexBusoObjeto" value="<?php  print($oObjeto); ?>">
<input size=40 type="hidden" name="TAValores" value="<?php  print(array_envia(array_recibe($TAValores))); ?>">
</form>

<script language="JavaScript">
	jBuscar();
</script>


</body>
</html>
