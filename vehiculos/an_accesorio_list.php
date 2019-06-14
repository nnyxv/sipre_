<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_accesorio_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_accesorio_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Adicionales</title>
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		tblAccesorio = (byId('tblAccesorio').style.display == '') ? '' : 'none';
		
		byId('tblAccesorio').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante11') != undefined) {
			byId('imgCerrarDivFlotante11').onclick = function () {
				byId('tblAccesorio').style.display = tblAccesorio;
				
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
		
		if (verTabla == "tblAccesorio") {
			document.forms['frmAccesorio'].reset();
			byId('hddIdAccesorio').value = '';
			
			byId('lstActivo').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			byId('txtPrecio').className = 'inputHabilitado';
			byId('txtCosto').className = 'inputHabilitado';
			
			byId('txtIdCliente').className = 'inputHabilitado';
			byId('txtIdMotivo').className = 'inputHabilitado';
			byId('lstTipoComision').className = 'inputHabilitado';
			byId('txtPorcentajeComision').className = 'inputHabilitado';
			byId('txtMontoComision').className = 'inputHabilitado';
			
			byId('txtIdMotivoCargo').className = 'inputHabilitado';
			
			xajax_formAccesorio(valor);
			
			if (valor > 0) {
				byId('txtNombre').className = 'inputInicial';
				byId('txtNombre').readOnly = true;
				
				tituloDiv1 = 'Editar Adicional';
			} else {
				byId('txtNombre').className = 'inputHabilitado';
				byId('txtNombre').readOnly = false;
				
				tituloDiv1 = 'Agregar Adicional';
			}
			byId('btnCancelarAccesorio').onclick = function () { byId('imgCerrarDivFlotante1').click(); }
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblAccesorio") {
			byId('txtNombre').focus();
			byId('txtNombre').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2, valor3) {
		tblPermiso = (byId('tblPermiso').style.display == '') ? '' : 'none';
		tblLista = (byId('tblLista').style.display == '') ? '' : 'none';
		tblListaMotivo = (byId('tblListaMotivo').style.display == '') ? '' : 'none';
		
		byId('tblPermiso').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblPermiso').style.display = tblPermiso;
				byId('tblLista').style.display = tblLista;
				byId('tblListaMotivo').style.display = tblListaMotivo;
				
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
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
			byId('btnCancelarPermiso').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		} else if (verTabla == "tblLista") {
			byId('trBuscarEmpleado').style.display = 'none';
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarConcepto').style.display = 'none';
			byId('btnGuardarLista').style.display = 'none';
			
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('trBuscarCliente').style.display = '';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv2 = 'Clientes';
				byId(verTabla).width = "960";
			}
			byId('btnCancelarLista').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('txtCriterioBuscarMotivo').className = 'inputHabilitado';
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddPagarCobrarMotivo').value = valor2;
			byId('hddIngresoEgresoMotivo').value = valor3;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv2 = 'Motivos';
			byId('btnCancelarListaMotivo').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			}
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function validarFrmAccesorio() {
		error = false;
		if (!(validarCampo('lstTipoAdicional','t','lista') == true
		&& validarCampo('lstActivo','t','listaExceptCero') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('txtPrecio','t','numPositivo') == true
		&& validarCampo('txtCosto','t','numPositivo') == true
		&& validarCampo('lstPoseeIva','t','listaExceptCero') == true
		&& validarCampo('lstMostrarPredeterminado','t','listaExceptCero') == true)) {
			validarCampo('lstTipoAdicional','t','lista');
			validarCampo('lstActivo','t','listaExceptCero');
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			validarCampo('txtPrecio','t','numPositivo');
			validarCampo('txtCosto','t','numPositivo');
			validarCampo('lstPoseeIva','t','listaExceptCero');
			validarCampo('lstMostrarPredeterminado','t','listaExceptCero');
			
			error = true;
		}
		
		if (byId('txtIdMotivo').value > 0) {
			if (!(validarCampo('txtIdCliente','t','') == true
			&& validarCampo('txtIdMotivo','t','') == true
			&& validarCampo('lstTipoComision','t','') == true)) {
				validarCampo('txtIdCliente','t','');
				validarCampo('txtIdMotivo','t','');
				validarCampo('lstTipoComision','t','');
				
				error = true;
			}
			
			if (byId('lstTipoComision').value == 1) {
				if (!(validarCampo('txtPorcentajeComision','t','numPositivo') == true)) {
					validarCampo('txtPorcentajeComision','t','numPositivo');
					
					error = true;
				}
			}
			
			if (byId('lstTipoComision').value == 2) {
				if (!(validarCampo('txtMontoComision','t','numPositivo') == true)) {
					validarCampo('txtMontoComision','t','numPositivo');
					
					error = true;
				}
			}
		}
		
		if (byId('lstTipoAdicional').value == 4 || byId('txtIdMotivoCargo').value > 0) {
			if (!(validarCampo('txtIdMotivoCargo','t','') == true)) {
				validarCampo('txtIdMotivoCargo','t','');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarAccesorio(xajax.getFormValues('frmAccesorio'), xajax.getFormValues('frmListaAccesorio'));
		}
	}
	
	function validarEliminar(idAccesorio){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAccesorio(idAccesorio, xajax.getFormValues('frmListaAccesorio'));
		}
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
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
			<td class="tituloPaginaVehiculos">Adicionales</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAccesorio');">
							<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
						</a>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Tipo Adicional:</td>
                    <td id="tdlstTipoAdicionalBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Aplica Impuesto:</td>
                    <td id="tdlstPoseeIvaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Mostrar Predeterminado:</td>
                    <td id="tdlstMostrarPredeterminadoBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Genera Comisión:</td>
                    <td id="tdlstGeneraComisionBuscar"></td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Incluir en Costo de la Unidad:</td>
                    <td id="tdlstIncluirCostoCompraUnidadBuscar"></td>
                    <td align="right" class="tituloCampo">Estatus:</td>
                    <td>
                        <select id="lstActivoBuscar" name="lstActivoBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Inactivo</option>
                            <option value="1">Activo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarAccesorio(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaAccesorio" name="frmListaAccesorio" style="margin:0">
                <div id="divListaAccesorio" style="width:100%"></div>
            </form>
            </td>
        </tr>            
        <tr>
             <table width="100%" cellpadding="0" cellspacing="0" class="divMsjInfo2">
             <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table>
                    <tr>
                        <td><img src="../img/iconos/ico_verde.gif"></td><td>Activo</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_rojo.gif"></td><td>Inactivo</td>                                            
                    </tr>
                    </table>
                </td>
            </tr>
            </table>   
        </tr>                    
	</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante11" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante12" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmAccesorio" name="frmAccesorio" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAccesorio" width="960">
    <tr>
        <td>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Adicional:</td>
                <td id="tdlstTipoAdicional"></td>
                <td></td>
                <td></td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td>
                    <select id="lstActivo" name="lstActivo" style="width:99%">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1" selected="selected">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td colspan="5"><input type="text" id="txtNombre" name="txtNombre" size="40"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td colspan="5"><input type="text" id="txtDescripcion" name="txtDescripcion" style="width:99%"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="16%">
                    <span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:
                    <br>
                    <span class="textoNegrita_10px">(Para Ventas)</span>
                </td>
                <td width="18%"><input type="text" id="txtPrecio" name="txtPrecio" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"//></td>
                <td align="right" class="tituloCampo" width="16%">
                    <span class="textoRojoNegrita">*</span>Costo Unit.:
                    <br>
                    <span class="textoNegrita_10px">(Para Compras)</span>
                </td>
                <td width="18%"><input type="text" id="txtCosto" name="txtCosto" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event)" size="16" style="text-align:right"//></td>
                <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Aplica Impuesto:</td>
                <td id="tdlstPoseeIva" width="16%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">
                	<span class="textoRojoNegrita">*</span>Mostrar Predeterminado:
                    <br>
                    <span class="textoNegrita_10px">(Para Ventas)</span>
				</td>
                <td id="tdlstMostrarPredeterminado"></td>
                <td align="right" class="tituloCampo">
                    <span class="textoRojoNegrita">*</span>Genera Comisión:
                    <br>
                    <span class="textoNegrita_10px">(Para Ventas)</span>
                </td>
                <td>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td id="tdlstGeneraComision" width="100%"></td>
                        <td>&nbsp;</td>
                        <td>
                        <a class="modalImg" id="aGeneraComision" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'an_accesorio_list_genera_comision');">
                            <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                        </a>
                        </td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo">
                	<span class="textoRojoNegrita">*</span>Incluir en Costo de la Unidad:
                    <br>
                    <span class="textoNegrita_10px">(Para Compras)</span>
				</td>
                <td>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td id="tdlstIncluirCostoCompraUnidad" width="100%"></td>
                        <td>&nbsp;</td>
                        <td>
                        <a class="modalImg" id="aIncluirCostoCompraUnidad" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'an_accesorio_list_incluir_costo_unidad');">
                            <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                        </a>
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trParametroAdicionalContrato">
        <td>
        <fieldset><legend class="legend">Creación automática de Cuenta por Cobrar (Adicional por Contrato)</legend>
        	<table border="0" width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
            	<td colspan="5">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, '', '', '', 'true', 'false');" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aListarCliente" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista', 'Cliente');">
                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                    </tr>
                    <tr align="center">
                        <td id="tdMsjCliente" colspan="3"></td>
                    </tr>
                    </table>
                    <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                    <input type="hidden" id="hddTipoPagoCliente" name="hddTipoPagoCliente"/>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:</td>
            	<td colspan="5">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" onblur="xajax_asignarMotivo(this.value, 'Motivo', 'CC', 'I', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarMotivo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'CC', 'I');">
                            <button type="button" id="btnListarMotivo" name="btnListarMotivo" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtMotivo" name="txtMotivo" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Tipo Comisión:</td>
            	<td width="20%">
                	<select id="lstTipoComision" name="lstTipoComision" style="width:99%">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="1">Porcentaje</option>
                        <option value="2">Monto</option>
                        <option value="3">Utilidad</option>
                    </select>
                </td>
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Porcentaje Comisión:</td>
            	<td width="16%"><input type="text" id="txtPorcentajeComision" name="txtPorcentajeComision" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="16" style="text-align:right"/></td>
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Monto Comisión:</td>
            	<td width="16%"><input type="text" id="txtMontoComision" name="txtMontoComision" maxlength="12" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="16" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        </td>
    </tr>
    <tr id="trParametroAdicionalCargo">
        <td>
        <fieldset><legend class="legend">Creación automática de Cuenta por Cobrar (Adicional por Cargo)</legend>
        	<table border="0" width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Motivo:</td>
            	<td width="84%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdMotivoCargo" name="txtIdMotivoCargo" onblur="xajax_asignarMotivo(this.value, 'MotivoCargo', 'CC', 'I', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarMotivoCargo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoCargo', 'CC', 'I');">
                            <button type="button" id="btnListarMotivoCargo" name="btnListarMotivoCargo" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtMotivoCargo" name="txtMotivoCargo" readonly="readonly" size="45"/></td>
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
            <input type="hidden" id="hddIdAccesorio" name="hddIdAccesorio"/>
            <button type="submit" id="btnGuardarAccesorio" name="btnGuardarAccesorio" onclick="validarFrmAccesorio();">Guardar</button>
            <button type="button" id="btnCancelarAccesorio" name="btnCancelarAccesorio">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante21" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante22" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>

