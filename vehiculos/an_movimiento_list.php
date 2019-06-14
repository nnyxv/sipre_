<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_movimiento_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_movimiento_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Movimientos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblCierreVenta').style.display = 'none';
		
		if (verTabla == "tblCierreVenta") {
			document.forms['frmCierreVenta'].reset();
			
			byId('txtObservacionCierreVenta').className = 'inputHabilitado';
			
			xajax_formCierreVenta(valor, xajax.getFormValues('frmCierreVenta'));
			
			tituloDiv1 = 'Cierre de Venta';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblCierreVenta") {
			/*byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();*/
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2, valor3) {
		byId('tblLista').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
		if (verTabla == "tblLista") {
			byId('trBuscarEmpleado').style.display = 'none';
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarConcepto').style.display = 'none';
			byId('btnGuardarLista').style.display = 'none';
			
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('trBuscarCliente').style.display = '';
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('hddObjDestinoCliente').value = valor2;
				
				byId('btnBuscarCliente').click();
				
				tituloDiv2 = 'Clientes';
				byId(verTabla).width = "960";
			}
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('txtCriterioBuscarMotivo').className = 'inputHabilitado';
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddPagarCobrarMotivo').value = valor2;
			byId('hddIngresoEgresoMotivo').value = valor3;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv2 = 'Motivos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			}
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function validarBuscar() {
		if (validarCampo('txtFechaDesde','t','fecha') == true
		&& validarCampo('txtFechaHasta','t','fecha') == true) {
			xajax_buscarMovimiento(xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('txtFechaDesde','t','fecha');
			validarCampo('txtFechaHasta','t','fecha');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmCierreVenta() {
		error = false;
		/*if (!(validarCampo('txtIdFactura','t','') == true)) {
			validarCampo('txtIdFactura','t','');
			
			error = true;
		}*/
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea registrar el cierre de venta?') == true) {
				xajax_guardarCierreVenta(xajax.getFormValues('frmCierreVenta'), xajax.getFormValues('frmListaMovimiento'));
			}
		}
	}
	</script>
</head>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Listado de Movimientos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarMovimiento(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
                </tr>
                </table>
				
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
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
                	<td align="right" class="tituloCampo">Filtrar por Fecha:</td>
                    <td id="tdlstTipoFecha"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Módulo:</td>
                	<td id="tdlstModulo"></td>
                    <td align="right" class="tituloCampo">Estado de Venta:</td>
                	<td id="tdlstEstadoVenta"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Tipo Mov.:</td>
                    <td id="tdlstTipoMovimiento">
                    	<select multiple id="lstTipoMovimiento" name="lstTipoMovimiento" class="inputHabilitado" onchange="xajax_cargaLstClaveMovimiento('lstClaveMovimiento', $('#lstModulo').val(), $('#lstTipoMovimiento').val());" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">1.- Compra</option>
                            <option value="2">2.- Entrada</option>
                            <option value="3">3.- Venta</option>
                            <option value="4">4.- Salida</option>
                        </select>
					</td>
                	<td align="right" class="tituloCampo" width="120">Clave Mov.:</td>
                    <td id="tdlstClaveMovimiento">
                    	<select id="lstClaveMovimiento" name="lstClaveMovimiento">
                        	<option>[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Empleado:</td>
                    <td id="tdlstEmpleadoVendedor"></td>
				</tr>
                <tr align="left">
	                <td></td>
	                <td></td>
	                <td></td>
	                <td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
				</tr>
                <tr align="right">
                    <td colspan="6">
                    	<button type="submit" id="btnBuscar" onclick="validarBuscar();">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaMovimiento" name="frmListaMovimiento" style="margin:0">
                <div id="divListaMovimiento" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
                </div>
			</form>
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
    
<form id="frmCierreVenta" name="frmCierreVenta" onsubmit="return false;" style="margin:0">
	<div id="tblCierreVenta" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="14%">Financiado por:</td>
                	<td width="86%"><input id="txtBancoFinanciar" name="txtBancoFinanciar" readonly="readonly" size="45"/></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Observación<br>Cierre de Venta:</td>
                    <td><textarea id="txtObservacionCierreVenta" name="txtObservacionCierreVenta" rows="2" style="width:99%"></textarea></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" class="texto_9px" width="100%">
                <tr class="tituloColumna">
                    <td></td>
                    <td width="6%">Nro.</td>
                    <td width="0%">Código</td>
                    <td width="54%">Descripción</td>
                    <td width="10%">Precio Unit.</td>
                    <td width="10%">Costo Unit.</td>
                    <td width="8%">% Impuesto</td>
                    <td width="10%">Total</td>
                </tr>
                <tr id="trItmPieAdicionalOtro" align="right" class="trResaltarTotal"></tr>
                </table>
            </td>
		</tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdFacturaCierreVenta" name="hddIdFacturaCierreVenta" readonly="readonly"/>
                <button type="submit" id="btnGuardarCierreVenta" name="btnGuardarCierreVenta"  onclick="validarFrmCierreVenta();">Guardar</button>
                <button type="button" id="btnCancelarCierreVenta" name="btnCancelarCierreVenta" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>

    <div id="tblLista" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr id="trBuscarEmpleado">
            <td>
            <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado"/></td>
                    <td>
                        <button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'), xajax.getFormValues('frmDcto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr id="trBuscarCliente">
            <td>
            <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddObjDestinoCliente" name="hddObjDestinoCliente" readonly="readonly" />
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente"/></td>
                    <td>
                        <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr id="trBuscarConcepto">
            <td>
            <form id="frmBuscarConcepto" name="frmBuscarConcepto" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarConcepto" name="txtCriterioBuscarConcepto"/></td>
                    <td>
                        <button type="submit" id="btnBuscarConcepto" name="btnBuscarConcepto" onclick="xajax_buscarConcepto(xajax.getFormValues('frmBuscarConcepto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarConcepto'].reset(); byId('btnBuscarConcepto').click();">Limpiar</button>
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
                        <button type="submit" id="btnGuardarLista" name="btnGuardarLista" onclick="xajax_asignarGasto(xajax.getFormValues('frmLista'), xajax.getFormValues('frmListaArticulo'));">Aceptar</button>
                        <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
    
    <table border="0" id="tblListaMotivo" width="960">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddPagarCobrarMotivo" name="hddPagarCobrarMotivo" readonly="readonly" />
            <input type="hidden" id="hddIngresoEgresoMotivo" name="hddIngresoEgresoMotivo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo"/></td>
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
        <form id="frmListaMotivo" name="frmListaMotivo" onsubmit="return false;" style="margin:0">
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
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoFecha();
xajax_cargaLstModulo('2');
xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2');
xajax_cargaLstEmpleado('lstEmpleadoVendedor','tdlstEmpleadoVendedor','');
xajax_cargaLstEstadoVenta();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>