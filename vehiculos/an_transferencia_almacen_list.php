<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_transferencia_almacen_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_transferencia_almacen_list.php");

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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Transferencia de Almacén</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
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
		byId('tblPermiso').style.display = 'none';
		byId('tblUnidadFisica').style.display = 'none';
		byId('tblEstadoVenta').style.display = 'none';
		byId('tblVistaTransferenciaAlmacen').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv1 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblUnidadFisica") {
			document.forms['frmUnidadFisica'].reset();
			
			xajax_formUnidadFisica(valor, valor2);
			xajax_formUnidadFisicaAgregado(valor, xajax.getFormValues('frmUnidadFisica'));
			
			tituloDiv1 = 'Ver Unidad Física';
		} else if (verTabla == "tblEstadoVenta") {
			document.forms['frmEstadoVenta'].reset();
			
			byId('txtObservacion').className = 'inputHabilitado';
			byId('hddIdEmpleadoAutorizado').className = 'inputHabilitado';
			
			xajax_formEstadoVenta(xajax.getFormValues('frmUnidadFisica'));
			
			tituloDiv1 = 'Estado de Venta';
		} else if (verTabla == "tblVistaTransferenciaAlmacen") {
			byId('iframeVistaTransferenciaAlmacen').src = 'an_transferencia_almacen_imp.php?id=' + valor;
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblUnidadFisica") {
			byId('txtIdUnidadFisica').focus();
			byId('txtIdUnidadFisica').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaEmpleado').style.display = 'none';
		
		if (verTabla == "tblListaEmpleado") {
			document.forms['frmBuscarEmpleado'].reset();
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			
			byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
			
			byId('btnBuscarEmpleado').click();
				
			tituloDiv2 = 'Empleados';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpleado") {
			byId('txtCriterioBuscarEmpleado').focus();
			byId('txtCriterioBuscarEmpleado').select();
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
	
	function validarFrmEstadoVenta() {
		if (validarCampo('txtNombreEmpleadoElaborado','t','') == true
		&& validarCampo('txtNombreEmpleadoAutorizado','t','') == true) {
			xajax_guardarEstadoVenta(xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmEstadoVenta'));
		} else {
			validarCampo('txtNombreEmpleadoElaborado','t','');
			validarCampo('txtNombreEmpleadoAutorizado','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmUnidadFisica() {
		if (byId('hddAccionFrmUnidadFisica').value == 'an_estado_venta_unidad_fisica_form.php') {
			if (validarCampo('txtIdUnidadFisica','t','') == true
			&& validarCampo('lstEstadoVenta','t','lista') == true) {
				if (byId('hddEstadoVenta').value != byId('lstEstadoVenta').value) {
					abrirDivFlotante1(null, 'tblPermiso', 'an_estado_venta_unidad_fisica_form.php');
				} else {
					if (byId('hddEstadoVenta').value == byId('lstEstadoVenta').value) {
						byId('lstEstadoVenta').className = "inputErrado";
					}
					
					alert("El campo señalado en rojo no ha variado");
				}
			} else {
				validarCampo('txtIdUnidadFisica','t','');
				validarCampo('lstEstadoVenta','t','lista')
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		} else if (byId('hddAccionFrmUnidadFisica').value == 'an_transferencia_almacen_form.php') {
			if (validarCampo('txtIdUnidadFisica','t','') == true
			&& validarCampo('lstAlmacen','t','lista') == true) {
				if (byId('hddIdAlmacen').value != byId('lstAlmacen').value) {
					abrirDivFlotante1(null, 'tblPermiso', 'an_transferencia_almacen_form.php');
				} else {
					if (byId('hddIdAlmacen').value == byId('lstAlmacen').value) {
						byId('lstAlmacen').className = "inputErrado";	
					}
					
					alert("El campo señalado en rojo no ha variado");
				}
			} else {
				validarCampo('txtIdUnidadFisica','t','');
				validarCampo('lstAlmacen','t','lista')
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Transferencia de Almacén</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscar"></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Estado de Compra:</td>
                    <td id="tdlstEstadoCompraBuscar"></td>
                	<td align="right" class="tituloCampo">Estado de Venta:</td>
                    <td id="tdlstEstadoVentaBuscar"></td>
                    <td align="right" class="tituloCampo">Condición:</td>
                    <td id="tdlstCondicionBuscar"></td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo">Almacén:</td>
                    <td id="tdlstAlmacenBuscar"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" name="txtCriterio" id="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaUnidadFisica" name="frmListaUnidadFisica" style="margin:0">
            	<div id="divListaUnidadFisica" style="width:100%"></div>
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
                            <td><img src="../img/iconos/page_refresh.png"/></td><td>Cambiar Estado de Venta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_movimiento_almacen.gif"/></td><td>Transferencia de Almacén</td>
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
    
<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso" style="display:none" width="560">
    <tr>
    	<td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%">Acción:</td>
                <td width="75%"><input type="text" id="txtDescripcionPermiso" name="txtDescripcionPermiso" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td>
                    <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" onclick="validarFrmPermiso();">Aceptar</button>
            <button type="button" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>

<form id="frmUnidadFisica" name="frmUnidadFisica" onsubmit="return false;" style="margin:0">
    <div id="tblUnidadFisica" style="max-height:500px; overflow:auto; width:960px;">
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
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td width="30%">
                            	<input type="text" id="txtNombreUnidadBasica" name="txtNombreUnidadBasica" readonly="readonly" size="24"/>
				            	<input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica"/>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasica" name="txtClaveUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcion" name="txtDescripcion" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasica" name="txtMarcaUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasica" name="txtModeloUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasica" name="txtVersionUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td><input type="text" id="txtAno" name="txtAno" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
                            <td><input type="text" id="txtCondicion" name="txtCondicion" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacion" name="txtFechaFabricacion" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometraje" name="txtKilometraje" onblur="setFormatoRafk(this,0);" onkeypress="return validarSoloNumeros(event);" size="24" style="text-align:right"/></td>                            
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="2" valign="top">
                        <table border="0" width="100%">
                        <tr>
                            <td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Id Unidad Física:</td>
                            <td width="60%"><input type="text" id="txtIdUnidadFisica" name="txtIdUnidadFisica" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td id="tdlstEmpresaUnidadFisica"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Almacén:</td>
                            <td>
                            	<div id="tdlstAlmacen"></div>
                                <input type="hidden" id="hddIdAlmacen" name="hddIdAlmacen">
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td><input type="text" id="txtEstadoCompra" name="txtEstadoCompra" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Venta:</td>
                            <td>
                            	<div id="tdlstEstadoVenta"></div>
                                <input type="hidden" id="hddEstadoVenta" name="hddEstadoVenta" readonly="readonly">
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
                            <td width="30%"><input type="text" id="txtColorExterno1" name="txtColorExterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                            <td width="30%"><input type="text" id="txtColorExterno2" name="txtColorExterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                            <td><input type="text" id="txtColorInterno1" name="txtColorInterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td><input type="text" id="txtColorInterno2" name="txtColorInterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td valign="top">
                    <fieldset><legend class="legend">Seriales</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialCarroceria; ?>:</td>
                            <td width="30%">
                            <div style="float:left">
                                <input type="text" id="txtSerialCarroceria" name="txtSerialCarroceria" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>" readonly="readonly"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotor" name="txtSerialMotor" readonly="readonly"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculo" name="txtNumeroVehiculo" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRegistroLegalizacion; ?>:</td>
                            <td><input type="text" id="txtRegistroLegalizacion" name="txtRegistroLegalizacion" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederal" name="txtRegistroFederal" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top">
                    <fieldset><legend class="legend">Trade-In</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Allowance:</td>
                            <td align="right" width="55%">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtAllowance" name="txtAllowance" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularMonto(this.id);" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto por el cual será recibido" /></td>
                                </tr>
                                <tr id="trtxtAllowanceAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtAllowanceAnt" name="txtAllowanceAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>ACV:</td>
                            <td align="right">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtAcv" name="txtAcv" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularMonto();" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Valor en el inventario" /></td>
								</tr>
                                <tr id="trtxtAcvAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtAcvAnt" name="txtAcvAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
								</tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Payoff:</td>
                            <td align="right">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtPayoff" name="txtPayoff" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularMonto();" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto total adeudado" /></td>
								</tr>
                                <tr id="trtxtPayoffAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtPayoffAnt" name="txtPayoffAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
								</tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Crédito Neto:</td>
                            <td align="right">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtCreditoNeto" name="txtCreditoNeto" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Crédito Neto" /></td>
								</tr>
                                <tr id="trtxtCreditoNetoAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtCreditoNetoAnt" name="txtCreditoNetoAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
								</tr>
                                </table>
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
            <td align="right"><hr>
                <input type="hidden" id="hddAccionFrmUnidadFisica" name="hddAccionFrmUnidadFisica"/>
            	<a class="modalImg" id="aGuardarUnidadFisica" rel="#divFlotante2"></a>
                <button type="button" id="btnGuardarUnidadFisica" name="btnGuardarUnidadFisica" onclick="validarFrmUnidadFisica();">Guardar</button>
                <button type="button" id="btnCancelarUnidadFisica" name="btnCancelarUnidadFisica" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
    
<form id="frmEstadoVenta" name="frmEstadoVenta" style="margin:0px">
    <table border="0" id="tblEstadoVenta" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Empleado:</td>
        <td width="86%">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="hddIdEmpleadoElaborado" name="hddIdEmpleadoElaborado" readonly="readonly" size="6" style="text-align:right;"/></td>
                <td></td>
                <td><input type="text" id="txtNombreEmpleadoElaborado" name="txtNombreEmpleadoElaborado" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Autorizado:</td>
        <td>
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="hddIdEmpleadoAutorizado" name="hddIdEmpleadoAutorizado" onblur="xajax_asignarEmpleado(this.value, 'EmpleadoAutorizado', 'false');" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" id="aListarEmpleado" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpleado', 'EmpleadoAutorizado')">
                    <button type="button" id="btnListarEmpleado" name="btnListarEmpleado" title="Listar"><img src="../img/iconos/help.png"/></button>
                </a>
                </td>
                <td><input type="text" id="txtNombreEmpleadoAutorizado" name="txtNombreEmpleadoAutorizado" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td valign="top" width="50%">
                <fieldset><legend class="legend">Vale Salida</legend>
                    <table width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="20%">Empresa:</td>
                        <td width="80%">
                            <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtIdEmpresaValeSalida" name="txtIdEmpresaValeSalida" readonly="readonly" size="6" style="text-align:right;"/></td>
                                <td></td>
                                <td><input type="text" id="txtEmpresaValeSalida" name="txtEmpresaValeSalida" readonly="readonly" size="45"/></td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                        <td>
                            <select id="lstTipoMovimientoSalida" name="lstTipoMovimientoSalida">
                                <option>[ Seleccione ]</option>
                                <option value="2">ENTRADA</option>
                                <option value="4">SALIDA</option>
                            </select>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                        <td id="tdlstClaveMovimientoSalida"></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Almacén:</td>
                        <td>
                            <input type="text" id="txtAlmacenOrigen" name="txtAlmacenOrigen" readonly="readonly" size="30"/>
                            <input type="hidden" id="hddIdAlmacenOrigen" name="hddIdAlmacenOrigen"/>
                        </td>
                    </tr>
                    </table>
                </fieldset>
                </td>
                <td valign="top" width="50%">
                <fieldset><legend class="legend">Vale Entrada</legend>
                    <table width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="20%">Empresa:</td>
                        <td width="80%">
                            <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtIdEmpresaValeEntrada" name="txtIdEmpresaValeEntrada" readonly="readonly" size="6" style="text-align:right;"/></td>
                                <td></td>
                                <td><input type="text" id="txtEmpresaValeEntrada" name="txtEmpresaValeEntrada" readonly="readonly" size="45"/></td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                        <td>
                            <select id="lstTipoMovimientoEntrada" name="lstTipoMovimientoEntrada">
                                <option>[ Seleccione ]</option>
                                <option value="2">ENTRADA</option>
                                <option value="4">SALIDA</option>
                            </select>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                        <td id="tdlstClaveMovimientoEntrada"></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Almacén:</td>
                        <td>
                            <input type="text" id="txtAlmacenDestino" name="txtAlmacenDestino" readonly="readonly" size="30"/>
                            <input type="hidden" id="hddIdAlmacenDestino" name="hddIdAlmacenDestino"/>
                        </td>
                    </tr>
                    </table>
                </fieldset>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <table>
                    <tr align="left">
                        <td class="tituloCampo">Observación:</td>
                    </tr>
                    <tr align="left">
                        <td><textarea id="txtObservacion" name="txtObservacion" cols="55" rows="3"></textarea></td>
                    </tr>
                    </table>
                </td>
                <td valign="top">
                    <table border="0" width="100%">
                    <tr align="right">
                        <td class="tituloCampo" width="36%">Subtotal:</td>
                        <td style="border-top:1px solid;" width="24%"></td>
                        <td style="border-top:1px solid;" width="13%"></td>
                        <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                        <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <button type="button" id="btnGuardarEstadoVenta" name="btnGuardarEstadoVenta" onclick="validarFrmEstadoVenta();">Guardar</button>
            <button type="button" id="btnCancelarEstadoVenta" name="btnCancelarEstadoVenta" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblVistaTransferenciaAlmacen">
    <tr>
    	<td>
        <iframe id="iframeVistaTransferenciaAlmacen" frameborder="0" marginheight="0" marginwidth="0" height="300" src="an_transferencia_almacen_imp.php" width="960"></iframe>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddImpresion" name="hddImpresion"/>
            <button type="button" onclick="
            if (byId('hddImpresion').value == 'true') {
            	window.location.href='an_transferencia_almacen_list.php';
			} else {
            	alert('Debe Imprimir el Documento');
			}">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpleado" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" onkeyup="byId('btnBuscarEmpleado').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpleado" name="frmListaEmpleado" onsubmit="return false;" style="margin:0">
            <div id="divListaEmpleado" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpleado" name="btnCancelarListaEmpleado" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
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

//var lstEstadoCompraBuscar = $.map($("#lstEstadoCompraBuscar option:selected"), function (el, i) { return el.value; });
//var lstEstadoVentaBuscar = $.map($("#lstEstadoVentaBuscar option:selected"), function (el, i) { return el.value; });
//var lstCondicionBuscar = $.map($("#lstCondicionBuscar option:selected"), function (el, i) { return el.value; });
//var lstAlmacenBuscar = $.map($("#lstAlmacenBuscar option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstAlmacenBuscar(\'lstAlmacenBuscar\', this.value); byId(\'btnBuscar\').click();\"');
xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "Buscar", "true", "", "", "byId('btnBuscar').click();");
xajax_cargaLstEstadoCompraBuscar('lstEstadoCompraBuscar');
xajax_cargaLstEstadoVentaBuscar('lstEstadoVentaBuscar');
xajax_cargaLstCondicionBuscar();
xajax_cargaLstAlmacenBuscar('lstAlmacenBuscar', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaUnidadFisica(0, 'CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>