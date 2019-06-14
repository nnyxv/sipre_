<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_cheque_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_cheque_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Cheque</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
		
	<script language="javascript">
	function calcularPagos(){
		/*$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado)').removeClass();//limpio de clases
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):odd').addClass('trResaltar5');//odd impar
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):even').addClass('trResaltar4');//even par*/
		
		$('tr[name=trItmDctoPagado]').removeClass();//limpio de clases
		$('tr[name=trItmDctoPagado]:odd').addClass('trResaltar5');//odd impar
		$('tr[name=trItmDctoPagado]:even').addClass('trResaltar4');//even par
		
		xajax_calcularPagos(xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS">Cheque</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDcto" name="frmDcto" style="margin:0" onsubmit="return false;">
				<table border="0" width="100%">
                <tr align="left">
					<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
					<td>
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
							<td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
						</tr>
						</table>
					</td>
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
                    <td align="right" class="tituloCampo" width="12%">Fecha Registro:</td>
                    <td width="18%"><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
				<tr>
					<td colspan="4">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td valign="top" width="70%">
							<fieldset><legend class="legend">Cliente</legend>
                                <table id="tblIdCliente" border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                    <td colspan="3">
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
                                        <input type="hidden" id="hddTipoPagoCliente" name="hddTipoPagoCliente"/>
                                    </td>
                                    <td align="right" class="tituloCampo"><?php echo $spanClienteCxC; ?>:</td>
                                    <td><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                                    <td colspan="3" rowspan="2"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanNIT; ?>:</td>
                                    <td><input type="text" id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Días Crédito:</td>
                                    <td>
                                        <table border="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td width="40%">Días:</td>
                                            <td width="60%"><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        <tr>
                                            <td>Disponible:</td>
                                            <td><input type="text" id="txtCreditoCliente" name="txtCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="16%">Teléfono:</td>
                                    <td width="15%"><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                    <td align="right" class="tituloCampo" width="16%">Otro Teléfono:</td>
                                    <td width="15%"><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                    <td width="16%"></td>
                                    <td width="22%"></td>
                                </tr>
                                </table>
                            </fieldset>
                            
                            <fieldset><legend class="legend">Observación</legend>
                                <table>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Observación:</td>
                                    <td><textarea name="txtObservacionCheque" id="txtObservacionCheque" cols="60" rows="2"></textarea></td>
                                </tr>
                                </table>
							</fieldset>
							</td>
							<td valign="top" width="30%">
							<fieldset><legend class="legend">Cheque</legend>
								<input type="hidden" id="hddIdCheque" name="hddIdCheque"/>
								<table border="0" width="100%">
								<tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Tipo de Cheque:</td>
                                    <td colspan="2" width="60%">
	                                    <div id="tdlstTipoCheque"></div>
										<input type="hidden" id="hddTipoCheque" name="hddTipoCheque"/>
                                    </td>
                                </tr>
								<tr align="left">
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
									<td id="tdlstModulo" colspan="2"></td>
								</tr>
                                <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                                    <td class="tituloCampo">Total:</td>
                                    <td id="tdTotalRegistroMoneda"></td>
                                    <td><input type="text" id="txtTotalCheque" name="txtTotalCheque" class="inputSinFondo" readonly="readonly" size="17" style="text-align:right"/></td>
                                </tr>
                                <tr align="right" class="trResaltarTotal3">
                                    <td class="tituloCampo">Saldo Disponible:</td>
                                    <td id="tdTotalSaldoMoneda"></td>
                                    <td><input type="text" id="txtTotalSaldo" name="txtTotalSaldo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td id="tdtxtEstatus" colspan="3"><input type="text" id="txtEstatus" name="txtEstatus" class="inputSinFondo" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
								</table>
							</fieldset>
                                
                                <table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <button type="button" id="btnReciboPagoPDF"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png"/></td><td>&nbsp;</td><td>Recibo(s) de Pago(s)</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
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
			<td width="100%">
            <form id="frmDetallePago" name="frmDetallePago" onsubmit="return false;">
            <fieldset><legend class="legend">Forma de Pago</legend>
                <table border="0" width="100%">
                <tr id="trBancoFechaDeposito" align="left">
                    <td id="tdEtiquetaBancoFechaDeposito" align="right" class="tituloCampo" width="164"><span class="textoRojoNegrita">*</span>Banco Cliente:</td>
                    <td scope="row">
                    	<div id="tdselBancoCliente">
                            <select id="selBancoCliente" name="selBancoCliente" style="width:200px">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr id="trNumeroCuenta" align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Cuenta:</td>
                    <td>
                        <div id="divselNumeroCuenta" style="display:none">
                            <select id="selNumeroCuenta" name="selNumeroCuenta" style="width:200px">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </div>
                        <input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="30"/>
                    </td>
                </tr>
                <tr id="trNumeroDocumento" align="left">
                    <td id="tdNumeroDocumento" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Cheque:</td>
                    <td>
                    	<input type="text" id="txtNumeroDctoPago" name="txtNumeroDctoPago"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto Cheque:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0"><!-- botones()-->
                        <tr>
                            <td><input type="text" id="txtMontoPago" name="txtMontoPago" onblur="setFormatoRafk(this,2); calcularPagos();" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
			</td>
		</tr>
        <tr>
			<td width="100%">
			<form id="frmListaDctoPagado" name="frmListaDctoPagado" onsubmit="return false;">
				<fieldset><legend class="legend">Documentos Pagados</legend>
                <table width="100%">
                <tr>
                    <td>
                        <table width="100%" id="tablaAnticiposAgregados">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                			<td width="4%">Nro.</td>
                            <td width="10%">Fecha Pago</td>
                            <td width="6%">Nro. Recibo</td>
                            <td width="6%">Forma de Pago / Dcto. Pagado</td>
                            <td width="12%">Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito</td>
                            <td width="19%">Banco Cliente / Cuenta Cliente</td>
                            <td width="19%">Banco Compañia / Cuenta Compañia</td>
                            <td width="14%">Caja</td>
                            <td width="10%">Monto</td>
                        </tr>
                        <tr id="trItmPieDctoPagado" class="trResaltarTotal">
                            <td align="right" class="tituloCampo" colspan="9">Total Dctos. Pagados:</td>
                            <td><input type="text" id="txtTotalDctoPagado" name="txtTotalDctoPagado" class="inputSinFondo" readonly="readonly" style="text-align:right" value="0.00"/></td>
                            <td></td>
                        </tr>
                        <tr class="trResaltarTotal3">
                            <td align="right" class="tituloCampo" colspan="9">Total Saldo Disponible:</td>
                            <td>
                                <input type="text" id="txtMontoRestante" name="txtMontoRestante" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/>
                                <input type="hidden" id="hddSaldoCheque" name="hddSaldoCheque"/>
                                <input type="hidden" id="hddMontoRestante" name="hddMontoRestante"/>
                            </td>
                            <td></td>
                        </tr>
                        </table>                
                    </td>
                </tr>
                </table>
				</fieldset>
			</form>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script language="javascript">
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

xajax_cargarDcto('<?php echo $_GET['id']; ?>');
</script>