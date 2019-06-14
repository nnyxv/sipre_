<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(validaAcceso("ga_orden_compra_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_orden_compra_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Compras -  Hist&oacute;rico Ordenes de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
		
    <script>
	function validarForm() {
		if (validarCampo('txtMarca','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		) {
			xajax_guardarMarca(xajax.getFormValues('frmMarca'));
		} else {
			validarCampo('txtMarca','t','');
			validarCampo('txtDescripcion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function cargar(marca){
		//alert(marca);
		xajax_cargarMarca(marca);
	}
	function eliminar(marca){
		//alert(marca);
		if (utf8confirm('&iquest;Desea eliminar La Solicitud: '+marca+'?')){
			window.location="ga_solicitud_compras_eliminar.php?id="+marca;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_compras.php"); ?></div>

    <div id="divInfo" class="print">
    <table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCompras">Hist&oacute;rico Ordenes de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            	<table align="right" id="tblBuscar">
                <tr>
                	 <td>
                     <form id="frmBuscar" name="frmBuscar" onsubmit="return false;">
                        <table width="100%" border="0">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Empresa:</td>
                                <td id="tdlstEmpresa" colspan="2"></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Fecha:</td>
                                <td colspan="2">&nbsp;Desde:&nbsp;<input id="txtFechaDesde"  name="txtFechaDesde" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off">&nbsp;Hasta:&nbsp;<input id="txtFechaHasta" name="txtFechaHasta" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off"></td>
                            </tr> 
                            <tr align="left">
                            	<td align="right" class="tituloCampo" width="120">Tipo de Pago:</td>
                                <td>
                                	<select id="tipoPago" name="tipoPago" class="inputHabilitado" onchange="byId('btnBuscar').click();">
                                    	<option value="-1">[ Seleccione ]</option>
                                        <option value="0">Crédito</option>
                                        <option value="1">Contado</option>
                                    </select>
                                </td>
                                <td align="right" class="tituloCampo" width="120">Estatus:</td>
                                <td>
                                	<select id="estatusOrden" name="estatusOrden" class="inputHabilitado" onchange="byId('btnBuscar').click();">
                                    	<option value="-1">[ Seleccione ]</option>
                                        <option value="2">Ordenado</option>
                                        <option value="3">Facturado</option>
                                        <option value="5">Anulado</option>
                                    </select>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Nro Sol./Orden:</td>
                                <td><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
                                <td align="right" class="tituloCampo" width="120">Criterio:</td> 
                                <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"></td>
                                <td>
                                <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
                                <button type="button" id="btnLimpiar" name="btnLimpiar" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                                </td>
                            </tr>
                        </table>
                     </form>
                     </td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td><div id="divListHistOrdenCompa"></div></td>
        </tr>
        <tr>
        	<td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table>
                                <tr>
                                    <td><img src="../img/iconos/aprob_mecanico.png"/></td>
                                    <td>Ordenado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/aprob_control_calidad.png"/></td>
                                    <td> Facturado </td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/aprob_jefe_taller.png"/></td>
                                    <td> Anulado </td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_view.png"/></td>
                                    <td> Ver Orden de Compra </td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                    <td> Orden de Compra Pdf </td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width:90%;max-height: 750px; overflow: auto;">
	<div id="divFlotanteTitulo" class="handle">
    	<table width="100%">
        	<tr>
            <td id="tdFlotanteTitulo" name="tdFlotanteTitulo" align="left"></td>
            <td align="right"><img src="../img/iconos/cross.png" width="16" height="16" alt="Cerrar" class="puntero close" /></td>
            </tr>
        </table>
    </div>
    <table width="100%" border="0">
        <tr>
            <td colspan="2">
                <table width="100%" border="0">
                    <tr>
                        <td class="tituloArea" colspan="2">Orden de Compra</td>
                    </tr>
                    <tr>
                        <td id="tdLogo" width="50%" align="left"></td>
                        <td id="tdDatosOrde" width="50%"></td>
                    </tr>
                    <tr>
                        <td class="tituloArea" colspan="2">Datos Del Proveeedor</td>
                    </tr>
                    <tr>
                        <td id="tdDatosProveedor" colspan="2"></td>
                    </tr>
                    <tr>
                        <td class="tituloArea" colspan="2">Datos de la Compra</td>
                    </tr>
                    <tr>
                        <td id="tdDatosCompras" colspan="2"></td>
                    </tr>  
                    <tr>
                        <td id="tdDetallesCompras" colspan="2"></td>
                    </tr>
             
                </table>
            </td>
        </tr>
        <tr>
        	<td align="right">
            		<hr/>
                <button  id="btnCancelar2" title="Cancelar Solicitud" style="cursor:default" class="close" name="btnCancelar2" type="button">
                    <table border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td><img src="../img/iconos/ico_error.gif"></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                      </tr>
                    </table>
                </button>            
            </td>
        </tr>
    </table>

</div> <!--FIN EMPRESA DEPARTAMENTO CENTRO COSTO-->


<script language="javascript">
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listHistCompOrden(0,'fecha','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||<?php echo date("01-m-Y")?>|<?php echo date(spanDateFormat)?>');

byId('txtFechaDesde').value = "<?php echo date(spanDateFormat,strtotime(date("01-m-Y"))); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

function openImg(idObj){
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

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

</script>