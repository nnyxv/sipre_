<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_retenciones_list"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_retenciones_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Retenciones</title>
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
	</style>
    
    <script>	
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblRetencion').style.display = 'none';
		
		if (verTabla == "tblRetencion") {
			var idEmpresa = byId('lstEmpresa').value;
			
			xajax_asignarEmpresa(idEmpresa);
			document.forms['frmRetencion'].reset();
			
			byId('tdInfoRetencionISLR').innerHTML = '';
			
			byId('selRetencionISLR').className = 'inputHabilitado';
			byId('txtMontoRetencionISLR').className = 'inputHabilitado';
			byId('txtBaseRetencionISLR').className = 'inputHabilitado';
			byId('txtFechaRetencion').className = 'inputHabilitado';
			
			tituloDiv1 = 'Nueva Retención ISLR';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';		
		byId('tblBeneficiariosProveedores').style.display = 'none';
		byId('tblFacturasNotas').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();			
			xajax_listaEmpresa();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblBeneficiariosProveedores") {
			
			tituloDiv2 = 'Beneficario / Proveedor';
		} else if (verTabla == "tblFacturasNotas") {
		
			tituloDiv2 = 'Factura / Nota de Cargo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblBeneficiariosProveedores") {
			byId('txtCriterioBusqProveedor').focus();
			byId('txtCriterioBusqProveedor').select();
		} else if (verTabla == "tblFacturasNotas") {
			byId('txtCriterioBuscarFacturaNota').focus();
			byId('txtCriterioBuscarFacturaNota').select();
		}
	}
	
	function arcv(){
		if(byId('lstEmpresa').value == ""){
			return alert("Debe seleccionar Empresa");
		}
		
		if(byId('txtFecha').value == ""){
			return alert("Debe Seleccionar Fecha");
		}
		
		if(byId('txtIdProv').value == ""){
			return alert("Debe Seleccionar Proveedor");
		}
		 
		verVentana("reportes/arcv.php?empresa=" + byId('lstEmpresa').value + "&proveedor=" + byId('txtIdProv').value + "&fecha=" + byId('txtFecha').value,700,700);		
	}
	
	function calcularRetencion(){		
		var baseRetencion = parseFloat(byId('txtBaseRetencionISLR').value).toFixed(2);
		var montoMayorAplicar = parseFloat(byId('hddMontoMayorAplicar').value).toFixed(2);
		var sustraendo = parseFloat(byId('hddSustraendoRetencion').value).toFixed(2);
		var porcentaje = parseFloat(byId('hddPorcentajeRetencion').value).toFixed(2);		
		var montoRetencion = 0;
		
		//NOTA: toFixed(2) regresa string y debes volver a usar parseFloat
		if(parseFloat(baseRetencion) >= parseFloat(montoMayorAplicar) && !isNaN(baseRetencion)){
			montoRetencion = (baseRetencion * (porcentaje / 100)) - sustraendo;			
		}
		
		byId('txtMontoRetencionISLR').value = montoRetencion.toFixed(2);
	}
	
	function validarRetencionForm(){
		if (validarCampo('selRetencionISLR','t','lista') == true
			&& validarCampo('txtMontoRetencionISLR','t','monto') == true
			&& validarCampo('txtBaseRetencionISLR','t','monto') == true
			&& validarCampo('txtFechaRetencion','t','') == true){
			
			xajax_guardarRetencion(xajax.getFormValues('frmRetencion'));
		}else{
			validarCampo('selRetencionISLR','t','lista') == true;
			validarCampo('txtMontoRetencionISLR','t','monto');
			validarCampo('txtBaseRetencionISLR','t','monto');
			validarCampo('txtFechaRetencion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
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
        	<td class="tituloPaginaTesoreria">Retenciones ISLR<br/></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
	            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblRetencion', 0);">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
                    </td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
					<td>
						<button type="button" id="btnExportar" onclick="xajax_exportarListado(xajax.getFormValues('frmBuscar'));" class="puntero" style="width:100%;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Doc Listado</td></tr></table></button>
					</td>
					<td>
						<button type="button" id="btnExportar" onclick="xajax_exportarListadoSeniat(xajax.getFormValues('frmBuscar'));" class="puntero" style="width:100%;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Doc SENIAT</td></tr></table></button>
					</td>
				</tr>
                <tr>
                	<td></td>
                    <td></td>
					<td>
						<button type="button" id="btnArcv" onclick="arcv();" class="puntero" style="width:100%;"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/pdf_ico.png"/></td><td>&nbsp;</td><td>ARCV</td></tr></table></button>
					</td>
                    <td>
                    	<button onclick="xajax_cargaLstAdministradoraPDF();" type="button" class="puntero" style="width:100%;"><table cellspacing="0" cellpadding="0" align="center"><tbody><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"></td><td>&nbsp;</td><td>PDF por Lote</td></tr></tbody></table></button>
					</td>
				</tr>
                <tr>
                	<td></td>
                	<td></td>
                	<td colspan="2" id="tdlstAdministradoraPDF" align="right"></td>
                </tr>
				</table>
                
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" align="left"></td>
			
                    <td align="right" class="tituloCampo" width="120">Tipo Pago:</td>
                    <td>
                    	<select name="listPago" id="listPago" onchange="$('#btnBuscar').click();" class="inputHabilitado">
                        	<option value="">[ Seleccione ]</option>
                            <option value="0">Cheque</option>
                            <option value="1">Transferencia</option>
                            <option value="2">Sin Documento</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Mes Consulta:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <input type="text" id="txtFecha" name="txtFecha" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center" readonly="readonly"/>
                            </td>
                       </tr>
                       </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td>
                    	<select name="listAnulado" id="listAnulado" onchange="$('#btnBuscar').click();" class="inputHabilitado">
                        	<option value="">[ Seleccione ]</option>
                            <option value="0" selected="selected">Activo</option>
                            <option value="1">Anulado</option>
                        </select>
                    </td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Proveedor:</td>
                    <td align="left">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value);" size="6" style="text-align:right" class="inputHabilitado"/><input type="hidden" name="hddSelBePro" id="hddSelBePro"/>
                            </td>
                            <td>
                            <a class="modalImg" id="aProveedor" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblBeneficiariosProveedores', 0);">
                            	<button type="button" id="btnSeleccionearBP" name="btnSeleccionearBP" title="Seleccionar Proveedor / Beneficiario" class="puntero"
                                    onclick="
                                    byId('tdBeneficiarios').className = 'rafktabs_title';
                                    byId('tdProveedores').className = 'rafktabs_titleActive';
                                    
                                    byId('buscarProv').value = '1';//proveedor
                                    byId('btnBuscarCliente').click();
                                    
                                     byId('tdProveedores').onclick = function(){
                                        byId('tdBeneficiarios').className = 'rafktabs_title';
                                        byId('tdProveedores').className = 'rafktabs_titleActive';
                                        byId('buscarProv').value = '1';//proveedor
                                        byId('btnBuscarCliente').click();
                                        };
                                        
                                     byId('tdBeneficiarios').onclick = function(){
                                        byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                        byId('tdProveedores').className = 'rafktabs_title';
                                        byId('buscarProv').value = '2';//beneficiario
                                        byId('btnBuscarCliente').click();
                                        };">
                                        <img src="../img/iconos/help.png"/>
								</button>
                            </a>
                            </td>
                            <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="35"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Nro. Factura / Control:</td>
					<td><input type="text" name="txtCriterio" id="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
					<td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
        	<td id="tdListaRetencion"></td>
        </tr>
        <tr>
        	<td><br>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tbody>
                        <tr>
                            <td width="25"></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_verde.gif"></td>
                                    <td>Activo</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_rojo.gif"></td>
                                    <td>Inactivo</td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
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
    <form id="frmRetencion" name="frmRetencion" onsubmit="return false;">
    <table border="0" id="tblRetencion" width="810">
    	<tr>
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
             </td>
           </tr>
           <tr>
              <td colspan="2">                              
                <fieldset><legend class="legend">Detalles del Documento</legend>
                <table border="0" width="100%">
                <tr align="left">
                    <td class="tituloCampo" width="15%" align="right"><span class="textoRojoNegrita">*</span>Factura/Nota:</td>
                    <td width="10%" align="left">
                        <table>
                            <tr>
                                <td>
                                    <input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="10"/>
                                </td>
                                <td>
                                    <a class="modalImg" id="aListarFacturaNota" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblFacturasNotas');">
                                    <button type="button" id="btnInsertarFactura" name="btnInsertarFactura" title="Seleccionar Factura" class="puntero"
                                        onclick="                                            
                                            document.forms['frmBuscarDocumento'].reset();
                                            byId('tblFacturasNotas').style.display = '';
                                            byId('tdContenidoDocumento').style.display = '';                                        
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
                                            byId('buscarTipoDcto').value = '2';//factura
                                            byId('btnBuscarFacturaNota').click();
                                            
                                            byId('tdFacturas').onclick = function(){
                                                byId('tdNotaCargo').className = 'rafktabs_title';
                                                byId('tdFacturas').className = 'rafktabs_titleActive';
                                                byId('buscarTipoDcto').value = '2';//factura
                                                byId('btnBuscarFacturaNota').click();
                                                };
    
                                             byId('tdNotaCargo').onclick = function(){												
                                                byId('tdNotaCargo').className = 'rafktabs_titleActive';
                                                byId('tdFacturas').className = 'rafktabs_title';
                                                byId('buscarTipoDcto').value = '1';//nota de cargo
                                                byId('btnBuscarFacturaNota').click();
                                                };
                                        ">
                                        <img src="../img/iconos/help.png"/>
                                    </button>
									</a>
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
                        <input type="text" id="hddBaseImponible" readonly="readonly" name="hddBaseImponible" size="15" />
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">Descripción</td>
                    <td align="left" colspan="4">
                        <textarea id="txtDescripcionFactura" name="txtDescripcionFactura" readonly="readonly" cols="55"></textarea>
                        <input type="hidden" id="hddIva" name="hddIva" />
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
                
                <fieldset><legend class="legend">Retenci&oacute;n ISLR</legend>
                <table width="100%" border="0">                
                <tr align="left">
                	<td>
                    	<table width="100%" border="0">
                        	<tr>
								<td class="tituloCampo" align="right" width="35%">
                                    <span class="textoRojoNegrita">*</span>Fecha Retenci&oacute;n:
                                </td>
                                <td align="left" colspan="2">
 	                               <input type="text" size="10" class="inputHabilitado" name="txtFechaRetencion" id="txtFechaRetencion" readonly="readonly"/>
                                </td>
                            </tr>
                            <tr>
                                <td class="tituloCampo" align="right" width="35%">
                                    <span class="textoRojoNegrita">*</span>Retenci&oacute;n ISLR:
                                </td>
                                <td align="left" id="tdRetencionISLR" colspan="2">
                                </td>
                            </tr>
                            <tr>
                            	<td></td>
								<td colspan="2" id="tdInfoRetencionISLR"></td>
							</tr>
                            <tr>
                            	<td class="tituloCampo" align="right" width="35%">
                                	<span class="textoRojoNegrita">*</span>Base Retenci&oacute;n:
                                </td>
								<td>
                                	<input type="text" onkeyup="calcularRetencion();" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado" size="10" name="txtBaseRetencionISLR" id="txtBaseRetencionISLR">
                                </td>
                            	<td class="tituloCampo" align="right" width="35%">
                                	<span class="textoRojoNegrita">*</span>Monto Retenci&oacute;n:
                                </td>
                                <td>
                                	<input type="text" readonly="readonly" size="10" name="txtMontoRetencionISLR" id="txtMontoRetencionISLR">
                                </td>
							</tr>
                       </table>
                    </td>
                    <td colspan="2">
						<table width="100%" border="0">
                        <tr>
                        	<td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">Mayor a aplicar:</td>
                            <td><input size="5" readonly="readonly" id="hddMontoMayorAplicar" name="hddMontoMayorAplicar" /></td>
                            <td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">Porcentaje:</td>
                            <td style="white-space:nowrap;"><input size="5" readonly="readonly" id="hddPorcentajeRetencion" name="hddPorcentajeRetencion" />%</td>
                        </tr>
                        <tr>
                        	<td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">C&oacute;digo Concepto:</td>
                            <td><input size="5" readonly="readonly" id="hddCodigoRetencion" name="hddCodigoRetencion" /></td>
                            <td style="white-space:nowrap;" class="tituloCampo" align="right" width="35%">Sustraendo:</td>
                            <td><input size="5" readonly="readonly" id="hddSustraendoRetencion" name="hddSustraendoRetencion" /></td>
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
            	<button type="button" id="btnAceptar" name="btnAceptar" onclick="validarRetencionForm();">Aceptar</button>
            	<button type="button" id="btnCancelarRetencion" name="btnCancelarRetencion" class="close">Cancelar</button>
            </td>
    	</tr>
    </table>
    </form>     
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
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
    
    <table border="0" id="tblBeneficiariosProveedores" style="display:none;" width="700">
    <tr>
    	<td>
			<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdBeneficiarios" width="120">Beneficiarios</td>
                        <td class="rafktabs_title" id="tdProveedores" width="120">Proveedores</td>
		            </tr>
                    <tr>
                	<td align="right" class="tituloCampo" width="15">Criterio:</td>
                	<td>
                        <input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="byId('btnBuscarCliente').click();" class="inputHabilitado"/>
					</td>
                        <td><button type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente1(xajax.getFormValues('frmBuscarCliente'));" class="puntero">Buscar</button>
                        	<button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                        </td>
                </tr>
					</table>
				</td>
            </tr>
            <tr>
                <td class="rafktabs_panel" id="tdContenido" style="border:0px;"></td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
	<tr>
    	<td align="right"><hr>
			<button type="button" id="btnCancelarBeneficiariosProveedores" name="btnCancelarBeneficiariosProveedores" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
    
   	<table id="tblFacturasNotas" border="0" style="display:none" width="1050">
    <tr>
    	<td>
        	<form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="byId('btnBuscarFacturaNota').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdFacturas"  width="120">Facturas</td>
                        <td class="rafktabs_title" id="tdNotaCargo" width="120">Notas De Cargo</td>
		            </tr>
              
					<tr>
                        <td align="right" class="tituloCampo" width="15">Criterio:</td>
                        <td>
                            <input type="hidden" id="buscarTipoDcto" name="buscarTipoDcto" value="2" />
                            <input type="text" id="txtCriterioBuscarFacturaNota" name="txtCriterioBuscarFacturaNota" onkeyup="byId('btnBuscarFacturaNota').click();" class="inputHabilitado"/>
                        </td>
                        <td><button type="button" id="btnBuscarFacturaNota" name="btnBuscarFacturaNota" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), byId('lstEmpresa').value);">Buscar</button>
                        	<button type="button" onclick="document.forms['frmBuscarDocumento'].reset(); byId('btnBuscarFacturaNota').click();">Limpiar</button>
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
        <td align="right"><hr>
            <button type="button" id="btnCancelarFacturaNota" name="btnCancelarFacturaNota" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaListRetencionISLR();
xajax_listaRetencion(0,'','','||||0|');

new JsDatePick({
	useMode:2,
	target:"txtFecha",
	dateFormat:"%m-%Y",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaRetencion",
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