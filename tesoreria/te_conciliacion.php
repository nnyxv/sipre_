<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_conciliacion"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_conciliacion.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Conciliación</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblNuevaConciliacion').style.display = 'none';
		byId('tblAnularConciliacion').style.display = 'none';
		
		if (verTabla == "tblNuevaConciliacion") {
			document.forms['frmNuevaConciliacion'].reset();
			
			xajax_formNuevaConciliacion();
			
			tituloDiv1 = 'Nueva Conciliación';
		} else if (verTabla == "tblAnularConciliacion") {
			document.forms['frmAnularConciliacion'].reset();
			byId('hddIdConciliacionEliminar').value = '';
			
			xajax_formAnularConciliacion(valor);
			
			tituloDiv1 = 'Anular Conciliación';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaBanco').style.display = 'none';
		byId('tblListaBanco1').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor, valor2);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblListaBanco") {			
			document.forms['frmBuscarBanco'].reset();
			xajax_listaBanco();
			
			tituloDiv2 = 'Bancos';
		} else if (verTabla == "tblListaBanco1") {			
			document.forms['frmBuscarBanco1'].reset();
			xajax_listaBanco1();
			
			tituloDiv2 = 'Bancos';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();			
			xajax_listaEmpresa();
			
			tituloDiv2 = 'Empresas';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblListaBanco") {			
			byId('txtCriterioBuscarBanco').focus();
			byId('txtCriterioBuscarBanco').select();
		} else if (verTabla == "tblListaBanco1") {			
			byId('txtCriterioBuscarBanco1').focus();
			byId('txtCriterioBuscarBanco1').select();
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		}
	}
	
	function validarFrmGuardarConciliacion(){
		if (validarCampo('txtNombreEmpresa','t','') == true
			&& validarCampo('txtNombreBanco1','t','') == true
			&& validarCampo('lstCuenta1','t','lista') == true
			&& validarCampo('txtFechaConciliacion','t','') == true
			&& validarCampo('txtSaldoBanco','t','monto') == true)
		{
			xajax_guardarConciliacion(xajax.getFormValues('frmNuevaConciliacion'));
		} else {
			validarCampo('txtNombreEmpresa','t','');
			validarCampo('txtNombreBanco1','t','');
			validarCampo('lstCuenta1','t','lista');
			validarCampo('txtFechaConciliacion','t','');
			validarCampo('txtSaldoBanco','t','monto');			
			
			alert("Los campos señalados en rojo son requeridos");

			return false;
		}
	}
	
	function limpiarAnulacionForm(){
		byId('nombreEmpresaAnular').innerHTML = '';
		byId('fechaConciliacionAnular').innerHTML = '';
		byId('nombreBancoAnular').innerHTML = '';
		byId('cuentaAnular').innerHTML = '';
		byId('restaAnular').innerHTML = '';
		byId('saldoCuentaAnular').innerHTML = '';
		byId('nuevoSaldoAnular').innerHTML = '';
		byId('tblclaveAnularConciliacion').style.display='';
		byId('tblAnularConciliacion').style.display='none'; 
	}
	
	function validarFrmAnularConciliacion(){
		if (validarCampo('hddIdConciliacionEliminar','t','') == true) {
			xajax_guardarAnularConciliacion(byId('hddIdConciliacionEliminar').value);
		} else {
			validarCampo('hddIdConciliacionEliminar','t','');
			
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
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_tesoreria.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Conciliación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblNuevaConciliacion', 0);">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
					</td>
				</tr>
				</table>
                
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" align="left"></td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Banco:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                    <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                                </td>
                                <td>
									<a onclick="abrirDivFlotante2(this, 'tblListaBanco');" rel="#divFlotante2" id="aListarBanco" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Nro. Cuenta:</td>
                    <td id="tdLstCuenta" align="left"></td>
					<td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarConciliacion(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('hddIdBanco').value = ''; byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListaConciliacion"></td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                        	<td><img src="../img/iconos/pencil.png"></td>
                            <td>Editar Conciliación</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"></td>
                            <td>Resumen Conciliación</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pdf_ico.png"></td>
                            <td>Detalle Conciliación</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_quitar.gif"></td>
                            <td>Anular Conciliación</td>
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
    
    <form id="frmNuevaConciliacion" name="frmNuevaConciliacion" onsubmit="return false;">
    <table border="0" id="tblNuevaConciliacion" style="display:none" width="420">
    <tr align="left">
    	<td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                        <td><a onclick="abrirDivFlotante2(this, 'tblListaEmpresa');" rel="#divFlotante2" id="aListarEmpresa" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button></a></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Banco:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtNombreBanco1" name="txtNombreBanco1" size="25" readonly="readonly"/><input type="hidden" id="hddIdBanco1" name="hddIdBanco1"/></td>
                        <td><a onclick="abrirDivFlotante2(this, 'tblListaBanco1');" rel="#divFlotante2" id="aListarBanco1" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button></a></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td  align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Cuenta:</td>
                <td id="tdLstCuenta1"></td>
            </tr>
            <tr align="left">
                <td  align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha a Conciliar:</td>
                <td><input type="text" name="txtFechaConciliacion" id="txtFechaConciliacion" readonly="readonly" /></td>
            </tr>
            <tr align="left">
                <td  align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Saldo Banco:</td>
                <td><input type="text" name="txtSaldoBanco" id="txtSaldoBanco" class="inputHabilitado"/></td>
            </tr>
            </table>
		</td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnGuardarConciliacion" name="btnGuardarConciliacion" onclick="validarFrmGuardarConciliacion();">Aceptar</button>
            <button type="button" id="btnCancelarConciliacion" name="btnCancelarConciliacion" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    </form>
        
    <form name="frmAnularConciliacion" id="frmAnularConciliacion"  onsubmit="return false;">
        <table border="0" id="tblAnularConciliacion" style="display:none;" width="350">                
		<tr align="left">
        	<td>
            	<table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="120">Empresa:&nbsp;</td>
                    <td id="nombreEmpresaAnular"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Fecha:&nbsp;</td>
                    <td id="fechaConciliacionAnular"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Banco:&nbsp;</td>
                    <td id="nombreBancoAnular"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Cuenta:&nbsp;</td>
                    <td id="cuentaAnular"></td>
                </tr>
                </table>
                
                <fieldset><legend class="legend">Saldo Conciliado</legend>
                <table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="170">Monto Conciliación(C-D):&nbsp;</td>
                    <td id="restaAnular"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Saldo Actual Cuenta:&nbsp;</td>
                    <td id="saldoCuentaAnular"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Nuevo Saldo al Anular:&nbsp;</td>
                    <td id="nuevoSaldoAnular"></td>
                </tr>
                </table>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="4" align="right" id="tdBotonesDiv"><hr>
                <button type="button" id="btnGuardarAnularConciliacion" name="btnGuardarAnularConciliacion" onclick="validarFrmAnularConciliacion();">Aceptar</button>
                <button type="button" id="btnCancelarAnularConciliacion" name="btnCancelarAnularConciliacion" class="close">Cancelar</button>
                
                <input type="hidden" id="hddIdConciliacionEliminar" name="hddIdConciliacionEliminar" value=""/>
                <input type="hidden" id="hddPasoClaveAnulacion" name="hddPasoClaveAnulacion" value=""/>
            </td>
        </tr>
        </table>
    </form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:2;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
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
                        <input type="hidden" id="hddIdConciliacion" name="hddIdConciliacion" readonly="readonly" size="30"/>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
                <button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cancelar</button>
			</td>
		</tr>
		</table>
    </form>
    
    <table border="0" id="tblListaEmpresa" style="display:none" width="610">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarEmpresa" id="frmBuscarEmpresa">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarEmpresa').click();" class="inputHabilitado" name="txtCriterioBuscarEmpresa" id="txtCriterioBuscarEmpresa"></td>
                    <td>
                        <button onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));" name="btnBuscarEmpresa" id="btnBuscarEmpresa" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListaEmpresa"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarEmpresa" name="btnCancelarEmpresa" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaBanco" style="display:none" width="610">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarBanco" id="frmBuscarBanco">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarBanco').click();" class="inputHabilitado" name="txtCriterioBuscarBanco" id="txtCriterioBuscarBanco"></td>
                    <td>
                        <button onclick="xajax_buscarBanco(xajax.getFormValues('frmBuscarBanco'));" name="btnBuscarBanco" id="btnBuscarBanco" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarBanco'].reset(); byId('btnBuscarBanco').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListaBanco"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarBanco" name="btnCancelarBanco" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaBanco1" style="display:none" width="610">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarBanco1" id="frmBuscarBanco1">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarBanco1').click();" class="inputHabilitado" name="txtCriterioBuscarBanco1" id="txtCriterioBuscarBanco1"></td>
                    <td>
                        <button onclick="xajax_buscarBanco1(xajax.getFormValues('frmBuscarBanco1'));" name="btnBuscarBanco1" id="btnBuscarBanco1" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarBanco1'].reset(); byId('btnBuscarBanco1').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListaBanco1"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarBanco1" name="btnCancelarBanco1" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaLstCuenta();
xajax_listaConciliacion(0,'fecha','DESC','<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>');

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

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

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

</script>