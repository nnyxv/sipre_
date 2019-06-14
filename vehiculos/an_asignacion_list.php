<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_asignacion_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_asignacion_list.php");

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
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Asignación</title>
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
		
		if (verTabla == "tblAsignacion") {
			document.forms['frmAsignacion'].reset();
			byId('hddIdAsignacion').value = '';
			
			byId('txtIdEmpresa').className = 'inputHabilitado';
			byId('txtIdProv').className = 'inputHabilitado';
			byId('txtReferencia').className = 'inputHabilitado';
			byId('txtAsignacion').className = 'inputHabilitado';
			byId('txtFechaCierreCompra').className = 'inputHabilitado';
			byId('txtFechaCierreVenta').className = 'inputHabilitado';
			
			byId('txtIdEmpresa').readOnly = false;
			byId('txtIdProv').readOnly = false;
			
			byId('aListarEmpresa').style.display = '';
			byId('aListarProv').style.display = '';
			
			xajax_formAsignacion(valor, xajax.getFormValues('frmAsignacion'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Asignación';
			} else {
				tituloDiv1 = 'Agregar Asignación';
			}
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
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaProveedor').style.display = 'none';
		byId('tblUnidadBasica').style.display = 'none';
		byId('tblListaCliente').style.display = 'none';
		
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
			
			tituloDiv2 = 'Importar Pedido';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblListaProveedor") {
			document.forms['frmBuscarProveedor'].reset();
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv2 = 'Proveedores';
		} else if (verTabla == "tblUnidadBasica") {
			document.forms['frmBuscarUnidadBasica'].reset();
			document.forms['frmUnidadAsignacion'].reset();
			byId('hddIdDetalleAsignacion').value = '';
			
			byId('txtCriterioBuscarUnidadBasica').className = 'inputHabilitado';
			
			byId('lstTipoAsignacion').className = 'inputHabilitado';
			byId('txtNombreCliente').className = 'inputInicial';
			byId('txtUnidadBasica').className = 'inputInicial';
			byId('txtCantidadAsignada').className = 'inputHabilitado';
			
			xajax_formUnidadAsignacion();
			
			tituloDiv2 = 'Agregar Unidad Asignación';
		} else if (verTabla == "tblListaCliente") {
			document.forms['frmBuscarCliente'].reset();
			
			byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
			
			byId('btnBuscarCliente').click();
			
			tituloDiv2 = 'Clientes';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblListaProveedor") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		} else if (verTabla == "tblUnidadBasica") {
			byId('txtCriterioBuscarUnidadBasica').focus();
			byId('txtCriterioBuscarUnidadBasica').select();
		} else if (verTabla == "tblListaCliente") {
			byId('txtCriterioBuscarCliente').focus();
			byId('txtCriterioBuscarCliente').select();
		}
	}
	
	function validarCerrarAsignacion(idAsignacion) {
		if (confirm('¿Seguro desea cerrar la asignación?') == true) {
			xajax_cerrarAsignacion(idAsignacion, xajax.getFormValues('frmListaAsignacion'));
		}
	}
	
	function validarEliminar(idAsignacion) {
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAsignacion(idAsignacion, xajax.getFormValues('frmListaAsignacion'));
		}
	}
	
	function validarEliminarUnidadAsignacion(hddNumeroArt) {
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarUnidadAsignacion(hddNumeroArt, xajax.getFormValues('frmAsignacion'));
		}
	}
	
	function validarFrmAsignacion() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtReferencia','t','') == true
		&& validarCampo('txtAsignacion','t','') == true
		&& validarCampo('txtFechaCierreCompra','t','') == true
		&& validarCampo('txtFechaCierreVenta','t','') == true) {
			xajax_guardarAsignacion(xajax.getFormValues('frmAsignacion'), xajax.getFormValues('frmListaAsignacion'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdProv','t','');
			validarCampo('txtReferencia','t','');
			validarCampo('txtAsignacion','t','');
			validarCampo('txtFechaCierreCompra','t','');
			validarCampo('txtFechaCierreVenta','t','');
			
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
	
	function validarFrmUnidadAsignacion() {
		error = false;
		
		if (!(validarCampo('lstTipoAsignacion','t','listaExceptCero') == true
		&& validarCampo('txtUnidadBasica','t','') == true
		&& validarCampo('txtCantidadAsignada','t','cantidad') == true)) {
			validarCampo('lstTipoAsignacion','t','listaExceptCero');
			validarCampo('txtUnidadBasica','t','');
			validarCampo('txtCantidadAsignada','t','cantidad');
			
			error = true;
		}
		
		if (byId('lstTipoAsignacion').value == 1) {
			if (!(validarCampo('txtNombreCliente','t','') == true)) {
				validarCampo('txtNombreCliente','t','')
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		} else {
			xajax_insertarUnidadAsignacion(xajax.getFormValues('frmUnidadAsignacion'), xajax.getFormValues('frmAsignacion'));
		}
	}
	
	function seleccionarLstTipoAsignacion(tipoAsignacion) {
		byId('txtIdCliente').value = '';
		byId('txtNombreCliente').value = '';
		
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtNombreCliente').className = 'inputInicial';
		if (tipoAsignacion == 0) {
			byId('aInsertarCliente').style.display = 'none';
		} else if (tipoAsignacion == 1) {
			byId('aInsertarCliente').style.display = '';
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
        	<td class="tituloPaginaVehiculos">Asignación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAsignacion');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
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
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarAsignacion(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaAsignacion" name="frmListaAsignacion" style="margin:0">
            	<div id="divListaAsignacion" style="width:100%"></div>
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
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Asignación</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"/></td><td>Cerrar Asignación</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/aprob_mecanico.png"/></td><td>Aceptar Unidades</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/aprob_control_calidad.png"/></td><td>Confirmar Unidades</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Asignación PDF</td>
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
	<div id="tblAsignacion" style="max-height:500px; min-height:320px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'true', 'false');" size="6" style="text-align:right"/></td>
                            <td>
                            <a class="modalImg" id="aListarProv" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaProveedor');">
                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td><input type="text" id="txtFecha" name="txtFecha" autocomplete="off" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Asignación:</td>
                    <td width="45%"><input type="text" id="txtAsignacion" name="txtAsignacion" size="45"/></td>
                    <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Fecha Cierre Compra:</td>
                    <td width="25%"><input type="text" id="txtFechaCierreCompra" name="txtFechaCierreCompra" autocomplete="off" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Referencia:</td>
                    <td><input type="text" id="txtReferencia" name="txtReferencia" size="20" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Cierre Ventas:</td>
                    <td><input type="text" id="txtFechaCierreVenta" name="txtFechaCierreVenta" autocomplete="off" size="10" style="text-align:center"/></td>
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
                    <a class="modalImg" id="aNuevoUnidadAsignacion" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblUnidadBasica');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/add.png" title="Nuevo"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                    <a class="modalImg" id="aImportar" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblImportarPedido');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                            <td width="4%">Nro.</td>
                            <td></td>
                            <td width="8%">Unidad Básica</td>
                            <td width="16%">Modelo</td>
                            <td width="18%">Versión</td>
                            <td width="12%"><?php echo $spanClienteCxC; ?></td>
                            <td width="18%">Cliente</td>
                            <td width="8%">Asignados</td>
                            <td width="8%">Aceptados</td>
                            <td width="8%">Confirmados</td>
                            <td></td>
                        </tr>
                        <tr id="trItmPie"></tr>
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
                                    <td><img src="../img/iconos/ico_vehiculo_normal.png"/></td>
                                    <td>Vehículo Normal</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_vehiculo_flotilla.png"/></td>
                                    <td>Vehículo por Flotilla</td>
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

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
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
                        <td colspan="5">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
                        <td width="20%">Código Unidad</td>
                        <td width="20%"><?php echo $spanClienteCxC; ?></td>
                        <td width="20%">Asignados</td>
                        <td width="20%">Aceptados</td>
                        <td width="20%">Confirmados</td>
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
	
	<table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly"/>
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
  	
    <table border="0" id="tblListaProveedor" width="760">
    <tr>
    	<td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" class="inputHabilitado" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        	<div id="divListaProveedor" style="width:100%"></div>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaProveedor" name="btnCancelarListaProveedor" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <div id="tblUnidadBasica" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarUnidadBasica" name="frmBuscarUnidadBasica" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscarUnidadBasica"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscarUnidadBasica"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscarUnidadBasica"></td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAnoBuscarUnidadBasica"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarUnidadBasica" name="txtCriterioBuscarUnidadBasica" onkeyup="byId('btnBuscarUnidadBasica').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarUnidadBasica" onclick="xajax_buscarUnidadBasica(xajax.getFormValues('frmBuscarUnidadBasica'), xajax.getFormValues('frmAsignacion'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarUnidadBasica'].reset(); byId('btnBuscarUnidadBasica').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <div id="divListaUnidadBasica" style="width:100%"></div>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmUnidadAsignacion" name="frmUnidadAsignacion" onsubmit="return false;" style="margin:0">
                <table border="0" width="100%">
                <tr>
                    <td>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Asignación:</td>
                            <td>
                                <select id="lstTipoAsignacion" name="lstTipoAsignacion" class="inputHabilitado" onchange="seleccionarLstTipoAsignacion(this.value)">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">Asignación Normal</option>
                                    <option value="1">Asignación por Flotilla</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" id="tdCliente1"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aInsertarCliente" rel="#divFlotante2" onclick="abrirDivFlotante2(null, 'tblListaCliente');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad Básica:</td>
                            <td><input type="text" id="txtUnidadBasica" name="txtUnidadBasica" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="15%">Modelo:</td>
                            <td width="20%"><input type="text" id="txtModelo" name="txtModelo" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo" width="15%">Versión:</td>
                            <td width="50%"><input type="text" id="txtVersion" name="txtVersion" readonly="readonly" size="40"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                            <td><input type="text" id="txtCantidadAsignada" name="txtCantidadAsignada" class="inputHabilitado" size="10" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <input type="hidden" id="hddIdDetalleAsignacion" name="hddIdDetalleAsignacion" readonly="readonly"/>
                        <input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica" readonly="readonly"/>
                        <button type="submit" id="btnGuardarModelo" name="btnGuardarModelo" onclick="validarFrmUnidadAsignacion();">Guardar</button>
                        <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
  	
    <div id="tblListaCliente" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
                    <table align="right">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120">Criterio:</td>
                        <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                        <td>
                            <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmAsignacion'));">Buscar</button>
                            <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                        </td>
                    </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaCliente" name="frmListaCliente" onsubmit="return false;" style="margin:0">
                <table width="100%">
                <tr>
                    <td><div id="divListaCliente" style="width:100%;"></div></td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <button type="button" id="btnCancelarListaCliente" name="btnCancelarListaCliente" onclick="
                        byId('tblListaEmpresa').style.display = 'none';
                        byId('tblListaProveedor').style.display = 'none';
                        byId('tblUnidadBasica').style.display = '';
                        byId('tblListaCliente').style.display = 'none';">Cerrar</button>
                    </td>
                </tr>
                </table>
            </form>
			</td>
        </tr>
        </table>
	</div>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		
		$("#txtFechaCierreCompra").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaCierreVenta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
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
		target:"txtFechaCierreCompra",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaCierreVenta",
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
xajax_listaAsignacion(0, "idAsignacion", "DESC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>