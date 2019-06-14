<?php
require_once ("../connections/conex.php");

@session_start();

if(isset($idEmpresa)){
	$idEmpresa = (!isset($_SESSION['idEmpresaUsuarioSysGts'])) ? NULL : $_SESSION['idEmpresaUsuarioSysGts'];
}

$query = sprintf("SELECT * FROM sa_v_empresa_sucursal WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rs = mysql_query($query, $conex) or die(mysql_error());
$row = mysql_fetch_assoc($rs);
//barra alternativa: <div style="position:fixed;left:49%;top:49%;z-index:500;height:31px;width:31px;background-image:url(../img/iconos/multibox_loader.gif);background-repeat:no-repeat;z-index:120;" id="load_animate" >&nbsp;</div>
?>
<div id="load_animate" >&nbsp;</div>

<script type="text/javascript">
if(typeof(xajax)!='undefined'){
	if(xajax!=null){
		xajax.callback.global.onRequest = function() {
			//xajax.$('loading').style.display = 'block';
			document.getElementById('load_animate').style.display='';
		}
		xajax.callback.global.beforeResponseProcessing = function() {
			//xajax.$('loading').style.display='none';
			document.getElementById('load_animate').style.display='none';
		}
	}
}else{
	document.getElementById('load_animate').style.display='none';
}
</script>

<table cellpadding="0" cellspacing="0" width="100%" class="noprint" >
<tr>
	<td colspan="3" height="10"></td>
</tr>
<tr>
	<td align="left" width="40%"><img src="../img/servicios/imgmodulo.png"/></td>
    <td align="center" width="20%"><img src="../<?php  echo ($row['logo_familia']) ? $row['logo_familia'] : NULL; ?>" height="80"></td>
    <td align="right" width="40%"><img src="../img/logos/logo_gotosystems.jpg" height="80"></td>
</tr>
<tr>
	<td colspan="3" height="4"></td>
</tr>
<tr>
	<td align="center" class="textoBlancoNegrita_12px" colspan="3" style="background-image:url(../img/servicios/piepagina.png); color:white; font-weight: bold;" height="35">Copyright 2008, Goto Systems C.A. All rights reserved | Privacy Policy | Copyrights Information</td>
</tr>
</table>