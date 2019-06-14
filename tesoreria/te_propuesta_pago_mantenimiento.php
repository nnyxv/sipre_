<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_general.php");
require("controladores/ac_te_propuesta_pago_mantenimiento.php");

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
    
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento Propuesta</title>
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
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblAprobarPropuesta').style.display = 'none';
		
		if (verTabla == "tblAprobarPropuesta") {
			document.forms['frmPropuesta'].reset();
			byId('hddIdPropuestaAprobar').value = '';
			
			xajax_formPropuesta(valor);
			
			tituloDiv1 = 'Aprobar Propuesta';			
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaBanco').style.display = 'none';
		byId('tblBeneficiariosProveedores').style.display = 'none';
		byId('tblEliminarPropuesta').style.display = 'none';
		
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
		} else if (verTabla == "tblBeneficiariosProveedores") {
			byId('btnBuscarProveedor').click();
			
			tituloDiv2 = 'Beneficario / Proveedor';
		} else if (verTabla == "tblEliminarPropuesta") {
			document.forms['frmEliminarPropuesta'].reset();
			
			xajax_formEliminarPropuesta(valor);
			
			tituloDiv2 = 'Eliminar Propuesta';
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
		} else if (verTabla == "tblBeneficiariosProveedores") {
			byId('txtCriterioBusqProveedor').focus();
			byId('txtCriterioBusqProveedor').select();
		}
	}
	
	function validarClaveAprobacion(){
		if (validarCampo('txtClaveAprobacion','t','') == true){
			xajax_verificarClave(xajax.getFormValues('frmClave'));
		 }else{
		 	validarCampo('txtClaveAprobacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
		
	function validarFrmAprobacion(){
		if (validarCampo('numeroChequeManual','t','') == true
		&& validarCampo('txtFechaRegistro','t','') == true
		&& validarCampo('txtFechaLiberacion','t','') == true
		&& validarCampo('txtConceptoCheque','t','') == true
		&& validarCampo('txtObservacionCheque','t','') == true){
			xajax_aprobarPropuesta(xajax.getFormValues('frmPropuesta'));
		 }else{
		 	validarCampo('numeroChequeManual','t','');
			validarCampo('txtFechaRegistro','t','');
			validarCampo('txtFechaLiberacion','t','');		 	
			validarCampo('txtConceptoCheque','t','');
			validarCampo('txtObservacionCheque','t','');
			
			alert("Los campos señalados en rojo son requeridos");
 			desbloquearGuardado();
			return false;
		 }
	}
	
	function validarFrmEliminarPropuesta(){
		if (validarCampo('txtIdPropuestaEliminar','t','') == true
		&& validarCampo('hddIdPropuestaEliminar','t','') == true){
			xajax_eliminarPropuesta(xajax.getFormValues('frmEliminarPropuesta'));
		 }else{
		 	validarCampo('txtIdPropuestaEliminar','t','');
			validarCampo('hddIdPropuestaEliminar','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
	
	function validarLongitud(campo){
		if (byId(campo).value.length > 119){
			var cadena = byId(campo).value.substring(0,119);
			byId(campo).value = cadena;
		}
	}
	
	function buscador(seccion){//buscadores
		if(seccion == 'proveedor'){
			byId('buscadorProveedor').style.display = '';
		}else if(seccion == 'empresa'){//por si se agrega a otro
			byId('buscadorProveedor').style.display = 'none';
		}else if(seccion == 'banco'){//por si se agrega a otro
			byId('buscadorProveedor').style.display = 'none';
		}
	}
	
	function limpiarFormulario(){
		desbloquearGuardado();
	}
	
	function numeros(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[0-9]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}
	
	function desbloquearGuardado(){
		byId('btnGuardar').disabled = false;
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
            <td class="tituloPaginaTesoreria">Mantenimiento Propuesta de Pago</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevaPropuesta" onclick="window.open('te_propuesta_pago.php?id_propuesta=0&acc=1','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" align="left"></td>
                    <td align="right" class="tituloCampo" width="120">Proveedor:</td>
					<td>
                   	  <table cellpadding="0" cellspacing="0">
                            <td>
                                <input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value);" size="6" style="text-align:right" class="inputHabilitado"/>
                            </td>
                            <td>
                                <a class="modalImg" id="aProveedor" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblBeneficiariosProveedores');"><button type="button" title="Seleccionar Proveedor" class="puntero"><img src="../img/iconos/help.png"/></button></a>
                            </td>
                            <td>
                                <input type="text" name="nombreProveedorBuscar" id="nombreProveedorBuscar" readonly="readonly" size="30"></input>
                            </td>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="110">Banco:</td>
                    <td colspan="1" align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                            </td>
                            <td><a onclick="abrirDivFlotante2(this, 'tblListaBanco');" rel="#divFlotante2" id="aListarBanco" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button></a>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="110">Cuentas:</td>
                    <td align="left" colspan="3" id="tdLstCuenta"></td>
					<td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPropuesta(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="byId('hddIdBanco').value = ''; document.forms['frmBuscar'].reset(); byId('btnBuscar').click(); xajax_cargarLstCuenta(xajax.getFormValues('frmBuscar'));">Limpiar</button>
					</td>
                </tr>
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListaPropuesta"></td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                        	<td><img src="../img/iconos/ico_view.png"></td>
                            <td>Ver</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"></td>
                            <td>Editar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_quitar.gif"></td>
                            <td>Eliminar Propuesta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_aceptar.gif"></td>
                            <td>Aprobar Propuesta</td>
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
    
	<form id="frmPropuesta" name="frmPropuesta" onsubmit="return false;">
	<table border="0" id="tblAprobarPropuesta" style="display:none" width="560">
    <tr align="left">
        <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Nro Cheque:</td>
        <td>
            <input type="text" id="numeroChequeManual" name="numeroChequeManual" onkeypress="return numeros(event);" readonly="readonly" /><span id="spanChequeManual" style="display:none;" class="textoRojoNegrita"> (Nro Cheque Manual)</span>
        </td>
    </tr>
        <tr align="left">
            <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
            <td>
				<input type="text" id="txtFechaRegistro" name="txtFechaRegistro" class="inputHabilitado" readonly="readonly" />
                <a class="modalImg" id="aDesbloquearFechaRegistro" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'te_cheque_fecha_registo');">
                    <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear">
                </a>
            </td>
		</tr>
        <tr align="left">
            <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Fecha Liberaci&oacute;n:</td>
            <td>
				<input type="text" id="txtFechaLiberacion" name="txtFechaLiberacion" class="inputHabilitado" readonly="readonly" />
            </td>
		</tr>
		<tr align="left">
            <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Nro Propuesta:</td>
            <td>
                <input type="text" id="txtIdPropuestaAprobar" name="txtIdPropuestaAprobar"  readonly="readonly">
                <input type="hidden" id="hddIdPropuestaAprobar" name="hddIdPropuestaAprobar" readonly="readonly" />
            </td>
		</tr>
		<tr align="left">
			<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Concepto del Cheque:</td>
			<td><label>
            		<textarea id="txtConceptoCheque" name="txtConceptoCheque" class="inputHabilitado" cols="48" rows="2" onkeyup="validarLongitud('txtConceptoCheque');" onblur="validarLongitud('txtConceptoCheque'); byId('txtObservacionCheque').value = this.value;"></textarea>                
			</label></td>
		</tr>
        <tr align="left">
			<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observacion del Cheque:</td>
			<td><label>                
                 <textarea id="txtObservacionCheque" name="txtObservacionCheque" class="inputHabilitado" cols="48" rows="2" onkeyup="validarLongitud('txtObservacionCheque');" onblur="validarLongitud('txtObservacionCheque');"></textarea>
			</label></td>
		</tr>
		<tr>
			<td align="right" colspan="2"><hr>
			<button type="button" id="btnGuardar" name="btnGuardar" onclick="this.disabled = true; validarFrmAprobacion();">Aceptar</button>
			<button type="button" id="btnCancelarPropuesta" name="btnCancelarPropuesta" class="close">Cancelar</button>
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
                        <input type="hidden" id="hddIdPropuestaPermisoEliminar" name="hddIdPropuestaPermisoEliminar" readonly="readonly" />
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
	
   	<table border="0" id="tblBeneficiariosProveedores" style="display:none;" width="700">
    <tr>
    	<td>
			<form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td>
                    <table align="right">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120">Criterio:</td>
                        <td>
                            <input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                            <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="byId('btnBuscarProveedor').click();" class="inputHabilitado"/>
                        </td>
                        <td><button type="button" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));" class="puntero">Buscar</button>
                        	<button type="button" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button>
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
        <td id="tdContenido"></td>
    </tr>
	<tr>
    	<td align="right"><hr>
			<button type="button" id="btnCancelarBeneficiariosProveedores" name="btnCancelarBeneficiariosProveedores" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
    
    <form id="frmEliminarPropuesta" name="frmEliminarPropuesta" onsubmit="return false;" style="margin:0px">
    <table border="0" id="tblEliminarPropuesta" style="display:none" width="290">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="120">Nro Propuesta:</td>
                <td>
                    <input type="text" id="txtIdPropuestaEliminar" name="txtIdPropuestaEliminar"  readonly="readonly">
                    <input type="hidden" id="hddIdPropuestaEliminar" name="hddIdPropuestaEliminar" readonly="readonly" />
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="120">Fecha Propuesta:</td>
                <td>
                    <input type="text" id="txtFechaPropuestaEliminar" name="txtFechaPropuestaEliminar"  readonly="readonly">
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
        <button type="button" onclick="validarFrmEliminarPropuesta();">Aceptar</button>
        <button type="button" id="btnCancelarEliminarPropuesta" name="btnCancelarEliminarPropuesta" class="close">Cancelar</button>
        </td>
    </tr>
	</table>
    </form>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaLstCuenta();
xajax_listaPropuesta();

new JsDatePick({
	useMode:2,
        target:"txtFechaLiberacion",
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