<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if((!validaAcceso("iv_presupuesto_venta_list","insertar") && !$_GET['id'])
|| (!validaAcceso("iv_presupuesto_venta_list","editar") && $_GET['id'] > 0)) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_presupuesto_venta_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Presupuesto de Venta</title>
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
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblLista').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('trBuscarCliente').style.display = '';
				byId('btnGuardarLista').style.display = 'none';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "960";
			} else if (valor == "Gasto") {
				document.forms['frmLista'].reset();
				
				byId('trBuscarCliente').style.display = 'none';
				byId('btnGuardarLista').style.display = '';
				
				xajax_formGastosArticulo(xajax.getFormValues('frmListaArticulo'), valor2);
				
				tituloDiv1 = 'Gastos del Artículo';
				byId(verTabla).width = "960";
			}
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblArticulo") {
			if (validarCampo('txtIdEmpresa','t','') == true
			&& validarCampo('txtIdCliente','t','') == true) {
				document.forms['frmBuscarArticulo'].reset();
				document.forms['frmDatosArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				byId('hddIdIvaArt').value = '';
				
				byId('txtCodigoArt').className = 'inputInicial';   
				byId('txtCantidadArt').className = 'inputHabilitado';
				byId('txtPrecioArt').className = 'inputHabilitado';
				
				byId('tdMsjArticulo').style.display = 'none';
				
				cerrarVentana = false;
				
				byId('divListaArticulo').innerHTML = '';
			} else {
				validarCampo('txtIdEmpresa','t','');
				validarCampo('txtIdCliente','t','');
				
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
			
			tituloDiv1 = 'Artículos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "Gasto") {
				if (byId('txtMontoGasto1') != undefined) {
					byId('txtMontoGasto1').focus();
					byId('txtMontoGasto1').select();
				}
			}
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblArticulo") {
			if (byId('txtCodigoArticulo0') != undefined) {
				byId('txtCodigoArticulo0').focus();
				byId('txtCodigoArticulo0').select();
			}
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblArticuloSustituto').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblArticuloSustituto") {
			xajax_listaArticuloSustituto(0,'','', valor + '|' + valor2);
			xajax_listaArticuloAlterno(0,'','', valor + '|' + valor2);
			
			tituloDiv2 = 'Articulos Sustitutos y Alternos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		}
	}
	
	function validarFrmDatosArticulo() {
		error = false;
		if (!(validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		&& validarCampo('lstPrecioArt','t','lista') == true
		&& validarCampo('txtPrecioArt','t','monto') == true)) {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','cantidad');
			validarCampo('lstPrecioArt','t','lista');
			validarCampo('txtPrecioArt','t','monto');
			
			error = true;
		}
		
		if (byId('lstCasillaArt') != undefined && byId('tdUbicacion').style.visibility != 'hidden') {
			if (!(validarCampo('lstCasillaArt','t','lista') == true)) {
				validarCampo('lstCasillaArt','t','lista');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtNombreEmpleado','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFechaVencimientoPresupuesto','t','fecha') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalOrden','t','monto') == true) {
			if (byId('hddObj').value.length > 0) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			} else {
				alert("Debe agregar articulos al presupuesto");
				return false;
			}
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtNombreEmpleado','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtFechaVencimientoPresupuesto','t','fecha');
			validarCampo('lstMoneda','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtTotalOrden','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'), xajax.getFormValues('frmDatosArticulo'));
		} else {
			validarCampo('txtContrasena','t','');
			
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
        	<td class="tituloPaginaRepuestos">Presupuesto de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" style="margin:0">
            	<table border="0" width="100%">
                <tr align="left">
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
                    <td width="18%"><input type="text" id="txtFechaPresupuesto" name="txtFechaPresupuesto" onchange="xajax_asignarFechaVencimiento(xajax.getFormValues('frmDcto'));" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Empleado:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="70%">
                            <fieldset><legend class="legend">Cliente</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                    <td width="46%">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarCliente" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Cliente');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                        </tr>
                                        <tr align="center">
                                        	<td id="tdMsjCliente" colspan="3"></td>
                                        </tr>
                                        </table>
                                        <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                                    </td>
                                    <td align="right" class="tituloCampo" width="16%"><?php echo $spanClienteCxC; ?>:</td>
                                    <td width="22%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" rowspan="3">Dirección:</td>
                                    <td rowspan="3"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                                    <td align="right" class="tituloCampo">Teléfono:</td>
                                    <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Otro Teléfono:</td>
                                    <td><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Días Crédito:</td>
                                    <td><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                </tr>
                                </table>
                            </fieldset>
                            
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                                    <td width="16%">
                                        <select id="lstTipoClave" name="lstTipoClave">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="1">1.- COMPRA</option>
                                            <option value="2">2.- ENTRADA</option>
                                            <option value="3">3.- VENTA</option>
                                            <option value="4">4.- SALIDA</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave:</td>
                                    <td width="28%">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td id="tdlstClaveMovimiento"></td>
                                            <td>&nbsp;</td>
                                            <td>
                                            <a class="modalImg" id="aDesbloquearClaveMovimiento" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_pedido_venta_clave_mov');">
                                                <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo" width="12%">Tipo de Pago:</td>
                                    <td width="20%">
                                        <label><input type="radio" id="rbtTipoPagoCredito" name="rbtTipoPago" value="0"/> Crédito</label>
                                        <label><input type="radio" id="rbtTipoPagoContado" name="rbtTipoPago" value="1" checked="checked"/> Contado</label>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos del Presupuesto</legend>
                                <input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto"/>
                                <input type="hidden" id="hddEstatusPresupuesto" name="hddEstatusPresupuesto"/>
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width="40%">Nro. Presupuesto:</td>
                                    <td width="60%"><input type="text" id="txtNumeroPresupuesto" name="txtNumeroPresupuesto" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo" width="15%">Fecha Vencimiento:</td>
                                    <td><input type="text" id="txtFechaVencimientoPresupuesto" name="txtFechaVencimientoPresupuesto" readonly="readonly" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                    <td id="tdlstMoneda"></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo">Nro. Siniestro:</td>
                                    <td><input type="text" id="txtNumeroSiniestro" name="txtNumeroSiniestro" onblur="xajax_verificarSiniestro(this.value);" size="20"/></td>
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
			</td>
        </tr>
        <tr>
            <td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItmArticulo" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"/></td>
                	<td width="4%">Nro.</td>
                    <td width="14%">Código</td>
                    <td width="40%">Descripción</td>
                    <td width="6%">Cantidad</td>
                    <td width="6%">Pendiente</td>
                    <td width="8%">Gastos</td>
                    <td width="8%"><?php echo $spanPrecioUnitario; ?></td>
                    <td width="4%">% Impuesto</td>
                    <td width="10%">Total</td>
                </tr>
                <tr id="trItmPieArticulo"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj"/>
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    	<div id="tdGastos" width="100%"></div>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
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
                                <input type="hidden" id="hddConfig19" name="hddConfig19"/>
                                <input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true', 'true');" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoPresupuesto" align="right" class="trResaltarTotal">
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
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_presupuesto_venta_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
    <table border="0" id="tblLista" style="display:none" width="760">
    <tr id="trBuscarCliente">
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="byId('btnBuscarCliente').click(); return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" class="inputHabilitado" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" id="btnLimpiarCliente" name="btnLimpiarCliente" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddNumeroItm" name="hddNumeroItm">
        	<table width="100%">
            <tr>
            	<td><div id="divLista" style="width:100%;"></div></td>
			</tr>
            <tr>
                <td align="right"><hr>
                    <button type="submit" id="btnGuardarLista" name="btnGuardarLista" onclick="xajax_asignarGasto(xajax.getFormValues('frmLista'), xajax.getFormValues('frmListaArticulo'));">Aceptar</button>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
    
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
	
    <div id="tblArticulo" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
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
                        <button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td><div id="divListaArticulo" style="width:100%"></div></td>
        </tr>
        <tr>
            <td>
            <form id="frmDatosArticulo" name="frmDatosArticulo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
            <input type="hidden" id="hddNumeroArt" name="hddNumeroArt"/>
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
                <tr>
                    <td class="divMsjAlerta" colspan="5" id="tdMsjArticulo" style="display:none"></td>
                </tr>
                </table>
            </fieldset>
                
                <table border="0" width="100%">
                <tr>
                    <td width="12%"></td>
                    <td width="34%"></td>
                    <td width="12%"></td>
                    <td width="20%"></td>
                    <td width="22%"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                    <td colspan="3">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtCantidadArt" name="txtCantidadArt" class="inputHabilitado" maxlength="6" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" size="10" style="text-align:right;"/></td>
                            <td>&nbsp;</td>
                            <td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                        </tr>
                        </table>
                    </td>
                    <td rowspan="2" width="34%">
                        <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr><td><img src="../img/iconos/tick.png"/></td><td>Suficiente Disp.</td></tr>
                                <tr><td><img src="../img/iconos/error.png"/></td><td>Poca Disp.</td></tr>
                                <tr><td><img src="../img/iconos/cancel.png"/></td><td>Sin Disp.</td></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td id="tdlstPrecioArt" width="100%"></td>
                            <td>&nbsp;</td>
                            <td id="tdMonedaPrecioArt"></td>
                            <td>&nbsp;</td>
                            <td align="right">
                                <input type="text" id="txtPrecioArt" name="txtPrecioArt" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="10" style="text-align:right"/>
                            </td>
                            <td align="center" id="tdDesbloquearPrecio"></td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddIdArtPrecio" name="hddIdArtPrecio"/>
                        <input type="hidden" id="hddIdPrecioArtPredet" name="hddIdPrecioArtPredet"/>
                        <input type="hidden" id="hddPrecioArtPredet" name="hddPrecioArtPredet"/>
                        <input type="hidden" id="hddBajarPrecio" name="hddBajarPrecio"/>
                        <a class="modalImg" id="aDesbloquearPrecioArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_catalogo_venta_precio_venta');"></a>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                    <td>
                        <input type="hidden" id="hddIdIvaArt" name="hddIdIvaArt"/>
                        <input type="text" id="txtIvaArt" name="txtIvaArt" readonly="readonly" size="10" style="text-align:right"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Precio Sugerido:</td>
                    <td><input type="text" id="txtPrecioSugerido" name="txtPrecioSugerido" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="10" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td align="right" colspan="5"><hr>
                        <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
                        <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
	
<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
    <table border="0" id="tblPermiso" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" size="30"/>
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
	
    <table border="0" id="tblArticuloSustituto" width="760">
    <tr>
    	<td>
            <div class="wrap">
                <!-- the tabs -->
                <ul class="tabs">
                    <li><a href="#">Sustitutos</a></li>
                    <li><a href="#">Alternos</a></li>
                </ul>
                
                <!-- tab "panes" -->
                <div id="divListaArticuloSustituto" class="pane">
                </div>
                
                <div id="divListaArticuloAlterno" class="pane">
                </div>
        	</div>
        </td>
    </tr>
	<tr>
    	<td align="right"><hr>
            <button type="button" id="btnGuardarArticuloSustituto" name="btnGuardarArticuloSustituto" onclick="validarFrmDatosArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloSustituto" name="btnCancelarArticuloSustituto" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</div>

<script>
window.onload = function(){
	jQuery(function($){
		$("#txtFechaVencimientoPresupuesto").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaVencimientoPresupuesto",
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargarDcto('<?php echo $_GET['id']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>