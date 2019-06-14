<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_registro_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_registro_compra_form.php");

//MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
//MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Registro de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>

   	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante(accion, nomObjeto){
		
		switch(accion){
			case "agregar": 
				if(validarencabezadoOrden() == true){
					if(validarTotales() == true){
						nomObjeto = "";
						byId('txtTotalFactura').value = "";
						byId('tdFlotanteTitulo8').innerHTML = "Indique el Total de la Factura de Compra";
						nomObjeto = divFlotante8;
						var a = "txtTotalFactura";
					}else{
						xajax_cargaLstTipoArt();
						xajax_listadoArticulos(0,'','',byId('txtIdEmpresa').value);
						var a = "textCodigoArtBus";
					}
				}else{
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			break;					
			case "editar": 
			//tomar los id del impuesto y pasarcelo a la funcion para que genere el listado de impuesto
				byId('tdFlotanteTitulo').innerHTML = "Editar Articulo";
				byId('hddTextAccion').value = "editarArt";
				if ($('#trIva').is(':visible')){
					byId('trIva').style.display = 'none';
				}
				byId('txtCantidadArt').readOnly = true; 
				byId('txtCantidadArt').className = 'inputInicial'; 
				xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'));
				var a = "txtCantidadArt";
			break;
			case "MostrarImpuesto": 
				document.forms['frmLstImpuesto'].reset();
				byId('tdFlotanteTitulo5').innerHTML = "Lista de Impuesto";
				byId('btsAceptarImpuestoPorBLoque').style.display = 'none';
				/*byId('btsAceptarImpuesto').style.display = '';*/
				xajax_listImpuesto(0,'iva','ASC','impuestoItems');
				if ($('#divFlotante4').is(':visible')){
					byId('btnListArt').click();
				}
			break;
			case "MostrarImpuestoPorBLoque":
				document.forms['frmLstImpuesto'].reset();
				byId('tdFlotanteTitulo5').innerHTML = "Lista de Impuesto";
				xajax_listImpuesto(0,'iva','ASC','impuestoBloque');
				byId('btsAceptarImpuestoPorBLoque').style.display = '';
			break;
			case "MostrarGastos":
				document.forms['frmLstGasto'].reset();
				byId('btnLimpiarGastos').click();
				byId('tdFlotanteTitulo6').innerHTML = "Lista de Gastos";
				var a = "txtBuscarCriterio";
			break;					
			case "Mostrarsolicitud":
				if(validarTotales() == true){
					nomObjeto = "";
					byId('txtTotalFactura').value = "";
					byId('tdFlotanteTitulo8').innerHTML = "Indique el Total de la Factura de Compra";
					nomObjeto = divFlotante8;
				}else{
					byId('tdFlotanteTitulo7').innerHTML = "Buscar Solicitud";
				}	
			break;
			case "totalFactura":
				byId('txtTotalFactura').value = "";
				byId('tdFlotanteTitulo8').innerHTML = "Indique el Total de la Factura de Compra";
				nomObjeto = divFlotante8;
			break;
			case "unidad":
				document.forms['frmBuscarUnidadFisica'].reset();
				document.forms['frmDatosUnidadFisica'].reset();				
				byId('btnBuscarUnidadFisica').click();
				
				byId('txtCostoUnitarioUnidadFisica').className = 'inputHabilitado';
				byId('lstTipoFactura').className = 'inputHabilitado';
				byId('txtIdUnidadFisica').className = 'inputCompleto';
				
				byId('trServicioMantenimiento').style.display = 'none';
				byId('txtDescripcionServicioMantenimiento').className = 'inputInicial';		
				byId('hddIdServicioMantenimiento').value = '';
				
				var a = "txtCriterioBuscarUnidadFisica";
			break;
			case "servicioMantenimiento":
				document.forms['frmBuscarServicioMantenimiento'].reset();				
				byId('btnBuscarServicioMantenimiento').click();
				
				var a = "txtCriterioBuscarServicioMantenimiento";
			break;			
			default:
				xajax_listProveedores(0,'','','');
				var a = "textCriterioProveed";
			break;
			}
		openImg(nomObjeto);
		if(a != null){
			byId(a).focus();
			byId(a).select();
		}
	}

	function calcularArtDescuento(){
		var string =",";
		var costoArt = byId('txtCostoArt').value.replace(string,'');
		var cantidadArt = byId('txtCantidadArt').value.replace(string,'');;
		
		var a = byId('rbtPorcDescuentoArt').checked;
		if(a == true){ // si coloca el %
			var total =	(costoArt * cantidadArt);
			var totalDeDesc = total * (byId('txtPorcDescuentoArt').value / 100);
				byId('txtMontoDescuentoArt').value = parseFloat(totalDeDesc).toFixed(2);
		} else { // si coloca el monto
			var total =	(costoArt * cantidadArt);
			var porcentaje = (byId('txtMontoDescuentoArt').value * 100) / total;
				byId('txtPorcDescuentoArt').value = parseFloat(porcentaje).toFixed(2);
				
		}	
	}
	
	function cargaDatosArt(idArt, accion, idObj){
		
		openImg(idObj); 
		xajax_asignarArticulo(null,false,accion,idArt);
	
		if(accion == 'AgregarListArt'){
			byId('txtCantidadArt').readOnly = false; 
			byId('txtCantidadArt').className = 'inputHabilitado';
		}
		
	}
	
	function eliminarGastos(Item){
		if(Item == "Iva"){
			if (confirm('¿Seguro Desea eliminar este Item?') == true){
				switch(Item){ 
					case "Iva": xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'),1); break;
				}
			}else{
				fila = document.getElementById('trItemArtIva:0');
				padre = fila.parentNode;
				padre.removeChild(fila);		
			}
		}
	}
	
	function eliminarUnidadFisica(){
		$('#cbxItmUnidad:enabled:checked').each(function(){//checkbox con dicha clase que esten habilitados y checados
			if(this.value > 0){
				if(byId('hddIdDetUnidadFisicaEliminar').value == ""){
					byId('hddIdDetUnidadFisicaEliminar').value = this.value;
				}else{
					byId('hddIdDetUnidadFisicaEliminar').value = byId('hddIdDetUnidadFisicaEliminar').value+ ',' +this.value;
				}
			}
			$(this).parent().parent().remove();//tr
		});
	}
	
	function habilitar(idObj, nameObj, accion){
		switch(nameObj){
			case "rbtTipoArt": //radio Tipo
				if(idObj == "rbtTipoArtReposicion"){
					byId('txtIdClienteArt').value = ''; 
					byId('txtNombreClienteArt').value = ''; 
					byId('txtIdClienteArt').disabled = true;
					byId('txtNombreClienteArt').disabled = true;
					byId('ButtInsertClienteArt').style.display = 'none';
				} else {
					byId('txtIdClienteArt').value = '';
					byId('txtNombreClienteArt').value = ''; 
					byId('txtIdClienteArt').disabled = '';
					byId('txtNombreClienteArt').disabled = ''; 
					byId('ButtInsertClienteArt').style.display = '';
					xajax_listaCliente(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
				}
					break;
			case "rbtDescuento": //radio descuento
				if(idObj == "rbtPorcDescuentoArt"){
					byId('txtPorcDescuentoArt').readOnly = false; 
					byId('txtPorcDescuentoArt').className = 'inputHabilitado'; 
					byId('txtMontoDescuentoArt').readOnly = true; 
					byId('txtMontoDescuentoArt').className = 'inputInicial'; 
						if (byId('txtPorcDescuentoArt').value != 0) {
							byId('txtMontoDescuentoArt').value = '0.00';
							byId('txtPorcDescuentoArt').value = '0.00'; 
						}
				}else {
					byId('txtMontoDescuentoArt').readOnly = false;
					byId('txtMontoDescuentoArt').className = 'inputHabilitado';
					byId('txtPorcDescuentoArt').readOnly = true; 
					byId('txtPorcDescuentoArt').className = 'inputInicial';
						if (byId('txtMontoDescuentoArt').value != 0) {
							byId('txtPorcDescuentoArt').value = '0.00'; 
							byId('txtMontoDescuentoArt').value = '0.00'; 
						}
				}
					break;
			case "btnAgregarProv": //para el boton de proveedores
				if(accion == "hide"){
					byId('btnAgregarProv').style.display = 'none';
					byId('btnAgregarProv').disabled = true;
					byId('txtIdProv').className = 'inputInicial';
					byId('txtIdProv').readOnly = true;
				} else{
					byId('btnAgregarProv').style.display = 'block';
					byId('btnAgregarProv').disabled = false;
					byId('txtIdProv').className = 'inputHabilitado';
					byId('txtIdProv').readOnly = false;
				}
					break;
			case "btnAgregarArt": 
				if(accion == "hide"){
					byId('btnAgregarArt').style.display = 'none';
					byId('btnAgregarArt').disabled = true;
				} else{
					byId('btnAgregarArt').style.display = 'block';
					byId('btnAgregarArt').disabled = false;
				}
					break;
			case "mostrarOrden": 
				if(accion == "hide"){
					if ($('#tdListOrdenes').is(':visible')){
						byId('tdListOrdenes').style.display = 'none';
					}
					if ($('#tdListItemsOrden').is(':visible')){
						byId('tdListItemsOrden').style.display = 'none';
					}	
					document.forms['frmBuscarOrden'].reset();				
				}else{
					byId(idObj).style.display = '';
				}
					break;
		}
	}
	
	function mostrarServicioMantenimiento(idLstTipoFactura){
		byId('txtDescripcionServicioMantenimiento').className = 'inputInicial';
		byId('txtDescripcionServicioMantenimiento').value = '';
		byId('hddIdServicioMantenimiento').value = '';
		
		if(idLstTipoFactura == 1){ 
			byId('trServicioMantenimiento').style.display = '';
		} else { 
			byId('trServicioMantenimiento').style.display = 'none'; 
		}
	}

	function seleccionarTodosCheckbox(idObj,clase){
		if ($('#'+idObj).get(0).checked == true){
			$('.'+clase).each(function() { 
                this.checked = true;    
            });
		} else {
			$('.'+clase).each(function() { 
                this.checked = false;    
            });
		}
	}
	
	function RecorrerForm(nameFrm,accion,arrayBtn){ 
		var frm = document.getElementById(nameFrm);
		var sAux= "";
		for (i=0; i < frm.elements.length; i++)	{// RECORRE LOS ELEMENTOS DEL FROM
			if(frm.elements[i].type == 'button' || frm.elements[i].type == 'submit'){// SI SON DE TIPO BUTTON Y SUBMIT 
				sAux = frm.elements[i].id;
				if(arrayBtn != "" && arrayBtn != null){// PARA LOS BOTONOES QUE NO DEBE HACER NINGUNA ACCION
					for(a = 0; a < arrayBtn.length; a++){
						if(sAux != arrayBtn[a]){
							document.getElementById(sAux).disabled = accion; //ACCION = TRUE DESAHABILITA; ACCION = FALSE HABILITA; 
						}
					}
				}else{
					document.getElementById(sAux).disabled = accion; //ACCION = TRUE DESAHABILITA; ACCION = FALSE HABILITA;
				}
			}
		}	
	}
	
	/*falta validar los art ingresados a la solicitud*/
	function validarFormArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true && validarCampo('txtCantidadRecibArt','t','cantidad') == true
		&& validarCampo('txtCostoArt','t','monto') == true && validarCampo('txtCantidadArt','t','') == true) {
			
			if (byId('rbtTipoArtCliente').checked == true && validarCampo('txtNombreClienteArt','t','') != true
				&& validarCampo('txtIdClienteArt','t','') != true ){
				
				RecorrerForm('frmDatosArticulo',false);
				alert("Los campos señalados en rojo son requeridos");
				return false;
				
			} else if (parseInt(byId('txtCantidadRecibArt').value) > parseInt(byId('txtCantidadArt').value)) {
				
				
				alert("La cantidad recibida no puede ser mayor a la pedida");
				RecorrerForm('frmDatosArticulo',false);
				return false;
			} else {
				var accion = byId('hddTextAccion').value;

				 switch(accion){ //
					case "AgregarListArt": 
					RecorrerForm('frmDatosArticulo',false);
					xajax_validarArt(xajax.getFormValues('frmListaArticulo'),xajax.getFormValues('frmDatosArticulo'));
					case "editarArt": 
					 RecorrerForm('frmDatosArticulo',false);
					 xajax_editarArticulo(xajax.getFormValues('frmDatosArticulo')); 
						break;	 
				}
			}

		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadRecibArt','t','cantidad');
			validarCampo('txtCostoArt','t','monto');
			validarCampo('txtCantidadArt','t','');
			
				if (byId('rbtTipoArtCliente').checked == true){
					validarCampo('txtNombreClienteArt','t','');
					validarCampo('txtIdClienteArt','t','');
				}
			
			RecorrerForm('frmDatosArticulo',false);
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormOrden() {
		error = false; 
		if (!(validarCampo('txtFechaRegistroCompra','t','') == true
		&& validarCampo('txtNumeroFacturaProveedor','t','') == true
		&& validarCampo('txtNumeroControl','t','numeroControl') == true
		&& validarCampo('txtFechaProveedor','t','fecha') == true
		&& validarCampo('lstTipoClave','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtIdProv','t','') == true
		&& validarCampo('txtNombreProv','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtTotalOrdenValidar','t','') == true
		&& validarCampo('lstRetencionImpuesto','t','listaExceptCero') == true
		&& validarCampo('lstRetencionISLR','t','listaExceptCero') == true)) {
			
			validarCampo('txtFechaRegistroCompra','t','');
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroControl','t','numeroControl');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtIdProv','t','');
			validarCampo('txtNombreProv','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtTotalOrdenValidar','t','');
			validarCampo('lstRetencionImpuesto','t','listaExceptCero');
			validarCampo('lstRetencionISLR','t','listaExceptCero');
			
			error = true;
		}
		
		if (error == true) {
				if(validarCampo('txtTotalOrdenValidar','t','') == false){
					abrirDivFlotante('totalFactura', divFlotante8);
				}
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
				if (confirm('¿Seguro Desea Registrar La Compra?') == true) {
					RecorrerForm('frmDcto',true);
					RecorrerForm('frmListaArticulo',true);
					RecorrerForm('frmTotalDcto',true);
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaUnidadFisica'));	
				}
		}
	}
	
	function validarencabezadoOrden() {
		if (validarCampo('txtNumeroFacturaProveedor','t','') == true
			&& validarCampo('txtNumeroControl','t','numeroControl') == true
			&& validarCampo('txtFechaProveedor','t','fecha') == true
			&& validarCampo('lstTipoClave','t','lista') == true
			&& validarCampo('lstClaveMovimiento','t','lista') == true
			&& validarCampo('txtIdProv','t','') == true
			&& validarCampo('txtNombreProv','t','') == true
		) {
				byId('tdFlotanteTitulo').innerHTML = "Agregar Articulo";
				document.forms['frmBuscarArt'].reset(); 
					if ($('#trIva').is(':visible')){
						byId('trIva').style.display ='none';
					}
				
				error = true;	

		} else {
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroControl','t','numeroControl');
			validarCampo('txtFechaProveedor','t','fecha');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtIdProv','t','');
			validarCampo('txtNombreProv','t','');

			error = false;
		}
		return error;
	}
	
	function validarFormAlmacen() {
		if (validarCampo('txtCodigoArticulo','t','') == true
		&& validarCampo('txtArticulo','t','') == true
		&& validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('lstAlmacenAct','t','lista') == true
		&& validarCampo('lstCalleAct','t','lista') == true
		&& validarCampo('lstEstanteAct','t','lista') == true
		&& validarCampo('lstTramoAct','t','lista') == true
		&& validarCampo('lstCasillaAct','t','lista') == true
		) {
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
	
	function validarFrmTotalFactura(){
		if(validarCampo('txtTotalFactura','t','') == true){
			var totalFacturaUsuario = byId('txtTotalFactura').value;
			byId('txtTotalOrdenValidar').value = totalFacturaUsuario;
			byId('btnCancelarTotalFactura').click();
		}else{
			validarCampo('txtTotalFactura','t','');
			
			alert("Debe indicar el total de factura");
		}	
	}
	
	function validarFrmUnidadFisica(){		
		if (byId('lstTipoFactura').value == 1) {
			if (validarCampo('txtDescripcionServicioMantenimiento','t','') == true) {
				//continuar
			} else {
				validarCampo('txtDescripcionServicioMantenimiento','t','');
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}			
		}
		
		if (validarCampo('txtIdUnidadFisica','t','') == true
		&& validarCampo('lstTipoFactura','t','listaExceptCero') == true
		&& validarCampo('txtCostoUnitarioUnidadFisica','t','monto') == true
		) {
			xajax_insertarUnidadFisica(xajax.getFormValues('frmDatosUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'));
		}else{
			validarCampo('txtIdUnidadFisica','t','');
			validarCampo('lstTipoFactura','t','listaExceptCero');
			validarCampo('txtCostoUnitarioUnidadFisica','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

	//PARA VALIDAR QUE SE 
	function validarImpuestoBloque(){
		var frm = document.getElementById('frmLstImpuesto');
		for (i=0; i < frm.elements.length; i++)	{// RECORRE LOS ELEMENTOS DEL FROM}
			if(frm.elements[i].checked){// SI EXISTE ALGUN ELEMENTO EN CHECKED
				xajax_insertarImpuestoBloque(xajax.getFormValues('frmListaArticulo'),xajax.getFormValues('frmLstImpuesto'));
				return true;	
			} 
		}
		
		alert('Debe seleccioanr una opcion'); 
		return false; 
	}
	
	function validarTotales(){
		if(byId('txtTotalOrdenValidar').value == ""){		 
			abrirFormTotal = true; //abre formulario totales
		}else{
			abrirFormTotal = false; //no abre formulario totales
		}
		return abrirFormTotal;		
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_compras.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
            <tr><td class="tituloPaginaCompras">Registro de Compra</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td align="left"><!--FORMULARIO EN CABEZADO-->
                    <form id="frmDcto" name="frmDcto" style="margin:0"> 
                        <table border="0" width="100%">
                            <tr align="left">
                                <td colspan="4"></td>
                                <td align="right" class="tituloCampo">Id Reg. Compra:</td>
                                <td><input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td colspan="4" >
                                	<table width="100%">
                                    	<tr>
                                        	<td align="right" width="13%" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Empresa:</td>
                                            <td>
                                           		<input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/>
                                    			<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/>
                                            </td>
                                        </tr>
                                    </table>
                                
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
                                <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaRegistroCompra" name="txtFechaRegistroCompra" readonly="readonly" size="10" style="text-align:center"/>
                                    </div>
                                </td>
                            </tr>
                            <tr align="left">
                           	 	<td colspan="2" rowspan="5" valign="top">
                                <fieldset>
                                <legend class="legend">Proveedor </legend>
                                    <table border="0" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                                            <td colspan="3">
                                                <table cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td>
                                                        	<input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value,'Prov');" size="6" style="text-align:right"/></td>
                                                        <td>
                                							<a class="modalImg" id="alistProveed" rel="#divFlotante3" onclick="abrirDivFlotante('',this);">
                                                                <button type="button" id="btnAgregarProv" name="btnAgregarProv" style="cursor:pointer" title="Agregar Proveedor">
                                                                    <table align="center" cellpadding="0" cellspacing="0">
                                                                        <tr>
                                                                            <td>&nbsp;</td>
                                                                            <td><img src="../img/iconos/cita_add.png"/></td>
                                                                            <td>&nbsp;</td>
                                                                        </tr>
                                                                    </table>
                                                                </button>
                                                            </a>
                                                        </td>
                                                    <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" rowspan="3" width="20%">Dirección:</td>
                                            <td rowspan="3" width="38%"><textarea id="txtDireccionProv" name="txtDireccionProv" cols="28" readonly="readonly" rows="3"></textarea></td>
                                            <td align="right" class="tituloCampo" width="20%"><?php echo $spanProvCxP; ?>:</td>
                                            <td width="22%"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Teléfono:</td>
                                            <td><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="12" style="text-align:center"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Días Crédito:</td>
                                            <td><input type="text" id="txtDiasCredito" name="txtDiasCredito" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Email:</td>
                                            <td colspan="3"><input type="text" id="txtEmailContactoProv" name="txtEmailContactoProv" readonly="readonly" size="26"/></td>
                                        </tr>
                                    </table>
                                </fieldset>
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Factura Prov.:</td>
                                <td><input type="text" id="txtNumeroFacturaProveedor" name="txtNumeroFacturaProveedor" class="inputHabilitado" size="20" style="text-align:center;"/></td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control Prov.:</td>
                                <td>
                                    <div style="float:left">
                                        <input type="text" id="txtNumeroControl" name="txtNumeroControl" class="inputHabilitado" size="20" style="text-align:center"/>&nbsp;
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/ico_pregunta.gif" title="Formato Ej.: 00-000000 / Máquinas Fiscales"/>
                                    </div>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Emisión: <span class="textoNegrita_10px">(Proveedor)</span></td>
                                <td colspan="3">
                                	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" size="10" style="text-align:center" class="inputHabilitado"/></td>
                                            <td><label><input type="checkbox" id="cbxFechaRegistro" name="cbxFechaRegistro" onclick="xajax_asignarFechaRegistro(xajax.getFormValues('frmDcto'));" value="1"/>Asignar como fecha de registro</label></td>
                                        </tr>
                                    </table>
                                </td>
                        	</tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                                <td id="tdlstTipoClave">
                                    <select id="lstTipoClave" name="lstTipoClave" >
                                        <option value="-1">[ Seleccione ]</option>
                                        <!--<option value="1" selected="selected">COMPRA</option>
                                        <option value="2">ENTRADA</option>onchange="selectedOption(this.id,1); xajax_cargaLstClaveMovimiento(this.value)"-->
                                    </select>
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                                <td id="tdlstClaveMovimiento">
                                    <select id="lstClaveMovimiento" name="lstClaveMovimiento" class="inputHabilitado">
                                        <option value="-1">[ Seleccione ]</option>
                                    </select>
                                </td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                                <td colspan="3">
                                    <input id="rbtTipoPagoCredito" name="rbtTipoPago" type="radio" value="0" checked="checked"/>Crédito
                                    <input id="rbtTipoPagoContado" name="rbtTipoPago" type="radio" value="1"/>Contado
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td><!--FORMULARIO QUE CARGA EL LISTADO DE LOS ARTICULO DEL REGISTRO-->
                	<fieldset>
					<legend class="legend">Art&iacute;culos</legend>
                        <table align="left">
                        <tr>
                            <td><div id="divBtnAgregar"></div></td>
                            <td>
                                <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" style="cursor:default" title="Eliminar Artículo">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/delete.png"/></td>
                                            <td>&nbsp;</td>
                                            <td>Quitar</td>
                                        </tr>
                                    </table>
                                </button>
                            </td>
                             <td>
                                <a class="modalImg" id="AgregarImpuArt" onclick="abrirDivFlotante('MostrarImpuestoPorBLoque',this);" rel="#divFlotante5">
                                <button type="button" id="btnImpuestoArt" name="btnImpuestoArt" style="cursor:default" title="Agregar Impuesto">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/text_signature.png"/></td>
                                            <td>&nbsp;</td>
                                            <td>Impuesto</td>
                                        </tr>
                                    </table>
                                </button>
                                </a>
                            </td>
                        </tr>
                        </table>
                        <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0"> 
                            <table border="0" width="100%" id="tableItmDoc">
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                                <td width="4%">Nro.</td>
                                <td></td>
                                <td style="display:none">Ubic.</td> <!---->
                                <td width="14%">Código</td>
                                <td width="50%">Descripción</td>
                                <td width="4%">Ped.</td>
                                <td width="4%">Recib.</td>
                                <td width="4%">Pend.</td>
                                <td width="8%">Costo Unit.</td>
                                <td width="4%">Impuesto</td>
                                <td width="8%">Total</td>
                            </tr>
                            <tr id="trItmPie"></tr>
                            </table>
                        </form>
					</fieldset>
                </td>
            </tr>
            <tr>
            	<td>
                	<fieldset>
                    <legend class="legend">Asociar Gastos Unidades de Alquiler</legend>
                        <table align="left">
                        <tr>
                            <td>
                                <a class="modalImg" id="aUnidadFisica" rel="#divFlotante9" onclick="abrirDivFlotante('unidad',this);">
                                    <button type="button" style="cursor:default" title="Agregar Unidad">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr>
                                    </table>
                                    </button>
                                </a>
                            </td>
                            <td>
                                <button type="button" onclick="eliminarUnidadFisica();" style="cursor:default" title="Eliminar Unidad">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/delete.png"/></td>
                                            <td>&nbsp;</td>
                                            <td>Quitar</td>
                                        </tr>
                                    </table>
                                </button>
                            </td>
                        </tr>
                        </table>
                        <form id="frmListaUnidadFisica" name="frmListaUnidadFisica" style="margin:0"> 
                        	<input type="hidden" id="hddIdDetUnidadFisicaEliminar" name="hddIdDetUnidadFisicaEliminar" readonly="readonly"/>
                            <table border="0" width="100%" class="tablaResaltarPar">
                            <thead>
                                <tr align="center" class="tituloColumna">
                                    <td width="1%"><input type="checkbox" onclick="selecAllChecks(this.checked,'cbxItmUnidad',1);"/></td>
                                    <td width="5%">Nro. Uni.</td>
                                    <td width="8%"><?php echo $spanPlaca; ?></td>
                                    <td width="10%"><?php echo $spanSerialCarroceria; ?></td>
                                    <td width="30%">Descripción</td>
                                    <td width="10%"><?php echo $spanKilometraje; ?></td>
                                    <td width="10%">Servicio / Mantenimiento</td>
                                    <td width="8%">Costo Unit.</td>
                                </tr>
                            </thead>
                            <tbody>
                            	<tr id="trItmPieUnidadFisica"></tr>
                            </tbody>
                            </table>
                        </form>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td align="right"><!--FORMULARIO DE LOS TOTALES TOTAL DE GASTO TOTAL DE IMPUESTO-->
                    <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0"> 
                    <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                        <table border="0" width="100%">
                            <tr>
                                <td align="right" id="tdGastos" valign="top" width="50%"> <!--LOS GASTO DE LA FACTURA-->
                                	<fieldset>
                                    	<legend class="legend">Gastos</legend>
                                    <table width="100%" border="0">
                                    	<tr>
                                        <td colspan="6"><!--BOTON AGREGAR GASTOS-->
                                        	<a class="modalImg" id="AgregarGastos" rel="#divFlotante6" onclick="abrirDivFlotante('MostrarGastos',this);">
                                            <button title="Agregar Gastos" id="btnAgregarGasto" name="btnAgregarGasto" type="button">
                                                <table cellspacing="0" cellpadding="0" align="center">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td><img src="../img/iconos/add.png"></td>
                                                        <td>&nbsp;</td>
                                                        <td>Agregar</td>
                                                    </tr>
                                                </table>
                                            </button>
                                            </a>
                                        <!--BOTON DE QUITAR GASTOS-->
<button title="Quitar Gastos" onclick="xajax_eliminarGastos(xajax.getFormValues('frmTotalDcto'))" name="btnQuitarGasto" id="btnQuitarGasto" type="button">
                                                <table cellspacing="0" cellpadding="0" align="center">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td><img src="../img/iconos/delete.png"></td>
                                                        <td>&nbsp;</td>
                                                        <td>Quitar</td>
                                                    </tr>
                                                </table>
                                            </button>
                                        </td>
                                        </tr>
                                        <tr class="tituloColumna" align="center">
                                            <td><input id="checkGastoItemFactura" type="checkbox" onclick="seleccionarTodosCheckbox('checkGastoItemFactura','checkItemClaseGasto');"></td>
                                            <td>Nro</td>
                                            <td>Descripcion Gasto</td>
                                            <td>% Gasto</td>
                                            <td>Monto Gasto</td>
                                            <td>Impuesto</td>
                                            <td></td>
                                        </tr>
                                        <tr id="trItmPieGastos"></tr>
                                        <tr class="trResaltarTotal">
                                        	<td colspan="4" class="tituloCampo" align="right">Total Gasto</td>
                                            <td> 
                                            	<input id="txtTotalGasto" class="inputSinFondo" type="text" style="text-align:right" readonly="readonly" name="txtTotalGasto">
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                        <tr>
                                            <td class="divMsjInfo2" colspan="7">
                                                <table width="100%" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                                                        <td align="center">
                                                            <table>
                                                                <tr>
                                                                    <td><img src="../img/iconos/accept.png"></td>
                                                                    <td>Gastos que llevan impuesto</td>
                                                                    <td>&nbsp;</td>
                                                                    <td><img src="../img/iconos/stop.png"></td>
                                                                    <td>No afecta cuenta por pagar</td>
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
                                <td width="50%">
                                    <table border="0" width="100%">
                                        <tr align="right">
                                            <td class="tituloCampo" width="36%">Sub-Total:</td>
                                            <td width="24%"></td>
                                            <td width="13%"></td>
                                            <td id="tdSubTotalMoneda" width="5%"></td>
                                            <td width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Descuento:</td>
                                            <td></td>
                                            <td nowrap="nowrap">
                                            <input type="text" id="txtDescuento" name="txtDescuento" class="inputHabilitado" size="6" style="text-align:right" 
                                                onfocus="if (byId('txtDescuento').value <= 0){ byId('txtDescuento').select(); }" 
                                                onkeypress="return validarSoloNumerosReales(event);" 
                                                onkeyup="xajax_calcularDcto(xajax.getFormValues('frmDcto'),
                                                            xajax.getFormValues('frmListaArticulo'), 
                                                            xajax.getFormValues('frmTotalDcto'))"/>%
                                            </td>
                                            <td id="tdDescuentoMoneda"></td>
                                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdGastoConIvaMoneda"></td>
                                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                    <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                                        <tr align="right" id="trGastosSinIva">
                                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdGastoSinIvaMoneda"></td>
                                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"></td>
                                        </tr>
                                        <tr class="trResaltarTotal" align="right">
                                            <td class="tituloCampo">Total Registro Compra:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdTotalRegistroMoneda"></td>
                                            <td>
                                            	<input type="text" id="txtTotalOrden" name="txtTotalOrden" readonly="readonly" class="inputSinFondo" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right" id="trNetoOrden">
                                            <td class="tituloCampo">Total Factura Compra:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdTotalRegistroMoneda"></td>
                                            <td>
                                                <input type="text" id="txtTotalOrdenValidar" name="txtTotalOrdenValidar" class="inputTotal" readonly="readonly" size="16" style="text-align:right; "/></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"><hr></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Exento:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdExentoMoneda"></td>
                                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" readonly="readonly" size="16" style="text-align:right"/></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo">Exonerado:</td>
                                            <td></td>
                                            <td></td>
                                            <td id="tdExoneradoMoneda"></td>
                                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" readonly="readonly" size="16" style="text-align:right"/></td>
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
                                            <td id="tdBaseRetencionISLR"><input type="text" id="txtBaseRetencionISLR" name="txtBaseRetencionISLR" class="inputHabilitado" size="16" style="text-align:center" onblur="setFormatoRafk(this,2); xajax_calcularMontoRetencionISLR(xajax.getFormValues('frmTotalDcto'));"/></td>
                                            <td><input type="text" id="txtPorcentajeISLR" name="txtPorcentajeISLR" readonly="readonly" size="6" style="text-align:right"/>%</td>
                                            <td id="tdRetencionISLRMoneda"></td>
                                            <td id="tdMontoRetencionISLR"><input type="text" id="txtTotalMontoRetencionISLR" name="txtTotalMontoRetencionISLR" readonly="readonly" size="16" style="text-align:right"/></td>
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
                            <tr>
                                <td colspan="2">
                                    <table border="0" width="100%">
                                        <tr>
                                            <td valign="top" width="50%">
                                                <fieldset><legend class="legend">Datos del Pedido</legend>
                                                    <table border="0" width="100%">
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo">Id Pedido:</td>
                                                            <td><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="18"/></td>
                                                            <td align="right" class="tituloCampo">Fecha:</td>
                                                            <td><input type="text" id="txtFechaPedido" name="txtFechaPedido" readonly="readonly" size="18"/></td>
                                                        </tr>
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo" width="24%">Nro. Pedido Propio:</td>
                                                            <td width="26%"><input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="18"/></td>
                                                            <td align="right" class="tituloCampo" width="24%">Nro. Referencia:</td>
                                                            <td width="26%"><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" readonly="readonly" size="18"/></td>
                                                        </tr>
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo">Empleado:</td>
                                                            <td><input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="18"/></td>
                                                        </tr>
                                                    </table>
                                                </fieldset>
                                            </td>
                                            <td valign="top" width="50%">
                                                <fieldset><legend class="legend">Datos de la Orden</legend>
                                                    <table border="0" width="100%">
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo" width="24%">Id Orden Compra:</td>
                                                            <td width="26%"><input type="text" id="txtIdOrdenCompra" name="txtIdOrdenCompra" readonly="readonly" size="16"/></td>
                                                            <td align="right" class="tituloCampo" width="24%">Fecha:</td>
                                                            <td width="26%">
                                                            <div style="float:left">
                                                                <input type="text" id="txtFechaOrden" name="txtFechaOrden" readonly="readonly" size="10"/>
                                                            </div>
                                                            </td>
                                                        </tr>
                                                        <tr align="left">
                                                            <td align="right" class="tituloCampo">Observaciones:</td>
                                                            <td colspan="3"><textarea cols="30" id="txtObservacionFactura" name="txtObservacionFactura" rows="2"></textarea></td>
                                                        </tr>
                                                    </table>
                                                </fieldset>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" align="right"><hr><!--BOTONES PARA GUARDAR O CANCELAR EL REGISTRO-->
                                    <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFormOrden();" style="cursor:default">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/ico_save.png"/></td>
                                            <td>&nbsp;</td>
                                            <td>Guardar</td>
                                        </tr>
                                    </table>
                                    </button>
                                    <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('ga_registro_compra_list.php','_self');" style="cursor:default">
                                        <table align="center" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img src="../img/iconos/ico_error.gif"/></td>
                                                <td>&nbsp;</td>
                                                <td>Cancelar</td>
                                            </tr>
                                        </table>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </form>
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

<!--PARA EDITAR O AGREGAR LOS DATOS DEL ARTICULO-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo" width="100%" align="left"></td></tr></table></div>
<!--FORMULARIO PARA EDITAR O AGREGAR EL ARTICULO-->
    <form id="frmDatosArticulo" name="frmDatosArticulo" style="margin:0" onsubmit="return false;">
    <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
    <table border="0" id="tblArticulo" width="960">
    <tr id="trDatosArticulo" ><!--style="display:none"-->
        <td>
            <fieldset><!--CARGA LOS DATOS DEL ART-->
                <legend class="legend">Datos de Articulo</legend>
                <table border="0" width="100%"> 
                <tr align="left">
                    <td align="right" class="tituloCampo">	
                        <span class="textoRojoNegrita">*</span>Código:	
                    </td>
                    <td>
                        <input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
                        <input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/>
                    </td>
                    <td class="tituloCampo" align="center">Descripcion del Articulo</td>
                    <td align="right" class="tituloCampo">Fecha Ult. Compra:</td>
                    <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Sección:</td>
                    <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="38"/></td>
                    <td rowspan="2" valign="top" align="center"><textarea id="txtDescripcionArt" name="txtDescripcionArt"  cols="50" rows="3" readonly="readonly"></textarea></td>
                    <td align="right" class="tituloCampo">Fecha Ult. Venta:</td>
                    <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                    <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                    <td align="right" class="tituloCampo">Disponible:</td>
                    <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
                </tr>
                </table>
            </fieldset>
            
			<table width="100%" border="0"><!--TABLA PARA EDITAR EL PEDIDO DEL ART-->
			<tr>
                <td valign="top">
					<table border="0" width="100%"> 
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="11%"><span class="textoRojoNegrita">*</span>Cantidad Pedida:</td>
                        <td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" onblur="setFormatoRafk(this,2);" readonly="readonly" size="12" style="text-align:right"/></td>
                        <td align="right" class="tituloCampo" width="13%"><span class="textoRojoNegrita">*</span>Cantidad Recibida:</td> <!--txtCantidadRecibArt-->
                        <td width="15%" colspan="2"><input type="text" id="txtCantidadRecibArt" name="txtCantidadRecibArt" onblur="setFormatoRafk(this,2);" class="inputHabilitado" size="10" style="text-align:right"/></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                        <td><input type="text" id="txtCostoArt" style="width: 110px;" name="txtCostoArt" maxlength="16" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" class="inputHabilitado" size="10" style="text-align:right"/></td>
                        <td align="right" class="tituloCampo">Descuento:</td>
                        <td width="19%">
                            <input type="radio" id="rbtPorcDescuentoArt" name="rbtDescuento" checked="checked" onclick="habilitar('rbtPorcDescuentoArt','rbtDescuento')" value="0">
                            <input type="text" id="txtPorcDescuentoArt" name="txtPorcDescuentoArt" onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularArtDescuento();" onblur="setFormatoRafk(this,2);" class="inputHabilitado" size="10" style="text-align:right"/>%
                        </td>
                        <td colspan="2">
                            <input type="radio" id="rbtMontoDescuentoArt" name="rbtDescuento" onclick="habilitar('rbtMontoDescuentoArt','rbtDescuento')" value="1">
                            <input type="text" id="txtMontoDescuentoArt" name="txtMontoDescuentoArt" onkeyup="calcularArtDescuento();" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);"
                            size="10" style="text-align:right"/>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Tipo:</td>
                        <td width="25%">
                            <input id="rbtTipoArtReposicion" name="rbtTipoArt" onclick="habilitar('rbtTipoArtReposicion','rbtTipoArt');" type="radio" value="0" checked="checked"/> Reposicion
                            &nbsp;&nbsp;
                            <input id="rbtTipoArtCliente" name="rbtTipoArt" onclick="habilitar('rbtTipoArtCliente','rbtTipoArt');" type="radio" value="1" /> Cliente
                        </td>
                        <td align="right" class="tituloCampo">Nombre:</td>
                        <td colspan="4">
                            <table cellspacing="0" cellpadding="0">
                            <tr>
                                <td><input id="txtIdClienteArt" type="text" size="8" readonly="readonly" name="txtIdClienteArt"></td>
                                <td>
                                    <button id="ButtInsertClienteArt" name="ButtInsertClienteArt" class="modalImg" onclick="openImg(divFlotante2)" style="display:none">
                                        <img src="../img/iconos/help.png">
                                    </button>
                                </td>
                                <td><input id="txtNombreClienteArt" type="text" size="30"  name="txtNombreClienteArt"></td>
                            </tr>
                            </table>
                        </td>
                    </tr>
					</table>
                </td>
                <td>                
                    <fieldset>
                    <legend class="legend">% Impuesto</legend>
                        <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">
                        <tr>
                            <td  align="left" colspan="2"> <!--BOTON AGREGAR IMPUESTO-->
                                <a class="modalImg" id="AgregarIpuesto" rel="#divFlotante5" onclick="abrirDivFlotante('MostrarImpuesto', this)">
                                    <button id="btnAgregarImpuesto" name="btnAgregarImpuesto" type="button" title="Agregar Impuesto">
                                        <table cellspacing="0" cellpadding="0" align="center">
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/add.png"></td>
                                            <td>&nbsp;</td>
                                            <td>Agregar</td>
                                        </tr>
                                        </table>
                                    </button>
                                </a><!--/**/-->
                                <button name="btnQuitarImpuesto" id="btnQuitarImpuesto" onclick="xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'),1)" type="button" title="Quitar Impuesto">
                                    <table cellspacing="0" cellpadding="0" align="center">
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/delete.png"></td>
                                        <td>&nbsp;</td>
                                        <td>Quitar</td>
                                    </tr>
                                    </table>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><!--TABALA DONDE SE AGRAGAN LOS IMPUESTOS-->
                                <table border="0" id="tblIva" width="100%">                                       	
                                <tr class="tituloColumna" align="center">
                                    <td width="10%" align="center">
                                    <input id="cbxItmsImpuesto" type="checkbox" onclick="seleccionarTodosCheckbox('cbxItmsImpuesto','cbxItmImpuesto');">
                                    </td>
                                    <td width="13%">Id</td>
                                    <td>Impuesto</td>
                                </tr>
                                <tr id="trItemArtImpuesto"></tr>
                                </table> 
                            </td>                                    
                        </tr>
                        <tr class="trResaltarTotal">
                            <td align="right" class="tituloCampo" width="30%">Total Impuesto:</td>
                            <td><input type="text" id="textTotaIva" name="textTotaIva" readonly="readonly" class="inputSinFondo" style="text-align:right" value=""/> </td>
                        </tr>
						</table>
                    </fieldset>
                </td>
              </tr>
			</table>
        </td>
    </tr>
    <tr > <!--MUESTRA LA TABLA DE IMPUESTO-->
        <td id="trIva" style="display:none">
            <table width="100%" border="0">
            <caption id="capTituloTable" class="legend"></caption>
            <tr>
                <td id=""></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trBstDatosArticulo"> <!--BOTONES Y CAMPOS PARA GUDAR O CALCELAR-->
        <td align="right"><hr>
            <input type="hidden" id="hddTextIdArtAsigando" name="hddTextIdArtAsigando" />
            <input type="hidden" id="hddTextAccion" name="hddTextAccion" />
            <button type="button" id="btnGuardarDatosArticulo" name="btnGuardarDatosArticulo" onclick="RecorrerForm('frmDatosArticulo',true);validarFormArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarDatosArticulo" name="btnCancelarDatosArticulo" class="close" onclick="xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'));">Cerrar</button>
        </td>
    </tr>
    </table>
    </form>
</div>

<!--LISTADO DE CLIENTES-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo2" align="left">Cliente</td></tr></table></div>
    
    <table width="960">
    <tr>
        <td align="right"> <!--CONTIENE EL BUSCADOR DEL LISTADO-->
            <table  border="0" align="right"> 
            <tr>
                <td class="tituloCampo">Criterio:</td>
                <td colspan="4">
                    <form id="frmBuscarCliente" name="frmBuscarCliente" style="margin:0" onsubmit="return false;">
                        <input type="text" id="textCriterioCliente" name="textCriterioCliente" size="25"/>
                    </form>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right">
                <button id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_BuscarCliente(xajax.getFormValues('frmBuscarCliente'),xajax.getFormValues('frmDcto'));">Buscar</button>
                <button id="btnLimpiarCliente" name="btnLimpiarCliente" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                </td>
            </tr>	
            <tr>
                <td colspan="4">&nbsp;</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr> <!--DONDE SERA CREA EL LISTADO-->
        <td id="tdListaCliente" align="right"></td>
    </tr> 
    <tr><!--CONTIENE EL BOTON DE CERRAR EL LISTADO-->
        <td id=""  align="right">
            <hr />
            <button id="btnCerrarListaCLiente" name="btnCerrarListaCLiente" class="close">
                Cerrar
            </button>
        </td>
    </tr>
    </table>
</div>

<!--LISTADO DE PROVEEDORES-->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo3" align="left">Proveedores</td></tr></table></div>
    
    <table width="960"> <!--CONTIENE EL BUSCADOR Y LISTADO PROVEEDORES-->
    <tr>
        <td>
            <form id="frmBuscarProveedor" name="frmBuscarProveedor" style="margin:0" onsubmit="return false;">
            <table border="0" align="right"> <!--CONTIENE EL BUSCADOR DEL LISTADO-->
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="textCriterioProveed" name="textCriterioProveed" class="inputHabilitado" onkeyup="byId('BtnBuscarProvee').click();"/></td>
                <td>
                    <button id="BtnBuscarProvee" name="BtnBuscarProvee" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                    <button id="BtnLimpiarProvee" name="BtnLimpiarProvee" onclick="document.forms['frmBuscarProveedor'].reset(); byId('BtnBuscarProvee').click();">Limpiar</button>
                </td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr><td id="tdListProveedores"></td></tr><!--LISTADO PROVEEDORES-->
    <tr>
        <td align="right"><hr />
            <button id="btnCerrarListaProveedor" class="close" >Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<!--LISTADO DE ART-->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo4" align="left">Listado de Artículos</td></tr></table></div>
    
<!--TABLA QUE CONTIENE EL LISTADO ARTICULO Y EL BUSCADOR DE ART style="display:none"-->
    <table border="0" id="tblArticulo" width="960">
    <tr id="tdBuscarArt"> <!--CONTIENE EL FORM PAR ABUSCAR ART EN EL LIST ART-->
		<td align="right">
            <form id="frmBuscarArt" name="frmBuscarArt" style="margin:0" onsubmit="return false;">
                <table>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Tipo de Art:</td>
                    <td id="tdTipoArticulo" colspan="3">
                        <select id="lstTipoArticuloBus" name="lstTipoArticuloBus" class="inputHabilitado">
                        <option value="-"> [ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código Art:</td>
                    <td><input type="text" id="textCodigoArtBus" name="textCodigoArtBus" class="inputHabilitado" onkeyup="byId('btnBuscarArt').click();"/></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="textCriterioBus" name="textCriterioBus" class="inputHabilitado" onkeyup="byId('btnBuscarArt').click();"/></td>
                    <td>
                        <button type="button" id="btnBuscarArt" name="btnBuscarArt" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArt'),xajax.getFormValues('frmDcto'));">Buscar</button>
                        <button type="button" id="btnLimpiar" name="btnLimpiar" onclick="document.forms['frmBuscarArt'].reset(); byId('btnBuscarArt').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListadoArticulo"></td><!--LIST ART--> 
    </tr>
    <tr>
        <td align="right"><hr /><button id="btnListArt" name="btnListArt" class="close">Cerrar</button></td>
    </tr>
    </table >
</div>

<!--LISTADO IMPUESTO-->
<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo5" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo5" align="left"></td></tr></table></div>
    
    <form id="frmLstImpuesto" onsubmit="return false" style="margin:0">
    <table width="640" border="0">
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>
            <div id="divListIpmuesto"></div>
        </td>
    </tr>
    <tr>
        <td align="right"><hr />
            <button id="btsAceptarImpuestoPorBLoque" name="btsAceptarImpuestoPorBLoque" style="display:none" onclick="validarImpuestoBloque();">Aceptar</button>	
            <button id="btsCerraImpuesto" name="btsCerraImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    </form>
</div>

<!--LISTA DE GASTOS-->
<div id="divFlotante6" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo6" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo6" align="left"></td></tr></table></div>
    
    <table width="960" border="0">
    <tr>
        <td id="tdBuscarGastos" align="right">
            <form id="frmBuscarGastos" onsubmit="return false" style="margin:0">
            <table border="0">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Modo:</td>
                <td>
                    <select id="selctModoGastos" name="selctModoGastos" class="inputHabilitado" onchange="byId('btnBuscarGastos').click()">
                        <option value="-1">[ Todo ]</option>
                        <option value="1">Gastos</option>
                        <option value="2">Otros Cargos</option>
                        <option value="3">Gastos por Importación</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="120">Afecta Documento:</td>
                <td>
                    <select id="selctAfectaDoct" name="selctAfectaDoct" class="inputHabilitado" onchange="byId('btnBuscarGastos').click()">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="1">SI</option>
                        <option value="0">No</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td></td>
                <td></td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" name="txtBuscarCriterio" id="txtBuscarCriterio" class="inputHabilitado" onkeyup="byId('btnBuscarGastos').click()"></td>
                <td>
                    <button id="btnBuscarGastos" name="btnBuscarGastos" onclick="xajax_BuscarGastos(xajax.getFormValues('frmBuscarGastos'))">Buscar</button>
                    <button id="btnLimpiarGastos" name="btnLimpiarGastos" onclick="document.forms['frmBuscarGastos'].reset(); byId('btnBuscarGastos').click();">Limpiar</button>
                </td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <form id="frmLstGasto" onsubmit="return false" style="margin:0">
            <table border="0" width="100%">
            <tr>
            	<td><div id="divLstGastos"></div></td>
			</tr>
            <tr>
                <td class="divMsjInfo2">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                            <td align="center">
                                <table>
                                    <tr>
                                        <td><img title="Activo" src="../img/iconos/ico_verde.gif"></td>
                                        <td>Impuestos Activo</td>
                                        <td>&nbsp;</td>
                                        <td><img src="../img/iconos/stop.png"></td>
                                        <td>No afecta cuenta por pagar</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="right"><hr  />
                <button id="btnCerraGastos" name="btnCerraGastos" class="close">Cerrar</button>
                </td>
            </tr>
            </table> 
            </form>
        </td>
    </tr>
    </table>
</div>

<!--BUSCAR SOLICITUD-->
<div id="divFlotante7" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo7" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo7" align="left"></td></tr></table></div>
    
    <table border="0" width="960">
    <tr>
        <td>
            <form id="frmBuscarOrden" name="frmBuscarOrden" onsubmit="return false" style="margin:0">
            <table align="right"> 
            <tr>
                <td align="right" class="tituloCampo" width="120">Nro. Orden:</td>
                <td><input id="textNumOrden" name="textNumOrden" class="inputHabilitado"/></td>
                <td colspan="2" align="right">
                    <button id="btnBuscarNumOrden" name="btnBuscarNumOrden" onclick="xajax_buscarNumOrden(xajax.getFormValues('frmBuscarOrden'))">Buscar</button>
                    <button id="btnLimpiarNumOrden" name="btnLimpiarNumOrden" onclick="habilitar('tdListOrdenes', 'mostrarOrden', 'hide')" >Limpiar</button>
                </td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListOrdenes" style="display:none">
            <form id="frmLstOrden" onsubmit="return false" style="margin:0">
                <fieldset >
                    <legend class="legend"> Listado de Ordenes</legend>
                        <div id="divLstOrden"></div>
                </fieldset>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListItemsOrden" style="display:none">
            <form id="frmLstOrdenDetalle" onsubmit="return false" style="margin:0">
                <fieldset >
                    <legend id="lgdOrden" class="legend"></legend>
                        <div id="divLstOrdenDetalle"></div>
                </fieldset>
            </form>
        </td>
    </tr>
    <tr>
        <td align="right">
            <hr />
            <button id="btsCerraArtOrden" name="btsCerraArtOrden" class="close" onclick="byId('btnLimpiarNumOrden').click();">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<!--TOTAL DE LA FACTURA-->
<div id="divFlotante8" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo8" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo8" width="100%" align="left"></td><td></td></tr></table></div>
    
   <form id="formTotalFactura" name="formTotalFactura" onsubmit="return false" style="margin:0">
    <table width="440" border="0">
    <tr>
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Total Factura Compra:</td>
        <td>
            <input name="txtTotalFactura" id="txtTotalFactura" class="inputHabilitado" type="text" size="30" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);">
        </td>
    </tr>
    <tr>
        <td colspan="2" align="right"> 
        <hr>
            <input type="hidden" readonly="readonly" name="hddFrm" id="hddFrm" value="tblArticulosPedido">
            <button onclick="validarFrmTotalFactura();" name="btnGuardarTotalFactura" id="btnGuardarTotalFactura" type="submit">Aceptar</button>
            <button class="close" name="btnCancelarTotalFactura" id="btnCancelarTotalFactura" type="button">Cerrar</button>
        </td>
    </tr>
    </table>
   </form>    
</div>

<div id="divFlotante9" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo9" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo9" align="left">Listado de Unidades F&iacute;sicas</td></tr></table></div>
    <table border="0" id="tblUnidadFisica" width="960">
    <tr>
        <td align="right">
            <form id="frmBuscarUnidadFisica" name="frmBuscarUnidadFisica" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td width="120" align="right" class="tituloCampo">Criterio:</td>
                <td><input type="text" onkeyup="byId('btnBuscarUnidadFisica').click();" name="txtCriterioBuscarUnidadFisica" id="txtCriterioBuscarUnidadFisica" class="inputHabilitado"></td>
                <td>
                	<button onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscarUnidadFisica'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaUnidadFisica'));" name="btnBuscarUnidadFisica" id="btnBuscarUnidadFisica" type="submit">Buscar</button>
                    <button onclick="document.forms['frmBuscarUnidadFisica'].reset(); byId('btnBuscarUnidadFisica').click();" type="button">Limpiar</button>
				</td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListadoUnidadFisica"></td>
    </tr>
    <tr>
    	<td>
        	<form name="frmDatosUnidadFisica" id="frmDatosUnidadFisica"  onsubmit="return false;">
                <table style="float:left;">
                <tr>
                    <td width="100" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Unidad:</td>
                    <td><input type="text" id="txtIdUnidadFisica" name="txtIdUnidadFisica" size="10" readonly="readonly" style="text-align:left"></td>
                </tr>
				<tr>
                    <td width="100" align="right" class="tituloCampo"><?php echo utf8_encode($spanPlaca); ?>:</td>
                    <td><input type="text" id="txtPlacaUnidadFisica" name="txtPlacaUnidadFisica" size="10" readonly="readonly" style="text-align:left"></td>
                    <td width="100" align="right" class="tituloCampo"><?php echo utf8_encode($spanSerialCarroceria); ?>:</td>
                    <td><input type="text" id="txtSerialCarroceriaUnidadFisica" name="txtSerialCarroceriaUnidadFisica" readonly="readonly" style="text-align:left"></td>
                </tr>
                <tr align="left">
                    <td width="100" align="right" class="tituloCampo">Unidad:</td>
                    <td colspan="5"><input type="text" id="txtDescripcionUnidadFisica" name="txtDescripcionUnidadFisica" size="50" readonly="readonly" style="text-align:left"></td>
                </tr>
                <tr align="left"> 
                	<td width="100" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Factura:</td>
                    <td colspan="5">
                    	<select id="lstTipoFactura" name="lstTipoFactura" onchange="mostrarServicioMantenimiento(this.value);">
                        	<option value="">[ Seleccione ]</option>
                            <option value="0">NORMAL</option>
                            <option value="1">SERVICIO / MANTENIMIENTO</option>
                        </select>
                    </td>
                </tr>
                <tr align="left" id="trServicioMantenimiento">
                	<td width="100" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Servicio / Mantenimiento:</td>
                    <td colspan="5">
                    	<table>
                        <tr>
                        	<td><input type="text" id="txtDescripcionServicioMantenimiento" name="txtDescripcionServicioMantenimiento" size="40" readonly="readonly" style="text-align:left">
                            	<input type="hidden" id="hddIdServicioMantenimiento" name="hddIdServicioMantenimiento" readonly="readonly">
                            </td>
                            <td>
                                <a class="modalImg" id="aServicioMantenimiento" rel="#divFlotante10" onclick="abrirDivFlotante('servicioMantenimiento',this);">
                                    <button type="button" style="cursor:default" title="Agregar Servicio Mantenimiento">
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img src="../img/iconos/help.png"/></td><td>&nbsp;</td></tr>
                                    </table>
                                    </button>
                                </a>
	                        </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td width="100" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo Unitario:</td>
                    <td><input type="text" id="txtCostoUnitarioUnidadFisica" name="txtCostoUnitarioUnidadFisica" size="10" style="text-align:right" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);"></td>
                </tr>
                </table>
                
                <fieldset style="width: 30%; float: right;"><legend class="legend">Recordatorio de Mantenimiento</legend>
                <table style="float:left;">
                <tr>
                    <td width="100" align="right" class="tituloCampo"><?php echo $spanKilometraje; ?> Actual:</td>
                    <td><input type="text" id="txtKmUnidadFisica" name="txtKmUnidadFisica" size="10" style="text-align:right" readonly="readonly"></td>
                </tr>
                <tr>
                    <td width="100" align="right" class="tituloCampo"><?php echo $spanKilometraje; ?> Próximo:</td>
                    <td><input type="text" id="txtKmProximoUnidadFisica" name="txtKmProximoUnidadFisica" size="10" style="text-align:right" readonly="readonly"></td>
                </tr>
                </table>
                </fieldset>
            </form>
        </td>
    </tr>
    <tr>
        <td align="right">
            <hr />
			<button onclick="validarFrmUnidadFisica();" name="btnAgregarUnidadFisica" id="btnAgregarUnidadFisica" type="button">Aceptar</button>
            <button id="btnCancelarUnidadFisica" name="btnCancelarUnidadFisica" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante10" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo10" class="handle"><table width="100%"><tr><td id="tdFlotanteTitulo10" align="left">Listado de Servicios y Mantenimientos</td></tr></table></div>
    <table border="0" id="tblServicioMantenimiento" width="960">
    <tr>
        <td align="right">
            <form id="frmBuscarServicioMantenimiento" name="frmBuscarServicioMantenimiento" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td width="120" align="right" class="tituloCampo">Criterio:</td>
                <td><input type="text" onkeyup="byId('btnBuscarServicioMantenimiento').click();" name="txtCriterioBuscarServicioMantenimiento" id="txtCriterioBuscarServicioMantenimiento" class="inputHabilitado"></td>
                <td>
                	<button onclick="xajax_buscarServicioMantenimiento(xajax.getFormValues('frmBuscarServicioMantenimiento'), xajax.getFormValues('frmDatosUnidadFisica'));" name="btnBuscarServicioMantenimiento" id="btnBuscarServicioMantenimiento" type="submit">Buscar</button>
                    <button onclick="document.forms['frmBuscarServicioMantenimiento'].reset(); byId('btnBuscarServicioMantenimiento').click();" type="button">Limpiar</button>
				</td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListadoServicioMantenimiento"></td>
    </tr>
    <tr>
        <td align="right">
            <hr />
            <button id="btnCancelarServicioMantenimiento" name="btnCancelarServicioMantenimiento" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
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

jQuery(function($){
	$("#txtFechaProveedor").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
});
	
new JsDatePick({
	useMode:2,
	target:"txtFechaProveedor",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

xajax_listImpuesto(0,'iva','ASC','impuestoBloque');
xajax_listadoGastos(0,'id_gasto','ASC');

<?php if (!(isset($_GET['id']))) { ?>
	xajax_nuevoDcto(); 
<?php } else { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo5");
var theRoot   = document.getElementById("divFlotante5");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo6");
var theRoot   = document.getElementById("divFlotante6");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo7");
var theRoot   = document.getElementById("divFlotante7");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo8");
var theRoot   = document.getElementById("divFlotante8");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo9");
var theRoot   = document.getElementById("divFlotante9");
Drag.init(theHandle, theRoot);
</script>