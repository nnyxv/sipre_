<?php

require("../connections/conex.php");
session_start();
require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
require("controladores/ac_fi_form_financiamiento.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Financiamiento - Pedido</title>
	<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>   
	<link rel="stylesheet" type="text/css" href="../js/domDragFinanciamientos.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
     
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
	
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
    <script src="../js/highcharts/js/highcharts.js"></script>
    <script src="../js/highcharts/js/modules/exporting.js"></script>
	
<!-- <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/> -->    
<!-- <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/> -->    
        
<!-- Reacomodando valores del ccs por la cantidad de informacion dentro de los modales -->
        
<style type="text/css">
	.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
	}
</style>
 
 <script>

	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		if (valor != "Factura") {
			document.forms['frmLista'].reset();
		}
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('divLista').style.display = 'block';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			document.forms['frmBuscar'].reset();
			byId('txtDireccionCliente').value = '';
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
			
		}else if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				byId('trBuscarCliente').style.display = 'block';
				byId('trBuscarDocumento').style.display = 'none';
				byId('btnAsignarMonto').style.display = 'none';
				byId('trBuscarMonto').style.display = 'none';
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "800";
				
			} else	if (valor == "Factura") {
				
				byId('trBuscarDocumento').style.display = 'block';
				byId('txtCriterioBusq').className = 'inputHabilitado';
				byId('trBuscarCliente').style.display = 'none';
				byId('btnAsignarMonto').style.display = 'none';
				byId('trBuscarMonto').style.display = 'none';
				byId('btnBuscarDocumento').click();
				
				tituloDiv1 = 'Seleccionar Factura';
				byId(verTabla).width = "760";
				
			} else	if (valor == "Monto") {
				document.forms['frmBuscarMonto'].reset();
				
				byId('trBuscarMonto').style.display = 'block';
				byId('trBuscarDocumento').style.display = 'none';
				byId('txtMonto').className = 'inputHabilitado';
				byId('txtDescripcionMonto').className = 'inputHabilitado';
				byId('txtObservacionMonto').className = 'inputHabilitado';
				byId('trBuscarCliente').style.display = 'none';
				byId('btnAsignarMonto').style.display = 'block';
				byId('divLista').style.display = 'none';
				
				tituloDiv1 = 'Ingresar Monto';
				byId(verTabla).width = "600";
			} 
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else 	if (valor == "Factura") {
				byId('txtCriterioBusq').focus();
				byId('txtCriterioBusq').select();
				xajax_cargaLstModulo('', "onchange=\"byId('btnBuscarDocumento').click();\"");//CARGAR LISTA DE MODULOS
			} 
		}
	}

	function validarMonto(){
		if (!(validarCampo('txtDescripcionMonto','t','text') == true
				&& validarCampo('txtMonto','t','numPositivo') == true)){
					validarCampo('txtDescripcionMonto','t','text');
					validarCampo('txtMonto','t','numPositivo');
		}else{
				xajax_asignarMonto(xajax.getFormValues('frmBuscarMonto'),xajax.getFormValues('frmFacturas'));xajax_calcularMonto(xajax.getFormValues('frmFacturas'));
		}
	}
	

</script>

