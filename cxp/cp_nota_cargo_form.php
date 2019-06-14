<?php
require_once("../connections/conex.php");
set_time_limit(0);
ini_set('memory_limit', '-1');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(isset($_GET['id']))) {
	if(!(validaAcceso("cp_nota_cargo_captura_list","insertar")) && !(validaAcceso("cp_nota_cargo_captura_list","editar"))) {
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
include("controladores/ac_cp_nota_cargo_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Nota de Débito</title>
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
		byId('tblMetodoPago').style.display = 'none';
		
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
		} else if (verTabla == "tblMetodoPago") {
			document.forms['frmMetodoPago'].reset();
			
			byId('txtMontoNotaCredito').className = 'inputHabilitado';
			
			byId('fieldsetTransferencia').style.display = 'none';
			byId('fieldsetCheque').style.display = 'none';
			byId('fieldsetAnticipo').style.display = 'none';
			byId('fieldsetNotaCredito').style.display = 'none';
			
			xajax_asignarMetodoPago(-1);
			
			window.onload = function(){
				jQuery(function($){
					$("#txtFechaTransferencia").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
					$("#txtFechaCheque").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
					
					$("#txtCuentaProveedorTransferencia").maskInput("9999-9999-99-9999999999",{placeholder:" "});
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaTransferencia",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"torqoise"
				});
				new JsDatePick({
					useMode:2,
					target:"txtFechaCheque",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"torqoise"
				});
			};
			xajax_cargaLstBanco('', 'lstBancoCompaniaTransferencia', 'onchange=\"xajax_cargaLstCuenta(this.value, \'\', \'lstCuentaCompaniaTransferencia\');\"');
			xajax_cargaLstCuenta(-1, '', 'lstCuentaCompaniaTransferencia');
			xajax_cargaLstBanco('', 'lstBancoProveedorTransferencia');
			
			xajax_cargaLstBanco('', 'lstBancoCompaniaCheque', 'onchange=\"xajax_cargaLstCuenta(this.value, \'\', \'lstCuentaCompaniaCheque\');\"');
			xajax_cargaLstCuenta(-1, '', 'lstCuentaCompaniaCheque');
			
			tituloDiv1 = 'Agregar Método de Pago';
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
		} else if (verTabla == "tblMetodoPago") {
			byId('lstMetodoPago').focus();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaAnticipo').style.display = 'none';
		byId('tblListaNotaCredito').style.display = 'none';
		
		if (verTabla == "tblListaAnticipo") {
			document.forms['frmBuscarAnticipo'].reset();
			
			byId('hddObjDestinoAnticipo').value = valor;
			byId('hddNomVentanaAnticipo').value = valor2;
			
			byId('btnBuscarAnticipo').click();
			
			tituloDiv2 = 'Anticipos';
		} else if (verTabla == "tblListaNotaCredito") {
			document.forms['frmBuscarNotaCredito'].reset();
			
			byId('hddObjDestinoNotaCredito').value = valor;
			byId('hddNomVentanaNotaCredito').value = valor2;
			
			byId('btnBuscarNotaCredito').click();
			
			tituloDiv2 = 'Notas de Crédito';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaAnticipo") {
			byId('txtCriterioBuscarAnticipo').focus();
			byId('txtCriterioBuscarAnticipo').select();
		} else if (verTabla == "tblListaNotaCredito") {
			byId('txtCriterioBuscarNotaCredito').focus();
			byId('txtCriterioBuscarNotaCredito').select();
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtFechaProveedor','t','fecha') == true
		&& validarCampo('lstTipoPago','t','listaExceptCero') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('lstAplicaLibro','t','listaExceptCero') == true
		&& validarCampo('txtObservacion','t','') == true
		&& validarCampo('txtTotalExento','t','numPositivo') == true
		&& validarCampo('txtTotalExonerado','t','numPositivo') == true
		&& validarCampo('txtTotalSaldo','t','numPositivo') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdProv','t','');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoPago','t','listaExceptCero');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('lstAplicaLibro','t','listaExceptCero');
			validarCampo('txtObservacion','t','');
			validarCampo('txtTotalExento','t','numPositivo');
			validarCampo('txtTotalExonerado','t','numPositivo');
			validarCampo('txtTotalSaldo','t','numPositivo');
			
			error = true;
		}
		
		if (byId('cbxNroAutomatico').checked == true) {
			if (byId('lstAplicaLibro').value == 1) {
			}
		} else {
			if (!(validarCampo('txtNumeroNotaCargo','t','') == true
			&& validarCampo('txtNumeroControl','t','') == true)){
				validarCampo('txtNumeroNotaCargo','t','');
				validarCampo('txtNumeroControl','t','');
				
				error = true;
			}
		}
		
		if (!(byId('hddObjItmMotivo').value.length > 0) && !(byId('txtIdNotaCargo').value > 0)) {
			alert("Debe agregar motivos al documento");
			return false;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea Registrar el Documento?') == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		}
	}
	
	function validarFrmMetodoPago() {
		error = false;
		if (byId('lstMetodoPago').value == 1) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
			if (!(validarCampo('txtFechaTransferencia','t','fecha') == true
			&& validarCampo('txtNumeroTransferencia','t','') == true
			&& validarCampo('lstBancoCompaniaTransferencia','t','lista') == true
			&& validarCampo('lstCuentaCompaniaTransferencia','t','lista') == true
			&& validarCampo('lstBancoProveedorTransferencia','t','lista') == true
			&& validarCampo('txtCuentaProveedorTransferencia','t','') == true
			&& validarCampo('txtMontoTransferencia','t','monto') == true)) {
				validarCampo('txtFechaTransferencia','t','fecha');
				validarCampo('txtNumeroTransferencia','t','');
				validarCampo('lstBancoCompaniaTransferencia','t','lista');
				validarCampo('lstCuentaCompaniaTransferencia','t','lista');
				validarCampo('lstBancoProveedorTransferencia','t','lista');
				validarCampo('txtCuentaProveedorTransferencia','t','');
				validarCampo('txtMontoTransferencia','t','monto');
				
				error = true;
			}
		} else if (byId('lstMetodoPago').value == 2) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
			if (!(validarCampo('txtFechaCheque','t','fecha') == true
			&& validarCampo('txtNumeroCheque','t','') == true
			&& validarCampo('lstBancoCompaniaCheque','t','lista') == true
			&& validarCampo('lstCuentaCompaniaCheque','t','lista') == true
			&& validarCampo('txtMontoCheque','t','monto') == true)) {
				validarCampo('txtFechaCheque','t','fecha');
				validarCampo('txtNumeroCheque','t','');
				validarCampo('lstBancoCompaniaCheque','t','lista');
				validarCampo('lstCuentaCompaniaCheque','t','lista');
				validarCampo('txtMontoCheque','t','monto');
				
				error = true;
			}
		} else if (byId('lstMetodoPago').value == 3) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
			if (!(validarCampo('txtIdAnticipo','t','') == true
			&& validarCampo('txtMontoAnticipo','t','monto') == true)) {
				validarCampo('txtIdAnticipo','t','');
				validarCampo('txtMontoAnticipo','t','monto');
				
				error = true;
			}
		} else if (byId('lstMetodoPago').value == 4) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
			if (!(validarCampo('txtIdNotaCredito','t','') == true
			&& validarCampo('txtMontoNotaCredito','t','monto') == true)) {
				validarCampo('txtIdNotaCredito','t','');
				validarCampo('txtMontoNotaCredito','t','monto');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea Registrar el Pago?') == true) {
				xajax_insertarMetodoPago(xajax.getFormValues('frmMetodoPago'), xajax.getFormValues('frmListaPagoDcto'));
			}
		}
	}
	
	function validarInsertarMotivo(idMotivo) {
		xajax_insertarMotivo(idMotivo, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'));
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_pagar.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCuentasPorPagar">Nota de Débito</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
            	<table border="0" width="100%">
                <tr>
                	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
					</td>
                    <td align="right" width="12%"></td>
                    <td width="18%"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%">Registrado por:</td>
                    <td width="58%">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Fecha Registro:</td>
                    <td><input type="text" id="txtFechaRegistroCompra" name="txtFechaRegistroCompra" readonly="readonly" size="10" style="text-align:center"/></td>
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
                                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'Prov', 'true', 'false');" size="6" style="text-align:right"/></td>
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
                                    <td rowspan="3" width="44%"><textarea id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="3" style="width:99%"></textarea></td>
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
                            	
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" width="12%"></td>
                                    <td width="16%"></td>
                                    <td align="right" width="12%"></td>
                                    <td id="tdlstClaveMovimiento" width="28%"></td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                    				<td width="20%">
                                    	<select id="lstTipoPago" name="lstTipoPago">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="0">Contado</option>
                                            <option value="1">Crédito</option>
                                        </select>
                                    </td>
                                </tr>
                                </table>
                                
                                <table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <button type="button" id="btnNotaCargoPDF"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Nota de Débito PDF</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos de la Nota de Débito</legend>
                                <input type="hidden" id="txtIdNotaCargo" name="txtIdNotaCargo" readonly="readonly" size="20" style="text-align:center"/>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Aplica a Libro:</td>
                                    <td width="60%">
                                    	<select id="lstAplicaLibro" name="lstAplicaLibro" onchange="xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));" style="width:99%">
                                        	<option value="-1">[ Seleccione ]</option>
                                            <option value="0">NO</option>
                                            <option value="1">SI</option>
                                        </select>
                                    </td>
								</tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                                    <td id="tdlstModulo"></td>
								</tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota de Débito:</td>
                                    <td>
                                    	<input type="text" id="txtNumeroNotaCargo" name="txtNumeroNotaCargo" size="20" style="text-align:center"/>
                                        <br>
				                    	<label id="lblNroAutomatico"><input type="checkbox" id="cbxNroAutomatico" name="cbxNroAutomatico" onclick="xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));" value="1"/> Nro. Correlativo Automático</label>
									</td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                                    <td><input type="text" id="txtNumeroControl" name="txtNumeroControl" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Nota de Débito:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td id="tdtxtFechaProveedor"><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" size="10" style="text-align:center"/></td>
										</tr>
                                        <tr>
                                            <td><label id="lblFechaRegistro"><input type="checkbox" id="cbxFechaRegistro" name="cbxFechaRegistro" onclick="xajax_asignarFechaRegistro(xajax.getFormValues('frmDcto'));" value="1"/>Asignar como fecha de registro</label></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td id="tdtxtEstatus" colspan="2"><input type="text" id="txtEstatus" name="txtEstatus" class="inputSinFondo" readonly="readonly" size="20" style="text-align:center"/></td>
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
        <tr id="trListaMotivo" align="left">
            <td>
            <form id="frmListaMotivo" name="frmListaMotivo" style="margin:0">
                <table border="0" width="100%">
                <tr>
                    <td align="left" colspan="20">
                        <a class="modalImg" id="aAgregarMotivo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaMotivo', 'Motivo');">
                            <button type="button" title="Agregar Motivo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button type="button" id="btnQuitarMotivo" name="btnQuitarMotivo" onclick="xajax_eliminarMotivoLote(xajax.getFormValues('frmListaMotivo'));" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaMotivo');"/></td>
                	<td width="4%">Nro.</td>
					<td width="14%">Código</td>
                    <td width="40%">Descripción</td>
                    <td width="16%">Módulo</td>
                    <td width="16%">Tipo Transacción</td>
                    <td width="10%">Total</td>
                    <td><input type="hidden" id="hddObjItmMotivo" name="hddObjItmMotivo" readonly="readonly" title="hddObjItmMotivo"/></td>
                </tr>
                <tr id="trItmPie"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td align="right">
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    	<div id="tdGastos" style="100%"></div>
                        
                    <fieldset id="fieldsetPlanMayor"><legend class="legend">Nota de Débito de Factura por Plan Mayor</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Nro. Factura:</td>
                        	<td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                    <td></td>
                                    <td>
                                    <a id="aVerFactura" target="_self"><img src="../img/iconos/ico_view.png" title="Ver Factura"/></a>
                                    </td>
                                </tr>
                                </table>
							</td>
                        	<td align="right" class="tituloCampo">Fecha Registro:</td>
                            <td><input type="text" id="txtFechaRegistroFactura" name="txtFechaRegistroFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Nro. Control:</td>
                        	<td><input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Fecha:</td>
                        	<td><input type="text" id="txtFechaFactura" name="txtFechaFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Tipo de Pago:</td>
                        	<td><input type="text" id="txtTipoPago" name="txtTipoPago" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr>
                            <td id="tdtxtEstatusFactura" colspan="4"><input type="text" id="txtEstatusFactura" name="txtEstatusFactura" class="inputSinFondo" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo" width="22%">Módulo:</td>
                        	<td width="28%"><input type="text" id="txtModulo" name="txtModulo" readonly="readonly" size="20" style="text-align:center"/></td>
                        	<td align="right" class="tituloCampo" width="22%">Aplica a Libro:</td>
                        	<td width="28%"><input type="text" id="txtAplicaLibro" name="txtAplicaLibro" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Motivo:</td>
                        	<td colspan="3">
                            	<table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtMotivo" name="txtMotivo" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                        
                    	<table width="100%">
                        <tr align="left">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
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
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap">
                            	<input type="radio" id="rbtInicialPorc" name="rbtInicial" onclick="byId('txtDescuento').readOnly = false; byId('txtSubTotalDescuento').readOnly = true;" style="display:none" value="1">
                                
                            	<input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td>
                            	<input type="radio" id="rbtInicialMonto" name="rbtInicial" onclick="byId('txtDescuento').readOnly = true; byId('txtSubTotalDescuento').readOnly = false;" style="display:none" value="2">
                                
                            	<input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/>
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
                            <td><input type="text" id="txtTotalNotaCargo" name="txtTotalNotaCargo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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
        <tr id="trListaPagoDcto">
        	<td>
            <fieldset><legend class="legend">Desglose de Pagos</legend>
            <form id="frmListaPagoDcto" name="frmListaPagoDcto" style="margin:0">
            	<input type="hidden" id="hddObjPago" name="hddObjPago" readonly="readonly"/>
            	<table border="0" width="100%">
                <tr id="trBtnListaPagoDcto">
                    <td align="left">
                        <a class="modalImg" id="aAgregarPago" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblMetodoPago');">
                            <button type="button" title="Agregar Pago"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button type="button" id="btnQuitarPago" name="btnQuitarPago" onclick="xajax_eliminarMetodoPago(xajax.getFormValues('frmListaPagoDcto'));" title="Eliminar Pago"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            	
            	<table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItmPago" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                	<td width="10%">Fecha Pago</td>
                	<td width="6%">Método Pago</td>
                	<td width="18%">Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito</td>
                    <td width="14%">Banco Compañia</td>
                    <td width="14%">Cuenta Compañia</td>
                    <td width="14%">Banco Proveedor</td>
                    <td width="14%">Cuenta Proveedor</td>
                    <td width="10%">Monto</td>
                </tr>
                <tr id="trItmPiePago" align="right" class="trResaltarTotal">
                	<td class="tituloCampo" colspan="8">Total Pagos:</td>
                	<td><input type="text" id="txtTotalPago" name="txtTotalPago" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                </table>
            </form>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<script type="text/javascript">
					
					console.log('<?php 
					$v1=$_GET['id']; 
					echo $v1; ?>');

				</script>


				<script type="text/javascript">
					
						console.log('<?php 



					$query = "SELECT * FROM cp_notadecargo  WHERE id_notacargo = $v1 ";

					$rs = mysql_query($query);
					$reg=mysql_fetch_array($rs);
					echo "consulta";
					echo $reg['fecha_notacargo'];
					$fechaRegistro = $reg['fecha_notacargo'];
					$dateTime_fechaReconversion = '2018-08-20';

					 ?>');

				</script>
        	<td align="right"><hr>
        		<?php
            		/*if($fechaRegistro > $dateTime_fechaReconversion){
            			echo "<button style=\"display:none;\" type=\"button\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(0)\"><table align=\"left\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion  </td></tr></table></button>";
					}else{
						echo "<input type='hidden' value='1' id='muestraBtnReconversion'>";
						echo "<button type=\"button\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(1)\"><table align=\"left\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion  </td></tr></table></button>";
					}*/

            	?>
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
    
<form id="frmMetodoPago" name="frmMetodoPago" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblMetodoPago" width="760">
    <tr align="left">
        <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Método de Pago:</td>
        <td>
            <select id="lstMetodoPago" name="lstMetodoPago" onchange="xajax_asignarMetodoPago(this.value)">
                <option value="-1">[ Seleccione ]</option>
                <!--<option value="1">Transferencia</option>
                <option value="2">Cheque</option>-->
                <option value="3">Anticipo</option>
                <option value="4">Nota de Crédito</option>
            </select>
        </td>
    </tr>
    <tr>
    	<td colspan="2">
        <fieldset id="fieldsetTransferencia"><legend class="legend">Transferencia</legend>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Transacción:</td>
            	<td><input type="text" id="txtFechaTransferencia" name="txtFechaTransferencia" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Transacción:</td>
            	<td><input type="text" id="txtNumeroTransferencia" name="txtNumeroTransferencia" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Banco Compañia:</td>
            	<td id="tdlstBancoCompaniaTransferencia" width="30%"></td>
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Cuenta Compañia:</td>
            	<td id="tdlstCuentaCompaniaTransferencia" width="30%"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Banco Proveedor:</td>
            	<td id="tdlstBancoProveedorTransferencia"></td>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cuenta Proveedor:</td>
            	<td><input type="text" id="txtCuentaProveedorTransferencia" name="txtCuentaProveedorTransferencia" size="30" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto a Cancelar:</td>
            	<td><input type="text" id="txtMontoTransferencia" name="txtMontoTransferencia" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        <fieldset id="fieldsetCheque"><legend class="legend">Cheque</legend>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Cheque:</td>
            	<td><input type="text" id="txtFechaCheque" name="txtFechaCheque" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Cheque:</td>
            	<td><input type="text" id="txtNumeroCheque" name="txtNumeroCheque" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Banco Compañia:</td>
            	<td id="tdlstBancoCompaniaCheque" width="30%"></td>
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Cuenta Compañia:</td>
            	<td id="tdlstCuentaCompaniaCheque" width="30%"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto a Cancelar:</td>
            	<td><input type="text" id="txtMontoCheque" name="txtMontoCheque" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        <fieldset id="fieldsetAnticipo"><legend class="legend">Anticipo</legend>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Anticipo:</td>
            	<td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="hidden" id="txtIdAnticipo" name="txtIdAnticipo" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aListarAnticipo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaAnticipo', 'Anticipo');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Fecha:</td>
            	<td><input type="text" id="txtFechaAnticipo" name="txtFechaAnticipo" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Estatus:</td>
            	<td><input type="text" id="txtEstadoAnticipo" name="txtEstadoAnticipo" readonly="readonly" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%">Total:</td>
            	<td width="30%"><input type="text" id="txtTotalAnticipo" name="txtTotalAnticipo" maxlength="12" readonly="readonly" size="16" style="text-align:right"/></td>
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Saldo:</td>
            	<td width="30%"><input type="text" id="txtSaldoAnticipo" name="txtSaldoAnticipo" maxlength="12" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto a Cancelar:</td>
            	<td><input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        <fieldset id="fieldsetNotaCredito"><legend class="legend">Nota de Crédito</legend>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota de Crédito:</td>
            	<td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="hidden" id="txtIdNotaCredito" name="txtIdNotaCredito" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aListarNotaCredito" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaNotaCredito', 'NotaCredito');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Fecha:</td>
            	<td><input type="text" id="txtFechaNotaCredito" name="txtFechaNotaCredito" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Estatus:</td>
            	<td><input type="text" id="txtEstadoNotaCredito" name="txtEstadoNotaCredito" readonly="readonly" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%">Total:</td>
            	<td width="30%"><input type="text" id="txtTotalNotaCredito" name="txtTotalNotaCredito" maxlength="12" readonly="readonly" size="16" style="text-align:right"/></td>
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Saldo:</td>
            	<td width="30%"><input type="text" id="txtSaldoNotaCredito" name="txtSaldoNotaCredito" maxlength="12" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto a Cancelar:</td>
            	<td><input type="text" id="txtMontoNotaCredito" name="txtMontoNotaCredito" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
            <button type="submit" id="btnGuardarMetodoPago" name="btnGuardarMetodoPago" onclick="validarFrmMetodoPago();">Aceptar</button>
            <button type="button" id="btnCancelarMetodoPago" name="btnCancelarMetodoPago" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblListaAnticipo" width="960">
    <tr>
        <td>
        <form id="frmBuscarAnticipo" name="frmBuscarAnticipo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoAnticipo" name="hddObjDestinoAnticipo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaAnticipo" name="hddNomVentanaAnticipo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarAnticipo" name="txtCriterioBuscarAnticipo" class="inputHabilitado" onkeyup="byId('btnBuscarAnticipo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarAnticipo" name="btnBuscarAnticipo" onclick="xajax_buscarAnticipo(xajax.getFormValues('frmBuscarAnticipo'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarAnticipo'].reset(); byId('btnBuscarAnticipo').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaAnticipo" name="frmListaAnticipo" onsubmit="return false;" style="margin:0">
            <div id="divListaAnticipo" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaAnticipo" name="btnCancelarListaAnticipo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaNotaCredito" width="960">
    <tr>
        <td>
        <form id="frmBuscarNotaCredito" name="frmBuscarNotaCredito" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoNotaCredito" name="hddObjDestinoNotaCredito" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaNotaCredito" name="hddNomVentanaNotaCredito" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarNotaCredito" name="txtCriterioBuscarNotaCredito" class="inputHabilitado" onkeyup="byId('btnBuscarNotaCredito').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarNotaCredito" name="btnBuscarNotaCredito" onclick="xajax_buscarNotaCredito(xajax.getFormValues('frmBuscarNotaCredito'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarNotaCredito'].reset(); byId('btnBuscarNotaCredito').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaNotaCredito" name="frmListaNotaCredito" onsubmit="return false;" style="margin:0">
            <div id="divListaNotaCredito" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaNotaCredito" name="btnCancelarListaNotaCredito" class="close">Cerrar</button>
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

xajax_formNotaCargo('<?php echo $_GET['id']?>', xajax.getFormValues('frmListaPagoDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>

<script type="text/javascript">
	
	function reconversionMonetaria(activo){

	if(activo == 0){

		alert("No está permitido reconvertir una nota de cargo con fecha posterior al 20-Agosto-2018");
		return false;

	}else{

		var idNotaCargoReconversion = <?php echo $_GET['id'] ?>;
		var confirmacion = confirm("¿Desea realizar la reconversión de la nota de cargo ? Esta acción no se puede revertir");

		if (confirmacion == true){
			mensaje = xajax_reconversion(idNotaCargoReconversion);

		}else{
			return false;
		}
	}

	return true;
}

</script>