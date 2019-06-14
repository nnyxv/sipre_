<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("sa_informe_historial_unidad"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("controladores/ac_iv_general.php");
require("controladores/ac_sa_informe_historial_unidad.php");

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>.: SIPRE 3.0 :. Servicios - Informe Historial Unidad</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>

<link rel="stylesheet" type="text/css" href="../js/domDragServicios.css"/>
<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>

<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>    
<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    
<script type="text/javascript">
        
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblLista').style.display = '';
		
		if (valor == "Cliente") {
			document.forms['frmBuscarCliente'].reset();
			
			byId('txtCriterioBuscarCliente').className = 'inputHabilitado';				
			byId('trBuscarCliente').style.display = '';				
			byId('btnBuscarCliente').click();
			
			byId(verTabla).width = "760";
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		
		byId('txtCriterioBuscarCliente').focus();
		byId('txtCriterioBuscarCliente').select();
	}
		
	function exportarExcel(){
		//console.log(JSON.stringify(xajax.getFormValues('frmBuscarS')));
		var valForm = JSON.stringify(xajax.getFormValues('frmBuscarS'));
		window.open('reportes/sa_informe_historial_unidad_excel.php?valForm='+valForm,'_self');
	}

</script>
    
    <?php  $xajax->printJavascript("../controladores/xajax/"); ?>
</head>
    
<body class="bodyVehiculos">
    
<div id="divGeneralPorcentaje" style="text-align:left; ">
    <div> <?php include("banner_servicios.php"); ?> </div>
    <div id="divInfo" class="print">
        <table border="0" width="100%" class="noprint">
            <tr>
                <td class="tituloPaginaServicios" id="titulopag">Informe Historial Unidad</td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td class="noprint">
                    <table align="left">
                        <tr>
                            <td>
                                <button type="button" class="noprint" onclick="exportarExcel();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
                            </td>
                        </tr>
                    </table>

                    <form id="frmBuscarS" name="frmBuscarS" onsubmit="return false;" style="margin:0">
                        <table align="right">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Empresa:</td>
                                <td id="tdlstEmpresa">
                                    
                                </td>
                                <td align="right" class="tituloCampo" width="120">Fecha Orden:</td>
                                <td style="white-space:nowrap">
                                   Desde:<input type="text" id="txtFechaDesde" name="txtFechaDesde" size="10" class="inputHabilitado" style="text-align:center" size="15" value="" />
                                   Hasta:<input type="text" id="txtFechaHasta" name="txtFechaHasta" size="10" class="inputHabilitado" style="text-align:center" size="15" value="" />
                                </td>
                                <td></td>
                            </tr>
							<tr>
                                <td align="right" class="tituloCampo" width="120">Cliente:</td>
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input type="text" id="txtIdCliente" class="inputHabilitado" name="txtIdCliente" onblur="xajax_asignarCliente(this.value);" size="6" style="text-align:right"/></td>
                                        <td>
                                        <a class="modalImg" id="aListarCliente" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Cliente');">
                                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                        </a>
                                        </td>
                                        <td><input type="text" size="30" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" /></td>
                                    </tr>
                                    </table>
                                </td>
                                <td align="right" class="tituloCampo">Criterio:</td>
                                <td><input type="text" name="txtCriterio" id="txtCriterio" class="inputHabilitado"></td>
                                <td></td>
                                <td align="right">
                                    <button type="button" id="btnBuscar" class="noprint" onclick="xajax_buscarHistorial(xajax.getFormValues('frmBuscarS'));">Buscar</button>
                                    <button type="button" class="noprint" onclick="document.forms['frmBuscarS'].reset(); byId('btnBuscar').click();">Limpiar</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
        </table>
        <div id="divListaHistorial"> 
			<table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo">
                <tbody><tr>
                    <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                    <td align="center">Ingrese los datos a Buscar</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="noprint">
		<?php include("pie_pagina.php"); ?>
    </div>

</div>
    
</body>
</html>


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Clientes</td></tr></table></div>
    
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr id="trBuscarCliente">
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
	<tr>
        <td>
        	<div id="divLista" style="width:100%;"></div>
		</td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button class="close" name="btnCancelarLista" id="btnCancelarLista" type="button">Cerrar</button>
        </td>
    </tr>
    
    </table>
</div>

<script type="text/javascript">

$("#txtFechaDesde").maskInput("99-99-9999",{placeholder:" "});
$("#txtFechaHasta").maskInput("99-99-9999",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"%d-%m-%Y",
	cellColorScheme:"greenish"
});
	
new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"%d-%m-%Y",
	cellColorScheme:"greenish"
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'btnBuscar\').click();"','','','Todos'); //buscador

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);


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
</script>