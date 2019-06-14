<?php
require_once("connections/conex.php");

session_start();

// Validación del Módulo
include('inc_sesion.php');
if (!(validaAcceso("pg_comision_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
// Fin Validación del Módulo

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_comision_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Comisión</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblComision').style.display = 'none';
		
		if (verTabla == "tblComision") {
			document.forms['frmComision'].reset();
			byId('hddIdComision').value = '';
			
			byId('trNivelProductividad').style.display = 'none';
			byId('trPorcentajeArticulo').style.display = 'none';
			byId('trNivelProductividadUnidad').style.display = 'none';
			
			byId('lstTipoPorcentaje').className = 'inputHabilitado';
			byId('lstTipoImporte').className = 'inputHabilitado';
			byId('lstAplicaIva').className = 'inputHabilitado';
			byId('txtPorcentajeComision').className = 'inputHabilitado';
			byId('lstModoComision').className = 'inputHabilitado';
			
			xajax_formComision(valor, xajax.getFormValues('frmComision'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Comisión';
			} else {
				tituloDiv1 = 'Agregar Comisión';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblComision") {
			byId('txtPorcentajeComision').focus();
			byId('txtPorcentajeComision').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaTipoOrden').style.display = 'none';
		byId('tblNivelProdutividad').style.display = 'none';
		byId('tblPorcentajeArt').style.display = 'none';
		byId('tblNivelProductividadUnidad').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('txtCriterioBuscarEmpresa').className = 'inputHabilitado';
			
			byId('hddObjDestino').value = valor;
			byId('hddNomVentana').value = valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblListaTipoOrden") {
			document.forms['frmBuscarTipoOrden'].reset();
			byId('txtCriterioBuscarTipoOrden').className = 'inputHabilitado';
			byId('btnBuscarTipoOrden').click();
			
			tituloDiv2 = 'Filtros de Orden';
		} else if (verTabla == "tblNivelProdutividad"){
			document.forms['frmNivelProductividad'].reset();
			if (valor2 == "NuevoNivelProdutividad") {
				byId('hddIdNivelProductividad').value = "";
			}
			
			byId('txtProductividadMayor').className = 'inputHabilitado';
			byId('txtProductividadMenor').className = 'inputHabilitado';
			byId('txtPorcentajeProductividad').className = 'inputHabilitado';
			
			tituloDiv2 = valor;			
		}  else if (verTabla == "tblPorcentajeArt"){
			document.forms['frmPorcentajeArt'].reset(); 
			document.forms['frmBuscarArt'].reset(); 
			if (valor2 == "NuevoPorcentajeArt") {
				byId('hddIdPorcentajeArt').value = "";
			}
			
			xajax_listaAccesorio();
			
			tituloDiv2 = valor;
		}else if (verTabla == "tblNivelProductividadUnidad"){
			document.forms['frmNivelProductividadUnidad'].reset(); 
			if (valor2 == "nuevoNivelProdutividadUnidad") {
				byId('hddIdNivelProductividadUnidad').value = "";
			}
			
			tituloDiv2 = valor;
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblListaTipoOrden") {
			byId('txtCriterioBuscarTipoOrden').focus();
			byId('txtCriterioBuscarTipoOrden').select();
		} else if (verTabla == "tblNivelProdutividad"){
			byId('txtProductividadMayor').focus();
			byId('txtProductividadMayor').select();
		}
	}
	
	function asignarTipoPorcentaje(){
		idTipoComision = byId('lstTipoComision').value;
		idTipoPorcentaje = byId('lstTipoPorcentaje').value;
		comisionSobre = byId('lstTipoImporte').value;
		
		if (byId('trPorcentajeArticulo').style.display == '') { // PARA LOS ACCESSORIO
			byId('trPorcentajeArticulo').style.display = 'none';
		}
		if (byId('trNivelProductividad').style.display == '') { // PARA LOS NIVELES DE PRODUTIVIDA (SERVICIO)
			byId('trNivelProductividad').style.display = 'none';
		}
		if (byId('trNivelProductividadUnidad').style.display == '') { // PARA LOS NIVELES DE PRODUTIVIDA (VEHICULO)
			byId('trNivelProductividadUnidad').style.display = 'none';
		}
		
		switch (idTipoComision) {
			case "1": // M.O
				if (idTipoPorcentaje == 2) {
					byId('trNivelProductividad').style.display = ''; // POR PRODUCTIVIDAD
				}
				break;
			case "5": // VEHICULO
				if (inArray(comisionSobre, [5]) && idTipoPorcentaje == 3) {
					byId('trNivelProductividadUnidad').style.display = ''; // POR PRODUCTIVIDAD
				}
				break;
			case "6": // ACESSORIO
				if (inArray(comisionSobre, [3]) && idTipoPorcentaje == 4) {
					byId('trPorcentajeArticulo').style.display = ''; //ARTICULOS
				}
				break;
		}
	}
	
	function validarFrmComision() {
		if (validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('lstDepartamento','t','lista') == true
		&& validarCampo('lstCargo','t','lista') == true
		&& validarCampo('lstTipoPorcentaje','t','lista') == true
		&& validarCampo('lstTipoImporte','t','lista') == true
		&& validarCampo('lstAplicaIva','t','listaExceptCero') == true
		&& validarCampo('lstTipoComision','t','lista') == true
		&& validarCampo('txtPorcentajeComision','t','') == true
		&& validarCampo('lstModoComision','t','lista') == true) {
			byId('btnGuardarComision').disabled = true;
			byId('btnCancelarComision').disabled = true;
			xajax_guardarComision(xajax.getFormValues('frmComision'), xajax.getFormValues('frmListaComision'));
		} else {
			validarCampo('lstEmpresa','t','lista');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('lstDepartamento','t','lista');
			validarCampo('lstCargo','t','lista');
			validarCampo('lstTipoPorcentaje','t','lista');
			validarCampo('lstTipoImporte','t','lista');
			validarCampo('lstAplicaIva','t','listaExceptCero');
			validarCampo('lstTipoComision','t','lista');
			validarCampo('txtPorcentajeComision','t','');
			validarCampo('lstModoComision','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idComision){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarComision(idComision, xajax.getFormValues('frmListaComision'));
		}
	}
	
	function validarInsertarEmpresa(idEmpresa) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarEmpresa' + cont) == undefined)) {
				byId('btnInsertarEmpresa' + cont).disabled = true;
			}
		}
		xajax_insertarEmpresa(idEmpresa, xajax.getFormValues('frmComision'));
	}
	
	function validarInsertarTipoOrden(idTipoOrden) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarTipoOrden' + cont) == undefined)) {
				byId('btnInsertarTipoOrden' + cont).disabled = true;
			}
		}
		xajax_insertarTipoOrden(idTipoOrden, xajax.getFormValues('frmComision'));
	}
	
	function validarInsertarArticulo(idArticulo) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarArt' + cont) == undefined)) {
				byId('btnInsertarArt' + cont).disabled = true;
			}
		}
		xajax_insertarArticulo(idArticulo, xajax.getFormValues('frmComision'));
	}
	
	function validarFrmNivelProductividad(){
		if (validarCampo('txtProductividadMayor','t','') == true
		&& validarCampo('txtProductividadMenor','t','') == true
		&& validarCampo('txtPorcentajeProductividad','t','') == true
		) {
			if (byId("hddIdNivelProductividad").value > 0){
				xajax_editarNivelProductividad(xajax.getFormValues('frmNivelProductividad'));
			} else{
				xajax_insertarNivelProductividad(xajax.getFormValues('frmComision'),xajax.getFormValues('frmNivelProductividad'));
			}
		} else {
			validarCampo('txtProductividadMayor','t','');
			validarCampo('txtProductividadMenor','t','');
			validarCampo('txtPorcentajeProductividad','t','');

			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

	function validarFrmNivelProductividadUnidad(){
		if (validarCampo('slctTipo','t','listaExceptCero') == true
		&& validarCampo('txtMayoIgual','t','') == true
		&& validarCampo('txtMenorIgual','t','') == true
		&& validarCampo('txPorcentaje','t','') == true) {
			if (byId("hddIdNivelProductividadUnidad").value > 0){ //editar
				xajax_editarNivelProdUnidad(xajax.getFormValues('frmNivelProductividadUnidad'));
			} else {//insertar
				xajax_insertaNivelProductUnidad(xajax.getFormValues('frmComision'),xajax.getFormValues('frmNivelProductividadUnidad'));
			}
		} else {
			validarCampo('slctTipo','t','listaExceptCero');
			validarCampo('txtMayoIgual','t','');
			validarCampo('txtMenorIgual','t','');
			validarCampo('txPorcentaje','t','');

			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function habilitarInputMonto(column){
		$("input[name=textPorcentajeArt"+column+"]").val(" ");
	}
	
	function habilitarInputPorcentaje(column){
		$("input[name=textMonto"+column+"]").val(" ");
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaErp">Comisión</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblComision');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                	<td id="tdlstEmpresaBuscar" colspan="3"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Cargo:</td>
                	<td id="tdlstCargoBuscar" colspan="3">
                        <select id="lstCargoBuscar" name="lstCargoBuscar">
                        	<option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModuloBuscar">
                        <select id="lstModuloBuscar" name="lstModuloBuscar">
                        	<option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo" width="120">Tipo Comisión:</td>
                	<td id="tdlstTipoComisionBuscar"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Tipo Porcentaje:</td>
                	<td>
                        <select id="lstTipoPorcentajeBuscar" name="lstTipoPorcentajeBuscar" onchange="byId('btnBuscar').click();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">1.- Simple</option>
                            <option value="2">2.- Por Productividad</option>
                            <option value="3">3.- Por Rango</option>
                            <option value="4">4.- Por Item</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo">Modo Comisión:</td>
                    <td>
                        <select id="lstModoComisionBuscar" name="lstModoComisionBuscar" onchange="byId('btnBuscar').click();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">1.- Por Venta Propia</option>
                            <option value="2">2.- Por Venta General</option>
                            <option value="3">3.- Por Venta Subordinada</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarComision(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaComision" name="frmListaComision" style="margin:0">
            	<div id="divListaComision" style="width:100%"></div>
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
    
<form id="frmComision" name="frmComision" style="margin:0" onsubmit="return false;">
    <div id="tblComision" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td valign="top" width="65%">
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td width="30%" id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ] </option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Modulo:</td>
                    <td width="30%" id="tdlstModulo">
                        <select id="lstModulo" name="lstModulo">
                            <option value="-1">[ Seleccione ] </option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Departamento:</td>
                    <td id="tdlstDepartamento">
                        <select id="lstDepartamento" name="lstDepartamento">
                            <option value="-1">[ Seleccione ] </option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cargo:</td>
                    <td id="tdlstCargo">
                        <select id="lstCargo" name="lstCargo">
                            <option value="-1">[ Seleccione ] </option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Comision Sobre:</td>
                    <td>
                        <select id="lstTipoImporte" name="lstTipoImporte" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">1.- Precio</option>
                            <option value="2">2.- Costo</option>
                            <option value="3">3.- Monto Fijo</option>
                            <option value="4">4.- UT</option>
                            <option value="5">5.- Utilidad</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Con Impuesto:</td>
                    <td>
                        <select id="lstAplicaIva" name="lstAplicaIva" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Comisión:</td>
                    <td id="tdlstTipoComision"></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Porcentaje:</td>
                    <td>
                        <select id="lstTipoPorcentaje" name="lstTipoPorcentaje" onchange="asignarTipoPorcentaje();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">1.- Simple</option>
                            <option value="2">2.- Por Productividad</option>
                            <option value="3">3.- Por Rango</option>
                            <option value="4">4.- Por Item</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Porcentaje Comision:</td>
                    <td><input type="text" id="txtPorcentajeComision" name="txtPorcentajeComision" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Modo Comisión:</td>
                    <td>
                        <select id="lstModoComision" name="lstModoComision" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">1.- Por Venta Propia</option>
                            <option value="2">2.- Por Venta General</option>
                            <option value="3">3.- Por Venta Subordinada</option>
                        </select>
                    </td>
                </tr>
                </table>
            </td>
            <td valign="top" width="35%">
            <fieldset><legend class="legend">Filtro de Orden Asignadas Por Servicios</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevoTipoOrden" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaTipoOrden');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button id="btnEliminarTipoOrden" name="btnEliminarTipoOrden" onclick="xajax_eliminarComisionTipoOrden(xajax.getFormValues('frmComision'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="max-height:250px; overflow:auto; width:100%;">
                            <table border="0" class="texto_9px" width="100%">
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" id="cbxItmTipoOrden" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                <td width="18%">Id</td>
                                <td width="82%">Tipo Orden</td>
                            </tr>
                            <tr id="trItmPieTipoOrden"></tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="2">
            <fieldset><legend class="legend">Empresas Asignadas Por Venta General</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarComisionEmpresa(xajax.getFormValues('frmComision'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="max-height:250px; overflow:auto; width:100%;">
                            <table border="0" class="texto_9px" width="100%">
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                <td width="14%">R.I.F.</td>
                                <td width="43%">Empresa</td>
                                <td width="43%">Sucursal</td>
                            </tr>
                            <tr id="trItmPie"></tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        
        <tr id="trNivelProductividad">
            <td colspan="2">
            <fieldset><legend class="legend">Nivel de Productividad</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevoNivel" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblNivelProdutividad', 'Nuevo Nivel Productividad', 'NuevoNivelProdutividad');">
                            <button type="button" id="btnAgregarProductividad" name="btnAgregarProductividad">
                                <table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table>
                            </button>
                        </a>
                        <button id="btnEliminarProductividad" name="btnEliminarProductividad" onclick="xajax_eliminarNivelProductividad(xajax.getFormValues('frmComision'))">
                            <table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="max-height:125px; overflow:auto; width:100%;">
                            <table border="0" class="texto_9px" width="100%">
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" id="cbxNivelProductividad" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                <td width="10%">Id</td>
                                <td width="30%">Mayor > </td>
                                <td width="30%">< Menor</td>
                                <td width="30%">Porcentaje %</td>
                                <td></td>
                            </tr>
                            <tr id="trItmPieNivelProductividad"></tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        
        <tr id="trPorcentajeArticulo">
            <td colspan="2">
            <fieldset><legend class="legend">Porcentaje Articulo</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevoPorcentajeArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPorcentajeArt', 'Lista Articulo', 'NuevoPorcentajeArt');">
                            <button type="button" id="btnAgregarArt" name="btnAgregarArt">
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr>
                                </table>
                            </button>
                        </a>
                        <button id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarComisionArticulo(xajax.getFormValues('frmComision'))">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;</td>
                                    <td><img src="img/iconos/delete.png"/></td>
                                    <td>&nbsp;</td>
                                    <td>Quitar</td>
                                </tr>
                            </table>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmArt" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                            <td width="10%">Id</td>
                            <td width="35%">Articulo</td>
                            <td width="35%">Descripcion de Articulo</td>
                            <td width="10%">Porcentaje %</td>
                            <td width="10%">Monto</td>
                        </tr>
                        <tr id="trItmPorcentajeArt"></tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        
        <tr id="trNivelProductividadUnidad">
            <td colspan="2">
            <fieldset><legend class="legend">Nivel de Productividad Por unidad</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevoNivelUnidad" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblNivelProductividadUnidad', 'Nuevo Nivel Productividad Por Unidad', 'nuevoNivelProdutividadUnidad');">
                            <button type="button" id="btnAgregarProductividadUnidad" name="btnAgregarProductividadUnidad">
                                <table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table>
                            </button>
                        </a>
                        <button id="btnEliminarProductividadUnidad" name="btnEliminarProductividadUnidad" onclick="xajax_eliminarNivelProdUnidad(xajax.getFormValues('frmComision'))">
                            <table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxNivelProductividadUnidad" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                            <td width="5%">Id</td>
                            <td width="20%">Tipo</td>
                            <td width="10%">Mayor ></td>
                            <td width="10%">< Menor</td>
                            <td width="20%">Tipo 2</td>
                            <td width="10%">Mayor ></td>
                            <td width="10%">< Menor</td>
                            <td width="15%">Porcentaje %</td>
                            <td></td>
                        </tr>
                        <tr id="trItmPieNivelProductividadUnidad"></tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2"><hr>
                <input type="hidden" id="hddIdComision" name="hddIdComision"/>
                <button type="submit" id="btnGuardarComision" name="btnGuardarComision" onclick="validarFrmComision();">Guardar</button>
                <button type="button" id="btnCancelarComision" name="btnCancelarComision" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
    	<td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly"/>
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
    
	<table border="0" id="tblListaTipoOrden" width="760">
    <tr>
    	<td>
        <form id="frmBuscarTipoOrden" name="frmBuscarTipoOrden" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarTipoOrden" name="txtCriterioBuscarTipoOrden" onkeyup="byId('btnBuscarTipoOrden').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarTipoOrden" name="btnBuscarTipoOrden" onclick="xajax_buscarTipoOrden(xajax.getFormValues('frmBuscarTipoOrden'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarTipoOrden'].reset(); byId('btnBuscarTipoOrden').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaTipoOrden" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaTipoOrden" name="btnCancelarListaTipoOrden" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
<form id="frmNivelProductividad" name="frmNivelProductividad" onsubmit="return false;" style="margin:0">
    <div id="tblNivelProdutividad" style="max-height:520px; overflow:auto; width:360px;">
        <table border="0" width="100%">
        <tr>
            <td align="right" class="tituloCampo" width="55%">Nivel de Productividad Mayor a:</td> 
            <td width="45%"><input type="text" id="txtProductividadMayor" name="txtProductividadMayor" onkeypress="return validarSoloNumerosReales(event);" size="20" style="text-align:right"/></td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Nivel de Productividad Menor a:</td>
            <td><input type="text" id="txtProductividadMenor" name="txtProductividadMenor" onkeypress="return validarSoloNumerosReales(event);" size="20" style="text-align:right"/></td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Porcentaje de Comisión:</td>
            <td><input type="text" id="txtPorcentajeProductividad" name="txtPorcentajeProductividad" onkeypress="return validarSoloNumerosReales(event);" size="20" style="text-align:right"/></td>
        </tr>
        <tr>
            <td align="right" colspan="2"><hr/>
                <input type="hidden" id="hddIdNivelProductividad" name="hddIdNivelProductividad" readonly="readonly"/>
                <button type="submit" id="btnGuardarNivelProductividad" name="btnGuardarNivelProductividad" onclick="validarFrmNivelProductividad();">Aceptar</button>
                <button type="button" id="btnCancelarNivelProductividad" name="btnCancelarNivelProductividad" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>   
</form>
    
    <table id="tblPorcentajeArt" width="760">
    <tr>
        <td>
        <form id="frmBuscarArt" name="frmBuscarArt" onsubmit="return false;" style="margin:0">
            <table id="tblBuscaPorcentajeArt" align="right" border="0">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input id="txtCriterioBuscarArticulo" class="inputHabilitado" type="text" onkeyup="byId('btnBuscarPorcentajeArt').click();" name="txtCriterioBuscarArticulo"></td>
                <td>
                    <button type="button" id="btnBuscarPorcentajeArt" name="btnBuscarPorcentajeArt" onclick="xajax_buscarAccesorio(xajax.getFormValues('frmBuscarArt'));">Buscar</button>
                    <button type="button" id="btnLimpiarPorcentajeArt" name="btnLimpiarPorcentajeArt" onclick="document.forms['frmBuscarArt'].reset(); byId('btnBuscarPorcentajeArt').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmPorcentajeArt" name="frmPorcentajeArt" onsubmit="return false;" style="margin:0">
            <table width="100%">
            <tr>
                <td><div id="divListaPorcentajeArt" style="width:100%"></div></td>
            </tr>
            <tr>
                <td align="right"><hr/>
                    <input type="hidden" id="hddIdPorcentajeArt" name="hddIdPorcentajeArt" readonly="readonly"/>
                    <button type="button" id="btnCancelarPorcentajeArt" name="btnCancelarPorcentajeArt" class="close">Cancelar</button>
                </td>
            </tr>
            </table>
        </form>	
        </td>
    </tr>
    </table>
    
<form id="frmNivelProductividadUnidad" name="frmNivelProductividadUnidad" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblNivelProductividadUnidad" width="760">
    <tr align="left">
        <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Tipo de Unidad:</td>
        <td width="18%">
            <select id="slctTipo" name="slctTipo" class="inputHabilitado">
                <option value="-1">[ Seleccione ]</option>
                <option value="0"> Todos </option>
                <option value="1"> Nuevo </option>
                <option value="2"> Usado </option>
                <option value="3"> Usado Particular </option>
            </select>
        </td>
        <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Mayor o Igual</td>
        <td width="18%"><input type="text" id="txtMayoIgual" name="txtMayoIgual" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
        <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Menor o Igual</td>
        <td width="16%"><input type="text" id="txtMenorIgual" name="txtMenorIgual" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
    </tr>
    
    <tr align="left">
        <td align="right" class="tituloCampo">Tipo de Unidad:</td>
        <td>
            <select id="slctTipo2" name="slctTipo2" class="inputHabilitado">
                <option value="-1">[ Seleccione ]</option>
                <option value="0"> Todos </option>
                <option value="1"> Nuevo </option>
                <option value="2"> Usado </option>
                <option value="3"> Usado Particular </option>
            </select>
        </td>
        <td align="right" class="tituloCampo">Mayor o Igual</td>
        <td><input type="text" id="txtMayoIgual2" name="txtMayoIgual2" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
        <td align="right" class="tituloCampo">Menor o Igual</td>
        <td><input type="text" id="txtMenorIgual2" name="txtMenorIgual2" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
    </tr>
    
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Porcentaje</td>
        <td colspan="5"><input type="text" id="txPorcentaje" name="txPorcentaje" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
    </tr>
    
    <tr>
        <td align="right" colspan="6"><hr/>
            <input type="hidden" id="hddIdNivelProductividadUnidad" name="hddIdNivelProductividadUnidad" readonly="readonly"/>
            <button type="button" id="btnGrdNivelProductividadUnidad" name="btnGrdNivelProductividadUnidad" onclick="validarFrmNivelProductividadUnidad();">Aceptar</button>
            <button type="button" id="btnCnlNivelProducUnidad" name="btnCnlNivelProducUnidad" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('lstTipoPorcentajeBuscar').className = 'inputHabilitado';
byId('lstModoComisionBuscar').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstCargoBuscar(this.value); byId(\'btnBuscar\').click();\"', 'lstEmpresaBuscar');
xajax_cargaLstCargoBuscar('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstModuloBuscar();
xajax_cargaLstTipoComisionBuscar();
xajax_listaComision(0,'nombre_cargo','ASC','');

var theHandle1 = document.getElementById("divFlotanteTitulo1");
var theRoot1   = document.getElementById("divFlotante1");
Drag.init(theHandle1, theRoot1);

var theHandle2 = document.getElementById("divFlotanteTitulo2");
var theRoot2   = document.getElementById("divFlotante2");
Drag.init(theHandle2, theRoot2);
</script>