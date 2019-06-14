<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!--<link rel="stylesheet" type="text/css" href="./resources/css/ext-all.css" />
-->

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

<script language="JavaScript"src="./GlobalUtility.js">
</script>
<script language= "javascript" >
<!--*****************************************************************************************-->
<!--************************VER CONFIGURACION DE REPORTE*************************************-->
<!--*****************************************************************************************-->
function Entrar(){
if (document.FrmCambiodeClave.TexConfirmar.value == ''){
        alert("Debe indicar una clave ");
		return;
}
if (document.FrmCambiodeClave.TexConfirmar.value != document.FrmCambiodeClave.TexClave.value){
        alert("La confirmacion es Diferente a la clave");
		return;
}
document.FrmCambiodeClave.target='_self';
document.FrmCambiodeClave.method='post';
document.FrmCambiodeClave.action='GuardarCambiodeClave.php';
document.FrmCambiodeClave.submit();

}

function SelTexto(obj){
if (obj.length != 0){
obj.select();
}
}
</script>
<title>.: SIPRE 2.0 :. Contabilidad - Cambio de Clave</title>
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
<div class="noprint"><?php include("banner_contabilidad.php"); ?></div> 

<form name="FrmCambiodeClave" method="post" action="GuardarCambiodeClave.php">
	 <div style="width:220px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
				<legend class="legend">Cambio de Clave</legend>				
  <fieldset>
				    	 <br> 
                        <td align="right" class="tituloCampo" width="30%">Clave:           
                        <input Maxlength=10 name="TexClave" size="25" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" type="password" class="cTexBox">
                    	</div>
						<br>
                        <td align="right" class="tituloCampo" width="30%">Confirmar:
                        <input Maxlength=10 name="TexConfirmar" size = "25" onFocus="SelTexto(this);"onKeyPress="fn(this.form,this,event,'')" witdh = "15" type="password" class="cTexBox"></label>
                    	</div>
  </fieldset>
            </div>
         </div></div></div>
  <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
    </div>
  <button type="submit" onClick="Entrar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/accept.png"/></td><td>&nbsp;</td><td><font size="-4">Aceptar</font></td></tr></table></button>
    </font>
<?php if ($TMensaje	!= ""){ ?>
   <div style="width:220px;">
        <div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>
        <div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
            <div class="x-form-bd" id="container">
      <P align="center"><font size="-1" color="#CC0000"><?php print($TMensaje); ?> </font></p>
            </div>
         </div></div></div>
        <div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>
    </div>
<?php } ?>	
</form>
<p><font size="-1"></font></p>
<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>
</body>
</html>