</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_financiamiento.php'); ?>
    </div>

    <div id="divInfo" class="print">
      <table width="100%" border="0">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table width="100%" border="0">
				<tr>
					<td class="tituloPaginaFinanciamientos" colspan="2">Pedido de Financiamiento</td>
				</tr>
				<tr class="noprint">
					<td align="left">
						<table width="100%" border="0">
						<tr align="left">
		                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
		                    <td width="29%">
		                        <table cellpadding="0" cellspacing="0">
		                        <tr>
		                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" class="inputHabilitado" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
		                            <td>
		                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
		                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
		                            </a>
		                            </td>
		                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
		                        </tr>
		                        </table>
		                    </td>
		                    <td id="tdUsuario"  width="29%" >
		                        <table cellpadding="0" cellspacing="0">
		                        <tr>
		                            <td  width="13%" ></td>
				                    <td align="right" width="8%" class="tituloCampo">Vendedor:</td>
		                            <td  width="13%" >
		          							<input style="text-align: center;" type="text" id="txtUsuario" name="txtUsuario" readonly="readonly" size="25"/>
											<input style="text-align: center;" type="hidden" id="hddIdUsuario" name="hddIdUsuario" />		                            	
		                       		</td>
		                        </tr>
		                        </table>
		                    </td>
		                    <td width="10%"></td>
		                    <td width="29%" >
		                        <table cellpadding="0" cellspacing="0">
		                        <tr id="trNroPedido" name="trNroPedido" style="display:none">
				                    <td align="right" width="8%" class="tituloCampo">Nro. Pedido:</td>
		                            <td  width="13%" ><input style="text-align: center;" type="text" id="txtPedido" name="txtPedido" readonly="readonly" size="12"/></td>
		                        </tr>
		                        </table>
		                    </td>
						</tr>
						</table>
	                </td>
	            </tr>
		        <tr>
	                <td colspan="2">
	                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
	                       <tr>
	                        <td valign="top" width="60%">
					             <fieldset><legend class="legend">Cliente</legend>
				                        <table border="0" width="100%">
				                        <tr align="left">
				                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
				                            <td colspan="3">
				                                <table cellpadding="0" cellspacing="0">
				                                <tr>
				                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" class="inputHabilitado" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false');" size="6" style="text-align:right"/></td>
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
	                            </td>
	                            <td valign="top" width="40%">
	                            <fieldset><legend class="legend">Datos del Financiamiento</legend>
	                                <table border="0" width="100%">
		                                <tr align="justify" width="100%">
		                                    <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Tipo de Interes:</td>
		                                    <td width="25%" id="tdlstTipoInteres" name="tdlstTipoInteres"></td>
		                                    <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Fecha de Pedido:</td>
		                                    <td width="25%"><input type="text" style="text-align:center" id="txtFechaPedido" name="txtFechaPedido" value="<?php echo date(spanDateFormat); ?>"  readonly size="10"/></td>
		                                </tr>
		                                <tr align="center">
                    						<td align="right" width="25%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Financiar:</td>
                    						<td align="left" width="25%"><input type="text" id="txtFechaInicial" name="txtFechaInicial" onblur="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);" autocomplete="off" size="10" style="text-align:center"/></td>
                    						<td align="right" width="25%"class="tituloCampo">Interes de Mora:</td>
                    						<td align="left" width="25%"id="tdInteresMoraFinanciar" name="tdInteresMoraFinanciar"></td>
		                                </tr>
		                                <tr id="trBotonesLista" name="trBotonesLista" style="display: none" align="center">
		                                	<td colspan="4">
			                                	<table align="center" border="0" width="100%">
			                                		<tr>
					                                    <td align="right" width="33%">
					                                        <a class="modalImg" id="listarFacturas"  rel="#divFlotante1" onclick="abrirDivFlotante1(this,'tblLista','Factura');">
				                                            	<button type="button" id="btnListarFacturas" name="btnListarFacturas" title="Agregar Factura"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Facturas</td></tr></table></button>
				                                            </a>
					                                    </td>
					                                    <td align="center" width="33%">
					                                        <a class="modalImg" id="frmPrestamo1"  rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Monto');">
				                                            	<button type="button" id="btnFrmPrestamo1" name="btnFrmPrestamo1"  title="Agregar Monto a Financiar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Monto</td></tr></table></button>
				                                            </a>
					                                    </td>
					                                    <td align="left" width="33%">
					                                        <a class="modalImg" id="eliminarItem"  rel="#divFlotante1" onclick="xajax_eliminarFactura(xajax.getFormValues('frmFacturas'));xajax_calcularMonto(xajax.getFormValues('frmFacturas'));">
				                                            	<button type="button" id="btnEliminarItem" name="btnEliminarItem"  title="Eliminar Item"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar Item</td></tr></table></button>
				                                            </a>
					                                    </td>	
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
		                </table>
		            </form>
	            </td>
	        </tr>
            <tr>
            	<td colspan="8">
                	<form id="frmFacturas" name="frmFacturas" >
                	<table width="100%">
                    	<tr>
                            <td id="tdListadoFacturas">
                                <table border="0" class="tablaStripped" cellpadding="2" width="100%">
                                    <tr class="tituloColumna">
                                        <td width='1%' class="noprint"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmFacturas');"/></td>
                                        <td width='12%'>id.</td>
                                        <td width='12%'>Tipo</td>
                                        <td width='35%'>Descripci&oacute;n</td>
                                        <td width='20%'>Fecha</td>
                                        <td width='20%'>Saldo</td>
                                    </tr>
                                    <tr id="trItmPie"></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                	</form>
                </td>
            </tr>     
      	 <tr>
        	<td align="right">
            	<table width="100%">
	                <tr align="right">
	                	<td width="76%"></td>
	                	<td width="12%" class="tituloCampo"><b>Total Monto: </b></td>
	                    <td width="12%" id="tdTotalFinanciamiento" class="trResaltarTotal"></td>
					</tr>
                </table>
            </td>
        </tr>

	    <tr>
	       <td>
	         <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
	            <tr>
	               <td height="15px" align="left"></td>
	           </tr>
	         </table>
	       </td>
	    </tr>
	    
        <tr id="trfrmFinanciamientoDetalle" name="trfrmFinanciamientoDetalle" style="visibility: hidden">
                <td>
                   <form id="frmFinanciamientoDetalle" name="frmFinanciamientoDetalle" >
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                       <tr>
                        <td>
                           <fieldset><legend class="legend">Completar financiamiento</legend>
                              <table border="0" width="100%">
	                               	<tr align="center">
	                                    <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Interes a Financiar:</td>
	                                    <!-- Se puede cambiar en caso de que se quiera colocar manualmente y no se haga esta opcion por manteniemiento -->
	                                    <td align="left" id="tdInteresFinanciar" name="tdInteresFinanciar" width="4%" onchange="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);"></td>
	                                    <!--<td align="left" width="4%"><input type="text" style="text-align:right" id="txtInteresFinanciar"  onkeypress="return validarNumerosConPuntos(event);" onchange="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);" name="txtInteresFinanciar"  class="inputHabilitado" size="5"/> %</td>-->
	                                    <td width="7%" id="tdPlazoInteres" name="tdPlazoInteres" onchange="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);"></td>
	                                    <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Frecuencia de Pago:</td>
	                                    <td  align="left" width="14%" id="tdlstFrecuenciaPago" onchange="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);"></td>
	                                    <td width="7%"></td>
	                                    <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Duracion de Pago:</td>
	                                    <td align="left" width="2%"><input type="text" style="text-align: center" id="txtCuotasFinanciar" onkeypress="return validarNumeros (event);" onchange="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);" name="txtCuotasFinanciar" class="inputHabilitado" size="10"/></td>
	                                    <td width="16%" id="tdlstDuracionPago" align="left" onchange="xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);"></td>
	                                </tr>
	                               	<tr align="center">
	                                    <td width="14%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto a Financiar:</td>
	                                    <td width="4%" align="left" ><input type="text" style="text-align:center" id="txtMontoPagoDocumentos" readonly name="txtMontoPagoDocumentos" /></td>
	                                    <td width="14%"></td>
	                                    <td align="right" class="tituloCampo"width="14%" ><span class="textoRojoNegrita">*</span>Numero de Pagos:</td>
	                                    <td align="left"width="3%"><input type="text" style="text-align:center" id="txtNumeroPagos" name="txtNumeroPagos" readonly size="5"/></td>
	                                    <td width="10%"></td>
	                                    <td align="right" class="tituloCampo" width="14%" style="visibility: hidden"><span class="textoRojoNegrita">*</span>Tipo de Interes Efectivo:</td>
	                                    <td align="left" width="2%"><input type="text" style="text-align:center;visibility:hidden;" id="txtInteresFinanciarEfectivo" name="txtInteresFinanciarEfectivo" readonly size="10"/></td>
	                                    <td width="16%"></td>
	                                </tr>
	                                <tr align="center">
	                                    <td style="height:20px"></td>
	                                </tr>
                                </table>
                          </fieldset>
                       </td>
					  </tr>
					  <tr style="height: 10px;"></tr>
					  <!-- ADICIONALES -->
					  <tr>
					  	<td>
					  		<fieldset><legend class="legend">Adicionales del Financiamiento</legend>
					  			<table border="0" width="100%">
					  				 <tr id="trBotonesListaAdicionales" name="trBotonesListaAdicionales"  align="center" width="100%">
		                                    <td align="right" class="tituloCampo" width="10%">Adicional:</td>
		                                    <td id="tdAdicionalFinanciar" name="tdAdicionalFinanciar" width="10%"></td>
		                                    <td  width="20%"></td>
		                                    <td align="right" width="10%">
		                                        <a class="modalImg" id="agregarAdicional"  onclick="xajax_insertarAdicional(xajax.getFormValues('frmFinanciamientoDetalle'),'');">
	                                            	<button type="button" id="btnAgregarAdicional" name="btnAgregarAdicional" title="Agregar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Adicional</td></tr></table></button>
	                                            </a>
		                                    </td>
		                                    <td align="left" width="10%">
		                                        <a class="modalImg" id="eliminarAdicional" name="eliminarAdicional" onclick="xajax_eliminarAdicionalLote(xajax.getFormValues('frmFinanciamientoDetalle'));">
	                                            	<button type="button" id="btnEliminarAdicional" name="btnEliminarAdicional"  title="Eliminar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Eliminar Adicional</td></tr></table></button>
	                                            </a>
		                                    </td>
		                                    <td  width="40%"></td>
		                                </tr>
		                                <tr id="pieAdicionales" width="100%">
		                                	 <td id="tdListadoAdicionales" width="100%" colspan="6">
				                                <table border="0" class="tablaStripped" cellpadding="2" width="100%">
				                                    <tr class="tituloColumna">
				                                        <td width='1%' class="noprint"><input type="checkbox" id="cbxItmAdicional" onclick="selecAllChecks(this.checked,this.id,'frmFinanciamientoDetalle');"/></td>
				                                        <td width='12%'>id.</td>
				                                        <td width='23%'>Nombre</td>
				                                        <td width='25%'>Tipo</td>
				                                        <td width='25%'>Monto</td>
				                                        <td width='10%'>Eliminar Item</td>
				                                    </tr>
				                                    <tr id="trItmPieAdicional"></tr>
				                                </table>
				                            </td>
		                                </tr>
					  			</table>
					  		</fieldset>
					  	</td>
					  </tr>
					  <tr class="noprint" id="trBotonesGenerar" style="display: none;">
					  	<td width="100%">
						  	<table width="100%" border="0" style="padding: 15px;">
						  		<tr width="100%">
								  	<td width="28%"></td>
								  	<td width="8%" align="right"><img src="../img/financiamientos/flecha-animada-izquierda-derecha.gif"/></a></td>
							        <td width="27%">
									   <button type="button"  id="btnGenerar" name="btnGenerar" onclick="xajax_generarCuadro(xajax.getFormValues('frmBuscar'),xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value);" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_examinar.png"/></td><td>&nbsp;</td><td><b>GENERAR CUADRO DE AMORTIZACIONES</td></tr></table></button>
							        </td>
								  	<td width="8%" align="left"><img src="../img/financiamientos/flecha-animada-derecha-izquierda.gif"/></td>
								  	<td width="28%"></td>
						  		</tr>
						  	</table>
					  	</td>
					  </tr>
					  <tr  id="cuadroAmortizaciones" class="trCuadro"></tr>
					  <tr  id="pieAmortizaciones"></tr>
			  	 	  <tr>
				         <td>
					        <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
					           <tr>
					               <td height="15px" align="left"></td>
					           </tr>
					        </table>
					     </td>
	    			 </tr>
			            <tr class="noprint" id="trBotones" style="visibility : hidden">
			                <td align="right">
			                     <table width="100%">
				                    <tr>
				                    	<td width="84%"></td>
				                    	<td width="8%">
					                    <button type="button" id="btnGuardar" onclick="xajax_guardarFinanciamiento('<?php echo $_GET['id'];?>',xajax.getFormValues('frmFacturas'),xajax.getFormValues('frmBuscar'),xajax.getFormValues('frmFinanciamientoDetalle'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
				                    	</td>
				                    	<td width="8%">
					                    <button type="button" id="btnCancelar" name="btnCancelar" onclick="document.location.href='fi_financiamiento_list.php';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
				                    	</td>
				                    </tr>
			                	</table>
			                </td>
			            </tr>
                  </table>
                 </form>
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

