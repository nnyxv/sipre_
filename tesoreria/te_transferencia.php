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
require("controladores/ac_te_transferencia.php");

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
    
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Transferencia</title>
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
    
	<style type="text/css">
	#tdFacturas, #tdNotaCargo, #tdBeneficiarios, #tdProveedores{
		-webkit-border-top-left-radius: 10px;
		-webkit-border-top-right-radius: 10px;
		-moz-border-radius-topleft: 10px;
		-moz-border-radius-topright: 10px;
		border-top-left-radius: 10px;
		border-top-right-radius: 10px;

		border-color:#CCCCCC;                                  
	}
        
        .tabla-propuesta{
           border: 1px solid #999999;
           border-collapse: collapse; 
        }
        .tabla-propuesta td{
            border: 1px solid #999999;
            padding: 7px;
        }
        .tabla-propuesta th{
            border: 1px solid #999999;
            background-color: #f0f0f0;
            padding: 7px;
        }
	</style>
    <script>
        
	function exportarExcel(){
		objInputs = xajax.getFormValues('frmBuscar');
		window.open("reportes/te_transferencias_excel.php?valBusq="+JSON.stringify(objInputs)+"&acc=<?php echo $_GET['acc']; ?>");
	}
        
        
	function validarTransferencia(){
		
		if (validarCampo('txtNombreEmpresa','t','') == true
		&&  validarCampo('txtNombreBanco','t','') == true
		&&  validarCampo('selCuenta','t','lista') == true
		&&  validarCampo('txtSaldoCuenta','t','') == true
		&&  validarCampo('txtIdBeneficiario','t','') == true
 		&&  validarCampo('txtCiRifBeneficiario','t','') == true
		&&  validarCampo('txtNombreBeneficiario','t','') == true
		&&  validarCampo('txtFechaRegistro','t','') == true
		&&  validarCampo('txtMonto','t','') == true
		&&  validarCampo('txtNumCuenta','t','') == true
		&&  validarCampo('txtComentario','t','') == true
		&&  validarCampo('numTransferencia','t','') == true
		&&  validarCampo('txtIdFactura','t','') == true
		&&  byId('txtMonto').value > 0){
			xajax_guardarTransferencia(xajax.getFormValues('frmTransferencia'));
		} else {
			validarCampo('txtNombreEmpresa','t','')
			validarCampo('txtNombreBanco','t','')
			validarCampo('selCuenta','t','lista')
			validarCampo('txtSaldoCuenta','t','')
			validarCampo('txtIdBeneficiario','t','')
			validarCampo('txtCiRifBeneficiario','t','')
			validarCampo('txtNombreBeneficiario','t','')
			validarCampo('txtFechaRegistro','t','')
			validarCampo('txtMonto','t','')
			validarCampo('txtNumCuenta','t','')
			validarCampo('txtComentario','t','')
			validarCampo('numTransferencia','t','')
			validarCampo('txtIdFactura','t','')
			
			if	(byId('txtMonto').value <= 0)
				byId('txtMonto').className = 'inputErrado';
			
			alert("Los campos señalados en rojo son requeridos");
                        desbloquearGuardado();
         
			return false;
		}
	}
	
	function nuevoTransferencia(idEmpresa){
		byId('txtNombreEmpresa').className = 'inputInicial';
		byId('txtNombreBanco').className = 'inputInicial';
		byId('selCuenta').style.display = 'none';
		byId('txtSaldoCuenta').className = 'inputInicial';
		byId('txtIdBeneficiario').className = 'inputInicial';
		byId('txtCiRifBeneficiario').className = 'inputInicial';
		byId('txtNombreBeneficiario').className = 'inputInicial';
		byId('txtFechaRegistro').className = 'inputHabilitado';
		byId('txtMonto').className = 'inputHabilitado';
		document.forms['frmTransferencia'].reset();
		byId('txtDescripcionFactura').innerHTML = '';
		byId('selRetencionISLR').disabled = 'disabled';
		xajax_asignarEmpresa(idEmpresa,0);
		byId('divFlotante1').style.display = 'none';
		byId('divFlotante').style.display = '';
		byId('btnAceptar').style.display = '';
		byId('btnActualizar').style.display = 'none';
		byId('tdFlotanteTitulo').innerHTML = 'Nueva Transferencia';
		centrarDiv(byId('divFlotante'));
		byId('trSaldoCuenta').style.display = '';
		byId('hddPorcentajeRetencion').value = '0';
		byId('tdTextoRetencionISLR').style.display = 'none';
		byId('tdMontoRetencionISLR').style.display = 'none';
		byId('txtMontoRetencionISLR').value = 0;
		byId('tdTxtSaldoFactura').style.display = '';
		byId('tdSaldoFactura').style.display = '';
	}
	
	function validarLongitud(campo){
		if (byId(campo).value.length > 119){
			var cadena = byId(campo).value.substring(0,119);
			byId(campo).value = cadena;
		}
	}
	
	
	function validarMonto(){
		if (parseFloat(byId('txtMonto').value) > parseFloat(byId('txtSaldoFactura').value) && byId('hddPermiso').value == 0){

			byId('btnAceptar').disabled = true;
			if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
				byId('divFlotanteClave').style.display = '';
				centrarDiv(byId('divFlotanteClave'));
				byId('tdFlotanteTituloClave').innerHTML = 'Aprobación';
				return false;
			}else{
				return false;
			}
		}else{
			if (parseFloat(byId('txtMonto').value)+parseFloat(byId('hddDiferido').value) > parseFloat(byId('hddSaldoCuenta').value) && byId('hddPermiso').value == 0){
				byId('btnAceptar').disabled = true;
				if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
					byId('divFlotanteClave').style.display = '';
					centrarDiv(byId('divFlotanteClave'));
					byId('tdFlotanteTituloClave').innerHTML = 'Aprobación';
					return false;
				}else{
					return false;
				}
			}else{
				byId('txtMonto').className = 'inputHabilitado';
			}
		}
			
	}
	
	function validarProveedor(){
		if (validarCampo('txtNombreEmpresa','t','') == true
		&&  validarCampo('txtNombreBanco','t','') == true
		&&  validarCampo('selCuenta','t','lista') == true
		&&  validarCampo('txtSaldoCuenta','t','') == true
		&&  validarCampo('txtIdBeneficiario','t','') == true
		&&  validarCampo('txtCiRifBeneficiario','t','') == true
		&&  validarCampo('txtNombreBeneficiario','t','') == true){
			xajax_listaFacturas(0,'','',byId('hddIdEmpresa').value + '|' + byId('txtIdBeneficiario').value);
			return true;
		}else{
			validarCampo('txtNombreEmpresa','t','')
			validarCampo('txtNombreBanco','t','')
			validarCampo('selCuenta','t','lista')
			validarCampo('txtSaldoCuenta','t','')
			validarCampo('txtIdBeneficiario','t','')
			validarCampo('txtCiRifBeneficiario','t','')
			validarCampo('txtNombreBeneficiario','t','')
					
			alert("Los campos señalados en rojo son requeridos");
			byId('divFlotante1').style.display = 'none';
			return false;
		}
	}
	
	function calcularRetencion(){
		if ((parseFloat(byId('txtSaldoFactura').value) >= parseFloat(byId('hddMontoMayorAplicar').value)) && (byId('hddPorcentajeRetencion').value > 0) ){
			byId('tdTextoRetencionISLR').style.display = '';
			byId('tdMontoRetencionISLR').style.display = '';
			
			if (byId('hddIva').value == 0){
				var monto_retencion = (byId('txtSaldoFactura').value * byId('hddPorcentajeRetencion').value / 100)-( byId('hddSustraendoRetencion').value);
				byId('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat(byId('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				byId('txtMonto').value = number_format(monto,'2','.','');
			}else{
				var monto_retencion = (byId('hddBaseImponible').value * byId('hddPorcentajeRetencion').value / 100)-(byId('hddSustraendoRetencion').value);
				byId('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat(byId('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				byId('txtMonto').value = number_format(monto,'2','.','');
			}
		
		}else{
			byId('tdTextoRetencionISLR').style.display = 'none';
			byId('tdMontoRetencionISLR').style.display = 'none';
			byId('txtMonto').value = byId('txtSaldoFactura').value;
			byId('txtMontoRetencionISLR').value = 0;
		}
	}
	
    function number_format( number, decimals, dec_point, thousands_sep ){
		var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
		var d = dec_point == undefined ? "," : dec_point;
		var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
		var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
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
	
	
	
	function validarClaveAnular(){
		if (validarCampo('txtClaveAnular','t','') == true){
			xajax_verificarClaveAnular(xajax.getFormValues('frmClaveAnular'));
		 }else{
		 	validarCampo('txtClaveAnular','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
        
        //la llama despues de confirmar la clave el xajax
    function antesAnular(idTransferencia){
        
        //compruebo si tiene impuesto el cheque
        var tiene = xajax.call('tieneImpuesto', {mode:'synchronous', parameters:[idTransferencia]});

        if(tiene == "SI"){
			if(confirm("La transferencia posee ISLR, si ya fue declarado no deberia ser eliminado, ¿deseas eliminar el impuesto?")){
				 xajax_anularTransferencia(idTransferencia,"SI");
			 }else{
				 xajax_anularTransferencia(idTransferencia);
			 }
        }else if(tiene == 'NO'){
            xajax_anularTransferencia(idTransferencia);
        }else{
            alert('Error: ' +tiene);
            return false;
        }       
        
    }
    
    function limpiarPropuesta(){        
        byId('numeroPropuestaPago').innerHTML = "";
        byId('fechaPropuestaPago').innerHTML = "";
        byId('numeroTransferenciaPropuestaPago').innerHTML = "";
        byId('estadoPropuestaPago').innerHTML = "";
        byId('detallePropuestaPago').innerHTML = "";
    }
    
    function desbloquearGuardado(){
        byId('btnAceptar').disabled = false;
    }
        
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>

    <div id="divInfo" class="print">
    	<table border="0" width="100%">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2" id="tdReferenciaPagina"></td>
            </tr>
            <tr>
                <td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" id="btnNuevo" onclick="nuevoTransferencia(byId('selEmpresa').value);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    <button type="button" id="btnExportarExcel" onclick="exportarExcel();" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
                <tr>
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdSelEmpresa" align="left">
                        <select id="selEmpresa" name="selEmpresa" class="inputHabilitado">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">
                        Benef. Prov.:
                    </td>
                    <td>
                        <input type="text" name="idProveedorBuscar" id="idProveedorBuscar" readonly="readonly" style="width:30px"></input>
                        <input type="text" name="nombreProveedorBuscar" id="nombreProveedorBuscar" readonly="readonly" size="30"></input>

                    </td>
                    <td>
                        <button title="Seleccionar Beneficiario o Proveedor" onclick="
                            byId('tblBancos').style.display = 'none';
                            byId('tblFacturasNcargos').style.display = 'none';
                            byId('tblBeneficiariosProveedores').style.display = '';
                            byId('tdContenido').style.display = '';
                            byId('tdFlotanteTitulo1').innerHTML = 'Beneficiario o Provedor';
                            byId('tdProveedores').className = 'rafktabs_titleActive';
                            byId('tdBeneficiarios').className = 'rafktabs_title';
                            byId('txtIdFactura').value = '';
                            byId('txtNumeroFactura').value = '';
                            byId('txtSaldoFactura').value = '';
                            byId('txtFechaRegistroFactura').value = '';
                            byId('txtFechaVencimientoFactura').value = '';
                            byId('txtDescripcionFactura').innerHTML = '';

                            //si cierra y abre no muestra el buscador input correcto
                            byId('txtCriterioBusqBeneficiario').style.display='none';
                            byId('txtCriterioBusqProveedor').style.display='';

                            byId('buscarListado').value = '1';
                            byId('buscarProv').value = '1';//proveedor
                            
                            byId('tdProveedores').onclick = function(){
                                xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),1);
                                 byId('tdBeneficiarios').className = 'rafktabs_title';
                                 byId('tdProveedores').className = 'rafktabs_titleActive';
                                 byId('txtCriterioBusqProveedor').style.display='';
                                 byId('txtCriterioBusqBeneficiario').style.display='none';
                                 byId('buscarProv').value = '1';//proveedor
                                 };

                              byId('tdBeneficiarios').onclick = function(){
                                 xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),0);
                                 byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                 byId('tdProveedores').className = 'rafktabs_title';
                                 byId('txtCriterioBusqProveedor').style.display='none';
                                 byId('txtCriterioBusqBeneficiario').style.display='';
                                 byId('buscarProv').value = '2';//beneficiario
                                 };
                            xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));
            " type="button"><img src="../img/iconos/ico_pregunta.gif"></button>
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo">Fecha Registro:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                       </tr>
                       </table>
                    </td>
                    
                    <td align="right" class="tituloCampo">
                        Concepto:
                    </td>
                    <td>
                        <input type="text" name="conceptoBuscar" id="conceptoBuscar" class="inputHabilitado"></input>                                            
                    </td>
                                        
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td id="tdSelEstado" align="left" >
                        <select id="selEstado" name="selEstado" class="inputHabilitado">
                            <option value="-1">Seleccione</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" >Nro. Transferencia:</td>
                    <td align="left"><input type="text" name="txtBusq" id="txtBusq" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>			
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListadoTransferencia"></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_rojo.gif"></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"></td>
                            <td>Aplicado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"></td>
                            <td>Conciliado</td>
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
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="frmTransferencia" name="frmTransferencia">
    <table border="0" id="tblTransferenciaNuevo" width="810">
    	<tr align="left">
    		<td>
    			<fieldset><legend class="legend">Datos Empresa</legend>
    			<table width="100%">
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                                <td><button type="button" id="btnListEmpresa"  name="btnListEmpresa" onclick="xajax_listEmpresa();" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                </table>
                </fieldset>
             </td>
             <td>  
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
                        
                                <td><button type="button" id="btnListBanco" name="btnListBanco" onclick="xajax_listBanco();" title="Seleccionar Beneficiario"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cuentas:</td>
                    <td colspan="3" id="tdSelCuentas"><select name="selCuenta" id="selCuenta" class="inputHabilitado"><option value="-1">Seleccione</option></select></td>
                </tr>
                <tr id="trSaldoCuenta">
                    <td align="right" class="tituloCampo" width="120">Saldo Cuenta:</td>
                    <td colspan="3">
                    	<input type="hidden" id="hddIdCuenta" name="hddIdCuenta" />
                        <input type="hidden" id="hddSaldoCuenta" name="hddSaldoCuenta" />
                        <input type="text" id="txtSaldoCuenta" name="txtSaldoCuenta" size="25" readonly="readonly" style="text-align:right"/>
                    </td>
                    
                    <td align="right" class="tituloCampo" width="110">Diferido:</td>
                        <td align="left" width="200">
                        <input type="text" id="txtDiferido" name="txtDiferido" readonly="readonly" style="text-align:right" /> 
                        <input type="hidden" id="hddDiferido" name="hddDiferido" />
                        </td>
                </tr>
                </table>
                </fieldset>
              </td>
           </tr>
           <tr align="left">
              <td colspan="2">
                <fieldset><legend class="legend">Datos del Beneficiario o Proveedor</legend>
                <table width="100%" border="0">
                <tr>
                    <td class="tituloCampo" width="25%" align="right">
                        <span class="textoRojoNegrita">*</span>Beneficiario o Proveedor:
                    </td>
                    <td width="10%" align="left">
                        <table>
                            <tr>
                                <td>
                                    <input type="text" id="txtIdBeneficiario" name="txtIdBeneficiario" readonly="readonly" size="10"/>
                                </td>
                                <td>
                                    <input type="hidden" id="hddBeneficiario_O_Provedor" name="hddBeneficiario_O_Provedor" />
                                    <button type="button"  id="btnBuscarCliente" name="btnBuscarCliente"
                                    
                                     onclick="xajax_listarProveedores();
                                     
                                     
                                    byId('tblBancos').style.display = 'none';
                                    byId('tblFacturasNcargos').style.display = 'none';
                                    byId('tblBeneficiariosProveedores').style.display = '';
                                    byId('tdContenido').style.display = '';
                                    byId('tdFlotanteTitulo1').innerHTML = 'Beneficiario o Provedor';
                                    byId('tdProveedores').className = 'rafktabs_titleActive';
                                    byId('tdBeneficiarios').className = 'rafktabs_title';
                                    byId('txtIdFactura').value = '';
                                    byId('txtNumeroFactura').value = '';
                                    byId('txtSaldoFactura').value = '';
                                    byId('txtFechaRegistroFactura').value = '';
                                    byId('txtFechaVencimientoFactura').value = '';
                                    byId('txtDescripcionFactura').innerHTML = '';
                                    
                                    //si cierra y abre no muestra el buscador input correcto
                                    byId('txtCriterioBusqBeneficiario').style.display='none';
                                    byId('txtCriterioBusqProveedor').style.display='';
                                        
                                    byId('buscarListado').value = '0';//listado1 de proveedores
                                    byId('buscarProv').value = '1';//proveedor
                                    
                                    byId('tdProveedores').onclick = function(){
                                       xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),1);
                                        byId('tdBeneficiarios').className = 'rafktabs_title';
                                        byId('tdProveedores').className = 'rafktabs_titleActive';
                                        byId('txtCriterioBusqProveedor').style.display='';
                                        byId('txtCriterioBusqBeneficiario').style.display='none';
                                        byId('buscarProv').value = '1';//proveedor
                                        };
                                        
                                     byId('tdBeneficiarios').onclick = function(){
                                        xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),0);
                                        byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                        byId('tdProveedores').className = 'rafktabs_title';
                                        byId('txtCriterioBusqProveedor').style.display='none';
                                        byId('txtCriterioBusqBeneficiario').style.display='';
                                        byId('buscarProv').value = '2';//beneficiario
                                        };">
                                        <img src="../img/iconos/ico_pregunta.gif"/>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" align="right">
                        <span class="textoRojoNegrita">*</span>C.I:/RIF:
                    </td>
                    <td align="left" colspan="1">
                        <input type="text" id="txtCiRifBeneficiario" name="txtCiRifBeneficiario" readonly="readonly" size="30" />
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right" width="20%">
                        <span class="textoRojoNegrita">*</span>Nombre:
                    </td>
                    <td align="left">
                        <input type="text" id="txtNombreBeneficiario" name="txtNombreBeneficiario" readonly="readonly" size="50" />
                    </td>
                    <td class="tituloCampo" align="right" width="20%">
                        <span class="textoRojoNegrita">*</span>Retenci&oacute;n ISLR:
                        <input type="hidden" id="hddMontoMayorAplicar" name="hddMontoMayorAplicar" />
                        <input type="hidden" id="hddPorcentajeRetencion" name="hddPorcentajeRetencion" />
                        <input type="hidden" id="hddCodigoRetencion" name="hddCodigoRetencion" />
                        <input type="hidden" id="hddSustraendoRetencion" name="hddSustraendoRetencion" />
                    </td>
                    <td align="left" id="tdRetencionISLR">

                    </td>
                </tr>
                <tr>
                        <td class="tituloCampo" align="right" width="20%">
                            <span class="textoRojoNegrita">*</span>Numero Cuenta:
                        </td>
                        <td align="left">
                            <input type="text" id="txtNumCuenta" name="txtNumCuenta" size="30" class="inputHabilitado"/>
                        </td>
                    <td colspan="2" id="tdInfoRetencionISLR"></td>
                </tr>
                </table>
                </fieldset>
               
                <fieldset><legend class="legend">Detalles de la factura</legend>
                <table border="0" width="100%">
                <tr>
                    <td class="tituloCampo" width="15%" align="right"><span class="textoRojoNegrita">*</span>Factura:</td>
                    <td width="10%" align="left">
                        <table>
                            <tr>
                                <td>
                                    <input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="10"/>
                                </td>
                                <td>
                                    <button type="button" id="btnInsertarFactura" name="btnInsertarFactura" title="Seleccionar Factura" 
                                    onclick="
                                    if(validarProveedor() === true){
     
                                        byId('tblBancos').style.display = 'none';
                                        byId('tblBeneficiariosProveedores').style.display = 'none';
                                        byId('tblFacturasNcargos').style.display = '';
                                        byId('tdContenidoDocumento').style.display = '';
                                        byId('tdFlotanteTitulo1').innerHTML = 'Factura / Nota de Cargo';
                                        byId('tdFacturas').className = 'rafktabs_titleActive';
                                        byId('tdNotaCargo').className = 'rafktabs_title';
                                        byId('txtIdFactura').value = '';
                                        byId('txtNumeroFactura').value = '';
                                        byId('txtSaldoFactura').value = '';
                                        byId('txtFechaRegistroFactura').value = '';
                                        byId('txtFechaVencimientoFactura').value = '';
                                        byId('txtDescripcionFactura').innerHTML = '';
                                        byId('tdFacturaNota').innerHTML = 'SIN DOCUMENTO';

                                        //si cierra y abre no muestra el buscador input correcto

                                        byId('buscarFact').value = '2';//factura

                                        byId('tdFacturas').onclick = function(){
                                            byId('tdNotaCargo').className = 'rafktabs_title';
                                            byId('tdFacturas').className = 'rafktabs_titleActive';
                                            byId('buscarFact').value = '2';//factura
                                            byId('btnBuscarDocumento').click();
                                            };

                                         byId('tdNotaCargo').onclick = function(){
                                            byId('tdNotaCargo').className = 'rafktabs_titleActive';
                                            byId('tdFacturas').className = 'rafktabs_title';
                                            byId('buscarFact').value = '1';//nota de cargo
                                            byId('btnBuscarDocumento').click();
                                            };
                                    }">
                                        <img src="../img/iconos/ico_pregunta.gif"/>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" width="10%" align="right"><span class="textoRojoNegrita">*</span>N&uacute;mero</td>
                    <td><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" />
                    <td class="tituloCampo" width="15%" align="right" id="tdSaldoFactura">Saldo Factura</td>
                    <td id="tdTxtSaldoFactura"><input type="text" id="txtSaldoFactura" name="txtSaldoFactura" readonly="readonly" />
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">Fecha Registro</td>
                    <td align="left" colspan="1">
                        <input type="text" id="txtFechaRegistroFactura" name="txtFechaRegistroFactura" readonly="readonly" size="15" />
                    </td>
                    <td class="tituloCampo" align="right" width="20%">Fecha Vencimiento</td>
                    <td align="left">
                        <input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="15" />
                    </td>
                    
                    <td class="tituloCampo" align="right" width="20%">Base Imponible</td>
                    <td align="left">
                            <input type="text" id="hddBaseImponible" name="hddBaseImponible" readonly="readonly" size="15" />
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">Descripción</td>
                    <td align="left" colspan="4">
                        <textarea id="txtDescripcionFactura" name="txtDescripcionFactura" readonly="readonly" cols="55">
                        </textarea>
                        <input type="hidden" id="hddIva" name="hddIva" />
