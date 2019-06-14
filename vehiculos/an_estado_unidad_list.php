<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_estado_unidad_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_estado_unidad_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Estado de Unidades</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Estado de Unidades</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td><input class="noprint" type="button" value="Imprimir" onclick="window.print();"/></td>
                </tr>
                </table>
                
        	<form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarUnidadBasica(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Almacén:</td>
                    <td id="tdlstAlmacen"></td>
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstadoVenta" name="lstEstadoVenta" onchange="byId('btnBuscar').click();">
                            <option value="1">Asignación Planta</option>
                            <option value="2">Confirmación Planta</option>
                            <option value="TRANSITO">TRANSITO</option>
                            <option value="POR REGISTRAR">POR REGISTRAR</option>
                            <option value="SINIESTRADO">SINIESTRADO</option>
                            <option value="DISPONIBLE">DISPONIBLE</option>
                            <option value="RESERVADO">RESERVADO</option>
                        </select>
                    </td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarUnidadBasica(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
		</tr>
		<tr>
        	<td><div id="divListaEstadoUnidad" style="width:100%"></div></td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('lstEstadoVenta').className = "inputHabilitado";

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstAlmacen(this.value); byId(\'btnBuscar\').click();\"');
xajax_cargaLstAlmacen('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaEstadoUnidad(0, 'nom_uni_bas', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||' + byId('lstEstadoVenta').value);
</script>