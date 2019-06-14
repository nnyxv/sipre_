<?php

require_once("../connections/conex.php");
require_once('clases/barcode128.inc.php');
session_start();
include("../inc_sesion.php");

if ($_GET['id'] == 0){
	if (!validaAcceso("sa_orden_tot_list","insertar")){
		echo "
		<script type=\"text/javascript\">
			alert('Acceso Denegado');
			window.location='index.php';
		</script>";
	}
}else if ($_GET['id'] != 0 && $_GET['accion'] != 0){//cambiado, el anterior no funcionaba gregor
	if (!validaAcceso("sa_orden_tot_list","editar")){
		echo "
		<script type=\"text/javascript\">
			alert('Acceso Denegado');
			window.location='index.php';
		</script>";
	}
}
	

require('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');
include("controladores/ac_sa_orden_compra_tot.php");
include("controladores/ac_iv_general.php");//necesario para el listado de empresa final

//lo usa al guardar-registrar el tot factura
// MODIFICADO ERNESTO
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	include("../contabilidad/GenerarEnviarContabilidadDirecto.php");	
}
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
    <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Orden Compra TOT</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
     <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
	<link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
        
	<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>

    <script language="javascript" type="text/javascript">
	
	function validarTodoForm(){
		if ($('auxiliar').value == 0){//CUANDO ES NUEVO, no tiene totales
			
			if (validarCampo('selEmpresa','t','lista') == true
			 && validarCampo('txtIdProv','t','') == true
			 && validarCampo('txtRifProv','t','') == true
			 && validarCampo('txtTelefonosProv','t','') == true
			 && validarCampo('txtDireccionProv','t','') == true
			 && validarCampo('txtPlaca','t','') == true
			 && validarCampo('txtChasis','t','') == true){
			 	if (validarCampo('hddObj','t','') == true){
					xajax_guardarOrdenCompra( $('selEmpresa').value, $('txtIdProv').value, $('txtOrden').value, $('hddIdUsuario').value, xajax.getFormValues('frmDetallesTrabajoRequeridos') );
				}else{
					alert("Debe agregar minimo un trabajo");
				}
			}else{
			 	validarCampo('txtIdProv','t','')
			 	validarCampo('txtRifProv','t','')
			 	validarCampo('txtTelefonosProv','t','')
			 	validarCampo('txtDireccionProv','t','')
			 	validarCampo('txtPlaca','t','')
			 	validarCampo('txtChasis','t','')
				
				alert("Los campos señalados en rojo son requeridos");
				
				return false;
			 }
		}else{//EXISTE TOT, se registra la factura segundo paso
			
			if (validarCampo('selEmpresa','t','lista') == true
			 && validarCampo('txtIdProv','t','') == true
			 && validarCampo('txtRifProv','t','') == true
			 && validarCampo('txtTelefonosProv','t','') == true
			 && validarCampo('txtDireccionProv','t','') == true
			 && validarCampo('txtPlaca','t','') == true
		 	 && validarCampo('txtChasis','t','') == true
			 && validarCampo('txtNumeroFacturaProveedor','t','') == true
			 && validarCampo('txtNumeroControl','t','numeroControl') == true
			 && validarCampo('txtFechaProveedor','t','') == true
			 && validarCampo('txtFechaOrigen','t','') == true
			 && validarCampo('txtObservacionFactura','t','') == true
			 && validarCampo('txtSubTotal','t','') == true
			 && validarCampo('txtTotalOrden','t','') == true
			 && validarCampo('idOrdenTOT','t','') == true
			 && validarCampo('selRetencionISLR','t','lista') == true){
			 
			 	if ($('hddObjTotalDetalles').value == 1){
					arreglo = $('hddObj').value.split("|");
					i = 1;
					aux = true;
					
					while (i < arreglo.length && aux == true){
						aux = validarCampo('txtMonto'+ i,'t','');
						aux = validarCampo('txtCantidad'+ i,'t','');
						i++;                                                
					}
			
					if (aux == true){
						if ($('radio').checked || $('radio2').checked || $('radio3').checked){
							xajax_actualizarTOT (xajax.getFormValues('frmDatosFactura'), xajax.getFormValues('frmDetallesTrabajoRequeridos'), xajax.getFormValues('frmTotalFactura'), $('idOrdenTOT').value );
						}else{
							alert('Selecione el tipo de retencion');
						}
					}else{						
						alert("Los campos señalados en rojo son requeridos");	
						return false;
					}
				}else{
					if ($('radio').checked || $('radio2').checked || $('radio3').checked){
						xajax_actualizarTOT (xajax.getFormValues('frmDatosFactura'), xajax.getFormValues('frmDetallesTrabajoRequeridos'), xajax.getFormValues('frmTotalFactura'), $('idOrdenTOT').value );
					}else{
						alert('Selecionar el tipo de retencion');
					}					
				}
				
			}else{
			 	validarCampo('txtIdProv','t','')
			 	validarCampo('txtRifProv','t','')
			 	validarCampo('txtTelefonosProv','t','')
			 	validarCampo('txtDireccionProv','t','')
			 	validarCampo('txtPlaca','t','')
			 	validarCampo('txtChasis','t','')
				validarCampo('txtNumeroFacturaProveedor','t','')
			 	validarCampo('txtNumeroControl','t','numeroControl')
			 	validarCampo('txtFechaProveedor','t','')
			 	validarCampo('txtFechaOrigen','t','')
			 	validarCampo('txtObservacionFactura','t','')
			 	validarCampo('txtSubTotal','t','')
			 	validarCampo('txtTotalOrden','t','')
				validarCampo('idOrdenTOT','t','')
				validarCampo('selRetencionISLR','t','lista')
				
				alert("Los campos señalados en rojo son requeridos");
	
				return false;
			 }
		}
	}
	
	function validarForm(){
		if (validarCampo('txtDescripcionTrabajoRequerido','t','') == true){
			xajax_asignarTrabajoRequerido(xajax.getFormValues('frmTrabajoRequerido'),xajax.getFormValues('frmDetallesTrabajoRequeridos'));
		}else{
			validarCampo('txtDescripcionTrabajoRequerido','t','')			
			alert("El campo señalado en rojo es requerido");
			return false;
		}
	}
	
	function borrarFormularios(){
		xajax_eliminarTrabajoRequeridoForzado(xajax.getFormValues('frmDetallesTrabajoRequeridos'));
		//formateo manual del form de proveedor, no se puede formatear automaticamente porque afecta al select de empresa
		$('txtIdProv').value = '';
		$('txtRifProv').value = '';
		$('txtNombreProv').value = '';
		$('txtContactoProv').value = '';
		$('txtEmailContactoProv').value = '';
		$('txtDireccionProv').value = '';
		$('txtTelefonosProv').value = '';
		$('txtFaxProv').value = '';
        
		document.forms['frmDatosVehiculo'].reset();
		document.forms['frmDatosFactura'].reset();
		document.forms['frmDetallesTrabajoRequeridos'].reset();

		//document.forms['frmTotalFactura'].reset();
		//formateo manual del form de TotalFactura, no se puede formatear automaticamente porque afecta al objeto oculto que controla los ivas insertados
		$('txtSubTotal').value = '';
		$('txtTotalOrden').value = '';                       
	}
	
	function calcularRetencion(){
		
		var baseRetencion = parseFloat(byId('txtBaseRetencionISLR').value).toFixed(2);
		var montoMayorAplicar = parseFloat(byId('hddMontoMayorAplicar').value).toFixed(2);
		var sustraendo = parseFloat(byId('hddSustraendoRetencion').value).toFixed(2);
		var porcentaje = parseFloat(byId('hddPorcentajeRetencion').value).toFixed(2);		
		var montoRetencion = 0;
		
		if(baseRetencion >= montoMayorAplicar && !isNaN(baseRetencion)){
			montoRetencion = (baseRetencion * (porcentaje / 100)) - sustraendo;			
		}
		
		byId('txtMontoRetencionISLR').value = montoRetencion.toFixed(2);
	}
	
	function tot(){
		$('trDatosFactura').style.display = '';
		$('trTotalFactura').style.display = '';
		xajax_contribuyente();
		//borrarFormularios();
		$('auxiliar').value = 1;
		$('selEmpresa').disabled = 'disabled';
		$('btnInsertarProveedor').disabled = 'disabled';                
		$('btnInsertarVehiculo').disabled = 'disabled';
		$('btnInsertarTrabajoRequerido').disabled = 'disabled';
		$('btnEliminarTrabajoRequerido').disabled = 'disabled';
	}
        
	function abrirAccesorios(){
		xajax_buscarAccesorio(document.getElementById('selEmpresa').value, document.getElementById('txtCriterioBusquedaAccesorios').value);
	}
        
        
	function actualizarListadoItems(){
		var radio = document.getElementById("idRdo0");
		
		if(radio.checked){
			$('idRdo0').click();
		}else{
			$('idRdo1').click();
		}
	}
        
	function numeros(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[1-9]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}        
        
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralVehiculos">
	
    <?php include("banner_servicios.php"); ?>
    <div id="divInfo" class="print">
    	<table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td id="tdTituloPaginaServicios" class="tituloPaginaServicios" colspan="2" >Orden Compra</td>
                <input type="hidden" id="auxiliar" name="auxiliar" value="0" /><!-- si auxiliar vale "0" es una orden de compra, si vale "1" un TOT-->
                <input type="hidden" id="idOrdenTOT" name="idOrdenTOT"  />
            </tr>
            <tr>
                <td align="left" colspan="2">
                    <form id="frmProveedor" name="frmProveedor" style="margin:0">
                        <br />
                        <table border="0" width="100%">
                            <tr>
                                <td>
                                    <table border="0" width="100%">
                                        <tr>
                                            <td width="150" align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Empresa/Sucursal:</td>
                                          	<td align="left" id="tdSelEmpresa">
                                            	<select id="selEmpresa" name="selEmpresa">
													<option value="0">Todas</option>
												</select>
                                          	</td>
                                            <?php 
                                            if (isset($_GET['id']) && $_GET['id'] != "" && $_GET['id'] != 0){
                                            ?>
                                            <td width="150"  align="right" class="tituloCampo" >N&uacute;mero de T.O.T:</td>
                                            <td width="250"><b style="font-size:16px;">
                                             <?php                                              
                                                $rs = mysql_query("SELECT numero_tot FROM sa_orden_tot WHERE id_orden_tot = ".valTpDato($_GET['id'],"int")." LIMIT 1") or die(mysql_error());
                                                $row = mysql_fetch_assoc($rs);
                                                echo $row["numero_tot"];
                                                getBarcode($row["numero_tot"],'clases/temp_codigo/img_codigo_tot');                                                
                                             ?></b>
                                            </td>
                                            <td align="right">
                                            <?php
                                                echo "<img src='clases/temp_codigo/img_codigo_tot.png' />";
                                            }
                                            ?>
                                            </td>
                                            <td align="right">
                                            	<button class="noprint puntero" type="button" onclick="window.print();">Imprimir</button>
                                          	</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <table border="0" width="100%">
                                        <tr>
                                            <td align="center" class="tituloArea" colspan="6">Datos del Proveedor</td>
										</tr>
										<tr>
											<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre / Razon Social:</td>
											<td align="left"><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="15"/> </td>
											<td class="noprint"><button type="button" class="puntero" id="btnInsertarProveedor" name="btnInsertarProveedor" onclick=" $('txtIdProv').value = ''; $('txtNombreProv').value = ''; $('txtContactoProv').value = ''; $('txtEmailContactoProv').value = ''; $('txtDireccionProv').value = ''; $('txtTelefonosProv').value = ''; $('txtFaxProv').value = ''; $('txtCriterioBusqueda').value = '';
                                            xajax_buscarProveedor(xajax.getFormValues('frmBuscar'));" title="Seleccionar Proveedor"><img src="../img/iconos/ico_pregunta.gif"/></button>
                                                                                            <button type="button" style="display:none;" class="puntero" id="btnEditarProveedor" name="btnEditarProveedor" onclick="alert('Advertencia: Si cambia de proveedor el efecto es inmediato \n independientemente  si guarda o no el tot'); xajax_buscarProveedor(xajax.getFormValues('frmBuscar'),1);" title="Editar Proveedor"><img src="../img/iconos/ico_cambio.png"/></button>
											</td>
											<td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="40" /></td>
											<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRIF ?></td>
											<td align="left"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="26"/></td>
										</tr>
										<tr>
											<td align="right" class="tituloCampo" width="17%">Persona Contacto:</td>
											<td align="left" width="18%" colspan="3"><input type="text" id="txtContactoProv" name="txtContactoProv" readonly="readonly" size="26"/></td>
											<td align="right" class="tituloCampo" width="10%">Email:</td>
											<td align="left" width="17%"><input type="text" id="txtEmailContactoProv" name="txtEmailContactoProv" readonly="readonly" size="26"/></td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" rowspan="2"><span class="textoRojoNegrita">*</span>Dirección:</td>
                                            <td align="left" colspan="3" rowspan="2"><textarea cols="59" id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="2"></textarea></td>
                                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                                            <td align="left"><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="26"/></td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo">Fax:</td>
                                            <td align="left"><input type="text" id="txtFaxProv" name="txtFaxProv" readonly="readonly" size="26"/></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <form id="frmDatosVehiculo" name="frmDatosVehiculo">
                        <table border="0" width="100%">
                            <tr>
                                <td valign="top">
                                    <table border="0" width="100%">
										<tr>
											<td align="center" class="tituloArea" colspan="9">Datos del Vehiculo</td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Nro Orden:</td>
                                      		<td align="left" width="11%">
                                            	<!-- numero orden solo para mostrar (nuevo) -->
                                            	<input type="text" id="numeroOrdenMostrar" name="numeroOrdenMostrar" readonly="readonly" size="15" />
                                                <!-- id de la orden a guardar (invisible ahora) -->
												<input type="hidden" id="txtOrden" name="txtOrden" readonly="readonly" size="15" />
											</td>
											<td align="left" width="5%" class="noprint">
                                            	<button type="button" class="puntero" id="btnInsertarVehiculo" name="btnInsertarVehiculo" onclick="document.forms['frmDatosVehiculo'].reset(); $('txtCriterioBusqueda').value = '';
  xajax_buscarVehiculos(xajax.getFormValues('frmBuscar'), document.getElementById('selEmpresa').value);" title="Seleccionar Orden"><img src="../img/iconos/ico_pregunta.gif"/></button>
                                          <button type="button" id="btnEditarVehiculo" class="puntero" style="display:none;" name="btnEditarVehiculo" onclick="alert('Advertencia: Si cambia de orden el efecto es inmediato \n independientemente  si guarda o no el tot, si el tot estaba \n asignado a una orden  deberá volver a asignarlo ');  xajax_buscarVehiculos(xajax.getFormValues('frmBuscar'), document.getElementById('selEmpresa').value, 1)" 
                                                  title="Editar Orden"><img src="../img/iconos/ico_cambio.png"/></button>
                                            </td>
											<td align="right" class="tituloCampo" width="10%"><span class="textoRojoNegrita">*</span>Placa:</td>
											<td align="left" width="11%">
   	    										<input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="16"/>
                                            </td>
                                            <td align="right" class="tituloCampo" width="7%"><span class="textoRojoNegrita">*</span>Chasis:</td>
											<td align="left" width="13%"><input type="text" id="txtChasis" name="txtChasis" readonly="readonly" size="20"/></td>
											<td align="right" class="tituloCampo" width="16%">Unidad Basica:</td>
											<td align="left" width="13%"><input type="text" id="txtUnidadBasica" name="txtUnidadBasica" readonly="readonly" size="20"/></td>
										</tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" >Marca:</td>
                                            <td align="left" colspan="2"><input type="text" id="txtMarca" name="txtMarca" readonly="readonly" size="26"/></td>
                                            <td align="right" class="tituloCampo">Modelo:</td>
                                            <td align="left" ><input type="text" id="txtModelo" name="txtModelo" readonly="readonly" size="16" /></td>
                                            <td align="right" class="tituloCampo">Año:</td>
                                            <td align="left"><input type="text" id="txtAno" name="txtAno" readonly="readonly" size="20"/></td>
                                            <td align="right" class="tituloCampo">Color:</td>
                                            <td align="left"><input type="text" id="txtColor" name="txtColor" readonly="readonly" size="20"/></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
			</tr>
            <tr id="trDatosFactura" style="display:none">
                <td colspan="2">
                    <form id="frmDatosFactura" name="frmDatosFactura">
                        <table border="0" width="100%">
                            <tr>
                            	<td align="center" class="tituloArea" colspan="7">Datos de la Factura</td>
                            </tr>
                            <tr>
                            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Nº Factura Proveedor:</td>
                            	<td align="left" width="17%"><input type="text" id="txtNumeroFacturaProveedor" name="txtNumeroFacturaProveedor" class="inputHabilitado" size="20"/></td>
                            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Nº Control:</td>
                            	<td align="left" width="14%">
						<div style="float:left">
							<input type="text" id="txtNumeroControl" name="txtNumeroControl" class="inputHabilitado" size="20"/>
						</div>
						<div style="float:left">
							<img src="../img/iconos/ico_pregunta.gif" title="Formato Ej.: 00-000000"/>
						</div></td>
                            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Fecha Factura:</td>
                            	<td width="14%" style="white-space:nowrap;">
                            		<div style="float:left"><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" class="inputHabilitado" size="16" readonly="readonly"/></div>
                            		<div style="/*float:left;*/" class="noprint"><img src="../img/iconos/ico_date.png" id="imgFechaProveedor" name="imgFechaProveedor" class="puntero"></div>
                            		<script type="text/javascript">
										Calendar.setup({
										inputField : "txtFechaProveedor",
										 ifFormat : "%d-%m-%Y",
										button : "imgFechaProveedor"
										 });
									</script>
                            	</td>
                            </tr>
                            <tr>
                            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Pago:</td>
                                <td align="left" id="tdSelTipoPago">
                                    <label>
                                        <select name="slctTipoPago" id="slctTipoPago" class="inputHabilitado">
                                            <option value="0">Contado</option>
                                            <option value="1">Credito</option>
                                        </select>
                                    </label>
                                </td>
                            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Aplica Libros</td>
                                <td width="14%" align="left" id="tdSelAplicaLibros">
                                    <label>
                                        <select name="slctAplicaLibros" id="slctAplicaLibros" class="inputHabilitado">
                                            <option value="1" selected="selected">Si</option>
                                            <option value="0">No</option>
                                        </select>
                                    </label>
                                </td>
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Origen:</td>
                                <td>
                                	<div style="float:left"><input type="text" id="txtFechaOrigen" name="txtFechaOrigen" size="16" readonly="readonly" value="<?php echo date("d-m-Y"); ?>"/></div>
                                </td>
                            </tr>
                            <tr>
                                <td width="17%" rowspan="2" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observacion:</td>
                                <td colspan="3" rowspan="2" align="left">
                                	<label>
                                		<textarea name="txtObservacionFactura" id="txtObservacionFactura" class="inputHabilitado" cols="59" rows="2"></textarea>
                                	</label>
                                </td>
							</tr>
                            <tr>
                                <td align="right">&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr id="trRetencionIva" style="display:none">
                                <td colspan="6">
                                    <hr>
                                    <table width="100%">
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="14%">Retención del:</td>
                                        <td width="8%">
                                            <input type="radio" name="rbtRetencion" id="radio" value="1" />No
                                        </td>
                                        <td width="8%">
                                            <input type="radio" name="rbtRetencion" id="radio2" value="2"/>75% 
                                        </td>
                                        <td width="8%">
                                            <input type="radio" name="rbtRetencion" id="radio3" value="3"/>100% 
                                        </td>
                                        <td width="62%">
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
                            <hr>
                        </table>
                    </form>
                </td>
			</tr>
            <tr>
                <td id="tdTrabajosRequeridos" align="left" colspan="2">
                    <form id="frmDetallesTrabajoRequeridos" name="frmDetallesTrabajoRequeridos" onSubmit="return false;" style="margin:0">
                        <table border="0" width="100%">
                            <tr>
                            	<td align="left" class="textoNegrita_12px" colspan="2">
									<hr>
                                	<table width="100%" >
                                    	<tr>
                                        	<td width="50%" class="noprint">
                                            	<button type="button" style="display:none" class="puntero" id="btnInsertarTrabajoRequerido" name="btnInsertarTrabajoRequerido" onclick="$('tblTrabajoRequeridos').style.display = ''; $('divFlotante').style.display = ''; $('divFlotanteTitulo').innerHTML = 'Agregar Detalle'; $('tblListados').style.display = 'none'; centrarDiv($('divFlotante')); document.forms['frmTrabajoRequerido'].reset();" title="Agregar Detalle"><img src="../img/iconos/ico_agregar.gif"/></button>
                                            	<button type="button" style="display:none" class="puntero" id="btnInsertarAccesorios" name="btnInsertarAccesorios" onclick="abrirAccesorios();" title="Agregar Detalle"><img src="../img/iconos/ico_agregar.gif"/></button>
                    &nbsp;
                    <button type="button" id="btnEliminarTrabajoRequerido" name="btnEliminarTrabajoRequerido" onclick="xajax_eliminarTrabajoRequerido(xajax.getFormValues('frmDetallesTrabajoRequeridos'));" title="Eliminar Detalle"><img src="../img/iconos/ico_quitar.gif"/></button>
                                            </td>
                                            <td width="50%" align="right" style="display:none">
                                            	Costo Total<input type="radio" id="idRdo0" name="idRdo" value="0" onclick="xajax_eliminarTrabajoRequeridoForzado(xajax.getFormValues('frmDetallesTrabajoRequeridos')); xajax_listadoDetalles(xajax.getFormValues('frmDetallesTrabajoRequeridos'),$('txtOrden').value); $('hddObjTotalDetalles').value = 0; $('txtMontoExento').value = 0;" />
												Costo Por Detalle<input type="radio" id="idRdo1" name="idRdo" value="1" onclick="xajax_eliminarTrabajoRequeridoForzado(xajax.getFormValues('frmDetallesTrabajoRequeridos')); xajax_listadoDetalles(xajax.getFormValues('frmDetallesTrabajoRequeridos'),$('txtOrden').value); $('hddObjTotalDetalles').value = 1; $('txtMontoExento').value = 0;" />
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
								<td align="center" class="tituloArea" colspan="2">Trabajos Requeridos<input type="hidden" id="hddObj" name="hddObj" /></td>
                            </tr>
                            <tr>
                                <td align="center" class="tituloCampo" width="2%">&nbsp;</td>
                                <td align="center" class="tituloCampo" width="100%">Descripci&oacute;n</td>
							</tr>
							<tr id="trPie">
                            	<td><br /><br /><br /></td>
							</tr>
                        </table>
                    </form>
                </td>
            </tr>
            <tr id="trTotalFactura" style="display:none">
                <td align="right" colspan="2">
                    <form id="frmTotalFactura" name="frmTotalFactura" style="margin:0">
                        <hr>
                        <table border="0" width="100%">
                            <tr>
                                <td align="right" id="tdGastos" valign="top" width="45%"><br><br><br><br><br><br><br><br><br></td>
                                <td rowspan="2" width="55%">
                                    <table border="0" width="100%">
                                        <tr align="right">
                                            <td class="tituloCampo" width="37%"><span class="textoRojoNegrita">*</span>Sub-Total:</td>
                                            <td width="26%"></td>
                                            <td width="12%"></td>
                                            <td align="right" width="25%"><input type="text" id="txtSubTotal" name="txtSubTotal"  class="inputHabilitado" size="17" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onkeyup="xajax_calcularTotal(xajax.getFormValues('frmTotalFactura'),xajax.getFormValues('frmDetallesTrabajoRequeridos'),0);" /></td>
                                        </tr>
                                        <tr align="right">
                                            <td class="tituloCampo" width="37%"><span class="textoRojoNegrita">*</span>Monto Exento:</td>
                                            <td width="26%"></td>
                                            <td width="12%"></td>
                                            <td align="right" width="25%"><input type="text" id="txtMontoExento" name="txtMontoExento" class="inputHabilitado" size="17" style="text-align:right" onkeyup="xajax_calcularTotal(xajax.getFormValues('frmTotalFactura'),xajax.getFormValues('frmDetallesTrabajoRequeridos'),0);" /></td>
                                        </tr>
                                        <tr id="trPieFactura">
                                            <td colspan="4"><hr></td>
                                        </tr>                                        
                                        <tr>
                                        	<td class="tituloCampo" align="right"><span class="textoRojoNegrita">*</span>Retenci&oacute;n ISLR:</td>
                                            <td colspan="3" id="tdRetencionISLR"></td>
                                            
                                        </tr>
                                        <tr>
                                        	<td colspan="4">
                                            	<table width="100%">
                                                	<tr>
                                                        <td class="tituloCampo" align="right" width="120">Base Retenci&oacute;n:</td>
                                                        <td style="white-space:nowrap">
                                                        	<input type="text" id="txtBaseRetencionISLR" name="txtBaseRetencionISLR"  size="12" class="inputHabilitado" onkeypress="return validarSoloNumerosReales(event);" onkeyup="calcularRetencion();"/>
                                                                                                                        
                                                            <input id="hddPorcentajeRetencion" type="text" name="hddPorcentajeRetencion" value="" size="3" readonly="readonly" /> %
                                                            <input id="hddCodigoRetencion" type="hidden" name="hddCodigoRetencion" value=""/>
                                                        </td>                                       
                                                        <td class="tituloCampo" align="right" width="120" style="white-space: nowrap" >Monto Retenido:</td>
                                                        <td align="right">
                                                        	<input type="text" id="txtMontoRetencionISLR" name="txtMontoRetencionISLR" size="17" readonly="readonly" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                    	<td class="tituloCampo" align="right" width="120">Monto Mayor a:</td>
                                                        <td>
                                                        	<input id="hddMontoMayorAplicar" type="text" name="hddMontoMayorAplicar" readonly="readonly" value=""/>
                                                        </td>
                                                        <td class="tituloCampo" align="right" width="120">Sustraendo:</td>
                                                        <td>
                                                        	<input id="hddSustraendoRetencion" type="text" name="hddSustraendoRetencion" size="17" readonly="readonly" value="" />
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                        	<td colspan="4"><hr></td>
                                        </tr>
                                        <tr align="right" id="trNetoOrden">
                                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Neto Factura:</td>
                                            <td></td>
                                            <td></td>
                                            <td align="right">
                                            	<input type="text" id="txtTotalOrden" name="txtTotalOrden" readonly="readonly" size="17" style="text-align:right"/>
                                                <input type="hidden" id="hddObjIva" name="hddObjIva"  />
                                                <input type="hidden" id="hddObjValoresIva" name="hddObjValoresIva" />
                                                <!-- si en 0 es costo total, si es 1 es costo detallado -->
                                                <input type="hidden" id="hddObjTotalDetalles" name="hddObjTotalDetalles" value="0"  />
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="divMsjInfo2">
                                    <table cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                            <td align="center">
                                                <table>
                                                    <tr>
                                                        <td><img src="../img/iconos/accept.png" /></td>
                                                        <td>Gastos que llevan Impuesto</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
						</table>
                    </form>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <form id="frmDatosEmpleado" name="frmDatosEmpleado">
                        <table border="0" width="100%">
                            <tr>
                                <td valign="top">
                                    <table border="0" width="100%">
                                        <tr>
											<td align="center" class="tituloArea" colspan="9">Empleado</td>
                                        </tr>
                                        <tr>
											<td colspan="9">&nbsp;</td>
                                        </tr>
                                        <tr>
                                        	<input type="hidden" id="hddIdUsuario" name="hddIdUsuario" />
                                            <td id="tdNombreEmpleado" align="center" width="33%"><script> xajax_cargarEmpleado();</script></td>
                                            <td id="tdCargoEmpleado" align="center" width="33%"></td>
                                            <td align="center" width="34%">________________________________</td>
                                        </tr>
                                        <tr>
                                            <td id="tdNombreEmpleado" align="center" width="33%">&nbsp;</td>
                                            <td id="tdCargoEmpleado" align="center" width="33%">&nbsp;</td>
                                            <td align="center" width="34%">Firma</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
			</tr>
            <tr>
                <td align="right" width="50%">
                    <hr>
                    <button type="button" id="btnGuardar" name="btnGuardar" class="puntero" onclick="validarTodoForm();">Guardar</button>
                    <?php
                        if($_GET['acc']==0){
                            $boton_cancelar = "'sa_orden_tot_list.php?acc=".$_GET['acc']."','_self'";
                        }else{
                            $boton_cancelar = "'sa_historico_tot.php?acc=".$_GET['acc']."','_self'";
                        }
                    ?>
                    <button type="button" id="btnCancelar" name="btnCancelar"  onclick="window.open(<?php echo $boton_cancelar; ?>);" class="noprint puntero">Cancelar</button>
                </td>
            </tr>
        </table>
    </div>
	
    <div class="noprint" >
	<?php include("menu_serviciosend.inc.php"); ?>
    </div>
    
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	<form id="frmBuscar" name="frmBuscar" onSubmit="return false;">
    <table border="0" id="tblListados" width="980px" style="display:none">
    <tr>
        <td id="tdCriterioBusqueda" align="right" class="tituloCampo">Descripci&oacute;n:</td>
        <td id="tdCriterioBusquedaInput">
            <input type="text" id="txtCriterioBusqueda" name="txtCriterioBusqueda" />
        </td>
        <td id="tdCriterioBusquedaButton">
            <button class="noprint puntero" type="button" id="btnBuscar" name="btnBuscar" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button>
        </td>
    </tr>
    <tr>
    	<td id="tdListado" colspan="3">
        	<table width="100%">
            <tr class="tituloColumna">
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="3">
			<hr>
			<button type="button" class="puntero" onclick="$('divFlotante').style.display='none';" >Cancelar</button>
		</td>
    </tr>
    </table>
    </form>
    <form id="frmTrabajoRequerido" name="frmTrabajoRequerido" style="margin:0">
    <table border="0" id="tblTrabajoRequeridos" width="400px" style="display:none">
        
    <tr>
        <td>
            <table width="100%">
            <tr>
            	<td align="center" class="tituloCampo" width="100%"><span class="textoRojoNegrita">*</span>Descripci&oacute;n:</td>
            <tr>
            </tr>
                <td>
                	<textarea id="txtDescripcionTrabajoRequerido" name="txtDescripcionTrabajoRequerido" class="inputHabilitado" cols="60" rows="3"></textarea>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right" width="15%">
            <hr>
            <button type="button" class="puntero" id="bttGuardarDivFlotante" name="bttGuardarDivFlotante" onclick="validarForm();">Guardar</button>
            <button type="button" class="puntero" id="bttCancelarDivFlotante" name="bttCancelarDivFlotante" onclick="$('divFlotante').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
    </form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">Accesorios</td></tr></table></div>
	
    
    <table border="0" id="tblListadoAccesorios" width="500px" >
        <tr>
            <td align="right" class="tituloCampo">Descripci&oacute;n:</td>
            <td>
                <input type="text" id="txtCriterioBusquedaAccesorios" name="txtCriterioBusquedaAccesorios" />
            </td>
            <td id="tdCriterioBusquedaButton">
                <button class="noprint puntero" type="button" onClick="xajax_buscarAccesorio(document.getElementById('selEmpresa').value, document.getElementById('txtCriterioBusquedaAccesorios').value);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar</td></tr></table></button>
            </td>
        </tr>
        <tr>
            <td id="tdListadoAccesorios" colspan="4">
                
            </td>
        </tr>
        <tr>
            <td align="right" colspan="6">
                <hr/>
                <button type="button" class="puntero" onclick="$('divFlotante2').style.display='none';">Cerrar</button>
            </td>
        </tr>
    </table>
   
</div>


<script language="javascript">
	
if (<?php echo $_GET['accion']; ?> == 0){
	$('btnGuardar').style.display = 'none';
}else{
	$('btnGuardar').style.display = '';
}

xajax_cargarIvas();
xajax_comboRetencionISLR();

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','','selEmpresa','tdSelEmpresa'); 
	
</script>