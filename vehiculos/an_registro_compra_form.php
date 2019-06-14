<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if((!validaAcceso("an_preregistro_compra_form","insertar") && !$_GET['id'])
|| (!validaAcceso("an_registro_compra_form","insertar") && $_GET['id'] > 0)) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_registro_compra_form.php");

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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Registro de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
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
		byId('tblListaAdicional').style.display = 'none';
		byId('tblListaGasto').style.display = 'none';
		byId('tblListaGastoImportacion').style.display = 'none';
		byId('tblListaOtrosCargos').style.display = 'none';
		byId('tblListaPais').style.display = 'none';
		byId('tblFacturaOtroCargo').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
		if (verTabla == "tblListaAdicional") {
			document.forms['frmBuscarGasto'].reset();
			document.forms['frmBuscarPaquete'].reset();
			
			byId('btnBuscarAdicional').click();
			byId('btnBuscarPaquete').click();
			
			tituloDiv1 = 'Adicionales';
		} else if (verTabla == "tblListaGasto") {
			document.forms['frmBuscarGasto'].reset();
			
			byId('btnBuscarGasto').click();
			
			tituloDiv1 = 'Gastos';
		} else if (verTabla == "tblListaGastoImportacion") {
			document.forms['frmBuscarGastoImportacion'].reset();
			
			byId('btnBuscarGastoImportacion').click();
			
			tituloDiv1 = 'Gastos por Importación';
		} else if (verTabla == "tblListaOtrosCargos") {
			xajax_listaOtroCargo(0, 'nombre', 'ASC');
			tituloDiv1 = 'Otros Cargos';
		} else if (verTabla == "tblListaPais") {
			document.forms['frmBuscarPais'].reset();
			
			byId('hddObjDestinoPais').value = valor;
			
			byId('btnBuscarPais').click();
			
			tituloDiv1 = 'Pais';
		} else if (verTabla == "tblFacturaOtroCargo") {
			xajax_cargarFacturaCargo(valor, xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmFacturaGasto'));
			tituloDiv1 = 'Datos del Documento';
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddPagarCobrarMotivo').value = valor2;
			byId('hddIngresoEgresoMotivo').value = valor3;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv1 = 'Motivos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaGasto") {
			byId('txtCriterioBuscarGasto').focus();
			byId('txtCriterioBuscarGasto').select();
		} else if (verTabla == "tblListaGastoImportacion") {
			byId('txtCriterioBuscarGastoImportacion').focus();
			byId('txtCriterioBuscarGastoImportacion').select();
		} else if (verTabla == "tblListaPais") {
			byId('txtCriterioBuscarPais').focus();
			byId('txtCriterioBuscarPais').select();
		} else if (verTabla == "tblFacturaOtroCargo") {
			byId('txtCriterioBuscarRegistroCompra').focus();
			byId('txtCriterioBuscarRegistroCompra').select();
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaProveedor').style.display = 'none';
		
		if (verTabla == "tblListaProveedor") {
			document.forms['frmBuscarProveedor'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv2 = 'Proveedores';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaProveedor") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		}
	}
	
	function seleccionarCondicion(idCondicionGasto) {
		if (idCondicionGasto == 1 && byId('lstAsociaDocumento').value == 1) { // 1 = Real && 1 = Si
			byId('fieldsetDatosFactura').style.display = 'none';
			byId('fieldsetListaRegistroCompra').style.display = '';
			
			byId('txtSubTotalFacturaGasto').className = 'inputSinFondo';
			byId('txtSubTotalFacturaGasto').readOnly = true;
		} else if (idCondicionGasto == 2 || byId('lstAsociaDocumento').value == 0) { // 2 = Estimado || 0 = No
			byId('fieldsetDatosFactura').style.display = '';
			byId('fieldsetListaRegistroCompra').style.display = 'none';
			
			byId('txtSubTotalFacturaGasto').className = 'inputHabilitado';
			byId('txtSubTotalFacturaGasto').readOnly = false;
			byId('txtSubTotalFacturaGasto').size = '17';
			
			byId('txtSubTotalFacturaGasto').focus();
			byId('txtSubTotalFacturaGasto').select();
		}
	}
	
	function seleccionarEnvio(idViaEnvio) {
		byId('tdArancelGrupo').style.display = 'none';
		byId('tdlstArancelGrupo').style.display = 'none';
		
		var lista = document.getElementById('lstViaEnvio');
		for(i = 0; i <= lista.options.length; i++){
			if (lista.options[i] != null) {
				if (lista.options[i].value == idViaEnvio && lista.options[i].text == 'VOR') {
					byId('tdArancelGrupo').style.display = '';
					byId('tdlstArancelGrupo').style.display = '';
					xajax_cargaLstArancelGrupo();
					break;
				}
			}
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtFechaRegistroCompra','t','fecha') == true
		&& validarCampo('txtNumeroFacturaProveedor','t','') == true
		&& validarCampo('txtNumeroControl','t','numeroControl') == true
		&& validarCampo('txtFechaProveedor','t','fecha') == true
		&& validarCampo('lstTipoClave','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtSubTotalDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalOrden','t','monto') == true
		&& validarCampo('lstRetencionImpuesto','t','listaExceptCero') == true
		&& validarCampo('lstGastoItem','t','listaExceptCero') == true)) {
			validarCampo('txtFechaRegistroCompra','t','fecha');
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroControl','t','numeroControl');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('lstMoneda','t','lista');
			validarCampo('txtIdProv','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtSubTotalDescuento','t','numPositivo');
			validarCampo('txtTotalOrden','t','monto');
			validarCampo('lstRetencionImpuesto','t','listaExceptCero');
			validarCampo('lstGastoItem','t','listaExceptCero');
			
			error = true;
		}
		
		if (byId('lstMoneda').value != byId('hddIdMoneda').value) {
			var cadena = byId('hddObjOtroCargo').value;
			var arrayObj = cadena.split("|");
			
			for (var i = 0; i < arrayObj.length; i++) {
				if (arrayObj[i] > 0) {
					if (!(validarCampo('hddSubTotalFacturaGasto' + arrayObj[i],'t','monto') == true)) {
						validarCampo('hddSubTotalFacturaGasto' + arrayObj[i],'t','monto');
						
						error = true;
					}
				}
			}
		}
		
		if (!(byId('lstTasaCambio') == undefined)) {
			if (!(validarCampo('lstTasaCambio','','listaExceptCero') == true)) {
				validarCampo('lstTasaCambio','','listaExceptCero');
				
				error = true;
			}
		}
		
		if (byId('txtIdNotaCargo').value > 0) {
			if (byId('lstRetencionImpuesto').value > 0) {
				if (!(validarCampo('txtIdMotivo','t','') == true)) {
					validarCampo('txtIdMotivo','t','');
				
					error = true;
				}
			}
			
			if (!(validarCampo('txtIdMotivoNCPlanMayor','t','') == true)) {
				validarCampo('txtIdMotivoNCPlanMayor','t','');
			
				error = true;
			}
		}
		
		if (byId('lstGastoItem').value == 1 && byId('txtTotalGastoItem').value != byId('txtTotalGasto').value) { // 0 = No, 1 = Si
			alert("El Total del Gasto Manual por Item no coincide con el Total de Gastos");
			return false;
		}
		
		if (byId('lstRetencionISLR').value > 0 && parseNumRafk(byId('txtPorcentajeISLR').value) > 0) {
			if (!(validarCampo('txtBaseImpISLR','t','monto') == true)) {
				validarCampo('txtBaseImpISLR','t','monto');
				
				error = true;
			}
		}
		
		if (byId('txtTotalOrden').value != byId('txtMontoTotalFactura').value) {
			alert("El Total del Registro de Compra no coincide con el Total de la Factura de Compra");
			return false;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			/*if (comparar(byId('txtFechaProveedor').value, byId('txtFechaPedido').value) > -1
			&& comparar(byId('txtFechaProveedor').value, byId('txtFechaOrden').value) > -1
			&& comparar(byId('txtFechaProveedor').value, byId('txtFechaRegistroCompra').value) < 1) {*/
				if (confirm('¿Seguro desea registrar la compra?') == true) {
					byId('btnGuardar').disabled = true;
					byId('btnAprobar').disabled = true;
					byId('btnCancelar').disabled = true;
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
			/*} else {
				alert("La fecha de la factura del proveedor no es válida");
				return false;
			}*/
		}
	}
	
	function validarFrmDctoAprobar() {
		error = false;
		if (!(validarCampo('txtIdFactura','t','') == true
		&& validarCampo('txtFechaRegistroCompra','t','fecha') == true
		&& validarCampo('txtNumeroFacturaProveedor','t','') == true
		&& validarCampo('txtNumeroControl','t','numeroControl') == true
		&& validarCampo('txtFechaProveedor','t','fecha') == true
		&& validarCampo('lstTipoClave','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtSubTotalDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalOrden','t','monto') == true
		&& validarCampo('lstRetencionImpuesto','t','listaExceptCero') == true
		&& validarCampo('lstGastoItem','t','listaExceptCero') == true)) {
			validarCampo('txtIdFactura','t','');
			validarCampo('txtFechaRegistroCompra','t','fecha');
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroControl','t','numeroControl');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('lstMoneda','t','lista');
			validarCampo('txtIdProv','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtSubTotalDescuento','t','numPositivo');
			validarCampo('txtTotalOrden','t','monto');
			validarCampo('lstRetencionImpuesto','t','listaExceptCero');
			validarCampo('lstGastoItem','t','listaExceptCero');
			
			error = true;
		}
		
		if (byId('lstMoneda').value != byId('hddIdMoneda').value) {
			if (!(validarCampo('lstNacionalizar','t','listaExceptCero') == true
			&& validarCampo('lstViaEnvio','t','lista') == true
			&& validarCampo('txtPuertoEmbarque','t','') == true
			&& validarCampo('txtDiferenciaCambiaria','t','numPositivo') == true
			&& validarCampo('txtIdMonedaNegociacion','t','') == true
			&& validarCampo('txtPorcSeguro','t','numPositivo') == true
			&& validarCampo('txtDctoTransporte','t','') == true
			&& validarCampo('txtFechaDctoTransporte','t','fecha') == true
			&& validarCampo('txtFechaVencDctoTransporte','t','fecha') == true
			&& validarCampo('txtFechaEstimadaLlegada','t','fecha') == true
			&& validarCampo('txtPlanillaImportacion','t','') == true)) {
				validarCampo('lstNacionalizar','t','listaExceptCero');
				validarCampo('lstViaEnvio','t','lista');
				validarCampo('txtPuertoEmbarque','t','');
				validarCampo('txtDiferenciaCambiaria','t','numPositivo');
				validarCampo('txtIdMonedaNegociacion','t','');
				validarCampo('txtPorcSeguro','t','numPositivo');
				validarCampo('txtDctoTransporte','t','');
				validarCampo('txtFechaDctoTransporte','t','fecha');
				validarCampo('txtFechaVencDctoTransporte','t','fecha');
				validarCampo('txtFechaEstimadaLlegada','t','fecha');
				validarCampo('txtPlanillaImportacion','t','');
			
				error = true;
			}
			
			var cadena = byId('hddObjOtroCargo').value;
			var arrayObj = cadena.split("|");
			
			for (var i = 0; i < arrayObj.length; i++) {
				if (arrayObj[i] > 0) {
					if (!(validarCampo('hddSubTotalFacturaGasto' + arrayObj[i],'t','monto') == true)) {
						validarCampo('hddSubTotalFacturaGasto' + arrayObj[i],'t','monto');
						
						error = true;
					}
				}
			}
		}
		
		if (!(byId('lstTasaCambio') == undefined)) {
			if (!(validarCampo('lstTasaCambio','','listaExceptCero') == true)) {
				validarCampo('lstTasaCambio','','listaExceptCero');
				
				error = true;
			}
		}
		
		if (byId('txtIdNotaCargo').value > 0) {
			if (byId('lstRetencionImpuesto').value > 0) {
				if (!(validarCampo('txtIdMotivo','t','') == true)) {
					validarCampo('txtIdMotivo','t','');
				
					error = true;
				}
			}
			
			if (!(validarCampo('txtIdMotivoNCPlanMayor','t','') == true)) {
				validarCampo('txtIdMotivoNCPlanMayor','t','');
			
				error = true;
			}
		}
		
		if (byId('lstGastoItem').value == 1 && byId('txtTotalGastoItem').value != byId('txtTotalGasto').value) { // 0 = No, 1 = Si
			alert("El Total del Gasto Manual por Item no coincide con el Total de Gastos");
			return false;
		}
		
		if (byId('lstRetencionISLR').value > 0 && parseNumRafk(byId('txtPorcentajeISLR').value) > 0) {
			if (!(validarCampo('txtBaseImpISLR','t','monto') == true)) {
				validarCampo('txtBaseImpISLR','t','monto');
				
				error = true;
			}
		}
		
		if (byId('txtTotalOrden').value != byId('txtMontoTotalFactura').value) {
			alert("El Total del Registro de Compra no coincide con el Total de la Factura de Compra");
			return false;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			/*if (comparar(byId('txtFechaProveedor').value, byId('txtFechaPedido').value) > -1
			&& comparar(byId('txtFechaProveedor').value, byId('txtFechaOrden').value) > -1
			&& comparar(byId('txtFechaProveedor').value, byId('txtFechaRegistroCompra').value) < 1) {*/
				if (confirm('¿Seguro desea registrar la compra?') == true) {
					byId('btnGuardar').disabled = true;
					byId('btnAprobar').disabled = true;
					byId('btnCancelar').disabled = true;
					xajax_aprobarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
			/*} else {
				alert("La fecha de la factura del proveedor no es válida");
				return false;
			}*/
		}
	}
	
	function validarFrmFacturaGasto() {
		error = false;
		if (byId('lstCondicionGasto').value == 2 || byId('lstAsociaDocumento').value == 0) { // 2 = Estimado || 0 = No
			if (!(validarCampo('lstCondicionGasto','t','lista') == true
			&& validarCampo('txtSubTotalFacturaGasto','t','monto') == true)) {
				validarCampo('lstCondicionGasto','t','lista');
				validarCampo('txtSubTotalFacturaGasto','t','monto');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_asignarFacturaCargo(xajax.getFormValues('frmListaRegistroCompra'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmFacturaGasto'));
		}
	}
	
	function validarInsertarAdicional(idAccesorio) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarAdicional' + cont) == undefined)) {
				byId('btnInsertarAdicional' + cont).disabled = true;
			}
		}
		xajax_insertarAdicional(idAccesorio, xajax.getFormValues('frmListaArticulo'));
	}
	
	function validarInsertarGasto(idGasto) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarGasto' + cont) == undefined)) {
				byId('btnInsertarGasto' + cont).disabled = true;
			}
		}
		xajax_insertarGasto(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	
	function validarInsertarGastoImportacion(idGasto) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarGastoImportacion' + cont) == undefined)) {
				byId('btnInsertarGastoImportacion' + cont).disabled = true;
			}
		}
		xajax_insertarGastoImportacion(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	
	function validarInsertarOtroCargo(idGasto) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarOtroCargo' + cont) == undefined)) {
				byId('btnInsertarOtroCargo' + cont).disabled = true;
			}
		}
		xajax_insertarOtroCargo(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	
	function validarInsertarPaquete(idPaquete) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarPaquete' + cont) == undefined)) {
				byId('btnInsertarPaquete' + cont).disabled = true;
			}
		}
		xajax_insertarPaquete(idPaquete, xajax.getFormValues('frmListaArticulo'));
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Registro de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" style="margin:0">
                <table border="0" width="100%">
                <tr>
                    <td width="12%"></td>
                    <td width="38%"></td>
                    <td width="11%"></td>
                    <td width="14%"></td>
                    <td width="11%"></td>
                    <td width="14%"></td>
                </tr>
                <tr align="left">
                    <td></td>
                    <td></td>
                    <td align="right" class="tituloCampo">Nro. Nota Cargo:</td>
                    <td>
                    	<input type="text" id="txtNumeroNotaCargo" name="txtNumeroNotaCargo" readonly="readonly" size="20" style="text-align:center"/>
                    	<input type="hidden" id="txtIdNotaCargo" name="txtIdNotaCargo"/>
					</td>
                    <td align="right" class="tituloCampo">Id Reg. Compra:</td>
                    <td><input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td colspan="3">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td>&nbsp;</td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
                    <td><input type="text" id="txtFechaRegistroCompra" name="txtFechaRegistroCompra" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td colspan="2" rowspan="6" valign="top">
                    <fieldset><legend class="legend">Proveedor</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Razón Social:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3" width="18%">Dirección:</td>
                            <td rowspan="3" width="44%"><textarea id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="3" style="width:99%"></textarea></td>
                            <td align="right" class="tituloCampo" width="18%"><?php echo $spanProvCxP; ?>:</td>
                            <td width="20%"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Teléfono:</td>
                            <td><input type="text" id="txtTelefonoProv" name="txtTelefonoProv" readonly="readonly" size="18" style="text-align:center"/></td>
                        </tr>

                        <tr align="left">
                            <td align="right" class="tituloCampo">Días Crédito:</td>
                            <td><input type="text" id="txtDiasCreditoProv" name="txtDiasCreditoProv" readonly="readonly" size="12" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Factura:
                        <br>
                        <span class="textoNegrita_10px">(Proveedor)</span></td>
                    <td><input type="text" id="txtNumeroFacturaProveedor" name="txtNumeroFacturaProveedor" size="20" style="text-align:center;"/></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:
                        <br>
                        <span class="textoNegrita_10px">(Proveedor)</span></td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtNumeroControl" name="txtNumeroControl" size="20" style="text-align:center"/>&nbsp;
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000 / Máquinas Fiscales"/>
                    </div>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Emisión:
                        <br>
                        <span class="textoNegrita_10px">(Proveedor)</span>
					</td>
                    <td colspan="3">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" size="10" style="text-align:center"/></td>
                        	<td><label><input type="checkbox" id="cbxFechaRegistro" name="cbxFechaRegistro" onclick="xajax_asignarFechaRegistro(xajax.getFormValues('frmDcto'));" value="1"/>Asignar como fecha de registro</label></td>
                        </tr>
                        </table>
					</td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                    <td>
                        <select id="lstTipoClave" name="lstTipoClave" onchange="selectedOption(this.id,1); xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '1');">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1" selected="selected">1.- COMPRA</option>
                            <option value="2">2.- ENTRADA</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                    <td id="tdlstClaveMovimiento">
                        <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                    <td>
                        <label><input type="radio" id="rbtTipoPagoContadoProv" name="rbtTipoPago" value="0"/> Contado</label>
                        <label><input type="radio" id="rbtTipoPagoCreditoProv" name="rbtTipoPago" value="1"/> Crédito</label>
                    </td>
                    <td id="tdNacionalizar" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nacionalizar:</td>
                    <td id="tdlstNacionalizar">
                        <select id="lstNacionalizar" name="lstNacionalizar" onchange="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                </tr>
                <tr id="trlstArancelGrupo" align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Vía de Envio:</td>
                    <td id="tdlstViaEnvio">
                        <select id="lstViaEnvio" name="lstViaEnvio">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td id="tdArancelGrupo" align="right" class="tituloCampo">% ADV General:</td>
                    <td id="tdlstArancelGrupo">
                    	<select id="lstArancelGrupo" name="lstArancelGrupo">
                        	<option>[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                    <td colspan="3">
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
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aAgregarArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaAdicional');">
                    	<button type="button" title="Agregar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                        <button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarAdicional(xajax.getFormValues('frmListaArticulo'));" title="Eliminar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
					</td>
				</tr>
                </table>
			</td>
		</tr>
        <tr>
        	<td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" class="texto_9px" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked, this.id, 1);"/></td>
                    <td width="4%">Nro.</td>
                	<td></td>
                	<td></td>
                    <td width="10%">Código</td>
                    <td width="44%">Descripción</td>
                    <td width="4%">Cant.</td>
                    <td width="8%">Costo Unit.</td>
                    <td width="4%">% Impuesto</td>
                    <td width="4%">% ADV</td>
                    <td width="6%">Peso Unit. (g)</td>
                    <td width="8%">Gasto</td>
                    <td width="8%">Total</td>
                </tr>
                <tr id="trItmPie">
                	<td colspan="10"></td>
                    <td class="trResaltarTotal" title="Total Peso por Item (g)"><input type="text" id="txtTotalPesoItem" name="txtTotalPesoItem" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                    <td class="trResaltarTotal" title="Total Gasto por Item"><input type="text" id="txtTotalGastoItem" name="txtTotalGastoItem" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                </table>
			</form>
            </td>
		</tr>
        <tr>
        	<td>
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
                <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetGastos"><legend class="legend">Gastos</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                        	<td colspan="7">
                                <a class="modalImg" id="aAgregarGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaGasto');">
                                    <button type="button" title="Agregar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarGasto" name="btnQuitarGasto" onclick="xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));" title="Quitar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" colspan="2"><span class="textoRojoNegrita">*</span>Gasto Manual por Item:</td>
                            <td colspan="2">
                            	<select id="lstGastoItem" name="lstGastoItem" onchange="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
                                	<option value="-1">[ Seleccione ]</option>
                                	<option value="0">No</option>
                                	<option value="1">Si</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="trItmPieGasto" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="4">Total Gastos:</td>
                            <td><input type="text" id="txtTotalGasto" name="txtTotalGasto" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                        	<td></td>
                        	<td width="24%"></td>
                            <td width="16%"></td>
                            <td width="8%"></td>
                            <td width="24%"></td>
                            <td width="14%"></td>
                            <td width="14%"></td>
						</tr>
                        </table>
					</fieldset>
                    
                    <fieldset id="fieldsetGastosImportacion"><legend class="legend">Gastos por Importación</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                        	<td colspan="7">
                                <a class="modalImg" id="aAgregarGastoImportacion" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaGastoImportacion');">
                                    <button type="button" title="Agregar Gastos por Importación"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarGastoImportacion" name="btnQuitarGastoImportacion" onclick="xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));" title="Quitar Gastos por Importación"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr id="trItmPieGastoImportacion" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="4">Total Gastos por Importación:</td>
                            <td><input type="text" id="txtTotalGastoImportacion" name="txtTotalGastoImportacion" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                        	<td></td>
                        	<td width="24%"></td>
                            <td width="16%"></td>
                            <td width="8%"></td>
                            <td width="24%"></td>
                            <td width="14%"></td>
                            <td width="14%"></td>
						</tr>
                        </table>
					</fieldset>
                    	
						<table width="100%">
                        <tr>
                        	<td width="24%"></td>
                        	<td width="76%"></td>
                        </tr>
                    	<tr id="trMotivo" align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:
                            	<br><span class="textoNegrita_10px">(Retención)</span>
                            </td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" onblur="xajax_asignarMotivo(this.value, 'Motivo', 'CP', 'I', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarMotivo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaMotivo', 'Motivo', 'CP', 'I');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtMotivo" name="txtMotivo" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr id="trMotivoNCPlanMayor" align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:
                            	<br><span class="textoNegrita_10px">(Nota Créd. Plan Mayor)</span>
                            </td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdMotivoNCPlanMayor" name="txtIdMotivoNCPlanMayor" onblur="xajax_asignarMotivo(this.value, 'MotivoNCPlanMayor', 'CP', 'I', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarMotivoNCPlanMayor" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaMotivo', 'MotivoNCPlanMayor', 'CP', 'I');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtMotivoNCPlanMayor" name="txtMotivoNCPlanMayor" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                        
                    	<table width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacionFactura" name="txtObservacionFactura" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="50%">
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="30%">Subtotal:</td>
                            <td style="border-top:1px solid;" width="22%"></td>
                            <td style="border-top:1px solid;" width="22%"></td>
                            <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="4%"></td>
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap">
                            	<input type="radio" id="rbtInicialPorc" name="rbtInicial" onclick="byId('txtDescuento').readOnly = false; byId('txtSubTotalDescuento').readOnly = true;" style="display:none" value="1">
                                
                            	<input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" size="6" style="text-align:right"/>%</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td>
                            	<input type="radio" id="rbtInicialMonto" name="rbtInicial" checked="checked" onclick="byId('txtDescuento').readOnly = true; byId('txtSubTotalDescuento').readOnly = false;" style="display:none" value="2">
                                
                            	<input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/>
							</td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total Registro Compra:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Total Factura Compra:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalFacturaMoneda"></td>
                        	<td><input type="text" id="txtMontoTotalFactura" name="txtMontoTotalFactura" class="inputSinFondo divMsjInfo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border-top:1px solid;"></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Exento:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExentoMoneda"></td>
                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Exonerado:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExoneradoMoneda"></td>
                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5"><hr></td>
                        </tr>
                        <tr align="right" id="trRetencionISLR" style="display:none">
                        	<td class="tituloCampo">Retención ISLR:</td>
                        	<td id="tdlstRetencionISLR"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr align="right" id="trBaseImponibleISLR" style="display:none">
                        	<td class="tituloCampo">Base Imponible ISLR:</td>
                            <td><input type="text" id="txtBaseImpISLR" name="txtBaseImpISLR" class="inputSinFondo" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                            <td><input type="text" id="txtPorcentajeISLR" name="txtPorcentajeISLR" readonly="readonly" size="6" style="text-align:right"/>%</td>
                            <td id="tdRetencionISLRMoneda"></td>
                            <td><input type="text" id="txtTotalRetencionISLR" name="txtTotalRetencionISLR" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" id="trMontoMayorISLR" style="display:none">
                        	<td class="tituloCampo">Monto Mayor a:</td>
                            <td><input type="text" id="txtMontoMayorISLR" name="txtMontoMayorISLR" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                            <td class="tituloCampo">Sustraendo:</td>
                            <td id="tdSustraendoISLRMoneda"></td>
                            <td><input type="text" id="txtSustraendoISLR" name="txtSustraendoISLR" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" id="trRetencionIva" style="display:none">
                        	<td class="tituloCampo">Retención de Impuesto:</td>
                            <td colspan="4">
                                <table border="0" width="100%">
                                <tr>
                                	<td id="tdlstRetencionImpuesto"></td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                                        <tr>
                                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                            <td align="center">Usted es Contribuyente Especial</td>
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
                <tr id="trDatosImportacion">
                	<td colspan="2">
                        <div class="wrap">
                            <!-- the tabs -->
                            <ul class="tabs">
                                <li><a href="#">Básicos</a></li>
                                <li><a href="#">Registro</a></li>
                                <li><a href="#">Otros Cargos</a></li>
                                <li><a href="#">Códigos Arancelarios</a></li>
                            </ul>
                            
                            <!-- tab "panes" -->
                            <div class="pane">
                                <table width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="14%">Actividad del Importador:</td>
                                    <td id="tdlstActividadImportador" width="19%">
                                        <select id="lstActividadImportador" name="lstActividadImportador">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo" width="14%">Clase de Importador:</td>
                                    <td id="tdlstClaseImportador" width="20%">
                                        <select id="lstClaseImportador" name="lstClaseImportador">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo" width="14%">Clase de Solicitud:</td>
                                    <td id="tdlstClaseSolicitud" width="19%">
                                        <select id="lstClaseSolicitud" name="lstClaseSolicitud">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Puerto de Llegada:</td>
                                    <td><input type="text" id="txtPuertoLlegada" name="txtPuertoLlegada"/></td>
                                    <td align="right" class="tituloCampo">Destino Final:</td>
                                    <td><input type="text" id="txtDestinoFinal" name="txtDestinoFinal"/></td>
                                    <td align="right" class="tituloCampo">Compañia Transportadora:</td>
                                    <td><input type="text" id="txtCompaniaTransporte" name="txtCompaniaTransporte"/></td>
                                </tr>
                                </table>
                            </div>
                            <div class="pane">
                                <table border="0" width="100%">
                                <tr>
                                    <td width="13%"></td>
                                    <td width="31%"></td>
                                    <td width="13%"></td>
                                    <td width="15%"></td>
                                    <td width="13%"></td>
                                    <td width="15%"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Exportador:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdProvExportador" name="txtIdProvExportador" onblur="xajax_asignarProveedor(this.value, 'ProvExportador', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aInsertarProvExportador" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaProveedor', 'ProvExportador');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreProvExportador" name="txtNombreProvExportador" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Consignatario:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdProvConsignatario" name="txtIdProvConsignatario" onblur="xajax_asignarProveedor(this.value, 'ProvConsignatario', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aInsertarProvConsignatario" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaProveedor', 'ProvConsignatario');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreProvConsignatario" name="txtNombreProvConsignatario" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Aduana:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdPaisAduana" name="txtIdPaisAduana" onblur="xajax_asignarPais(this.value, 'PaisAduana', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aInsertarPaisAduana" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaPais', 'PaisAduana');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombrePaisAduana" name="txtNombrePaisAduana" readonly="readonly" size="26"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo">Nro. Expediente:</td>
                                    <td>
                                    	<input type="hidden" id="hddIdExpediente" name="hddIdExpediente"/>
                                    	<input type="text" id="txtExpediente" name="txtExpediente" readonly="readonly" style="text-align:center"/>
									</td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Planilla Importación:</td>
                                    <td><input type="text" id="txtPlanillaImportacion" name="txtPlanillaImportacion" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">País Origen:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdPaisOrigen" name="txtIdPaisOrigen" onblur="xajax_asignarPais(this.value, 'PaisOrigen', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aInsertarPaisOrigen" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaPais', 'PaisOrigen');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombrePaisOrigen" name="txtNombrePaisOrigen" readonly="readonly" size="26"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo">Nro. Embarque / BL:</td>
                                    <td><input type="text" id="txtNumeroEmbarque" name="txtNumeroEmbarque" readonly="readonly" style="text-align:center"/></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Dcto. Transporte:</td>
                                    <td><input type="text" id="txtDctoTransporte" name="txtDctoTransporte" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">País Compra:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdPaisCompra" name="txtIdPaisCompra" onblur="xajax_asignarPais(this.value, 'PaisCompra', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aInsertarPaisCompra" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaPais', 'PaisCompra');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombrePaisCompra" name="txtNombrePaisCompra" readonly="readonly" size="26"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Puerto de Embarque:</td>
                                    <td><input type="text" id="txtPuertoEmbarque" name="txtPuertoEmbarque" size="25"/></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Dcto. Transporte:</td>
                                    <td><input type="text" id="txtFechaDctoTransporte" name="txtFechaDctoTransporte" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda de Negociación:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdMonedaNegociacion" name="txtIdMonedaNegociacion" onkeyup="xajax_asignarMoneda(this.value,'MonedaExtranjera');" readonly="readonly" size="6" style="text-align:right"/></td>
                                            <td>&nbsp;</td>
                                            <td><input type="text" id="txtMonedaNegociacion" name="txtMonedaNegociacion" readonly="readonly" size="26"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Seguro:</td>
                                    <td><input type="text" id="txtPorcSeguro" name="txtPorcSeguro" onblur="setFormatoRafk(this,2);" size="16" style="text-align:right"/></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Vencimiento Dcto. Transporte:</td>
                                    <td><input type="text" id="txtFechaVencDctoTransporte" name="txtFechaVencDctoTransporte" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Diferencia Cambiaria:</td>
                                    <td><input type="text" id="txtDiferenciaCambiaria" name="txtDiferenciaCambiaria" onblur="setFormatoRafk(this,3);" size="16" style="text-align:right"/></td>
                                    <td></td>
                                    <td></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Estimada Llegada:</td>
                                    <td><input type="text" id="txtFechaEstimadaLlegada" name="txtFechaEstimadaLlegada" size="10" style="text-align:center"/></td>
                                </tr>
                                </table>
                            </div>
                            <div class="pane">
                                <table align="left">
                                <tr>
                                    <td>
                                    <a class="modalImg" id="aAgregarOtrosCargos" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaOtrosCargos');">
                                        <button type="button" title="Agregar Cargo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                    </a>
                                        <button type="button" id="btnEliminarOtrosCargos" name="btnEliminarOtrosCargos" onclick="xajax_eliminarOtroCargo(xajax.getFormValues('frmTotalDcto'));" title="Eliminar Cargo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
                                
                                <table border="0" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td><input type="checkbox" id="cbxItmOtroCargo" onclick="selecAllChecks(this.checked, this.id, 2);"/></td>
                                    <td></td>
                                    <td width="32%">Gasto</td>
                                    <td width="11%">Fecha Reg. Compra</td>
                                    <td width="10%">Nro. Factura</td>
                                    <td width="10%">Nro. Control</td>
                                    <td width="27%">Proveedor</td>
                                    <td width="10%">Subtotal</td>
                                </tr>
                                <tr id="trItmPieOtroCargo" align="right" class="trResaltarTotal">
                                    <td class="tituloCampo" colspan="7">Total:</td>
                                    <td><span id="spnTotalOtrosCargos"></span></td>
                                </tr>
                                </table>
                                <input type="hidden" id="hddObjOtroCargo" name="hddObjOtroCargo" readonly="readonly"/>
                            </div>
                            <div class="pane">
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
									<td width="2%"></td>
                                    <td width="8%">Código Arancelario</td>
                                    <td width="34%">Descripción</td>
                                    <td width="6%">% ADV</td>
                                    <td width="6%">Cant. Items</td>
                                    <td width="6%">F.O.B.<span id="spnFOBArancelMoneda"></span></td>
                                    <td width="20%">
                                    	<table cellpadding="0" cellspacing="0" width="100%">
                                        <tr><td align="center">GASTOS<span id="spnGastosArancelMoneda"></span></td></tr>
                                        <tr><td id="tdGastosArancel"></td></tr>
                                        </table>
									</td>
                                    <td width="6%">C.I.F.<span id="spnCIFArancelMoneda"></span></td>
                                    <td width="6%">Peso Neto (g)</td>
                                    <td width="6%">Cant. Artículos</td>
                                </tr>
                                <tr id="trItmPieArancel" align="right" class="trResaltarTotal">
                                    <td class="tituloCampo" colspan="3">Total:</td>
                                    <td></td>
                                    <td><span id="spnCantItemsArancel"></span></td>
                                    <td><span id="spnTotalFOBArancel"></span></td>
                                    <td><span id="spnTotalGastosArancel"></span></td>
                                    <td><span id="spnTotalCIFArancel"></span></td>
                                    <td><span id="spnTotalPesoNetoArancel"></span></td>
                                    <td><span id="spnCantArticulosArancel"></span></td>
                                </tr>
                                </table>
                            </div>
						</div>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td align="right"><hr>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnAprobar" name="btnAprobar" onclick="validarFrmDctoAprobar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/tick.png"/></td><td>&nbsp;</td><td>Aprobar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
        </tr>
        </table>
	</div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
    <div id="tblListaAdicional" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Adicionales</a></li>
                        <li><a href="#">Paquetes</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table width="100%">
                        <tr>
                            <td>
                            <form id="frmBuscarAdicional" name="frmBuscarAdicional" onsubmit="return false;" style="margin:0">
                                <table align="right">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                    <td><input type="text" id="txtCriterioBuscarAdicional" name="txtCriterioBuscarAdicional" class="inputHabilitado" onkeyup="byId('btnBuscarAdicional').click();"/></td>
                                    <td>
                                        <button type="submit" id="btnBuscarAdicional" name="btnBuscarAdicional" onclick="xajax_buscarAdicional(xajax.getFormValues('frmBuscarAdicional'));">Buscar</button>
                                        <button type="button" onclick="byId('txtCriterioBuscarAdicional').value = ''; byId('btnBuscarAdicional').click();">Limpiar</button>
                                    </td>
                                </tr>
                                </table>
                            </form>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <form id="frmListaAdicional" name="frmListaAdicional" onsubmit="return false;" style="margin:0">
                                <div id="divListaAdicional" style="width:100%"></div>
                            </form>
                            </td>
                        </tr>
                        </table>
                    </div>
                    <div class="pane">
                        <table width="100%">
                        <tr>
                            <td>
                            <form id="frmBuscarPaquete" name="frmBuscarPaquete" onsubmit="return false;" style="margin:0">
                                <table align="right">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                    <td><input type="text" id="txtCriterioBuscarPaquete" name="txtCriterioBuscarPaquete" class="inputHabilitado" onkeyup="byId('btnBuscarPaquete').click();"/></td>
                                    <td>
                                        <button type="submit" id="btnBuscarPaquete" name="btnBuscarPaquete" onclick="xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));">Buscar</button>
                                        <button type="button" onclick="byId('txtCriterioBuscarPaquete').value = ''; byId('btnBuscarPaquete').click();">Limpiar</button>
                                    </td>
                                </tr>
                                </table>
                            </form>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <form id="frmListaPaquete" name="frmListaPaquete" onsubmit="return false;" style="margin:0">
                                <div id="divListaPaquete" style="width:100%"></div>
                            </form>
                            </td>
                        </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelarListaAdicional" name="btnCancelarListaAdicional" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
	
    <table border="0" id="tblListaGasto" width="760">
    <tr>
        <td>
        <form id="frmBuscarGasto" name="frmBuscarGasto" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarGasto" name="txtCriterioBuscarGasto" class="inputHabilitado" onkeyup="byId('btnBuscarGasto').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarGasto" name="btnBuscarGasto" onclick="xajax_buscarGasto(xajax.getFormValues('frmBuscarGasto'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarGasto').value = ''; byId('btnBuscarGasto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaGasto" name="frmListaGasto" onsubmit="return false;" style="margin:0">
            <div id="divListaGasto" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaGasto" name="btnCancelarListaGasto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
	
    <table border="0" id="tblListaGastoImportacion" width="760">
    <tr>
        <td>
        <form id="frmBuscarGastoImportacion" name="frmBuscarGastoImportacion" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarGastoImportacion" name="txtCriterioBuscarGastoImportacion" class="inputHabilitado" onkeyup="byId('btnBuscarGastoImportacion').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarGastoImportacion" name="btnBuscarGastoImportacion" onclick="xajax_buscarGastoImportacion(xajax.getFormValues('frmBuscarGastoImportacion'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarGastoImportacion').value = ''; byId('btnBuscarGastoImportacion').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaGastoImportacion" name="frmListaGastoImportacion" onsubmit="return false;" style="margin:0">
            <div id="divListaGastoImportacion" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaGastoImportacion" name="btnCancelarListaGastoImportacion" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaOtrosCargos" width="760">
    <tr>
    	<td><div id="divListaOtrosCargos" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<button type="button" id="btnCancelarOtrosCargos" name="btnCancelarOtrosCargos" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaPais" width="760">
    <tr>
    	<td>
        <form id="frmBuscarPais" name="frmBuscarPais" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarPais" name="txtCriterioBuscarPais" class="inputHabilitado" onkeyup="byId('btnBuscarPais').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarPais" name="btnBuscarPais" onclick="xajax_buscarPais(xajax.getFormValues('frmBuscarPais'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarPais'].reset(); byId('btnBuscarPais').click();">Limpiar</button>
                    <input type="hidden" id="hddObjDestinoPais" name="hddObjDestinoPais"/>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaPais" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaPais" name="btnCancelarListaPais" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
   
    <div id="tblFacturaOtroCargo" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmFacturaGasto" name="frmFacturaGasto" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddItmGasto" name="hddItmGasto"/>
                
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="14%">Condición Gasto:</td>
                    <td width="20%">
                        <select id="lstCondicionGasto" name="lstCondicionGasto">
                            <option value="-1">[ Seleccion ]</option>
                            <option value="1">Real</option>
                            <option value="2">Estimado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="14%">Asocia Documento:</td>
                    <td width="52%">
                        <select id="lstAsociaDocumento" name="lstAsociaDocumento">
                            <option value="-1">[ Seleccion ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                </tr>
                </table>
                
            <fieldset id="fieldsetDatosFactura"><legend class="legend">Datos de la Factura</legend>
            	<table width="100%">
                <tr>
                    <td>
                    	<table>
                        <tr align="right">
                            <td class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Subtotal:</td>
                            <td><input type="text" id="txtSubTotalFacturaGasto" name="txtSubTotalFacturaGasto" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <button type="submit" id="btnGuardarFacturaGasto" name="btnGuardarFacturaGasto" onclick="validarFrmFacturaGasto();">Aceptar</button>
                        <button type="button" id="btnCancelarFacturaGasto" name="btnCancelarFacturaGasto" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
            
            <fieldset id="fieldsetListaRegistroCompra"><legend class="legend">Lista Reg. Compra</legend>
                <table width="100%">
                <tr>
                    <td>
                    <form id="frmBuscarRegistroCompra" name="frmBuscarRegistroCompra" onsubmit="return false;" style="margin:0">
                        <table align="right">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" id="txtCriterioBuscarRegistroCompra" name="txtCriterioBuscarRegistroCompra" class="inputHabilitado" onkeyup="byId('btnBuscarRegistroCompra').click();"/></td>
                            <td>
                                <button type="submit" id="btnBuscarRegistroCompra" name="btnBuscarRegistroCompra" onclick="xajax_buscarRegistroCompra(xajax.getFormValues('frmBuscarRegistroCompra'));">Buscar</button>
                                <button type="button" onclick="document.forms['frmBuscarRegistroCompra'].reset(); byId('btnBuscarRegistroCompra').click();">Limpiar</button>
                                <input type="hidden" id="hddObjDestinoRegistroCompra" name="hddObjDestinoRegistroCompra"/>
                            </td>
                        </tr>
                        </table>
                    </form>
                    </td>
                </tr>
                <tr>
                    <td>
                    <form id="frmListaRegistroCompra" name="frmListaRegistroCompra" onsubmit="return false;" style="margin:0">
                        <input type="hidden" id="hddItmGastoListaRegistroCompra" name="hddItmGastoListaRegistroCompra"/>
                        <div id="divListaRegistroCompra" style="width:100%">
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
                <tr>
                    <td align="right"><hr>
                        <button type="button" id="btnCancelarListaRegistroCompra" name="btnCancelarListaRegistroCompra" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </fieldset>
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

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
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
                    <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaProveedor" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaProveedor" name="btnCancelarListaProveedor" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtNumeroFacturaProveedor').className = 'inputHabilitado';
byId('txtNumeroControl').className = 'inputHabilitado';
byId('txtFechaProveedor').className = 'inputHabilitado';
if (byId('rbtInicialPorc').checked == true) { 
	byId('txtDescuento').className = 'inputHabilitado';
	byId('txtSubTotalDescuento').className = 'inputSinFondo';
	byId('txtSubTotalDescuento').readOnly = true;
} else if (byId('rbtInicialMonto').checked == true) {
	byId('txtDescuento').className = 'inputInicial';
	byId('txtDescuento').readOnly = true;
	byId('txtSubTotalDescuento').className = 'inputHabilitado';
}
byId('txtBaseImpISLR').className = 'inputHabilitado';
byId('lstGastoItem').className = 'inputHabilitado';
byId('txtIdMotivo').className = 'inputHabilitado';
byId('txtIdMotivoNCPlanMayor').className = 'inputHabilitado';
byId('txtObservacionFactura').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaProveedor").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaDctoTransporte").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaVencDctoTransporte").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaEstimadaLlegada").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaProveedor",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
		/*selectedDate:{				This is an example of what the full configuration offers.
			day:5,						For full documentation about these settings please see the full version of the code.
			month:9,
			year:2006
		},
		yearsRange:[1978,2020],
		limitToToday:false,
		cellColorScheme:"orange",
		imgPath:"img/"
		dateFormat:"<?php echo spanDatePick; ?>",
		weekStartDay:1*/
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDctoTransporte",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaVencDctoTransporte",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaEstimadaLlegada",
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargaLstRetencionISLR();
<?php if (isset($_GET['idPedidoDetalle'])) { ?>
	xajax_nuevoDcto('<?php echo $_GET['idPedidoDetalle']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } else { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>