<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_kardex_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_kardex_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Listado de Kardex</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
   
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script type="text/javascript" language="javascript">
	function validarFechas(){
		var fechaDesde = byId('txtFechaDesde').value;
		var fechaHasta = byId('txtFechaHasta').value;
					
		diaDesde = fechaDesde.substring(0,2);
		dDesde = parseInt(diaDesde,10);
		mesDesde = fechaDesde.substring(3,5);
		mDesde = parseInt(mesDesde,10);
		anoDesde = fechaDesde.substring(6,10);
		aDesde = parseInt(anoDesde,10);
		
		diaHasta = fechaHasta.substring(0,2);
		dHasta = parseInt(diaHasta,10);
		mesHasta = fechaHasta.substring(3,5);
		mHasta = parseInt(mesHasta,10);
		anoHasta = fechaHasta.substring(6,10);
		aHasta = parseInt(anoHasta,10);
		
		var fecha1 = new Date(aDesde,mDesde-1,dDesde);
		var fecha2 = new Date(aHasta,mHasta-1,dHasta);
		if (fecha1 <= fecha2) {
			byId('txtFechaDesde').className = "inputHabilitado";
			byId('txtFechaHasta').className = "inputHabilitado";
			xajax_buscarArticulo(xajax.getFormValues('frmBuscar'));
		} else {
			byId('txtFechaDesde').className = "inputErrado";
			byId('txtFechaHasta').className = "inputErrado";
			alert("La fecha Desde no puede ser mayor que la fecha Hasta")
		}
	}
	
	function validarExportar(tipoExportar) {
		error = false;
		if (!(validarCampo('lstVerExcel','t','lista') == true)) {
			validarCampo('lstVerExcel','t','lista');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_exportarKardex(xajax.getFormValues('frmBuscar'), tipoExportar);
		}
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
            <td class="tituloPaginaRepuestos">Listado de Kardex</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_encabezadoEmpresa(byId('lstEmpresa').value); window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
						<button type="button" onclick="validarExportar('EXCEL')"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                        <button type="button" onclick="validarExportar('TXT');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_get.png"/></td><td>&nbsp;</td><td>TXT</td></tr></table></button>
					</td>
                </tr>
                </table>
            	
            <form id="frmBuscar" name="frmBuscar" onsubmit="byId('btnBuscar').click(); return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left" class="noprint">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Ver en Excel:</td>
                    <td>
                    	<select id="lstVerExcel" name="lstVerExcel">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="1">Hoja por Cada Código</option>
                        	<option value="2">Hoja Unica</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Tipo Mov.:</td>
                    <td>
                        <select multiple id="lstTipoMovimiento" name="lstTipoMovimiento" onchange="byId('btnBuscar').click();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="1">1.- Compra</option>
                            <option selected="selected" value="2">2.- Entrada</option>
                            <option selected="selected" value="3">3.- Venta</option>
                            <option selected="selected" value="4">4.- Salida</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Codigo:</td>
                	<td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Descripci&oacute;n:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="validarFechas();">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <div id="divListaKardex" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese los datos del Kardex a Buscar</td>
                    </tr>
                    </table>
				</div>
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
                            <td align="right"><img src="../img/iconos/ico_cambio.png"/></td><td align="center">Movimiento Inter-Almacen</td>
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
byId('lstVerExcel').className = "inputHabilitado";
byId('lstTipoMovimiento').className = 'inputHabilitado';
byId('txtCriterio').className = "inputHabilitado";

byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
};

var lstTipoMovimiento = $.map($("#lstTipoMovimiento option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>