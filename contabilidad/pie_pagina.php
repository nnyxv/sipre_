<div id="load_animate">&nbsp;</div>

<script type="text/javascript">	
var cerrarVentana = true;
window.onbeforeunload = function() {
	if (cerrarVentana == false) {
		return "Se recomienda CANCELAR este cuadro de mensaje\n\nDebe Cerrar la Ventana de ERP para efectuar las transacciones efectivamente";
	}
}

if (typeof(xajax) != 'undefined') {
	if(xajax != null){
		xajax.callback.global.onRequest = function() {
			//xajax.$('loading').style.display = 'block';
			document.getElementById('load_animate').style.display='';
		}
		xajax.callback.global.beforeResponseProcessing = function() {
			//xajax.$('loading').style.display='none';
			document.getElementById('load_animate').style.display='none';
		}
	}
}
document.getElementById('load_animate').style.display='none';
</script>

<table cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td colspan="3" height="10"></td>
</tr>
<tr>
	<td align="left" width="40%"><img src="../img/contabilidad/imgmodulo.png"/></td>
    <td align="center" width="20%"><img src="<?php echo (strlen($_SESSION['logoEmpresaSysGts']) > 5) ? "../".$_SESSION['logoEmpresaSysGts'] : ""; ?>" height="80"/></td>
    <td align="right" width="40%"><img src="../img/logos/logo_gotosystems.jpg" height="80"></td>
</tr>
<tr>
	<td colspan="3" height="4"></td>
</tr>
<tr>
	<td align="center" class="textoBlancoNegrita_12px" colspan="3" style="border-radius:6px;" background="../img/contabilidad/header.gif" height="35">Copyright 2008, <a class="linkBlanco" href="http://www.gotosys.com" target="_blank">Goto Systems C.A.</a> All rights reserved | Privacy Policy</td>
</tr>
</table>