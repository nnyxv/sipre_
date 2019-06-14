<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if(!(validaAcceso("fi_pagos_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_fi_pagos_list.php");
//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Financiamientos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragFinanciamientos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
 
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_financiamiento.php"); ?></div>
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaFinanciamientos">Pagos de Financiamientos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                    	<select id="lstEstatusPedido" name="lstEstatusPedido" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                            <option value="3">Aprobado</option>
                            <option value="1">Parcialmente Pagado</option>
                            <option value="4">Atrasado</option>
                        </select>
                    </td>
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPedido(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPedido" name="frmListaPedido" style="margin:0">
            	<div id="divListaPedido" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                    	<tr>
                            <td><img src="../img/iconos/money_add.png"/></td><td>Pagar Pedidos</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/find.png"/></td><td>Ver Recibos de Pagos</td>
                            <td>&nbsp;</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
       </table>
   </div>
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>

	
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('lstEstatusPedido').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"ocean_blue"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"ocean_blue"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPedido(0, 'id_pedido_financiamiento', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||'+byId('lstEstatusPedido').value);
</script>