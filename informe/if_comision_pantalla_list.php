<?php
require_once("../connections/conex.php");
include('../js/libGraficos/Code/PHP/Includes/FusionCharts.php');
include('../js/libGraficos/Code/PHP/Includes/FC_Colors.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
/*if(!(validaAcceso("if_comisiones_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}*/
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_if_comision_pantalla_list.php");

//$xajax->setFlag('debug',true); 
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Informe Gerencial - Comisiones por Pantalla</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../style/styleInforme.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragInforme.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function validarFrmBuscar() {
		if (validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('txtFecha','t','') == true
		&& validarCampo('lstCargo','t','lista') == true
		&& validarCampo('lstEmpleado','t','lista') == true) {
			xajax_buscarComision(xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('lstEmpresa','t','lista');
			validarCampo('txtFecha','t','');
			validarCampo('lstCargo','t','lista');
			validarCampo('lstEmpleado','t','lista');
			
			alert('Los campos señalados en rojo son requeridos');
			return false;
		}
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div>
	<?php include("banner_informe.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaInforme">Comisiones por Pantalla</td>
        </tr>
        <tr>
            <td>
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Mes - Año:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Cargo:</td>
                    <td id="tdlstCargo">
                        <select id="lstCargo" name="lstCargo">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo" width="120">Empleado:</td>
                    <td id="tdlstEmpleado">
                        <select id="lstEmpleado" name="lstEmpleado">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="validarFrmBuscar();">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaComision" name="frmListaComision" style="margin:0">
            	<div id="divListaComision" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese los datos de la Comisiones a Buscar</td>
                    </tr>
                    </table>
                </div>
            </form>
            </td>
        </tr>
        </table>
	</div>
	
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<script type="text/javascript">
byId('txtFecha').className = "inputHabilitado";

byId('txtFecha').value = "<?php echo date("m-Y"); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFecha").maskInput("<?php echo "99-9999"; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"<?php echo "%m-%Y"; ?>"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'byId(\'btnBuscar\').click();\"');
xajax_cargaLstCargo();
xajax_cargaLstModulo();
</script>