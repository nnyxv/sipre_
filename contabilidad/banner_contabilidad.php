<?php
require_once ("../connections/conex.php");

$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
?>

<link rel="stylesheet" href="../js/styleDropdownMenuContabilidad.css" type="text/css" />

<script type="text/javascript" language="JavaScript" src="../js/dropdownMenu/scriptDropdownMenu.js"></script>

<?php //echo var_dump($_SESSION);?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr height="6">
	<td></td>
</tr>
<tr>
	<td><?php $raiz = "../"; include("../banner_sesion_contab.php"); ?></td>
</tr>
<tr height="6">
	<td></td>
</tr>
<tr>
	<td style="border-radius:6px;" background="../img/contabilidad/header.gif">
<ul class="menu" id="menu">
	<li><a href="javascript: window.parent.document.getElementById('aMenuErp').click();" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="../img/contabilidad/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Men&uacute; ERP"); ?></td></tr></table></a></li>
	<!--<li><a href="iv_surtido_taller_list.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="../img/caja_rs/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Surtido"); ?></td></tr></table></a></li>-->

</ul>
	</td>
</tr>
</table>
<script type="text/javascript">
var menu = new menu.dd("menu");
menu.init("menu","menuhover");
</script>