<?php
$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales
WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsEmpresa = mysql_num_rows($rsEmpresa);
$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

$_SESSION['nombreEmpresaUsuarioSysGts'] = $rowEmpresa['nombre_empresa'];
$_SESSION['logoEmpresaSysGts'] = $rowEmpresa['logo_familia'];

$hrefIndex = (file_exists("principal2.php")) ? "principal2.php" : "principal.php";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<script type="text/javascript" language="javascript" src="<?php echo $raiz; ?>js/scriptRafk.js"></script>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr align="left">
    <td><a href="<?php echo $hrefIndex; ?>" target="_blank"><img src="<?php echo $raiz; ?>img/iconos/new_window.png" border="0" title="Abrir Nueva Pesta&ntilde;a"/></a></td>
    <td>&nbsp;</td>
	<td style="font-size:15px; font-weight:bold; color:#bdb5aa;" width="100%">
    	<span style="display:inline-block; text-transform:uppercase; color:#35AFC4; padding-left:2px; text-decoration:none">SIPRE 3.0</span>
        <span style="display:inline-block; text-transform:uppercase; color:#C00; padding-right:2px;"><?php echo (strstr($_SESSION['database_conex'], "prueba")) ? "(SISTEMA DE PRUEBA)" : ""; ?></span>
	</td>
	<td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;"><img src="<?php echo $raiz; ?>img/iconos/building.png" title="Empresa"/><?php echo $_SESSION['nombreEmpresaUsuarioSysGts']; ?></td>

    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;" ><img src="<?php echo $raiz; ?>img/iconos/ico_cliente.gif" title="Usuario"/><a href="<?php echo $raiz; ?>pg_cambio_clave.php" title="Cambiar Clave de Usuario"><font color="#FFFFFF"><?php echo " ". $_SESSION['nombreUsuarioSysGts']; ?></font></a></td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;"><img src="<?php echo $raiz; ?>img/iconos/ico_cargo.gif" title="Departamento / Cargo"/><?php echo " ".$_SESSION['cargoUsuarioSysGts']; ?></td>
	<td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
    <td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:11px;"><img src="<?php echo $raiz; ?>img/iconos/ico_fecha.gif" title="Fecha"/><?php echo " ". date("d-m-Y"); ?></td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>
	<td>&nbsp;</td>
    <td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c11.png" width="9" border="0"/></td>
    <td nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:9px;"><img src="<?php echo $raiz; ?>img/iconos/ico_reloj.gif" title="Hora"/></td>    <td id="tdHoraSistema" nowrap="nowrap" style="background-image:url(<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c25.png); background-repeat:repeat-x; color:#FFFFFF; font-size:9px;"></td> 
	<td><img src="<?php echo $raiz; ?>img/erp/menu_repuestos_r2_c15.png" width="9" border="0"/></td>

</tr>
</table>
<iframe id="iframeReloj" name="iframeReloj" style="display:none"></iframe>

<script type="text/javascript">
function mueveRelojPHP() {
	document.getElementById('iframeReloj').src = '<?php echo $raiz."reloj.php"; ?>';
	
    setTimeout("mueveRelojPHP();",30000)
}
mueveRelojPHP();
</script>

</head>
</html>