<!-- MODALES FLOTANTES -->


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<!-- Listas-->  
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr id="trBuscarCliente" width="100%">
        <td width="47%"></td>
		<td width="53%">
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            	<table width="100%">
                <tr align="right" width="100%">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'),xajax.getFormValues('frmBuscar'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarDocumento" width="100%">
    	<td width="10%"></td>
    	<td width="90%">
        <form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="$('btnBuscarDocumento').click(); return false;" style="margin:0">
            	<table width="100%">
                <tr align="right" width="100%">
                    <td align="right" class="tituloCampo" width="120">Departamento:</td>
                    <td id="tdlstModulo"></td>
                	<td align="right" class="tituloCampo" width="115">Criterio:</td>
                	<td>
                        <input type="hidden" id="buscarFact" name="buscarFact" value = "1"/>
                    	<input type="text" id="txtCriterioBusq" name="txtCriterioBusq" class="inputHabilitado" onkeyup="$('btnBuscarDocumento').click();"/>
					</td>
                    <td>
                    	<button type="button" id="btnBuscarDocumento" name="btnBuscarDocumento" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscar'),xajax.getFormValues('frmBuscarDocumento'));" >Buscar</button>
                    </td>
                    <td>
                    	<button type="button" onClick="byId('frmBuscarDocumento').reset(); byId('btnBuscarDocumento').click();" >Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    <tr id="trBuscarMonto">
    	<td>
	        <form id="frmBuscarMonto" name="frmBuscarMonto" onsubmit="return false;" style="margin:0">
	            	<table width="100%">
	            		<input type="hidden" id="hddIdLstMonto" name="hddIdLstMonto" value="m0nt0"></input>
		                <tr>
		                    <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Monto Solicitado:</td>
		                	<td align="right"  width="15%"><input type="text" style="text-align: right" onblur="setFormatoRafk(this, 2);" id="txtMonto" name="txtMonto"/></td>
		                	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Descripcion del Monto:</td>
		                	<td align="left" width="35%"><input type="text"  id="txtDescripcionMonto" name="txtDescripcionMonto"/></td>
		                </tr>
		                <tr>
							<td align="right" class="tituloCampo" rowspan="3">Observacion:</td>
                            <td align="left" colspan="4"><textarea id="txtObservacionMonto" name="txtObservacionMonto" cols="55" rows="3"></textarea></td>
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
			<hr>
            <tr>
                <td align="right">
                    <table width="100%">
	                    <tr>
	                    	<td width="75%"></td>
	                    	<td width="12%">
		                    	<button type="submit" id="btnAsignarMonto" name="btnAsignarMonto" onclick="validarMonto();">Aceptar</button>
	                    	</td>
	                    	<td width="12%">
		                    	<button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
	                    	</td>
	                    </tr>
                	</table>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
 
