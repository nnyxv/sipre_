<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_documento_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_documento_venta_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Documentos de Ventas</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
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
    
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblPedido').style.display = 'none';
		
		if (verTabla == "tblPedido") {
			document.forms['frmPedido'].reset();
			
			byId('txtFechaEntrega').className = 'inputHabilitado';
			
			xajax_formPedido(valor, xajax.getFormValues('frmPedido'));
			
			tituloDiv1 = 'Editar Orden de Pedido';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPedido") {
			byId('txtIdPedido').focus();
			byId('txtIdPedido').select();
		}
	}
	
	function abrirDivFlotante2(idObj, tituloDiv, idPedido, idContrato, tipoAccion){ 	
		document.forms['frmContrato'].reset();
		
		$('.trDedCrLife').css('visibility','hidden');
		$('.trDedContServicio').css('visibility','hidden');
		$('.trDedGap').css('visibility','hidden');
		$('.tdOtroGAP').css('visibility','hidden');
		
		$('#frmContrato').hide();
		$('#frmImpDoc').hide();
		byId('rdAct2').disabled = false;
		byId('rdAct3').disabled = false;
		byId('rdAct4').disabled = false;
		
		if (tipoAccion == 0) {
			byId('frmContrato').style.display = 'block';
			titulo = "Datos adicionales Contrato de Venta";		
			byId('hddIdPedido').value = idPedido;
			byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
			openImg(idObj);
			byId(tituloDiv).innerHTML = titulo;
			xajax_formContrato(idPedido, idContrato);
		} else if (tipoAccion == 1) {
			byId('frmContrato').style.display = 'block';
			titulo = "Editar adicionales Contrato de Venta";
			byId('hddIdPedido').value = idPedido;
			byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
			openImg(idObj);	
			byId(tituloDiv).innerHTML = titulo;
			xajax_formContrato(idPedido, idContrato);
		} else if (tipoAccion == 2) { // Imprimir
			byId('frmImpDoc').style.display = 'block';
			titulo = "Imprimir Documentos Contrato Financiamiento";
			openImg(idObj);	
			byId(tituloDiv).innerHTML = titulo;
			xajax_formDocumentos(idPedido, idContrato);
		}
	}
	
	function validarFrmPedido() {
		if (validarCampo('txtIdPedido','t','') == true
		&& validarCampo('txtFechaEntrega','t','fecha') == true) {
			xajax_guardarPedido(xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaPedido'));
		} else {
			validarCampo('txtIdPedido','t','');
			validarCampo('txtFechaEntrega','t','fecha');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

	function validarFormContrato(){
		//Guardando
		if (validarCampo('lstGerenteVenta','t','') == true
		&& validarCampo('txtCargoPorFinan','t','monto') == true) {
			xajax_guardarContrato(xajax.getFormValues('frmContrato'),xajax.getFormValues('frmListaPedido')); 
		} else {
			validarCampo('lstGerenteVenta','t','');
			validarCampo('txtCargoPorFinan','t','monto');
					
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

    function showContent(valor) {

		var element,check;
    	
		 if(valor == 2){
        	element = $(".trDedCrLife");
            check = document.getElementById("rdAct2");
        }else if(valor == 3){
        	element = $(".trDedGap");
            check = document.getElementById("rdAct3");
        }else if(valor == 4){
        	element = $(".trDedContServicio");
            check = document.getElementById("rdAct4");
        }

        
        if (check.checked) {
            element.css('visibility','visible');
        }else {
        	element.css('visibility','hidden');
			if(valor == 2){
            	document.getElementById("txtPeriodoCrLife").value = '';  
            	document.getElementById("txtDedCrLife").value = '';                      
            }else if(valor == 3){
            	document.getElementById("txtPeriodoGap").value = ''; 
            	document.getElementById("txtDedGap").value = '';                       
            }else if(valor == 4){
            	document.getElementById("txtPeriodoContServicio").value = '';  
            	document.getElementById("txtDedContServicio").value = '';            
            }
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
        	<td class="tituloPaginaVehiculos">Documentos de Ventas</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
        	   <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarDocumentoVenta(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
                </tr>
                </table>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
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
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                    	<select id="lstEstatusPedido" name="lstEstatusPedido" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                            <option value="3">Pedido Desautorizado</option>
                            <option value="1">Pedido Autorizado</option>
                            <option value="2">Facturado</option>
                            <option value="4">Nota de Crédito</option>
                            <option value="5">Anulado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPedido(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPedido" name="frmListaPedido" style="margin:0">
                <div id="divListaPedido" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_marron.gif"/></td><td>Anulado</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_gris.gif"/></td><td>Factura (Con Devolución)</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_morado.gif"/></td><td>Factura</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Pedido Autorizado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"/></td><td>Pedido Desautorizado</td>
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
                        	<td><img src="../img/iconos/accept.png"/></td><td>Vehículo Entregado</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/pencil.png"/></td><td>Editar Inspección de Pre-Entrega</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/aprobar_presup.png"/></td><td>Inspección de Pre-Entrega</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page.png"/></td><td>Carta de Bienvenida</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_green.png"/></td><td>Carta de Agradecimiento</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_red.png"/></td><td>Certificado de Orígen</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_print.png"/></td><td>Orden de Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/contrato.png"/></td><td>Contrato de Venta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/contrato_editar.png"/></td><td>Editar Contrato de Venta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/contrato_imprimir.png"/></td><td>Imprimir Contrato de Venta</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td><td><a onclick="byId('divFlotante').style.display='none';" id="aCerrarDivFlotante"><img title="Cerrar" src="../img/iconos/cross.png" id="imgCerrarDivFlotante" class="close puntero" /></a></td></tr></table></div>
	
	<form id="frmImpDoc" name="frmImpDoc" onsubmit="return false;" style="margin:0">
		<div id="tblDocumentos" class="pane" style="max-height:500px; overflow:auto; width:960px;"></div>	
	</form>
	
	
	<form id="frmContrato" name="frmContrato" onsubmit="return false;" style="margin:0">
		<div class="pane" style="max-height:500px; overflow:auto; width:960px;">
	        <table border="0" id="tblProspecto" width="100%">
	        <tr>
	            <td>
	                <div class="wrap">
	                    <!-- the tabs -->
	                    <ul class="tabs">
	                        <li><a href="#">Datos Obligatorios </a></li>
	                        <li><a href="#">Co - Deudor</a></li>
	                        <li><a href="#">Seguro</a></li>
	                        <li><a href="#">Cuotas</a></li>
	                        <li><a href="#">Adicionales</a></li>
	                        <li><a href="#">Trade in</a></li>
	                    </ul>
	                    
	                    <!-- tab "panes" DATOS OBLIGATORIO-->
	                    <div class="pane">
                            <table border="0" width="100%">
                            <tr align="left">                  
                                <td width="50%">
                                <fieldset><legend class="legend"><span class="textoRojoNegrita">*</span>Gerente de Financiamiento</legend>
                                    <table border="0" width="100%">
                                    <tr align="left">
                                        <!--<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gerente de financiamiento</td>-->
                                        <td id="tdlstGerenteVenta"></td>
                                    </tr>
                                    </table>
                                </fieldset> 
                                </td>
                                <td width="50%">
                                <fieldset><legend class="legend">Datos de la Unidad</legend>
                                    <table border="0" width="100%">
                                    <tr align="center">
                                        <td>
                                            <label><input type="checkbox" id="rdSunRoof" value="1" name="rdSunRoof"/> Sun Roof</label>
                                            <label><input type="checkbox" id="rdIntCuero" value="1" name="rdIntCuero"/> Interiores de Cuero</label>
                                        </td>
                                    </tr>
                                    </table>
                                </fieldset>
                                </td>		
                            </tr>
                            <tr align="left">                  
                                <td width="50%">
                                <fieldset><legend class="legend" ><span class="textoRojoNegrita">*</span>Cargo por Financiamiento</legend>
                                    <table border="0" width="100%">
                                    <tr align="center">
                                        <td><input type="text" id="txtCargoPorFinan" name="txtCargoPorFinan" readonly onchange="setFormatoRafk(this, 2);" style="text-align:right;"/></td>
                                    </tr>
                                    </table>
                                </fieldset> 
                                </td>
                            </tr>		                        
	                        </table>
	                    </div>
	                    
	                    <!-- tab "panes" Co - Deudor -->
	                    <div class="pane">
                            <table border="0" width="70%">
                            <tr align="left">
                                <td>
                                <fieldset><legend class="legend">Elegir  Co - Deudor</legend>
                                    <table border="0" id="tblListaCliente" width="900">
                                    <tr id="trBuscarCliente">
                                        <td>
                                            <table align="right">
                                            <tr align="left">
                                                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                                <td><input type="text" id="txtCriterioBuscarCliente" onchange="byId('btnBuscarCliente').click();" name="txtCriterioBuscarCliente" /></td> 
                                                <td>
                                                    <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmContrato'));">Buscar</button>
                                                    <button type="button" onclick="document.forms['frmContrato'].reset(); byId('frmContrato').click();">Limpiar</button>
                                                </td>
                                            </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                    	<td><div id="divListaCliente" style="width:100%;"></div></td>
                                    </tr>
                                    </table>
                                </fieldset>
                                </td>	
                            </tr>
                            </table>
	                    </div>
	                    
	                    <!-- tab "panes" Poliza -->
						<div class="pane">
                            <table border="0" width="100%">
                            <tr>
                                <td width="50%">
                                <fieldset><legend class="legend" >NMAC Nombrado como Beneficiario de Perdida</legend>
                                    <table border="0" width="100%">
                                    <tr align="left" width="100%">
                                        <td align="center" width="100%">
                                            SI <input type="radio" id="rdNmacSi" value="1" name="rdNmac" /> 
                                            NO <input type="radio" id="rdNmacNo" value="2" name="rdNmac" />
                                        </td>
                                    </tr>
                                    </table>
                                </fieldset> 
                                </td>
                                <td width="50%">
                                <fieldset><legend class="legend" >Motivo de Adquisicion</legend>
                                    <table border="0" width="100%">
                                    <tr align="left" width="100%">
                                        <td align="center" >
                                            Personal <input type="radio" id="rdMotSi" value="1" name="rdMot" /> 
                                            Comercial <input type="radio" id="rdMotNo" value="2" name="rdMot" />
                                        </td>
                                    </tr>
                                    </table>
                                </fieldset> 
                                </td>   
                            </tr>
                            <tr>
                                <td>
                                <fieldset><legend class="legend">Seguro</legend>
                                    <table border="0" width="100%">
                                    <tr>
                                        <td>
                                            <table width="100%">
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">P&oacute;liza:</td>
                                                <td id="tdlstPoliza" colspan="3"></td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Nombre de la Agencia:</td>
                                                <td colspan="3"><input type="text" id="txtNombreAgenciaSeguro" name="txtNombreAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo">Dirección de la Agencia:</td>
                                                <td colspan="3">
                                                    <table width="100%">
                                                    <tr>
                                                        <td colspan="4"><textarea id="txtDireccionAgenciaSeguro" name="txtDireccionAgenciaSeguro" class="inputHabilitado" rows="3" style="width:99%"></textarea></td>
                                                    </tr>
                                                    <tr align="right">
                                                        <td class="tituloCampo" width="20%"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                                        <td width="30%"><input type="text" id="txtCiudadAgenciaSeguro" name="txtCiudadAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                                        <td class="tituloCampo" width="20%">País:</td>
                                                        <td width="30%"><input type="text" id="txtPaisAgenciaSeguro" name="txtPaisAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                                    </tr>
                                                    </table>
                                            </td>
                                            </tr>
                                            <tr align="left">
                                                <td align="right" class="tituloCampo">Teléfono de la Agencia:</td>
                                                <td colspan="3">
                                                <div style="float:left">
                                                    <input type="text" name="txtTelefonoAgenciaSeguro" id="txtTelefonoAgenciaSeguro" class="inputHabilitado" size="16" style="text-align:center"/>
                                                </div>
                                                <div style="float:left">
                                                    <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                                </div>
                                                </td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo">Numero de Poliza:</td>
                                                <td></td>
                                                <td></td>
                                                <td><input type="text" id="txtNumPoliza" name="txtNumPoliza" class="inputHabilitado"  style="text-align:center;"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo">Periodo:</td>
                                                <td></td>
                                                <td></td>
                                                <td><input type="text" id="txtPeriodoPoliza" name="txtPeriodoPoliza" class="inputCompletoHabilitado" style="text-align:right;"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo">Deducible:</td>
                                                <td></td>
                                                <td></td>
                                                <td><input type="text" id="txtDeduciblePoliza" name="txtDeduciblePoliza" class="inputCompletoHabilitado" onchange="setFormatoRafk(this, 2); percent();" style="text-align:right;"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo" width="30%">Prima:</td>
                                                <td width="34%"></td>
                                                <td width="6%"></td>
                                                <td width="30%"><input type="text" id="txtMontoSeguro" name="txtMontoSeguro" class="inputCompletoHabilitado" onchange="setFormatoRafk(this, 2); percent();" style="text-align:right;"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td align="right" class="tituloCampo">Fecha Efectividad:</td>
                                                <td></td>
                                                <td></td>
                                                <td><input type="text" id="txtFechaEfect" name="txtFechaEfect" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td align="right" class="tituloCampo">Fecha Expiracion:</td>
                                                <td></td>
                                                <td></td>                   		 
                                                <td><input type="text" id="txtFechaExpi" name="txtFechaExpi" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo"><?php echo $spanInicial; ?>:</td>
                                                <td></td>
                                                <td></td>
                                                <td><input type="text" id="txtInicialPoliza" name="txtInicialPoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                                            </tr>
                                            <tr align="right">
                                                <td class="tituloCampo">Cuotas:</td>
                                                <td>
                                                    <table border="0" cellpadding="0" cellspacing="0">
                                                    <tr align="right">
                                                        <td>Meses:</td>
                                                        <td><input type="text" id="txtMesesPoliza" name="txtMesesPoliza" class="inputHabilitado" size="6" style="text-align:center;"/></td>
                                                    </tr>
                                                    </table>
                                                </td>
                                                <td></td>
                                                <td>
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                    <tr align="right">
                                                        <td>Monto:</td>
                                                        <td width="100%"><input type="text" id="txtCuotasPoliza" name="txtCuotasPoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
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
                            </table>  
	                    </div>    
	                    
	                    <!-- tab "panes" Cuotas -->
	                    <div class="pane">
                            <table>
                            <tr>
                                <td width="470">
                                <fieldset id="fieldsetFormaPago"><legend class="legend">Contrato a Pagarse de acuedo con</legend>
                                    <table border="0" width="100%">
                                        <tbody>
                                        <tr class="trResaltar5" align="right">
                                            <td id="tdFinanciamiento" class="tituloCampo" rowspan="4">Financiamiento:</td>
                                            <td id="capameses_financiar"><table border="0"><tbody><tr align="right"><td><input id="lstMesesFinanciar" name="lstMesesFinanciar" class="inputHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:40px;" value="" type="text" /></td><td> Meses</td><td>&nbsp;/&nbsp;</td><td><input id="txtInteresCuotaFinanciar" name="txtInteresCuotaFinanciar" class="inputHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:60px;" value="" type="text" /></td><td>%</td></tr><tr align="right"><td colspan="2">Fecha Pago:</td><td colspan="3"><input id="txtFechaCuotaFinanciar" name="txtFechaCuotaFinanciar" autocomplete="off" class="inputHabilitado" size="10" style="text-align:center" value="" type="text" /></td></tr></tbody></table></td>
                                            <td id="tdCuotasFinanciarMoneda">US$</td>
                                            <td id="tdtxtCuotasFinanciar"><input id="txtCuotasFinanciar" name="txtCuotasFinanciar" class="inputCompletoHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right;" value="" type="text" /></td>
                                        </tr>
                                        <tr id="trCuotasFinanciar2" class="trResaltar4" style="" align="right">
                                            <td id="capameses_financiar2"><table border="0"><tbody><tr><td><input id="lstMesesFinanciar2" name="lstMesesFinanciar2" class="inputHabilitado" onchange="percent();  setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:40px;" value="" type="text" /></td><td> Meses</td><td>&nbsp;/&nbsp;</td><td><input id="txtInteresCuotaFinanciar2" name="txtInteresCuotaFinanciar2" class="inputHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:60px;" value="" type="text" /></td><td>%</td></tr><tr align="right"><td colspan="2">Fecha Pago:</td><td colspan="3"><input id="txtFechaCuotaFinanciar2" name="txtFechaCuotaFinanciar2" autocomplete="off" class="inputHabilitado" size="10" style="text-align:center" value="" type="text" /></td></tr></tbody></table></td>
                                            <td id="tdCuotasFinanciarMoneda2">US$</td>
                                            <td id="tdtxtCuotasFinanciar2"><input id="txtCuotasFinanciar2" name="txtCuotasFinanciar2" class="inputCompletoHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right;" value="" type="text" /></td>
                                        </tr>
                                        <tr id="trCuotasFinanciar3" class="trResaltar5" style="" align="right">
                                            <td id="capameses_financiar3"><table border="0"><tbody><tr><td><input id="lstMesesFinanciar3" name="lstMesesFinanciar3" class="inputHabilitado" onchange="percent();  setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:40px;" value="" type="text" /></td><td> Meses</td><td>&nbsp;/&nbsp;</td><td><input id="txtInteresCuotaFinanciar3" name="txtInteresCuotaFinanciar3" class="inputHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:60px;" value="" type="text" /></td><td>%</td></tr><tr align="right"><td colspan="2">Fecha Pago:</td><td colspan="3"><input id="txtFechaCuotaFinanciar3" name="txtFechaCuotaFinanciar3" autocomplete="off" class="inputHabilitado" size="10" style="text-align:center" value="" type="text" /></td></tr></tbody></table></td>
                                            <td id="tdCuotasFinanciarMoneda3">US$</td>
                                            <td id="tdtxtCuotasFinanciar3"><input id="txtCuotasFinanciar3" name="txtCuotasFinanciar3" class="inputCompletoHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right;" value="" type="text" /></td>
                                        </tr>
                                        <tr id="trCuotasFinanciar4" class="trResaltar4" style="" align="right">
                                            <td id="capameses_financiar4"><table border="0"><tbody><tr><td><input id="lstMesesFinanciar4" name="lstMesesFinanciar4" class="inputHabilitado" onchange="percent();  setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:40px;" value="" type="text" /></td><td> Meses</td><td>&nbsp;/&nbsp;</td><td><input id="txtInteresCuotaFinanciar4" name="txtInteresCuotaFinanciar4" class="inputHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right; width:60px;" value="" type="text" /></td><td>%</td></tr><tr align="right"><td colspan="2">Fecha Pago:</td><td colspan="3"><input id="txtFechaCuotaFinanciar4" name="txtFechaCuotaFinanciar4" autocomplete="off" class="inputHabilitado" size="10" style="text-align:center" value="" type="text" /></td></tr></tbody></table></td>
                                            <td id="tdCuotasFinanciarMonedaFinal">US$</td>
                                            <td id="tdtxtCuotasFinanciar4"><input id="txtCuotasFinanciar4" name="txtCuotasFinanciar4" class="inputCompletoHabilitado" onchange="percent(); asignarPrecio(); setformato(this);" onkeypress="return inputnum(event);" style="text-align:right;" value="" type="text" /></td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </fieldset>
                                </td>
                            </tr>
                            </table>  
	                    </div>    
	                  	
	                    <!-- tab "panes" Adicionales-->
	                    <div class="pane">
                            <table>
                            <tr width="100%">
                            	<td width="30%">
                                <fieldset><legend class="legend">CREDIT LIFE INS</legend>
                                    <table border="0" width="100%">
                                    <tr align="left">
                                    	<td colspan="2"><label><input type="checkbox" id="rdAct2" name="rdAct2" value="1" onchange="javascript:showContent(2);"/> Solicitado</label></td>
                                    </tr>
                                    <tr align="right" class="trDedCrLife" style="visibility:hidden;">
                                        <td class="tituloCampo">Nombre:</td>
                                        <td><input type="text" id="txtCrLifeNombre" name="txtPeriodoCrLifeNombre" class="inputInicial" style="text-align:center;" readonly /></td>
                                    </tr>
                                    <tr align="right" class="trDedCrLife" style="visibility:hidden;">
                                        <td class="tituloCampo">Precio:</td>
                                        <td><input type="text" id="txtCrLifePrecio" name="txtPeriodoCrLifePrecio" class="inputInicial" style="text-align:center;" readonly /></td>
                                    </tr>
                                    <tr align="right" class="trDedCrLife" style="visibility:hidden;">
                                        <td class="tituloCampo">Periodo:</td>
                                        <td><input type="text" id="txtPeriodoCrLife" name="txtPeriodoCrLife" class="inputHabilitado" style="text-align:center;" /></td>
                                    </tr>	
                                    <tr align="right" class="trDedCrLife" style="visibility:hidden;">
                                        <td class="tituloCampo">Deducible:</td>
                                        <td><input type="text" id="txtDedCrLife" name="txtDedCrLife" class="inputHabilitado" onchange="setFormatoRafk(this, 2);" style="text-align:center;" /></td>
                                    </tr>
                                    </table>
                                </fieldset> 
                                </td>
                                <td width="30%">
                                <fieldset><legend class="legend">GAP</legend>
                                    <table border="0" width="100%">
                                    <tr align="left">
                                        <td colspan="2"><label><input type="checkbox" id="rdAct3" name="rdAct3" onchange="javascript:showContent(3)" value="1"/> Solicitado</label></td>
                                    </tr>
                                    <tr align="right" class="trDedGap" style="visibility:hidden;">
                                        <td class="tituloCampo">Agencia:</td>
                                        <td><input type="text" id="txtGapAgencia" name="txtGapAgencia" class="inputHabilitado" style="text-align:center;" /></td>
                                    </tr>
                                    <tr align="right" class="trDedGap" style="visibility:hidden;">
                                        <td class="tituloCampo">Nombre:</td>
                                        <td><input type="text" id="txtGapNombre" name="txtPeriodoCrLifeNombre" class="inputInicial" readonly style="text-align:center;" /></td>
                                    </tr>
                                    <tr align="right" class="trDedGap" style="visibility:hidden;">
                                        <td class="tituloCampo">Precio:</td>
                                        <td><input type="text" id="txtGapPrecio" name="txtPeriodoCrLifePrecio" class="inputInicial" readonly style="text-align:center;" /></td>
                                    </tr>
                                    <tr align="right" class="trDedGap" style="visibility:hidden;">
                                        <td class="tituloCampo">Periodo:</td>
                                        <td><input type="text" id="txtPeriodoGap" name="txtPeriodoGap" class="inputHabilitado" style="text-align:center;" /></td>
                                    </tr>	
                                    <tr align="right" class="trDedGap" style="visibility:hidden;">
                                        <td class="tituloCampo">Deducible:</td>
                                        <td><input type="text" id="txtDedGap" name="txtDedGap" class="inputHabilitado" onchange="setFormatoRafk(this, 2);" style="text-align:center;" /></td>
                                    </tr>			                            
                                    </table>
                                </fieldset> 
                                </td>
                            </tr>  
                            <tr>
                                <td width="30%">
                                <fieldset><legend class="legend">Contrato de Servicio</legend>
                                    <table border="0" width="100%">
                                    <tr align="left">
                                        <td colspan="2"><label><input type="checkbox" id="rdAct4" name="rdAct4" onchange="javascript:showContent(4)" value="1"/> Solicitado</label></td>
                                    </tr>
                                    <tr align="right" class="trDedContServicio" style="visibility:hidden;">
                                        <td class="tituloCampo">Nombre:</td>
                                        <td><input type="text" id="txtContServicioNombre" name="txtPeriodoCrLifeNombre" class="inputInicial" readonly style="text-align:center;" /></td>
                                    </tr>
                                    <tr align="right" class="trDedContServicio" style="visibility:hidden;">
                                        <td class="tituloCampo">Precio:</td>
                                        <td><input type="text" id="txtContServicioPrecio" name="txtPeriodoCrLifePrecio" class="inputInicial" readonly style="text-align:center;" /></td>
                                    </tr>
                                    <tr align="right" class="trDedContServicio" style="visibility:hidden;">
                                        <td class="tituloCampo">Empresa:</td>
                                        <td id="tdlstContServicio"></td>
                                    </tr>
                                    <tr align="right" class="trDedContServicio" style="visibility:hidden;">
                                        <td class="tituloCampo">Periodo:</td>
                                        <td><input type="text" id="txtPeriodoContServicio" name="txtPeriodoContServicio" class="inputHabilitado" style="text-align:center;" /></td>
                                    </tr>
                                    <tr align="right" class="trDedContServicio" style="visibility:hidden;">
                                        <td class="tituloCampo">Deducible:</td>
                                        <td><input type="text" id="txtDedContServicio" name="txtDedContServicio" class="inputHabilitado" onchange="setFormatoRafk(this, 2);" style="text-align:center;" /></td>
                                    </tr>				                            
                                    </table>
                                </fieldset> 
                                </td> 
                            </tr>
                            </table>  
                        </div>  
	                    
	                    <!-- tab "panes" Trade in -->
	                    <div class="pane">
                            <table border="0" width="70%">
                            <tr align="left">
                                <td width="50%">
                                <fieldset><legend class="legend">Uso de compra</legend>
                                    <table border="0" width="100%">
                                    <tr align="left">
                                        <td><label><input type="checkbox" id="rdPersonal" name="rdPersonal" checked value="1"/> Personal, Familia u Hogar</label></td>
                                    </tr>
                                    <tr align="left">
                                        <td><label><input type="checkbox" id="rdNegocio" name="rdNegocio" value="1"/> Negocios o Comercial</label></td>
                                    </tr>
                                    <tr align="left">
                                        <td><label><input type="checkbox" id="rdAgricola" name="rdAgricola" value="1"/> Agrícola</label></td>
                                    </tr>
                                    </table>
                                </fieldset>
                                </td>		
                            </tr>
	                        </table>
	                    </div>                  
                    </div>
	            </td>
	        </tr>
	        <tr>
	            <td align="right"><hr />
	    			<input Type="hidden" id="hddIdPedido" name="hddIdPedido" />
	    			<button type="button" id="btnGuardarContrato" name="btnGuardarContrato"  onclick="validarFormContrato();">Guardar</button>
	            	<button type="button" id="btnCancelarContrato" name="btnCancelarContrato" class="close" >Cancelar</button> 
	            </td>
	        </tr>
	        </table>
		</div>
	</form>    

	
</div>


<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmPedido" name="frmPedido" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblPedido" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Nro. Pedido:</td>
                <td width="70%"><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha de Entrega:</td>
                <td><input type="text" id="txtFechaEntrega" name="txtFechaEntrega" autocomplete="off" size="10" style="text-align:center"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr />
            <button type="submit" id="btnGuardarPedido" name="btnGuardarPedido" onclick="validarFrmPedido();">Guardar</button>
            <button type="button" id="btnCancelarPedido" name="btnCancelarPedido" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('lstEstatusPedido').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaEntrega").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaEfect").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaExpi").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaCuotaFinanciar").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaCuotaFinanciar2").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaCuotaFinanciar3").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaCuotaFinanciar4").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
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
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaEntrega",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});

	new JsDatePick({
		useMode:2,
		target:"txtFechaEfect",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});

	new JsDatePick({
		useMode:2,
		target:"txtFechaExpi",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaCuotaFinanciar",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaCuotaFinanciar2",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaCuotaFinanciar3",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaCuotaFinanciar4",
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
//perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPedido(0, 'id_pedido', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value + '|' + byId('lstEstatusPedido').value);

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>

</body>
</html>