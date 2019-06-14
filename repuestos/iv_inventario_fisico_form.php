<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_inventario_fisico_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_inventario_fisico_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Inventario Físico</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblPermiso').style.display = 'none';
		byId('tblImprimir').style.display = 'none';
		byId('tblImprimirInvComparativo').style.display = 'none';
		byId('tblListaCodigoArticulo').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv1 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblImprimir") {
			document.forms['frmImprimir'].reset();
			
			xajax_formImprimir(xajax.getFormValues('frmInventario'));
			
			tituloDiv1 = 'Imprimir Inventario Físico';
		} else if (verTabla == "tblImprimirInvComparativo") {
			document.forms['frmImprimirInvComparativo'].reset();
			
			xajax_formImprimirInvComparativo(xajax.getFormValues('frmInventario'));
			
			tituloDiv1 = 'Imprimir Inventario Comparativo';
		} else if (verTabla == "tblListaCodigoArticulo") {
			document.forms['frmBuscarCodigoArticulo'].reset();
			document.forms['frmListaCodigoArticulo'].reset();
			
			byId('divListaCodigoArticulo').innerHTML = '';
			
			tituloDiv1 = 'Buscar Artículo por Código';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblListaCodigoArticulo") {
			byId('txtCodigoArticulo0').focus();
			byId('txtCodigoArticulo0').select();
		}
	}
	
	function validarFrmArticuloManual() {
		if (validarCampo('txtCantidadArt','t','numPositivo') == true) {
			xajax_insertarArticuloManual(xajax.getFormValues('frmInventario'), xajax.getFormValues('frmBuscarNumeroPosicion'), xajax.getFormValues('frmArticuloManual'), xajax.getFormValues('frmListaArticulosInventario'));
		} else {
			validarCampo('txtCantidadArt','t','numPositivo');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmBuscarNumeroPosicion() {
		if (validarCampo('txtNumero','t','') == true) {
			xajax_asignarArticulo(xajax.getFormValues('frmInventario'), xajax.getFormValues('frmBuscarNumeroPosicion'));
		} else {
			validarCampo('txtNumero','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function bloquearForm() {
		byId('aAjusteInventario').style.display = 'none';
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaRepuestos">Inventario Físico</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                    <td>
                    <a class="modalImg" id="aImprimir" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImprimir');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_print.png" title="Imprimir"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
			</td>
		</tr>
        <tr>
        	<td>
            <form id="frmInventario" name="frmInventario" onsubmit="return false;" style="margin:0">
            	<input type="hidden" id="hddNumeroConteo" name="hddNumeroConteo"/>
            	<table border="0" width="100%">
                <tr>
                	<td width="9%"></td>
                    <td width="13%"></td>
                    <td width="11%"></td>
                    <td width="11%"></td>
                    <td width="34%"></td>
                    <td width="9%"></td>
                    <td width="13%"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td colspan="4">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
					</td>
                    <td align="right" class="tituloCampo">Nro. Inv. Fisico:</td>
                    <td><input type="text" id="txtIdInventarioFisico" name="txtIdInventarioFisico" readonly="readonly" size="12" style="text-align:center"/></td>
				</tr>
				<tr align="left">
                	<td align="right" class="tituloCampo">Empleado:</td>
                    <td colspan="4">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                	<td align="right" class="tituloCampo">Fecha:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="12" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Proceso:</td>
                	<td>
                    	<input type="hidden" id="hddTipoConteo" name="hddTipoConteo" readonly="readonly"/>
	                    <input type="text" id="txtTipoConteo" name="txtTipoConteo" readonly="readonly"/>
					</td>
                    <td align="right" class="tituloCampo">Cantidad Conteos:</td>
                    <td colspan="2"><input type="text" id="txtCantidadConteos" name="txtCantidadConteos" readonly="readonly" size="10" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo">Hora:</td>
                    <td><input type="text" id="txtHora" name="txtHora" readonly="readonly" size="12" style="text-align:center"/></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Articulo:</td>
                    <td><input type="text" id="txtFiltroArticulos" name="txtFiltroArticulos" readonly="readonly"/></td>
                    <td align="right" class="tituloCampo">Orden:</td>
                    <td colspan="2"><input type="text" id="txtOrdenArticulos" name="txtOrdenArticulos" readonly="readonly"/></td>
                </tr>
                <tr align="left">
                    <td align="right" colspan="7">
                        <table>
                        <tr>
                        	<td>
                                <button type="button" id="btnNuevoConteo" onclick="if(confirm('¿Seguro desea realizar un Nuevo Conteo?') == true){xajax_nuevoConteo(xajax.getFormValues('frmInventario'), xajax.getFormValues('frmListaArticulosInventario'));}"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_conteo.gif"/></td><td>&nbsp;</td><td>Nuevo Conteo</td></tr></table></button>
							</td>
                            <td>
                            <a class="modalImg" id="aVerComparativo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImprimirInvComparativo');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_view.png" title="Ver Inventario Comparativo"/></td><td>&nbsp;</td><td>Ver Inventario Comparativo</td></tr></table></button>
                            </a>
                            </td>
                            <td>
                            <a class="modalImg" id="aAjusteInventario" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'iv_inventario_fisico_form');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/lock.png" title="Ajustar Inventario"/></td><td>&nbsp;</td><td>Ajustar Inventario</td></tr></table></button>
                            </a>
							</td>
						</tr>
                        </table>
                    </td>
				</tr>
                </table>
			</form>
            </td>
        </tr>
        <tr id="trFormConteo" style="display:none">
        	<td>
            <fieldset><legend class="legend">Buscar Artículo</legend>
            	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr align="left">
                	<td valign="top" width="36%">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td>
                            <form id="frmBuscarNumeroPosicion" name="frmBuscarNumeroPosicion" onsubmit="return false;" style="margin:0">
                                <table border="0">
                                <tr align="left">
                                	<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span><span id="spanTituloBuscar">Nro.</span>:</td>
                                    <td><input autocomplete="off" type="text" id="txtNumero" name="txtNumero" size="25"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarProv" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaCodigoArticulo');">
                                        <button type="button" title="Buscar por Código"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><button type="submit" id="btnBuscarNumeroPosicion" onclick="validarFrmBuscarNumeroPosicion();">Buscar</button></td>
                                </tr>
                                </table>
                            </form>
                            </td>
                        </tr>
                        <tr id="trCantidad" style="display:none">
                        	<td>
                            <form id="frmArticuloManual" name="frmArticuloManual" onsubmit="return false;" style="margin:0">
                                <input type="hidden" id="hddIdInvFisicoDet" name="hddIdInvFisicoDet" readonly="readonly" size="10"/>
                                <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly" size="10"/>
                            	<input type="hidden" id="txtCodigoArticulo" name="txtCodigoArticulo" readonly="readonly" size="10"/>
                            	<table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120">Cantidad:</td>
                                    <td>
                                    	<input autocomplete="off" type="text" id="txtCantidadArt" name="txtCantidadArt" size="25" onkeyup="
                                        if (event.target)
                                            idObj = event.target.id
                                        else if (event.srcElement)
                                            idObj = event.srcElement.id;
                                        
                                        teclaCodigo = (document.all) ? event.keyCode : event.which;
                                        
                                        if (teclaCodigo == 27) {
                                            byId('btnCancelar').click();
                                        }"/>
									</td>
                                </tr>
                                <tr>
                                    <td align="right" colspan="2">
                                        <button type="submit" id="btnAceptar" onclick="validarFrmArticuloManual();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/accept.png"/></td><td>&nbsp;</td><td>Aceptar</td></tr></table></button>
                                        <button type="button" id="btnCancelar" name="btnCancelar" onclick="
                                            byId('tblDatosArticulo').style.display = 'none';
                                            byId('trCantidad').style.display = 'none';
                                            byId('txtNumero').readOnly = false;
                                            byId('txtNumero').focus();
                                            document.forms['frmBuscarNumeroPosicion'].reset();
                                            
                                            xajax_listaArticulosInventario(
                                            	byId('pageNum').value,
                                                byId('campOrd').value,
                                                byId('tpOrd').value,
												byId('valBusq').value);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
                            </form>
                            </td>
						</tr>
                        </table>
					</td>
                    <td width="64%">
                    	<table border="0" id="tblDatosArticulo" style="display:none" width="100%">
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Código:</td>
                            <td colspan="3"><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Descripción:</td>
                            <td colspan="3"><textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="70" rows="3" readonly="readonly"></textarea></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo" width="14%">Marca:</td>
                        	<td width="18%"><input type="text" id="txtMarcaArt" name="txtMarcaArt" readonly="readonly" size="25"/></td>
                        	<td align="right" class="tituloCampo" width="14%">Tipo de Pieza:</td>
                            <td width="54%"><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
						</tr>
                        </table>
                        
                        <div id="divMsj"></div>
                    </td>
				</tr>
                </table>
			</fieldset>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArticulosInventario" name="frmListaArticulosInventario" style="margin:0">
            	<div id="divListaArticulosInventario" style="width:100%"></div>
			</form>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="bloquearForm(); window.open('iv_inventario_fisico_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
<form id="frmPermiso" name="frmPermiso" style="margin:0px" onsubmit="return false;">
    <table border="0" id="tblPermiso" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
            <button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
<form id="frmImprimir" name="frmImprimir" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblImprimir" width="760">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="20%">Incluir Columna:</td>
                <td width="45%">
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    	<td width="25%"><label><input type="checkbox" id="cbxImpKardex" name="cbxImp[]" value="K"/> Kardex</label></td>
                    	<td width="25%"><label><input type="checkbox" id="cbxImpConteo1" name="cbxImp[]" value="1"/> #1</label></td>
                    	<td width="25%"><label><input type="checkbox" id="cbxImpConteo2" name="cbxImp[]" value="2"/> #2</label></td>
                    	<td width="25%"><label id="legendCbxImpConteo3"><input type="checkbox" id="cbxImpConteo3" name="cbxImp[]" value="3"/> #3</label></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" width="15%">Con Datos:</td>
                <td width="20%">
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    	<td width="50%"><label><input type="radio" id="rdbImpSi" name="cbxImp[]" value="SI"/> Si</label></td>
                    	<td width="50%"><label><input type="radio" id="rdbImpNo" name="cbxImp[]" checked="checked" value="NO"/> No</label></td>
                    </tr>
                    </table>
                </td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Inv. Físico Comparativo:</td>
            	<td><label><input type="checkbox" id="cbxImpInvFisico" name="cbxImp[]" value="4"/> Si</label></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Listado Descuadres:</td>
            	<td>
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    	<td width="50%"><label><input type="checkbox" id="cbxImpFaltantes" name="cbxImp[]" value="5"/> Faltantes</label></td>
                    	<td width="50%"><label><input type="checkbox" id="cbxImpSobrantes" name="cbxImp[]" value="6"/> Sobrantes</label></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Imprimir Ajuste:</td>
            	<td>
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    	<td width="50%"><label><input type="checkbox" id="cbxImpSalida" name="cbxImp[]" value="7"/> Salida</label></td>
                    	<td width="50%"><label><input type="checkbox" id="cbxImpEntrada" name="cbxImp[]" value="8"/> Entrada</label></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
		</td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" onclick="xajax_imprimirInventario(xajax.getFormValues('frmImprimir'), xajax.getFormValues('frmInventario'));">Aceptar</button>
            <button type="button" id="btnCancelarImprimir" name="btnCancelarImprimir" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblImprimirInvComparativo" width="560">
    <tr>
    	<td>
        <form id="frmImprimirInvComparativo" name="frmImprimirInvComparativo" onsubmit="return false;" style="margin:0">
        	<table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%">Comparar Con Conteo:</td>
            	<td width="70%">
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
	                	<td width="30%"><label><input type="radio" id="rbtImpConteo1" name="rbtImp[]" value="1"/> #1</label></td>
                        <td width="30%"><label><input type="radio" id="rbtImpConteo2" name="rbtImp[]" value="2"/> #2</label></td>
                        <td width="40%"><label id="tdRbtImpConteo3"><input type="radio" id="rbtImpConteo3" name="rbtImp[]" value="3"/> #3</label></td>
					</tr>
                    </table>
                </td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Incluir Columna:</td>
            	<td>
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="30%"><label><input type="checkbox" id="cbxImpKardexInvComp" name="cbxImpInvComp[]" value="K" checked="checked"/> Kardex</label></td>
                        <td width="70%"><label><input type="checkbox" id="cbxImpConteoInvComp" name="cbxImpInvComp[]" value="C"/> Conteo</label></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Articulos:</td>
                <td>
                	<table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="30%"><label><input type="checkbox" id="cbxImpIgualdades" name="cbxImpInvComp[]" value="I" checked="checked"/> Iguales</label></td>
                        <td width="30%"><label><input type="checkbox" id="cbxImpFaltantes" name="cbxImpInvComp[]" value="F" checked="checked"/> Faltantes</label></td>
                        <td width="40%"><label><input type="checkbox" id="cbxImpSobrantes" name="cbxImpInvComp[]" value="S" checked="checked"/> Sobrantes</label></td>
                    </tr>
                    </table>
                </td>
			</tr>
            </table>
		</form>
		</td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" onclick="xajax_imprimirInventarioComparativo(xajax.getFormValues('frmImprimirInvComparativo'), xajax.getFormValues('frmInventario'));">Aceptar</button>
            <button type="button" id="btnCancelarImprimirInvComparativo" name="btnCancelarImprimirInvComparativo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
    <div id="tblListaCodigoArticulo" style="max-height:520px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarCodigoArticulo" name="frmBuscarCodigoArticulo" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarCodigoArticulo(xajax.getFormValues('frmBuscarCodigoArticulo'), xajax.getFormValues('frmInventario'));">Buscar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaCodigoArticulo" name="frmListaCodigoArticulo" onsubmit="return false;" style="margin:0">
                <div id="divListaCodigoArticulo" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2"><hr>
                <button type="button" id="btnCancelarListaCodigoArticulo" name="btnCancelarListaCodigoArticulo" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
    </div>
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

xajax_cargarInventarioFisico('<?php echo $_GET['id']; ?>');
bloquearForm();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>