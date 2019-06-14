<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("sa_informe_historial_unidad"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("controladores/ac_iv_general.php");
require("controladores/ac_sa_informe_tempario_facturado.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Informe Historial Unidad</title>
<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />

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
		
		if (valor == "Mecanico") {
			document.forms['frmBuscarMecanico'].reset();
			
			byId('txtCriterioBuscarMecanico').className = 'inputHabilitado';				
			byId('trBuscarMecanico').style.display = '';				
			byId('btnBuscarMecanico').click();
			
			//byId(verTabla).width = "760";
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		
		byId('txtCriterioBuscarMecanico').focus();
		byId('txtCriterioBuscarMecanico').select();
	}
		
	function exportarExcel(){
		//console.log(JSON.stringify(xajax.getFormValues('frmBuscar')));
		var valForm = JSON.stringify(xajax.getFormValues('frmBuscar'));
		window.open('reportes/sa_informe_historial_unidad_excel.php?valForm='+valForm,'_self');
	}

</script>
    
    <?php  $xajax->printJavascript("../controladores/xajax/"); ?>
    
    <style type="text/css">
		.tabla, .tabla td{
			border-color: #999999;
			border-radius: 0px;
		}
		.tabla2{
			border-left: 1px solid;
			border-right: 1px solid;
			border-bottom: 1px solid;
			border-color: #999999;	
		}
		.tablaResumen{
			border: 1px solid;
			border-color: #999999;	
		}
	</style>
</head>
    
<body class="bodyVehiculos">

<div id="divGeneralPorcentaje" style="text-align:left; ">
    <div> <?php include("banner_servicios.php"); ?> </div>
    <div id="divInfo" class="print">
        <table border="0" width="100%" class="noprint">
            <tr>
                <td class="tituloPaginaServicios" id="titulopag">Informe Temparios Facturados</td>
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

                    <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                        <table align="right">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Empresa:</td>
                                <td id="tdlstEmpresa">
                                    
                                </td>
                                <td align="right" class="tituloCampo" width="120">Fecha:</td>
                                <td style="white-space:nowrap">
                                   Desde:<input type="text" id="txtFechaDesde" name="txtFechaDesde" size="10" class="inputHabilitado" style="text-align:center" size="15" value="<?php echo date(spanDateFormat,strtotime("01-".date("m-Y"))); ?>" />
                                   Hasta:<input type="text" id="txtFechaHasta" name="txtFechaHasta" size="10" class="inputHabilitado" style="text-align:center" size="15" value="<?php echo date(spanDateFormat); ?>" />
                                </td>
                                <td></td>
                            </tr>
							<tr>
                                <td align="right" class="tituloCampo" width="120">Mecanico:</td>
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input type="text" id="txtIdMecanico" class="inputHabilitado" name="txtIdMecanico" onblur="xajax_asignarMecanico(this.value);" size="6" style="text-align:right"/></td>
                                        <td>
                                        <a class="modalImg" id="aListarMecanico" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Mecanico');">
                                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                        </a>
                                        </td>
                                        <td><input type="text" size="30" id="txtNombreMecanico" name="txtNombreMecanico" readonly="readonly" /></td>
                                    </tr>
                                    </table>
                                </td>
                                <td align="right" class="tituloCampo">Criterio:</td>
                                <td><input type="text" name="txtCriterio" id="txtCriterio" class="inputHabilitado"></td>
                                <td></td>
                                <td align="right">
                                    <button type="button" id="btnBuscar" class="noprint" onclick="xajax_buscarTempario(xajax.getFormValues('frmBuscar'));">Buscar</button>
                                    <button type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%">Mecanicos</td></tr></table></div>
    
    <table border="0" id="tblLista" style="display:none" width="860">
    <tr id="trBuscarMecanico">
    	<td>
        <form id="frmBuscarMecanico" name="frmBuscarMecanico" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMecanico" name="txtCriterioBuscarMecanico" onkeyup="byId('btnBuscarMecanico').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarMecanico" name="btnBuscarMecanico" onclick="xajax_buscarMecanico(xajax.getFormValues('frmBuscarMecanico'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMecanico'].reset(); byId('btnBuscarMecanico').click();">Limpiar</button>
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
        <td>
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table>
                    <tr>
                        <td><img src="../img/iconos/ico_verde.gif"/></td><td>Activo</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Inactivo</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
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

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"greenish"
});
	
new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"greenish"
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="byId(\'btnBuscar\').click();"','','','Todos'); //buscador

//byId('txtFechaDesde').value = '<?php echo date(spanDateFormat,strtotime("01-".date("m-Y"))); ?>';
//byId('txtFechaHasta').value = '<?php echo date(spanDateFormat); ?>';

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