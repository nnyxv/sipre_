<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_arancel_familia_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_arancel_familia_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Familia Arancelaria</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblArancelFamilia').style.display = 'none';
		
		if (verTabla == "tblArancelFamilia") {
			document.forms['frmArancelFamilia'].reset();
			byId('hddIdArancelFamilia').value = '';
			
			byId('txtIdArancelGrupo').className = 'inputHabilitado';
			byId('txtCodigoFamilia').className = 'inputHabilitado';
			byId('txtCodigoArancel').className = 'inputHabilitado';
			byId('txtDescripcionArancel').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			
			xajax_formArancelFamilia(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Familia Arancelaria';
			} else {
				tituloDiv1 = 'Agregar Familia Arancelaria';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblArancelFamilia") {
			byId('txtIdArancelGrupo').focus();
			byId('txtIdArancelGrupo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaArancelGrupo').style.display = 'none';
		
		if (verTabla == "tblListaArancelGrupo") {
			document.forms['frmBuscarArancelGrupo'].reset();
		
			byId('btnBuscarArancelGrupo').click();
			
			tituloDiv2 = 'Grupos Arancelarios';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaArancelGrupo") {
			byId('txtCriterioBuscarArancelGrupo').focus();
			byId('txtCriterioBuscarArancelGrupo').select();
		}
	}
	
	function validarFrmArancelFamilia() {
		if (validarCampo('txtIdArancelGrupo','t','numPositivo') == true
		&& validarCampo('txtCodigoFamilia','t','') == true
		&& validarCampo('txtCodigoArancel','t','') == true
		&& validarCampo('txtDescripcionArancel','t','') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
			xajax_guardarArancelFamilia(xajax.getFormValues('frmArancelFamilia'), xajax.getFormValues('frmListaArancelFamilia'));
		} else {
			validarCampo('txtIdArancelGrupo','t','numPositivo');
			validarCampo('txtCodigoFamilia','t','');
			validarCampo('txtCodigoArancel','t','');
			validarCampo('txtDescripcionArancel','t','');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idArancelFamilia){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarArancelFamilia(idArancelFamilia, xajax.getFormValues('frmListaArancelFamilia'));
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
        	<td class="tituloPaginaErp">Familia Arancelaria</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArancelFamilia');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right">			
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="0">Inactivo</option>
                            <option selected="selected" value="1">Activo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarArancelFamilia(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArancelFamilia" name="frmListaArancelFamilia" style="margin:0">
            	<div id="divListaArancelFamilia" style="width:100%"></div>
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
                        	<td><img src="img/iconos/ico_verde.gif" /></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/ico_rojo.gif" /></td>
                            <td>Inactivo</td>
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
    
<form id="frmArancelFamilia" name="frmArancelFamilia" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblArancelFamilia" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Grupo Arancelario:</td>
                <td width="70%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdArancelGrupo" name="txtIdArancelGrupo" onkeyup="xajax_asignarArancelGrupo(this.value);" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaArancelGrupo');">
                            <button type="button" id="btnSeleccionar" name="btnSeleccionar" title="Seleccionar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtDescripcionGrupoArancel" name="txtDescripcionGrupoArancel" readonly="readonly" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código Familia:</td>
                <td><input type="text" id="txtCodigoFamilia" name="txtCodigoFamilia" size="20"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código Arancelario:</td>
                <td><input type="text" id="txtCodigoArancel" name="txtCodigoArancel" size="20"/></td>
			</tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción Arancel:</td>
            	<td><input type="text" id="txtDescripcionArancel" name="txtDescripcionArancel" size="45"/></td>
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
            <input type="hidden" id="hddIdArancelFamilia" name="hddIdArancelFamilia"/>
            <button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarFrmArancelFamilia();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaArancelGrupo" width="760">
    <tr>
    	<td>
        <form id="frmBuscarArancelGrupo" name="frmBuscarArancelGrupo" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArancelGrupo" name="txtCriterioBuscarArancelGrupo" class="inputHabilitado" onkeyup="byId('btnBuscarArancelGrupo').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarArancelGrupo" name="btnBuscarArancelGrupo" onclick="xajax_buscarArancelGrupo(xajax.getFormValues('frmBuscarArancelGrupo'));">Buscar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaArancelGrupo" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right">
            <hr>
            <button type="button" id="btnCancelar2" name="btnCancelar2" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstEstatusBuscar').className = "inputHabilitado";
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

xajax_listaArancelFamilia(0, 'codigo_arancel', 'ASC', byId('lstEstatusBuscar').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>