<!--                        <input type="hidden" id="hddBaseImponible" name="hddBaseImponible" />-->
                        <input type="hidden" id="hddMontoExento" name="hddMontoExento" />
                        <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento" />
                    </td>
                    <td>
                        <table width="100%" border="0">
                            <tr>
                                <td id="tdFacturaNota" style="white-space:nowrap;" class="divMsjInfo2" align="center">SIN DOCUMENTO</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
                
                <fieldset><legend class="legend">Detalles Transferencia</legend>
                <table border="0" width="100%">
                <tr>
                    <td class="tituloCampo" align="right" width="10%">
                        <span class="textoRojoNegrita">*</span>Fecha:
                    </td>
                    <td align="left">
                        <input type="text" id="txtFechaRegistro" name="txtFechaRegistro" readonly="readonly" size="15"/>
                    </td>
            </tr>
            <tr>
                <td class="tituloCampo" align="right">
                     <span class="textoRojoNegrita">*</span>Número Transferencia:
                
                </td>
                <td>
                <input type="text" id="numTransferencia" name="numTransferencia" class="inputHabilitado" size="25" style="text-align:left" onblur="validarMonto()"/>
                </td>

            </tr>
            <tr>
                <td class="tituloCampo" align="right">
                    <span class="textoRojoNegrita">*</span>Observación:
                </td>
                <td colspan="4" align="left">
                    <textarea id="txtComentario" name="txtComentario" class="inputHabilitado" cols="48" rows="2"  onkeyup="validarLongitud('txtComentario');" onblur="validarLongitud('txtComentario');  validarMonto()"></textarea>
                </td>
            </tr>
                <tr>
                    <td colspan="6">
                        <table id ="tblCheques" width="100%">
                            <tr>
                                <td>
                                    <hr>
                                    <div style="max-height:150px; overflow:auto; padding:1px">
                                    <table border="0" class="tabla" cellpadding="2" width="97%" style="margin:auto;">
                                    	<tr>
                                            <td class="tituloCampo" align="right">
                                                <span class="textoRojoNegrita">*</span>Monto: 
                                            </td>
                                            <td colspan="2" align="left">
                                                <input type="text" id="txtMonto" name="txtMonto" class="inputHabilitado" size="30" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onblur="validarMonto();" onfocus="byId('btnAceptar').disabled = false;"/>
                                            </td>
                                            <td class="tituloCampo" align="right" id="tdTextoRetencionISLR" style="display:none">
                                                <span class="textoRojoNegrita">*</span>Retencion ISLR: 
                                            </td>
                                            <td colspan="2" align="left" id="tdMontoRetencionISLR" style="display:none">
                                                <input type="text" id="txtMontoRetencionISLR" name="txtMontoRetencionISLR" size="30" style="text-align:right" readonly="readonly" />
                                            </td>
                                        </tr>
                                    </table>
                                    </div>
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
    		<td align="right" id="tdDepositoBotones" colspan="2"><hr>
            	<input type="button" id="btnActualizar" name="btnActualizar" value="Actualizar" onclick="xajax_actualizarTransferencia(xajax.getFormValues('frmTransferencia'))" style="display:none"/>
            	<input type="button" id="btnAceptar" name="btnAceptar" onclick="this.disabled = true; validarTransferencia();" value="Aceptar" disabled="disabled"/>
            	<input type="button" onclick="byId('divFlotante').style.display='none'; byId('divFlotante1').style.display='none';" value="Cancelar"/>
            </td>
    	</tr>
    </table>
    </form>
     
