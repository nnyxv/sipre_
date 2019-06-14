<?php

require("../connections/conex.php");
session_start();
require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
require("controladores/ac_fi_pago_pedido.php");

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
		max-height:700px;
		position: absolute;
	}
</style>
<script>


function abrirDivFlotante1(idObj,idPedido){ 	

	document.forms['frmMotivos'].reset();
	openImg(idObj);
	byId('tdFlotanteTitulo1').innerHTML = 'Asignar Motivos Interes de Mora';
	byId('tdFlotanteTitulo1').width= '800px';
	byId('divFlotante1').style.left = '200px';
	xajax_cargarFrmInteresMora(xajax.getFormValues('frmPagoPedido'));
	
}	

function abrirDivFlotante2(nomObjeto, valor) {

	document.forms['frmBuscarMotivo'].reset();
	byId('hddObjDestinoMotivo').value = valor;
	byId('btnBuscarMotivo').click();
	tituloDiv1 = 'Motivos de Interes de Mora';
	openImg(nomObjeto);
	byId('tdFlotanteTitulo2').innerHTML = tituloDiv1;
	byId('txtCriterioBuscarMotivo').focus();
	byId('txtCriterioBuscarMotivo').select();
	
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
	        	  <form id="frmPagoPedido" name="frmPagoPedido" action="return false;">
	        		<table  width="100%" border="0">
	        			<tr>
							<td colspan="2"  align="right" class="tituloPaginaFinanciamientos">Pago de Cuotas</td>
	        			</tr>
	        			<tr>
	        				<td >
								<dl>
								
							    <dt>RESUMEN DEL PEDIDO</dt>
							    
								    <dd>
								    	<table width="100%" border="0">
											<tbody>
											<tr class="noprint">
												<td align="left">
									            <fieldset><legend class="legend">Datos Generales</legend>
													<table width="100%" border="0">
														<tr>
										                    <td id="tdEmpresa" width="15%">
										                        <table cellspacing="0" cellpadding="0">
											                        <tbody>
												                        <tr>
														                    <td class="tituloCampo" width="50%" align="right">Empresa:</td>
												                            <td width="50%"><input id="txtEmpresa" name="txtEmpresa" readonly="readonly"  type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
										                    <td id="tdUsuario" width="15%">
										                        <table width="100%" border="0">
											                        <tbody>
												                        <tr>
														                    <td class="tituloCampo" width="50%" align="right">Vendedor:</td>
												                            <td  width="50%">
											          							<input style="text-align: center;" id="txtUsuario" name="txtUsuario" readonly="readonly" size="25" type="text">
												                       		</td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
										                    <td id="tdPedido" style="visibility: visible;" width="15%">
										                        <table width="100%" border="0">
											                        <tbody>
												                        <tr>
														                    <td class="tituloCampo" width="50%" align="right">Nro. Pedido:</td>
												                            <td width="50%"><input style="text-align: center;" id="txtPedido" name="txtPedido" readonly="readonly" size="12" type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
										                    <td id="tdPedido" style="visibility: visible;" width="15%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
														                    <td class="tituloCampo" width="50%" align="right">Fecha Financiamiento:</td>
												                            <td width="50%"><input style="text-align: center;" id="txtFechaFinanciar" name="txtFechaFinanciar" readonly="readonly" size="12" type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
										                    <td id="tdPedido" style="visibility: visible;" width="15%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
														                    <td class="tituloCampo" width="50%" align="right">Fecha Culminacion:</td>
												                            <td width="50%"><input style="text-align: center;" id="txtFechaCulminar" name="txtFechaCulminar" readonly="readonly" size="12" type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
													</table>
												</fieldset>
								                </td>
								            </tr>
									        <tr>
								                <td colspan="2">
								                    <table width="100%" cellspacing="0" cellpadding="0" border="0">
								                       <tbody>
									                       <tr>
									                        <td width="40%" valign="top" rowspan="2">
													             <fieldset><legend class="legend">Datos del Cliente</legend>
												                        <table width="100%" border="0">
													                        <tbody>
														                        <tr align="left">
														                            <td class="tituloCampo" align="right">Cliente:</td>
														                            <td colspan="3">
														                                <table cellspacing="0" cellpadding="0">
														                                <tbody><tr>
														                                    <td><input id="txtIdCliente" name="txtIdCliente" class=""  size="6" style="text-align:right" readonly="" type="text"></td>
														                                    <td><input id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45" type="text"></td>
														                                </tr>
														                                <tr align="center">
														                                    <td id="tdMsjCliente" colspan="3"></td>
														                                </tr>
														                                </tbody></table>
														                                <input id="hddPagaImpuesto" name="hddPagaImpuesto" type="hidden">
														                            </td>
														                            <td class="tituloCampo" align="right"><?php echo $spanClienteCxC; ?>:</td>
														                            <td><input id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right" type="text"></td>
														                        </tr>
														                        <tr align="left">
														                            <td class="tituloCampo" rowspan="2" align="right">Dirección:</td>
														                            <td colspan="3" rowspan="2"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
														                            <td class="tituloCampo" align="right"><?php echo $spanNIT; ?>:</td>
														                            <td><input id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:right" type="text"></td>
														                        </tr>
														                        <tr align="left">
														                        </tr>
														                        <tr align="left">
														                            <td class="tituloCampo" width="16%" align="right">Teléfono:</td>
														                            <td width="15%"><input id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center" type="text"></td>
														                            <td class="tituloCampo" width="16%" align="right">Otro Teléfono:</td>
														                            <td width="15%"><input id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center" type="text"></td>
														                            <td width="16%"></td>
														                            <td width="22%"></td>
														                        </tr>
													                        </tbody>
												                        </table>
												                    </fieldset>
									                            </td>
									                            <td width="60%" valign="top">
									                            <fieldset><legend class="legend">Datos del Financiamiento</legend>
									                                <table width="100%" border="0">
										                                <tbody>
											                                <tr>
												                                <td class="tituloCampo" width="20%" align="right"></span>Interes a Financiar:</td>
											                                    <td width="20%" align="left"><input style="text-align:right" id="txtInteresFinanciar"  name="txtInteresFinanciar"  size="8" type="text"> %</td>
											                                    <td class="tituloCampo" width="20%" align="right"></span>Frecuencia de Pago:</td>
											                                    <td id="tdlstFrecuenciaPago"  width="20%" align="left"><input style="text-align:center" id="txtFrecuenciaPago"  name="txtFrecuenciaPago"  size="10" type="text"></td>
											                                </tr>
											                               	<tr align="center">
											                                    <td class="tituloCampo" width="20%" align="right"></span>Monto a Financiar:</td>
											                                    <td width="20%" align="left"><input style="text-align:center" id="txtMontoPagoDocumentos" readonly="" name="txtMontoPagoDocumentos" class="" type="text"></td>
											                                    <td class="tituloCampo" width="20%" align="right"></span>Tipo de Interes:</td>
											                                    <td width="20%" align="left"><input style="text-align:center" id="txtTipoInteres" name="txtTipoInteres" readonly="" size="10" type="text"></td>
											                                </tr>
											                                <tr>
											                                    <td class="tituloCampo" width="20%" align="right"></span>Duracion de Pago:</td>
											                                    <td width="20%" align="left"><input style="text-align: center" id="txtCuotasFinanciar"  name="txtCuotasFinanciar"  size="20" type="text"></td>
											                                    <td class="tituloCampo" width="20%" align="right"></span>Numero de Pagos:</td>
											                                    <td width="20%" align="left"><input style="text-align:center" id="txtNumeroPagos" name="txtNumeroPagos" readonly="" size="10" type="text"></td>
											                                </tr>
									                                	</tbody>
									                                </table>
									                            </fieldset>
																</td>
															</tr>
															<tr>
									                            <td width="60%" valign="top">
									                            <fieldset><legend class="legend">Datos Intereses Por mora</legend>
									                                <table width="100%" border="0">
										                                <tbody>
											                                <tr>
												                                <td class="tituloCampo" width="20%" align="right"></span>Tipo de Interes:</td>
											                                    <td width="20%" align="left"><input style="text-align:center" id="txtTipoInteresMora"  name="txtTipoInteresMora"  size="15" type="text" ></td>
											                                    <td class="tituloCampo" width="20%" align="right"></span>Valor de Interes:</td>
											                                    <td width="20%" align="left"><input style="text-align:center" id="txtValorInteresMora"  name="txtValorInteresMora"  size="10" type="text" ></td>
											                                </tr>
									                                	</tbody>
									                                </table>
									                            </fieldset>
																</td>
															</tr>
								                        </tbody>
							                        </table>
												</td>
							                </tr>
						                </tbody>
						                </table>
								    </dd>
								    
						   		<dt>AVANCE DEL FINANCIAMIENTO</dt>
						   		
								    <dd>
								    <table width="100%" border="0">
											<tbody>
											<tr class="noprint">
												<td align="left" width="50%">
										            <fieldset><legend class="legend">Datos de Tiempo</legend>
														<table width="100%" border="0">
															<tr>
											                    <td id="tdCuotasPendientes" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Cuotas Pendientes:</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtCuotasPendientes" name="txtCuotasPendientes" style="text-align: center" class="inputPorcentajeNegativo" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr>
											                    <td id="tdCuotasAmortizadas" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Cuotas Amortizadas:</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtCuotasAmortizadas" name="txtCuotasAmortizadas" style="text-align: center" class="inputPorcentajePositivo" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr>
											                    <td id="tdCuotasTotales" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Cuotas totales:</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtCuotasTotales" name="txtCuotasTotales" style="text-align: center" class="inputPorcentajePositivo" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
														</table>
													</fieldset>
								                </td>
												<td align="left" width="50%">
										            <fieldset><legend class="legend">Datos de Cuotas</legend>
														<table width="100%" border="0">
															<tr>
											                    <td id="tdInteresesPagados" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Intereses Pagados:</td>
													                             <td width="33%"></td>
													                            <td width="33%" ><input id="txtInteresesPagados" name="txtInteresesPagados" style="text-align: center" class="inputPorcentajePositivo" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr>
											                    <td id="tdCuotasAmortizadas" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Monto Amortizado:</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtMontoAmortizado" name="txtMontoAmortizado" class="inputPorcentajePositivo" style="text-align: center" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr id="trAdicionalesAmortizados" style="display: none;">
											                    <td width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Adicionales Pagados:</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtAdicionalesAmortizados" name="txtAdicionalesAmortizados" class="inputPorcentajePositivo" style="text-align: center" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr>
											                    <td id="tdMontoPagado" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Monto Pagado:</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtMontoPagado" name="txtMontoPagado" style="text-align: center" class="inputPorcentajePositivo" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr id="trSaldoAdicionales" style="display: none;">
											                    <td  width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Saldo Adicionales :</td>
													                             <td width="33%"></td>
													                            <td width="33%"><input id="txtSaldoAdicionales" name="txtSaldoAdicionales" class="inputPorcentajeNegativo" style="text-align: center" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
															<tr>
											                    <td id="tdSaldoTotal" width="100%">
											                        <table  width="100%" border="0">
												                        <tbody>
													                        <tr>
															                    <td class="tituloCampo" width="33%" align="right">Saldo Total:</td>
															                    <td width="33%"></td>
													                            <td width="33%"><input id="txtSaldoTotal" name="txtSaldoTotal" style="text-align: center" class="inputPorcentajeNegativo" readonly="readonly"  type="text"></td>
													                        </tr>
												                        </tbody>
											                        </table>
											                    </td>
															</tr>
														</table>
													</fieldset>
								                </td>
								            </tr>
						                </tbody>
						           </table>
								   </dd>
								   
								   
					   			<dt>PAGO RAPIDO</dt>
								   
								<dd>
								    <table width="100%" border="0">
										<tbody>
											<tr>
											<td align="left" width="50%">
									            <fieldset><legend class="legend">Pago Actual</legend>
													<table width="100%" border="0" >
														<tr>
										                    <td width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                        	<td class="tituloCampo" width="33%" align="right">Periodo:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtPeriodoActual" name="txtPeriodoActual" style="text-align: center"  readonly="readonly"  type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                        	<td class="tituloCampo" width="33%" align="right">Fecha Limite Pago:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtFechaLimiteActual" name="txtFechaLimiteActual" style="text-align: center"  readonly="readonly"  type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                        	<td class="tituloCampo" width="33%" align="right">Amortizacion a capital:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtCuotaAmortizacionActual" name="txtCuotaAmortizacionActual" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                         	<td width="33%" class="tituloCampo" align="right">Interes:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtInteresesActual" name="txtInteresesActual" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr id="trAdicionalesActual" name="trAdicionalesActual" style="display: none;">
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                         	<td width="33%" class="tituloCampo" align="right">Adicionales:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtAdicionalesActual" name="txtAdicionalesActual" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                         	<td width="33%" class="tituloCampo" align="right">Cuota a Pagar:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtCuotaActual" name="txtCuotaActual" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
														                    <td width="100%" id="txtPagoCuotaActual" name="txtPagoCuotaActual"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
													</table>
												</fieldset>
							                </td>
							                
							                
											<td align="left" width="50%">
									            <fieldset><legend class="legend">Pago Siguiente</legend>
													<table width="100%" border="0">
														<tr>
										                    <td width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                        	<td class="tituloCampo" width="33%" align="right">Periodo:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtPeriodoSiguiente" name="txtPeriodoSiguiente" style="text-align: center;"  readonly="readonly"  type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                        	<td class="tituloCampo" width="33%" align="right">Fecha Limite Pago:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtFechaLimiteSiguiente" name="txtFechaLimiteSiguiente" style="text-align: center"  readonly="readonly"  type="text"></td>
												                        </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                        	<td class="tituloCampo" width="33%" align="right">Amortizacion a capital:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtCuotaAmortizacionSiguiente" name="txtCuotaAmortizacionSiguiente" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                         	<td width="33%" class="tituloCampo" align="right">Interes:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtInteresesSiguiente" name="txtInteresesSiguiente" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr id="trAdicionalesSiguiente" name="trAdicionalesSiguiente" style="display: none;">
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                         	<td width="33%" class="tituloCampo" align="right">Adicionales:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtAdicionalesSiguiente" name="txtAdicionalesSiguiente" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
												                         	<td width="33%" class="tituloCampo" align="right">Cuota a Pagar:</td>
														                    <td width="33%"></td>
												                            <td width="33%" ><input id="txtCuotaSiguiente" name="txtCuotaSiguiente" style="text-align: center"  readonly="readonly"  type="text"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
														<tr>
										                    <td  width="100%">
										                        <table  width="100%" border="0">
											                        <tbody>
												                        <tr>
														                    <td width="33%"></td>
														                    <td width="33%" id="txtPagoCuotaSiguiente" name="txtPagoCuotaSiguiente"></td>
														                    <td width="33%"></td>
											                            </tr>
											                        </tbody>
										                        </table>
										                    </td>
														</tr>
													</table>
												</fieldset>
							                </td>
							              </tr>
						                </tbody>
						           </table>
								</dd>
								   
								<dt>CUADRO DE AMORTIZACIONES</dt>
								
									<dd>
										<table style="width: 100%">
											  <tr  id="cuadroAmortizaciones" class="trCuadro"></tr>
										</table>	
									</dd>
									
							   </dl>
	        				</td>
	        			</tr>
	        			<tr id="trFooter" name="trFooter">
	        				<td>
	        				    <a class="modalImg" style="display: none;" id="aAgregarMotivo" rel="#divFlotante1" onclick="abrirDivFlotante1(this,byId('hddIdPedido').value);"></a>
	        				    <input style="display: none;" type="hidden" id="hddIdPedido" name="hddIdPedido"/>
	        				    <input style="display: none;" type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/>
	        				    <input style="display: none;" type="hidden" id="hddIdInteresMora" name="hddIdInteresMora"/>
	        				    <input style="display: none;" type="hidden" id="hddVerificarMotivoInteresMora" name="hddVerificarMotivoInteresMora" value="0"/>
	        					<button type="button" id="btnCancelar" name="btnCancelar" onclick="window.location='fi_pagos_list.php';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cerrar Pago</td></tr></table></button>			
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


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:200px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1"  width="100%"></td></tr></table></div>
    <div style="overflow:auto; max-width:1050px; max-height:500px;" >
	    <form id="frmMotivos" name="frmMotivos" onsubmit="return false;">
	      <table id="tblMotivosIntMora" border="0" width="100%"></table>
	    </form>
	</div>
</div>


<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaMotivo" width="760">
	    <tr>
	        <td>
	        <form id="frmBuscarMotivo" name="frmBuscarMotivo" style="margin:0" onsubmit="return false;">
        	    <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
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

</body>
</html>

<script language="javascript">

//Acordeon de listas

 $('dl dt').click(function(){
   if ($(this).hasClass('activo')) {
        $(this).removeClass('activo');
        $(this).next().slideUp();
   } else {
        $('dl dt').removeClass('activo');
        $(this).addClass('activo');
        $('dl dd').slideUp();
        $(this).next().slideDown();
   }
});

// CARGANDO XAJAX INICIALES

xajax_validarInteresMora(<?php echo $_GET['id']; ?>);
xajax_cargarCampos(<?php echo $_GET['id']; ?>);

//RUTINA PARA PERSISTIR EN LA ASIGNACION DE LOS MOTIVOS DE INTERES POR MORA

document.addEventListener("keydown", validarIngresoMotivos, false);

function validarIngresoMotivos(e) {
	
	var keyCode = e.keyCode;
	verificar = byId('hddVerificarMotivoInteresMora').value;
	  if(keyCode == 27 && verificar == 1) {
		alert("Ingrese los motivos de los intereses por mora.");
	   	setTimeout(enlazarMotivos, 2);
	  } 	  
}

function enlazarMotivos () {
   	byId('aAgregarMotivo').click();
}


//RUTINA DE LOS MODALES

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