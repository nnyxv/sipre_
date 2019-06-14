<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_articulo_costo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_articulo_costo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Costos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblCostoArticulo').style.display = 'none';
		
		if (verTabla == "tblCostoArticulo") {
			document.forms['frmCostoArticulo'].reset();
			byId('hddIdArticuloCosto').value = '';
			byId('hddIdArticulo').value = '';
			byId('txtMotivoCreacion').innerHTML = '';
			
			byId('txtCodigoArticulo').className = 'inputInicial';
			byId('txtIdProv').className = 'inputHabilitado';
			byId('txtFechaCosto').className = 'inputHabilitado';
			byId('txtCosto').className = 'inputHabilitado';
			byId('txtMotivoCreacion').className = 'inputHabilitado';
			
			xajax_formCosto(valor, valor2);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Costo';
			} else {
				tituloDiv1 = 'Agregar Costo';
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblCostoArticulo") {
			byId('txtCosto').focus();
			byId('txtCosto').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblLista').style.display = 'none';
		
		if (verTabla == "tblLista") {
			byId('trBuscarArticulo').style.display = 'none';
			byId('trBuscarProveedor').style.display = 'none';
			byId('divLista').innerHTML = '';
			if (valor == "Articulo") {
				document.forms['frmBuscarArticulo'].reset();
				
				byId('trBuscarArticulo').style.display = '';
				byId('txtCriterioBuscarArticulo').className = 'inputHabilitado';
				
				xajax_objetoCodigoDinamico('tdCodigoArtBuscar', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', '', '', '', false, 'Buscar');
				
				byId('btnBuscarArticulo').click();
				
				tituloDiv2 = 'Articulos';
				byId(verTabla).width = "960";
			} else if (valor == "Proveedor") {
				document.forms['frmBuscarProveedor'].reset();
				
				byId('trBuscarProveedor').style.display = '';
				byId('txtCriterioBuscarProveedor').className = 'inputHabilitado';
				
				byId('btnBuscarProveedor').click();
				
				tituloDiv2 = 'Proveedores';
				byId(verTabla).width = "760";
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblLista") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		}
	}
	
	function validarActivarLote(idArticuloCosto) {
		if (confirm('¿Seguro desea actualizar el estatus del lote?') == true) {
			xajax_activarLote(idArticuloCosto, xajax.getFormValues('frmListaCostos'));
		}
	}
	
	function validarFrmCostoArticulo() {
		if (validarCampo('txtCodigoArticulo','t','') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtFechaCosto','t','fecha') == true
		&& validarCampo('txtCosto','t','monto') == true
		&& validarCampo('txtMotivoCreacion','t','') == true) {
			xajax_guardarCosto(xajax.getFormValues('frmCostoArticulo'), xajax.getFormValues('frmListaCostos'), xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('txtCodigoArticulo','t','');
			validarCampo('txtIdProv','t','');
			validarCampo('txtFechaCosto','t','fecha');
			validarCampo('txtCosto','t','monto');
			validarCampo('txtMotivoCreacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Costos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblCostoArticulo');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="xajax_exportarCosto(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
                </tr>
                </table>
                
	        <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Estatus:</td>
                	<td>
                        <select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="">Inactivo</option>
                            <option selected="selected" value="1">Activo</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarCosto(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
		</tr>
        <tr>
        	<td>
            <form id="frmListaCostos" name="frmListaCostos" style="margin:0">
            	<div id="divListaCostos" style="width:100%"></div>
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
                            <td align="right"><img src="../img/iconos/cancel.png"/></td><td align="center">Desactivar</td>
                            <td>&nbsp;</td>
                            <td align="right"><img src="../img/iconos/accept.png"/></td><td align="center">Activar</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	Unid. Disponible = Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
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
    
<form id="frmCostoArticulo" name="frmCostoArticulo" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddIdArticuloCosto" name="hddIdArticuloCosto" readonly="readonly"/>
    <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
    <table id="tblCostoArticulo" border="0" width="560">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Código</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" readonly="readonly" size="25"/></td>
                        <td>
                        <a class="modalImg" id="aCodigoArticulo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista', 'Articulo');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value);" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aInsertarProv" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista', 'Proveedor');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                <td><input type="text" id="txtFechaCosto" name="txtFechaCosto" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                <td><input type="text" id="txtCosto" name="txtCosto" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="14" style="text-align:right"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:</td>
                <td><textarea id="txtMotivoCreacion" name="txtMotivoCreacion" rows="3" style="width:99%"></textarea></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarCostoArticulo" name="btnGuardarCostoArticulo" onclick="validarFrmCostoArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarCostoArticulo" name="btnCancelarCostoArticulo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
  	
    <table border="0" id="tblLista" width="760">
    <tr id="trBuscarArticulo">
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddModoArticulo" name="hddModoArticulo"/>
            <table align="right">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArtBuscar"></td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticulo" name="txtCriterioBuscarArticulo"/></td>
                <td>
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmArticulo'));">Buscar</button>
                	<button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarProveedor">
    	<td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divLista" style="width:100%;"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstEstatus').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaCosto").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaCosto",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
};

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
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaCosto(0, "id_articulo_costo", "DESC", "<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|" + byId('lstEstatus').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>