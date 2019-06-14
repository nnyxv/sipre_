<?php
require_once ("../connections/conex.php");

@session_start();

$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
$rs = mysql_query($query, $conex) or die(mysql_error());
$row = mysql_fetch_assoc($rs);
?>
<div id="load_animate">&nbsp;</div>

<script type="text/javascript">
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
	<td align="left" width="40%"><img src="../img/tesoreria/imgmodulo.png"/></td>
    <td align="center" width="20%"><img src="../<?php echo $row['logo_familia'];?>" height="80"></td>
    <td align="right" width="40%"><img src="../img/logos/logo_gotosystems.jpg" height="80"></td>
</tr>
<tr>
	<td colspan="3" height="4"></td>
</tr>
<tr>
	<td align="center" class="textoBlancoNegrita_12px" colspan="3" style="background-image:url(../img/tesoreria/piepagina.png)" height="35">Copyright 2008, Goto Systems C.A. All rights reserved | Privacy Policy | Copyrights Information</td>
</tr>
</table>