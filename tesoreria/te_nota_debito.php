<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_nota_debito"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_nota_debito.php");

//modificado Ernesto
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	require("../contabilidad/GenerarEnviarContabilidadDirecto.php");
}
//Fin modificado Ernesto

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Notas de Débito</title>
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
		document.forms['frmNotaDebito'].reset();
		$('.trItemMotivo').remove();
		if (valor > 0) {
			tituloDiv1 = 'Ver Nota de Débito';
		} else {			
			tituloDiv1 = 'Nueva Nota de Débito';
		}
		xajax_formNotaDebito(valor);
		
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaBanco').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
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
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();			
			xajax_listaEmpresa();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();			
			xajax_buscarMotivo();
			
			tituloDiv2 = 'Motivos';
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
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function eliminarMotivo(obj){
		$(obj).closest('tr').remove();
	}
	
	function eliminarMotivoLote(){
		$('input[name="cbxItm[]"]:enabled:checked').closest('tr').remove();
	}

	function validarFrmInsertar(){	
		error = false;
		objArrMotivoPrecio = $('input[name="txtPrecioItm[]"]');
		
		if(objArrMotivoPrecio.length == 0){
			alert("Debes agregar almenos un motivo");
			desbloquearGuardado();
			return false;
		}
		
		objArrMotivoPrecio.each(function(){
			if($.trim(this.value).length == 0 || this.value <= 0){				
				this.className = 'inputCompletoErrado';
				error = true;
			}else{
				this.className = 'inputCompletoHabilitado';
			}
		});
		
		totalMotivo = 0;
		objArrMotivoPrecio.each(function(){
			totalMotivo += parseFloat(this.value.replace(",", ""));
		});
		
		totalDcto = parseFloat(byId('txtImporteMovimiento').value.replace(",", ""));
		
		if (!(validarCampo('txtNombreEmpresa','t','') == true
			&& validarCampo('txtNombreBanco','t','') == true
			&& validarCampo('txtObservacion','t','') == true
			&& validarCampo('lstEstadoDcto','t','lista') == true
			&& validarCampo('txtSaldoCuenta','t','') == true 
			&& validarCampo('txtFechaRegistro','t','') == true 
			&& validarCampo('txtFechaAplicacion','t','') == true 
			&& validarCampo('txtNumeroDocumento','t','') == true 
			&& validarCampo('txtImporteMovimiento','t','monto') == true
			&& validarCampo('lstCuenta','t','lista') == true))
		{
			validarCampo('txtNombreEmpresa','t','');
			validarCampo('txtNombreBanco','t','');
			validarCampo('txtObservacion','t','');
			validarCampo('lstEstadoDcto','t','lista');
			validarCampo('txtSaldoCuenta','t','');
			validarCampo('txtFechaRegistro','t','');
			validarCampo('txtFechaAplicacion','t','');
			validarCampo('txtNumeroDocumento','t','');
			validarCampo('txtImporteMovimiento','t','monto');
			validarCampo('lstCuenta','t','lista');
			
			error = true;			
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			desbloquearGuardado();
			return false;
		} else {
			if(totalMotivo !== totalDcto){
				alert("El total de Motivos debe ser igual al total del documento");
				desbloquearGuardado();
				return false;
			}
			
			if (parseNumRafk(byId('txtImporteMovimiento').value)+parseNumRafk(byId('hddDiferido').value) > parseNumRafk(byId('hddSaldoCuenta').value) && byId('hddPasoClaveSobreGiro').value != 1){
				if (confirm('El monto de la Nota de Debito es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
					byId('aDesbloquearClaveSobreGiro').click();
				}
				desbloquearGuardado();
				return false;
			} else {
				xajax_guardarNotaDebito(xajax.getFormValues('frmNotaDebito'));
			}			
		}
	}
	
	function calcularPorcentajeTarjetaCredito() {	
		montoFinal = 0;
		
		if (byId('selTipoNotaDebito').value == 3) {//tarjeta de credito
			montoRetencion = parseNumRafk(byId('montoBase').value) * parseNumRafk(byId('porcentajeRetencion').value) / 100;
			byId('montoTotalRetencion').value = formatoRafk(montoRetencion,2);
			
			montoComision = parseNumRafk(byId('montoBase').value) * parseNumRafk(byId('porcentajeComision').value) / 100;
			byId('montoTotalComision').value = formatoRafk(montoComision,2);
			
			//resto comision ISLR segun formula de caja pagos cargados del dia
			montoRetencionFinal = parseNumRafk(byId('montoBase').value) / 1.12 * parseNumRafk(byId('porcentajeRetencion').value) / 100;
			montoFinal = byId('montoBase').value - (montoComision + montoRetencionFinal) ;
			
		} else if (byId('selTipoNotaDebito').value == 2) {//tarjeta de debito
			montoComision = parseNumRafk(byId('montoBase').value) * parseNumRafk(byId('porcentajeComision').value) / 100;
			byId('montoTotalComision').value = formatoRafk(montoComision,2);
			
			montoFinal = parseNumRafk(byId('montoBase').value) - montoComision;
		}
		
		byId('txtImporteMovimiento').value = formatoRafk(montoFinal,2);
	}
		
	function limpiarMontosTarjeta(){	
		byId('porcentajeRetencion').value = "";
		byId('porcentajeComision').value = "";
		byId('montoTotalRetencion').value = "";
		byId('montoTotalComision').value = "";
		
		byId('montoBase').value = "";
		byId('txtImporteMovimiento').value = "";
	}
	
	function mostrarTarjetas(tipoNotaDebito){
		if(tipoNotaDebito == 2 || tipoNotaDebito == 3){
			$("#trTarjetas").show();
		}else{
			$("#trTarjetas").hide();
		}
	}
	
	function desbloquearGuardado(){                    
		byId("btnGuardar").disabled = false;
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
        	<td class="tituloPaginaTesoreria">Nota de D&eacute;bito</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, '', 0);">
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
					<td align="right" class="tituloCampo" width="120">Estado:</td>
					<td id="tdLstEstado"></td>
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" name="txtCriterio" id="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
					<td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarNotaDebito(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
        	<td id="tdListaNotaDebito"></td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                        	<td><img src="../img/iconos/ico_tesoreria.gif"></td>
                            <td>Tesoreria</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_caja_vehiculo.gif"></td>
                            <td>Caja Vehiculos</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_caja_rs.gif"></td>
                            <td>Caja Repuestos y Servicios</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_ingregos_bonificaciones.gif"></td>
                            <td>Ingresos Por Bonificaciones</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_otros_ingresos.gif"></td>
                            <td>Otros Ingresos</td>
                        </tr>
                        </table>
                        <table>
                        	<tr>
							<td><img src="../img/iconos/ico_rojo.gif"></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"></td>
                            <td>Aplicada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"></td>
                            <td>Conciliada</td>
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
	<div style="max-height:480px; overflow-y:auto; overflow-x : hidden; width:825px;">
    <form id="frmNotaDebito" name="frmNotaDebito" onsubmit="return false;">
    <table border="0" width="810">
    	<tr align="left">
    		<td>
    			<fieldset><legend class="legend">Datos Empresa</legend>
    			<table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                                <td>
                                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
    			<fieldset><legend class="legend">Datos Bancos</legend>
    			<table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Banco:</td>
                    <td colspan="3" align="left">
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
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cuentas:</td>
                    <td colspan="3" id="tdLstCuenta"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Saldo Cuenta:</td>
                        <td colspan="3"><input type="text" id="txtSaldoCuenta" name="txtSaldoCuenta" size="25" readonly="readonly" style="text-align:right"/>
                         <input type="hidden" id="hddSaldoCuenta" name="hddSaldoCuenta" />
                         <input type="hidden" id="hddDiferido" name="hddDiferido" />
                         <input type="hidden" id="hddPasoClaveSobreGiro" name="hddPasoClaveSobreGiro"/>
						<a class="modalImg" id="aDesbloquearClaveSobreGiro" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'te_propuesta_pago');" style="display:none;">
							<img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
						</a>
		    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Telefono Banco:</td>
                    <td><input type="text" id="txtTelefonoBanco" name="txtTelefonoBanco" size="25" readonly="readonly"/></td>
                    <td align="right" class="tituloCampo" width="120">Email Banco:</td>
                    <td><input type="text" id="txtEmailBanco" name="txtEmailBanco" size="25" readonly="readonly"/></td>
                </tr>
            </table>
            </fieldset>
            <fieldset><legend><span class="legend">Datos Nota Débito</span></legend>
            <table>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
                   <td align="left">
                    <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <input type="text" name="txtFechaRegistro" id="txtFechaRegistro" readonly="readonly"/>
                                </td>
                                <td></td>
                           </tr>
                       </table>
					</td>  
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Fecha Aplicación:</td>
                    <td align="left"><input type="text" id="txtFechaAplicacion" name="txtFechaAplicacion" size="25" readonly="readonly"/></td>
                </tr>
                 <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Numero Nota Débito:</td>
                    <td colspan="3"><input type="text" id="txtNumeroDocumento" name="txtNumeroDocumento" size="25" onkeypress="return validarSoloNumerosReales(event);"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Tipo Nota Débito:</td>
                    <td colspan="3">
                        <select id="selTipoNotaDebito" name="selTipoNotaDebito" onChange="mostrarTarjetas(this.value); limpiarMontosTarjeta(); xajax_cargaLstTarjetaCuenta(byId('lstCuenta').value,this.value);" class="inputHabilitado">
                            <option value="1" selected="selected">Normal</option>
                            <option value="2">Tarjeta de Debito</option>
                            <option value="3">Tarjeta de Credito</option>
                            <option value="4">Transferencia</option>
                        </select>
                	</td>
                </tr>

				<tr id="trTarjetas" style="display:none;">
                	<td colspan="4">
                    <fieldset><legend class="legend">Tarjetas:</legend>
                        <table align="center">
                       		<tr>
                            	<td align="right" class="tituloCampo" width="120">Tipo Tarjeta:</td>
                                <td id="tdtarjeta"></td>
                            	<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Monto:</td>
                                <td><input type="text" style="text-align:right;" size="10" name="montoBase" id="montoBase" onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularPorcentajeTarjetaCredito();" class="inputHabilitado"></td>
                            </tr>
                            <tr>
                                <td align="right" class="tituloCampo" width="140">Porcentaje Retenci&oacute;n:</td>
                                <td><input type="text" style="text-align:right;" size="10" readonly="readonly" name="porcentajeRetencion" id="porcentajeRetencion"></td>
                                <td align="right" class="tituloCampo" width="120">Monto Retenci&oacute;n:</td>
                                <td><input type="text" style="text-align:right;" size="19" readonly="readonly" name="montoTotalRetencion" id="montoTotalRetencion"></td>
                            </tr>
                            <tr>
                                <td align="right" class="tituloCampo" width="140">Porcentaje Comisi&oacute;n</td>
                                <td><input type="text" style="text-align:right;" size="10" readonly="readonly" name="porcentajeComision" id="porcentajeComision"></td>
                                <td align="right" class="tituloCampo" width="120">Monto Comisi&oacute;n:</td>
                                <td><input type="text" style="text-align:right;" size="19" readonly="readonly" name="montoTotalComision" id="montoTotalComision"></td>
                            </tr>
                        </table>
                    </fieldset>
                	</td>
                </tr>
                
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Observacion:</td>
                    <td colspan="3"><textarea name="txtObservacion" cols="72" rows="2" id="txtObservacion"></textarea></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Origen:</td>
                    <td><input type="text" id="txtOrigenNotaDebito" name="txtOrigenNotaDebito" size="25" readonly="readonly"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Estado:</td>
                    <td id="tdLstEstadoDcto"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Importe de Movimiento:</td>
                    <td><input type="text" id="txtImporteMovimiento" name="txtImporteMovimiento" size="25" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);"/></td>
                </tr>
                </table>
                </fieldset>
    		</td>
    	</tr>
	<tr align="left">
		<td>
            <fieldset><legend class="legend">Motivos:</legend>
            <table border="0" width="100%">
            <tr id="trListaMotivo" align="left">
                <td>
                    <table border="0" width="100%" class="tablaResaltarPar">
                    <thead>
                    <tr>
                        <td align="left" colspan="20">
                            <a class="modalImg" id="aAgregarMotivo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo');">
                                <button type="button" title="Agregar Motivo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                            <button type="button" id="btnQuitarMotivo" name="btnQuitarMotivo" onclick="eliminarMotivoLote();" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                        </td>
                    </tr>
                    
                    <tr align="center" class="tituloColumna">
                        <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmNotaDebito');"/></td>
                        <td width="14%">Código</td>
                        <td width="40%">Descripción</td>
                        <td width="16%">Módulo</td>
                        <td width="16%">Tipo Transacción</td>
                        <td width="10%">Total</td>
                        <td><input type="hidden" id="hddObjItmMotivo" name="hddObjItmMotivo" readonly="readonly" title="hddObjItmMotivo"/></td>
                    </tr>
                    </thead>
                    <tr id="trItmPie"></tr>
                    </table>
                </td>
            </tr>
            </table>
            </fieldset>
		</td>
	</tr>
    	<tr>
			<td align="right"><hr>
                <button type="button" id="btnGuardar" onclick="this.disabled = true; validarFrmInsertar();">Guardar</button>
                <button type="button" id="btnCancelar" class="close">Cancelar</button>
			</td>
    	</tr>
    </table>
    </form>
    </div>
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
       
    <table border="0" id="tblListaMotivo" width="610" style="display:none;">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarMotivo" id="frmBuscarMotivo">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarMotivo').click();" class="inputHabilitado" name="txtCriterioBuscarMotivo" id="txtCriterioBuscarMotivo"></td>
                    <td>
                        <button onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));" name="btnBuscarMotivo" id="btnBuscarMotivo" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>        
    <tr>
        <td id="tdListaMotivo"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarMotivo" name="btnCancelarMotivo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaLstEstado();
xajax_listaNotaDebito(0,'id_nota_debito','DESC','<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>');

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaRegistro").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaAplicacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

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
	
objFechaRegistro = new JsDatePick({
	useMode:2,
	target:"txtFechaRegistro",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

objFechaRegistro.setOnSelectedDelegate(function(){
	byId('txtFechaRegistro').value = this.getSelectedDayFormatted();
	byId('txtFechaAplicacion').value = this.getSelectedDayFormatted();
	this.closeCalendar();
});

new JsDatePick({
	useMode:2,
	target:"txtFechaAplicacion",
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