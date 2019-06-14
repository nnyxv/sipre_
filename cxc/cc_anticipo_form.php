<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if ((!validaAcceso("cc_captura_nota_credito_list","insertar") && in_array($_GET['acc'], array(1,2,3)) && !($_GET['id'] > 0))
|| (!validaAcceso("cc_captura_nota_credito_list","editar") && in_array($_GET['acc'], array(4)) && $_GET['id'] > 0)) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_anticipo_form.php");

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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Anticipo</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblLista').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
		if (verTabla == "tblLista") {
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarFactura').style.display = 'none';
			byId('trBuscarNotaCargo').style.display = 'none';
			
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('trBuscarCliente').style.display = '';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "760";
			} else if (valor == "Factura") {
				document.forms['frmBuscarFactura'].reset();
				
				byId('txtCriterioBuscarFactura').className = 'inputHabilitado';
				
				byId('trBuscarFactura').style.display = '';
				
				byId('btnBuscarFactura').click();
				
				tituloDiv1 = 'Facturas';
				byId(verTabla).width = "960";
			} else if (valor == "NotaCargo") {
				document.forms['frmBuscarNotaCargo'].reset();
				
				byId('txtCriterioBuscarNotaCargo').className = 'inputHabilitado';
				
				byId('trBuscarNotaCargo').style.display = '';
				
				byId('btnBuscarNotaCargo').click();
				
				tituloDiv1 = 'Notas de Débito';
				byId(verTabla).width = "960";
			}
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv1 = 'Motivos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "Factura") {
				byId('txtCriterioBuscarFactura').focus();
				byId('txtCriterioBuscarFactura').select();
			} else if (valor == "NotaCargo") {
				byId('txtCriterioBuscarNotaCargo').focus();
				byId('txtCriterioBuscarNotaCargo').select();
			}
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function calcularPagos(){
		/*$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado)').removeClass();//limpio de clases
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):odd').addClass('trResaltar5');//odd impar
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):even').addClass('trResaltar4');//even par*/
		
		$('tr[name=trItmDctoPagado]').removeClass();//limpio de clases
		$('tr[name=trItmDctoPagado]:odd').addClass('trResaltar5');//odd impar
		$('tr[name=trItmDctoPagado]:even').addClass('trResaltar4');//even par
		
		xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));
	}
	
	function calcularTotal(){
		var arregloIva = new Array();
		var txtSubTotal = 0;
		var txtSubTotalDescuento = 0;
		var txtFlete = 0;
		var txtBaseImponible = 0;
		var txtTotalIva = 0;
		var txtMontoExento = 0;
		var txtMontoExonerado = 0;
		
		if (byId('txtSubTotal').value != '')
			txtSubTotal = parseNumRafk(byId('txtSubTotal').value);
			
		if (byId('txtSubTotalDescuento').value != '')
			txtSubTotalDescuento = parseNumRafk(byId('txtSubTotalDescuento').value);
			
		if (byId('txtFlete').value != '')
			txtFlete = parseNumRafk(byId('txtFlete').value);
			
		if (byId('txtMontoExonerado').value != '')
			txtMontoExonerado = parseNumRafk(byId('txtMontoExonerado').value);
			
		if (byId('txtMontoExento').value != '')
			txtMontoExento = parseNumRafk(byId('txtMontoExento').value);
		
		var frm = document.forms['frmTotalDcto'];
		for (i = 0; i < frm.length; i++){
			if (frm.elements[i].id == 'cbxIva'){
				valorId = frm.elements[i].value;
				byId('txtSubTotalIva' + valorId).value = formatoRafk(0,2);
			}
		}
		
		if (document.getElementsByName('cbxIva[]').length == undefined) {
			txtMontoExento = 0;
			if (document.getElementsByName('cbxIva[]').checked) {
				arregloIva[0] = document.getElementsByName('cbxIva[]').value;
				
				txtBaseImpIva = parseNumRafk(byId('txtBaseImpIva' + arregloIva[1]).value);
				txtSubTotalIva = (txtBaseImpIva * parseNumRafk(byId('txtIva' + (1)).value)) / 100;
				byId('txtSubTotalIva' + arregloIva[1]).value = formatoRafk(txtSubTotalIva,2);
				txtTotalIva += parseFloat(txtSubTotalIva.toFixed(2));
				
				if (byId('hddLujoIva' + arregloIva[counter]).value != 1) {
					txtBaseImponible = txtBaseImpIva;
				}
			}
		} else {
			txtMontoExento = 0;
			for (counter = 0; counter < document.getElementsByName('cbxIva[]').length; counter++) {
				if (document.getElementsByName('cbxIva[]')[counter].checked) {
					arregloIva[counter] = document.getElementsByName('cbxIva[]')[counter].value;
					
					txtBaseImpIva = parseNumRafk(byId('txtBaseImpIva' + arregloIva[counter]).value);
					txtSubTotalIva = (txtBaseImpIva * parseNumRafk(byId('txtIva' + arregloIva[counter]).value)) / 100;
					byId('txtSubTotalIva' + arregloIva[counter]).value = formatoRafk(txtSubTotalIva,2);
					txtTotalIva += parseFloat(txtSubTotalIva.toFixed(2));
					
					if (byId('hddLujoIva' + arregloIva[counter]).value != 1) {
						txtBaseImponible = txtBaseImpIva;
					}
				}
			}
		}
		txtMontoExento = parseFloat(txtSubTotal) - parseFloat(txtSubTotalDescuento) + parseFloat(txtFlete) - txtBaseImponible;
		txtTotalAnticipo = parseFloat(txtSubTotal) - parseFloat(txtSubTotalDescuento) + parseFloat(txtFlete) + parseFloat(txtTotalIva);
		
		byId('txtSubTotal').value = formatoRafk(txtSubTotal,2);
		byId('txtSubTotalDescuento').value = formatoRafk(txtSubTotalDescuento,2);
		byId('txtFlete').value = formatoRafk(txtFlete,2);
		byId('txtTotalAnticipo').value = formatoRafk(txtTotalAnticipo,2);
		byId('txtMontoExonerado').value = formatoRafk(txtMontoExonerado,2);
		byId('txtMontoExento').value = formatoRafk(txtMontoExento,2);
	}
	
	function validarAsignarDepartamento() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('lstAplicaLibro','t','listaExceptCero') == true) {
			xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstAplicaLibro','t','listaExceptCero');
			
			selectedOption('lstModulo',-1);
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar"><span id="tituloPagina">Anticipo</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
			<form id="frmDcto" name="frmDcto" style="margin:0">
                <table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
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
                    <td width="12%"></td>
                    <td width="18%"></td>
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
                            
                                <table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <button type="button" id="btnAnticipoPDF"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Anticipo PDF</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Anticipo</legend>
                                <input type="hidden" id="hddIdAnticipo" name="hddIdAnticipo"/>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Anticipo:</td>
                                    <td colspan="2">
                                        <div id="tdlstTipoAnticipo"></div>
                                        <input type="hidden" id="hddTipoAnticipo" name="hddTipoAnticipo"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Módulo:</td>
                                    <td id="tdlstModulo" colspan="2" width="60%"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Anticipo:</td>
                                    <td colspan="2"><input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" placeholder="Por Asignar" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
                                    <td id="tdtxtFecha" colspan="2"><input type="text" id="txtFecha" name="txtFecha" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                                    <td class="tituloCampo">Total:</td>
                                    <td id="tdTotalRegistroMoneda"></td>
                                    <td><input type="text" id="txtTotalAnticipo" name="txtTotalAnticipo" class="inputSinFondo" readonly="readonly" size="17" style="text-align:right"/></td>
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
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
			</form>
			</td>
		</tr>
        <tr id="trListaPagoDcto">
        	<td>
            <fieldset><legend class="legend">Desglose de Pagos</legend>
            <form id="frmListaPagoDcto" name="frmListaPagoDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
            	<table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaPagoDcto');"/></td>
                    <td><img src="../img/iconos/information.png" title="Mostrar en pagos de la factura"/></td>
                    <td><img src="../img/iconos/information.png" title="En la copia del banco sumar al: &#10;C = Pago de Contado &#10;T = Trade In"/></td>
                	<td width="8%">Fecha Pago</td>
					<td width="6%">Nro. Recibo</td>
                	<td width="12%">Forma de Pago</td>
                	<td width="26%">Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito</td>
                    <td width="15%">Banco Cliente / Cuenta Cliente</td>
                    <td width="15%">Banco Compañia / Cuenta Compañia</td>
                    <td width="8%">Caja</td>
                    <td width="10%">Monto</td>
                </tr>
                <tr id="trItmPiePago" align="right" class="trResaltarTotal">
                	<td class="tituloCampo" colspan="10">Total Pagos:</td>
                	<td><input type="text" id="txtTotalPago" name="txtTotalPago" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                    <td colspan="2"></td>
                </tr>
                </table>
            </form>
            </fieldset>
            </td>
		</tr>
        <tr id="trListaDctoPagado">
        	<td>
            <fieldset><legend class="legend">Documentos Pagados</legend>
            <form id="frmListaDctoPagado" name="frmListaDctoPagado" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
            	<table border="0" width="100%">
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
                <tr id="trItmPieDctoPagado" align="right" class="trResaltarTotal">
                	<td class="tituloCampo" colspan="9">Total Dctos. Pagados:</td>
                	<td><input type="text" id="txtTotalDctoPagado" name="txtTotalDctoPagado" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr class="trResaltarTotal3">
                    <td align="right" class="tituloCampo" colspan="9">Total Saldo Disponible:</td>
                    <td>
                        <input type="text" id="txtMontoRestante" name="txtMontoRestante" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/>
                        <input type="hidden" id="hddSaldoAnticipo" name="hddSaldoAnticipo"/>
                        <input type="hidden" id="hddMontoRestante" name="hddMontoRestante"/>
                    </td>
                    <td></td>
                </tr>
                </table>
            </form>
            </fieldset>
            </td>
        </tr>
		<tr>
			<td align="right">
			<form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
				<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
				<table border="0" width="100%">
				<tr>
					<td valign="top" width="50%">
                    	<table width="100%">
                        <tr align="left">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
					</td>
					<td valign="top" width="50%"></td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr>
			<script type="text/javascript">
					
					console.log('<?php 
					$v1=$_GET['id']; 
					echo $v1; ?>');

				</script>


				<script type="text/javascript">
					
						console.log('<?php 



					$query = "SELECT * FROM cj_cc_anticipo  WHERE idAnticipo = $v1 ";

					$rs = mysql_query($query);
					$reg=mysql_fetch_array($rs);
					echo "consulta";
					echo $reg['fechaAnticipo'];
					$fechaRegistro = $reg['fechaAnticipo'];
					$dateTime_fechaReconversion = '2018-08-20';

					 ?>');

				</script>
			<td align="right"><hr>
				<?php
            		/*if($fechaRegistro > $dateTime_fechaReconversion){
            			echo "<button style=\"display:none;\" type=\"hidden\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(0)\"><table align=\"left\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion  </td></tr></table></button>";
					}else{
						echo "<input type='hidden' value='1' id='muestraBtnReconversion'>";
						echo "<button type=\"hidden\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(1)\"><table align=\"left\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion  </td></tr></table></button>";
					}*/

            	?>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
	<table border="0" id="tblLista" style="display:none" width="960">
	<tr id="trBuscarCliente">
		<td>
			<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="byId('btnBuscarCliente').click(); return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
					<td>
						<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),xajax.getFormValues('frmDcto'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
					</td>
				</tr>
				</table>
			</form>
		</td>
	</tr>
	<tr id="trBuscarFactura">
		<td>
			<form id="frmBuscarFactura" name="frmBuscarFactura" onsubmit="byId('btnBuscarFactura').click(); return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td>
						<input type="text" id="txtCriterioBuscarFactura" name="txtCriterioBuscarFactura" onkeyup="byId('btnBuscarFactura').click();"/>
					</td>
					<td>
						<button type="submit" id="btnBuscarFactura" name="btnBuscarFactura" onclick="xajax_buscarFactura(xajax.getFormValues('frmBuscarFactura'), xajax.getFormValues('frmDcto'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarFactura'].reset(); byId('btnBuscarFactura').click();">Limpiar</button>
					</td>
				</tr>
				</table>
			</form>
		</td>
	</tr>
	<tr id="trBuscarNotaCargo">
		<td>
			<form id="frmBuscarNotaCargo" name="frmBuscarNotaCargo" onsubmit="byId('btnBuscarNotaCargo').click(); return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td>
						<input type="text" id="txtCriterioBuscarNotaCargo" name="txtCriterioBuscarNotaCargo" onkeyup="byId('btnBuscarNotaCargo').click();"/>
					</td>
					<td>
						<button type="submit" id="btnBuscarNotaCargo" name="btnBuscarNotaCargo" onclick="xajax_buscarNotaCargo(xajax.getFormValues('frmBuscarNotaCargo'), xajax.getFormValues('frmDcto'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarNotaCargo'].reset(); byId('btnBuscarNotaCargo').click();">Limpiar</button>
					</td>
				</tr>
				</table>
			</form>
		</td>
	</tr>
	<tr>
		<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddNumeroItm" name="hddNumeroItm">
        	<table width="100%">
            <tr>
            	<td><div id="divLista" style="width:100%;"></div></td>
			</tr>
            <tr>
                <td align="right"><hr>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
	</tr>
	</table>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana"/>
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" onsubmit="return false;" style="margin:0">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaMotivo" width="760">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" class="inputHabilitado" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaMotivo" name="frmListaMotivo" style="margin:0" onsubmit="return false;">
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
</div>

<script>
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

xajax_formDcto('<?php echo $_GET['id']; ?>','<?php echo $_GET['acc']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>

<script type="text/javascript">
	
	function reconversionMonetaria(activo){

	if(activo == 0){

		alert("No está permitido reconvertir una nota de credito con fecha posterior al 20-Agosto-2018");
		return false;

	}else{

		var idAnticipoReconversion = <?php echo $_GET['id'] ?>;
		var confirmacion = confirm("¿Desea realizar la reconversión del Anticipo ? Esta acción no se puede revertir");

		if (confirmacion == true){
			mensaje = xajax_reconversion(idAnticipoReconversion);

		}else{
			return false;
		}
	}

	return true;
}

</script>