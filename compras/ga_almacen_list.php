<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_almacen_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_almacen_list.php");

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
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Almacenes</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css" />
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
    <script language="javascript" type="text/javascript">
	function formListaEmpresas(nomObjeto, objDestino, nomVentana) {
		openImg(nomObjeto);
		
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = objDestino;
		byId('hddNomVentana').value = nomVentana;
		
		byId('btnBuscarEmpresa').click();
		
		byId('tblListaEmpresa').style.display = '';
		byId('tblDetallesAlmacen').style.display = 'none';
		
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
		
		byId('txtCriterioBuscarEmpresa').focus();
		byId('txtCriterioBuscarEmpresa').select();
	}
	
	function validarEliminar(idAlmacen){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAlmacen(idAlmacen, xajax.getFormValues('frmListaAlmacen'));
		}
	}
	
	function validarEliminarLote(){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAlmacenBloque(xajax.getFormValues('frmListaAlmacen'));
		}
	}
	
	function validarFormAlmacen() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtDesAlmacen','t','') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true
		&& validarCampo('lstEstatusVenta','t','listaExceptCero') == true
		&& validarCampo('lstEstatusCompra','t','listaExceptCero') == true) {
			xajax_guardarAlmacen(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmListaAlmacen'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtDesAlmacen','t','')
			validarCampo('lstEstatus','t','listaExceptCero');
			validarCampo('lstEstatusVenta','t','listaExceptCero')
			validarCampo('lstEstatusCompra','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormImportar() {
		if (validarCampo('txtIdEmpresaImportarAlmacen','t','') == true
		&& validarCampo('hddUrlArchivo','t','') == true) {
			byId('btnGuardarImportarAlmacen').disabled = 'disabled';
			byId('btnCancelarImportarAlmacen').disabled = 'disabled';
			
			xajax_importarAlmacen(xajax.getFormValues('frmImportarAlmacen'), xajax.getFormValues('frmListaAlmacen'));
		} else {
			validarCampo('txtIdEmpresaImportarAlmacen','t','');
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function detalles(nomObjeto, tipo)	{
		openImg(nomObjeto);
		
		document.forms['frmUbicacionAlmacen'].reset();
		byId('hddIdCalle').value = "";
		byId('hddIdEstante').value = "";
		byId('hddIdTramo').value = "";
		byId('hddIdCasilla').value = "";
		
		byId('txtCalle').readOnly = true;
		byId('txtEstante').readOnly = true;
		byId('txtTramo').readOnly = true;
		byId('txtCasilla').readOnly = true;
		
		byId('txtCalle').className = 'inputInicial';
		byId('txtEstante').className = 'inputInicial';
		byId('txtTramo').className = 'inputInicial';
		byId('txtCasilla').className = 'inputInicial';
		
		byId('trCalle').style.display = '';
		
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblDetallesAlmacen').style.display = '';
		
		if (tipo == 0) {
			byId('txtCalle').readOnly = false;
			
			byId('txtCalle').className = 'inputHabilitado';
			
			byId('trEstante').style.display = 'none';
			byId('trTramo').style.display = 'none';
			byId('trCasilla').style.display = 'none';
			
			byId('tdFlotanteTitulo2').innerHTML = 'Nueva Calle';
			
			byId('txtCalle').focus();
			byId('txtCalle').select();
		} else if (tipo == 1) {
			byId('txtEstante').readOnly = false;
			
			byId('txtEstante').className = 'inputHabilitado';
			
			byId('trEstante').style.display = '';
			byId('trTramo').style.display = 'none';
			byId('trCasilla').style.display = 'none';
			
			xajax_asignarCalle(byId('lstCalle').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Nuevo Estante';
			
			byId('txtEstante').focus();
			byId('txtEstante').select();
		} else if (tipo == 2) {
			byId('txtTramo').readOnly = false;
			
			byId('txtTramo').className = 'inputHabilitado';
			
			byId('trEstante').style.display = '';
			byId('trTramo').style.display = '';
			byId('trCasilla').style.display = 'none';
			
			xajax_asignarCalle(byId('lstCalle').value);
			xajax_asignarEstante(byId('lstEstante').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Nuevo Tramo';
			
			byId('txtTramo').focus();
			byId('txtTramo').select();
		} else if (tipo == 3) {
			byId('txtCasilla').readOnly = false;
			
			byId('txtCasilla').className = 'inputHabilitado';
			
			byId('trEstante').style.display = '';
			byId('trTramo').style.display = '';
			byId('trCasilla').style.display = '';
			
			xajax_asignarCalle(byId('lstCalle').value);
			xajax_asignarEstante(byId('lstEstante').value);
			xajax_asignarTramo(byId('lstTramo').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Nueva Casilla';
			
			byId('txtCasilla').focus();
			byId('txtCasilla').select();
		}
		
		byId('hddTipo').value = tipo;
	}
	
	function validarDetalles() {
		if (byId('hddTipo').value == 0) {
			cadena = "txtCalle";
		} else if (byId('hddTipo').value == 1) {
			cadena = "txtEstante";
		} else if (byId('hddTipo').value == 2) {
			cadena = "txtTramo";
		} else {
			cadena = "txtCasilla";
		}
		
		if (validarCampo(cadena,'t','') == true) {
			xajax_guardarUbicacion(xajax.getFormValues('frmUbicacionAlmacen'), xajax.getFormValues('frmAlmacen'));
		} else {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function editarDetalles(nomObjeto, tipo) {
		openImg(nomObjeto);
		
		document.forms['frmUbicacionAlmacen'].reset();
		byId('hddIdCalle').value = "";
		byId('hddIdEstante').value = "";
		byId('hddIdTramo').value = "";
		byId('hddIdCasilla').value = "";
		
		byId('txtCalle').readOnly = true;
		byId('txtEstante').readOnly = true;
		byId('txtTramo').readOnly = true;
		byId('txtCasilla').readOnly = true;
		
		byId('txtCalle').className = 'inputInicial';
		byId('txtEstante').className = 'inputInicial';
		byId('txtTramo').className = 'inputInicial';
		byId('txtCasilla').className = 'inputInicial';
		
		byId('trCalle').style.display = 'none';
		byId('trEstante').style.display = 'none';
		byId('trTramo').style.display = 'none';
		byId('trCasilla').style.display = 'none';
		
		if (tipo == 0) {
			byId('hddIdCalle').value = byId('lstCalle').value;
			
			byId('txtCalle').readOnly = false;
			
			byId('txtCalle').className = 'inputHabilitado';
			
			byId('trCalle').style.display = '';
			
			xajax_asignarCalle(byId('lstCalle').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Editar Calle';
			
			byId('txtCalle').focus();
			byId('txtCalle').select();
		} else if (tipo == 1) {
			byId('hddIdCalle').value = byId('lstCalle').value;
			byId('hddIdEstante').value = byId('lstEstante').value;
			
			byId('txtEstante').readOnly = false;
			
			byId('txtEstante').className = 'inputHabilitado';
			
			byId('trCalle').style.display = '';
			byId('trEstante').style.display = '';
			
			xajax_asignarCalle(byId('lstCalle').value);
			xajax_asignarEstante(byId('lstEstante').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Editar Estante';
			
			byId('txtEstante').focus();
			byId('txtEstante').select();
		} else if (tipo == 2) {
			byId('hddIdEstante').value = byId('lstEstante').value;
			byId('hddIdTramo').value = byId('lstTramo').value;
			
			byId('txtTramo').readOnly = false;
			
			byId('txtTramo').className = 'inputHabilitado';
			
			byId('trCalle').style.display = '';
			byId('trEstante').style.display = '';
			byId('trTramo').style.display = '';
			
			xajax_asignarCalle(byId('lstCalle').value);
			xajax_asignarEstante(byId('lstEstante').value);
			xajax_asignarTramo(byId('lstTramo').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Editar Tramo';
			
			byId('txtTramo').focus();
			byId('txtTramo').select();
		} else if (tipo == 3) {
			byId('hddIdTramo').value = byId('lstTramo').value;
			byId('hddIdCasilla').value = byId('lstCasilla').value;
			
			byId('txtCasilla').readOnly = false;
			
			byId('txtCasilla').className = 'inputHabilitado';
			
			byId('trCalle').style.display = '';
			byId('trEstante').style.display = '';
			byId('trTramo').style.display = '';
			byId('trCasilla').style.display = '';
			
			xajax_asignarCalle(byId('lstCalle').value);
			xajax_asignarEstante(byId('lstEstante').value);
			xajax_asignarTramo(byId('lstTramo').value);
			xajax_asignarCasilla(byId('lstCasilla').value);
			
			byId('tdFlotanteTitulo2').innerHTML = 'Editar Casilla';
			
			byId('txtCasilla').focus();
			byId('txtCasilla').select();
		}
		
		byId('hddTipo').value = tipo;
	}
</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_compras.php"); ?>
    </div>

    <div id="divInfo" class="print">
		<table width="100%">
        <tr>
            <td class="tituloPaginaCompras">Almacenes</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_formAlmacen(this.id);">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                        <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="validarEliminarLote();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
					<a class="modalImg" id="aImportar" rel="#divFlotante" onclick="xajax_formImportarAlmacen(this.id);">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right">			
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select name="lstEmpresa" id="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarAlmacen(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaAlmacen" name="frmListaAlmacen" style="margin:0">
            	<div id="tdListaAlmacen" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
                            <td>Inactivo</td>
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
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>


<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmAlmacen" name="frmAlmacen" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblAlmacen" width="600" style="display:none;">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
            	<td colspan="2">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onkeyup="xajax_asignarEmpresaUsuario(this.value,'Empresa','ListaEmpresa');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="formListaEmpresas(this,'Empresa','ListaEmpresa');">
                        	<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/ico_pregunta.gif"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
				</td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Almacén:</td>
                <td colspan="2">
                	<input type="text" id="txtDesAlmacen" name="txtDesAlmacen" size="40"/>
                	<input type="hidden" id="hddIdAlmacen" name="hddIdAlmacen"/>
				</td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td colspan="2" width="75%">
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Estatus para Venta:</td>
                <td colspan="2" width="75%">
                	<select id="lstEstatusVenta" name="lstEstatusVenta">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Estatus para Compra:</td>
                <td colspan="2">
                	<select id="lstEstatusCompra" name="lstEstatusCompra">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left" id="trlstCalle">
            	<td align="right" class="tituloCampo">Calle:</td>
                <td id="tdlstCalle">
                	<select id="lstCalle" name="lstCalle">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" rel="#divFlotante2" onclick="detalles(this, 0);">
                    <button type="button" id="btnAgregarCalle" name="btnAgregarCalle" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" rel="#divFlotante2" onclick="editarDetalles(this, 0);">
                    <button type="button" id="btnEditarCalle" name="btnEditarCalle" title="Editar" disabled="disabled"><img src="../img/iconos/pencil.png"/></button>
                </a>
                	<button type="button" id="btnEliminarCalle" name="btnEliminarCalle" onclick="if (confirm('¿Desea eliminar la calle?') == true) xajax_eliminarCalle(byId('lstCalle').value)" title="Eliminar Calle" disabled="disabled"/><img src="../img/iconos/delete.png" />
                </td>
            </tr>
            <tr align="left" id="trlstEstante">
            	<td align="right" class="tituloCampo">Estante:</td>
                <td id="tdlstEstante">
                	<select id="lstEstante" name="lstEstante">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" rel="#divFlotante2" onclick="detalles(this, 1);">
                    <button type="button" id="btnAgregarEstante" name="btnAgregarEstante" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" rel="#divFlotante2" onclick="editarDetalles(this, 1);">
                    <button type="button" id="btnEditarEstante" name="btnEditarEstante" title="Editar" disabled="disabled"><img src="../img/iconos/pencil.png"/></button>
                </a>
                	<button type="button" id="btnEliminarEstante" name="btnEliminarEstante" onclick="if (confirm('¿Desea eliminar el estante?') == true) xajax_eliminarEstante(byId('lstEstante').value)" title="Eliminar Estante" disabled="disabled"/><img src="../img/iconos/delete.png" />
                </td>
            </tr>
            <tr align="left" id="trlstTramo">
            	<td align="right" class="tituloCampo">Tramo:</td>
                <td id="tdlstTramo">
                	<select id="lstTramo" name="lstTramo">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" rel="#divFlotante2" onclick="detalles(this, 2);">
                    <button type="button" id="btnAgregarTramo" name="btnAgregarTramo" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" rel="#divFlotante2" onclick="editarDetalles(this, 2);">
                    <button type="button" id="btnEditarTramo" name="btnEditarTramo" title="Editar" disabled="disabled"><img src="../img/iconos/pencil.png"/></button>
                </a>
                	<button type="button" id="btnEliminarTramo" name="btnEliminarTramo" onclick="if (confirm('¿Desea eliminar el tramo?') == true) xajax_eliminarTramo(byId('lstTramo').value)" title="Eliminar Tramo" disabled="disabled"/><img src="../img/iconos/delete.png" />
                </td>
            </tr>
            <tr align="left" id="trlstCasilla">
            	<td align="right" class="tituloCampo">Casilla:</td>
                <td id="tdlstCasilla">
                	<select id="lstCasilla" name="lstCasilla">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" rel="#divFlotante2" onclick="detalles(this, 3);">
                    <button type="button" id="btnAgregarCasilla" name="btnAgregarCasilla" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" rel="#divFlotante2" onclick="editarDetalles(this, 3);">
                    <button type="button" id="btnEditarCasilla" name="btnEditarCasilla" title="Editar" disabled="disabled"><img src="../img/iconos/pencil.png"/></button>
                </a>
                    <button type="button" id="btnEliminarCasilla" name="btnEliminarCasilla"onclick="if (confirm('¿Desea eliminar la casilla?') == true) xajax_eliminarCasilla(byId('lstCasilla').value)" title="Eliminar Casilla" disabled="disabled"/><img src="../img/iconos/delete.png" />
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<button type="submit" onclick="validarFormAlmacen();">Guardar</button>
        	<button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</form>

<form action="controladores/ac_upload_file_almacen.php" enctype="multipart/form-data" id="frmImportarAlmacen" name="frmImportarAlmacen" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarAlmacen" width="960" style="display:none;">
    <tr align="left">
        <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
        <td width="85%">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="txtIdEmpresaImportarAlmacen" name="txtIdEmpresaImportarAlmacen" onkeyup="xajax_asignarEmpresaUsuario(this.value,'EmpresaImportarAlmacen','ListaEmpresa');" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" id="aListarEmp" rel="#divFlotante2" onclick="formListaEmpresas(this,'EmpresaImportarAlmacen','ListaEmpresa');">
                    <button type="button" id="btnListarEmpresaImportarAlmacen" name="btnListarEmpresaImportarAlmacen" title="Listar"><img src="../img/iconos/ico_pregunta.gif"/></button>
                </a>
                </td>
                <td><input type="text" id="txtEmpresaImportarAlmacen" name="txtEmpresaImportarAlmacen" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr align="left">
    	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td>
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript: submit();" />
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
                        <td>Almacén</td>
                        <td>Calle</td>
                        <td>Estante</td>
                        <td>Tramo</td>
                        <td>Casilla</td>
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
        	<button type="submit" id="btnGuardarImportarAlmacen" name="btnGuardarImportarAlmacen" onclick="validarFormImportar();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarAlmacen" name="btnCancelarImportarAlmacen" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="700">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
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
        <form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
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
    
<form id="frmUbicacionAlmacen" name="frmUbicacionAlmacen" style="margin:0" onsubmit="return false;">
    <input type="hidden" id="hddTipo" name="hddTipo" readonly="readonly"/>
    <table border="0" id="tblDetallesAlmacen" width="400">
    <tr id="trCalle">
        <td align="right" class="tituloCampo" width="35%"><span class="textoRojoNegrita">*</span>Calle:</td>
        <td width="65%">
            <input type="text" id="txtCalle" name="txtCalle"/>
            <input type="hidden" id="hddIdCalle" name="hddIdCalle" readonly="readonly"/>
        </td>
    </tr>
    <tr id="trEstante">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estante:</td>
        <td>
            <input type="text" id="txtEstante" name="txtEstante"/>
            <input type="hidden" id="hddIdEstante" name="hddIdEstante" readonly="readonly"/>
        </td>
    </tr>
    <tr id="trTramo">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tramo:</td>
        <td>
            <input type="text" id="txtTramo" name="txtTramo"/>
            <input type="hidden" id="hddIdTramo" name="hddIdTramo" readonly="readonly"/>
        </td>
    </tr>
    <tr id="trCasilla">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Casilla:</td>
        <td>
            <input type="text" id="txtCasilla" name="txtCasilla"/>
            <input type="hidden" id="hddIdCasilla" name="hddIdCasilla" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <button type="submit" onclick="validarDetalles();">Aceptar</button>
            <button type="button" id="btnCancelarUbicacionAlmacen" name="btnCancelarUbicacionAlmacen" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoAlmacenes(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

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

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle1 = document.getElementById("divFlotanteTitulo2");
var theRoot1   = document.getElementById("divFlotante2");
Drag.init(theHandle1, theRoot1);
</script>