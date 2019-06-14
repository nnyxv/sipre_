<?php
require_once ("../connections/conex.php");

session_start();
require('../controladores/xajax/xajax_core/xajax.inc.php');
include('../inc_sesion.php');

//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_fi_adicionales.php");


//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Financiamiento - Mantenimiento de Adicionales</title>
	<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>   
	<link rel="stylesheet" type="text/css" href="../js/domDragFinanciamientos.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
     
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
	
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
    <script src="../js/highcharts/js/highcharts.js"></script>
    <script src="../js/highcharts/js/modules/exporting.js"></script>
	
<!-- <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/> -->    
<!-- <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/> -->    
        
<!-- Reacomodando valores del ccs por la cantidad de informacion dentro de los modales -->
        
<style type="text/css">
	.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
	}

	
</style>
<script>

	function abrirDivFlotante1(nomObjeto, tituloDiv, modo = '', idAdicional = '') {
		
			document.forms['frmCrearAdicional'].reset();
			
			byId('txtNombreAdicional').className = 'inputHabilitado';
			byId('selTipoAdicional').className = 'inputHabilitado';
			byId('selTipoAdicional').value = -1;
			byId('txtMontoAdicional').className = 'inputHabilitado';
			byId('selEstatusAdicional').className = 'inputHabilitado';
			byId('selEstatusAdicional').value = -1;
			
		if(modo == 'Crear'){
			openImg(nomObjeto);
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv;
			byId('tdFlotanteTitulo1').width = '450px';

			
		}else if(modo == 'Editar'){
			openImg(nomObjeto);
			byId('hddIdAdicional').value = idAdicional;
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv;
			xajax_cargarAdicional(idAdicional);
		}
	}

	function validarAdicional(){
		if (validarCampo('txtNombreAdicional','t','') == true
			&& validarCampo('selTipoAdicional','t','lista') == true 
			&& validarCampo('txtMontoAdicional','t','real_inglesa') == true 
			&& validarCampo('selEstatusAdicional','t','listaExceptCero') == true ){
				xajax_guardarAdicional(xajax.getFormValues('frmCrearAdicional'));
		} else {
			validarCampo('txtNombreAdicional','t','');
			validarCampo('selTipoAdicional','t','lista');
			validarCampo('txtMontoAdicional','t','real_inglesa');
			validarCampo('selEstatusAdicional','t','listaExceptCero');
			alert("El campo señalado en rojo es requerido");
			return false;
		}
	}
</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_financiamiento.php'); ?>
    </div>

    <div id="divInfo" class="print">
		<table width="80%" align="center">
            <tr>
                <td class="tituloPaginaFinanciamientos" colspan="2">Mantenimiento de Adicionales</td>
            </tr>
            <tr>
                <td align="left">
                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'Ingresar Adicional','Crear');">
	                    <button type="button" title="Listar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/select_date.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
	                </a>
                </td>
            </tr>
            <tr>
            	<td id="tdListaAdicional"></td>
            </tr>
		</table>
    </div>
    <div class="noprint">
	<?php include ('pie_pagina.php'); ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmCrearAdicional" name="frmCrearAdicional" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAdicional" width="100%">
    <tr>
    	<td>
            <table border="0">
                <tr>
                	<td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                    <td width="30%">
                    	<input type="text" id="txtNombreAdicional" name="txtNombreAdicional" />
                        <input type="text" style="display: none" id="hddIdAdicional" name="hddIdAdicional" />
                    </td>
                	<td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Tipo de Adicional:</td>
                    <td id="tdselTipoAdicional" width="30%">
                        <select id="selTipoAdicional" name="selTipoAdicional" style="width: 100%">
                        	<option value="-1">[ Selected ]</option>
                        	<option value="Cuota"> Cuota</option>
                            <option value="Total"> Total</option>
                        </select>
                    </td>
                    <td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Monto Adicional:</td>
                    <td width="30%"><input type="text" id="txtMontoAdicional" style="text-align: right;" name="txtMontoAdicional"  onblur="setFormatoRafk(this, 2);" /></td>
                    <td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Estatus del Adicional:</td>
                    <td id="tdselEstatusAdicional" width="30%">
                    	<select id="selEstatusAdicional" name="selEstatusAdicional" style="width: 100%">
                        	<option value="-1">[ Selected ]</option>
                        	<option value="1"> Activa</option>
                            <option value="0"> Inactiva</option>
                        </select>
                    </td>
                    <td width="20%"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr/>
        	<input type="button" id="btnGuardar" name="btnGuardar" value="Guardar" onclick="validarAdicional();"/>
            <input type="button" id="btnCancelarLista" name="btnCancelarLista" class="close"  value="Cancelar"/>
		</td>
    </tr>
    <tr>
    </table>
</form>
	
</div>

<script language="javascript">

//cargando scripts iniciales

xajax_listarAdicional(0,'','','');

//Abre el div Flotante
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
						//color: '#000000', 
						zIndex: 10090, 
						closeOnClick: false, 
						closeOnEsc: false, 
						loadSpeed: 0, 
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						//color: '#000000', 
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
						//color: '#000000', 
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

//Movimiento del flotante
	var theHandle = document.getElementById("divFlotanteTitulo1");
	var theRoot   = document.getElementById("divFlotante1");
	Drag.init(theHandle, theRoot);
</script>