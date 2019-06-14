<?php
require_once("../connections/conex.php");
include('../js/libGraficos/Code/PHP/Includes/FusionCharts.php');
include('../js/libGraficos/Code/PHP/Includes/FC_Colors.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("sa_comisiones_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("../informe/controladores/ac_if_comisiones_list.php");
require_once("../controladores/ac_pg_calcular_comision.php");
require_once("../controladores/ac_pg_calcular_comision_servicio.php");

//$xajax->setFlag('debug',true); 
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Comisiones</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    <script src="../js/login-modernizr/modernizr.custom.63321.js"></script>
    <link rel="stylesheet" type="text/css" href="../js/login-modernizr/font-awesome.css" />
</head>

<body>
<div>
    <div><?php include("banner_servicios.php"); ?></div>
    
    <div id="divInfo" style="text-align:center">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">Comisiones</td>
        </tr>
        <tr>
            <td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_imprimirComisiones(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    	<button type="button" onclick="xajax_exportarComisiones(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                    <td align="right" class="tituloCampo">Desde:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFechaDesde" name="txtFechaDesde" class="inputHabilitado" readonly="readonly" size="10" style="text-align:center" value="<?php echo date(str_replace("d","01",spanDateFormat)); ?>"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/ico_date.png" id="imgFecha1" name="imgFecha1" class="puntero noprint"/>
                        <script type="text/javascript">
                        Calendar.setup({
							inputField : "txtFechaDesde",
							ifFormat : "%d-%m-%Y",
							button : "imgFecha1"
                        });
                        </script>
                    </div>
                    </td>
                     <td align="right" class="tituloCampo">Hasta:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFechaHasta" name="txtFechaHasta" class="inputHabilitado" readonly="readonly" size="10" style="text-align:center" value="<?php echo date("d-m-Y"); ?>"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/ico_date.png" id="imgFecha2" name="imgFecha2" class="puntero noprint"/>
                        <script type="text/javascript">
                        Calendar.setup({
                            inputField : "txtFechaHasta",
                            ifFormat : "%d-%m-%Y",
                            button : "imgFecha2"
                        });
                        </script>
                    </div>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Cargo:</td>
                    <td id="tdlstCargo"></td>
                	<td align="right" class="tituloCampo" width="120">Empleado:</td>
                    <td id="tdlstEmpleado"></td>
                	<td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModulo"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarComision(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaComisiones" name="frmListaComisiones" class="form-3" style="margin:0">
            	<div id="divListaComisiones" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese los datos de las Comisiones a Buscar</td>
                    </tr>
                    </table>
                </div>
            </form>
            </td>
        </tr>
        </table>
	</div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
    <div id="tblListaComisionDetalle" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
                <form id="frmListaComisionDetalle" class="form-3" style="margin:0"></form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
    
<form id="frmComisionProduccion" name="frmComisionProduccion" onsubmit="return false;" style="margin:0px">
    <div id="tblComisionProduccion" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td><div id="divListaComisionProduccion" style="background-color:#333; width:100%"></div></td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="submit" id="btnGuardarComisionProduccion" name="btnGuardarComisionProduccion"  onclick="validarFrmComisionProduccion();">Guardar</button>
                <button type="button" id="btnCancelarComisionProduccion" name="btnCancelarComisionProduccion" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" width="360">
    <tr>
    	<td align="right" class="tituloCampo">Porcentaje:</td>
        <td><input type="text" id="txtPorcentaje" name="txtPorcentaje"/></td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
            <button type="button" id="btnCancelar2" name="btnCancelar2" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script type="text/javascript">
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstCargo(this.value); byId(\'btnBuscar\').click();\"');
xajax_cargaLstCargo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstModulo(1);

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


var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>