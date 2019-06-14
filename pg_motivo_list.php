<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_motivo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_motivo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Motivos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
    <script language="javascript">
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblMotivo').style.display = 'none';
		
		if (verTabla == "tblMotivo") {
			document.forms['frmMotivo'].reset();
			byId('hddIdMotivo').value = '';
			
			byId('txtDescripcion').className = 'inputHabilitado';
			
			xajax_formMotivo(valor, xajax.getFormValues('frmMotivo'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Motivo';
			} else {
				tituloDiv1 = 'Agregar Motivo';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblMotivo") {
			byId('txtDescripcion').focus();
			byId('txtDescripcion').select();
		}
	}
	
	function validarFrmMotivo() {
		if (validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstModuloAdministracion','t','lista') == true
		&& validarCampo('lstTipoTransaccion','t','lista') == true) {
			byId('btnGuardarMotivo').disabled = true;
			byId('btnCancelarMotivo').disabled = true;
			xajax_guardarMotivo(xajax.getFormValues("frmMotivo"), xajax.getFormValues("frmListaMotivo"));
		} else{
			validarCampo('txtDescripcion','t','');
			validarCampo('lstModuloAdministracion','t','lista');
			validarCampo('lstTipoTransaccion','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
		}
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaErp">Motivos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr style="vertical-align:top">
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblMotivo');">
                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                        <button type="button" onclick="xajax_exportarMotivo(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Módulo:</td>
                	<td id="tdlstModuloAdministracionBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Tipo Transacción:</td>
                	<td id="tdlstTipoTransaccionBuscar"></td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaMotivo" name="frmListaMotivo" style="margin:0">
            	<div id="divListaMotivo" style="width:100%"></div>
            </form>
            </td>
        </tr>
            <tr>
                <td>
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tr>
                        <td width="25"><img src="img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table>
                            <tr>
                                <td><img src="img/iconos/pencil.png"/></td><td>Editar</td>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmMotivo" name="frmMotivo" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblMotivo" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td width="70%"><input type="text" id="txtDescripcion" name="txtDescripcion" maxlength="50" size="35"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                <td id="tdlstModuloAdministracion"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Transacción:</td>
                <td id="tdlstTipoTransaccion"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
	        <input type="hidden" id="hddIdMotivo" name="hddIdMotivo" />
            <button type="submit" id="btnGuardarMotivo" name="btnGuardarMotivo" onclick="validarFrmMotivo();">Guardar</button>
            <button type="button" id="btnCancelarMotivo" name="btnCancelarMotivo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtCriterio').className = 'inputHabilitado';

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

xajax_cargaLstModuloBuscar("lstModuloAdministracionBuscar", "byId('btnBuscar').click();");
xajax_cargaLstTipoTransaccion("lstTipoTransaccionBuscar", "byId('btnBuscar').click();");
xajax_listaMotivo(0, 'id_motivo', 'DESC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>