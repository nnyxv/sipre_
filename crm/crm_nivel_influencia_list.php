<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_nivel_influencia_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_crm_nivel_influencia_list.php");
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Nivel de Influencia</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
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
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true
		) {
			xajax_guardarNivelInfluencia(xajax.getFormValues('frmNivelInfluencia'), xajax.getFormValues('frmListaConfiguracion'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtNombre','t','');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idNivelInfluencia){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarNivelInfluencia(idNivelInfluencia, xajax.getFormValues('frmListaConfiguracion'));
		}
	}
	
	//LISTA LAS EMPRESAS
    function formListaEmpresa(valor, valor2) {
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = valor;
		byId('hddNomVentana').value = valor2;
		
		byId('btnBuscarEmpresa').click();
		
		tituloDiv1 = 'Empresas';
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv1;
    }

	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_crm.php"); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCrm">Nivel de Influencia</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_formNivelInfluencia(this.id);">
                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
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
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarNivelInfluencia(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaConfiguracion" name="frmListaConfiguracion" style="margin:0">
            	<div id="divListaConfiguracion" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
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
    
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>


<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmNivelInfluencia" name="frmNivelInfluencia" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblClaveEspecial" width="560">
    <tr align="left">
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td width="85%">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="left">
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="openImg(this); formListaEmpresa('Empresa', 'ListaEmpresa');">
                            <button type="button" id="btnInsertarEmp" name="btnInsertarEmp" style="cursor:default" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="50"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombre" name="txtNombre"/></td>
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
    <tr align="left">
    	<td align="right">
	        <hr>
            <input type="hidden" id="hddIdNivelInfluencia" name="hddIdNivelInfluencia"/>
            <button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarForm();">
               <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_save.png"/></td>
                        <td>&nbsp;</td>
                        <td>Guardar</td>
                    </tr>
                </table>
            </button>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                    </tr>
                </table>
            </button>
        </td>
    </tr>
    </table>
</form>
</div>

<!--Listad de empresas-->
<div id="divFlotante2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;" class="root">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
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
        <form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
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
xajax_listadoNivelInfluencia(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>
<script language="javascript">
var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>