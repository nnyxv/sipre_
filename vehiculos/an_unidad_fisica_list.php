<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_unidad_fisica_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_unidad_fisica_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Unidad Física</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css" />
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
		byId('tblUnidadFisica').style.display = 'none';
		
		byId('trSistemaGNV').style.display = 'none';
		
		if (verTabla == "tblUnidadFisica") {
			document.forms['frmUnidadFisica'].reset();
			
			byId('txtNombreUnidadBasica').style.display = '';
			byId('tdlstUnidadBasica').style.display = 'none';
			byId('txtPlaca').readOnly = true;
			byId('txtPlaca').className = 'inputInicial';
			byId('txtFechaFabricacion').readOnly = true;
			byId('txtFechaFabricacion').className = 'inputInicial';
			byId('txtKilometraje').readOnly = true;
			byId('txtKilometraje').className = 'inputInicial';
			byId('txtFechaExpiracionMarbete').readOnly = true;
			byId('txtFechaExpiracionMarbete').className = 'inputInicial';
			
			byId('txtSerialCarroceria').readOnly = true;
			byId('txtSerialCarroceria').className = 'inputCompleto';
			byId('txtSerialMotor').readOnly = true;
			byId('txtSerialMotor').className = 'inputInicial';
			byId('txtNumeroVehiculo').readOnly = true;
			byId('txtNumeroVehiculo').className = 'inputInicial';
			byId('txtTituloVehiculo').readOnly = true;
			byId('txtTituloVehiculo').className = 'inputInicial';
			byId('txtRegistroLegalizacion').readOnly = true;
			byId('txtRegistroLegalizacion').className = 'inputInicial';
			byId('txtRegistroFederal').readOnly = true;
			byId('txtRegistroFederal').className = 'inputInicial';
			
			byId('txtPaisOrigen').style.display = '';
			byId('tdlstPaisOrigen').style.display = 'none';
			byId('txtUso').style.display = '';
			byId('tdlstUso').style.display = 'none';
			
			byId('btnGuardarUnidadFisica').style.display = 'none';
			
			if (valor2 == 'editar') {
				byId('txtNombreUnidadBasica').style.display = 'none';
				byId('tdlstUnidadBasica').style.display = '';
				byId('txtPlaca').className = 'inputHabilitado';
				byId('txtPlaca').readOnly = false;
				byId('txtFechaFabricacion').className = 'inputHabilitado';
				byId('txtFechaFabricacion').readOnly = false;
				byId('txtKilometraje').className = 'inputHabilitado';
				byId('txtKilometraje').readOnly = false;
				byId('txtFechaExpiracionMarbete').className = 'inputHabilitado';
				byId('txtFechaExpiracionMarbete').readOnly = false;
				
				byId('txtSerialCarroceria').className = 'inputCompletoHabilitado';
				byId('txtSerialCarroceria').readOnly = false;
				byId('txtSerialMotor').className = 'inputHabilitado';
				byId('txtSerialMotor').readOnly = false;
				byId('txtNumeroVehiculo').className = 'inputHabilitado';
				byId('txtNumeroVehiculo').readOnly = false;
				byId('txtTituloVehiculo').className = 'inputHabilitado';
				byId('txtTituloVehiculo').readOnly = false;
				byId('txtRegistroLegalizacion').className = 'inputHabilitado';
				byId('txtRegistroLegalizacion').readOnly = false;
				byId('txtRegistroFederal').className = 'inputHabilitado';
				byId('txtRegistroFederal').readOnly = false;
				
				byId('txtPaisOrigen').style.display = 'none';
				byId('tdlstPaisOrigen').style.display = '';
				byId('txtUso').style.display = 'none';
				byId('tdlstUso').style.display = '';
				
				byId('btnGuardarUnidadFisica').style.display = '';
				
				jQuery(function($){
					$("#txtFechaFabricacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
					$("#txtFechaExpiracionMarbete").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaFabricacion",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"orange"
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaExpiracionMarbete",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"orange"
				});
			} else {
				jQuery(function($){
					$("#txtFechaFabricacion").unmaskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
					$("#txtFechaExpiracionMarbete").unmaskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaFabricacion",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"orange"
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaExpiracionMarbete",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"orange"
				});
			}
			
			xajax_formUnidadFisica(valor, valor2);
			xajax_formUnidadFisicaAgregado(valor, xajax.getFormValues('frmUnidadFisica'));
			
			tituloDiv1 = 'Ver Unidad Física';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblUnidadFisica") {
			byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();
		}
	}
	
	function calcularMonto(objAccion){
	}
	
	function validarFrmUnidadFisica() {
		if (validarCampo('lstUnidadBasica','t','lista') == true
		&& validarCampo('lstAno','t','lista') == true
		&& validarCampo('lstCondicion','t','lista') == true
		&& validarCampo('txtFechaFabricacion','t','') == true
		&& validarCampo('txtKilometraje','t','numPositivo') == true
		&& validarCampo('lstColorExterno1','t','lista') == true
		&& validarCampo('lstColorInterno1','t','lista') == true
		&& validarCampo('txtSerialCarroceria','t','') == true
		&& validarCampo('txtSerialMotor','t','') == true
		&& validarCampo('txtNumeroVehiculo','t','') == true
		&& validarCampo('txtTituloVehiculo','t','') == true
		&& validarCampo('txtRegistroLegalizacion','t','') == true
		&& validarCampo('txtRegistroFederal','t','') == true
		&& validarCampo('lstPaisOrigen','t','lista') == true
		&& validarCampo('lstUso','t','lista') == true) {
			xajax_guardarUnidadFisica(xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'));
		} else {
			validarCampo('lstUnidadBasica','t','lista');
			validarCampo('lstAno','t','lista');
			validarCampo('lstCondicion','t','lista');
			validarCampo('txtFechaFabricacion','t','');
			validarCampo('txtKilometraje','t','numPositivo');
			validarCampo('lstColorExterno1','t','lista');
			validarCampo('lstColorInterno1','t','lista');
			validarCampo('txtSerialCarroceria','t','');
			validarCampo('txtSerialMotor','t','');
			validarCampo('txtNumeroVehiculo','t','');
			validarCampo('txtTituloVehiculo','t','');
			validarCampo('txtRegistroLegalizacion','t','');
			validarCampo('txtRegistroFederal','t','');
			validarCampo('lstPaisOrigen','t','lista');
			validarCampo('lstUso','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
        	<td class="tituloPaginaVehiculos">Unidad Física</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_imprimirUnidadFisica(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
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
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscar"></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAnoBuscar"></td>
                	<td align="right" class="tituloCampo">Estado de Compra:</td>
                    <td id="tdlstEstadoCompraBuscar"></td>
                	<td align="right" class="tituloCampo">Estado de Venta:</td>
                    <td id="tdlstEstadoVentaBuscar"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Condición:</td>
                    <td id="tdlstCondicionBuscar"></td>
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
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver Unidad Física</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Unidad Física</td>
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
                                <div id="tdlstUnidadBasica"></div>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasica" name="txtClaveUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcion" name="txtDescripcion" cols="20" readonly="readonly" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td>
                            	<input type="text" id="txtMarcaUnidadBasica" name="txtMarcaUnidadBasica" readonly="readonly" size="24"/>
                                <input type="hidden" id="hddIdMarcaUnidadBasica" name="hddIdMarcaUnidadBasica"/>
							</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td>
                            	<input type="text" id="txtModeloUnidadBasica" name="txtModeloUnidadBasica" readonly="readonly" size="24"/>
                                <input type="hidden" id="hddIdModeloUnidadBasica" name="hddIdModeloUnidadBasica"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td>
                            	<input type="text" id="txtVersionUnidadBasica" name="txtVersionUnidadBasica" readonly="readonly" size="24"/>
                                <input type="hidden" id="hddIdVersionUnidadBasica" name="hddIdVersionUnidadBasica"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td><div id="tdlstAno"></div>
							</td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
                            <td><div id="tdlstCondicion"></div>
							</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlaca" name="txtPlaca" size="24"/></td>
                            <td align="right" class="tituloCampo">Tipo Tablilla</td>
                            <td id="tdlstTipoTablilla"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacion" name="txtFechaFabricacion" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometraje" name="txtKilometraje" onblur="setFormatoRafk(this,0);" onkeypress="return validarSoloNumeros(event);" size="24" style="text-align:right"/></td>   
                            <td align="right" class="tituloCampo">Expiración Marbete:</td>
                            <td><input type="text" id="txtFechaExpiracionMarbete" name="txtFechaExpiracionMarbete" autocomplete="off" size="10" style="text-align:center"/></td>                         
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
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Almacén:</td>
                            <td><input type="text" id="txtAlmacen" name="txtAlmacen" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td><input type="text" id="txtEstadoCompra" name="txtEstadoCompra" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Venta:</td>
                            <td><input type="text" id="txtEstadoVenta" name="txtEstadoVenta" readonly="readonly" size="24" style="text-align:center"/></td>
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
                            <td width="30%"><div id="tdlstColorExterno1"></div>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                            <td width="30%"><div id="tdlstColorExterno2"></div>
							</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                            <td><div id="tdlstColorInterno1"></div>
							</td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td><div id="tdlstColorInterno2"></div>
							</td>
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
                                <input type="text" id="txtSerialCarroceria" name="txtSerialCarroceria" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotor" name="txtSerialMotor"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculo" name="txtNumeroVehiculo"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Titulo del Vehiculo:</td>
                            <td><input type="text" id="txtTituloVehiculo" name="txtTituloVehiculo"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRegistroLegalizacion; ?>:</td>
                            <td><input type="text" id="txtRegistroLegalizacion" name="txtRegistroLegalizacion"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederal" name="txtRegistroFederal"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top">
                    <fieldset><legend class="legend">Trade-In</legend>
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="45%">Id Trade-In:</td>
                            <td width="55%"><input type="text" id="txtIdTradeIn" name="txtIdTradeIn" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:center"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Allowance:</td>
                            <td>
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
                        <tr align="right">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>ACV:</td>
                            <td>
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
                        <tr align="right">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Payoff:</td>
                            <td>
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
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Crédito Neto:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtCreditoNeto" name="txtCreditoNeto" class="inputCompleto" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
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
                <tr>
                    <td colspan="2">
                    <div class="wrap">
                        <!-- the tabs -->
                        <ul class="tabs">
                            <li><a href="#">Especif. Técnicas</a></li>
                            <li><a href="#">Agregados</a></li>
                            <li><a href="#">Historial Trade-In</a></li>
                            <li><a href="#">Historial Kardex</a></li>
                        </ul>
                        
                        <!-- tab "panes" -->
                        <div class="pane">
                            <table width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Origen:</td>
                                <td><input type="text" id="txtPaisOrigen" name="txtPaisOrigen" readonly="readonly"/>
                                    <div id="tdlstPaisOrigen"></div>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="12%">Clase:</td>
                                <td width="21%"><input type="text" id="txtClase" name="txtClase" readonly="readonly"/></td>
                                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Uso:</td>
                                <td width="21%"><input type="text" id="txtUso" name="txtUso" readonly="readonly"/>
                                    <div id="tdlstUso"></div>
                                </td>
                                <td align="right" class="tituloCampo" width="12%"># Puertas:</td>
                                <td width="22%"><input type="text" id="txtNumeroPuertas" name="txtNumeroPuertas" readonly="readonly" style="text-align:right"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo"># Cilindros:</td>
                                <td><input type="text" id="txtNumeroCilindros" name="txtNumeroCilindros" readonly="readonly" style="text-align:right"/></td>
                                <td align="right" class="tituloCampo">Cilindrada Cm3:</td>
                                <td><input type="text" id="txtCilindrada" name="txtCilindrada" readonly="readonly" style="text-align:right"/></td>
                                <td align="right" class="tituloCampo">Caballos de Fuerza (HP):</td>
                                <td><input type="text" id="txtCaballosFuerza" name="txtCaballosFuerza" readonly="readonly" style="text-align:right"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Transmisión:</td>
                                <td><input type="text" id="txtTransmision" name="txtTransmision" readonly="readonly"/></td>
                                <td align="right" class="tituloCampo">Combustible:</td>
                                <td><input type="text" id="txtCombustible" name="txtCombustible" readonly="readonly"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Capacidad (Kg):</td>
                                <td><input type="text" id="txtCapacidad" name="txtCapacidad" readonly="readonly" style="text-align:right"/></td>
                                <td align="right" class="tituloCampo">Unidad:</td>
                                <td><input type="text" id="txtUnidad" name="txtUnidad" readonly="readonly"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Años de Garantía:</td>
                                <td><input type="text" id="txtAnoGarantia" name="txtAnoGarantia" readonly="readonly" style="text-align:right"/></td>
                                <td align="right" class="tituloCampo"><?php echo $spanKilometraje; ?> de Garantía:</td>
                                <td><input type="text" id="txtKmGarantia" name="txtKmGarantia" readonly="readonly" style="text-align:right"/></td>
                            </tr>
                            </table>
                        </div>
                        
                        <div class="pane">
                            <div id="divListaUniFisAgregado" style="width:100%">
                                <table border="0" class="texto_9px" width="100%">
                                <tr class="tituloColumna">
                                    <td></td>
                                    <td width="4%">Nro.</td>
                                    <td width="8%">Tipo de Dcto.</td>
                                    <td width="6%">Fecha Registro</td>
                                    <td width="6%">Nro. Dcto.</td>
                                    <td width="52%">Cliente / Proveedor</td>
                                    <td width="8%">Estado Dcto.</td>
                                    <td width="8%">Saldo Dcto.</td>
                                    <td width="8%">Total Dcto.</td>
                                    <td></td>
                                </tr>
                                <tr id="trItmPie" align="right" class="trResaltarTotal" height="24">
                                    <td class="tituloCampo" colspan="8">Total Agregados:</td>
                                    <td><input type="text" id="txtTotalAgregado" name="txtTotalAgregado" class="inputSinFondo" readonly="readonly"/></td>
                                    <td></td>
                                </tr>
                               </table>
                            </div>
                        </div>
                        
                        <div class="pane">
                        	<div id="divListaTradeInAuditoria" style="width:100%"></div>
                        </div>
                        
                        <div class="pane">
                        	<div id="divListaKardex" style="width:100%"></div>
                        </div>
					</div>
                    </td>
                </tr>
                <tr id="trSistemaGNV">
                	<td colspan="2">
                    <fieldset><legend class="legend">Sistema GNV</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="14%">Serial 1:</td>
                            <td width="19%"><input type="text" id="txtSerial1" name="txtSerial1" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo" width="14%">Código Único:</td>
                            <td width="19%"><input type="text" id="txtCodigoUnico" name="txtCodigoUnico" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo" width="14%">Marca Kit:</td>
                            <td width="20%"><input type="text" id="txtMarcaKit" name="txtMarcaKit" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Modelo Regulador:</td>
                            <td><input type="text" id="txtModeloRegulador" name="txtModeloRegulador" readonly="readonly"/></td>
                        	<td align="right" class="tituloCampo">Serial Regulador:</td>
                            <td><input type="text" id="txtSerialRegulador" name="txtSerialRegulador" readonly="readonly"/></td>
                        	<td align="right" class="tituloCampo">Marca Cilindro:</td>
                            <td><input type="text" id="txtMarcaCilindro" name="txtMarcaCilindro" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Capacidad Cilindro (NG):</td>
                            <td><input type="text" id="txtCapacidadCilindro" name="txtCapacidadCilindro" readonly="readonly"/></td>
                        	<td align="right" class="tituloCampo">Fecha Elab. Cilindro:</td>
                            <td><input type="text" id="txtFechaCilindro" name="txtFechaCilindro" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr />
                <button type="submit" id="btnGuardarUnidadFisica" name="btnGuardarUnidadFisica"  onclick="validarFrmUnidadFisica();">Guardar</button>
                <button type="button" id="btnCancelarUnidadFisica" name="btnCancelarUnidadFisica" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

//var lstEstadoCompraBuscar = $.map($("#lstEstadoCompraBuscar option:selected"), function (el, i) { return el.value; });
//var lstEstadoVentaBuscar = $.map($("#lstEstadoVentaBuscar option:selected"), function (el, i) { return el.value; });
//var lstCondicionBuscar = $.map($("#lstCondicionBuscar option:selected"), function (el, i) { return el.value; });
//var lstAlmacenBuscar = $.map($("#lstAlmacenBuscar option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstAlmacenBuscar(\'lstAlmacenBuscar\', this.value); byId(\'btnBuscar\').click();\"');
xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "Buscar", "true", "", "", "byId('btnBuscar').click();");
xajax_cargaLstAnoBuscar();
xajax_cargaLstEstadoCompraBuscar('lstEstadoCompraBuscar', '');
xajax_cargaLstEstadoVentaBuscar('lstEstadoVentaBuscar', '');
xajax_cargaLstCondicionBuscar();
xajax_cargaLstAlmacenBuscar('lstAlmacenBuscar', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaUnidadFisica(0, 'CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>