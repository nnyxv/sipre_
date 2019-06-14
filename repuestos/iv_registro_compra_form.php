<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if((!validaAcceso("iv_preregistro_compra_list","insertar") && !$_GET['id'])
|| (!validaAcceso("iv_registro_compra_list","insertar") && $_GET['id'] > 0)) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_registro_compra_form.php");

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
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Registro de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('trListaPedidoCompra').style.display = 'none';
		byId('trArticulosPedido').style.display = 'none';
		
		byId('tblImportarArchivo').style.display = 'none';
		byId('tblArticulosPedido').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		byId('tblArticuloImpuesto').style.display = 'none';
		byId('tblAlmacen').style.display = 'none';
		byId('tblListaGasto').style.display = 'none';
		byId('tblListaGastoImportacion').style.display = 'none';
		byId('tblListaOtrosCargos').style.display = 'none';
		byId('tblListaPais').style.display = 'none';
		byId('tblFacturaOtroCargo').style.display = 'none';
		byId('tblTotalFactura').style.display = 'none';
		byId('tblCliente').style.display = 'none';
		
		if (verTabla == "tblImportarArchivo") {
			if (unformatNumberRafk(byId('txtMontoTotalFactura').value) > 0) {
				xajax_formImportar();
				tituloDiv1 = 'Importar Pedido';
			} else {
				abrirDivFlotante1(nomObjeto, "tblTotalFactura", "tblImportarArchivo");
				return;
			}
		} else if (verTabla == "tblArticulosPedido") {
			if (unformatNumberRafk(byId('txtMontoTotalFactura').value) > 0) {
				xajax_formListadoArticulosPedido();
				tituloDiv1 = 'Agregar Item';
			} else {
				abrirDivFlotante1(nomObjeto, "tblTotalFactura", "tblArticulosPedido");
				return;
			}
		} else if (verTabla == "tblArticulo") {
			xajax_asignarArticulo(valor, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'));
			tituloDiv1 = 'Editar Artículo';
		} else if (verTabla == "tblArticuloImpuesto") {
			xajax_formArticuloImpuesto();
			tituloDiv1 = 'Editar Impuesto de Articulos';
		} else if (verTabla == "tblAlmacen") {
			xajax_formAlmacen(valor, xajax.getFormValues('frmListaArticulo'));
			tituloDiv1 = 'Distribuir Artículo en Almacen';
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
		} else if (verTabla == "tblTotalFactura") {
			document.forms['frmTotalFactura'].reset();
			
			byId('hddFrm').value = valor;
			
			tituloDiv1 = 'Indique el Total de la Factura Compra';
		} else if (verTabla == "tblCliente") {
			xajax_formDatosCliente(valor, xajax.getFormValues('frmDcto'));
			
			tituloDiv1 = 'Datos de Cliente';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaPais") {
			byId('txtCriterioBuscarPais').focus();
			byId('txtCriterioBuscarPais').select();
		} else if (verTabla == "tblListaGasto") {
			byId('txtCriterioBuscarGasto').focus();
			byId('txtCriterioBuscarGasto').select();
		} else if (verTabla == "tblListaGastoImportacion") {
			byId('txtCriterioBuscarGastoImportacion').focus();
			byId('txtCriterioBuscarGastoImportacion').select();
		} else if (verTabla == "tblFacturaOtroCargo") {
			byId('txtCriterioBuscarRegistroCompra').focus();
			byId('txtCriterioBuscarRegistroCompra').select();
		} else if (verTabla == "tblTotalFactura") {
			byId('txtTotalFactura').focus();
			byId('txtTotalFactura').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaCliente').style.display = 'none';
		byId('tblListaProveedor').style.display = 'none';
		byId('tblListaArtSust').style.display = 'none';
		byId('tblArticuloMultiple').style.display = 'none';
		
		if (verTabla == "tblListaCliente") {
			document.forms['frmBuscarCliente'].reset();
			
			byId('btnBuscarCliente').click();
	
			tituloDiv2 = 'Clientes';
		} else if (verTabla == "tblListaProveedor") {
			document.forms['frmBuscarProveedor'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv2 = 'Proveedores';
		} else if (verTabla == "tblListaArtSust") {
			document.forms['frmBuscarArtSust'].reset();
			
			byId('btnBuscarArtSust').click();
			
			tituloDiv2 = "Sustituir Artículo";
		} else if (verTabla == "tblArticuloMultiple") {
			xajax_formArticuloMultiple(valor);
			
			tituloDiv2 = "Agregar Item con Diferentes Costos";
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaCliente") {
			byId('txtCriterioBuscarCliente').focus();
		} else if (verTabla == "tblListaProveedor") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		} else if (verTabla == "tblListaArtSust") {
			byId('txtCriterioBuscarArtSust').focus();
			byId('txtCriterioBuscarArtSust').select();
		}
	}
	
	function buscarEnColumna(valor, buscarEnColumna) {
		var frm = document.forms['frmListaArticulo'];
		for (i = 0; i < frm.length; i++){
			if (frm.elements[i].id == "cbx") {
				indice = frm.elements[i].value;
				
				byId('trItm:' + indice).style.display = '';
				if (buscarEnColumna == 'porcentaje_grupo'
				&& byId('lstTarifaAdValorem' + indice).value != valor && valor != "") {
					byId('trItm:' + indice).style.display = 'none';
				}
			}
		}
	}
	
	function comparar(fe1,fe2) {
		dia1 = fe1.substring(0,2);
		d1 = parseInt(dia1,10);
		mes1 = fe1.substring(3,5);
		m1 = parseInt(mes1,10);
		año1 = fe1.substring(6,10);
		a1 = parseInt(año1,10);
		dia2 = fe2.substring(0,2);
		d2 = parseInt(dia2,10);
		mes2 = fe2.substring(3,5);
		m2 = parseInt(mes2,10);
		año2 = fe2.substring(6,10);
		a2 = parseInt(año2,10);
		
		if (a1 > a2)
			return 1;
		else if (a1 < a2)
			return -1;
		else
			if (m1 > m2)
				return 1;
			else if (m1 < m2)
				return -1;
			else
				if (d1 > d2)
					return 1;
				else if (d1 < d2)
					return -1;
				else
					return 0;
	}
	
	function calcularArticuloMultiple() {
		var totalCantArtMult = 0;
		var totalCantEntregadaArtMult = 0;
		
		for (cont = 1; cont <= byId('hddCantItmArticuloMultiple').value; cont++) {
			if (byId('txtCantArtMult' + cont) != null && isNaN(parseFloat(byId('txtCantArtMult' + cont).value)) == false) {
				totalCantArtMult += parseFloat(byId('txtCantArtMult' + cont).value);
			}
			if (byId('txtCantEntregadaArtMult' + cont) != null && isNaN(parseFloat(byId('txtCantEntregadaArtMult' + cont).value)) == false) {
				totalCantEntregadaArtMult += parseFloat(byId('txtCantEntregadaArtMult' + cont).value);
			}
		}
		
		byId('txtTotalCantArtMult').value = totalCantArtMult;
		byId('txtTotalCantEntregadaArtMult').value = totalCantEntregadaArtMult;
	}
	
	function calcularDescuentoArt() {
		if (byId('hddTipoDescuento').value == 0) {
			byId('txtMontoDescuentoArt').value = formatoRafk((byId('txtPorcDescuentoArt').value * byId('txtCostoArt').value) / 100,2);
		} else if (byId('hddTipoDescuento').value == 1) {
			byId('txtPorcDescuentoArt').value = formatoRafk((byId('txtMontoDescuentoArt').value * 100) / byId('txtCostoArt').value,2);
		}
	}
	
	function eliminarFilaArticuloMultiple(cont) {
		fila = byId('trItmArtMult:' + cont);
		padre = fila.parentNode;
		padre.removeChild(fila);
	}
	
	function insertarFilaArticuloMultiple(decimales) {
		contFila = byId('hddCantItmArticuloMultiple').value;
		contFila++;
		
		clase = (contFila % 2 == 0) ? "trResaltar4" : "trResaltar5";
		
		// INSERTA EL ARTICULO SIN INJECT
		$('#trItmPieArtMultiple').before(
			'<tr id=\"trItmArtMult:' + contFila + '\" align=\"center\" class=\"textoGris_11px ' + clase + '\">' +
				'<td><input type=\"text\" id=\"txtCantArtMult' + contFila + '\" name=\"txtCantArtMult' + contFila + '\" class=\"inputHabilitado\" size=\"12\" style=\"text-align:right\"/></td>' +
				'<td><input type=\"text\" id=\"txtCantEntregadaArtMult' + contFila + '\" name=\"txtCantEntregadaArtMult' + contFila + '\" class=\"inputHabilitado\" size=\"12\" style=\"text-align:right\"/></td>' +
				'<td><input type=\"text\" id=\"txtCostoArtMult' + contFila + '\" name=\"txtCostoArtMult' + contFila + '\" class=\"inputHabilitado\" style=\"text-align:right\"/></td>' +
				'<td><img class=\"puntero\" id=\"imgEliminarArtMult' + contFila + '\" name=\"imgEliminarArtMult' + contFila + '\" onclick=\"eliminarFilaArticuloMultiple(' + contFila + '); calcularArticuloMultiple();\" src=\"../img/iconos/cross.png\"/></td>' +
			'</tr>');
		
		byId('txtCantArtMult' + contFila).onkeyup = function(e){ calcularArticuloMultiple(); }
		byId('txtCantEntregadaArtMult' + contFila).onkeyup = function(e){ calcularArticuloMultiple(); }
		
		if (navigator.appName == 'Netscape') {
			byId('txtCantArtMult' + contFila).onkeypress = function(e){
				if (decimales == 0) { return validarSoloNumeros(e); } else { return validarSoloNumerosReales(e); }
			}
			byId('txtCantArtMult' + contFila).onblur = function(e){
				if (decimales == 0) { setFormatoRafk(this,0); } else { setFormatoRafk(this,2); }
			}
			byId('txtCantEntregadaArtMult' + contFila).onkeypress = function(e){
				if (decimales == 0) { return validarSoloNumeros(e); } else { return validarSoloNumerosReales(e); }
			}
			byId('txtCantEntregadaArtMult' + contFila).onblur = function(e){
				if (decimales == 0) { setFormatoRafk(this,0); } else { setFormatoRafk(this,2); }
			}
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantArtMult' + contFila).onkeypress = function(e){
				if (decimales == 0) { return validarSoloNumeros(event); } else { return validarSoloNumeros(event); }
			}
			byId('txtCantArtMult' + contFila).onblur = function(e){
				if (decimales == 0) { setFormatoRafk(this,0); } else { setFormatoRafk(this,2); }
			}
			byId('txtCantEntregadaArtMult' + contFila).onkeypress = function(e){
				if (decimales == 0) { return validarSoloNumeros(event); } else { return validarSoloNumeros(event); }
			}
			byId('txtCantEntregadaArtMult' + contFila).onblur = function(e){
				if (decimales == 0) { setFormatoRafk(this,0); } else { setFormatoRafk(this,2); }
			}
		}
		
		byId('hddCantItmArticuloMultiple').value = contFila;
	}
	
	function seleccionarCondicionGasto(idCondicionGasto) {
		byId('fieldsetDatosFactura').style.display = 'none';
		byId('fieldsetListaRegistroCompra').style.display = 'none';
		
		if (idCondicionGasto == 1 && byId('lstAsociaDocumento').value == 1) { // 1 = Real && 1 = Si
			byId('fieldsetListaRegistroCompra').style.display = '';
			
			byId('txtSubTotalFacturaGasto').className = 'inputSinFondo';
			byId('txtSubTotalFacturaGasto').readOnly = true;
		} else if (idCondicionGasto == 2 || byId('lstAsociaDocumento').value == 0) { // 2 = Estimado || 0 = No
			byId('fieldsetDatosFactura').style.display = '';
			
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
	
	function seleccionarTarifaAdValorem(accion, indice) {
		if (accion == 'agregar') {
			byId('trlstTarifaAdValoremDif' + indice).style.display = '';
			byId('aAgregarTarifaAdValorem' + indice).style.display = 'none';
		} else if (accion == 'quitar') {
			byId('trlstTarifaAdValoremDif' + indice).style.display = 'none';
			byId('aAgregarTarifaAdValorem' + indice).style.display = '';
			selectedOption('lstTarifaAdValoremDif' + indice, 0);
			
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
	}
	
	function validarAsignarADV() {
		if (confirm('Desea asignarle el % ADV seleccionado a todos los items?')) {
			xajax_asignarADV(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'));
		}
	}
	
	function validarFrmAlmacen() {
		if (validarCampo('txtCodigoArticulo','t','') == true
		&& validarCampo('txtArticulo','t','') == true
		&& validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('lstAlmacenAct','t','lista') == true
		&& validarCampo('lstCalleAct','t','lista') == true
		&& validarCampo('lstEstanteAct','t','lista') == true
		&& validarCampo('lstTramoAct','t','lista') == true
		&& validarCampo('lstCasillaAct','t','lista') == true) {
			if (confirm('Desea realizar la distribución del Artículo a este Almacen?')) {
				xajax_asignarAlmacen(xajax.getFormValues('frmAlmacen'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			validarCampo('txtCodigoArticulo','t','');
			validarCampo('txtArticulo','t','');
			validarCampo('lstEmpresa','t','lista');
			validarCampo('lstAlmacenAct','t','lista');
			validarCampo('lstCalleAct','t','lista');
			validarCampo('lstEstanteAct','t','lista');
			validarCampo('lstTramoAct','t','lista');
			validarCampo('lstCasillaAct','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadRecibArt','t','') == true
		&& validarCampo('txtCostoArt','t','') == true
		&& validarCampo('lstIvaArt','t','listaExceptCero') == true) {
			if (byId('rbtTipoArtCliente').checked == true
			&& validarCampo('txtIdClienteArt','t','') != true
			&& validarCampo('txtNombreClienteArt','t','') != true) {
				alert("Los campos señalados en rojo son requeridos");
				return false;
			} else if (parseInt(byId('txtCantidadRecibArt').value) > parseInt(byId('txtCantidadArt').value)) {
				alert("La cantidad recibida no puede ser mayor a la pedida");
				return false;
			} else
				xajax_editarArticulo(xajax.getFormValues('frmArticulo'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadRecibArt','t','');
			validarCampo('txtCostoArt','t','');
			validarCampo('lstIvaArt','t','listaExceptCero');
			
			if (byId('rbtTipoArtCliente').checked == true) {
				validarCampo('txtIdClienteArt','t','');
				validarCampo('txtNombreClienteArt','t','');
			}
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmArticuloImpuesto() {
		if (validarCampo('lstIvaCbx','t','listaExceptCero') == true) {
			xajax_asignarArticuloImpuesto(xajax.getFormValues('frmArticuloImpuesto'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('lstIvaCbx','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmArticuloMultiple() {
		if (validarCampo('txtTotalCantArtMult','t','monto') == true
		&& validarCampo('txtTotalCantEntregadaArtMult','t','monto') == true) {
			xajax_insertarArticuloMult(xajax.getFormValues('frmArticuloMultiple'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtTotalCantArtMult','t','monto');
			validarCampo('txtTotalCantEntregadaArtMult','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmTotalFactura() {
		if (validarCampo('txtTotalFactura','t','monto') == true) {
			if (byId('hddFrm').value == 'tblArticulosPedido') {
				byId('txtMontoTotalFactura').value = formatoRafk(byId('txtTotalFactura').value,2);
				byId('btnCancelarTotalFactura').click();
				byId('aAgregarArticulo').click();
			} else if (byId('hddFrm').value == 'tblImportarArchivo') {
				byId('txtMontoTotalFactura').value = formatoRafk(byId('txtTotalFactura').value,2);
				byId('btnCancelarImportarPedido').click();
				byId('aImportar').click();
			}
		} else {
			validarCampo('txtTotalFactura','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmImportarPedido() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarDcto(xajax.getFormValues('frmImportarArchivo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
					if (!(validarCampo('hddSubTotalFacturaGastoCargo' + arrayObj[i],'t','monto') == true)) {
						validarCampo('hddSubTotalFacturaGastoCargo' + arrayObj[i],'t','monto');
						
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
				if (confirm('¿Seguro desea Registrar la Compra?') == true) {
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
					if (!(validarCampo('hddSubTotalFacturaGastoCargo' + arrayObj[i],'t','monto') == true)) {
						validarCampo('hddSubTotalFacturaGastoCargo' + arrayObj[i],'t','monto');
						
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
				if (confirm('¿Seguro Desea Registrar La Compra?') == true) {
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
	
	function validarInsertarArticulo(idPedidoCompraDetalle, cantPendiente) {
		if (parseFloat(cantPendiente) > 1) {
			if (confirm('¿El Item tiene diferentes Costos dentro de la Misma Factura?') == true) {
				byId('aAgregarFormArtMult').onclick = function(e) { abrirDivFlotante2(this, 'tblArticuloMultiple', idPedidoCompraDetalle); }
				byId('aAgregarFormArtMult').click();
			} else {
				xajax_insertarArticulo(idPedidoCompraDetalle, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			xajax_insertarArticulo(idPedidoCompraDetalle, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
	}
	
	function validarInsertarArticuloFacturaGasto(idArticulo) {
		xajax_insertarArticuloFacturaGasto(idArticulo, xajax.getFormValues('frmFacturaGasto'));
	}
	
	function validarInsertarGasto(idGasto) {
		xajax_insertarGasto(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	
	function validarInsertarGastoImportacion(idGasto) {
		xajax_insertarGastoImportacion(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	
	function validarInsertarOtroCargo(idGasto) {
		xajax_insertarOtroCargo(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Registro de Compra</td>
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
                    <td></td>
                    <td></td>
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
                        <select id="lstTipoClave" name="lstTipoClave" onchange="selectedOption(this.id,1); xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', this.value, '', '1');">
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
                    <a class="modalImg" id="aAgregarArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticulosPedido');">
                    	<button type="button" title="Agregar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                        <button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
					<a class="modalImg" id="aImpuestoArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticuloImpuesto');">
                    	<button type="button" title="Impuesto Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/text_signature.png"/></td><td>&nbsp;</td><td>Impuesto</td></tr></table></button>
                    </a>
                        <button type="button" id="btnExportar" name="btnExportar" onclick="xajax_exportarRegistroCompra(xajax.getFormValues('frmDcto'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					<a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarArchivo');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
					</td>
				</tr>
                </table>
                
                <table align="right" cellpadding="0" cellspacing="0" class="divMsjInfo2" width="400">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td class="trResaltar6" style="border:1px solid #000000">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Artículo Sin Ubicación</td>
                            <td>&nbsp;</td>
                            <td class="trResaltar7" style="border:1px solid #000000">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Artículo Con Multiple Ubicación</td>
                        </tr>
                        </table>
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
                	<td rowspan="2"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked, this.id, 1);"/></td>
                    <td rowspan="2" width="4%">Nro.</td>
                	<td rowspan="2"></td>
                	<td rowspan="2"></td>
                    <td rowspan="2" width="10%">Ubic.</td>
                    <td rowspan="2" width="10%">Código</td>
                    <td rowspan="2" width="20%">Descripción</td>
                    <td rowspan="2" width="4%">Ped.</td>
                    <td rowspan="2" width="4%">Recib.</td>
                    <td rowspan="2" width="4%">Pend.</td>
                    <td rowspan="2" width="6%">Nro. Ref.</td>
                    <td rowspan="2" width="8%">Costo Unit.</td>
                    <td rowspan="2" width="4%">% Impuesto</td>
                    <td width="4%">% ADV</td>
                    <td rowspan="2" width="6%">Peso Unit. (g)</td>
                    <td rowspan="2" width="8%">Gasto</td>
                    <td rowspan="2" width="8%">Total</td>
                </tr>
                <tr align="center" class="tituloColumna">
                    <td id="tdlstArancelGrupoBuscar"></td>
                </tr>
                <tr id="trItmPie">
                	<td colspan="14"></td>
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
                        <tr id="trAgregarGasto" align="left">
                        	<td colspan="7">
                                <a class="modalImg" id="aAgregarGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaGasto');">
                                    <button type="button" title="Agregar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarGasto" name="btnQuitarGasto" onclick="xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));" title="Quitar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trGastoItem" align="left" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmGasto" onclick="selecAllChecks(this.checked, this.id, 2);"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gasto Manual por Item:</td>
                            <td colspan="5">
                            <div style="float:left">
                            	<select id="lstGastoItem" name="lstGastoItem" onchange="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
                                	<option value="-1">[ Seleccione ]</option>
                                	<option value="0">No</option>
                                	<option value="1">Si</option>
                                </select>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="No: Realiza el prorrateo automático entre los items agregados &#10;Si: Permitirá ingresar manualmente el gasto correspondiente a cada item agregado y los totales de los gastos deberán coincidir"/>
                            </div>
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
                                
                            	<input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td>
                            	<input type="radio" id="rbtInicialMonto" name="rbtInicial" checked="checked" onclick="byId('txtDescuento').readOnly = true; byId('txtSubTotalDescuento').readOnly = false;" style="display:none" value="2">
                                
                            	<input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/>
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
                                <li id="liBasicos"><a href="#">Básicos</a></li>
                                <li id="liRegistro"><a href="#">Registro</a></li>
                                <li id="liOtrosCargos"><a href="#">Otros Cargos</a></li>
                                <li id="liCodigosArancelarios"><a href="#">Códigos Arancelarios</a></li>
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
    
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarArchivo" name="frmImportarArchivo" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarArchivo" width="960">
    <tr align="left">
    	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td width="85%">
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript:submit();" size="100"/>
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
                        <td colspan="14">El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr align="center" class="tituloColumna">
                        <td width="7%">Nro. Referencia</td>
                        <td width="7%">Código</td>
                        <td width="9%">Descripción</td>
                        <td width="7%">Código Arancelario</td>
                        <td width="7%">% Arancelario</td>
                        <td width="7%">Ped.</td>
                        <td width="7%">Recib.</td>
                        <td width="7%">Pend.</td>
                        <td width="7%">Costo Unit.</td>
                        <td width="7%">% Impuesto</td>
                        <td width="7%">Total</td>
                        <td width="7%">Almacén</td>
                        <td width="7%">Ubicación</td>
                        <td width="7%">Id Cliente</td>
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

    <table border="0" id="tblArticulosPedido" style="display:none" width="960">
    <tr>
    	<td>	
        <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
        	<table>
            <tr>
                <td align="right" class="tituloCampo" width="120">Nro. Referencia:</td>
                <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPedido(xajax.getFormValues('frmBuscar'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                </td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
    <tr id="trListaPedidoCompra">
    	<td><div id="divListaPedidoCompra" style="width:100%"></div></td>
    </tr>
    <tr id="trArticulosPedido">
    	<td>
        <a class="modalImg" id="aAgregarFormArtMult" rel="#divFlotante2" style="display:none"></a>
        <fieldset><legend class="legend">Artículos del Pedido (<b>Nro. Referencia <span id="spanTituloPedido"></span></b>)</legend>
        	<div id="divArticulosPedido" style="width:100%"></div>
		</fieldset>
        </td>
	</tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarArticulosPedido" name="btnCancelarArticulosPedido" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
<form id="frmArticulo" name="frmArticulo" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
    <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
    <input type="hidden" id="hddIdArticuloSust" name="hddIdArticuloSust" readonly="readonly"/>
    <table border="0" id="tblArticulo" style="display:none" width="960">
    <tr>
    	<td>
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
                <td>
                	<table>
                    <tr>
                    	<td><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/></td>
                        <td>
                        <a class="modalImg" id="aCambiarArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaArtSust');">
                           <button type="button" title="Sustituir Artículo"><img src="../img/iconos/ico_cambio.png"/></button>
                        </a>
                        </td>
                        <td><button type="button" id="btnDeshacerArt" name="btnDeshacerArt" onclick="xajax_asignarArticuloSustituto(byId('hddIdArticulo').value, false, 'false');" title="Deshacer Artículo Sustituto"><img src="../img/iconos/ico_return.png"/></button></td>
					</tr>
                    </table>
                </td>
                <td rowspan="3" valign="top"><textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="50" rows="3" readonly="readonly"></textarea></td>
                <td align="right" class="tituloCampo">Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="38"/></td>
                <td align="right" class="tituloCampo">Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                <td align="right" class="tituloCampo">Unid. Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
            </tr>
			</table>
		</fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="13%"></td>
                <td width="13%"></td>
                <td width="10%"></td>
                <td width="16%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Cantidad Pedida:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" readonly="readonly" size="10" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                    </tr>
					</table>
                </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad Recibida:</td>
                <td colspan="4"><input type="text" id="txtCantidadRecibArt" name="txtCantidadRecibArt" onblur="setFormatoRafk(this,2);" onkeyup="calcularDescuentoArt();" size="10" style="text-align:right"/></td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                <td><input type="text" id="txtCostoArt" name="txtCostoArt" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularDescuentoArt();" size="10" style="text-align:right"/></td>
                <td align="right" class="tituloCampo">
                	Descuento:
                    <input type="hidden" id="hddTipoDescuento" name="hddTipoDescuento">
                </td>
                <td>
                	<input type="radio" id="rbtPorcDescuentoArt" name="rbtDescuento" onclick="
                    byId('hddTipoDescuento').value = 0;
                    byId('txtPorcDescuentoArt').readOnly = false;
                    byId('txtPorcDescuentoArt').className = 'inputHabilitado';
                    byId('txtMontoDescuentoArt').readOnly = true;
                    byId('txtMontoDescuentoArt').className = 'inputInicial';" value="0"/>
                	
                	<input type="text" id="txtPorcDescuentoArt" name="txtPorcDescuentoArt" onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularDescuentoArt();" size="6" style="text-align:right"/>%
				</td>
				<td>
                	<input type="radio" id="rbtMontoDescuentoArt" name="rbtDescuento" onclick="
                    byId('hddTipoDescuento').value = 1;
                    byId('txtPorcDescuentoArt').readOnly = true;
                    byId('txtPorcDescuentoArt').className = 'inputInicial';
                    byId('txtMontoDescuentoArt').readOnly = false;
                    byId('txtMontoDescuentoArt').className = 'inputHabilitado';" value="1"/>
                	
                	<input type="text" id="txtMontoDescuentoArt" name="txtMontoDescuentoArt" onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularDescuentoArt();" size="10" style="text-align:right"/>
				</td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaArt">
                	<select id="lstIvaArt" name="lstIvaArt">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Tipo:</td>
                <td>
                	<label><input type="radio" id="rbtTipoArtReposicion" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('aInsertarClienteArt').style.display = 'none';" value="0" checked="checked"/> Reposición</label>
                    &nbsp;&nbsp;
                    <label><input type="radio" id="rbtTipoArtCliente" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('aInsertarClienteArt').style.display = '';" value="1" /> Cliente</label>
				</td>
                <td align="right" class="tituloCampo">Nombre:</td>
                <td colspan="4">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdClienteArt" name="txtIdClienteArt" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aInsertarClienteArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaCliente');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNombreClienteArt" name="txtNombreClienteArt" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
				</td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFrmArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>

<form id="frmArticuloImpuesto" name="frmArticuloImpuesto" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblArticuloImpuesto" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaCbx" width="70%">
                	<select id="lstIvaCbx" name="lstIvaCbx">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
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
                        <td>Para seleccionar multiples impuestos se debe presionar la tecla Ctrl</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticuloImpuesto" name="btnGuardarArticuloImpuesto" onclick="validarFrmArticuloImpuesto();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloImpuesto" name="btnCancelarArticuloImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>

<form id="frmAlmacen" name="frmAlmacen" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAlmacen" style="display:none" width="760">
    <tr>
    	<td>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Código:</td>
                <td width="84%">
                    <input type="hidden" id="hddNumeroArt2" name="hddNumeroArt2" readonly="readonly"/>
                    <input type="text" id="txtCodigoArticulo" name="txtCodigoArticulo" size="30" readonly="readonly">
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Artículo:</td>
                <td><textarea id="txtArticulo" name="txtArticulo" cols="60" rows="3" readonly="readonly"></textarea></td>
            </tr>
            <tr align="left" style="display:none">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresa">
                    <select id="lstEmpresa" name="lstEmpresa">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ubicación</td>
                <td>
                    <table width="100%">
                    <tr align="center">
                        <td class="tituloCampo">Almacen</td>
                        <td class="tituloCampo">Calle</td>
                        <td class="tituloCampo">Estante</td>
                        <td class="tituloCampo">Tramo</td>
                        <td class="tituloCampo">Casilla</td>
                    </tr>
                    <tr>
                        <td id="tdlstAlmacenAct">
                            <select id="lstAlmacenAct" name="lstAlmacenAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                        <td id="tdlstCalleAct">
                            <select id="lstCalleAct" name="lstCalleAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                        <td id="tdlstEstanteAct">
                            <select id="lstEstanteAct" name="lstEstanteAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                        <td id="tdlstTramoAct">
                            <select id="lstTramoAct" name="lstTramoAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                        <td id="tdlstCasillaAct">
                            <select id="lstCasillaAct" name="lstCasillaAct">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="5" id="tdOtrasUbic"></td>
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
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad a Distribuir:</td>
                <td><input type="text" id="txtCantidadDisponible" name="txtCantidadDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarAlmacen" name="btnGuardarAlmacen" onclick="validarFrmAlmacen();">Aceptar</button>
            <button type="button" id="btnCancelarAlmacen" name="btnCancelarAlmacen" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
	
    <table border="0" id="tblListaGasto" width="760">
    <tr>
        <td>
        <form id="frmBuscarGasto" name="frmBuscarGasto" style="margin:0" onsubmit="return false;">
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
        <form id="frmListaGasto" name="frmListaGasto" style="margin:0" onsubmit="return false;">
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
        <form id="frmBuscarGastoImportacion" name="frmBuscarGastoImportacion" style="margin:0" onsubmit="return false;">
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
        <form id="frmListaGastoImportacion" name="frmListaGastoImportacion" style="margin:0" onsubmit="return false;">
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
   
    <div id="tblFacturaOtroCargo" style="max-height:520px; overflow:auto; width:960px">
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
    
<form id="frmTotalFactura" name="frmTotalFactura" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblTotalFactura" style="display:none" width="360">
    <tr>
    	<td>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Total Factura Compra:</td>
                <td width="55%"><input type="text" id="txtTotalFactura" name="txtTotalFactura" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right" size="16"></td>
            </tr>
            </table>
        </td>
	</tr>
    <tr>
    	<td align="right"><hr>
			<input type="hidden" id="hddFrm" name="hddFrm" readonly="readonly"/>
            <button type="submit" id="btnGuardarTotalFactura" name="btnGuardarTotalFactura" onclick="validarFrmTotalFactura();">Aceptar</button>
            <button type="button" id="btnCancelarTotalFactura" name="btnCancelarTotalFactura" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblCliente" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                <td width="55%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" width="15%"><?php echo $spanClienteCxC; ?>:</td>
                <td width="15%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                <td rowspan="2"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea></td>
                <td align="right" class="tituloCampo">Teléfono:</td>
                <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Días Crédito:</td>
                <td><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
			<button type="button" id="btnCancelarCliente" name="btnCancelarCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
  	
    <table border="0" id="tblListaCliente" width="760">
    <tr>
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" class="inputHabilitado" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaCliente" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaCliente" name="btnCancelarListaCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaProveedor" width="760">
    <tr>
    	<td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
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
        <td><div id="divListaProveedor" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaProveedor" name="btnCancelarListaProveedor" class="close">Cerrar</button>
        </td>
    </tr>
    </table>

	<table border="0" id="tblListaArtSust" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArtSust" name="frmBuscarArtSust" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td>
                    <input type="text" id="txtCriterioBuscarArtSust" name="txtCriterioBuscarArtSust" class="inputHabilitado" onkeyup="byId('btnBuscarArtSust').click();"/>
                </td>
                <td>
                	<button type="submit" id="btnBuscarArtSust" name="btnBuscarArtSust" onclick="xajax_buscarArticuloSustituto(xajax.getFormValues('frmBuscarArtSust'), xajax.getFormValues('frmArticulo'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArtSust'].reset(); byId('btnBuscarArtSust').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmArtSust" name="frmArtSust" style="margin:0">
        	<div id="divListaArtSust" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarArtSust" name="btnCancelarArtSust" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
<form id="frmArticuloMultiple" name="frmArticuloMultiple" onsubmit="return false;" style="margin:0">
	<input type="hidden" id="hddIdPedidoCompraDetalle" name="hddIdPedidoCompraDetalle" />
    <table border="0" id="tblArticuloMultiple" width="460">
    <tr align="left">
    	<td align="right" class="tituloCampo" width="25%">Código:</td>
    	<td width="75%"><input type="text" id="txtCodigoArtMultiple" name="txtCodigoArtMultiple" readonly="readonly" size="25"/></td>
    </tr>
    <tr align="left">
    	<td align="right" class="tituloCampo">Descripción:</td>
    	<td><textarea id="txtDescripcionArtMultiple" name="txtDescripcionArtMultiple" cols="50" rows="3" readonly="readonly"></textarea></td>
    </tr>
    <tr align="left">
    	<td align="right" class="tituloCampo">Cantidad Pedida:</td>
    	<td>
        	<table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="txtCantidadArtMultiple" name="txtCantidadArtMultiple" maxlength="6" onkeypress="return validarSoloNumeros(event);" readonly="readonly" size="12" style="text-align:right"/></td>
                <td>&nbsp;</td>
                <td><input type="text" id="txtUnidadArtMultiple" name="txtUnidadArtMultiple" readonly="readonly" size="15"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td colspan="2">
        	<table border="0" width="100%">
            <tr align="center" class="tituloColumna">
            	<td width="30%">Cantidad</td>
            	<td width="30%">Recibidos</td>
            	<td width="40%">Costo</td>
            	<td>
                	<a id="aAgregarArtMult"><img class='puntero' src="../img/iconos/add.png" title='Agregar'/></a>
            		<input type="hidden" id="hddCantItmArticuloMultiple" name="hddCantItmArticuloMultiple"/>
				</td>
            </tr>
            <tr align="center" id="trItmPieArtMultiple" class="trResaltarTotal">
            	<td><input type="text" id="txtTotalCantArtMult" name="txtTotalCantArtMult" class="inputSinFondo" readonly="readonly" size="12" style="text-align:right"/></td>
            	<td><input type="text" id="txtTotalCantEntregadaArtMult" name="txtTotalCantEntregadaArtMult" class="inputSinFondo" readonly="readonly" size="12" style="text-align:right"/></td>
                <td></td>
                <td></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td colspan="2">
        	<table align="right" cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    La Cantidad Total de ser igual a la Cantidad Pedida
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
            <button type="submit" id="btnGuardarArticuloMultiple" name="btnGuardarArticuloMultiple" onclick="validarFrmArticuloMultiple();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloMultiple" name="btnCancelarArticuloMultiple" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
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
		dateFormat:"<?php echo spanDatePick; ?>"/*,
		selectedDate:{				This is an example of what the full configuration offers.
			day:5,						For full documentation about these settings please see the full version of the code.
			month:9,
			year:2006
		},
		yearsRange:[1978,2020],
		limitToToday:false,
		cellColorScheme:"beige",
		imgPath:"img/"
		dateFormat:"%m-%d-%Y",
		weekStartDay:1*/
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDctoTransporte",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaVencDctoTransporte",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaEstimadaLlegada",
		dateFormat:"<?php echo spanDatePick; ?>"
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

xajax_cargaLstArancelGrupoBuscar();
xajax_cargaLstRetencionISLR();
xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>