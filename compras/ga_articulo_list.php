<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(validaAcceso("ga_articulo_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_articulo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Artículos</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
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

function abrirDivFlotante(idObjeto){
	openImg(idObjeto);
	switch(idObjeto){
		case "aNuevo":
			document.forms['formArticulo'].reset();
			xajax_formArticulo(idObjeto);
			break;			
	}
}

function showHide(showHide){
	switch(showHide){
		case "show":
			$('#trArtSustAlter').show();
			$('#btnGuardaArtSustAlt').show();
				break;
		case "hide":
			$('#btnGuardaArtSustAlt').hide();
			$('#trArtSustAlter').hide();
			document.getElementById('hddItemArticulo').value = "";	
			document.getElementById('nombObjArtAlterSust').value = "";	
				break;	
	}
}

function remover(){
	if(document.formArticulo.lstSubSeccionArt){
		$('#lstSubSeccionArt').remove();
	} else {
		$('#lstSubSeccionArtVer1').remove();
	}
}

function tomarIdArt(){
	var idArticulo = document.getElementById("hddIdArticulo").value;
	var itemArticulo = document.getElementById("hddItemArticulo").value;
	var nombObj = document.getElementById("nombObjArtAlterSust").value;

	xajax_validarArtSustAlt(itemArticulo, nombObj, xajax.getFormValues('formArticulo'));

	$('#btnGuardaArtSustAlt').hide();
}

function eliminarTr(){
	$('#tabArtSust tr:not(:first-child)').remove();
	$('#tabArtAlte tr:not(:first-child)').remove();
}

function validarDesactivarArticulo(idArticulo, hddIdItm) {
	if (confirm('¿Seguro desea Cambiarle el Estatus al Articulo?') == true) {
		byId('imgVerArticulo' + hddIdItm).style.display = 'none';
		byId('imgEditarArticulo' + hddIdItm).style.display = 'none';
		byId('imgDesactivarArticulo' + hddIdItm).style.display = 'none';
		xajax_desactivarArticulo(idArticulo, hddIdItm, xajax.getFormValues('frmListaArticulos'));
	}
}

function valElimArtSustAlte(idObj){
	switch(idObj){
		case "btnElimArtSustituto":
			if(confirm('¿Estas Seguro que se Desea Eliminar Este Items de los Articulos Sustituto?') == true)
				break;
		case "btnElimArtAlterno":
			if(confirm('¿Estas Seguro que se Desea Eliminar Este Items de los Articulos Alterno?') == true)
				break;		
	}
	xajax_eliminarArtSustAlterno(idObj,xajax.getFormValues('formArticulo'))
}

function validarFormArt(){
	if (validarCampo('txtCodigoArticulo','t','') == true
	&& validarCampo('txtCodigoArtPro','t','') == true
	&& validarCampo('txtDescripcion','t','') == true
	&& validarCampo('lstMarcaArt','t','lista') == true
	&& validarCampo('lstTipoArticuloArt','t','lista') == true
	&& validarCampo('lstTipoUnidad','t','lista') == true
	&& validarCampo('lstSeccionArt','t','lista') == true
	&& validarCampo('lstSubSeccionArt','t','lista') == true) {
		xajax_guardarArticulo(xajax.getFormValues('formArticulo'));
	} else {
		validarCampo('txtCodigoArticulo','t','');
		validarCampo('txtCodigoArtPro','t','');
		validarCampo('txtDescripcion','t','');
		validarCampo('lstMarcaArt','t','lista');
		validarCampo('lstTipoArticuloArt','t','lista');
		validarCampo('lstTipoUnidad','t','lista');
		validarCampo('lstSeccionArt','t','lista');
	
		if(document.formArticulo.lstSubSeccionArt){
			validarCampo('lstSubSeccionArt','t','lista')
		}
		alert("Los campos señalados en rojo son requeridos");
	}
}

</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_compras.php"); ?></div>
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaCompras">Artículos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint">
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                        <a class="modalImg" id="aNuevo" name="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante(this.id)"><!---->
                            <button type="button" style="cursor:default"><!--/*window.open('ga_articulo_form.php','_self');*/-->
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/ico_new.png"/></td>
                                        <td>&nbsp;</td>
                                        <td>Nuevo</td>
                                    </tr>
                                </table>
                            </button>
                        </a>
                    </td>
                    <td>
                    	<button type="button" id="btnExportarExcel" name="btnExportarExcel" onclick="xajax_exportarExcel(xajax.getFormValues('frmBuscar'));" style="cursor:default">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_excel.png"/></td>
                                    <td>&nbsp;</td>
                                    <td>Exportar</td>
                                </tr>
                            </table>
                        </button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="4"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Estatus:</td>
                    <td>
                        <select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();" class="inputHabilitado">
                            <option value="-1">[ Todos ]</option>
                            <option selected="selected" value="1">Activo</option>
                            <option value="">Inactivo</option>
                        </select>
                    </td>
                    
                    <td align="right" class="tituloCampo">Tipo De Articulo:</td>
                    <td id="tdlstTipoArticuloArtBus2" colspan="2"></td>
                    
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
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
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver Artículo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_edit.png"/></td><td>Editar Artículo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_aceptar.gif"/></td><td>Activar Artículo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_error.gif"/></td><td>Desactivar Artículo</td>
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

<!--FRORMULARIO PARA AGREGAR ARTICULO-->
<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo1" width="100%" align="left"></td>
            </tr>
        </table>
    </div>
    <form action="controladores/ac_upload_file_articulo.php" id="formArticulo" name="formArticulo" enctype="multipart/form-data" method="post" style="margin:0;" target="iframeUpload">
        <table width="100%" border="0">
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Marca</td>
                <td align="left" id="tdlstMarcaArt"></td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Artículo:</td>
                <td align="left" id="tdlstTipoArticuloArt">&nbsp;</td>
                <td align="" id="">
                    <button type="button" id="btnEtiqueta" name="btnEtiqueta" title="Generar Etiqueta" style="cursor:default; display:none">
                        <table align="center" cellpadding="0" cellspacing="0">
                            <tr>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/tag_blue.png"/></td>
                            <td>&nbsp;</td>
                            <td>Etiqueta</td></tr>
                        </table>
                    </button>
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Código:</td>
                <td align="left"> <input type="text" name="txtCodigoArticulo" id="txtCodigoArticulo"  maxlength="22" size="26"/></td> 
                <td align="right" class="tituloCampo" width="28%"><span class="textoRojoNegrita">*</span>Cód. Articulo (Proveedor):</td>
                <td align="left"><input type="text" name="txtCodigoArtPro" id="txtCodigoArtPro"  maxlength="22" size="26"/></td> 
                <td rowspan="5">
                    <table border="0" width="100%">
                        <tr>
                            <td><img border="0" id="imgCodigoBarra" name="imgCodigoBarra"></td>
                        </tr>
                        <tr>
                            <td align="center" class="imgBorde"><img id="imgArticulo" src="../img/logos/logo_gotosystems.jpg" height="100"/></td>
                        </tr>
                        <tr>
                            <td>
                                <input type="file" id="fleUrlImagen" name="fleUrlImagen" onchange="javascript: submit();"/>
                                	<iframe name="iframeUpload" style="display:none"></iframe>
                                <input type="hidden" id="hddUrlImagen" name="hddUrlImagen" value=""/>
                            </td>
                        </tr>
                    </table>                
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td align="left" colspan="3"><textarea cols="66" id="txtDescripcion" name="txtDescripcion" rows="4"></textarea></td> 
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad:</td>
                <td align="left" id="tdlstTipoUnidad" colspan="3"></td> 
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Seccion:</td>
                <td align="left" id="tdlstSeccionArt" colspan="3"></td> 
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sub-Seccion:</td>
                <td align="left" colspan="3" id="tdlstSubSeccionArt">
                    	<select id="" name="">
                            <option value="">[ Seleccione ]</option>
                        </select>
                </td>
            </tr>
            <tr>
                <td align="left" colspan="6">
                    <div class="wrap"> <!-- the tabs -->
                        <ul class="tabs">
                            <li><a href="#" onclick="">Articulos Sustitutos</a></li>
                            <li><a href="#" onclick="">Articulos Alternos</a></li>
                        </ul>
                        
                        <div id="" class="pane"> <!--ART SUSTITUTO-->
                            <table  cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tr>
                                    <td>
                                        <table cellpadding="0" cellspacing="0" width="100%" border="0">
                                            <tr>
                                                <td  width="100%" align="left">
                                                	<a class="modalImg" id="artSust" rel="#divFlotante2" onclick="xajax_formArticulo(this.id, xajax.getFormValues('formArticulo')); ">
                                                        <button id="btnAgreArtSustituto" name="btnAgreArtSustituto" type="button" title="Agregar Art. Sustituto" 
                                                        onclick="">
                                                           <table align="center" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/add.png" width="16" height="16" /></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar</td>
                                                            </tr>
                                                        </table>
                                                        </button>
                                                    </a>
                                                    <button id="btnElimArtSustituto" name="btnElimArtSustituto" type="button" title="Eliminar Art. Sustituto"
                                                    onclick="valElimArtSustAlte(this.id);">
                                                         <table align="center" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/delete.png" width="16" height="16"/></td>
                                                                <td>&nbsp;</td>
                                                                <td>Quitar</td>
                                                            </tr>
                                                        </table>
                                                    </button>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                    	<table id="tabArtSust" width="100%" border="0"> <!--ITEM AGREGADO ART SUSTITUTO-->
                                          <tr align="center" class="tituloColumna">
                                            <td width=""><input id="checkArtSust" type="checkbox" onclick="selecAllChecks(this.checked,this.id,'formArticulo');" /></td>
                                            <td width="15%">Código del Art.</td>
                                            <td width="70%">Descripción</td>
                                            <td width="15%">Existéncia</td>
                                            <td>&nbsp;</td>
                                          </tr>
                                          
                                        </table>

                                    </td>
                                </tr>
                            </table>
                        <input type="hidden" name="itemArtSustituto" id="itemArtSustituto" maxlength="22" size="26"/>     
                        </div> <!--FIN ART SUSTITUTO-->
                        
                         <div id="" class="pane"> <!--ART ALTERNO-->
                            <table  cellpadding="0" cellspacing="0" width="100%" border="0">
                                <tr>
                                    <td>
                                        <table cellpadding="0" cellspacing="0" width="100%" border="0">
                                            <tr>
                                                <td  width="100%" align="left">
                                                	<a class="modalImg" id="artAlter" rel="#divFlotante2" onclick="xajax_formArticulo(this.id, xajax.getFormValues('formArticulo'));">
                                                        <button id="btnAgreArtAlerteno" name="btnAgreArtAlerteno" type="button" title="Agregar Art. Aleterno" 
                                                        onclick="">
                                                        <table align="center" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/add.png" width="16" height="16" /></td>
                                                                <td>&nbsp;</td>
                                                                <td>Agregar</td>
                                                            </tr>
                                                        </table>
                                                        </button>
                                                    </a>
                                                    <button id="btnElimArtAlterno" name="btnElimArtAlterno" type="button" title="Eliminar Art. Aleterno"
                                                    onclick="valElimArtSustAlte(this.id);">
                                                         <table align="center" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td>&nbsp;</td>
                                                                <td><img src="../img/iconos/delete.png" width="16" height="16"/></td>
                                                                <td>&nbsp;</td>
                                                                <td>Quitar</td>
                                                            </tr>
                                                        </table>
                                                    </button>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                    	<table id="tabArtAlte" width="100%" border="0"> <!--ITEM AGREGADO ART ALERTNOS-->
                                          <tr align="center" class="tituloColumna">
                                            <td width=""><input id="checkArtAlte" type="checkbox" onclick="selecAllChecks(this.checked,this.id,'formArticulo');" /></td>
                                            <td width="15%">Código del Art.</td>
                                            <td width="70%">Descripción</td>
                                            <td width="15%">Existéncia</td>
                                            <td>&nbsp;</td>
                                          </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>
                     <input type="hidden" name="itemArtAlterno" id="itemArtAlterno" maxlength="22" size="26"/>       
                        </div> <!--FIN ART ALETERNO-->

                        
                     </div>
                </td>
            </tr>
            <tr>
                <td colspan="5" align="right">
                <hr>
            		<button type="button" id="btnGuardaArt" name="btnGuardaArt"  onclick="validarFormArt();">
                        Guardar
                    </button>
                    <button type="button" id="btnImprimiArt" name="btnImprimiArt" style="display:none">
                        Ver PDF
                    </button>
                    <button type="button" id="btnCancelaArt" name="btnCancelaArt" class="close">
                    	Cancelar
                    </button>
                    <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly" value=""/>
                </td>
            </tr>
        </table>
    </form>
</div> <!--FIN AGREGAR ARITICULO NUEVO-->

<!--PARA BUSCAR ARTICULOS SUSTITUTOS Y/O ALTERNOS-->  <!---->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width:auto">
	<div id="divFlotanteTitulo2" class="handle">
        <table width="100%">
            <tr>
                <td id="tdFlotanteTitulo2"  align="left"></td>
            </tr>
        </table>
    </div>
        <table border="0" id="tblDcto" width="980px">
            <tr>
                <td id="">
                    <form id="formBuscarSustiAlter" name="formBuscarSustiAlter" onsubmit="return false;">
                        <input type="hidden" id="textCriterioHidIdArtBus" name="textCriterioHidIdArtBus" />
                        <table border="0" align="right"> <!--BUSCAR ARTICULOS SUSTITUTOS Y/O ALTERNOS-->
                            <tr>
                                <td class="tituloCampo">Criterio:</td>
                                <td><input type="text" id="textCriterio" name="textCriterio" /></td>
                                <td>
                                <button type="button" id="btnBuscarArtSustAlt" name="btnBuscarArtSustAlt" 
                                onclick="xajax_buscarArtSustAlte(xajax.getFormValues('formBuscarSustiAlter'));">
                                    Buscar
                                </button>
                                </td>
                                <td><button type="button" id="btnLimpiarArtSustAlt" name="btnLimpiarArtSustAlt" 
                                onclick="this.form.reset(); byId('btnBuscarArtSustAlt').click();">Limpiar
                                </button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td id="tdartSustitutoAlterno"></td>
            </tr>
            <tr style="display:none" id="trArtSustAlter">
                <td id="divArtSustAlter">
                    <fieldset><legend align="right"><img src="../img/iconos/cross.png" onclick="showHide('hide');"></legend>
                        <table width="100%" border="0">
                            <tr>
                                <td align="right" class="tituloCampo">Codigo:</td>
                                <td align="left"><input type="text" name="txtBusCodigoArticulo" id="txtBusCodigoArticulo" maxlength="22" size="26" readonly="readonly"/></td>
                                <td align="right" class="tituloCampo">Codigo Art. (Proveedor):</td>
                                <td align="left"><input type="text" name="txtBusCodigoArtProv" id="txtBusCodigoArtProv" maxlength="22" size="26" readonly="readonly"/></td>
                                <td align="right" class="tituloCampo">Marca</td>
                                <td align="left" id="tdlstMarcaArtBus"></td>
                            </tr>
                            <tr>
                                <td align="right" class="tituloCampo">Tipo Articulo</td>
                                <td align="left" id="tdlstTipoArticuloArtBus"></td>
                                <td align="right" class="tituloCampo">Seccion</td>
                                <td align="left" id="tdlstSeccionArtBus"  colspan="3"></td>
                            </tr>
                            <tr>
                                <td align="right" class="tituloCampo">Sub-seccion:</td>
                                <td align="left" id="tdlstSubSeccionBus" colspan="5"></td>
                            </tr>
                            <tr>
                                <td align="right" class="tituloCampo">Descripción:</td>
                                <td align="left" colspan="6"><textarea cols="66" id="txtBusDescripcion" name="txtBusDescripcion" rows="4" readonly="readonly"></textarea></td>
                            </tr>
                            <tr>
                                <td align="right" colspan="2" class="tituloCampo">Stock Máximo:</td>
                                <td align="left"><input type="text" name="txtBusStockMax" id="txtBusStockMax" maxlength="22" size="26" readonly="readonly"/></td>
                                 <td align="right" colspan="2" class="tituloCampo">Stock Minimo:</td>
                                <td align="left"><input type="text" name="txtBusStockMin" id="txtBusStockMin" maxlength="22" size="26" readonly="readonly"/></td>
                            </tr>
                            <tr>
                        </table>
                        <input type="hidden" id="hddItemArticulo" name="hddItemArticulo" readonly="readonly" value=""/>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td align="right"><hr>
                     <button type="button" id="btnGuardaArtSustAlt" name="btnGuardaArtSustAlt" style="display:none" onclick="tomarIdArt(); $('#trArtSustAlter').hide();">
                        Guardar
                    </button>
                    <button type="button" id="btnCancelarArtSustAlt" name="btnCancelarArtSustAlt" class="close" onclick="showHide('hide');">
                       Cancelar
                    </button>
                <input id="nombObjArtAlterSust" name="nombObjArtAlterSust" type="hidden" value=""/> 
                </td>
            </tr>
        </table>
</div><!--FIN BUSCAR ARTICULOS SUSTITUTOS Y/O ALTERNOS-->

<!--VER ARTICULO-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width:auto">
	<div id="divFlotanteTitulo3" class="handle">
        <table width="100%">
            <tr>
                <td id="tdFlotanteTitulo3" align="left">Ver Articulo</td>
            </tr>
        </table>
    </div>
    <table width="100%" border="0">
        <tr>
            <td align="right" class="tituloCampo">Marca</td>
            <td align="left" id="tdlstMarcaArtVer"></td>
            <td align="right" class="tituloCampo">Tipo de Artículo:</td>
            <td align="left" id="tdlstTipoArticuloArtVer">&nbsp;</td>
            <td align="" id="">&nbsp;</td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo" width="16%">Código:</td>
            <td align="left"><input type="text" name="txtCodigoArtVer" id="txtCodigoArtVer" maxlength="22" size="26" readonly="readonly"/></td> 
            <td align="right" class="tituloCampo" width="28%">Cód. Articulo (Proveedor):</td>
            <td align="left"><input type="text" name="txtCodigoArtProVer" id="txtCodigoArtProVer"  maxlength="22" size="26" readonly="readonly"/></td> 
            <td rowspan="4">
            </td>
        </tr>
        <tr> 
            <td align="right" class="tituloCampo">Descripción:</td>
            <td align="left" colspan="3"><textarea cols="66" id="txtDescripcionVer" name="txtDescripcionVer" rows="4" readonly="readonly"></textarea></td> 
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Stock Máximo:</td>
            <td align="left"><input type="text" name="txtStockMaxVer" id="txtStockMaxVer"  maxlength="22" size="26" readonly="readonly"/></td> 
            <td align="right" class="tituloCampo">Stock Minimo:</td>
            <td align="left"><input type="text" name="txtStockMinVer" id="txtStockMinVer"  maxlength="22" size="26" readonly="readonly"/></td> 
    
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Seccion:</td>
            <td colspan="" id="tdlstSeccionArtVer"></td> 
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Sub-Seccion:</td>
            <td align="left" colspan="5" id="tdlstSubSeccionVer"></td>
        </tr>
        <tr>
            <td align="right" colspan="5" >
            <hr />
                <button type="button" id="btnCancelarArtSustAlt" name="btnCancelarArtSustAlt" class="close" onclick="showHide('hide');">
                    Cerrar
                </button>
            </td>
        </tr>
    </table>
</div> <!--FIN VER ARTICULO-->

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange="xajax_objetoCodigoDinamicoCompras(\'tdCodigoArt\',this.value); byId(\'btnBuscar\').click();"');
xajax_objetoCodigoDinamicoCompras('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoArticulos(0,'','',<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>+'|'+byId('lstEstatus').value);
xajax_cargaLstTipoArticulo('','buscador');

$(function() {
	$("ul.tabs").tabs("> .pane", {initialIndex: 0});
});

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

var theHandle1 = document.getElementById("divFlotanteTitulo1");
var theRoot1   = document.getElementById("divFlotante1");
Drag.init(theHandle1, theRoot1);

var theHandle2 = document.getElementById("divFlotanteTitulo2");
var theRoot2   = document.getElementById("divFlotante2");
Drag.init(theHandle2, theRoot2);

var theHandle3 = document.getElementById("divFlotanteTitulo3");
var theRoot3 = document.getElementById("divFlotante3");
Drag.init(theHandle3, theRoot3);

</script>