</div>


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
   	<table id="tblBeneficiariosProveedores" border="0" style="display:none" width="700px">
    <tr>
    	<td>
        	<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="byId('btnBuscarCliente').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdBeneficiarios" width="120px">Beneficiarios</td>
                        <td class="rafktabs_title" id="tdProveedores" width="120px">Proveedores</td>
		            </tr>
              
                        <tr>
                	<td align="right" class="tituloCampo" width="15">Criterio:</td>
                	<td>
                    	<input type="hidden" id="buscarListado" name="buscarListado" value="0" />
                    	<input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                    	<input type="text" id="txtCriterioBusqBeneficiario" name="txtCriterioBusqBeneficiario" onkeyup="byId('tdBeneficiarios').onclick()" class="inputHabilitado" style="display:"/>
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="byId('tdProveedores').onclick()" class="inputHabilitado" style="display:none"/>
					</td>
                             <td><input type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" value="Buscar..."/></td>
                </tr>
					</table>
				</td>
       
            </tr>
                 
            <tr>
				<td class="rafktabs_panel" id="tdContenido" style="display:none; border:0px;"></td>
            </tr>
            </table></form>
        </td>
    </tr>
	<tr>
    	<td align="right">
			<hr>
			<input type="button" onclick="byId('divFlotante1').style.display='none';" value="Cancelar">
		</td>
          </form>
    </tr>
    </table>

   
   
   <table id="tblFacturasNcargos" border="0" style="display:none" width="1050px">
    <tr>
    	<td>
        	<form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td>
					<table align="right">
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Departamento:</td>
                            <td id="tdlstModulo"></td>
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td>
                                    <input type="hidden" id="buscarFact" name="buscarFact" value="2" />
                                <input type="text" id="txtCriterioBusqFacturaNota" name="txtCriterioBusqFacturaNota" onkeyup="byId('btnBuscarDocumento').click();" class="inputHabilitado" />
                            </td>
                             <td><button type="button" id="btnBuscarDocumento" name="btnBuscarDocumento" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), byId('hddIdEmpresa').value, byId('txtIdBeneficiario').value, byId('buscarFact').value);" >Buscar</button>
                             </td>
                            <td><button type="button" onClick="byId('frmBuscarDocumento').reset(); byId('btnBuscarDocumento').click();" >Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0" width="100%">                    
                    <tr align="center">
                        <td class="rafktabs_title" id="tdFacturas"  width="120px">Facturas</td>
                        <td class="rafktabs_title" id="tdNotaCargo" width="120px">Notas De Cargo</td>                        
						<td align="right">
                            <table style="margin-right: 100px;">
                                <tr>
                                    <td width="120" align="right" class="tituloCampo">Días Vencidos:</td>
                                    <td align="left" id="tdDiasVencidos">
                                </tr>
                            </table>
                        </td>
		            </tr>
					</table>
				</td>               
            </tr>
                 
            <tr>
				<td class="rafktabs_panel" id="tdContenidoDocumento" style="display:none; border:0px;"></td>
            </tr>
            </table></form>
        </td>
    </tr>
	<tr>
    	<td align="right">
			<hr>
			<input type="button" onclick="byId('divFlotante1').style.display='none';" value="Cancelar">
		</td>
          </form>
    </tr>
    </table>

    
    <table border="0" id="tblBancos" style="display:none" width="610">
        <tr>
            <td id="tdDescripcionArticulo">
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr>
                <input type="button" id="" name="" onclick="byId('divFlotante1').style.display='none';" value="Cancelar">
            </td>
        </tr>
        </table>
