<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_almacen_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_almacen_list.php");

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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Almacenes</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
    <script language="javascript" type="text/javascript">
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		tblAlmacen = (byId('tblAlmacen').style.display == '') ? '' : 'none';
		tblImportarAlmacen = (byId('tblImportarAlmacen').style.display == '') ? '' : 'none';
		
		byId('tblAlmacen').style.display = 'none';
		byId('tblImportarAlmacen').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante11') != undefined) {
			byId('imgCerrarDivFlotante11').onclick = function () {
				byId('tblAlmacen').style.display = tblAlmacen;
				byId('tblImportarAlmacen').style.display = tblImportarAlmacen;
				
				setTimeout(function(){
					(byId('imgCerrarDivFlotante11') == undefined) ? byId('imgCerrarDivFlotante1').id = 'imgCerrarDivFlotante11' : '';
					byId('imgCerrarDivFlotante12').id = 'imgCerrarDivFlotante1';
					
					byId('imgCerrarDivFlotante11').style.display = 'none';
					byId('imgCerrarDivFlotante1').style.display = '';
				}, 5);
			};
		}
		
		if (byId('imgCerrarDivFlotante11') == undefined && byId('imgCerrarDivFlotante1') != undefined
		&& byId('imgCerrarDivFlotante1').className == 'puntero') {
			byId('imgCerrarDivFlotante1').id = 'imgCerrarDivFlotante11';
		}
		
		if (byId('imgCerrarDivFlotante12') == undefined && byId('imgCerrarDivFlotante1') != undefined
		&& byId('imgCerrarDivFlotante1').className == 'close puntero') {
			byId('imgCerrarDivFlotante1').id = 'imgCerrarDivFlotante12';
		}
		
		if (nomObjeto != null) {
			byId('imgCerrarDivFlotante12').id = 'imgCerrarDivFlotante1';
			
			byId('imgCerrarDivFlotante11').style.display = 'none';
			byId('imgCerrarDivFlotante1').style.display = '';
		} else {
			byId('imgCerrarDivFlotante11').id = 'imgCerrarDivFlotante1';
			
			byId('imgCerrarDivFlotante1').style.display = '';
			byId('imgCerrarDivFlotante12').style.display = 'none';
		}
		
		if (verTabla == "tblAlmacen") {
			document.forms['frmAlmacen'].reset();
			byId('hddIdAlmacen').value = '';
			
			byId('txtDesAlmacen').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			byId('lstEstatusVenta').className = 'inputHabilitado';
			byId('lstEstatusCompra').className = 'inputHabilitado';
			
			xajax_formAlmacen(valor);
			
			if (valor > 0) {
				byId('txtIdEmpresa').className = 'inputInicial';
				
				byId('trlstCalle').style.display = '';
				byId('trlstEstante').style.display = '';
				byId('trlstTramo').style.display = '';
				byId('trlstCasilla').style.display = '';
				
				byId('txtIdEmpresa').readOnly = true;
				byId('aListarEmpresa').style.display = 'none';
				
				tituloDiv1 = 'Editar Almacén';
			} else {
				byId('txtIdEmpresa').className = 'inputHabilitado';
				
				byId('trlstCalle').style.display = 'none';
				byId('trlstEstante').style.display = 'none';
				byId('trlstTramo').style.display = 'none';
				byId('trlstCasilla').style.display = 'none';
				
				byId('txtIdEmpresa').readOnly = false;
				byId('aListarEmpresa').style.display = '';
				
				tituloDiv1 = 'Agregar Almacén';
			}
		} else if (verTabla == "tblImportarAlmacen") {
			document.forms['frmImportarAlmacen'].reset();
			byId('hddUrlArchivo').value = '';
			
			byId('txtIdEmpresaImportarAlmacen').className = 'inputHabilitado';
			byId('fleUrlArchivo').className = 'inputHabilitado';
			
			xajax_formImportarAlmacen();
			
			tituloDiv1 = 'Importar Almacén';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblAlmacen") {
			byId('txtDesAlmacen').focus();
			byId('txtDesAlmacen').select();
		} else if (verTabla == "tblImportarAlmacen") {
			byId('fleUrlArchivo').focus();
			byId('fleUrlArchivo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		tblListaEmpresa = (byId('tblListaEmpresa').style.display == '') ? '' : 'none';
		tblDetallesAlmacen = (byId('tblDetallesAlmacen').style.display == '') ? '' : 'none';
		
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblDetallesAlmacen').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblListaEmpresa').style.display = tblListaEmpresa;
				byId('tblDetallesAlmacen').style.display = tblDetallesAlmacen;
				
				setTimeout(function(){
					(byId('imgCerrarDivFlotante21') == undefined) ? byId('imgCerrarDivFlotante2').id = 'imgCerrarDivFlotante21' : '';
					byId('imgCerrarDivFlotante22').id = 'imgCerrarDivFlotante2';
					
					byId('imgCerrarDivFlotante21').style.display = 'none';
					byId('imgCerrarDivFlotante2').style.display = '';
				}, 5);
			};
		}
		
		if (byId('imgCerrarDivFlotante21') == undefined && byId('imgCerrarDivFlotante2') != undefined
		&& byId('imgCerrarDivFlotante2').className == 'puntero') {
			byId('imgCerrarDivFlotante2').id = 'imgCerrarDivFlotante21';
		}
		
		if (byId('imgCerrarDivFlotante22') == undefined && byId('imgCerrarDivFlotante2') != undefined
		&& byId('imgCerrarDivFlotante2').className == 'close puntero') {
			byId('imgCerrarDivFlotante2').id = 'imgCerrarDivFlotante22';
		}
		
		if (nomObjeto != null) {
			byId('imgCerrarDivFlotante22').id = 'imgCerrarDivFlotante2';
			
			byId('imgCerrarDivFlotante21').style.display = 'none';
			byId('imgCerrarDivFlotante2').style.display = '';
		} else {
			byId('imgCerrarDivFlotante21').id = 'imgCerrarDivFlotante2';
			
			byId('imgCerrarDivFlotante2').style.display = '';
			byId('imgCerrarDivFlotante22').style.display = 'none';
		}
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblDetallesAlmacen") {
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
			byId('lstEstatusUbicacion').className = 'inputInicial';
			
			byId('trCalle').style.display = 'none';
			byId('trEstante').style.display = 'none';
			byId('trTramo').style.display = 'none';
			byId('trCasilla').style.display = 'none';
			
			byId('hddTipo').value = valor;
			
			if (valor == 'Calle') {
				byId('txtCalle').readOnly = false;
				byId('txtCalle').className = 'inputHabilitado';
				
				byId('trCalle').style.display = '';
			} else if (valor == 'Estante') {
				byId('txtEstante').readOnly = false;
				byId('txtEstante').className = 'inputHabilitado';
				
				byId('trCalle').style.display = '';
				byId('trEstante').style.display = '';
				
				xajax_asignarCalle(byId('lstCalle').value);
			} else if (valor == 'Tramo') {
				byId('txtTramo').readOnly = false;
				byId('txtTramo').className = 'inputHabilitado';
				
				byId('trCalle').style.display = '';
				byId('trEstante').style.display = '';
				byId('trTramo').style.display = '';
				
				xajax_asignarCalle(byId('lstCalle').value);
				xajax_asignarEstante(byId('lstEstante').value);
			} else if (valor == 'Casilla') {
				byId('txtCasilla').readOnly = false;
				byId('txtCasilla').className = 'inputHabilitado';
				
				byId('trCalle').style.display = '';
				byId('trEstante').style.display = '';
				byId('trTramo').style.display = '';
				byId('trCasilla').style.display = '';
				
				xajax_asignarCalle(byId('lstCalle').value);
				xajax_asignarEstante(byId('lstEstante').value);
				xajax_asignarTramo(byId('lstTramo').value);
			}
			
			if (valor2 > 0) {
				if (valor == 'Calle') {
					xajax_asignarCalle(valor2, true);
				} else if (valor == 'Estante') {
					xajax_asignarEstante(valor2, true);
				} else if (valor == 'Tramo') {
					xajax_asignarTramo(valor2, true);
				} else if (valor == 'Casilla') {
					xajax_asignarCasilla(valor2, true);
				}
				
				tituloDiv2 = 'Editar ' + valor;
			} else {
				byId('lstEstatusUbicacion').className = 'inputHabilitado';
				selectedOption('lstEstatusUbicacion',1);
				
				tituloDiv2 = 'Agregar ' + valor;
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblDetallesAlmacen") {
			byId('txt' + valor).focus();
			byId('txt' + valor).select();
		}
	}
	
	function validarEliminar(idAlmacen){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAlmacen(idAlmacen, xajax.getFormValues('frmListaAlmacen'));
		}
	}
	
	function validarEliminarLote(){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAlmacenBloque(xajax.getFormValues('frmListaAlmacen'));
		}
	}
	
	function validarFrmAlmacen() {
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
	
	function validarFrmImportarAlmacen() {
		if (validarCampo('txtIdEmpresaImportarAlmacen','t','') == true
		&& validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarAlmacen(xajax.getFormValues('frmImportarAlmacen'), xajax.getFormValues('frmListaAlmacen'));
		} else {
			validarCampo('txtIdEmpresaImportarAlmacen','t','');
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmUbicacionAlmacen() {
		nombreObjeto = "txt" + byId('hddTipo').value;
		
		error = false;
		if (!(validarCampo(nombreObjeto,'t','') == true
		&& validarCampo('lstEstatusUbicacion','t','listaExceptCero') == true)) {
			validarCampo(nombreObjeto,'t','');
			validarCampo('lstEstatusUbicacion','t','listaExceptCero');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar la ubicación?') == true) {
				xajax_guardarUbicacion(xajax.getFormValues('frmUbicacionAlmacen'), xajax.getFormValues('frmAlmacen'));
			}
		}
	}
</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>

    <div id="divInfo" class="print">
		<table width="100%">
        <tr>
            <td class="tituloPaginaRepuestos">Almacenes</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAlmacen');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                        <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="validarEliminarLote();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
					<a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarAlmacen');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">			
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select name="lstEmpresa" id="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
				</tr>		
                <tr align="left">
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
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
            	<div id="divListaAlmacen" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif"/></td><td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Inactivo</td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante11" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante12" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmAlmacen" name="frmAlmacen" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAlmacen" width="560">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr>
            	<td width="30%"></td>
            	<td width="35%"></td>
            	<td width="35%"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
            	<td colspan="2">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                        	<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
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
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td colspan="2">
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus para Venta:</td>
                <td colspan="2">
                	<select id="lstEstatusVenta" name="lstEstatusVenta">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus para Compra:</td>
                <td colspan="2">
                	<select id="lstEstatusCompra" name="lstEstatusCompra">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left" id="trlstCalle">
            	<td align="right" class="tituloCampo"><?php echo $spanAlmCalle; ?>:</td>
                <td id="tdlstCalle">
                	<select id="lstCalle" name="lstCalle">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" id="aAgregarCalle" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Calle');">
                    <button type="button" id="btnAgregarCalle" name="btnAgregarCalle" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" id="aEditarCalle" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Calle', byId('lstCalle').value);">
                    <button type="button" id="btnEditarCalle" name="btnEditarCalle" title="Editar"><img src="../img/iconos/pencil.png"/></button>
                </a>
                	<button type="button" id="btnEliminarCalle" name="btnEliminarCalle" onclick="if (confirm('¿Desea eliminar la calle?') == true) xajax_eliminarCalle(byId('lstCalle').value)" title="Eliminar"/><img src="../img/iconos/delete.png"/>
                </td>
            </tr>
            <tr align="left" id="trlstEstante">
            	<td align="right" class="tituloCampo"><?php echo $spanAlmEstante; ?>:</td>
                <td id="tdlstEstante">
                	<select id="lstEstante" name="lstEstante">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" id="aAgregarEstante" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Estante');">
                    <button type="button" id="btnAgregarEstante" name="btnAgregarEstante" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" id="aEditarEstante" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Estante', byId('lstEstante').value);">
                    <button type="button" id="btnEditarEstante" name="btnEditarEstante" title="Editar"><img src="../img/iconos/pencil.png"/></button>
                </a>
                	<button type="button" id="btnEliminarEstante" name="btnEliminarEstante" onclick="if (confirm('¿Desea eliminar el estante?') == true) xajax_eliminarEstante(byId('lstEstante').value)" title="Eliminar"/><img src="../img/iconos/delete.png"/>
                </td>
            </tr>
            <tr align="left" id="trlstTramo">
            	<td align="right" class="tituloCampo"><?php echo $spanAlmTramo; ?>:</td>
                <td id="tdlstTramo">
                	<select id="lstTramo" name="lstTramo">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" id="aAgregarTramo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Tramo');">
                    <button type="button" id="btnAgregarTramo" name="btnAgregarTramo" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" id="aEditarTramo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Tramo', byId('lstTramo').value);">
                    <button type="button" id="btnEditarTramo" name="btnEditarTramo" title="Editar"><img src="../img/iconos/pencil.png"/></button>
                </a>
                	<button type="button" id="btnEliminarTramo" name="btnEliminarTramo" onclick="if (confirm('¿Desea eliminar el tramo?') == true) xajax_eliminarTramo(byId('lstTramo').value)" title="Eliminar"/><img src="../img/iconos/delete.png"/>
                </td>
            </tr>
            <tr align="left" id="trlstCasilla">
            	<td align="right" class="tituloCampo"><?php echo $spanAlmCasilla; ?>:</td>
                <td id="tdlstCasilla">
                	<select id="lstCasilla" name="lstCasilla">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">N/A</option>
                    </select>
                </td>
                <td>
                <a class="modalImg" id="aAgregarCasilla" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Casilla');">
                    <button type="button" id="btnAgregarCasilla" name="btnAgregarCasilla" title="Agregar"><img src="../img/iconos/add.png"/></button>
                </a>
                <a class="modalImg" id="aEditarCasilla" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDetallesAlmacen', 'Casilla', byId('lstCasilla').value);">
                    <button type="button" id="btnEditarCasilla" name="btnEditarCasilla" title="Editar"><img src="../img/iconos/pencil.png"/></button>
                </a>
                    <button type="button" id="btnEliminarCasilla" name="btnEliminarCasilla" onclick="if (confirm('¿Desea eliminar la casilla?') == true) xajax_eliminarCasilla(byId('lstCasilla').value)" title="Eliminar"/><img src="../img/iconos/delete.png"/>
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
    <tr>
    	<td align="right"><hr>
        	<button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFrmAlmacen();">Guardar</button>
        	<button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</form>

<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarAlmacen" name="frmImportarAlmacen" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarAlmacen" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
        <td width="85%">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="txtIdEmpresaImportarAlmacen" name="txtIdEmpresaImportarAlmacen" onblur="xajax_asignarEmpresaUsuario(this.value, 'EmpresaImportarAlmacen', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" id="aListarEmp" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'EmpresaImportarAlmacen', 'ListaEmpresa');">
                    <button type="button" id="btnListarEmpresaImportarAlmacen" name="btnListarEmpresaImportarAlmacen" title="Listar"><img src="../img/iconos/help.png"/></button>
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
        	<button type="submit" id="btnGuardarImportarAlmacen" name="btnGuardarImportarAlmacen" onclick="validarFrmImportarAlmacen();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarAlmacen" name="btnCancelarImportarAlmacen" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante21" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante22" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
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
    
<form id="frmUbicacionAlmacen" name="frmUbicacionAlmacen" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddTipo" name="hddTipo" readonly="readonly"/>
    <table border="0" id="tblDetallesAlmacen" width="360">
    <tr id="trCalle" align="left">
        <td align="right" class="tituloCampo" width="35%"><span class="textoRojoNegrita">*</span><?php echo $spanAlmCalle; ?>:</td>
        <td width="65%">
            <input type="text" id="txtCalle" name="txtCalle"/>
            <input type="hidden" id="hddIdCalle" name="hddIdCalle" readonly="readonly"/>
        </td>
    </tr>
    <tr id="trEstante" align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmEstante; ?>:</td>
        <td>
            <input type="text" id="txtEstante" name="txtEstante"/>
            <input type="hidden" id="hddIdEstante" name="hddIdEstante" readonly="readonly"/>
        </td>
    </tr>
    <tr id="trTramo" align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmTramo; ?>:</td>
        <td>
            <input type="text" id="txtTramo" name="txtTramo"/>
            <input type="hidden" id="hddIdTramo" name="hddIdTramo" readonly="readonly"/>
        </td>
    </tr>
    <tr id="trCasilla" align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanAlmCasilla; ?>:</td>
        <td>
            <input type="text" id="txtCasilla" name="txtCasilla"/>
            <input type="hidden" id="hddIdCasilla" name="hddIdCasilla" readonly="readonly"/>
        </td>
    </tr>
    <tr align="left">
    	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
    	<td>
        	<select id="lstEstatusUbicacion" name="lstEstatusUbicacion">
            	<option value="-1">[ Seleccione ]</option>
            	<option value="0">Inactivo</option>
            	<option value="1">Activo</option>
            </select>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <button type="submit" id="btnGuardarUbicacionAlmacen" name="btnGuardarUbicacionAlmacen" onclick="validarFrmUbicacionAlmacen();">Aceptar</button>
            <button type="button" id="btnCancelarUbicacionAlmacen" name="btnCancelarUbicacionAlmacen" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
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
xajax_listaAlmacen(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>