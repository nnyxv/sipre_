<?php
require_once("../connections/conex.php");
require_once("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_devolucion_alquiler_form","insertar"))) {
	echo "<script>alert('Acceso Denegado');	top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_cj_devolucion_alquiler_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Devolución Factura de Alquiler</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>

    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    	
	<style>
    button{
        cursor:pointer;
    }
    </style>
    
    <script>
	
	function calcularDcto(){
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmListaAccesorio'), xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmTotalDcto'));
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('lstTipoContrato','t','lista') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('txtIdUnidadFisica','t','') == true
		&& validarCampo('txtNumeroControlFactura','t','') == true
		&& validarCampo('txtObservacion','t','') == true
		&& validarCampo('txtTotalOrden','t','monto') == true) {
			if ($(".checkboxPrecio").length > 0 || $(".checkboxAccesorio").length > 0 || $(".checkboxAdicional").length > 0 ) {
				if (confirm('¿Seguro desea generar la Nota de Credito?') == true) {
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmListaAccesorio'), xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmTotalDcto'));
				}
			} else {
				alert("Debe agregar items al contrato");
				return false;
			}
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstTipoContrato','t','lista');
			validarCampo('lstMoneda','t','lista');
			validarCampo('txtIdUnidadFisica','t','');
			validarCampo('txtNumeroControlFactura','t','');
			validarCampo('txtObservacion','t','');
			validarCampo('txtTotalOrden','t','monto');
			
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		}
	}
	
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_cj.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCaja">Devolución Factura de Alquiler</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" style="margin:0">
            	<table border="0" width="100%">
                <tr align="left">
                    <td width="70%" style="vertical-align:top">
                        <table cellpadding="0" width="100%">
                        <tr align="left">
                        	<td align="right" class="tituloCampo" width="16%" ><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr><td>&nbsp;</td></tr>
                        <tr><td>&nbsp;</td></tr>
                        <tr><td>&nbsp;</td></tr>
                        <tr><td>&nbsp;</td></tr>
                        </table>
                        
						<fieldset><legend class="legend">Cliente</legend>
                            <table border="0" width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                <td colspan="3">
                                    <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                                        <td></td>
                                        <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                    </tr>
                                    <tr align="center">
                                        <td id="tdMsjCliente" colspan="3"></td>
                                    </tr>
                                    </table>
                                    <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
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
			
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                            <td>
                                <select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">1.- COMPRA</option>
                                    <option value="2">2.- ENTRADA</option>
                                    <option value="3">3.- VENTA</option>
                                    <option value="4">4.- SALIDA</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td>
                                <input type="hidden" id="hddIdClaveMovimiento" name="hddIdClaveMovimiento" readonly="readonly"/>
                                <input type="text" id="txtClaveMovimiento" name="txtClaveMovimiento" readonly="readonly" size="30"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td valign="bottom">
                        <table>
                        <tr>
							<td width="40%">
                            <!--<fieldset><legend class="legend">Datos de la Nota de Cr&eacute;dito</legend>-->
                            	<table border="0" width="100%">
                                <tr align="left">
	                                <td align="right" class="tituloCampo">Nro. Nota Cr&eacute;dito:</td>                                
                                	<td><input type="text" style="text-align:center" size="20" readonly="readonly" name="txtNumeroNotaCredito" id="txtNumeroNotaCredito"></td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo">Fecha:</td>
                                    <td>
                         	           <input type="text" style="text-align:center" size="10" readonly="readonly" name="txtFechaNotaCredito" id="txtFechaNotaCredito">
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" id="txtNumeroControlNotaCredito" name="txtNumeroControlNotaCredito" size="16" style="color:#F00; font-weight:bold; text-align:center;" />
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr><td></td></tr>
                                <tr><td></td></tr>
                                </table>
                            <!--</fieldset>-->
                            <fieldset><legend class="legend">Datos de la Factura</legend>
                                <table border="0" width="100%">
                                <tr align="left" style="display:none;">
                                    <td align="right" class="tituloCampo" width="12%">Fecha Contrato:</td>
			                        <td width="18%"><input type="text" id="txtFechaContrato" name="txtFechaContrato" readonly="readonly" style="text-align:center" size="10"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%">Nro. Factura:</td>
                                    <td width="60%">
                                        <input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="hddIdFactura" name="hddIdFactura" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Nro. Contrato:</td>
                                    <td width="60%">
                                        <input type="text" id="txtNumeroContrato" name="txtNumeroContrato" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="hddIdContrato" name="hddIdContrato" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%">Tipo de Contrato:</td>
                                    <td width="20%" id="tdlstTipoContrato">
                                    	<select id="lstTipoContrato" name="lstTipoContrato">
                                        	<option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Moneda:</td>
                                    <td id="tdlstMoneda"></td>
                                </tr>
                                <tr align="left" style="display:none;">
                                    <td align="right" class="tituloCampo">Nro. Presupuesto:</td>
                                    <td>
                                        <input type="hidden" id="hddIdPresupuestoVenta" name="hddIdPresupuestoVenta"/>
                                        <input type="text" id="txtNumeroPresupuestoVenta" name="txtNumeroPresupuestoVenta" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Nro. Control:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" size="16" style="color:#F00; font-weight:bold; text-align:center;" readonly="readonly"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Fecha Registro:</td>
                                    <td>
                                        <input type="text" id="txtFechaFactura" name="txtFechaFactura" onchange="xajax_asignarFechaCredito(xajax.getFormValues('frmDcto'))" readonly="readonly" size="16" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Fecha Vencimiento:</td>
                                    <td><input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="16" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Vendedor:</td>
                                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="20" /></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%">Tipo de Pago:</td>
                                    <td width="20%">
                                        <input type="hidden" id="hddTipoPago" name="hddTipoPago" readonly="readonly"/>
                                        <input type="text" id="txtTipoPago" name="txtTipoPago" class="divMsjInfo2" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
							</td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="60%">
        						<fieldset>
                                <legend class="legend">Salida, Entrada, Tiempos</legend>
                                <table border="0" width="100%">
                                <tr class="tituloColumna" align="center">
                                    <td colspan="4">Salida</td>
                                    <td colspan="4">Entrada</td>
                                </tr>
                                <tr>
                                    <td width="14%" align="right" class="tituloCampo" style="white-space: nowrap;">Fecha Salida:</td>
                                    <td width="10%"><input type="text" id="txtFechaSalida" name="txtFechaSalida" size="10" readonly="readonly"  /></td>
                                    <td width="14%" align="right" class="tituloCampo">Hora Salida:</td>
                                    <td width="10%"><input type="text" id="txtHoraSalida" name="txtHoraSalida" size="10" readonly="readonly"  /></td>
                                    <td width="14%" align="right" class="tituloCampo" style="white-space: nowrap;">Fecha Entrada:</td>
                                    <td width="10%"><input type="text" id="txtFechaEntrada" name="txtFechaEntrada" size="10" readonly="readonly"  /></td>
                                    <td width="14%" align="right" class="tituloCampo">Hora Entrada:</td>
                                    <td width="10%"><input type="text" id="txtHoraEntrada" name="txtHoraEntrada" size="10" readonly="readonly"  /></td>
                                </tr>
        
                                <tr>
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanKilometraje); ?>:</td>
                                    <td><input type="text" id="txtKilometrajeSalida" name="txtKilometrajeSalida" size="10" readonly="readonly"/></td>
									<td align="right" class="tituloCampo">Combustible:</td>
                                    <td>
                                        <select id="lstCombustibleSalida" name="lstCombustibleSalida" >
                                            <option value="">-</option>
                                            <option value="0.00">0</option>
                                            <option value="0.25">1/4</option>
                                            <option value="0.50">1/2</option>
                                            <option value="0.75">3/4</option>
                                            <option value="1.00">1</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanKilometraje); ?>:</td>
                                    <td><input type="text" id="txtKilometrajeEntrada" name="txtKilometrajeEntrada" size="10" readonly="readonly"/></td>
                                    <td align="right" class="tituloCampo">Combustible:</td>
                                    <td>
                                        <select id="lstCombustibleEntrada" name="lstCombustibleEntrada" >
                                            <option value="">-</option>
                                            <option value="0.00">0</option>
                                            <option value="0.25">1/4</option>
                                            <option value="0.50">1/2</option>
                                            <option value="0.75">3/4</option>
                                            <option value="1.00">1</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td align="right" class="tituloCampo">Fecha Final:</td>
                                    <td><input type="text" id="txtFechaEntradaFinal" name="txtFechaEntradaFinal" size="10" readonly="readonly"  /></td>
                                    <td align="right" class="tituloCampo">Hora Final:</td>
                                    <td><input type="text" id="txtHoraEntradaFinal" name="txtHoraEntradaFinal" size="10" readonly="readonly" /></td>
                                </tr>
                                <tr class="tituloColumna" align="center">
                                    <td colspan="8">Tiempos</td>
                                </tr>
                                <tr>
                                	<td align="right" class="tituloCampo">D&iacute;as Contrato:</td>
                                    <td><input type="text" id="txtDiasContrato" name="txtDiasContrato" size="10" readonly="readonly" class="inputInicial"/></td>
                                    <td align="right" class="tituloCampo">D&iacute;as Sobre Tiempo:</td>
                                    <td><input type="text" id="txtDiasSobreTiempo" name="txtDiasSobreTiempo" size="10" readonly="readonly" class="inputInicial" /></td>
                                	<td align="right" class="tituloCampo">D&iacute;as Bajo Tiempo:</td>
                                    <td><input type="text" id="txtDiasBajoTiempo" name="txtDiasBajoTiempo" size="10" readonly="readonly" class="inputInicial"/></td>
                                    <td align="right" class="tituloCampo">D&iacute;as Total:</td>
                                    <td><input type="text" id="txtDiasTotal" name="txtDiasTotal" size="10" readonly="readonly" class="inputInicial"/></td>
                                </tr>
                                </table>
                                </fieldset>
                            </td>
                            <td valign="top" width="40%">
                                <fieldset><legend class="legend">Datos del Veh&iacute;culo</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                	<td align="right" width="21%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro Unidad:</td>
                                    <td>
										<table cellspacing="0" cellpadding="0">
                                        <tr>
                                        	<td><input type="text" style="text-align:right" id="txtIdUnidadFisica" name="txtIdUnidadFisica" readonly="readonly" size="6">
                                            </td>
	                                    </tr>
                                    	</table>
                        			</td>
                                    <td align="right" class="tituloCampo">Clase:</td>
                                    <td>
                                    	<input type="hidden" size="18" name="hddIdClase" id="hddIdClase">
                                    	<input type="text" size="18" readonly="readonly" name="txtClaseVehiculo" id="txtClaseVehiculo">
                                    </td>
                                </tr>
                                
                                <tr align="left">
                                    <td align="right" width="21%" class="tituloCampo"><?php echo utf8_encode($spanPlaca); ?>:</td>
                                    <td width="29%">
                                        <input type="text" style="text-align:center" size="18" readonly="readonly" name="txtPlacaVehiculo" id="txtPlacaVehiculo">
                                    </td>
                                    <td align="right" width="21%" class="tituloCampo">Año:</td>
                                    <td width="29%"><input type="text" style="text-align:center" size="18" readonly="readonly" name="txtAnoVehiculo" id="txtAnoVehiculo">
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanSerialCarroceria); ?>:</td>
                                    <td>
                                        <input type="text" style="text-align:center" size="18" readonly="readonly" name="txtSerialCarroceriaVehiculo" id="txtSerialCarroceriaVehiculo">
                                    </td>
                                    <td align="right" class="tituloCampo">Color:</td>
                                    <td><input type="text" size="18" readonly="readonly" name="txtColorVehiculo" id="txtColorVehiculo"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Marca:</td>
                                    <td><input type="text" size="18" readonly="readonly" name="txtMarcaVehiculo" id="txtMarcaVehiculo"></td>
                                    <td align="right" class="tituloCampo">Unidad Básica:</td>
                                    <td>
                                        <input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica">
                                        <input type="text" size="18" readonly="readonly" id="txtUnidadBasica" name="txtUnidadBasica">
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Modelo:</td>
                                    <td>
                                        <input type="hidden" name="hddIdModelo" id="hddIdModelo">
                                        <input type="text" size="18" readonly="readonly" name="txtModeloVehiculo" id="txtModeloVehiculo">
                                    </td>
                                    <td align="right" class="tituloCampo">Almac&eacute;n:</td>
                                    <td><input type="text" size="18" readonly="readonly" id="txtAlmacenVehiculo" name="txtAlmacenVehiculo"></td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo">Condici&oacute;n:</td>
                                    <td><input type="text" size="18" readonly="readonly" id="txtCondicionVehiculo" name="txtCondicionVehiculo"></td>
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanKilometraje); ?>:</td>
                                    <td><input type="text" size="18" readonly="readonly" id="txtKilometrajeVehiculo" name="txtKilometrajeVehiculo"></td>
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
        	<td>
	        <fieldset>
	            <legend class="legend">Precio / Tarifa</legend>
            	<table border="0" width="100%">

                <tr>
                    <td>
                    <form id="frmListaPrecio" name="frmListaPrecio" style="margin:0">
                        <table class="tablaResaltarPar"  border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td></td>
                                <td width="20%">Código</td>
                                <td width="30%">Descripción</td>
                                <td width="10%">Días</td>
                                <td width="15%">Precio</td>
                                <td width="15%">% Impuesto</td>
                                <td width="10%">Total</td>
                            </tr>
                        </thead>
                        <tbody>
	                        <tr id="trItmPiePrecio"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td>
	        <fieldset>
	            <legend class="legend">Accesorios</legend>
            	<table border="0" width="100%">

                <tr>
                    <td>
                    <form id="frmListaAccesorio" name="frmListaAccesorio" style="margin:0">
                        <table class="tablaResaltarPar" border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td></td>
                                <td width="20%">Código</td>
                                <td width="30%">Descripción</td>
                                <td width="10%">Cantidad</td>
                                <td width="15%">Precio</td>
                                <td width="15%">% Impuesto</td>
                                <td width="10%">Total</td>
                            </tr>
                        </thead>
                        <tbody>
                        	<tr id="trItmPieAccesorio"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td>
	        <fieldset>
	            <legend class="legend">Adicionales</legend>
            	<table border="0" width="100%">

                <tr>
                    <td>
                    <form id="frmListaAdicional" name="frmListaAdicional" style="margin:0">
                        <table class="tablaResaltarPar" border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td></td>
                                <td width="20%">Código</td>
                                <td width="30%">Descripción</td>
                                <td width="10%">Cantidad</td>
                                <td width="15%">Precio</td>
                                <td width="15%">% Impuesto</td>
                                <td width="10%">Total</td>
                            </tr>
                        </thead>
                        <tbody>
                        	<tr id="trItmPieAdicional"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
                <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
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
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
										<input type="hidden" id="hddConfig500" name="hddConfig500"/>
                                    </td>
                                	<td nowrap="nowrap">
										<input type="text" id="txtDescuento" name="txtDescuento"  readonly="readonly" size="6" style="text-align:right"/>%
									</td>
								</tr>
                                </table>
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                        <tr id="trNetoContrato" align="right" class="trResaltarTotal">
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
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('cj_devolucion_venta_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
        </tr>
        </table>
    </div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('txtNumeroControlNotaCredito').className = 'inputHabilitado';
byId('txtObservacion').className = 'inputHabilitado';

<?php if (isset($_GET['id_factura'])) { ?>
	xajax_cargarDcto('<?php echo $_GET['id_factura']; ?>');
<?php } ?>

</script>