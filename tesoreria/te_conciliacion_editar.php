<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_conciliacion"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("controladores/ac_te_conciliacion_editar.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Conciliación</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>    
    
	<script>
     
     
window.onload = function(){
	
  new JsDatePick({
		useMode:2,
		target:"fechaAplicada1",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"red"
	});
        
   new JsDatePick({
		useMode:2,
		target:"fechaAplicada2",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"red"
	});
	
};

function validarFormInsertar(){
	if (validarCampo('txtCiRifBeneficiario','t','') == true
		&& validarCampo('txtNombreEmpresa','t','') == true
		&& validarCampo('txtNumeroCuenta','t','') == true
		&& validarCampo('txtNumeroTransferencia','t','') == true
		&& validarCampo('txtNombreBanco','t','') == true
		&& validarCampo('txtObservacionNotaDebito','t','') == true
		&& validarCampo('txtSaldoCuenta','t','') == true
		&& validarCampo('txtImporteMovimiento','t','monto') == true)
	{
		xajax_guardarNotaDebito(xajax.getFormValues('frmNotaDebito'));
	} else {
		validarCampo('txtCiRifBeneficiario','t','');
		validarCampo('txtNombreEmpresa','t','');
		validarCampo('txtNumeroCuenta','t','');
		validarCampo('txtNumeroTransferencia','t','');
		validarCampo('txtNombreBanco','t','');
		validarCampo('txtObservacionNotaDebito','t','');
		validarCampo('txtSaldoCuenta','t','');
		validarCampo('txtImporteMovimiento','t','monto');
		
		alert("Los campos señalados en rojo son requeridos");

		return false;

	}
}
</script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_tesoreria.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Editar Conciliación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
                    <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                        <table width="100%">
                            <tr>
                                <td class="tituloCampo" align="right">Mes Conciliacion</td>
                                <td  align="left">
        <!--                        <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" value=""/>-->
                                    <input type="text" id="txtFecha" name="txtFecha" readonly="readonly" value="" size="25"/>
                                </td>
                                <td align="right" class="tituloCampo" width="10">Fecha:</td>
                                <td align="left">
                                   Desde:<input type="text" id="fechaAplicada1" name="fechaAplicada1" size="8" readonly="readonly" class="inputHabilitado" value="<?php echo date(spanDateFormat, strtotime("01-".date("m-Y"))); ?>" />  
                                   Hasta:<input type="text" id="fechaAplicada2" name="fechaAplicada2" size="8" readonly="readonly" class="inputHabilitado" value="<?php echo date(spanDateFormat, strtotime(date("t-m-Y"))); ?>" />
                                </td>
                                <td colspan="2">
                                    <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarEstadoCuenta(<?php echo $_GET['id_con'];?>,xajax.getFormValues('frmBuscar'));">Buscar</button>
                                    <button type="button" onclick="document.getElementById('fechaAplicada1').value='';  document.getElementById('fechaAplicada2').value = '';  document.getElementById('btnBuscar').click();">Limpiar</button>
                                </td>
                            </tr>
                            <tr align="left">
                                <td width="15%" align="right" class="tituloCampo">Empresa / Sucursal:</td>
                                <td align="left">
                                        <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" value=""/>
                                        <input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="25"/>
                                </td>
                                <td align="right" class="tituloCampo" width="15%">Banco:</td>
                                <td align="left">
                                    <input type="hidden" id="hddIdBanco" name="hddIdBanco" value=""/>
                                    <input type="text" id="txtBanco" name="txtBanco" readonly="readonly" size="25"/>
                                </td>
                                <td width="15%"  class="tituloCampo" align="right">Nº Cuenta:</td>
                                <td align="left">
                                    <input type="hidden" id="hddSaldoBancoSaldo1" name="hddSaldoBancoSaldo1" value=""/>
                                    <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" value=""/>																	
                                    <input type="text" id="txtCuenta" name="txtCuenta" readonly="readonly" size="25"/>						
                                </td>
                            </tr>
                        </table>
                    </form>
            </td>
        </tr>
        </table>
        
        <table width="100%">
        <tr>
            <td valign="top">
            	<form id="frmListadoEstadoCuenta1" name="frmListadoEstadoCuenta1">
            	<fieldset><legend class="legend">Documentos Aplicados</legend>
                <table width="100%">
                <tr>
		            <td id="tdListadoEstadoCuenta1"></td>
                </tr>
                </table>
                <table width="100%">
                <tr>
		            <td><input type="hidden" id="hddObj" name="hddObj"/></td>
                </tr>
                </table>
             	</fieldset>
                </form>
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
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
        <table border="0" id="tblListados" style="display:none" width="610">
        <tr>
            <td id="tdDescripcionArticulo">
                <table width="100%">
                <tr class="tituloColumna">
                    <td>Orden</td>
                    <td>Nº Orden Propio</td>
                    <td>Nº Referencia</td>
                    <td>Fecha</td>
                    <td>Proveedor</td>
                    <td>Articulos</td>
                    <td>Pedidos</td>
                    <td>Pendientes</td>
                    <td>Total</td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr />
                <input type="button" id="" name="" onclick="document.getElementById('divFlotante').style.display='none';" value="Cancelar" />
            </td>
        </tr>
        </table>
        
        
	<?php
    $arrayRutaArch = explode("/",$_SERVER['PHP_SELF']);
    //$rutaArch = $arrayRutaArch[strlen($arrayRutaArch)-2]; Revisar con roger para ver el otro dia acomodo algo y funciono perfect
    ?>
    <form id="frmPermiso" name="frmPermiso" onsubmit="validarFormPermiso(); return false;" style="margin:0px">
    <table border="0" id="tblPermiso" style="display:none" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" size="30" value="<?php echo $rutaArch;?>"/>
                    <input type="hidden" id="hddIdEstadoCuenta" name="hddIdEstadoCuenta" size="30"/>
				</td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Observacion:</td>
                <td>
                	<textarea  id="txtObservacion" name="txtObservacion" cols="45" rows="5"></textarea>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="validarFormPermiso();" value="Aceptar">
            <input type="button" onclick="document.getElementById('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
    </form>
</div>
<script>
xajax_CargarDatos(<?php echo $_GET['id_con'];?>);

xajax_buscarEstadoCuenta(<?php echo $_GET['id_con'];?>,xajax.getFormValues('frmBuscar'));

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

</script>