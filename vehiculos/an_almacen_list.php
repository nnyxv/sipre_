<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_almacen_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_almacen_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Almacenes</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblAlmacen').style.display = 'none';
		
		if (verTabla == "tblAlmacen") {
			document.forms['frmAlmacen'].reset();
			byId('hddIdAlmacen').value = '';
			
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			
			xajax_formAlmacen(valor);
			
			if (valor > 0) {
				byId('txtIdEmpresa').className = 'inputInicial';
				
				byId('txtIdEmpresa').readOnly = true;
				byId('aListarEmpresa').style.display = 'none';
				
				tituloDiv1 = 'Editar Almacén';
			} else {
				byId('txtIdEmpresa').className = 'inputHabilitado';
				
				byId('txtIdEmpresa').readOnly = false;
				byId('aListarEmpresa').style.display = '';
				
				tituloDiv1 = 'Agregar Almacén';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblAlmacen") {
			byId('txtNombre').focus();
			byId('txtNombre').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		}
	}
	
	function validarFrmAlmacen() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
			xajax_guardarAlmacen(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmListaAlmacen'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idAlmacen){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAlmacen(idAlmacen, xajax.getFormValues('frmListaAlmacen'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Almacenes</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAlmacen');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">			
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select name="lstEmpresa" id="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
				</tr>		
                <tr align="left">
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarAlmacen(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaAlmacen" name="frmListaAlmacen" style="margin:0">
            	<div id="divListaAlmacen" style="width:100%"></div>
            </form>
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
        </table>
	</div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmAlmacen" name="frmAlmacen" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAlmacen" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td width="70%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                        	<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombre" name="txtNombre"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td><input type="text" id="txtDescripcion" name="txtDescripcion" size="50"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
            	<td>
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdAlmacen" name="hddIdAlmacen"/>
            <button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFrmAlmacen();">Guardar</button>
            <button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly"/>
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" onsubmit="return false;" style="margin:0">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtCriterio').className = "inputHabilitado";

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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaAlmacen(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>