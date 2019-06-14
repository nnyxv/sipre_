<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_articulo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
// se incluye el plugin
require_once '../controladores/xajax/xajax_core/xajaxPlugin.inc.php';
require_once '../controladores/xajax/xajax_core/xajaxPluginManager.inc.php';
require_once '../controladores/xajax/xajax_plugins/response/comet/comet.inc.php';
//Configuranto la ruta del manejador de script
//$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_articulo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);
	
/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Artículos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblArticulo').style.display = 'none';
		byId('tblListaDcto').style.display = 'none';
		byId('tblModeloArticulo').style.display = 'none';
		byId('tblImportarArticulo').style.display = 'none';
		byId('tblUbicacionArticulo').style.display = 'none';
		
		if (verTabla == "tblArticulo") {
			document.forms['frmArticulo'].reset();
			byId('hddIdArticulo').value = '';
			byId('txtDescripcion').innerHTML = '';
			/*byId('hddUrlImagen').value = '';
			byId('txtIdArancelFamilia').value = '';*/
			
			xajax_formArticulo(valor, xajax.getFormValues('frmArticulo'), valor2);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Artículo';
			} else {
				tituloDiv1 = 'Agregar Artículo';
			}
		} else if (verTabla == "tblListaDcto") {
			xajax_formSaldos(valor, valor2);
			
			tituloDiv1 = 'Saldos del Artículo';
		} else if (verTabla == "tblModeloArticulo") {
			xajax_formModeloArticulo(valor, valor2);
			
			tituloDiv1 = 'Modelos Compatibles';
		} else if (verTabla == "tblImportarArticulo") {
			document.forms['frmImportarArticulo'].reset();
			byId('hddUrlArchivo').value = '';
			
			byId('txtIdEmpresaImportarArticulo').className = 'inputHabilitado';
			byId('fleUrlArchivo').className = 'inputHabilitado';
			
			xajax_formImportarArticulo();
			
			tituloDiv1 = 'Importar Artículo';
		} else if (verTabla == "tblUbicacionArticulo") {
			document.forms['frmUbicacionArticulo'].reset();
			
			xajax_formUbicacionArticulo(valor, valor2);
			
			tituloDiv1 = 'Editar Ubicaciones';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblImportarArticulo") {
			byId('fleUrlArchivo').focus();
			byId('fleUrlArchivo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblVentaPerdida').style.display = 'none';
		byId('tblModelo').style.display = 'none';
		byId('tblAlmacen').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddAccion').value = '';
			byId('hddFrm').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			byId('hddAccion').value = valor;
			byId('hddFrm').value = valor2;
			
			tituloDiv2 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblVentaPerdida") {
			byId('txtCantidadArtVentaPerdida').value = '';
			
			byId('hddIdArtVentaPerdida').className = 'inputInicial';
			byId('txtCodigoArtVentaPerdida').className = 'inputInicial';
			byId('txtCantidadArtVentaPerdida').className = 'inputHabilitado';
			
			tituloDiv2 = 'Venta Perdida';
		} else if (verTabla == "tblModelo") {
			document.forms['frmBuscarModelo'].reset();
			document.forms['frmModelo'].reset();
			
			byId('txtCriterioBuscarModelo').className = 'inputHabilitado';
			
			xajax_formModelo(xajax.getFormValues('frmModeloArticulo'));
			
			tituloDiv2 = 'Modelos';
		} else if (verTabla == "tblAlmacen") {
			byId('frmAlmacen').reset();
			
			xajax_formAlmacen(xajax.getFormValues('frmUbicacionArticulo'),xajax.getFormValues('frmBuscar'));
			
			tituloDiv2 = 'Distribuir Artículo en Almacen';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblVentaPerdida") {
			byId('txtCantidadArtVentaPerdida').focus();
			byId('txtCantidadArtVentaPerdida').select();
		} else if (verTabla == "tblModelo") {
			byId('txtCriterioBuscarModelo').focus();
			byId('txtCriterioBuscarModelo').select();
		}
	}
	
	function validarActivarArticulo(idArticulo) {
		if (confirm('¿Seguro desea actualizar el estatus del artículo?') == true) {
			xajax_activarArticulo(idArticulo, xajax.getFormValues('frmListaArticulos'), xajax.getFormValues('frmBuscar'));
		}
	}
	
	function validarEliminarModeloArticuloLote() {
		if (confirm('¿Seguro desea eliminar el(los) registro(s) seleccionado(s)?') == true) {
			xajax_eliminarModeloArticuloLote(xajax.getFormValues('frmModeloArticulo'));
		}
	}
	
	function validarEliminarUbicacionArticuloLote() {
		if (confirm('¿Seguro desea eliminar el(los) registro(s) seleccionado(s)?') == true) {
			xajax_eliminarUbicacionArticuloLote(xajax.getFormValues('frmUbicacionArticulo'));
		}
	}
	
	function validarFrmAlmacen() {
		if (validarCampo('lstAlmacenAct','t','lista') == true
		&& validarCampo('lstCalleAct','t','lista') == true
		&& validarCampo('lstEstanteAct','t','lista') == true
		&& validarCampo('lstTramoAct','t','lista') == true
		&& validarCampo('lstCasillaAct','t','lista') == true) {
			xajax_guardarUbicacionArticulo(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmUbicacionArticulo'));
		} else {
			validarCampo('lstAlmacenAct','t','lista');
			validarCampo('lstCalleAct','t','lista');
			validarCampo('lstEstanteAct','t','lista');
			validarCampo('lstTramoAct','t','lista');
			validarCampo('lstCasillaAct','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmImportarArticulo() {
		if (validarCampo('txtIdEmpresaImportarArticulo','t','') == true
		&& validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarArticulo(xajax.getFormValues('frmImportarArticulo'), xajax.getFormValues('frmListaArticulos'));
		} else {
			validarCampo('txtIdEmpresaImportarArticulo','t','');
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmModelo() {
		xajax_guardarModeloArticulo(xajax.getFormValues('frmModelo'), xajax.getFormValues('frmModeloArticulo'));
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
			return false;
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmVentaPerdida() {
		if (validarCampo('txtCodigoArtVentaPerdida','t','') == true
		&& validarCampo('txtCantidadArtVentaPerdida','t','cantidad') == true) {
			if (confirm('¿Seguro desea guardar la Venta Perdida?') == true) {
				xajax_guardarVentaPerdida(xajax.getFormValues('frmVentaPerdida'), xajax.getFormValues('frmBuscar'));
			}
		} else {
			validarCampo('txtCodigoArtVentaPerdida','t','');
			validarCampo('txtCantidadArtVentaPerdida','t','cantidad');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Artículos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="window.open('iv_articulo_form.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    <!--<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticulo');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>-->
                    <a class="modalImg" id="aEliminar" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', '1');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/cross.png" title="Eliminar"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="xajax_exportarArticulos(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					<a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarArticulo');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
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
                            <option value="0">Inactivo</option>
                            <option selected="selected" value="1">Activo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Modo de Compra:</td>
                    <td>
                    	<select id="lstModoCompra" name="lstModoCompra" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="1">Nacional</option>
                        	<option value="2">Importación</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Artículo:</td>
                    <td id="tdlstTipoArticulo"></td>
                    <td align="right" class="tituloCampo">Clasificación:</td>
                    <td id="tdlstVerClasificacion"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArticulos" name="frmListaArticulos" style="margin:0">
            	<div id="divListaArticulos" style="width:100%"></div>
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
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
                        	<td><img src="../img/iconos/money.png"/></td><td>Ver Precios</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/package_green.png"/></td><td>Ver Ubicaciones</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/car.png"/></td><td>Modelos Compatibles</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver Artículo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Artículo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/package_edit.png"/></td><td>Editar Ubicaciones</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cross.png"/></td><td>Eliminar Artículo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/select.png"/></td><td>Activar Artículo</td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form action="controladores/ac_upload_file_articulo.php" enctype="multipart/form-data" id="frmArticulo" name="frmArticulo" method="post" onsubmit="return false;" style="margin:0" target="iframeUpload">
    <div id="tblArticulo" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr>
                    <td width="68%"></td>
                    <td width="32%"></td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Código:</td>
                            <td id="tdCodigoArt" width="38%"></td>
                            <td align="right" class="tituloCampo" width="16%">
                                <span class="textoRojoNegrita">*</span>Cód. Artículo:
                                <br>
                                <span class="textoNegrita_10px">(Proveedor)</span>
                            </td>
                            <td width="30%"><input type="text" id="txtCodigoProveedor" name="txtCodigoProveedor" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3"><span class="textoRojoNegrita">*</span>Descripción:</td>
                            <td rowspan="3">
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><textarea id="txtDescripcion" name="txtDescripcion" cols="26" rows="3"></textarea></td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearDescripcion" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_articulo_form_descripcion');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Artículo:</td>
                            <td id="tdlstTipoArticuloArt"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Marca:</td>
                            <td id="tdlstMarcaArt"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad:</td>
                            <td id="tdlstTipoUnidad"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sección:</td>
                            <td id="tdlstSeccionArt"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sub-Sección:</td>
                            <td id="tdlstSubSeccionArt"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="2" valign="top">
                        <table border="0" width="100%">
                        <tr>
                            <td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="file" id="fleUrlImagen" name="fleUrlImagen" class="inputHabilitado" onchange="javascript:submit();"/>
                                <iframe name="iframeUpload" style="display:none"></iframe>
                                <input type="hidden" id="hddUrlImagen" name="hddUrlImagen"/>
                            </td>
                        </tr>
                        <tr align="center">
                        	<td class="tituloCampo" width="50%">Creación</td>
                        	<td class="tituloCampo" width="50%">Clasificación</td>
                        </tr>
                        <tr align="center">
                        	<td><span id="spnFechaRegistro"></span></td>
                        	<td>
                            	<div id="divClasificacion"></div>
                                <input type="hidden" id="hddClasificacion" name="hddClasificacion"/>
							</td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Datos para Compra y Venta</legend>
                        <table border="0" width="100%">
                        <tr>
                        	<td width="20%"></td>
                        	<td width="30%"></td>
                        	<td width="20%"></td>
                        	<td width="30%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Posición Arancelaria:</td>
                            <td colspan="3">
                            	<table cellpadding="0" cellspacing="0">
                                <tr align="left">
                                    <td><input type="hidden" id="txtIdArancelFamilia" name="txtIdArancelFamilia" onkeyup="xajax_asignarArancelFamilia(this.value, false);" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarArancelFamilia" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaArancelFamilia');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtCodigoArancelFamilia" name="txtCodigoArancelFamilia" readonly="readonly" size="26"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtDescripcionArancelFamilia" name="txtArancelFamilia" readonly="readonly" size="36"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Precio Predet.:
                                <br>
                                <span class="textoNegrita_10px">(Para Ventas)</span>
                            </td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="left">
                                    <td id="tdlstPrecioPredet"></td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearPrecio" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_articulo_form_precio');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Genera Comisión:
                                <br>
                                <span class="textoNegrita_10px">(Para Ventas)</span>
                            </td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="left">
                                    <td>
                                        <select id="lstGeneraComision" name="lstGeneraComision" class="inputHabilitado">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="0">No</option>
                                            <option value="1">Si</option>
                                        </select>
                                    </td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearComision" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_articulo_form_genera_comision');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Aplica Impuesto:</td>
                        	<td>
                                <select id="lstIvaArt" name="lstIvaArt">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </td>
                        </tr>
                        </table>
					</fieldset>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Impuestos</legend>
                        <table width="100%">
                        <tr align="left">
                            <td>
                            <a class="modalImg" id="aNuevoImpuesto" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaImpuesto');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="xajax_eliminarUnidadBasicaImpuesto(xajax.getFormValues('frmUnidadBasica'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td><input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                    <td width="25%%">Tipo Impuesto</td>
                                    <td width="55%">Observación</td>
                                    <td width="20%">% Impuesto</td>
                                </tr>
                                <tr id="trItmPieImpuesto"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                	<td valign="top">
                    <fieldset><legend class="legend">Otros Datos</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="35%">Largo:</td>
                            <td width="65%"><input type="text" id="txtLargo" name="txtLargo" size="12" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Ancho:</td>
                            <td><input type="text" id="txtAncho" name="txtAncho" size="12" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Alto:</td>
                            <td><input type="text" id="txtAlto" name="txtAlto" size="12" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="wrap">
                            <!-- the tabs -->
                            <ul class="tabs">
                                <li><a href="#">Art. que Sustituye</a></li>
                                <li><a href="#">Art. Alternos</a></li>
                            </ul>
                            
                            <!-- tab "panes" -->
                            <div class="pane">
                            	<input type="hidden" id="hddObjArtSust" name="hddObjArtSust"/>
                                <table border="0" width="100%">
                        		<tr align="left">
                                    <td>
                                    <a class="modalImg" id="aInsertarArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaArtSustAlt', 'formArticuloSustituto');">
                                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/add.png" title="Agregar Artículo Sustituto"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                    </a>
                                        <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListaArtSust'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/delete.png" title="Quitar Artículo Sustituto"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="max-height:300px; overflow:auto; width:100%;">
                                            <table border="0" width="100%">
                                            <tr align="center" class="tituloColumna">
                                                <td><input type="checkbox" id="cbxItmArtSust" onclick="selecAllChecks(this.checked,this.id,'frmListaArtSust');"/></td>
                                                <td width="20%">Código Artículo</td>
                                                <td width="80%">Descripción</td>
                                                <td></td>
                                            </tr>
                                            <tr id="trItmPieArtSust"></tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                </table>
                            </div>
                            <div class="pane">
                            	<input type="hidden" id="hddObjArtAlt" name="hddObjArtAlt"/>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td>
                                    <a class="modalImg" id="aInsertarArtAlt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaArtSustAlt', 'formArticuloAlterno');">
                                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/add.png" title="Agregar Artículo Alterno"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                    </a>
                                        <button type="button" id="btnEliminarArtAlt" name="btnEliminarArtAlt" onclick="xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListaArtAlt'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/delete.png" title="Quitar Artículo Alterno"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="max-height:300px; overflow:auto; width:100%;">
                                            <table border="0" width="100%">
                                            <tr align="center" class="tituloColumna">
                                                <td><input type="checkbox" id="cbxItmArtAlt" onclick="selecAllChecks(this.checked,this.id,'frmListaArtAlt');"/></td>
                                                <td width="20%">Código Artículo</td>
                                                <td width="80%">Descripción</td>
                                                <td></td>
                                            </tr>
                                            <tr id="trItmPieArtAlt"></tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                </table>
                            </div>
						</div>
					</td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo"  onclick="validarFrmArticulo();">Guardar</button>
                <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
    
    <div id="tblListaDcto" style="max-height:520px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr align="left">
            <td>
            <a class="modalImg" id="aVentaPerdida" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblVentaPerdida');">
                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/exclamation.png" title="Venta Perdida"/></td><td>&nbsp;</td><td>Venta Perdida</td></tr></table></button>
            </a>
            </td>
        </tr>
        <tr>
            <td><div id="divUbicacionesSaldos" style="width:100%"></div></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/aprob_mecanico.png"/></td><td>Ubicación Predeterminada para Venta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/aprob_control_calidad.png"/></td><td>Ubicación Predeterminada para Compra</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
                            <td class="divMsjInfo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Disponible</td>
                            <td>&nbsp;</td>
                            <td class="divMsjError">&nbsp;&nbsp;&nbsp;*</td><td>Ubicación Ocupada</td>
                            <td>&nbsp;</td>
                            <td class="divMsjInfo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Inactiva</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
                
            <fieldset id="fieldsetListaDcto"><legend class="legend" id="legendListaDcto"></legend>
                <div id="divListaDcto" style="width:100%"></div>
                <div id="tdMsj" style="width:100%"></div>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelarListaDcto" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
    </div>
    
<form id="frmModeloArticulo" name="frmModeloArticulo" style="margin:0;" onsubmit="return false;">
    <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/>
    <input type="hidden" id="hddIdArticuloModeloArticulo" name="hddIdArticuloModeloArticulo"/>
    <div id="tblModeloArticulo" style="max-height:520px; overflow:auto; width:960px">
    	<table border="0" width="100%">
        <tr align="left">
            <td>
            <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblModelo');">
                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
            </a>
                <button id="btnEliminar" name="btnEliminar" onclick="validarEliminarModeloArticuloLote()"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                
                <label>
                <table cellpadding="0" cellspacing="0">
                <tr>
                    <td><input type="checkbox" id="cbxItm3" onclick="selecAllChecks(this.checked,this.id,'frmModeloArticulo');"/></td>
                    <td class="textoNegrita_10px">Seleccionar Todos / Deseleccionar Todos</td>
                </tr>
                </table>
                </label>
            </td>
        </tr>
        <tr>
        	<td>
            	<div id="divListaModeloArticulo" style="width:100%"></div>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2"><hr>
                <button type="button" id="btnCancelarModeloArticulo" name="btnCancelarModeloArticulo" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
    </div>
</form>

<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarArticulo" name="frmImportarArticulo" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarArticulo" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
        <td width="85%">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="txtIdEmpresaImportarArticulo" name="txtIdEmpresaImportarArticulo" onblur="xajax_asignarEmpresaUsuario(this.value, 'EmpresaImportarArticulo', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                    <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                </a>
                </td>
                <td><input type="text" id="txtEmpresaImportarArticulo" name="txtEmpresaImportarArticulo" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr align="left">
    	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td>
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript: submit();"/>
			<iframe name="iframeUpload" style="display:none"></iframe>
            <input type="hidden" id="hddUrlArchivo" name="hddUrlArchivo" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table width="100%">
                    <tr>
                        <td colspan="10">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
                        <td>Código</td>
                        <td>Id Marca</td>
                        <td>Id Tipo Articulo</td>
                        <td>Código Art. Prov</td>
                        <td>Descripción</td>
                        <td>Id Subsección</td>
                        <td>Clasificación</td>
                        <td>Id Unidad</td>
                        <td>Id Proveedor</td>
                        <td>Costo</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
            <div id="divMsjImportar"></div>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
        	<button type="submit" id="btnGuardarImportarArticulo" name="btnGuardarImportarArticulo" onclick="validarFrmImportarArticulo();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarArticulo" name="btnCancelarImportarArticulo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
	
<form id="frmUbicacionArticulo" name="frmUbicacionArticulo" style="margin:0;" onsubmit="return false;">
    <input type="hidden" id="hddIdEmpresaUbicacionArticulo" name="hddIdEmpresaUbicacionArticulo"/>
    <input type="hidden" id="hddIdArticuloUbicacionArticulo" name="hddIdArticuloUbicacionArticulo"/>
    <div id="tblUbicacionArticulo" style="max-height:520px; overflow:auto; width:960px">
        <table border="0" width="960">
        <tr align="left">
            <td>
            <a class="modalImg" id="aNuevoUbicacion" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblAlmacen');">
                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
            </a>
                <button id="btnEliminar" name="btnEliminar" onclick="validarEliminarUbicacionArticuloLote()"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
            </td>
        </tr>
        <tr>
        	<td>
            	<div id="divListaUbicacionArticulo" style="width:100%"></div>
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
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Relacion Ubicación con Artículo Activa</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Relacion Ubicación con Artículo Inactiva</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"/></td><td>Activar Relación</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
                            <td class="divMsjInfo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Disponible</td>
                            <td>&nbsp;</td>
                            <td class="divMsjError">&nbsp;&nbsp;&nbsp;*</td><td>Ubicación Ocupada</td>
                            <td>&nbsp;</td>
                            <td class="divMsjInfo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Inactiva</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2"><hr>
                <button type="button" id="btnCancelarUbicacionArticulo" name="btnCancelarUbicacionArticulo" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmPermiso" name="frmPermiso" style="margin:0;" onsubmit="return false;">
    <table border="0" id="tblPermiso" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30" value="iv_articulo_list.php"/>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddAccion" name="hddAccion" readonly="readonly"/>
            <input type="hidden" id="hddFrm" name="hddFrm" readonly="readonly"/>
            <button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
            <button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana"/>
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
    
<form id="frmVentaPerdida" name="frmVentaPerdida" style="margin:0px" onsubmit="return false;">
    <input type="hidden" id="hddIdArtVentaPerdida" name="hddIdArtVentaPerdida"/>
	<table id="tblVentaPerdida" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Código:</td>
                <td width="70%"><input type="text" id="txtCodigoArtVentaPerdida" name="txtCodigoArtVentaPerdida" readonly="readonly" size="25"/></td>
			</tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
            	<td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArtVentaPerdida" name="txtCantidadArtVentaPerdida" maxlength="6" onkeypress="return validarSoloNumeros(event);" size="10" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArtVentaPerdida" name="txtUnidadArtVentaPerdida" readonly="readonly" size="15"/></td>
					</tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
	</tr>
    <tr>
    	<td align="right"><hr>
	        <button type="submit" id="btnGuardarVentaPerdida" name="btnGuardarVentaPerdida" onclick="validarFrmVentaPerdida();">Guardar</button>
            <button type="button" id="btnCancelarVentaPerdida" name="btnCancelarVentaPerdida" class="close">Cancelar</button>
		</td>
    </tr>
	</table>
</form>
    
    <div id="tblModelo" style="max-height:520px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarModelo" name="frmBuscarModelo" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarModelo" name="txtCriterioBuscarModelo" onkeyup="byId('btnBuscarModelo').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarModelo" onclick="xajax_buscarModelo(xajax.getFormValues('frmBuscarModelo'), xajax.getFormValues('frmModeloArticulo'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarModelo'].reset(); byId('btnBuscarModelo').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmModelo" name="frmModelo" onsubmit="return false;" style="margin:0">
                <label>
                <table cellpadding="0" cellspacing="0">
                <tr>
                    <td><input type="checkbox" id="cbxItm2" onclick="selecAllChecks(this.checked,this.id,'frmModelo');"/></td>
                    <td class="textoNegrita_10px">Seleccionar Todos / Deseleccionar Todos</td>
                </tr>
                </table>
                </label>
                <div id="divListaModelo" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="submit" id="btnGuardarModelo" name="btnGuardarModelo" onclick="validarFrmModelo();">Guardar</button>
                <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
	
<form id="frmAlmacen" name="frmAlmacen" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAlmacen" width="560">
    <tr>
        <td valign="top">
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresaAct">
                    <select id="lstEmpresaAct" name="lstEmpresaAct">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span><?php echo $spanAlmAlmacen; ?>:</td>
                <td id="tdlstAlmacenAct" width="94%">
                    <select id="lstAlmacenAct" name="lstAlmacenAct">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmCalle; ?>:</td>
                <td id="tdlstCalleAct">
                    <select id="lstCalleAct" name="lstCalleAct">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmEstante; ?>:</td>
                <td id="tdlstEstanteAct">
                    <select id="lstEstanteAct" name="lstEstanteAct">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmTramo; ?>:</td>
                <td id="tdlstTramoAct">
                    <select id="lstTramoAct" name="lstTramoAct">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmCasilla; ?>:</td>
                <td id="tdlstCasillaAct">
                    <select id="lstCasillaAct" name="lstCasillaAct">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
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
                    <table>
                    <tr>
                        <td class="divMsjInfo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Disponible</td>
                        <td>&nbsp;</td>
                        <td class="divMsjError">&nbsp;&nbsp;&nbsp;*</td><td>Ubicación Ocupada</td>
                        <td>&nbsp;</td>
                        <td class="divMsjInfo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Inactiva</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFrmAlmacen();">Aceptar</button>
            <button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('lstEstatus').className = "inputHabilitado";
byId('lstModoCompra').className = "inputHabilitado";
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoArticulo();
xajax_cargaLstClasificacion();
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaArticulo(0, 'id_articulo', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + $('lstEstatus').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>