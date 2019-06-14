<?php
require_once("control/model/model.conf.php");

$conet = mysql_connect(connectHOST, connectUSER, connectPASSWORD) or die(mysql_error()); 
mysql_select_db(connectBASENAME, $conet);
@session_start();

$query = sprintf("SELECT * FROM sa_v_empresa_sucursal WHERE id_empresa = '%s'",
	$_SESSION['idEmpresaUsuarioSysGts']);
$rs = mysql_query($query, $conet) or die(mysql_error());
$row = mysql_fetch_assoc($rs);
?>

<table cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td colspan="3" height="10"></td>
</tr>
<tr>
	<td align="left" width="40%"><!--<img src="img/erp/imgmodulo.png"/>--></td>
    <td align="center" width="20%"><img src="<?php echo $row['logo_familia'];?>" height="80"></td>
    <td align="right" width="40%"><img src="img/logos/logo_sipre.jpg" height="80"></td>
</tr>
<tr>
	<td colspan="3" height="4"></td>
</tr>
<tr>
	<td align="center" class="textoBlancoNegrita_12px" colspan="3" style="border-radius:6px;" background="img/erp/header.gif" height="35">Copyright 2008, <a class="linkBlanco" href="http://www.gotosys.com" target="_blank">Goto Systems C.A.</a> All rights reserved | Privacy Policy</td>
</tr>
</table>