<!-- Lista de las empresas -->  
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana"/>
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
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
	
</div>


<!-- ----------------------------------------- -->

<script language="javascript">

//CARGANDO XAJAX INICIALES
<?php if(isset($_GET['id'])) {?>
	xajax_cargarCampos('<?php echo $_GET['id']; ?>');
<?php }else{?>
	xajax_cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres');
	xajax_cargaLstTipoInteres('','selectTipoInteres');
	xajax_cargaLstInteres('','selectInteres'); //En caso de colocar los intereses por el moduulo de mantenimiento
	xajax_cargaLstInteresMora('','selectInteresMora'); //En caso de colocar los intereses por el moduulo de mantenimiento
	xajax_cargaLstAdicionales('','selectAdicionales');
	xajax_asignarEmpresaUsuario('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'Empresa', 'ListaEmpresa', '', '');
	xajax_cargarUsuario('<?php echo $_SESSION['nombreUsuarioSysGts']; ?>');
<?php }?>

//CARGA FECHA INICIAL

byId('txtFechaInicial').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaInicial").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaInicial",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"ocean_blue"
	});
	
};

//CARGA LOS MODALES

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
						//color: '#000000', 
						zIndex: 10090, 
						closeOnClick: false, 
						closeOnEsc: false, 
						loadSpeed: 0, 
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						//color: '#000000', 
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
						//color: '#000000', 
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

//MOVIMIENTO DE MODALES

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

function formatoNumero($cantidad){
    return number_format($cantidad,2,".",",");
}


function validarNumeros (evento){
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id;
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 8) // basckspace
		&& (teclaCodigo != 9) // tab
		&& (teclaCodigo != 13) // enter
		&& (teclaCodigo != 46) // delete
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)) { // 0 al 9
			return false;
		}
}

function validarNumerosConPuntos (evento){
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id;
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 8) // basckspace
		&& (teclaCodigo != 9) // tab
		&& (teclaCodigo != 13) // enter
		&& (teclaCodigo != 46) // delete
		&& (teclaCodigo != 190) // es el .
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)) { // 0 al 9
			return false;
		}
}
</script>