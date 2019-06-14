<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("if_resumen_postventa_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_if_generar_cierre_mensual.php");
include("../controladores/ac_iv_general.php");
include("../informe/controladores/ac_if_resumen_postventa_list.php");

//$xajax->setFlag('debug',true); 
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Informe Gerencial - Resumen Post-Venta</title>
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
    
	<script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2, valor3, valor4, valor5, valor6, valor7, valor8) {
		byId('tblGrafico').style.display = 'none';
		
		if (verTabla == "tblGrafico") {
			document.forms['frmGrafico'].reset();
			
			xajax_formGrafico(valor, valor2, valor3, valor4, valor5, valor6, valor7, valor8);
			
			tituloDiv1 = 'Gráfico';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" style="text-align:center">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Resumen de Post-Venta</td>
        </tr>
        <tr>
            <td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_imprimirResumen(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    	 <!--<button type="button" onclick="xajax_exportarResumen(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>-->
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table border="0" align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                    <td align="right" class="tituloCampo" width="120">Mes - Año:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" size="10" style="text-align:center"/></td>
                    <td>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    	<input type="hidden" id="hddIdCierreMensual" name="hddIdCierreMensual"/>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
                <table id="tblMsj" cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                </tr>
                </table>
                
            	<table id="tblInforme" border="0" style="display:none" width="100%">
                <tr>
                	<td style="padding-right:4px">
                        <form id="divListaTotalFacturacion" class="form-3"></form>
					</td>
                    <td valign="top">
                        <div class="divMsjInfo" id="tdMsjCierre" style="display:none"></div>
                    </td>
                </tr>
                <tr>
                	<td valign="top" width="60%">
                    	<div id="divListaProduccionTaller" style="width:100%"></div>
                        <br>
                    	<div id="divListaProduccionOtros" style="width:100%"></div>
                        <br>
                        <div id="divListaProduccionRepuestos" style="width:100%"></div>
                        <br>
                        <div id="divListaMargenRepuestosServiciosMostrador" style="width:100%"></div>
                        <br>
                        <div id="divListaAnalisisInv" style="width:100%"></div>
                        <br>
                        <div id="divListaCantidadVendida" style="width:100%"></div>
                        <br>
                        <div id="divListaIndicadoresTaller" style="width:100%"></div>
					</td>
        			<td valign="top" width="40%">
                    	<div id="divListaFacturacionAsesoresServicios" style="width:100%"></div>
                        <br>
                    	<div id="divListaFacturacionVendedorRepuestos" style="width:100%"></div>
                        <br>
                        <div id="divListaFacturacionTecnicosServicios" style="width:100%"></div>
                        <br>
                        <div id="divListaComprasRepuestos" style="width:100%"></div>
                        <br>
                        <div id="divListaVentasRepuestos" style="width:100%"></div>
                        <br>
                        <div id="divListaVentasServicios" style="width:100%"></div>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmGrafico" name="frmGrafico" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblGrafico" style="background-color:#002649" width="960">
    <tr>
    	<td align="center" id="tdGrafico"></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCerrar" name="btnCerrar" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtFecha').className = "inputHabilitado";

byId('txtFecha').value = "<?php echo date("m-Y")?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFecha").maskInput("99-9999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"%m-%Y"
	});
};

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>