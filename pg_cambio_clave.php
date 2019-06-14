<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
/*if(!(validaAcceso("pg_cambio_clave"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}*/
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_cambio_clave.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Cambiar Contraseña</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleLogin.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragInforme.css">
    <script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    
    <script src="js/login-modernizr/modernizr.custom.63321.js"></script>
    <link rel="stylesheet" type="text/css" href="js/login-modernizr/font-awesome.css"/>
    
	<script type="text/javascript" language="javascript">
	function validarFrmUsuario() {
		var ob = document.getElementById('txtPasswordActual');
		if (ob.value == "") {
			alert("Ingrese la contraseña actual");
			ob.focus();
			return false;
		}
		if (ob.value.length < 4) {
			alert("La contraseña actual debe contener al menos 4 caracteres");
			ob.focus();
			return false;
		}
		var ob2 = document.getElementById('txtPasswordNuevo');
		if (ob2.value == "") {
			alert("Ingrese la contraseña nueva");
			ob2.focus();
			return false;
		}
		if (ob2.value.length < 4) {
			alert("La contraseña nueva debe contener al menos 4 caracteres");
			ob2.focus();
			return false;
		}
		var ob3 = document.getElementById('txtPasswordConfirmar');
		if (ob2.value != ob3.value) {
			alert("Las contraseñas no coinciden");
			ob3.focus();
			return false;
		}
		if (ob.value == ob2.value) {
			alert("La contraseña nueva tiene que ser diferente a la anterior");
			ob3.focus();
			return false;
		}
		
		xajax_guardarUsuario(xajax.getFormValues('frmUsuario'), '');
	}
	
	function validarFrmClaveEspecial() {
		var ob = document.getElementById('txtPasswordClaveEspecial');
		if (ob.value == "") {
			alert("Ingrese la contraseña nueva");
			ob.focus();
			return false;
		}
		if (ob.value.length < 4) {
			alert("La contraseña actual debe contener al menos 4 caracteres");
			ob.focus();
			return false;
		}
		
		xajax_guardarUsuario('', xajax.getFormValues('frmClaveEspecial'));
	}
	</script>
    
    <style>
	body {
		background: #365A96 url(img/login/blurred<?php echo rand(1, 13); ?>.jpg) no-repeat center top;
		background-attachment:fixed;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		background-size: cover;
	}
	</style>
</head>

<body>
    <div id="wrapper">
    <form id="frmUsuario" name="frmUsuario" class="form-5" onsubmit="return false;">
        <table cellpadding="0" cellspacing="0" style="font-size:15px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; border-bottom:1px solid #333333; text-shadow:0 2px 0 rgba(102,102,102,0.8); box-shadow:0 1px 0 rgba(102,102,102,0.8);" width="100%">
        <tr>
            <td align="left">
                <span style="display:inline-block; text-transform:uppercase; color:#B7D154; padding-right:2px;">Cambiar Contraseña</span><!-- or sign up-->
            </td>
            <td align="right"><span>Sistema</span> <span style="display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;">SIPRE <?php echo cVERSION; ?></span></td>
        </tr>
        </table>
    
        <table style="padding-top:10px; border-top:1px solid rgba(255,255,255,1);" width="100%">
        <tr>
            <td>
            	<p class="field">
                    <input type="password" id="txtPasswordActual" name="txtPasswordActual" placeholder="Contraseña Actual">
                    <i class="icon-lock icon-large"></i>
                </p>
            	<p class="field">
                    <input type="password" id="txtPasswordNuevo" name="txtPasswordNuevo" placeholder="Contraseña Nueva">
                    <i class="icon-lock icon-large"></i>
                </p>
            	<p class="field">
                    <input type="password" id="txtPasswordConfirmar" name="txtPasswordConfirmar" placeholder="Confirmar Contraseña Nueva">
                    <i class="icon-lock icon-large"></i>
                </p>
                <p>&nbsp;</p>
                <p><input type="submit" id="cambiar" name="cambiar" onclick="validarFrmUsuario()" value="Cambiar Contraseña"></p>
            </td>
        </tr>
        </table>
        <input type="hidden" id="hddIdUsuario" name="hddIdUsuario"/>
    </form>
    
    <form id="frmClaveEspecial" name="frmClaveEspecial" class="form-5" onsubmit="return false;">
        <table cellpadding="0" cellspacing="0" style="font-size:15px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; border-bottom:1px solid #333333; text-shadow:0 2px 0 rgba(102,102,102,0.8); box-shadow:0 1px 0 rgba(102,102,102,0.8);" width="100%">
        <tr>
            <td align="left">
                <span style="display:inline-block; text-transform:uppercase; color:#B7D154; padding-right:2px;">Claves Especiales Asignadas</span><!-- or sign up-->
            </td>
        </tr>
        </table>
    
        <table style="padding-top:10px; border-top:1px solid rgba(255,255,255,1);" width="100%">
        <tr>
        	<td>
                <p class="field">
                	<input type="password" id="txtPasswordClaveEspecial" name="txtPasswordClaveEspecial" placeholder="Contraseña Nueva">
                    <i class="icon-lock icon-large"></i>
				</p>
                <p></p>
                <p></p>
                <p>&nbsp;</p>
                <p><input type="submit" id="cambiar" name="cambiar" onclick="validarFrmClaveEspecial();" value="Cambiar Contraseña"></p>
			</td>
        </tr>
        <tr>
            <td>
            	<div id="divListaClavesEspeciales" style="max-height:130px; overflow:auto; width:100%;"></div>
            </td>
        </tr>
        </table>
        <input type="hidden" id="hddIdUsuarioClaveEspecial" name="hddIdUsuarioClaveEspecial"/>
	</form>
    </div>
</body>
</html>

<script language="javascript" type="text/javascript">
byId('txtPasswordActual').focus();
<?php if ($_GET['acc'] == 1) echo "alert('Su contraseña de acceso ha caducado por favor introduzca una nueva');"; ?>
xajax_cargarUsuario('<?php echo $_SESSION['idUsuarioSysGts']; ?>');
</script>