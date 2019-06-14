<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("sa_resultados_servicio"))) {//no tenia agregado, antes llamado sa_resumen_servicio
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_resultados_servicio.php");

include("controladores/ac_iv_general.php");//empresa final

//$xajax->setFlag('debug',true); 
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Resultados Servicio</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
        
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div>
	<?php include("banner_servicios.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">Resultados Servicio</td>
        </tr>
        <tr>
            <td class="noprint">
            	<table align="left">
                <tr>
                	<td>
                    	<button type="button" class="noprint" onclick="xajax_imprimir(xajax.getFormValues('frmBuscarS'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
					</td>
                    
				</tr>
				</table>
                
            <form id="frmBuscarS" name="frmBuscarS" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa">
                    <!--<select id="lstEmpresa" name="lstEmpresa">
                        <option value="-1">[ Todos ]</option>
                    </select>-->
                    </td>
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
                        <script type="text/javascript">
                        Calendar.setup({
                        inputField : "txtFechaDesde",
                        ifFormat : "%d-%m-%Y",
                        button : "imgFechaDesde"
                        });
                        </script>
                    </div>
                    </td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
                        <script type="text/javascript">
                        Calendar.setup({
                        inputField : "txtFechaHasta",
                        ifFormat : "%d-%m-%Y",
                        button : "imgFechaHasta"
                        });
                        </script>
                    </div>
                    </td>
                    <td>
                    	<input type="button" id="btnBuscar" class="noprint" onclick="xajax_buscar(xajax.getFormValues('frmBuscarS'));" value="Buscar" />
						<input type="button" class="noprint" onclick="document.forms['frmBuscarS'].reset(); $('btnBuscar').click();" value="Limpiar" />
					</td>
                
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table border="0" width="100%">
                <tr>
                	<td colspan="2" style="padding-right:4px">
                        <div id="divListaResumenServ" style="width:100%">
                            <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                            <tr>
                                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                            </tr>
                            </table>
                        </div>
					</td>
                </tr>
                <tr>
                	<td valign="top" width="80%" align="center">
                        <div id="divListaResumen" style="width:80%"></div>
					</td>
                </tr>
                </table>
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


<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
    <table border="0" width="1000">
    <tr>
    	<td id="tdListadoResumenDetalle"></td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" id="btnCancelar" name="btnCancelar" class="close" value="Cerrar">
        </td>
    </tr>
    </table>
</div>



<script type="text/javascript">
function openImg2(idObj,i) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			effect: 'apple',
			oneInstance:false,
			closeOnClick: false,
			closeOnEsc: false,
			zIndex: 10100,
			onLoad: function(){
				if($.mask.isLoaded()) {
					//this is a second overlay, get old settings
					oldMaskZ = $.mask.getConf().zIndex;
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
				} else {
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				}
				//Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) {	//re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					//Assumes the other overlay has apple_overlay class
					$(".apple_overlay").css("zIndex", oldMaskZ + 2);
				}
			}
		});
	});
}




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

//xajax_cargaLstEmpresa('<?php //echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

</script>
<script language="javascript">
var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',''); //buscador
</script>