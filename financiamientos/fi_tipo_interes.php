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
include("controladores/ac_fi_tipo_interes.php");


//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Financiamiento - Mantenimiento de Tipo de TipoInteres</title>
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

	function abrirDivFlotante1(nomObjeto, tituloDiv, modo = '', idTipoInteres = '') {

			document.forms['frmCrearTipoInteres'].reset();
			
			byId('txtNombreTipoInteres').className = 'inputHabilitado';
			byId('txtTipoInteres').className = 'inputHabilitado';
			byId('selEstatus').className = 'inputHabilitado';
			
		if(modo == 'Crear'){
			openImg(nomObjeto);
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv;
			byId('tdFlotanteTitulo1').width = '400px';
			
		}else if(modo == 'Editar'){
			openImg(nomObjeto);
			byId('hddIdTipoInteres').value = idTipoInteres;
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv;
			byId('tdFlotanteTitulo1').width = '400px';
			xajax_cargarTipoInteres(idTipoInteres);
		}
	}

	function validarTipoInteres(){
		if (validarCampo('txtNombreTipoInteres','t','') == true
			&& validarCampo('txtTipoInteres','t','monto') == true 
			&& validarCampo('selEstatus','t','listaExceptCero') == true ){
				xajax_guardarTipoInteres(xajax.getFormValues('frmCrearTipoInteres'));
		} else {
			validarCampo('txtNombreTipoInteres','t','');
			validarCampo('txtTipoInteres','t','monto');
			validarCampo('selEstatus','t','listaExceptCero');
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
                <td class="tituloPaginaFinanciamientos" colspan="2">Mantenimiento de Tipo de Intereses</td>
            </tr>
            <tr>
                <td align="left">
                    <a class="modalImg" id="aListarTipoInteres" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'Ingresar TipoInteres','Crear');">
	                    <button type="button" title="Listar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/select_date.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
	                </a>
                </td>
            </tr>
            <tr>
            	<td id="tdListaTipoInteres"></td>
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
    
<form id="frmCrearTipoInteres" name="frmCrearTipoInteres" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblTipoInteres" width="100%">
    <tr>
    	<td>
            <table border="0">
                <tr>
                    <td width="25%"></td>
                    <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Descripción:</td>
                    <td>
                    	<input type="text" id="txtNombreTipoInteres" name="txtNombreTipoInteres" size="25%" />
                        <input type="hidden" id="hddIdTipoInteres" name="hddIdTipoInteres" />
                    </td>
                    <td width="25%"></td>
                </tr>
                <tr>
                    <td width="25%"></td>
                    <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Tipo de Interes:</td>
                    <td width="25%" align="left"><input type="text" id="txtTipoInteres" style="text-align : center" name="txtTipoInteres" size="3" onkeypress="return validarNumeros (event);"/></td>
                    <td width="25%"></td>
                </tr>
                <tr>
                    <td width="25%"></td>
                    <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Estado Tipo de Interes:</td>
                    <td id="tdselEstatus"  align="left" width="25%">
                    	<select id="selEstatus" width="100%" name="selEstatus">
                        	<option value="-1">[ Selected ]</option>
                        	<option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </td>
                    <td width="25%"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr/>
        	<input type="button" id="btnGuardar" name="btnGuardar" value="Guardar" onclick="validarTipoInteres();"/>
            <input type="button" id="btnCancelarLista" name="btnCancelarLista" class="close"  value="Cancelar"/>
		</td>
    </tr>
    <tr>
    </table>
</form>
	
</div>

<script language="javascript">

//cargando scripts iniciales

xajax_listarTipoInteres(0,'','','');

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


//funciones internas 


function validarNumeros (evento){
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id;
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 8) // basckspace
		&& (teclaCodigo != 9) // tab
		&& (teclaCodigo != 13) // enter
		&& (teclaCodigo != 46) // delete
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)) { // 0 al 9
			return false;
		}
}
</script>