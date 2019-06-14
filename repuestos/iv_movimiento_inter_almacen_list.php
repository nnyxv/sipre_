<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_movimiento_inter_almacen_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_movimiento_inter_almacen_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Movimientos Inter-Almacen</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2, valor3) {
		byId('tblImportarArchivo').style.display = 'none';
		byId('tblAlmacen').style.display = 'none';
		
		if (verTabla == "tblImportarArchivo") {
			document.forms['frmImportarArchivo'].reset();
			byId('hddUrlArchivo').value = '';
			byId('hddIdArticulo').value = '';
			
			byId('fleUrlArchivo').className = 'inputHabilitado';
			
			xajax_formImportarArchivo(xajax.getFormValues('frmImportarArchivo'));
			
			tituloDiv1 = 'Importar Pedido';
		} else if (verTabla == "tblAlmacen") {
			document.forms['frmAlmacen'].reset();
			
			byId('txtCantidadArt').readOnly = false;
			
			byId('txtCodigoArt').className = 'inputInicial';
			byId('txtCantidadArt').className = 'inputHabilitado';
			
			xajax_formMovimientoInterAlmacen(valor, valor2, valor3, xajax.getFormValues('frmBuscar'));
			
			tituloDiv1 = 'Transferencia de Almacén';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblAlmacen") {
			byId('txtCantidadArt').focus();
			byId('txtCantidadArt').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblVale').style.display = 'none';
		
		if (verTabla == "tblVale") {
			document.forms['frmVale'].reset();
			
			byId('txtObservacion').className = 'inputHabilitado';
			
			xajax_formDatosVale(xajax.getFormValues('frmVale'), xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmImportarArchivo'));
			
			tituloDiv2 = 'Datos de Vale';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblVale") {
			byId('txtObservacion').focus();
			byId('txtObservacion').select();
		}
	}
	
	function validarFrmAlmacen() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('lstAlmacenAct','t','lista') == true
		&& validarCampo('lstCalleAct','t','lista') == true
		&& validarCampo('lstEstanteAct','t','lista') == true
		&& validarCampo('lstTramoAct','t','lista') == true
		&& validarCampo('lstCasillaAct','t','lista') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true) {
			abrirDivFlotante2(byId('aGuardarAlmacen'), 'tblVale');
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('lstAlmacenAct','t','lista');
			validarCampo('lstCalleAct','t','lista');
			validarCampo('lstEstanteAct','t','lista');
			validarCampo('lstTramoAct','t','lista');
			validarCampo('lstCasillaAct','t','lista');
			validarCampo('txtCantidadArt','t','cantidad');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmImportarArchivo() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			abrirDivFlotante2(byId('aGuardarAlmacen'), 'tblVale');
		} else {
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmVale() {
		if (validarCampo('hddIdEmpleado','t','') == true
		&& validarCampo('lstClaveMovimientoSalida','t','lista') == true
		&& validarCampo('lstClaveMovimientoEntrada','t','lista') == true
		&& validarCampo('txtSubTotal','t','monto') == true) {
			xajax_guardarDcto(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmVale'), xajax.getFormValues('frmListaMovInterAlmacen'));
		} else {
			validarCampo('hddIdEmpleado','t','');
			validarCampo('lstClaveMovimientoSalida','t','lista');
			validarCampo('lstClaveMovimientoEntrada','t','lista');
			validarCampo('txtSubTotal','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Movimientos Inter-Almacen</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarArchivo');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                	<td></td>
                    <td></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Artículos:</td>
                	<td>
                    	<label><input type="checkbox" id="cbxVerArtUnaUbic" name="cbxVerArtUnaUbic" checked="checked" value="1"/> Con Una Ubicación</label>
                        <label><input type="checkbox" id="cbxVerArtMultUbic" name="cbxVerArtMultUbic" checked="checked" value="2"/> Con Múltiple Ubicación</label>
					</td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ubicaciones:</td>
                	<td>
                    	<!--<label><input type="checkbox" id="cbxVerUbicLibre" name="cbxVerUbicLibre" checked="checked" value="1"/> Libres</label>
                        <label><input type="checkbox" id="cbxVerUbicOcup" name="cbxVerUbicOcup" checked="checked" value="2"/> Ocupadas</label>-->
						<label><input type="checkbox" id="cbxVerUbicDisponible" name="cbxVerUbicDisponible" checked="checked" value="3"/> Con Disponibilidad</label>
                        <label><input type="checkbox" id="cbxVerUbicSinDisponible" name="cbxVerUbicSinDisponible" checked="checked" value="4"/> Sin Disponibilidad</label>
					</td>
                	<td align="right" class="tituloCampo">Estatus:</td>
                    <td>
                        <select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();">
                            <option selected="selected" value="-1">[ Seleccione ]</option>
                            <option value="0">Ubicación Inactiva</option>
                            <option value="1">Ubicación Activa</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ubicación:</td>
                	<td colspan="3">
                        <table>
                        <tr align="center">
                            <td class="tituloCampo">Almacen</td>
                            <td class="tituloCampo">Calle</td>
                            <td class="tituloCampo">Estante</td>
                            <td class="tituloCampo">Tramo</td>
                            <td class="tituloCampo">Casilla</td>
                        </tr>
                        <tr>
                            <td id="tdlstAlmacenBusqueda"></td>
                            <td id="tdlstCalleBusqueda"></td>
                            <td id="tdlstEstanteBusqueda"></td>
                            <td id="tdlstTramoBusqueda"></td>
                            <td id="tdlstCasillaBusqueda"></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarMovInterAlmacen(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaMovInterAlmacen" name="frmListaMovInterAlmacen" style="margin:0">
            	<div id="divListaMovInterAlmacen" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif"/></td><td>Ubicación Activa</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_gris.gif"/></td><td>Ubicación Inactiva</td>
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
                    	Unid. Disponible = Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
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
	
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarArchivo" name="frmImportarArchivo" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarArchivo" width="960">
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
                        <td colspan="9">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr>
                    	<td>
                            <div style="max-height:300px; overflow:auto; width:100%;">
                                <table width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td></td>
                                    <td width="16%">Código</td>
                                    <td width="12%">Id Empresa</td>
                                    <td width="12%">Almacén</td>
                                    <td width="13%">Ubicación</td>
                                    <td width="10%">Cantidad</td>
                                    <td width="12%">Id Empresa Destino</td>
                                    <td width="12%">Almacén Destino</td>
                                    <td width="13%">Ubicación Destino</td>
                                </tr>
                                <tr id="trItmPie"></tr>
                                </table>
							</div>
						</td>
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
        	<button type="submit" id="btnGuardarImportarArchivo" name="btnGuardarImportarArchivo" onclick="validarFrmImportarArchivo();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarArchivo" name="btnCancelarImportarArchivo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
<form id="frmAlmacen" name="frmAlmacen" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAlmacen" width="960">
    <tr>
    	<td>
            <input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
            <input type="hidden" id="hddIdCasilla" name="hddIdCasilla"/>
            <input type="hidden" id="hddIdArticuloCosto" name="hddIdArticuloCosto" title="Lote"/>
        <fieldset>
        	<table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="30%"></td>
                <td width="38%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
            	<td><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/></td>
            	<td rowspan="3" valign="top"><textarea id="txtDescripcionArt" name="txtDescripcionArt" class="inputSinFondo" rows="3" readonly="readonly" style="text-align:left"></textarea></td>
            	<td align="right" class="tituloCampo">Unid. Disponible:</td>
            	<td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
			</tr>
            <tr>
                <td align="right" class="tituloCampo">Tipo Artículo:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
            </tr>
            </table>
		</fieldset>
        
        	<table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
                <td width="30%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresaUnidadFisica"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ubicación:</td>
                <td colspan="5">
                	<table>
                    <tr align="center">
                    	<td class="tituloCampo">Almacen</td>
                    	<td class="tituloCampo">Calle</td>
                    	<td class="tituloCampo">Estante</td>
                    	<td class="tituloCampo">Tramo</td>
                    	<td class="tituloCampo">Casilla</td>
                    </tr>
                    <tr align="left">
                    	<td id="tdlstAlmacenAct"></td>
                    	<td id="tdlstCalleAct"></td>
                    	<td id="tdlstEstanteAct"></td>
                    	<td id="tdlstTramoAct"></td>
                    	<td id="tdlstCasillaAct"></td>
                    </tr>
                    <tr>
                        <td colspan="5" id="tdMsj"></td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                            <tr>
                                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                <td align="center">
                                    <table>
                                    <tr>
                                        <td class="divMsjInfo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Disponible</td>
                                        <td>&nbsp;</td>
                                        <td class="divMsjError">&nbsp;&nbsp;&nbsp;*</td><td>Ubicación Ocupada</td>
                                        <td>&nbsp;</td>
                                        <td class="divMsjInfo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Ubicación Inactiva</td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" size="10" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                    </tr>
					</table>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <a class="modalImg" id="aGuardarAlmacen" rel="#divFlotante2"></a>
			<button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFrmAlmacen();">Guardar</button>
			<button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
<form id="frmVale" name="frmVale" style="margin:0">
    <table border="0" id="tblVale" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Empleado:</td>
        <td width="86%">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly" size="6" style="text-align:right;"/></td>
                <td></td>
                <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td valign="top" width="50%">
                <fieldset><legend class="legend">Vale Salida</legend>
                    <table width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="20%">Empresa:</td>
                        <td width="80%">
                            <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtIdEmpresaValeSalida" name="txtIdEmpresaValeSalida" readonly="readonly" size="6" style="text-align:right;"/></td>
                                <td></td>
                                <td><input type="text" id="txtEmpresaValeSalida" name="txtEmpresaValeSalida" readonly="readonly" size="45"/></td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                        <td>
                            <select id="lstTipoMovimientoSalida" name="lstTipoMovimientoSalida">
                                <option>[ Seleccione ]</option>
                                <option value="2">ENTRADA</option>
                                <option value="4">SALIDA</option>
                            </select>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                        <td id="tdlstClaveMovimientoSalida"></td>
                    </tr>
                    </table>
                    <input type="hidden" id="hddIdCasillaOrigen" name="hddIdCasillaOrigen" readonly="readonly"/>
                </fieldset>
                </td>
                <td valign="top" width="50%">
                <fieldset><legend class="legend">Vale Entrada</legend>
                    <table width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="20%">Empresa:</td>
                        <td width="80%">
                            <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtIdEmpresaValeEntrada" name="txtIdEmpresaValeEntrada" readonly="readonly" size="6" style="text-align:right;"/></td>
                                <td></td>
                                <td><input type="text" id="txtEmpresaValeEntrada" name="txtEmpresaValeEntrada" readonly="readonly" size="45"/></td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                        <td>
                            <select id="lstTipoMovimientoEntrada" name="lstTipoMovimientoEntrada">
                                <option>[ Seleccione ]</option>
                                <option value="2">ENTRADA</option>
                                <option value="4">SALIDA</option>
                            </select>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                        <td id="tdlstClaveMovimientoEntrada"></td>
                    </tr>
                    </table>
                    <input type="hidden" id="hddIdCasillaDestino" name="hddIdCasillaDestino" readonly="readonly"/>
                </fieldset>
                </td>
            </tr>
            <tr>
            	<td colspan="2">
                	<div style="max-height:300px; overflow:auto; width:100%;">
                        <table class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td width="4%">Nro.</td>
                            <td width="16%">Código</td>
                            <td width="25%">Ubicación</td>
                            <td width="25%">Ubicación Destino</td>
                            <td width="10%">Cantidad</td>
                            <td width="10%">Costo Unit.</td>
                            <td width="10%">Total.</td>
                        </tr>
                        <tr id="trItmPieVale"></tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <table width="100%">
                    <tr align="left">
                        <td class="tituloCampo">Observación:</td>
                    </tr>
                    <tr align="left">
                        <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
                    </tr>
                    </table>
                </td>
                <td valign="top">
                    <table border="0" width="100%">
                    <tr align="right">
                        <td class="tituloCampo" width="36%">Subtotal:</td>
                        <td style="border-top:1px solid;" width="24%"></td>
                        <td style="border-top:1px solid;" width="13%"></td>
                        <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                        <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <button type="button" id="btnGuardarVale" name="btnGuardarVale" onclick="validarFrmVale();">Guardar</button>
            <button type="button" id="btnCancelarVale" name="btnCancelarVale" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('lstEstatus').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

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
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'null', 'null');
xajax_listaMovInterAlmacen(0, 'CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('cbxVerArtUnaUbic').value + '|' + byId('cbxVerArtMultUbic').value + '|||' + byId('cbxVerUbicDisponible').value + '|' + byId('cbxVerUbicSinDisponible').value + '|' + byId('lstEstatus').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>