</div>


<div id="divFlotanteAnular" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTituloAnular" class="handle"><table><tr><td id="tdFlotanteTituloAnular" width="100%">Eliminar Transferencia</td></tr></table></div>
	<form id="frmClaveAnular" name="frmClaveAnular" onsubmit="return false;">
	<table border="0" id="tblClaveAprobacionOrden">
		<tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
        <tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo" >Nro Transferencia:</td>
		<td>
			<input type="text" id="txtNumTransferencia" name="txtNumTransferencia"  readonly="readonly">
            <input type="hidden" id="hddIdTransferencia" name="hddIdTransferencia" readonly="readonly" />
		</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo">Clave:</td>
			<td><label>
				<input name="txtClaveAnular" id="txtClaveAnular" type="password" class="inputInicial" />
			</label></td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td align="right" colspan="2">
			<hr>
			<input type="submit" id="btnGuardar" name="btnGuardar" onclick="validarClaveAnular();" value="Aceptar" />
			<input type="button" onclick="byId('divFlotanteAnular').style.display='none';" value="Cancelar">
			</td>
		</tr>
	</table>
    </form>
</div>


<div id="divFlotanteClave" class="root" style="position:absolute; cursor:auto; display:none; left:0px;; top:0px; z-index:3;">
	<div id="divFlotanteTituloClave" class="handle"><table><tr><td id="tdFlotanteTituloClave" width="100%"></td></tr></table></div>
	<form id="frmClave" name="frmClave" onsubmit="return false;">
            <input type="hidden" id="hddPermiso" name="hddPermiso" title="hddPermiso" value="0" />
	<table border="0" id="tblClaveAprobacionOrden">
		<tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
        <tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo">Clave:</td>
			<td><label>
				<input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial" />
			</label></td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td align="right" colspan="2">
			<hr>
			<input type="submit" id="btnGuardar" name="btnGuardar" onclick="validarClaveAprobacion();" value="Aceptar" />
			<input type="button" onclick="byId('divFlotanteClave').style.display='none';" value="Cancelar">
			</td>
		</tr>
	</table>
    </form>
