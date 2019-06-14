<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_registro_unidad_fisica_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_registro_unidad_fisica_list.php");

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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Registro de Unidad Física</title>
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
    
    <style>
    .check_titulo{
        border:1px solid #000000;
        padding:2px;
        text-align:center;
        margin-top:5px;
        margin-bottom:5px;
    }
    
    .check_data{
        padding:2px;
        padding-left:10px;
    }
	
    .check_data input[type=checkbox]{
        float:left;
    }
	
    .check_data:hover{
        background:#DFDFDF;
        cursor:pointer;
    }
    </style>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblPedido').style.display = 'none';
		byId('tblCarta').style.display = 'none';
		
		byId('trMotivo').style.display = 'none';
		
		if (verTabla == "tblPedido") {
			document.forms['frmPedido'].reset();
			byId('hddIdPedido').value = '';
			
			byId('txtCriterioPedido').className = 'inputHabilitado';
			
			xajax_formPedido(valor, valor2, xajax.getFormValues('frmPedido'));
			
			tituloDiv1 = 'Unidades del Pedido';
		} else if (verTabla == "tblCarta") {
			xajax_listaCarta(0,'idCartaSolicitud','ASC',valor);
			
			tituloDiv1 = 'Cartas de Solicitud';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPedido") {
			byId('txtCriterioPedido').focus();
			byId('txtCriterioPedido').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblImportarPedido').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		byId('tblRegistrarUnidad').style.display = 'none';
		byId('tblInspeccion').style.display = 'none';
		byId('tblAnularUnidad').style.display = 'none';
		
		if (verTabla == "tblImportarPedido") {
			if (validarCampo('txtIdProv','t','') == true) {
				document.forms['frmImportarPedido'].reset();
				byId('hddUrlArchivo').value = '';
				
				byId('fleUrlArchivo').className = 'inputHabilitado';
			} else {
				validarCampo('txtIdProv','t','');
				
				alert('Los campos señalados en rojo son requeridos');
				return false; 
			}
			
			tituloDiv2 = 'Importar Asignación';
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddIngresoEgresoMotivo').value = valor2;
			
			byId('btnBuscarMotivo').click();
			
			if (nomObjeto != null) {
				byId('btnCancelarListaMotivo').className = 'close';
				byId('btnCancelarListaMotivo').onclick = function() { }
			}
			
			tituloDiv2 = 'Motivos';
		} else if (verTabla == "tblRegistrarUnidad") {
			document.forms['frmRegistrarUnidad'].reset();
			byId('hddIdUnidadFisica').value = '';
			byId('hddIdPedidoCompraDetalle').value = '';
			
			byId('txtSerialCarroceria').className = 'inputCompletoHabilitado';
			byId('txtSerialMotor').className = 'inputHabilitado';
			byId('txtNumeroVehiculo').className = 'inputHabilitado';
			
			xajax_formRegistrarUnidad(valor);
			
			tituloDiv2 = 'Registrar Unidad Física';
		} else if (verTabla == "tblInspeccion") {
			document.forms['frmInspeccion'].reset();
			byId('hddIdPedidoCompraDetalleInspeccion').value = '';
			
			byId('txtPlaca').className = 'inputHabilitado';
			byId('lstEstadoUnidad').className = 'inputHabilitado';
			byId('txtDescripcionSiniestro').className = 'inputHabilitado';
			
			byId('fieldsetDescripcionSiniestro').style.display = 'none';
			
			xajax_formInspeccion(valor);
			
			tituloDiv2 = 'Inspección de Pre-Entrega';
		} else if (verTabla == "tblAnularUnidad") {
			document.forms['frmAnularUnidad'].reset();
			byId('hddIdPedidoCompraDetalleAnular').value = '';
			
			byId('txtIdMotivoAnular').className = 'inputHabilitado';
			
			byId('btnCancelarListaMotivo').className = '';
			byId('btnCancelarListaMotivo').onclick = function() {
				byId('tblListaMotivo').style.display = 'none';
				byId('tblRegistrarUnidad').style.display = 'none';
				byId('tblInspeccion').style.display = 'none';
				byId('tblAnularUnidad').style.display = '';
			}
			
			xajax_formAnularUnidad(valor);
			
			tituloDiv2 = 'Anular Unidad';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		} else if (verTabla == "tblRegistrarUnidad") {
			byId('txtSerialCarroceria').focus();
			byId('txtSerialCarroceria').select();
		} else if (verTabla == "tblInspeccion") {
			byId('txtPlaca').focus();
			byId('txtPlaca').select();
		} else if (verTabla == "tblAnularUnidad") {
			byId('txtIdMotivoAnular').focus();
			byId('txtIdMotivoAnular').select();
		}
	}
	
	function validarFrmAnularUnidad() {
		error = false;
		if (!(validarCampo('txtIdMotivoAnular','t','') == true)) {
			validarCampo('txtIdMotivoAnular','t','');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea anular este registro?') == true) {
				xajax_anularUnidad(xajax.getFormValues('frmAnularUnidad'), xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaPedidoCompra'));
			}
		}
	}
	
	function validarFrmImportarPedido() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarDcto(xajax.getFormValues('frmImportarPedido'), xajax.getFormValues('frmPedido'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmInspeccion() {
		error = false;
		if (!(validarCampo('lstAno','t','lista') == true
		&& validarCampo('lstEstadoUnidad','t','listaExceptCero') == true)) {
			validarCampo('lstAno','t','lista');
			validarCampo('lstEstadoUnidad','t','listaExceptCero');
			
			error = true;
		}
		
		if (byId('lstEstadoUnidad').value == 3) { // 2 = POR REGISTRAR, 3 = SINIESTRADO
			if (!(validarCampo('txtDescripcionSiniestro','t','') == true)) {
				validarCampo('txtDescripcionSiniestro','t','');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar la inspección?') == true) {
				xajax_guardarInspeccion(xajax.getFormValues('frmInspeccion'), xajax.getFormValues('frmPedido'));
			}
		}
	}
	
	function validarFrmPedido() {
		if (validarCampo('lstMoneda','t','lista') == true) {
			if (confirm('¿Seguro desea guardar el plan de pago?') == true) {
				xajax_guardarPlanPago(xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaPedidoCompra'));
			}
		} else {
			validarCampo('lstMoneda','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmRegistrarUnidad() {
		if (validarCampo('txtSerialCarroceria','t','') == true
		&& validarCampo('txtSerialMotor','t','') == true
		&& validarCampo('txtNumeroVehiculo','t','') == true
		&& validarCampo('lstCondicion','t','lista') == true
		&& validarCampo('lstAlmacen','t','lista') == true
		&& validarCampo('lstColorExterno1','t','lista') == true
		&& validarCampo('lstColorInterno1','t','lista') == true) {
			if (confirm('¿Seguro desea guardar la unidad física?') == true) {
				xajax_guardarUnidadFisica(xajax.getFormValues('frmRegistrarUnidad'), xajax.getFormValues('frmPedido'));
			}
		} else {
			validarCampo('txtSerialCarroceria','t','');
			validarCampo('txtSerialMotor','t','');
			validarCampo('txtNumeroVehiculo','t','');
			validarCampo('lstCondicion','t','lista');
			validarCampo('lstAlmacen','t','lista');
			validarCampo('lstColorExterno1','t','lista');
			validarCampo('lstColorInterno1','t','lista');
			
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
        	<td class="tituloPaginaVehiculos">Registro de Unidad Física</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
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
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarPedidoCompra(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPedidoCompra" name="frmListaPedidoCompra" style="margin:0">
				<div id="divListaPedidoCompra"></div>
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
                            <td><img src="../img/iconos/unidadesAsignadas.png"/></td><td>Forma de Pago Asignado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/unidadesPendientesPorAsignarPago.png"/></td><td>Forma de Pago Parcialmente Asignado</td>
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
                            <td><img src="../img/iconos/ico_examinar.png"/></td><td>Ver Detalle</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_asignar_plan_pago.png"/></td><td>Asignar Plan de Pago</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_green.png"/></td><td>Cartas de Solicitud</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Pedido PDF</td>
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
    
<form id="frmPedido" name="frmPedido" onsubmit="return false;" style="margin:0">
	<div id="tblPedido" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Proveedor:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'true', 'false');" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="15%">Asignación:</td>
                    <td width="45%"><input type="text" id="txtAsignacion" name="txtAsignacion" size="45"/></td>
                    <td align="right" class="tituloCampo" width="15%">Fecha Cierre Compra:</td>
                    <td width="25%"><input type="text" id="txtFechaCierreCompra" name="txtFechaCierreCompra" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Referencia:</td>
                    <td><input type="text" id="txtReferencia" name="txtReferencia" size="20" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo">Fecha Cierre Ventas:</td>
                    <td><input type="text" id="txtFechaCierreVenta" name="txtFechaCierreVenta" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
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
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr id="trMotivo" align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:</td>
                    <td colspan="3">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" onblur="xajax_asignarMotivo(this.value, 'Motivo', 'E', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarMotivo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'E');">
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
        </tr>
        <tr>
            <td>
            <fieldset id="fieldsetUnidadAsignacion"><legend class="legend">Vehículos de la Asignación <b>Ref. <span id="spanTituloUnidadAsignacion"></span></b></legend>
                <table width="100%">
                <tr>
                    <td>
                        <table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                            <a class="modalImg" id="aImportar" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblImportarPedido');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                            </a>
                            </td>
						</tr>
	                    </table>
                    	
                        <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" id="txtCriterioPedido" name="txtCriterioPedido"/></td>
                            <td>
                                <button type="submit" id="btnBuscarPedido" onclick="xajax_buscarPedidoDetalle(xajax.getFormValues('frmPedido'));">Buscar</button>
                                <button type="button" onclick="byId('txtCriterioPedido').value = ''; byId('btnBuscarPedido').click();">Limpiar</button>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td><div id="divListaUnidadPedido" style="width:100%"></div></td>
                </tr>
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/car_go.png"/></td><td>Vendido</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/car_error.png"/></td><td>Reservado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/cancel.png"/></td><td>Anulado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/accept.png"/></td><td>Disponible</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/siniestrado.png"/></td><td>Siniestrado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/almacen_buen_estado.png"/></td><td>Inspeccionado</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/transito.png"/></td><td>En Transito</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/error.png"/></td><td>No Registrado</td>
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
                                    <td><img src="../img/iconos/ico_vehiculo_normal.png"/></td><td>Vehículo Normal</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_vehiculo_flotilla.png"/></td><td>Vehículo por Flotilla</td>
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
                                    <td><img src="../img/iconos/registrar_estado_vehiculo.png"/></td><td>Registrar Unidad</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/registro_pdi.png"/></td><td>Inspección de Pre-Entrega</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/book_next.png"/></td><td>Registrar Compra</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/chk_list_act.png"/></td><td>Imprimir Inspección</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Registro de Compra PDF</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_print.png"/></td><td>Imprimir Comprobante de Retención</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/cancel.png"/></td><td>Anular Unidad</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdPedido" name="hddIdPedido" readonly="readonly"/>
                <button type="submit" id="btnGuardarPedido" name="btnGuardarPedido"  onclick="validarFrmPedido();">Guardar</button>
                <button type="button" id="btnCancelarPedido" name="btnCancelarPedido" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
    
    <table border="0" id="tblCarta" width="560">
    <tr>
    	<td><div id="divListaCarta" style="width:100%"></div></td>
    </tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarCarta" name="btnCancelarCarta" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
	
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
                        <td colspan="16">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
						<td>Nro. Pedido</td>
						<td>Id. Detalle</td>
                        <td>Id. Unidad </td>
                    	<td>Código Unidad</td>
                        <td>Id. Cliente </td>
                        <td><?php echo $spanClienteCxC; ?></td>
                        <td><?php echo $spanSerialCarroceria; ?></td>
                        <td><?php echo $spanSerialMotor; ?></td>
                        <td>Nro. Vehículo</td>
                        <td>Condición</td>
                        <td>Almacén</td>
                        <td>Color Externo 1</td>
                        <td>Color Interno 1</td>
                        <td>Año</td>
                        <td><?php echo $spanPlaca; ?></td>
                        <td>Estatus</td>
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
	
    <table border="0" id="tblListaMotivo" width="960">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
            <input type="hidden" id="hddIngresoEgresoMotivo" name="hddIngresoEgresoMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" class="inputHabilitado" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarMotivo').value = ''; byId('btnBuscarMotivo').click();">Limpiar</button>
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
	
<form id="frmRegistrarUnidad" name="frmRegistrarUnidad" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblRegistrarUnidad" width="760">
    <tr>
    	<td>
        <fieldset><legend class="legend"><span id="spanTituloRegistrarUnidad"></span></legend>
        	<table width="100%">
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
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
            	<td id="tdlstCondicion"></td>
            </tr>
            </table>
            
            <fieldset>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Almacén:</td>
                    <td id="tdlstAlmacen"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
                    <td id="tdlstColorExterno1" width="30%"></td>
                    <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                    <td id="tdlstColorExterno2" width="30%"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                    <td id="tdlstColorInterno1"></td>
                    <td align="right" class="tituloCampo">Color Interno 2:</td>
                    <td id="tdlstColorInterno2"></td>
                </tr>
                </table>
            </fieldset>
		</fieldset>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdUnidadFisica" name="hddIdUnidadFisica" readonly="readonly"/>
            <input type="hidden" id="hddIdPedidoCompraDetalle" name="hddIdPedidoCompraDetalle" readonly="readonly"/>
            <button type="submit" id="btnGuardarRegistrarUnidad" name="btnGuardarRegistrarUnidad"  onclick="validarFrmRegistrarUnidad();">Guardar</button>
            <button type="button" id="btnCancelarRegistrarUnidad" name="btnCancelarRegistrarUnidad" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
	
<form id="frmInspeccion" name="frmInspeccion" onsubmit="return false;" style="margin:0">
	<div id="tblInspeccion" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
            <fieldset><legend class="legend"><span id="spanTituloInspeccion"></span></legend>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Año:</td>
                    <td id="tdlstAno" width="30%"></td>
                    <td rowspan="3" width="50%">
                    <fieldset id="fieldsetDescripcionSiniestro"><legend class="legend">Descripcion del Siniestro</legend>
                        <textarea id="txtDescripcionSiniestro" name="txtDescripcionSiniestro" cols="50" rows="3"></textarea>
                    </fieldset>
                    </td>
                </tr>
                <tr id="trPlacaInspeccion" align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanPlaca; ?>:</td>
                    <td><input type="text" id="txtPlaca" name="txtPlaca"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                    <td>
                        <select id="lstEstadoUnidad" name="lstEstadoUnidad" onchange="xajax_asignarEstadoUnidad(xajax.getFormValues('frmInspeccion'));">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="2">Buen Estado</option>
                            <option value="3">Siniestrado</option>
                        </select>
                    </td>
                </tr>
                </table>
                
                <fieldset><div id="divInspeccion" style="width:100%"></div></fieldset>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdPedidoCompraDetalleInspeccion" name="hddIdPedidoCompraDetalleInspeccion" readonly="readonly"/>
                <button type="submit" id="btnGuardarInspeccion" name="btnGuardarInspeccion"  onclick="validarFrmInspeccion();">Guardar</button>
                <button type="button" id="btnCancelarInspeccion" name="btnCancelarInspeccion" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
	
<form id="frmAnularUnidad" name="frmAnularUnidad" onsubmit="return false;" style="margin:0">
	<div id="tblAnularUnidad" style="max-height:500px; overflow:auto; width:560px;">
        <table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr id="trMotivo" align="left">
                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Motivo:</td>
                    <td width="80%">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdMotivoAnular" name="txtIdMotivoAnular" onblur="xajax_asignarMotivo(this.value, 'MotivoAnular', 'I', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarMotivoAnular" rel="#divFlotante2" onclick="abrirDivFlotante2(null, 'tblListaMotivo', 'MotivoAnular', 'I');">
                                <button type="button" id="btnListarMotivoAnular" name="btnListarMotivoAnular" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtMotivoAnular" name="txtMotivoAnular" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdPedidoCompraDetalleAnular" name="hddIdPedidoCompraDetalleAnular" readonly="readonly"/>
                <button type="submit" id="btnGuardarAnularUnidad" name="btnGuardarAnularUnidad"  onclick="validarFrmAnularUnidad();">Guardar</button>
                <button type="button" id="btnCancelarAnularUnidad" name="btnCancelarAnularUnidad" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
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
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
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
xajax_listaPedidoCompra(0, "idAsignacion", "DESC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>