<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso" style="display:none" width="560">
    <tr>
        <td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%">Acción:</td>
                <td width="75%"><input type="text" id="txtDescripcionPermiso" name="txtDescripcionPermiso" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td>
                    <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
			<button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso">Cancelar</button>
			</td>
		</tr>
	</table>
</form>
    
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr id="trBuscarEmpleado">
    	<td>
        <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" onkeyup="byId('btnBuscarEmpleado').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarCliente">
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarConcepto">
    	<td>
        <form id="frmBuscarConcepto" name="frmBuscarConcepto" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarConcepto" name="txtCriterioBuscarConcepto" onkeyup="byId('btnBuscarConcepto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarConcepto" name="btnBuscarConcepto" onclick="xajax_buscarConcepto(xajax.getFormValues('frmBuscarConcepto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarConcepto'].reset(); byId('btnBuscarConcepto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddNumeroItm" name="hddNumeroItm">
        	<table width="100%">
            <tr>
            	<td><div id="divLista" style="width:100%;"></div></td>
			</tr>
            <tr>
                <td align="right"><hr>
                    <button type="submit" id="btnGuardarLista" name="btnGuardarLista" onclick="xajax_asignarGasto(xajax.getFormValues('frmLista'), xajax.getFormValues('frmListaArticulo'));">Aceptar</button>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
    
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
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();">Limpiar</button>
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
            <button type="button" id="btnCancelarListaMotivo" name="btnCancelarListaMotivo">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstActivoBuscar').className = 'inputHabilitado';
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

xajax_cargaLstTipoAdicional("lstTipoAdicionalBuscar", "true", "", "byId('btnBuscar').click();");
xajax_cargaLstSiNo("lstPoseeIvaBuscar", "true", "", "byId('btnBuscar').click();");
xajax_cargaLstSiNo("lstMostrarPredeterminadoBuscar", "true", "", "byId('btnBuscar').click();");
xajax_cargaLstSiNo("lstGeneraComisionBuscar", "true", "", "byId('btnBuscar').click();");
xajax_cargaLstSiNo("lstIncluirCostoCompraUnidadBuscar", "true", "", "byId('btnBuscar').click();");
xajax_listaAccesorio(0, 'nom_accesorio', 'ASC', '|' + byId('lstActivoBuscar').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>