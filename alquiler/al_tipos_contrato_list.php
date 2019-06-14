<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("al_tipos_contrato_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_al_tipos_contrato_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler - Tipos de Contrato</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
	<link rel="stylesheet" type="text/css" href="../js/domDragAlquiler.css">
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblTipoContrato').style.display = 'none';
		
		if (verTabla == "tblTipoContrato") {
			document.forms['frmTipoContrato'].reset();
			byId('hddIdTipoContrato').value = '';

			byId('txtNombre').className = 'inputHabilitado';
			byId('lstModoFactura').className = 'inputHabilitado';
			
			xajax_frmTipoContrato(valor);
			
			if (valor > 0) {				
				tituloDiv1 = 'Editar Tipo de Contrato';
			} else {
				tituloDiv1 = 'Nuevo Tipo de Contrato';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
	}
	
	function validarfrmTipoContrato() {
		error = false;
				
		if (!(validarCampo('lstEmpresa','t','') == true
		&& validarCampo('txtNombre','t','') == true 
		&& validarCampo('lstFiltroContrato','t','listaExceptCero') == true
		&& validarCampo('lstModoFactura','t','listaExceptCero') == true
		&& validarCampo('lstClaveMovimiento','t','listaExceptCero') == true
		&& validarCampo('lstClaveMovimientoDev','t','listaExceptCero') == true
		&& validarCampo('lstClaveMovimientoSalida','t','listaExceptCero') == true
		&& validarCampo('lstClaveMovimientoEntrada','t','listaExceptCero') == true
		)) {
			validarCampo('lstEmpresa','t','listaExceptCero');
			validarCampo('txtNombre','t','');
			validarCampo('lstFiltroContrato','t','listaExceptCero');
			validarCampo('lstModoFactura','t','listaExceptCero');
			validarCampo('lstClaveMovimiento','t','listaExceptCero');
			validarCampo('lstClaveMovimientoDev','t','listaExceptCero');
			validarCampo('lstClaveMovimientoSalida','t','listaExceptCero');
			validarCampo('lstClaveMovimientoEntrada','t','listaExceptCero');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarTipoContrato(xajax.getFormValues('frmTipoContrato'));
		}
	}
	
	function validarEliminar(idPrecio){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarPrecio(idPrecio);
		}
	}
	
	function validarSoloTextoNumero(evento) {
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 0)
		&& (teclaCodigo != 8)
		&& (teclaCodigo != 32)
		&& (teclaCodigo < 65 || teclaCodigo > 90)
		&& (teclaCodigo < 97 || teclaCodigo > 122)
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)
		&& (teclaCodigo != 225) /* á */
		&& (teclaCodigo != 233) /* é */
		&& (teclaCodigo != 237) /* í */
		&& (teclaCodigo != 243) /* ó */
		&& (teclaCodigo != 250) /* ú */
		&& (teclaCodigo != 193) /* Á */
		&& (teclaCodigo != 201) /* É */
		&& (teclaCodigo != 205) /* Í */
		&& (teclaCodigo != 211) /* Ó */
		&& (teclaCodigo != 218) /* Ú */
		&& (teclaCodigo != 209) /* Ñ */
		&& (teclaCodigo != 241) /* ñ */
		) {
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_alquiler.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaAlquiler">Tipos de Contrato</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblTipoContrato');">
							<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
						</a>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
					<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarTipoContrato(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>            
                <div id="divListaTipoContrato" style="width:100%"></div>
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
    
<form id="frmTipoContrato" name="frmTipoContrato" style="margin:0" onsubmit="return false;">
	<table border="0" id="tblTipoContrato" width="460">
    <tr>
        <td>
            <table border="0" width="100%">
 			<tr align="left">
                <td align="right" class="tituloCampo" width="180"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresa"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombre" name="txtNombre" onkeypress="return validarSoloTextoNumero(event);" size="20"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Filtro:</td>
                <td id="tdListFiltroContrato"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Modo Factura:</td>
                <td>
                    <select id="lstModoFactura" name="lstModoFactura" onChange="xajax_cargarLstClaveMovimiento(this.value);">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="1">FACTURA</option>
                        <option value="2">VALE DE SALIDA</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Movimiento:</td>
                <td id="tdClaveMovimiento"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Movimiento Dev:</td>
                <td id="tdClaveMovimientoDev"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Movimiento Salida:</td>
                <td id="tdClaveMovimientoSalida"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Movimiento Entrada:</td>
                <td id="tdClaveMovimientoEntrada"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddIdTipoContrato" name="hddIdTipoContrato"/>
            <button type="submit" onclick="validarfrmTipoContrato();">Guardar</button>
            <button type="button" id="btnCancelarTipoContrato" name="btnCancelarTipoContrato" class="close">Cancelar</button>
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

xajax_cargaLstEmpresaFinal("<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>","onChange=\"byId('btnBuscar').click()\"","lstEmpresaBuscar");
xajax_listaTipoContrato(0, 'id_tipo_contrato', 'ASC', "<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>");

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

</script>