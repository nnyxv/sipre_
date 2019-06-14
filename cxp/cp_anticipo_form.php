<?php
require_once("../connections/conex.php");
set_time_limit(0);
ini_set('memory_limit', '-1');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(isset($_GET['id']))) {
	if(!(validaAcceso("cp_anticipo_list","insertar")) && !(validaAcceso("cp_anticipo_list","editar"))) {
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
include("controladores/ac_cp_nota_credito_form.php");

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
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Nota de Crédito</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaProveedor').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblListaProveedor") {
			document.forms['frmBuscarProveedor'].reset();
			
			byId('hddObjDestinoProveedor').value = valor;
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv1 = 'Proveedores';
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv1 = 'Motivos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblListaProveedor") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtNumeroNotaCredito','t','') == true
		&& validarCampo('txtNumeroControl','t','') == true
		&& validarCampo('txtFechaNotaCredito','t','fecha') == true
		&& validarCampo('lstTipoPago','t','listaExceptCero') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('lstAplicaLibro','t','listaExceptCero') == true
		&& validarCampo('txtIdMotivo','t','') == true
		&& validarCampo('txtObservacion','t','') == true
		&& validarCampo('txtTotalExento','t','numPositivo') == true
		&& validarCampo('txtTotalExonerado','t','numPositivo') == true
		&& validarCampo('txtTotalSaldo','t','numPositivo') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdProv','t','');
			validarCampo('txtNumeroNotaCredito','t','');
			validarCampo('txtNumeroControl','t','');
			validarCampo('txtFechaNotaCredito','t','fecha');
			validarCampo('lstTipoPago','t','listaExceptCero');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('lstAplicaLibro','t','listaExceptCero');
			validarCampo('txtIdMotivo','t','');
			validarCampo('txtObservacion','t','');
			validarCampo('txtTotalExento','t','numPositivo');
			validarCampo('txtTotalExonerado','t','numPositivo');
			validarCampo('txtTotalSaldo','t','numPositivo');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea Registrar el Documento?') == true) {
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		}
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_pagar.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCuentasPorPagar">Nota de Crédito</td>
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
                        	<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresa(this.value);" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
					</td>
                    <td align="right" class="tituloCampo" width="12%">Fecha Registro:</td>
                    <td width="18%"><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="70%">
                            <fieldset><legend class="legend">Proveedor</legend>
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Razón Social:</td>
                                    <td colspan="3">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'Prov');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarProv" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaProveedor', 'Prov');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" rowspan="3" width="18%">Dirección:</td>
                                    <td rowspan="3" width="44%"><textarea cols="55" id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="3"></textarea></td>
                                    <td align="right" class="tituloCampo" width="18%"><?php echo $spanProvCxP; ?>:</td>
                                    <td width="20%"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Teléfonos:</td>
                                    <td><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="12" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Días Crédito:</td>
                                    <td><input type="text" id="txtDiasCreditoProv" name="txtDiasCreditoProv" readonly="readonly" size="12" style="text-align:right"/></td>
                                </tr>
                                </table>
                            </fieldset>
                            
                            	<table align="right" border="0">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Módulo:</td>
                                    <td id="tdlstModulo"></td>
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Aplica a Libro:</td>
                                    <td>
                                    	<select id="lstAplicaLibro" name="lstAplicaLibro">
                                        	<option value="-1">[ Seleccione ]</option>
                                            <option value="0">NO</option>
                                            <option value="1">SI</option>
                                        </select>
                                    </td>
								</tr>
                                <tr>
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Motivo:</td>
                                    <td colspan="3">
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" onblur="xajax_asignarMotivo(this.value, 'Motivo');" size="6" style="text-align:right;"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarMotivo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaMotivo', 'Motivo');">
                                                <button type="button" id="btnListarMotivo" name="btnListarMotivo" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtMotivo" name="txtMotivo" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos de la Nota de Crédito</legend>
                                <table border="0" width="100%">
                                <tr>
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Nro. Nota de Crédito:</td>
                                    <td width="60%">
                                    	<input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito" size="20" style="text-align:center"/>
                                        <input type="hidden" id="txtIdNotaCredito" name="txtIdNotaCredito" readonly="readonly" size="20" style="text-align:center"/>
									</td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                                    <td><input type="text" id="txtNumeroControl" name="txtNumeroControl" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                    				<td><input type="text" id="txtFechaNotaCredito" name="txtFechaNotaCredito" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                    				<td>
                                    	<select id="lstTipoPago" name="lstTipoPago">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="0">Contado</option>
                                            <option value="1">Crédito</option>
                                        </select>
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
        <tr id="trListaPagoDcto">
        	<td>
            <fieldset><legend class="legend">Desglose de Pagos</legend>
            <form id="frmListaPagoDcto" name="frmListaPagoDcto" style="margin:0">
            	<table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                	<td width="12%">Fecha Tranferencia / Cheque / Anticipo / Nota de Crédito</td>
                	<td width="6%">Método Pago</td>
                	<td width="14%">Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito</td>
                    <td width="14%">Banco Compañia</td>
                    <td width="14%">Cuenta Compañia</td>
                    <td width="14%">Banco Proveedor</td>
                    <td width="14%">Cuenta Proveedor</td>
                    <td width="12%">Monto</td>
                </tr>
                <tr id="trItmPie" align="right" class="trResaltarTotal">
                	<td class="tituloCampo" colspan="8">Total Pagos:</td>
                	<td><input type="text" id="txtTotalPago" name="txtTotalPago" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                </table>
            </form>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td align="right">
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    	<div id="tdGastos" style="100%"></div>
                    	<table>
                        <tr align="left">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea cols="55" id="txtObservacion" name="txtObservacion" rows="3"></textarea></td>
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
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap">
                            	<input type="text" id="txtDescuento" name="txtDescuento" onkeypress="return validarSoloNumerosReales(event);" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con I.V.A.:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin I.V.A.:</td>
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
                            <td><input type="text" id="txtTotalPedido" name="txtTotalPedido" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal3">
                            <td class="tituloCampo">Saldo:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalSaldoMoneda"></td>
                            <td><input type="text" id="txtTotalSaldo" name="txtTotalSaldo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border-top:1px solid;"></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Exento:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExentoMoneda"></td>
                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Exonerado:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExoneradoMoneda"></td>
                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
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
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
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
    
    <table border="0" id="tblListaProveedor" width="760">
    <tr>
        <td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoProveedor" name="hddObjDestinoProveedor" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaProveedor" name="hddNomVentanaProveedor" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" class="inputHabilitado" onkeyup="byId('btnBuscarProveedor').click();"/></td>
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
        <td>
        <form id="frmListaProveedor" name="frmListaProveedor" onsubmit="return false;" style="margin:0">
            <div id="divListaProveedor" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaProveedor" name="btnCancelarListaProveedor" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaMotivo" width="760">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" class="inputHabilitado" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaMotivo" name="frmListaMotivo" onsubmit="return false;" style="margin:0">
            <div id="divListaMotivo" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaMotivo" name="btnCancelarListaMotivo" class="close">Cerrar</button>
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

xajax_formNotaCredito('<?php echo $_GET['id']?>', xajax.getFormValues('frmListaPagoDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>