<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_asignar_plan_pago_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_asignar_plan_pago_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug', true);
//$xajax->setFlag('allowAllResponseTypes', true);
	
/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Asignar Plan de Pago</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblAsignacion').style.display = 'none';
		
		byId('trMotivo').style.display = 'none';
		
		if (verTabla == "tblAsignacion") {
			document.forms['frmAsignacion'].reset();
			byId('hddIdAsignacion').value = '';
			
			byId('txtIdEmpresa').className = 'inputInicial';
			byId('txtIdProv').className = 'inputInicial';
			byId('txtReferencia').className = 'inputInicial';
			byId('txtAsignacion').className = 'inputInicial';
			byId('txtFechaCierreCompra').className = 'inputInicial';
			byId('txtFechaCierreVenta').className = 'inputInicial';
			
			byId('txtIdEmpresa').readOnly = true;
			byId('txtIdProv').readOnly = true;
			byId('txtReferencia').readOnly = true;
			byId('txtAsignacion').readOnly = true;
			byId('txtFechaCierreCompra').readOnly = true;
			byId('txtFechaCierreVenta').readOnly = true;
			
			xajax_formAsignacion(valor, xajax.getFormValues('frmAsignacion'));
			
			tituloDiv1 = 'Plan de Pago de Asignación';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblAsignacion") {
			byId('txtIdEmpresa').focus();
			byId('txtIdEmpresa').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblImportarPedido').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
		if (verTabla == "tblImportarPedido") {
			if (validarCampo('txtIdProv','t','') == true) {
				document.forms['frmImportarPedido'].reset();
				byId('hddUrlArchivo').value = '';
				
				byId('fleUrlArchivo').className = 'inputHabilitado';
			} else {
				validarCampo('txtIdProv','t','');
				
				alert('Los campos señalados en rojo son requeridos');
				return false; 
			}
			
			tituloDiv2 = 'Importar Asignación';
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddIngresoEgresoMotivo').value = valor2;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv2 = 'Motivos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function validarEliminar(idPlanPago){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarPlanPago(idPlanPago, xajax.getFormValues('frmListaPlanPago'));
		}
	}
	
	function validarFrmAsignacion() {
		if (validarCampo('lstMoneda', 't', 'lista') == true) {
			if (confirm('¿Seguro desea guardar el plan de pago?') == true) {
				byId('btnGuardarAsignacion').disabled = true;
				byId('btnCancelarAsignacion').disabled = true;
				xajax_guardarPlanPago(xajax.getFormValues('frmAsignacion'), xajax.getFormValues('frmListaPlanPago'));
			}
		} else {
			validarCampo('lstMoneda', 't', 'lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmImportarPedido() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			byId('btnGuardarImportarPedido').disabled = true;
			byId('btnCancelarImportarPedido').disabled = true;
			
			xajax_importarDcto(xajax.getFormValues('frmImportarPedido'), xajax.getFormValues('frmAsignacion'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
        	<td class="tituloPaginaVehiculos">Asignar Plan de Pago</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa">
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
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarPlanPago(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPlanPago" name="frmListaPlanPago" style="margin:0">
                <div id="divListaPlanPago" style="width:100%"></div>
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
                            <td><img src="../img/iconos/ico_asignar_plan_pago.png"/></td><td>Asignar Plan de Pago</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Asignación PDF</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_excel.png"/></td><td>XLS</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_delete.png"/></td><td>Eliminar Asignación</td>
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
	
<form id="frmAsignacion" name="frmAsignacion" onsubmit="return false;" style="margin:0">
	<div id="tblAsignacion" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Proveedor:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'true', 'false');" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="16%">Asignación:</td>
                    <td width="44%"><input type="text" id="txtAsignacion" name="txtAsignacion" size="45"/></td>
                    <td align="right" class="tituloCampo" width="16%">Fecha Cierre Compra:</td>
                    <td width="24%"><input type="text" id="txtFechaCierreCompra" name="txtFechaCierreCompra" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Referencia:</td>
                    <td><input type="text" id="txtReferencia" name="txtReferencia" size="20" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo">Fecha Cierre Ventas:</td>
                    <td><input type="text" id="txtFechaCierreVenta" name="txtFechaCierreVenta" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                    <td>
                    	<table border="0" cellpadding="0" cellspacing="0">
                        <tr align="left">
                            <td id="tdlstMoneda">
                                <select id="lstMoneda" name="lstMoneda">
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstTasaCambio"></td>
                            <td>
                                <input type="text" id="txtTasaCambio" name="txtTasaCambio" readonly="readonly" size="16" style="text-align:right"/>
                                <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" readonly="readonly"/>
                                <input type="hidden" id="hddIncluirImpuestos" name="hddIncluirImpuestos" readonly="readonly"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr id="trMotivo" align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:
						<br><span class="textoNegrita_10px">(Nota Cargo Plan Mayor)</span>
					</td>
                    <td colspan="3">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" onblur="xajax_asignarMotivo(this.value, 'Motivo', 'E', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarMotivo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'E');">
                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtMotivo" name="txtMotivo" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
            <fieldset id="fieldsetUnidadAsignacion"><legend class="legend">Vehículos de la Asignación <b>Ref. <span id="spanTituloUnidadAsignacion"></span></b></legend>
                <table width="100%">
                <tr align="left">
                    <td>
                    <a class="modalImg" id="aImportar" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblImportarPedido');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                <tr>
                    <td><div id="divListaUnidadAsignacion" style="width:100%"></div></td>
                </tr>
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">
                                <table>
                                <tr>
                                    <td><img src="../img/iconos/ico_vehiculo_normal.png"/></td><td>Vehículo Normal</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_vehiculo_flotilla.png"/></td><td>Vehículo por Flotilla</td>
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
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdAsignacion" name="hddIdAsignacion" readonly="readonly"/>
                <button type="submit" id="btnGuardarAsignacion" name="btnGuardarAsignacion"  onclick="validarFrmAsignacion();">Guardar</button>
                <button type="button" id="btnCancelarAsignacion" name="btnCancelarAsignacion" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarPedido" name="frmImportarPedido" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarPedido" width="960">
    <tr align="left">
    	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td width="85%">
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript:submit();"/>
			<iframe name="iframeUpload" style="display:none"></iframe>
            <input type="hidden" id="hddUrlArchivo" name="hddUrlArchivo" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table width="100%">
                    <tr>
                        <td colspan="10">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
						<td>Id. Asignacion</td>
                        <td>Nro. Referencia</td>
                        <td>Id. Unidad</td>
                        <td>Código Unidad</td>
                        <td><?php echo $spanClienteCxC; ?></td>
                        <td>Asignados</td>
                        <td>Aceptados</td>
                        <td>Confirmados</td>
                        <td>Costo Unit.</td>
                        <td>Forma Pago</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
            <div id="divMsjImportar"></div>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
        	<button type="submit" id="btnGuardarImportarPedido" name="btnGuardarImportarPedido" onclick="validarFrmImportarPedido();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarPedido" name="btnCancelarImportarPedido" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
	
    <table border="0" id="tblListaMotivo" width="960">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddIngresoEgresoMotivo" name="hddIngresoEgresoMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" class="inputHabilitado" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarMotivo').value = ''; byId('btnBuscarMotivo').click();">Limpiar</button>
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
xajax_listaPlanPago(0, "idAsignacion", "DESC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>