<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_ajuste_inventario_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_ajuste_inventario_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Ajuste de Inventario</title>
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
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		tblUnidadFisica = (byId('tblUnidadFisica').style.display == '') ? '' : 'none';
		tblUniFisAgregado = (byId('tblUniFisAgregado').style.display == '') ? '' : 'none';
		tblLista = (byId('tblLista').style.display == '') ? '' : 'none';
		
		byId('tblUnidadFisica').style.display = 'none';
		byId('tblUniFisAgregado').style.display = 'none';
		byId('tblLista').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante11') != undefined) {
			byId('imgCerrarDivFlotante11').onclick = function () {
				byId('tblUnidadFisica').style.display = tblUnidadFisica;
				byId('tblUniFisAgregado').style.display = tblUniFisAgregado;
				byId('tblLista').style.display = tblLista;
				
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
		
		if (verTabla == "tblUnidadFisica") {
			document.forms['frmUnidadFisica'].reset();
			
			xajax_formUnidadFisica(valor);
			
			tituloDiv1 = 'Editar Unidad Física';
		} else if (verTabla == "tblUniFisAgregado") {
			document.forms['frmUniFisAgregado'].reset();
			
			xajax_formUnidadFisicaAgregado(valor, xajax.getFormValues('frmUniFisAgregado'));
			
			tituloDiv1 = 'Ver Agregados';
		} else if (verTabla == "tblLista") {
			document.forms['frmBuscarLista'].reset();
			byId('btnBuscarLista').onclick = function () {
				xajax_buscarNotaCreditoValeEnt(xajax.getFormValues('frmBuscarLista'), xajax.getFormValues('frmAjusteInventario'));
			}
			
			byId('txtCriterioBuscarLista').className = 'inputHabilitado';
			
			byId('btnBuscarLista').click();
			
			tituloDiv1 = 'Notas de Crédito';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblUnidadFisica") {
			byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();
		} else if (verTabla == "tblLista") {
			byId('txtCriterioBuscarLista').focus();
			byId('txtCriterioBuscarLista').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		tblAjusteInventario = (byId('tblAjusteInventario').style.display == '') ? '' : 'none';
		tblListaCliente = (byId('tblListaCliente').style.display == '') ? '' : 'none';
		tblListaDocumento = (byId('tblListaDocumento').style.display == '') ? '' : 'none';
		
		byId('tblAjusteInventario').style.display = 'none';
		byId('tblListaCliente').style.display = 'none';
		byId('tblListaDocumento').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblAjusteInventario').style.display = tblAjusteInventario;
				byId('tblListaCliente').style.display = tblListaCliente;
				byId('tblListaDocumento').style.display = tblListaDocumento;
				
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
		
		if (verTabla == "tblAjusteInventario") {
			document.forms['frmAjusteInventario'].reset();
			byId('hddIdDcto').value = "";
			
			byId('txtIdCliente').className = 'inputHabilitado';
			byId('txtPlacaAjuste').className = 'inputHabilitado';
			byId('txtFechaFabricacionAjuste').className = 'inputHabilitado';
			byId('txtSerialCarroceriaAjuste').className = 'inputCompletoHabilitado';
			byId('txtSerialMotorAjuste').className = 'inputHabilitado';
			byId('txtNumeroVehiculoAjuste').className = 'inputHabilitado';
			byId('txtTituloVehiculoAjuste').className = 'inputHabilitado';
			byId('txtRegistroLegalizacionAjuste').className = 'inputHabilitado';
			byId('txtRegistroFederalAjuste').className = 'inputHabilitado';
			byId('txtObservacion').className = 'inputHabilitado';
			byId('lstTipoTablilla').className = 'inputHabilitado';

			
			jQuery(function($){
				$("#txtFechaFabricacionAjuste").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
			});
			
			new JsDatePick({
				useMode:2,
				target:"txtFechaFabricacionAjuste",
				dateFormat:"<?php echo spanDatePick; ?>",
				cellColorScheme:"orange"
			});
			
			xajax_formAjusteInventario(xajax.getFormValues('frmUnidadFisica'), valor);
			
			tituloDiv2 = 'Ajuste de Inventario';
		} else if (verTabla == "tblListaCliente") {
			document.forms['frmBuscarCliente'].reset();
			
			byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
			
			byId('btnBuscarCliente').click();
			
			tituloDiv2 = 'Clientes';
		} else if (verTabla == "tblListaDocumento") {
			document.forms['frmBuscarDocumento'].reset();
			
			byId('hddObjDestinoDocumento').value = valor;
			byId('hddNomVentanaDocumento').value = valor2;
			
			byId('txtFechaDesdeBuscarDocumento').className = "inputHabilitado";
			byId('txtFechaHastaBuscarDocumento').className = "inputHabilitado";
			byId('txtCriterioBuscarDocumento').className = 'inputHabilitado';
			
			xajax_formListaDocumento();
			
			tituloDiv2 = 'Documentos';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblAjusteInventario") {
			byId('txtIdCliente').focus();
			byId('txtIdCliente').select();
		} else if (verTabla == "tblListaCliente") {
			byId('txtCriterioBuscarCliente').focus();
			byId('txtCriterioBuscarCliente').select();
		} else if (verTabla == "tblListaDocumento") {
			byId('txtCriterioBuscarDocumento').focus();
			byId('txtCriterioBuscarDocumento').select();
		}
	}
	
	function validarFrmAjusteInventario() {
		error = false;
		if (!(validarCampo('txtIdEmpresa', 't', '') == true
		&& validarCampo('txtIdCliente', 't', '') == true
		&& validarCampo('lstClaveMovimiento', 't', 'lista') == true
		&& validarCampo('txtSubTotal', 't', 'monto') == true)) {
			validarCampo('txtIdEmpresa', 't', '');
			validarCampo('txtIdCliente', 't', '');
			validarCampo('lstClaveMovimiento', 't', 'lista');
			validarCampo('txtSubTotal', 't', 'monto');
			
			error = true;
		}
		
		if (!(byId('txtIdUnidadFisicaAjuste').value > 0)) {
			if (!(validarCampo('lstUnidadBasica', 't', 'lista') == true
			&& validarCampo('lstAno', 't', 'lista') == true
			&& validarCampo('lstCondicion', 't', 'lista') == true
			&& validarCampo('txtFechaFabricacionAjuste', 't', '') == true
			&& validarCampo('lstColorExterno1', 't', 'lista') == true
			&& validarCampo('lstColorInterno1', 't', 'lista') == true
			&& validarCampo('lstTipoTablilla', 't', 'lista') == true
			&& validarCampo('txtSerialCarroceriaAjuste', 't', '') == true
			&& validarCampo('txtSerialMotorAjuste', 't', '') == true
			&& validarCampo('txtNumeroVehiculoAjuste', 't', '') == true
			&& validarCampo('txtTituloVehiculoAjuste', 't', '') == true
			&& validarCampo('txtRegistroLegalizacionAjuste', 't', '') == true
			&& validarCampo('txtRegistroFederalAjuste', 't', '') == true
			&& validarCampo('lstAlmacenAjuste', 't', 'lista') == true
			&& validarCampo('lstEstadoVentaAjuste', 't', 'lista') == true
			&& validarCampo('lstMoneda', 't', 'lista') == true)) {
				validarCampo('lstUnidadBasica', 't', 'lista');
				validarCampo('lstAno', 't', 'lista');
				validarCampo('lstCondicion', 't', 'lista');
				validarCampo('lstTipoTablilla', 't', 'lista');	
				validarCampo('txtFechaFabricacionAjuste', 't', '');
				validarCampo('lstColorExterno1', 't', 'lista');
				validarCampo('lstColorInterno1', 't', 'lista');
				validarCampo('txtSerialCarroceriaAjuste', 't', '');
				validarCampo('txtSerialMotorAjuste', 't', '');
				validarCampo('txtNumeroVehiculoAjuste', 't', '');
				validarCampo('txtTituloVehiculoAjuste', 't', '');
				validarCampo('txtRegistroLegalizacionAjuste', 't', '');
				validarCampo('txtRegistroFederalAjuste', 't', '');
				validarCampo('lstAlmacenAjuste', 't', 'lista');
				validarCampo('lstEstadoVentaAjuste', 't', 'lista');
				validarCampo('lstMoneda', 't', 'lista');
				
				error = true;
			}
			
			if (byId('lstTipoVale').value == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
				if (!(validarCampo('txtNroDcto', 't', '') == true)) {
					validarCampo('txtNroDcto', 't', '');
					
					error = true;
				}
			}
		}
		
		if (byId('txtEstadoVentaAjuste').value == 'ACTIVO FIJO') {
			if (!(validarCampo('lstTipoActivo', 't', 'lista') == true)) {
				validarCampo('lstTipoActivo', 't', 'lista');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el vale?') == true) {
				xajax_guardarAjusteInventario(xajax.getFormValues('frmAjusteInventario'), xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'));
			}
		}
	}
	
	function validarFrmUnidadFisica() {
		error = false;
		
		if (!(validarCampo('lstEstadoVenta', 't', 'lista') == true)) {
			validarCampo('lstEstadoVenta', 't', 'lista');
			
			error = true;
		}
		
		if (byId('hddEstadoVenta').value == byId('lstEstadoVenta').options[byId('lstEstadoVenta').selectedIndex].text) {
			byId('lstEstadoVenta').className = "inputErrado";
			
			alert("El campo señalado en rojo no ha variado");
			return false;
		}
		
		if (byId('hddEstadoVenta').value != "DISPONIBLE" && byId('lstEstadoVenta').options[byId('lstEstadoVenta').selectedIndex].text != "DISPONIBLE") {
			alert("Variación de estado inválido");
			return false;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		} else {
			abrirDivFlotante2(byId('aGuardarUnidadFisica'), 'tblAjusteInventario', 1);
		}
	}
	
	function validarFrmUnidadFisicaAgregado() {
		error = false;
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		} else {
			xajax_guardarUnidadFisicaCargo(xajax.getFormValues('frmUniFisAgregado'), xajax.getFormValues('frmListaUnidadFisica'));
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
        	<td class="tituloPaginaVehiculos">Ajuste de Inventario</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblAjusteInventario', 0);">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
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
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscar"></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAnoBuscar"></td>
                	<td align="right" class="tituloCampo">Estado de Compra:</td>
                    <td id="tdlstEstadoCompraBuscar"></td>
                	<td align="right" class="tituloCampo">Estado de Venta:</td>
                    <td id="tdlstEstadoVentaBuscar"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Condición:</td>
                    <td id="tdlstCondicionBuscar"></td>
                	<td align="right" class="tituloCampo">Almacén:</td>
                    <td id="tdlstAlmacenBuscar"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" name="txtCriterio" id="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaUnidadFisica" name="frmListaUnidadFisica" style="margin:0">
            	<div id="divListaUnidadFisica" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0" class="divMsjInfo2">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/application_view_columns.png"/></td><td>Ver Agregados</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Unidad Física</td>
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
	
<form id="frmUnidadFisica" name="frmUnidadFisica" onsubmit="return false;" style="margin:0">
    <div id="tblUnidadFisica" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr>
                    <td width="68%"></td>
                    <td width="32%"></td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Nombre:</td>
                            <td width="30%">
                            	<input type="text" id="txtNombreUnidadBasica" name="txtNombreUnidadBasica" readonly="readonly" size="24"/>
				            	<input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica"/>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasica" name="txtClaveUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcion" name="txtDescripcion" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasica" name="txtMarcaUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasica" name="txtModeloUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasica" name="txtVersionUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Año:</td>
                            <td><input type="text" id="txtAno" name="txtAno" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Condición:</td>
                            <td><input type="text" id="txtCondicion" name="txtCondicion" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo">Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacion" name="txtFechaFabricacion" readonly="readonly" size="10"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="3" valign="top">
                        <table border="0" width="100%">
                        <tr>
                            <td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Id Unidad Física:</td>
                            <td width="60%"><input type="text" id="txtIdUnidadFisica" name="txtIdUnidadFisica" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Almacén:</td>
                            <td><input type="text" id="txtAlmacen" name="txtAlmacen" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td><input type="text" id="txtEstadoCompra" name="txtEstadoCompra" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Venta:</td>
                            <td>
                            	<div id="tdlstEstadoVenta"></div>
                            	<input type="hidden" id="hddEstadoVenta" name="hddEstadoVenta">
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Color Externo 1:</td>
                            <td width="30%"><input type="text" id="txtColorExterno1" name="txtColorExterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                            <td width="30%"><input type="text" id="txtColorExterno2" name="txtColorExterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Color Interno 1:</td>
                            <td><input type="text" id="txtColorInterno1" name="txtColorInterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td><input type="text" id="txtColorInterno2" name="txtColorInterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Seriales</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><?php echo $spanSerialCarroceria; ?>:</td>
                            <td width="30%">
                            <div style="float:left">
                                <input type="text" id="txtSerialCarroceria" name="txtSerialCarroceria" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>" readonly="readonly"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotor" name="txtSerialMotor" readonly="readonly"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculo" name="txtNumeroVehiculo" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo">Titulo Vehiculo:</td>
                            <td><input type="text" id="txtTituloVehiculo" name="txtTituloVehiculo" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanRegistroLegalizacion; ?>:</td>
                            <td><input type="text" id="txtRegistroLegalizacion" name="txtRegistroLegalizacion" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo">Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederal" name="txtRegistroFederal" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
            	<a class="modalImg" id="aGuardarUnidadFisica" rel="#divFlotante2"></a>
                <button type="button" id="btnGuardarUnidadFisica" name="btnGuardarUnidadFisica" onclick="validarFrmUnidadFisica();">Guardar</button>
                <button type="button" id="btnCancelarUnidadFisica" name="btnCancelarUnidadFisica" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>

<form id="frmUniFisAgregado" name="frmUniFisAgregado" onsubmit="return false;" style="margin:0">
    <div id="tblUniFisAgregado" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aAgregarAgregado" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaDocumento');">
                    	<button type="button" title="Agregar Agregado"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                        <button type="button" id="btnQuitarAgregado" name="btnQuitarAgregado" onclick="xajax_eliminarAgregado(xajax.getFormValues('frmUniFisAgregado'));" title="Eliminar Agregado"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
					</td>
				</tr>
                </table>
			</td>
		</tr>
        <tr>
        	<td>
            	<div id="divListaUniFisAgregado" style="width:100%">
                	<table border="0" class="texto_9px" width="100%">
                    <tr class="tituloColumna">
                        <td></td>
               			<td width="4%">Nro.</td>
                        <td width="8%">Tipo de Dcto.</td>
                        <td width="6%">Fecha Registro</td>
                        <td width="6%">Nro. Dcto.</td>
                        <td width="52%">Cliente / Proveedor</td>
                        <td width="8%">Estado Dcto.</td>
                        <td width="8%">Saldo Dcto.</td>
                        <td width="8%">Total Dcto.</td>
                        <td></td>
                    </tr>
                    <tr id="trItmPie" align="right" class="trResaltarTotal" height="24">
                        <td class="tituloCampo" colspan="8">Total Agregados:</td>
                        <td><input type="text" id="txtTotalAgregado" name="txtTotalAgregado" class="inputSinFondo" readonly="readonly"/></td>
                        <td></td>
                    </tr>
                   </table>
                </div>
			</td>
        </tr>
        <tr>
        	<td align="right"><hr>
            	<input type="hidden" id="hddIdTradeInCxP" name="hddIdTradeInCxP"/>
            	<input type="hidden" id="hddIdUnidadFisicaAgregado" name="hddIdUnidadFisicaAgregado"/>
                <input type="hidden" id="hddIdEmpresaUnidadFisicaAgregado" name="hddIdEmpresaUnidadFisicaAgregado"/>
                <button type="button" id="btnGuardarUnidadFisicaAgregado" name="btnGuardarUnidadFisicaAgregado" onclick="validarFrmUnidadFisicaAgregado();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelarUnidadFisicaAgregado" name="btnCancelarUnidadFisicaAgregado" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
            </td>
		</tr>
        </table>
	</div>
</form>
	
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr>
    	<td>
        	<form id="frmBuscarLista" name="frmBuscarLista" onsubmit="return false;" style="margin:0">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarLista" name="txtCriterioBuscarLista" onkeyup="byId('btnBuscarLista').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscarLista" name="btnBuscarLista">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarLista'].reset(); byId('btnBuscarLista').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divLista" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante21" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante22" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
<form id="frmAjusteInventario" name="frmAjusteInventario" onsubmit="return false;" style="margin:0">
    <div id="tblAjusteInventario" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" size="6" readonly="readonly" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
		</tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" width="65%">
                    <fieldset><legend class="legend">Datos Personales</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td width="85%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, '', '', 'true', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarCliente" rel="#divFlotante2" onclick="abrirDivFlotante2(null, 'tblListaCliente');">
                                        <button type="button" id="btnListarCliente" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    	<table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Estado Compra:</td>
                            <td id="tdlstEstadoCompraAjuste" width="30%"><input type="text" id="txtEstadoCompraAjuste" name="txtEstadoCompraAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="20%">Estado de Venta:</td>
                            <td width="30%"><input type="text" id="txtEstadoVentaAjuste" name="txtEstadoVentaAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Unidad Fisica:</td>
                        	<td><input type="text" id="txtIdUnidadFisicaAjuste" name="txtIdUnidadFisicaAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                            <td id="tdTipoActivo" align="right" class="tituloCampo" width="20%">Tipo Activo:</td>
                        	<td id="tdlstTipoActivo"></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="35%">
                    <fieldset><legend class="legend">Datos del Vale</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Vale:</td>
                            <td width="60%">
                                <input type="hidden" id="txtIdVale" name="txtIdVale" readonly="readonly"/>
                                <input type="text" id="txtNumeroVale" name="txtNumeroVale" readonly="readonly" size="20" style="text-align:center;"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Vale</td>
                            <td>
                                <select id="lstTipoVale" name="lstTipoVale" onchange="xajax_asignarTipoVale(this.value);">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Entrada / Salida</option>
                                    <option value="3">Nota de Crédito de CxC</option>
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov:</td>
                            <td id="tdlstTipoMovimiento"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td id="tdlstClaveMovimiento"></td>
                        </tr>
                        <tr align="left" id="trNroDcto" style="display:none">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota Crédito:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="hidden" id="hddIdDcto" name="hddIdDcto" readonly="readonly"/>
                                        <input type="text" id="txtNroDcto" name="txtNroDcto" readonly="readonly" size="20" style="text-align:center;"/>
                                    </td>
                                    <td>
                                    <a class="modalImg" id="aListarDcto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista');">
                                        <button type="button" id="btnListarDcto" name="btnListarDcto" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr id="trUnidadFisica">
        	<td>
            <fieldset><legend class="legend">Unidad Física</legend>
            	<table width="100%">
                <tr>
                	<td valign="top" width="68%">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td id="tdlstUnidadBasica" width="30%"></td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasicaAjuste" name="txtClaveUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcionAjuste" name="txtDescripcionAjuste" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasicaAjuste" name="txtMarcaUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasicaAjuste" name="txtModeloUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasicaAjuste" name="txtVersionUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td id="tdlstAno"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
                            <td id="tdlstCondicion"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlacaAjuste" name="txtPlacaAjuste" size="24"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Tablilla</td>
                            <td >
                            	<select id="lstTipoTablilla" name="lstTipoTablilla" style="width:99%">
                            		<option value="-1">[ Seleccione ]</option>
                            		<option value="CARGA">Carga</option>
                            		<option value="PRIVADA">Privada</option>
                            	</select>
                            </td>
                        </tr>
                        <tr align="left">
                               <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacionAjuste" name="txtFechaFabricacionAjuste" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="32%">
                    	<table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Almacén:</td>
                            <td id="tdlstAlmacenAjuste" width="60%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estado Venta:</td>
                            <td id="tdlstEstadoVentaAjuste"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                            <td id="tdlstMoneda"></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr>
                	<td valign="top">
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
                            <td id="tdlstColorExterno1" width="30%"></td>
                            <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                            <td id="tdlstColorExterno2" width="30%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                            <td id="tdlstColorInterno1"></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td id="tdlstColorInterno2"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Seriales</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialCarroceria; ?>:</td>
                            <td width="30%">
                            <div style="float:left">
                                <input type="text" id="txtSerialCarroceriaAjuste" name="txtSerialCarroceriaAjuste" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotorAjuste" name="txtSerialMotorAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculoAjuste" name="txtNumeroVehiculoAjuste"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Titulo Vehiculo:</td>
                            <td><input type="text" id="txtTituloVehiculoAjuste" name="txtTituloVehiculoAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRegistroLegalizacion; ?>:</td>
                            <td><input type="text" id="txtRegistroLegalizacionAjuste" name="txtRegistroLegalizacionAjuste"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederalAjuste" name="txtRegistroFederalAjuste"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
			</fieldset>
            </td>
        </tr>
        <tr>
        	<td>
            	<table width="100%">
                <tr>
                	<td valign="top" width="50%">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                    </td>
                	<td valign="top" width="50%">
                    	<table width="100%">
                        <tr id="trCostoUnidad" align="right">
                        	<td class="tituloCampo">Costo Compra:</td>
                        	<td><input type="text" id="txtCostoCompra" name="txtCostoCompra" class="inputSinFondo" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trMontoDepreciado" align="right">
                        	<td class="tituloCampo">Total Depreciado:</td>
                        	<td><input type="text" id="txtMontoDepreciado" name="txtMontoDepreciado" class="inputSinFondo" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                        	<td class="tituloCampo" width="36%"><span class="textoRojoNegrita">*</span>Subtotal:</td>
                        	<td width="64%"><input type="text" id="txtSubTotal" name="txtSubTotal" onblur="setFormatoRafk(this,2);" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnGuardarAjusteInventario" name="btnGuardarAjusteInventario" onclick="validarFrmAjusteInventario();">Guardar</button>
                <button type="button" id="btnCancelarAjusteInventario" name="btnCancelarAjusteInventario" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
  	
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
                        <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmAjusteInventario'));">Buscar</button>
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
                        <button type="button" id="btnCancelarListaCliente" name="btnCancelarListaCliente" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
    
<div id="tblListaDocumento" style="max-height:500px; overflow:auto; width:960px;">
    <table border="0" width="100%">
    <tr>
        <td>
        <form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoDocumento" name="hddObjDestinoDocumento" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaDocumento" name="hddNomVentanaDocumento" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Fecha:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;Desde:&nbsp;</td>
                        <td><input type="text" id="txtFechaDesdeBuscarDocumento" name="txtFechaDesdeBuscarDocumento" autocomplete="off" size="10" style="text-align:center"/></td>
                        <td>&nbsp;Hasta:&nbsp;</td>
                        <td><input type="text" id="txtFechaHastaBuscarDocumento" name="txtFechaHastaBuscarDocumento" autocomplete="off" size="10" style="text-align:center"/></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" width="120">Módulo:</td>
                <td id="tdlstModulo"></td>
			</tr>
            <tr align="left">
            	<td></td>
            	<td></td>
                <td align="right" class="tituloCampo">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarDocumento" name="txtCriterioBuscarDocumento" onkeyup="byId('btnBuscarDocumento').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarDocumento" name="btnBuscarDocumento" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), xajax.getFormValues('frmUniFisAgregado'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarDocumento'].reset(); byId('btnBuscarDocumento').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
            <div class="wrap">
                <!-- the tabs -->
                <ul class="tabs">
                    <li><a href="#">Vale de Salida (+)</a></li>
                    <li><a href="#">Nota de Créd. CxC (+)</a></li>
                    <li><a href="#">Nota de Déb. CxC (-)</a></li>
                </ul>
                
                <!-- tab "panes" -->
                <div class="pane">
                <form id="frmListaValeSalida" name="frmListaValeSalida" onsubmit="return false;" style="margin:0">
                    <div id="divListaValeSalida" style="width:100%"></div>
                </form>
                </div>
                
                <!-- tab "panes" -->
                <div class="pane">
                <form id="frmListaNotaCreditoCxC" name="frmListaNotaCreditoCxC" onsubmit="return false;" style="margin:0">
                    <div id="divListaNotaCreditoCxC" style="width:100%"></div>
                </form>
                </div>
                
                <!-- tab "panes" -->
                <div class="pane">
                <form id="frmListaNotaDebitoCxC" name="frmListaNotaDebitoCxC" onsubmit="return false;" style="margin:0">
                    <div id="divListaNotaDebitoCxC" style="width:100%"></div>
                </form>
                </div>
			</div>
        </td>
    </tr>
    <tr>
        <td align="right"><hr />
            <button type="button" id="btnCancelarListaDocumento" name="btnCancelarListaDocumento" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>
</div>

<script>
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesdeBuscarDocumento").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHastaBuscarDocumento").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesdeBuscarDocumento",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHastaBuscarDocumento",
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

//var lstEstadoCompraBuscar = $.map($("#lstEstadoCompraBuscar option:selected"), function (el, i) { return el.value; });
//var lstEstadoVentaBuscar = $.map($("#lstEstadoVentaBuscar option:selected"), function (el, i) { return el.value; });
//var lstCondicionBuscar = $.map($("#lstCondicionBuscar option:selected"), function (el, i) { return el.value; });
//var lstAlmacen = $.map($("#lstAlmacen option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'onchange=\"xajax_cargaLstAlmacenBuscar(\'lstAlmacenBuscar\', this.value); byId(\'btnBuscar\').click();\"');
xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "Buscar", "true", "", "", "byId('btnBuscar').click();");
xajax_cargaLstAnoBuscar();
xajax_cargaLstEstadoCompraBuscar('lstEstadoCompraBuscar', 'Ajuste');
xajax_cargaLstEstadoVentaBuscar('lstEstadoVentaBuscar', 'Ajuste');
xajax_cargaLstCondicionBuscar();
xajax_cargaLstAlmacenBuscar('lstAlmacenBuscar', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaUnidadFisica(0, 'CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>