<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_bloqueo_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_bloqueo_venta_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Bloqueo de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblBloqueoVenta').style.display = 'none';
		byId('tblDesbloqueoVenta').style.display = 'none';
		
		if (verTabla == "tblBloqueoVenta") {
			xajax_formBloqueoVenta(xajax.getFormValues('frmBloqueoVenta'));
			
			tituloDiv1 = 'Bloqueo de Venta';
		} else if (verTabla == "tblDesbloqueoVenta") {
			byId('txtCriterioDesbloqueoVenta').className = 'inputHabilitado';
			
			xajax_formDesbloqueoVenta(valor);
			
			tituloDiv1 = 'Detalle del Bloqueo de Venta';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblDesbloqueoVenta") {
			byId('txtCriterioDesbloqueoVenta').focus();
			byId('txtCriterioDesbloqueoVenta').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		byId('tblArticuloBloque').style.display = 'none';
		byId('tblDesbloqueoArticulo').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblArticulo") {
			byId('divListaArticulo').innerHTML = '';
			
			tituloDiv2 = 'Lista de Artículos';
		} else if (verTabla == "tblArticuloBloque") {
			document.forms['frmBuscarArticuloBloque'].reset();
			
			byId('lstVerClasificacion').className = 'inputHabilitado';
			
			xajax_cargaLstTipoArticulo();
			byId('btnBuscarArticuloBloque').click();
			
			tituloDiv2 = 'Lista de Artículos';
		} else if (verTabla == "tblDesbloqueoArticulo") {
			document.forms['frmDesbloqueoArticulo'].reset();
			
			byId('txtCantidadDesbloquear').className = 'inputHabilitado';
			
			xajax_formDesbloqueoArticulo(valor);
			
			tituloDiv2 = 'Desbloquear Artículo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblArticulo") {
			if (byId('txtCodigoArticulo0') != undefined) {
				byId('txtCodigoArticulo0').focus();
				byId('txtCodigoArticulo0').select();
			}
		} else if (verTabla == "tblDesbloqueoArticulo") {
			byId('txtCantidadDesbloquear').focus();
			byId('txtCantidadDesbloquear').select();
		}
	}
	
	function validarFrmArticuloBloque() {
		xajax_insertarArticuloBloque(xajax.getFormValues('frmListaArticuloBloque'), xajax.getFormValues('frmBloqueoVenta'));
	}
	
	function validarFrmBloqueo() {
		xajax_guardarBloqueo(xajax.getFormValues('frmBloqueoVenta'), xajax.getFormValues('frmListaRegistroCompra'));
	}
	
	function validarFrmDesbloqueoArticulo(){
		if (validarCampo('txtCantidadDesbloquear','t','cantidad') == true) {
			xajax_guardarDesbloqueoArticulo(xajax.getFormValues('frmDesbloqueoArticulo'), xajax.getFormValues('frmDesbloqueoVenta'), xajax.getFormValues('frmListaRegistroCompra'));
		} else {
			validarCampo('txtCantidadDesbloquear','t','cantidad');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarInsertarArticulo(idArticuloAlmacen) {
		xajax_insertarArticulo(idArticuloAlmacen, xajax.getFormValues('frmBloqueoVenta'), 'false');
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Bloqueo de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblBloqueoVenta');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
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
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ver:</td>
                	<td colspan="3">
                    	<label><input type="checkbox" id="cbxVerConItemsBloq" name="cbxVerConItemsBloq" checked="checked" value="1"/> Con Items Bloq.</label>
                        <label><input type="checkbox" id="cbxVerSinItemsBloq" name="cbxVerSinItemsBloq" value="2"/> Sin Items Bloq.</label>
					</td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Clave Mov.:</td>
                    <td id="tdlstClaveMovimiento"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaRegistroCompra" name="frmListaRegistroCompra" style="margin:0">
            	<div id="divListaRegistroCompra" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
				</div>
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
                            <td><img src="../img/iconos/ico_gris.gif"/></td><td>Factura (Con Devolución)</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_morado.gif"/></td><td>Factura</td>
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
                            <td><img src="../img/iconos/application_view_columns.png"/></td><td>Ver Detalle</td>
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

<form id="frmBloqueoVenta" name="frmBloqueoVenta" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblBloqueoVenta" width="960">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td colspan="2">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td>
        <fieldset><legend class="legend">Articulos Bloqueados</legend>
        	<table width="100%">
            <tr align="left">
            	<td>
                    <a class="modalImg" id="aNuevoBloque" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblArticuloBloque');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_registrar_compra.gif"/></td><td>&nbsp;</td><td>Agregar por Bloque</td></tr></table></button>
                    </a>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblArticulo');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                    <button id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmBloqueoVenta'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                </td>
            </tr>
            <tr>
            	<td>
                	<div style="max-height:300px; overflow:auto; width:100%;">
                        <table border="0" class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                            <td width="4%"></td>
                            <td width="14%">Código.</td>
                            <td width="52%">Descripción</td>
                            <td width="14%">Ubicación</td>
                            <td width="8%">Lote</td>
                            <td width="8%">Cantidad</td>
                        </tr>
                        <tr id="trItmPie"></tr>
                        </table>
					</div>
                </td>
            </tr>
            </table>
        </fieldset>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<button type="submit" id="btnGuardarBloqueo" name="btnGuardarBloqueo" onclick="validarFrmBloqueo();">Guardar</button>
            <button type="button" id="btnCancelarBloqueo" name="btnCancelarBloqueo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
    
<form id="frmDesbloqueoVenta" name="frmDesbloqueoVenta" onsubmit="return false;" style="margin:0">
	<input type="hidden" id="hddIdBloqueoVenta" name="hddIdBloqueoVenta"/>
    <table border="0" id="tblDesbloqueoVenta" width="960">
    <tr>
        <td>
            <table align="right" border="0">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArtDesbloqueo"></td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioDesbloqueoVenta" name="txtCriterioDesbloqueoVenta"/></td>
                <td>
                    <button type="submit" id="btnBuscarDesbloqueoVenta" onclick="xajax_buscarArticuloBloqueo(xajax.getFormValues('frmDesbloqueoVenta'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmDesbloqueoVenta'].reset(); byId('btnBuscarDesbloqueoVenta').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaArticuloBloqueo" style="width:100%"></div></td>
    </tr>
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table>
                    <tr>
                        <td><img src="../img/iconos/lock_open.png"/></td><td>Desbloqueado</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/lock.png"/></td><td>Bloqueado</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarDesbloqueoVenta" name="btnCancelarDesbloqueoVenta" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
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
    
    <table border="0" id="tblArticulo" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
        	<table align="right" border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Buscar por:</td>
                <td>
                	<select id="lstBuscarArticulo" name="lstBuscarArticulo" class="inputHabilitado" style="width:150px">
                    	<option value="1">Marca</option>
                        <option value="2">Tipo Artículo</option>
                        <option value="3">Sección</option>
                        <option value="4">Sub-Sección</option>
                        <option selected="selected" value="5">Descripción</option>
                        <option value="6">Cód. Barra</option>
                        <option value="7">Cód. Artículo Prov.</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticulo" name="txtCriterioBuscarArticulo" class="inputHabilitado"/></td>
                <td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArt"></td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmBloqueoVenta'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
		</form>
		</td>
    </tr>
    <tr>
    	<td><div id="divListaArticulo"></div></td>
    </tr>
    <tr>
        <td align="right" colspan="6"><hr>
            <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblArticuloBloque" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticuloBloque" name="frmBuscarArticuloBloque" onsubmit="return false;" style="margin:0">
        	<table align="right" border="0">
            <tr align="left">
	            <td align="right" class="tituloCampo" width="120">Tipo Artículo:</td>
                <td id="tdlstTipoArticulo"></td>
                <td align="right" class="tituloCampo" width="120">Clasificación:</td>
                <td>
                    <select id="lstVerClasificacion" name="lstVerClasificacion" onchange="byId('btnBuscarArticuloBloque').click();">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                    </select>
                </td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarArticuloBloque" name="btnBuscarArticuloBloque" onclick="xajax_buscarArticuloBloque(xajax.getFormValues('frmBuscarArticuloBloque'), xajax.getFormValues('frmBloqueoVenta'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticuloBloque'].reset(); byId('btnBuscarArticuloBloque').click();">Limpiar</button>
				</td>
            </tr>
            </table>
		</form>
        </td>
    </tr>
    <tr>
    	<td>
        	<form id="frmListaArticuloBloque" name="frmListaArticuloBloque" style="margin:0">
            	<div id="divListaArticuloBloque" style="max-height:300px; overflow:auto; width:100%">
                	<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
                </div>
            </form>
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
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticuloBloque" name="btnGuardarArticuloBloque" onclick="validarFrmArticuloBloque();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloBloque" name="btnCancelarArticuloBloque" class="close">Cerrar</button>
		</td>
    </tr>
    </table>
    
<form id="frmDesbloqueoArticulo" name="frmDesbloqueoArticulo" onsubmit="return false;" style="margin:0">
	<input type="hidden" id="hddIdBloqueoVentaDetalle" name="hddIdBloqueoVentaDetalle"/>
    <table border="0" id="tblDesbloqueoArticulo" width="960">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="14%">Código:</td>
                <td valign="top" width="26%"><input type="text" id="txtCodigoArt" name="txtCodigoArt" size="25" readonly="readonly"/></td>
                <td align="right" class="tituloCampo" width="14%">Descripcion:</td>
                <td rowspan="3" valign="top" width="46%"><textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="50" rows="3" readonly="readonly"></textarea></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Cant. Bloqueada:</td>
            	<td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" readonly="readonly" size="10" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                    </tr>
					</table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Cant. Desbloquear:</td>
            	<td><input type="text" id="txtCantidadDesbloquear" name="txtCantidadDesbloquear" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumeros(event)" size="10" style="text-align:right"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarDesbloqueoArticulo" name="btnGuardarDesbloqueoArticulo" onclick="validarFrmDesbloqueoArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarDesbloqueoArticulo" name="btnCancelarDesbloqueoArticulo" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
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
xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '1');
xajax_listaRegistroCompra(0,'id_bloqueo_venta','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||' + byId('cbxVerConItemsBloq').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>