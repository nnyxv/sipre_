<?php
require_once("../connections/conex.php");
set_time_limit(0);
ini_set('memory_limit', '-1');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(isset($_GET['id']))) {
	if(!(validaAcceso("iv_pedido_compra_list","insertar")) && !(validaAcceso("iv_pedido_compra_list","editar"))) {
		echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
	}
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_pedido_compra_form.php");

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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Pedido de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblImportarPedido').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		byId('tblArticuloImpuesto').style.display = 'none';
		byId('tblListaGasto').style.display = 'none';
		byId('tblCliente').style.display = 'none';
		
		if (verTabla == "tblImportarPedido") {
			if (validarCampo('txtIdProv','t','') == true
			&& validarCampo('lstTipoPedido','t','lista') == true) {
				document.forms['frmImportarPedido'].reset();
				byId('hddUrlArchivo').value = '';
				
				byId('fleUrlArchivo').className = 'inputHabilitado';
			} else {
				validarCampo('txtIdProv','t','');
				validarCampo('lstTipoPedido','t','lista');
				
				alert('Los campos señalados en rojo son requeridos');
				return false; 
			}
			
			tituloDiv1 = 'Importar Pedido';
		} else if (verTabla == "tblLista") {
			document.forms['frmBuscarProveedor'].reset();
			
			byId('trBuscarProveedor').style.display = '';
			byId('txtCriterioBuscarProveedor').className = 'inputHabilitado';
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv1 = 'Proveedores';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblArticulo") {
			if (validarCampo('txtIdProv','t','') == true
			&& validarCampo('lstTipoPedido','t','lista') == true) {
				document.forms['frmBuscarArticulo'].reset();
				document.forms['frmArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				
				byId('aInsertarClienteArt').style.display = 'none';
				
				byId('txtCodigoArt').className = 'inputInicial';
				byId('txtCantidadArt').className = 'inputHabilitado';
				byId('txtCostoArt').className = 'inputHabilitado';
				byId('lstIvaArt').className = 'inputHabilitado';
				byId('txtIdClienteArt').className = 'inputInicial';
				byId('txtNombreClienteArt').className = 'inputInicial';
				
				cerrarVentana = false;
				
				byId('divListaArticulo').innerHTML = '';
			} else {
				validarCampo('txtIdProv','t','');
				validarCampo('lstTipoPedido','t','lista');
				
				alert('Los campos señalados en rojo son requeridos');
				return false; 
			}
			
			tituloDiv1 = 'Artículos';
		} else if (verTabla == "tblArticuloImpuesto") {
			xajax_formArticuloImpuesto();
			tituloDiv1 = 'Editar Impuesto de Articulos';
		} else if (verTabla == "tblListaGasto") {
			document.forms['frmBuscarGasto'].reset();
			
			byId('btnBuscarGasto').click();
			
			tituloDiv1 = 'Gastos';
		} else if (verTabla == "tblCliente") {
			xajax_formDatosCliente(valor, xajax.getFormValues('frmDcto'));
			
			tituloDiv1 = 'Datos de Cliente';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblImportarPedido") {
			byId('fleUrlArchivo').focus();
			byId('fleUrlArchivo').select();
		} else if (verTabla == "tblLista") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblArticulo") {
			byId('txtCodigoArticulo0').focus();
			byId('txtCodigoArticulo0').select();
		} else if (verTabla == "tblListaGasto") {
			byId('txtCriterioBuscarGasto').focus();
			byId('txtCriterioBuscarGasto').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaCliente').style.display = 'none';
		
		if (verTabla == "tblListaCliente") {
			document.forms['frmBuscarCliente'].reset();
			
			byId('btnBuscarCliente').click();
			
			tituloDiv2 = 'Clientes';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaCliente") {
			byId('txtCriterioBuscarCliente').focus();
			byId('txtCriterioBuscarCliente').select();
		}
	}
	
	function buscarEnColumna(valor, buscarEnColumna) {
		var frm = document.forms['frmListaArticulo'];
		for (i = 0; i < frm.length; i++){
			if (frm.elements[i].id == "cbx") {
				indice = frm.elements[i].value;
				
				byId('trItm:' + indice).style.display = '';
				if (buscarEnColumna == 'porcentaje_grupo'
				&& byId('lstTarifaAdValorem' + indice).value != valor && valor != "") {
					byId('trItm:' + indice).style.display = 'none';
				}
			}
		}
	}
	
	function validarFrmArticuloImpuesto() {
		if (validarCampo('lstIvaCbx','t','listaExceptCero') == true) {
			xajax_asignarArticuloImpuesto(xajax.getFormValues('frmArticuloImpuesto'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('lstIvaCbx','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDatosArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		&& validarCampo('txtCostoArt','t','monto') == true
		&& validarCampo('lstIvaArt','t','listaExceptCero') == true) {
			if (byId('rbtTipoArtCliente').checked == true
			&& validarCampo('txtIdClienteArt','t','') != true) {
				validarCampo('txtIdClienteArt','t','');
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			} else
				xajax_insertarArticulo(xajax.getFormValues('frmArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','cantidad');
			validarCampo('txtCostoArt','t','monto');
			validarCampo('lstIvaArt','t','listaExceptCero');
			
			if (byId('rbtTipoArtCliente').checked == true) {
				validarCampo('txtIdClienteArt','t','');
			}
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmImportarPedido() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarDcto(xajax.getFormValues('frmImportarPedido'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('hddIdEmpleado','t','') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('lstTipoPedido','t','lista') == true
		&& validarCampo('txtNumeroReferencia','t','') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalOrden','t','monto') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtFecha','t','fecha')
			validarCampo('hddIdEmpleado','t','');
			validarCampo('txtIdProv','t','');
			validarCampo('lstTipoPedido','t','lista');
			validarCampo('txtNumeroReferencia','t','');
			validarCampo('lstMoneda','t','lista');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtTotalOrden','t','monto');
			
			error = true;
		}
		
		if (!(byId('lstTasaCambio') == undefined)) {
			if (!(validarCampo('lstTasaCambio','','listaExceptCero') == true)) {
				validarCampo('lstTasaCambio','','listaExceptCero');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (byId('hddObj').value.length > 0) {
				if (confirm('¿Seguro desea guardar el Pedido?') == true) {
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
			} else {
				alert("Debe agregar articulos al pedido");
				return false;
			}
		}
	}
	
	function validarInsertarGasto(idGasto) {
		xajax_insertarGasto(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	</script>
</head>

<body class="bodyVehiculos" onload="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Pedido de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
            	<table border="0" width="100%">
                <tr>
                	<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td width="58%">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
					</td>
                    <td align="right" class="tituloCampo" width="12%">Fecha:</td>
                    <td width="18%"><input type="text" id="txtFecha" name="txtFecha" class="inputHabilitado" size="10" style="text-align:center"/></td>
                </tr>
                <tr>
                	<td align="right" class="tituloCampo">Empleado:</td>
                    <td colspan="3">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="25"/></td>
                        </tr>
                        </table>
					</td>
                </tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="70%">
                            <fieldset><legend class="legend">Proveedor</legend>
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Razón Social:</td>
                                    <td width="44%">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'true', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarProv" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo" width="18%"><?php echo $spanProvCxP; ?>:</td>
                                    <td width="20%"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                                    <td rowspan="2"><textarea cols="55" id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="3"></textarea></td>
                                    <td align="right" class="tituloCampo" width="17%">Teléfonos:</td>
                                    <td><input type="text" id="txtTelefonoProv" name="txtTelefonoProv" readonly="readonly" size="18" style="text-align:center"/></td>
                                </tr>
                                </table>
                            </fieldset>
                            
                            	<table align="right" border="0">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                    <td>
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr align="left">
                                            <td id="tdlstMoneda">
                                                <select id="lstMoneda" name="lstMoneda">
                                                    <option value="-1">[ Seleccione ]</option>
                                                </select>
                                            </td>
                                            <td id="tdlstTasaCambio"></td>
                                            <td>
                                                <input type="text" id="txtTasaCambio" name="txtTasaCambio" readonly="readonly" size="16" style="text-align:right"/>
                                                <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" readonly="readonly"/>
                                                <input type="hidden" id="hddIncluirImpuestos" name="hddIncluirImpuestos" readonly="readonly"/>
            									<input type="hidden" id="hddModoCompra" name="hddModoCompra"/>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos del Pedido</legend>
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width="40%">Id Pedido:</td>
                                    <td width="60%"><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Pedido:</td>
                                    <td id="tdlstTipoPedido">
                                        <select id="lstTipoPedido" name="lstTipoPedido">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo">Nro. Pedido Propio:</td>
                                    <td><input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Referencia:</td>
                                    <td><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                                    <td>
                                        <label><input type="radio" id="rbtEstatusPedidoPendiente" name="rbtEstatusPedido" checked="checked" value="0"/> Pendiente</label>
                                        <br/>
                                        <label><input type="radio" id="rbtEstatusPedidoCerrado" name="rbtEstatusPedido" value="1"/> Cerrar Pedido</label>
									</td>
                                </tr>
                                </table>
                            </fieldset>
							</td>
						</tr>
                        </table>
					</td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td align="left">
            	<a class="modalImg" id="aAgregarArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticulo');">
                    <button type="button" title="Agregar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                </a>
                <button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                <a class="modalImg" id="aImpuestoArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticuloImpuesto');">
                    <button type="button" title="Impuesto Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/text_signature.png"/></td><td>&nbsp;</td><td>Impuesto</td></tr></table></button>
                </a>
                <a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarPedido');">
                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                </a>
			</td>
        </tr>
        <tr>
            <td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" class="texto_9px" width="100%">
                <tr align="center" class="tituloColumna">
                	<td rowspan="2"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"/></td>
                	<td rowspan="2" width="4%">Nro.</td>
                	<td rowspan="2"></td>
                    <td rowspan="2" width="14%">Código</td>
                    <td rowspan="2" width="40%">Descripción</td>
                    <td rowspan="2" width="6%">Ped.</td>
                    <td rowspan="2" width="6%">Recib.</td>
                    <td rowspan="2" width="6%">Pend.</td>
                    <td rowspan="2" width="8%">Costo Unit.</td>
                    <td rowspan="2" width="4%">% Impuesto</td>
                    <td width="4%">% ADV</td>
                    <td rowspan="2" width="8%">Total</td>
                </tr>
                <tr align="center" class="tituloColumna">
                    <td id="tdlstArancelGrupoBuscar"></td>
                </tr>
                <tr id="trItmPie"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td align="right">
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetGastos"><legend class="legend">Gastos</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarGasto" align="left">
                        	<td colspan="7">
                                <a class="modalImg" id="aAgregarGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaGasto');">
                                    <button type="button" title="Agregar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarGasto" name="btnQuitarGasto" onclick="xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));" title="Quitar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trGastoItem" align="left" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmGasto" onclick="selecAllChecks(this.checked,this.id,'frmTotalDcto');"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gasto Manual por Item:</td>
                            <td colspan="5">
                            <div style="float:left">
                            	<select id="lstGastoItem" name="lstGastoItem" onchange="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
                                	<option value="-1">[ Seleccione ]</option>
                                	<option value="0">No</option>
                                	<option value="1">Si</option>
                                </select>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="No: Realiza el prorrateo automático entre los items agregados &#10;Si: Permitirá ingresar manualmente el gasto correspondiente a cada item agregado y los totales de los gastos deberán coincidir"/>
                            </div>
                            </td>
                        </tr>
                        <tr id="trItmPieGasto" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="4">Total Gastos:</td>
                            <td><input type="text" id="txtTotalGasto" name="txtTotalGasto" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                        	<td></td>
                        	<td width="26%"></td>
                            <td width="14%"></td>
                            <td width="8%"></td>
                            <td width="24%"></td>
                            <td width="14%"></td>
                            <td width="14%"></td>
						</tr>
                        </table>
					</fieldset>
                    </td>
                    <td valign="top" width="50%">
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="36%">Subtotal:</td>
                            <td style="border-top:1px solid;" width="24%"></td>
                            <td style="border-top:1px solid;" width="13%"></td>
                            <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap">
                            	<input type="radio" id="rbtInicialPorc" name="rbtInicial" onclick="byId('txtDescuento').readOnly = false; byId('txtSubTotalDescuento').readOnly = true;" style="display:none" value="1">
                                
                            	<input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td>
                            	<input type="radio" id="rbtInicialMonto" name="rbtInicial" checked="checked" onclick="byId('txtDescuento').readOnly = true; byId('txtSubTotalDescuento').readOnly = false;" style="display:none" value="2">
                                
                            	<input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/>
							</td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoPedido" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border-top:1px solid;"></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Exento:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExentoMoneda"></td>
                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Exonerado:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExoneradoMoneda"></td>
                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_pedido_compra_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarPedido" name="frmImportarPedido" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarPedido" width="960">
    <tr align="left">
    	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td width="85%">
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript:submit();"/>
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
                        <td colspan="6">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
                        <td width="20%">Código</td>
                        <td width="16%">Código Arancelario</td>
                        <td width="16%">% Arancelario</td>
                        <td width="16%">Ped.</td>
                        <td width="16%">Costo Unit.</td>
                        <td width="16%">Id Cliente</td>
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
        	<button type="submit" id="btnGuardarImportarPedido" name="btnGuardarImportarPedido" onclick="validarFrmImportarPedido();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarPedido" name="btnCancelarImportarPedido" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblLista" width="760">
    <tr id="trBuscarProveedor">
    	<td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
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
	
	<table border="0" id="tblArticulo" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
        	<table align="right">
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
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); $('btnBuscarArticulo').click();">Limpiar</button>
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
    	<td>
        <form id="frmArticulo" name="frmArticulo" onsubmit="return false;" style="margin:0">
        	<input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
            <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" readonly="readonly"/>
            <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
        <fieldset>
        	<table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="30%"></td>
                <td width="38%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/></td>
                <td rowspan="3" valign="top"><textarea id="txtDescripcionArt" name="txtDescripcionArt" class="inputSinFondo" rows="3" readonly="readonly" style="text-align:left"></textarea></td>
                <td align="right" class="tituloCampo">Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" class="inputSinFondo" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
                <td align="right" class="tituloCampo">Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" class="inputSinFondo" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo Artículo:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
                <td align="right" class="tituloCampo">Unid. Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right;"/></td>
            </tr>
            </table>
        </fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
                <td width="30%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" size="12" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                    </tr>
					</table>
				</td>
			    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                <td><input type="text" id="txtCostoArt" name="txtCostoArt" maxlength="16" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaArt">
                	<select id="lstIvaArt" name="lstIvaArt">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Tipo:</td>
                <td>
                	<label><input type="radio" id="rbtTipoArtReposicion" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('aInsertarClienteArt').style.display = 'none';" value="0" checked="checked"/> Reposicion</label>
                    &nbsp;&nbsp;
                    <label><input type="radio" id="rbtTipoArtCliente" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('aInsertarClienteArt').style.display = '';" value="1"/> Cliente</label>
				</td>
                <td align="right" class="tituloCampo">Cliente:</td>
                <td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdClienteArt" name="txtIdClienteArt" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aInsertarClienteArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaCliente');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNombreClienteArt" name="txtNombreClienteArt" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="6"><hr>
                    <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
                    <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
		</td>
	</tr>
    </table>

<form id="frmArticuloImpuesto" name="frmArticuloImpuesto" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblArticuloImpuesto" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaCbx" width="70%">
                	<select id="lstIvaCbx" name="lstIvaCbx">
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
                        <td>Para seleccionar multiples impuestos se debe presionar la tecla Ctrl</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticuloImpuesto" name="btnGuardarArticuloImpuesto" onclick="validarFrmArticuloImpuesto();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloImpuesto" name="btnCancelarArticuloImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
	
    <table border="0" id="tblListaGasto" width="760">
    <tr>
        <td>
        <form id="frmBuscarGasto" name="frmBuscarGasto" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarGasto" name="txtCriterioBuscarGasto" class="inputHabilitado" onkeyup="byId('btnBuscarGasto').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarGasto" name="btnBuscarGasto" onclick="xajax_buscarGasto(xajax.getFormValues('frmBuscarGasto'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarGasto').value = ''; byId('btnBuscarGasto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaGasto" name="frmListaGasto" style="margin:0" onsubmit="return false;">
            <div id="divListaGasto" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaGasto" name="btnCancelarListaGasto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblCliente" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                <td width="55%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" width="15%"><?php echo $spanProvCxP; ?>:</td>
                <td width="15%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                <td rowspan="2"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea></td>
                <td align="right" class="tituloCampo">Teléfono:</td>
                <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Días Crédito:</td>
                <td><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
			<button type="button" id="btnCancelarCliente" name="btnCancelarCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
  	
    <table border="0" id="tblListaCliente" width="760">
    <tr>
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" class="inputHabilitado" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaCliente" style="width:100%;"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaCliente" name="btnCancelarListaCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
window.onload = function(){
	jQuery(function($){
		$("#txtFecha").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
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

xajax_cargaLstArancelGrupoBuscar();
xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>