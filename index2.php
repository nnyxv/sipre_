<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
/*include('inc_sesion.php');
if(!(validaAcceso("re"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}*/
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
// se incluye el plugin
require_once 'controladores/xajax/xajax_core/xajaxPlugin.inc.php';
require_once 'controladores/xajax/xajax_core/xajaxPluginManager.inc.php';
//require_once 'controladores/xajax/xajax_plugins/response/comet/comet.inc.php';
//Configuranto la ruta del manejador de script
//$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_index2.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();

if ($_SESSION['session_first_select'] == false) {
	// BUSCA LA EMPRESA PREDETERMINADA
	$queryUsuarioEmpresa = sprintf("SELECT
		vw_iv_usu_emp.id_empresa_reg,
		IF (vw_iv_usu_emp.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_usu_emp.nombre_empresa, vw_iv_usu_emp.nombre_empresa_suc), vw_iv_usu_emp.nombre_empresa) AS nombre_empresa,
		vw_iv_usu_emp.logo_familia
	FROM vw_iv_usuario_empresa vw_iv_usu_emp
	WHERE id_usuario = %s
		AND predeterminada = 1;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuarioEmpresa = mysql_query($queryUsuarioEmpresa) or die(mysql_error()."<br><br>Line: ".__LINE__);
	$totalRowsUsuarioEmpresa = mysql_num_rows($rsUsuarioEmpresa);
	$rowUsuarioEmpresa = mysql_fetch_assoc($rsUsuarioEmpresa);

	if ($totalRowsUsuarioEmpresa == 0) {
		// BUSCA LA PRIMERA EMPRESA QUE TENGA AGREGADA EL USUARIO
		$queryUsuarioEmpresa = sprintf("SELECT
			vw_iv_usu_emp.id_empresa_reg,
			IF (vw_iv_usu_emp.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_usu_emp.nombre_empresa, vw_iv_usu_emp.nombre_empresa_suc), vw_iv_usu_emp.nombre_empresa) AS nombre_empresa,
			vw_iv_usu_emp.logo_familia
		FROM vw_iv_usuario_empresa vw_iv_usu_emp
		WHERE id_usuario = %s;",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuarioEmpresa = mysql_query($queryUsuarioEmpresa) or die(mysql_error()."<br><br>Line: ".__LINE__);
		$totalRowsUsuarioEmpresa = mysql_num_rows($rsUsuarioEmpresa);
		$rowUsuarioEmpresa = mysql_fetch_assoc($rsUsuarioEmpresa);
		
		if ($totalRowsUsuarioEmpresa == 0) {
			echo "
			<script>
			alert('Usted no tiene UsuarioEmpresa(s)/Sucursal(es) Asignada(s), consulte al Administrador del Sistema');
			window.location = 'index.php';
			</script>";
			exit;
		} else {
			$updateSQL = sprintf("UPDATE pg_usuario_empresa SET
				predeterminada = 1
			WHERE id_usuario_empresa = %s;",
				valTpDato($rowUsuarioEmpresa['id_usuario_empresa'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."<br><br>Line: ".__LINE__);
			
			$mensaje = true;
		}
	}
	$_SESSION['idEmpresaUsuarioSysGts'] = $rowUsuarioEmpresa['id_empresa_reg'];
	$_SESSION['session_first_select'] = true;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :.</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
        <span class="textoNegroNegrita_24px">SISTEMA SIPRE</span>
        <br>
        <span class="textoGrisNegrita_13px">Versión <?php echo cVERSION; ?></span>
    	
        <br><br>
        
        <table align="center">
        <tr>
        	<td>
            <form id="frmEmpresa" name="frmEmpresa" style="margin:0" onsubmit="return false;">
            <fieldset>
                <legend class="legend">Seleccione Empresa</legend>
                
                <table align="center" width="360">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="30%">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <button type="submit" onClick="xajax_asignarEmpresa(xajax.getFormValues('frmEmpresa'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/accept.png"/></td><td>&nbsp;</td><td>Aceptar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
            </td>
		</tr>
        </table>
	</div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>
<?php
if ($mensaje)
	echo "<script>alert('Usted no tenia asignada una empresa predeterminada, debido a eso, se le asignó la que verá a continuación');</script>"; ?>
<script>
xajax_cargarSesion();
</script>