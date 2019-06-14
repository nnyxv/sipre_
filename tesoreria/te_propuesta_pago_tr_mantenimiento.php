<?php

require_once ("../connections/conex.php");
session_start();
require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_propuesta_pago_tr_mantenimiento.php");

//modificado Ernesto
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	include("../contabilidad/GenerarEnviarContabilidadDirecto.php");
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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento Propuesta Transferencia</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
    <script>
//    jQuery.noConflict();
//            jQuery(function($){
//                document.getElementById("#NumeroTransferencia").mask("9999-9999-99-9999999999",{placeholder:" "});
//            });

    </script>
    
    <script>
	function validarClaveAprobacion(){
		if (validarCampo('txtClaveAprobacion','t','') == true){
			xajax_verificarClave(xajax.getFormValues('frmClave'));
		 }else{
		 	validarCampo('txtClaveAprobacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
	
	
	
	function validarAprobacion(){
		if (validarCampo('txtFechaRegistro','t','') == true
                && validarCampo('txtIdPropuestaA','t','') == true
                && validarCampo('hddIdPropuestaA','t','') == true
                && validarCampo('NumeroTransferencia','t','') == true
				&& validarCampo('txtObservacionTr','t','') == true){
			xajax_aprobarPropuesta(xajax.getFormValues('frmTransferencia'),1);
		 }else{
                        
		 	validarCampo('txtFechaRegistro','t','');
		 	validarCampo('txtIdPropuestaA','t','');
		 	validarCampo('hddIdPropuestaA','t','');
		 	validarCampo('NumeroTransferencia','t','');
			validarCampo('txtObservacionTr','t','');
			
			alert("Los campos señalados en rojo son requeridos");
 			desbloquearGuardado();
			return false;
		 }
	}
	
	function validarLongitud(campo){
		if (document.getElementById(campo).value.length > 119){
			var cadena = document.getElementById(campo).value.substring(0,119)
			document.getElementById(campo).value = cadena;
		}
	}
        
        function buscador(seccion){//buscadores
            if(seccion == 'proveedor'){
                document.getElementById('buscadorProveedor').style.display = '';
            }else if(seccion == 'empresa'){//por si se agrega a otro
                document.getElementById('buscadorProveedor').style.display = 'none';
            }else if(seccion == 'banco'){//por si se agrega a otro
                document.getElementById('buscadorProveedor').style.display = 'none';
            }
        }
        
        function limpiarFormulario(){
            document.getElementById("frmTransferencia").reset();
            document.getElementById("hddIdPropuestaA").value = "";
            desbloquearGuardado();            
        }
        
        function desbloquearGuardado(){
            document.getElementById('btnGuardar').disabled = false;
        }
        
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_tesoreria.php'); ?>
    </div>
	
    <div id="divInfo" class="print">
		<table width="100%" border="0">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2">Mantenimiento Propuesta de Pago Transferencia</td>
            </tr>
			<tr>
                <td align="right" colspan="2">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevaPropuesta" onclick="window.open('te_propuesta_pago_transferencia.php?id_propuesta=0&acc=1','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/>
                                <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/>
                            </td>
                            <td><button type="button" id="btnListEmpresa"  name="btnListEmpresa" onclick="buscador('empresa'); xajax_listEmpresa(0,'','',document.getElementById('hddIdProveedorCabecera').value);" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="txtProveedorCabecera" name="txtProveedorCabecera" size="40" readonly="readonly"/>
                                <input type="hidden" id="hddIdProveedorCabecera" name="hddIdProveedorCabecera"/>
                            </td>
                            <td><button type="button" id="btnListarProveedor"  name="btnListarProveedor" onclick="buscador('proveedor'); xajax_listarProveedores(0, '', '', '');" title="Seleccionar Proveedor"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="110"><span class="textoRojoNegrita">*</span>Banco:</td>
                    <td colspan="1" align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                            </td>
                            <td><button type="button" id="btnListBanco" name="btnListBanco" onclick="buscador('banco'); xajax_listBanco();" title="Seleccionar Beneficiario"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="110"><span class="textoRojoNegrita">*</span>Cuentas:</td>
                    <td align="left" colspan="3" id="tdSelCuentas">
                    	<select name="selCuenta" id="selCuenta" class="inputHabilitado">
                        	<option value="-1">Seleccione</option>
                        </select>
                    </td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPropuesta(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.getElementById('hddIdEmpresa').value = ''; document.getElementById('hddIdProveedorCabecera').value = ''; document.getElementById('hddIdBanco').value = ''; document.forms['frmBuscar'].reset(); document.getElementById('btnBuscar').click(); xajax_comboCuentas(xajax.getFormValues('frmBuscar'))">Limpiar</button>
					</td>
                </tr>
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListadoPropuestas"></td>
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
    <div class="noprint">
	<?php include ('pie_pagina.php'); ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%">Aprobacion</td></tr></table></div>
	<form id="frmClave" name="frmClave" onsubmit="return false;">
	<table border="0" id="tblClaveAprobacionOrden">
    <tr>
        <td align="right" class="tituloCampo" >Nro Propuesta:</td>
        <td>
            <input type="text" id="txtIdPropuesta" name="txtIdPropuesta"  readonly="readonly">
            <input type="hidden" id="hddIdPropuesta" name="hddIdPropuesta" readonly="readonly" />
        </td>
    </tr>
    <tr>
        <td align="right" class="tituloCampo">Clave:</td>
        <td><label>
            <input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial" />
        </label></td>
    </tr>
    <tr>
        <td align="right" colspan="2">
        <hr>
        <input type="submit" onclick="validarClaveAprobacion();" value="Aceptar" />
        <input type="button" onclick="document.getElementById('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
	</table>
    </form>
</div>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; ">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
   	        
        <table border="0" id="tblBancos" style="display:none" width="610">
    <tr>
        <td id="buscadorProveedor" style="display:none;">
            <form name="frmBuscarProveedor" id="frmBuscarProveedor" onsubmit="return false;">
                <table>
                    <tr>
                        <td align="right" class="tituloCampo" width="115">Criterio:</td>
                        <td>
                            <input type="text" id="txtCriterioBusq" name="txtCriterioBusq" class="inputHabilitado" onkeyup="document.getElementById('btnBuscarProveedor').click();"/>
                        </td>
                        <td>    
                            <button type="button" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));" >Buscar...</button>
                        </td>
                    </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>        
        <td id="tdDescripcion">
        </td>
    </tr>
    <tr>
        <td align="right" id="tdBotonesDiv">
            <hr>
            <input type="button" id="" name="" onclick="document.getElementById('divFlotante1').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
	</div>
    
    <div id="divTransferencia" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTituloTransferencia" class="handle"><table><tr><td id="tdFlotanteTituloTransferencia" width="100%">Observacion Transferencia</td></tr></table></div>
	<form id="frmTransferencia" name="frmTransferencia" onsubmit="return false;">
	<table border="0" id="tblObservacionTransferencia">
	<tr align="left">
			<td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Nro Propuesta:</td>
		<td>
			<input type="text" id="txtIdPropuestaA" name="txtIdPropuestaA"  readonly="readonly">
            <input type="hidden" id="hddIdPropuestaA" name="hddIdPropuestaA" readonly="readonly" />
		</td>
		</tr>
	<tr align="left">
         <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Fecha Transferencia:</td>
        <td align="left">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <input type="text" name="txtFechaRegistro" id="txtFechaRegistro" class="inputHabilitado" readonly="readonly"/>
                    </td>
               </tr>
           </table>
         </td>  
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Numero Transferencia</td>
        <td>
            <label>
                <input type="text" id="NumeroTransferencia" name="NumeroTransferencia" class="inputHabilitado" size="25">
            </label>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observacion Transferencia</td>
        <td>
            <label>
                <textarea id="txtObservacionTr" name="txtObservacionTr" cols="48" rows="2" class="inputHabilitado" onkeyup="validarLongitud('txtObservacionTr');" onblur="validarLongitud('txtObservacionTr');"></textarea>
            </label>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2">
        <hr>
        <input type="submit" id="btnGuardar" name="btnGuardar" onclick="this.disabled = true; validarAprobacion();" value="Aceptar" />
        <input type="button" onclick="document.getElementById('divTransferencia').style.display='none';" value="Cancelar">
        </td>
    </tr>
	</table>
    </form>
</div>

<script>

new JsDatePick({
	useMode:2,
	target:"txtFechaRegistro",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
}); 

xajax_asignarEmpresa(0,0,document.getElementById('hddIdProveedorCabecera').value);
xajax_listarPropuestas('0','','','0|||');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
var theHandle = document.getElementById("divFlotanteTituloTransferencia");
var theRoot   = document.getElementById("divTransferencia");
Drag.init(theHandle, theRoot);
	
</script>