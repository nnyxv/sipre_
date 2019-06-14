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
include("controladores/ac_fi_plazos.php");


//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Financiamiento - Mantenimiento de Plazos</title>
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

	function abrirDivFlotante1(nomObjeto, tituloDiv, modo = '', idPlazo = '') {
		
			document.forms['frmCrearPlazo'].reset();
			
			byId('txtNombrePlazo').className = 'inputHabilitado';
			byId('txtPlazo').className = 'inputHabilitado';
			byId('txtSemanas').className = 'inputHabilitado';
			byId('selEstatusDuracion').className = 'inputHabilitado';
			byId('selEstatusFrecuencia').className = 'inputHabilitado';
			byId('selEstatusInteres').className = 'inputHabilitado';
			
		if(modo == 'Crear'){
			openImg(nomObjeto);
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv;
			byId('tdFlotanteTitulo1').width = '450px';

			
		}else if(modo == 'Editar'){
			openImg(nomObjeto);
			byId('hddIdPlazo').value = idPlazo;
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv;
			xajax_cargarPlazo(idPlazo);
		}
	}

	function validarPlazo(){
		if (validarCampo('txtNombrePlazo','t','') == true
			&& validarCampo('txtPlazo','t','entero') == true 
			&& validarCampo('txtSemanas','t','entero') == true 
			&& validarCampo('selEstatusFrecuencia','t','listaExceptCero') == true 
			&& validarCampo('selEstatusInteres','t','listaExceptCero') == true 
			&& validarCampo('selEstatusDuracion','t','listaExceptCero') == true ){
				xajax_guardarPlazo(xajax.getFormValues('frmCrearPlazo'));
		} else {
			validarCampo('txtNombrePlazo','t','');
			validarCampo('txtPlazo','t','entero');
			validarCampo('txtSemanas','t','entero');
			validarCampo('selEstatusDuracion','t','listaExceptCero');
			validarCampo('selEstatusInteres','t','listaExceptCero');
			validarCampo('selEstatusFrecuencia','t','listaExceptCero');
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
                <td class="tituloPaginaFinanciamientos" colspan="2">Mantenimiento de Plazos</td>
            </tr>
            <tr>
                <td align="left">
                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'Ingresar Plazo','Crear');">
	                    <button type="button" title="Listar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/select_date.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
	                </a>
                </td>
            </tr>
            <tr>
            	<td id="tdListaPlazo"></td>
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
    
<form id="frmCrearPlazo" name="frmCrearPlazo" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblPlazos" width="100%">
    <tr>
    	<td>
            <table border="0">
                <tr>
                	<td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Descripción:</td>
                    <td width="30%">
                    	<input type="text" id="txtNombrePlazo" name="txtNombrePlazo" />
                        <input type="text" style="display: none" id="hddIdPlazo" name="hddIdPlazo" />
                    </td>
                	<td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Cuotas Anuales:</td>
                    <td width="30%"><input type="text" id="txtPlazo" name="txtPlazo" /></td>
                    <td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Semanas:</td>
                    <td width="30%"><input type="text" id="txtSemanas" name="txtSemanas"  /></td>
                    <td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Estado Duracion:</td>
                    <td id="tdselEstatusDuracion" width="30%">
                    	<select id="selEstatusDuracion" name="selEstatusDuracion">
                        	<option value="-1">[ Selected ]</option>
                        	<option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </td>
                    <td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Estado Frecuencia:</td>
                    <td id="tdselEstatusFrecuencia" width="30%">
                    	<select id="selEstatusFrecuencia" name="selEstatusFrecuencia">
                        	<option value="-1">[ Selected ]</option>
                        	<option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </td>
                    <td width="20%"></td>
                </tr>
                <tr>
                    <td width="20%"></td>
                    <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Estado Plazo Interes:</td>
                    <td id="tdselEstatusInteres" width="30%">
                    	<select id="selEstatusInteres" name="selEstatusInteres">
                        	<option value="-1">[ Selected ]</option>
                        	<option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </td>
                    <td width="20%"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr/>
        	<input type="button" id="btnGuardar" name="btnGuardar" value="Guardar" onclick="validarPlazo();"/>
            <input type="button" id="btnCancelarLista" name="btnCancelarLista" class="close"  value="Cancelar"/>
		</td>
    </tr>
    <tr>
    </table>
</form>
	
</div>

<script language="javascript">

//cargando scripts iniciales

xajax_listarPlazos(0,'','','');

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