</div>


<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%">PROPUESTA DE PAGO</td></tr></table></div>
   	<table border="0" width="100%">
            <tr>
                <td class="tituloCampo" style="white-space:nowrap;" align="right">N&uacute;mero de Propuesta</td>
                <td id="numeroPropuestaPago" ></td>
                <td class="tituloCampo" style="white-space:nowrap;"  align="right">Fecha de Propuesta</td>
                <td id="fechaPropuestaPago" ></td>
                <td class="tituloCampo"  style="white-space:nowrap;" align="right">N&uacute;mero de Trasnferencia</td>
                <td id="numeroTransferenciaPropuestaPago" ></td>
                <td class="tituloCampo"  style="white-space:nowrap;" align="right">Estado de Propuesta</td>
                <td id="estadoPropuestaPago" ></td>
            </tr>
        </table>
        <fieldset>
            <legend class="legend">Detalles de la Propuesta</legend>
            <div id="detallePropuestaPago"></div>
        </fieldset>
    <table border="0"  width="100%">
        <tr>
            <td align="right">
                <hr>
                <input type="button" onclick="byId('divFlotante3').style.display='none';" value="Cancelar">
            </td>
        </tr>
    </table>
</div>

<script>

xajax_listadoTransferencia(0,'fecha_registro','DESC','-1|0||');
xajax_comboEmpresa('tdSelEmpresa','selEmpresa','');
xajax_comboEstado();
xajax_comboRetencionISLR();
xajax_cargaLstModulo('', "onchange=\"byId('btnBuscarDocumento').click();\"");//te general
xajax_cargarDiasVencidos();//te general

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

//$("#txtNumCuenta").mask("9999-9999-99-9999999999",{placeholder:" "});//panama no usa
        
new JsDatePick({
    useMode:2,
    target:"txtFechaRegistro",
    dateFormat:"<?php echo spanDatePick; ?>",
    cellColorScheme:"red"
});

var theHandle = byId("divFlotanteTitulo");
var theRoot   = byId("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTitulo1");
var theRoot   = byId("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTituloClave");
var theRoot   = byId("divFlotanteClave");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTituloAnular");
var theRoot   = byId("divFlotanteAnular");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTitulo3");
var theRoot   = byId("divFlotante3");
Drag.init(theHandle, theRoot);

</script>